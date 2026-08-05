<?php

namespace App\Http\Controllers\Admin\Transfer;

use App\Http\Controllers\Controller;
use App\Models\ProviderTransfer;
use App\Models\TransferPolicy;
use App\Models\TransferRequest;
use App\Models\User;
use Illuminate\Http\Request;

class AdminTransferController extends Controller
{
    // ── Transfer Policy CRUD ─────────────────────────────────────────────────

    public function policyIndex()
    {
        $policies = TransferPolicy::latest()->paginate(20);

        return view('dashboard.admin.transfers.policy.index', compact('policies'));
    }

    public function policyCreate()
    {
        return view('dashboard.admin.transfers.policy.form', ['policy' => null, 'button' => 'Create Policy']);
    }

    public function policyStore(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'transfer_days' => ['required', 'integer', 'min:1'],
            'transfer_methods' => ['required', 'array', 'min:1'],
            'transfer_methods.*' => ['in:bank_transfer,cash,check,digital_wallet'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'platform_fee_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        TransferPolicy::create($data);

        return redirect()->route('admin.transfers.policy.index')->with('success', __('lang.transfer_policy_created_msg'));
    }

    public function policyEdit(TransferPolicy $policy)
    {
        return view('dashboard.admin.transfers.policy.form', ['policy' => $policy, 'button' => 'Save Changes']);
    }

    public function policyUpdate(Request $request, TransferPolicy $policy)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'transfer_days' => ['required', 'integer', 'min:1'],
            'transfer_methods' => ['required', 'array', 'min:1'],
            'transfer_methods.*' => ['in:bank_transfer,cash,check,digital_wallet'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'platform_fee_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', false);
        $policy->update($data);

        return redirect()->route('admin.transfers.policy.index')->with('success', __('lang.transfer_policy_updated_msg'));
    }

    public function policyDestroy(TransferPolicy $policy)
    {
        $policy->delete();

        return back()->with('success', __('lang.policy_deleted_msg'));
    }

    // ── Provider Transfers CRUD ──────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = ProviderTransfer::with(['provider', 'policy'])
            ->when($request->provider_id, fn ($q) => $q->where('user_id', $request->provider_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest();
        $transfers = $query->paginate(25)->withQueryString();
        $providers = User::where('type', 'provider')->orderBy('name')->get(['id', 'name', 'email']);
        $stats = [
            'total_pending' => ProviderTransfer::where('status', 'pending')->sum('net_amount'),
            'total_completed' => ProviderTransfer::where('status', 'completed')->sum('net_amount'),
        ];

        return view('dashboard.admin.transfers.index', compact('transfers', 'providers', 'stats'));
    }

    public function create()
    {
        $providers = User::where('type', 'provider')->orderBy('name')->get(['id', 'name', 'email']);
        $policies = TransferPolicy::where('is_active', true)->get();

        return view('dashboard.admin.transfers.form', ['transfer' => null, 'providers' => $providers, 'policies' => $policies, 'button' => 'Create Transfer']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'transfer_policy_id' => ['nullable', 'exists:transfer_policies,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:bank_transfer,cash,check,digital_wallet'],
            'scheduled_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'reference' => ['nullable', 'string', 'max:200'],
        ]);

        $policy = $data['transfer_policy_id'] ? TransferPolicy::find($data['transfer_policy_id']) : null;
        $taxAmt = $policy ? round($data['amount'] * $policy->tax_rate / 100, 2) : 0;
        $feeAmt = $policy ? round($data['amount'] * $policy->platform_fee_rate / 100, 2) : 0;

        ProviderTransfer::create([
            ...$data,
            'tax_amount' => $taxAmt,
            'platform_fee' => $feeAmt,
            'net_amount' => $data['amount'] - $taxAmt - $feeAmt,
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.transfers.index')->with('success', __('lang.transfer_created_msg'));
    }

    public function edit(ProviderTransfer $transfer)
    {
        $providers = User::where('type', 'provider')->orderBy('name')->get(['id', 'name', 'email']);
        $policies = TransferPolicy::where('is_active', true)->get();

        return view('dashboard.admin.transfers.form', compact('transfer', 'providers', 'policies') + ['button' => 'Save Changes']);
    }

    public function update(Request $request, ProviderTransfer $transfer)
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,processing,completed,failed,cancelled'],
            'method' => ['required', 'in:bank_transfer,cash,check,digital_wallet'],
            'reference' => ['nullable', 'string', 'max:200'],
            'scheduled_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        if ($data['status'] === 'completed' && ! $transfer->transferred_at) {
            $data['transferred_at'] = now();
        }
        $transfer->update($data);

        return redirect()->route('admin.transfers.index')->with('success', __('lang.transfer_updated_msg'));
    }

    public function destroy(ProviderTransfer $transfer)
    {
        $transfer->delete();

        return back()->with('success', __('lang.transfer_deleted_msg'));
    }

    // ── Transfer Requests ────────────────────────────────────────────────────

    public function requests(Request $request)
    {
        $requests = TransferRequest::with('provider')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()->paginate(25);

        return view('dashboard.admin.transfers.requests', compact('requests'));
    }

    public function approveRequest(Request $request, TransferRequest $transferRequest)
    {
        $data = $request->validate([
            'admin_response' => ['nullable', 'string', 'max:500'],
        ]);

        $policy = TransferPolicy::where('is_active', true)->first();
        $amount = $transferRequest->requested_amount;
        $taxAmt = $policy ? round($amount * $policy->tax_rate / 100, 2) : 0;
        $feeAmt = $policy ? round($amount * $policy->platform_fee_rate / 100, 2) : 0;

        $transfer = ProviderTransfer::create([
            'user_id' => $transferRequest->user_id,
            'transfer_policy_id' => $policy?->id,
            'amount' => $amount,
            'tax_amount' => $taxAmt,
            'platform_fee' => $feeAmt,
            'net_amount' => $amount - $taxAmt - $feeAmt,
            'method' => $transferRequest->preferred_method,
            'status' => 'processing',
            'created_by' => auth()->id(),
        ]);

        $transferRequest->update([
            'status' => 'approved',
            'admin_response' => $data['admin_response'] ?? null,
            'transfer_id' => $transfer->id,
        ]);

        return back()->with('success', __('lang.transfer_request_approved_msg'));
    }

    public function rejectRequest(Request $request, TransferRequest $transferRequest)
    {
        $request->validate(['admin_response' => ['required', 'string', 'max:500']]);
        $transferRequest->update(['status' => 'rejected', 'admin_response' => $request->admin_response]);

        return back()->with('success', __('lang.request_rejected_msg'));
    }
}
