<?php

namespace App\Http\Controllers\Admin\Ads;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ads\AdRequest;
use App\Models\Ad;
use App\Models\User;
use Illuminate\Http\Request;

class AdminAdController extends Controller
{
    public function __construct(protected \App\Repositories\Interfaces\AdInterface $adRepo) {}

    public function index(Request $request)
    {
        $query = Ad::with('user')->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('title', 'like', "%{$s}%")
                ->orWhere('city', 'like', "%{$s}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%"))
            );
        }
        if ($request->filled('target_audience')) {
            $query->where('target_audience', $request->target_audience);
        }
        if ($request->filled('target_user_type')) {
            $query->where('target_user_type', $request->target_user_type);
        }
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true)->where('expires_at', '>', now());
            } else {
                $query->where(fn ($q) => $q->where('is_active', false)->orWhere('expires_at', '<=', now())->orWhereNull('expires_at'));
            }
        }

        $ads = $query->paginate(25)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name', 'email', 'type']);

        return view('dashboard.admin.ads.index', compact('ads', 'users'));
    }

    public function create()
    {
        $users = User::orderBy('type')->orderBy('name')->get(['id', 'name', 'email', 'type']);
        $ad = null;

        return view('dashboard.admin.ads.create', compact('users', 'ad'));
    }

    public function store(AdRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        unset($data['target_users']);

        // Delegate to repo — handleFiles() stores single media file + thumbnail
        // For admin multi-file: store first file only (matches existing single-string schema)
        $this->adRepo->create($data);

        return redirect()->route('admin.ads.index')->with('success', __('lang.ad_created_successfully_msg'));
    }

    public function show(Ad $ad)
    {
        $ad->load(['user', 'views', 'comments.user']);
        $users = User::orderBy('name')->get(['id', 'name', 'email', 'type']);

        return view('dashboard.admin.ads.show', compact('ad', 'users'));
    }

    public function edit(Ad $ad)
    {
        $users = User::orderBy('type')->orderBy('name')->get(['id', 'name', 'email', 'type']);

        return view('dashboard.admin.ads.edit', compact('ad', 'users'));
    }

    public function update(AdRequest $request, Ad $ad)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', false);
        unset($data['target_users']);

        $this->adRepo->update($ad->id, $data);

        return redirect()->route('admin.ads.index')->with('success', __('lang.ad_updated_successfully_msg'));
    }

    public function destroy(Ad $ad)
    {
        $ad->delete();

        return redirect()->route('admin.ads.index')->with('success', __('lang.ad_deleted_msg'));
    }
}
