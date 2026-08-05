<?php

namespace App\Http\Controllers\Admin\Ads;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ads\AdRequest;
use App\Http\Resources\Ads\AdResource;
use App\Models\User;
use App\Repositories\Interfaces\AdInterface;
use Illuminate\Http\Request;

class AdController extends Controller
{
    public function __construct(protected AdInterface $adRepo) {}

    public function index(Request $request)
    {
        $ads = $request->expectsJson()
            ? $this->adRepo->allActive()
            : $this->adRepo->all();

        return $request->expectsJson()
            ? AdResource::collection($ads)
            : view('dashboard.web.ads.index', compact('ads'));
    }

    public function create()
    {
        $users = User::all();

        return view('dashboard.web.ads.create', compact('users'));
    }

    public function store(AdRequest $request)
    {
        $data = $request->validated();

        $ad = $this->adRepo->create($data);

        return $request->expectsJson()
            ? new AdResource($ad)
            : redirect()->route('ads.index')->with('success', __('lang.ad_created_exclaim'));
    }

    public function show($id, Request $request)
    {
        $ad = $this->adRepo->find($id);

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
        $data = $request->validated();
        $ad = $this->adRepo->update($id, $data);

        return $request->expectsJson()
            ? new AdResource($ad)
            : redirect()->route('ads.index')->with('success', __('lang.ad_updated_exclaim'));
    }

    public function destroy($id, Request $request)
    {
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
        $this->adRepo->markAsSeen($id, $request->user()->id);

        return response()->json([
            'message' => 'Ad marked as seen',
        ]);
    }

    public function activate($id, Request $request)
    {
        $userId = $request->expectsJson() ? $request->user()->id : null;

        $ad = $this->adRepo->activate($id, $userId);

        return $request->expectsJson()
            ? new AdResource($ad)
            : redirect()->route('ads.index')->with('success', __('lang.ad_activated_24h'));
    }
}
