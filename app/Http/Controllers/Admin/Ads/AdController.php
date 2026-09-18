<?php

namespace App\Http\Controllers\Admin\Ads;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ads\AdRequest;
use App\Http\Resources\Ads\AdResource;
use App\Models\AppSetting;
use App\Models\User;
use App\Repositories\Interfaces\AdInterface;
use Illuminate\Http\Request;

class AdController extends Controller
{
    public function __construct(protected AdInterface $adRepo) {}

    public function index(Request $request)
    {
        $status = $request->query('status');

        $ads = $request->expectsJson()
            ? $this->adRepo->allActive()
            : $this->adRepo->all($status ? ['approval_status' => $status] : []);

        return $request->expectsJson()
            ? AdResource::collection($ads)
            : view('dashboard.web.ads.index', compact('ads', 'status'));
    }

    public function create()
    {
        $users = User::all();

        return view('dashboard.web.ads.create', compact('users'));
    }

    public function store(AdRequest $request)
    {
        // A user needs an active ad subscription to create a new ad listing —
        // unless the admin has enabled the free-trial flag
        // (allow_ads_without_subscription), in which case the gate is bypassed
        // entirely for the duration of the trial period.
        // Gated by expectsJson() so the admin dashboard web form is always
        // trusted (matching the pattern used by UniteController::store()).
        if ($request->expectsJson()) {
            $freeTrial = AppSetting::get('allow_ads_without_subscription');

            if (! $freeTrial) {
                $user = $request->user();
                if (! $user || ! $user->activeAdSubscription()) {
                    abort(403, __('lang.no_active_ad_subscription'));
                }
            }
        }

        $data = $request->validated();

        $ad = $this->adRepo->create($data);

        return $request->expectsJson()
            ? new AdResource($ad)
            : redirect()->route('ads.index')->with('success', __('lang.ad_created_exclaim'));
    }

    public function show($id, Request $request)
    {
        $ad = $this->adRepo->find($id);
        abort_unless($ad, 404);

        if ($request->expectsJson()) {
            $this->authorize('view', $ad);
        }

        return $request->expectsJson()
            ? new AdResource($ad)
            : view('dashboard.web.ads.show', compact('ad'));
    }

    public function edit($id)
    {
        $ad = $this->adRepo->find($id);
        $users = User::all();

        return view('dashboard.web.ads.edit', compact('ad', 'users'));
    }

    public function update(AdRequest $request, $id)
    {
        $ad = $this->adRepo->find($id);
        abort_unless($ad, 404);

        if ($request->expectsJson()) {
            $this->authorize('update', $ad);
        }

        $data = $request->validated();
        $ad = $this->adRepo->update($id, $data);

        return $request->expectsJson()
            ? new AdResource($ad)
            : redirect()->route('ads.index')->with('success', __('lang.ad_updated_exclaim'));
    }

    public function destroy($id, Request $request)
    {
        $ad = $this->adRepo->find($id);
        abort_unless($ad, 404);

        if ($request->expectsJson()) {
            $this->authorize('delete', $ad);
        }

        $this->adRepo->delete($id);

        return $request->expectsJson()
            ? response()->json(['message' => 'Deleted'])
            : redirect()->route('ads.index')->with('success', __('lang.ad_deleted_exclaim'));
    }

    public function userAds(Request $request)
    {
        $user = $request->user();

        $users = $this->adRepo->getAdsGroupedByUser($user->id);

        return response()->json([
            'users' => $users,
        ]);
    }

    public function markSeen($id, Request $request)
    {
        // markSeen is intentionally open to any authenticated user —
        // the whole point is that any viewer can register having seen an ad.
        // No ownership check is appropriate here.
        $this->adRepo->markAsSeen($id, $request->user()->id);

        return response()->json(['message' => 'Ad marked as seen']);
    }

    public function activate($id, Request $request)
    {
        $ad = $this->adRepo->find($id);
        abort_unless($ad, 404);

        $userId = $request->expectsJson() ? $request->user()->id : null;

        if ($request->expectsJson()) {
            $this->authorize('activate', $ad);
        }

        $ad = $this->adRepo->activate($id, $userId);

        return $request->expectsJson()
            ? new AdResource($ad)
            : redirect()->route('ads.index')->with('success', __('lang.ad_activated_24h'));
    }
}
