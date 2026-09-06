<?php

namespace App\Http\Controllers\Admin\Subscription;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\StoreSubscriptionRequest;
use App\Http\Resources\Subscription\SubscriptionResource;
use App\Models\AdPackage;
use App\Models\Payment;
use App\Models\PropertyPackage;
use App\Models\Subscription;
use App\Models\User;
use App\Repositories\Interfaces\PaymentGatewayInterface;
use App\Repositories\Interfaces\SubscriptionInterface;
use App\Services\Payment\PaymentMethodFactory;
use App\Support\Cache\HasVersionedCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    use HasVersionedCache;

    public function __construct(
        protected SubscriptionInterface $subscription,
        protected PaymentGatewayInterface $paymentGateway,
    ) {
        $this->middleware('permission:subscriptions.view')->only(['index', 'show']);
        // subscriptions.create intentionally NOT gated behind the permission
        // middleware: store() is dual-purpose — it's also the customer/provider
        // subscription-PURCHASE endpoint, called via Sanctum (routes/api.php:
        // Route::middleware('auth:sanctum')->group(fn () => apiResource('subscriptions'...))).
        // config/permission.php sets defaults.guard='admin' project-wide, so
        // permission:subscriptions.create would check Auth::guard('admin')->user()
        // regardless of which guard actually authenticated the request — a
        // Sanctum-authenticated customer has no Admin-guard Spatie role/permission
        // at all, so this middleware would return a hard 403 for every real
        // customer trying to buy a subscription. Enabling this WILL break
        // subscription purchases in production. If admin-created subscriptions
        // specifically need gating, branch on $request->wantsJson() inside
        // store() instead of using route/controller middleware here.
        $this->middleware('permission:subscriptions.update')->only(['update']);
        $this->middleware('permission:subscriptions.delete')->only(['destroy']);
    }

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $search = $request->get('search');

        $subscriptions = Subscription::with(['user', 'adPackage', 'propertyPackage'])
            ->when($search, fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            )
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        if ($request->wantsJson()) {
            return SubscriptionResource::collection($subscriptions);
        }

        $users = User::all();
        $adPackages = AdPackage::where('status', 'active')->get();
        $propertyPackages = PropertyPackage::where('status', 'active')->get();

        return view('dashboard.admin.subscriptions.index', compact(
            'subscriptions', 'users', 'adPackages', 'propertyPackages', 'search'
        ));
    }

    // -------------------------------------------------------------------------
    // Store — creates subscription + pending payment + returns Geidea URL
    // -------------------------------------------------------------------------

    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Resolve package and price
        $package = $this->resolvePackage($data['type'], $data['package_id']);

        if (! $package) {
            return response()->json(['success' => false, 'message' => __('lang.package_not_found')], 404);
        }

        try {
            $result = DB::transaction(function () use ($data, $package, $request) {
                $userId = $request->wantsJson() ? Auth::id() : ($data['user_id'] ?? Auth::id());
                $user = User::findOrFail($userId);

                // 1. Build the subscription row (status: pending until payment clears)
                $subData = $this->prepareSubscriptionData($data, $package);
                $subData['user_id'] = $userId;
                $subData['amount'] = (float) $package->price;
                $subData['status'] = 'pending';           // activated by callback

                $subscription = Subscription::create($subData);

                // Service fee — keyed by subscription type ('ad' -> ad_package,
                // 'property' -> property_package). Kept separate from
                // Subscription.amount (the pure package price) and tracked
                // only at the Payment level, matching the reservation flow's
                // same principle.
                $serviceFee = \App\Models\ServiceFee::feeFor($data['type'] === 'ad' ? 'ad_package' : 'property_package');
                $chargeAmount = $subscription->amount + $serviceFee;

                // 2. Resolve gateway first so payment_type is correct from creation
                $paymentMethod = $data['payment_method'] ?? 'geidea';
                try {
                    $gateway = PaymentMethodFactory::make($paymentMethod);
                } catch (\InvalidArgumentException $e) {
                    throw new \RuntimeException($e->getMessage());
                }

                // Create a pending Payment row with the correct payment_type
                $payment = Payment::create([
                    'user_id' => $userId,
                    'subscription_id' => $subscription->id,
                    'payment_type' => $paymentMethod,
                    'amount' => $chargeAmount,
                    'status' => 'pending',
                    'phone' => $user->phone,
                    'service_fee_amount' => $serviceFee > 0 ? $serviceFee : null,
                ]);

                $payment->items()->create([
                    'name' => $package->name.' — '.ucfirst($data['type']).' subscription',
                    'item_number' => (string) $subscription->id,
                    'price' => $subscription->amount,
                    'quantity' => 1,
                    'total_amount' => $subscription->amount,
                ]);
                if ($serviceFee > 0) {
                    $payment->items()->create([
                        'name' => __('lang.service_fee'),
                        'item_number' => (string) $subscription->id.'-fee',
                        'price' => $serviceFee,
                        'quantity' => 1,
                        'total_amount' => $serviceFee,
                    ]);
                }

                // Call the selected gateway
                $gatewayResult = $gateway->sendPayment([
                    'amount' => $chargeAmount,
                    'price' => $chargeAmount,
                    'quantity' => 1,
                    'description' => $package->name.' — '.ucfirst($data['type']).' subscription',
                    'currency' => config('services.'.$paymentMethod.'.currency', 'SAR'),
                    'merchantReferenceId' => $payment->reference_id,
                    'customer' => [
                        'name' => $user->name ?? 'Customer',
                        'email' => $user->email,
                        'phoneNumber' => $user->phone,
                    ],
                    'callbackUrl' => url('/api/'.$paymentMethod.'/callback'),
                    'returnUrl' => url('/payment-complete'),
                ]);

                if (! ($gatewayResult['success'] ?? false)) {
                    throw new \RuntimeException(
                        $gatewayResult['message'] ?? 'Payment gateway returned an error.'
                    );
                }

                $payment->update(['payment_id' => $gatewayResult['item_id']]);

                return [
                    'subscription' => $subscription,
                    'payment' => $payment->fresh()->load('items'),
                    'payment_url' => $gatewayResult['payment_url'] ?? $gatewayResult['url'] ?? null,
                    'payment_method' => $paymentMethod,
                ];
            });
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $this->bumpCacheVersion('my_subscriptions');

        if (! $request->wantsJson()) {
            return back()->with('success', __('lang.subscription_created_awaiting_payment_msg'));
        }

        return response()->json([
            'success' => true,
            'message' => __('lang.subscription_created_complete_payment'),
            'data' => [
                'subscription' => new SubscriptionResource($result['subscription']),
                'payment' => [
                    'id' => $result['payment']->id,
                    'reference_id' => $result['payment']->reference_id,
                    'amount' => $result['payment']->amount,
                    'status' => $result['payment']->status,
                ],
                'payment_url' => $result['payment_url'],
                'payment_method' => $result['payment_method'],
            ],
        ], 201);
    }

    // -------------------------------------------------------------------------
    // Show / Edit / Update / Destroy  (unchanged behaviour)
    // -------------------------------------------------------------------------

    public function show(Request $request, $id)
    {
        $subscription = Subscription::with(['user', 'adPackage', 'propertyPackage'])->findOrFail($id);

        return $request->wantsJson()
            ? new SubscriptionResource($subscription)
            : view('dashboard.admin.subscriptions.show', compact('subscription'));
    }

    public function create()
    {
        $users = User::all();
        $adPackages = AdPackage::where('status', 'active')->get();
        $propertyPackages = PropertyPackage::where('status', 'active')->get();

        return view('dashboard.admin.subscriptions.create', compact('users', 'adPackages', 'propertyPackages'));
    }

    public function edit($id)
    {
        $subscription = Subscription::findOrFail($id);
        $users = User::all();
        $adPackages = AdPackage::where('status', 'active')->get();
        $propertyPackages = PropertyPackage::where('status', 'active')->get();

        return view('dashboard.admin.subscriptions.edit', compact(
            'subscription', 'users', 'adPackages', 'propertyPackages'
        ));
    }

    public function update(StoreSubscriptionRequest $request, $id)
    {
        $subscription = Subscription::findOrFail($id);

        $data = $request->validated();
        $package = $this->resolvePackage($data['type'], $data['package_id']);
        $data['amount'] = $package ? (float) $package->price : 0;

        if ($request->wantsJson()) {
            $data['user_id'] = Auth::id();
        }

        $data = $this->prepareSubscriptionData($data, $package);

        $subscription->update($data);

        $this->bumpCacheVersion("my_subscriptions:{$subscription->user_id}");

        return $request->wantsJson()
            ? new SubscriptionResource($subscription)
            : back()->with('success', __('lang.subscription_updated_successfully_msg'));
    }

    public function destroy(Request $request, $id): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $this->subscription->delete($id);

        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => __('lang.deleted_successfully')])
            : back()->with('success', __('lang.subscription_deleted_successfully_msg'));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resolvePackage(string $type, int $packageId): AdPackage|PropertyPackage|null
    {
        return $type === 'property'
            ? PropertyPackage::find($packageId)
            : AdPackage::find($packageId);
    }

    private function prepareSubscriptionData(
        array $data,
        AdPackage|PropertyPackage|null $package
    ): array {
        $data['percentage'] = null;
        $data['count'] = null;
        $data['start_date'] = null;
        $data['end_date'] = null;

        if (! $package) {
            return $data;
        }

        if ($data['type'] === 'property') {
            if ($package->type === 'percentage') {
                $data['percentage'] = $package->percentage;
            } elseif ($package->type === 'time') {
                $data['start_date'] = now()->toDateString();
                $data['end_date'] = now()->addDays($package->duration)->toDateString();
            } elseif ($package->type === 'count') {
                $data['count'] = $package->count;
            }
        } elseif ($data['type'] === 'ad') {
            if ($package->type === 'duration') {
                $data['start_date'] = now()->toDateString();
                $data['end_date'] = now()->addDays($package->duration)->toDateString();
            } elseif ($package->type === 'count') {
                $data['count'] = $package->count;
            }
        }

        return $data;
    }
}
