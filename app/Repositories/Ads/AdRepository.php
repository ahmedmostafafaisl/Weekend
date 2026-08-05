<?php

namespace App\Repositories\Ads;

use App\Http\Resources\Ads\AdResource;
use App\Models\Ad;
use App\Models\AdView;
use App\Repositories\Interfaces\AdInterface;

class AdRepository implements AdInterface
{
    public function all(array $filters = [])
    {
        $query = Ad::with(['user', 'property']);

        if (! empty($filters['approval_status'])) {
            $query->where('approval_status', $filters['approval_status']);
        }

        return $query->latest()->get();
    }

    public function allActive(array $filters = [])
    {
        $user = auth()->user();

        // Auto-derive the audience filter from the viewer's own gender
        // instead of requiring the caller to pass one explicitly. Unknown
        // gender (guests, or existing users who registered before this
        // field existed) conservatively defaults to 'both' rather than
        // skipping the filter — scopeForUser now treats 'both' as a real
        // restriction (only ads marked 'both'), not a no-op, so this
        // correctly hides gender-specific ads from someone we can't
        // confidently place in either group.
        $audience = $filters['audience'] ?? match ($user?->gender) {
            'male' => 'men',
            'female' => 'women',
            default => 'both',
        };

        return Ad::with('user')
            ->activeNow()
            ->forUser(
                userId: $user?->id,
                city: $filters['city'] ?? null,
                audience: $audience,
                userType: $filters['user_type'] ?? $user?->type ?? null
            )
            ->latest()
            ->get();
    }

    public function find($id)
    {
        return Ad::with(['user', 'property'])->findOrFail($id);
    }

    public function create(array $data)
    {
        $data = $this->handleFiles($data);
        if (($data['type'] ?? null) === 'ad') {
            $data['property_id'] = null;
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        if ($data['is_active']) {
            $data['activated_at'] = now();
            $data['expires_at'] = now()->addDay();
        }

        // BUG FIX / SECURITY: force pending regardless of anything a client
        // might submit — approval_status must never be settable directly
        // through the create request, or a malicious request could just
        // submit approval_status=approved and skip review entirely.
        $data['approval_status'] = 'pending';
        $data['reviewed_by_admin_id'] = null;
        $data['reviewed_at'] = null;
        $data['rejection_note'] = null;

        $targetUsers = $data['target_users'] ?? null;
        unset($data['target_users']);

        $ad = Ad::create($data);

        if (is_array($targetUsers) && count($targetUsers)) {
            $ad->targetUsers()->sync(array_map('intval', $targetUsers));
        }

        // Notify every admin who can review ads — Spatie's permission()
        // scope correctly includes admins with the permission granted
        // directly AND those who have it via a role, matching how
        // permission checks work everywhere else in this codebase.
        $reviewers = \App\Models\Admin::permission('ads.review')->get();
        \Illuminate\Support\Facades\Notification::send($reviewers, new \App\Notifications\AdPendingApproval($ad));

        return $ad;
    }

    public function update($id, array $data)
    {
        $ad = Ad::findOrFail($id);

        $data = $this->handleFiles($data);
        if (($data['type'] ?? null) === 'ad') {
            $data['property_id'] = null;
        }
        if (array_key_exists('is_active', $data)) {
            $data['is_active'] = (bool) $data['is_active'];
        }

        if (! empty($data['is_active'])) {
            $data['activated_at'] = now();
            $data['expires_at'] = now()->addDay();
        }

        $targetUsers = $data['target_users'] ?? null;
        unset($data['target_users']);

        $ad->update($data);

        if (is_array($targetUsers)) {
            $ad->targetUsers()->sync(array_map('intval', $targetUsers));
        }

        return $ad->fresh();
    }

    public function delete($id)
    {
        $ad = Ad::findOrFail($id);

        return $ad->delete();
    }

    public function getAdsForUser($userId)
    {
        $myAds = Ad::with(['user', 'property'])
            ->activeNow()
            ->where('user_id', $userId)
            ->latest()
            ->get();

        $otherAds = Ad::with([
            'user',
            'property',
            'views' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            },
        ])
            ->activeNow()
            ->approved()
            ->where('user_id', '!=', $userId)
            ->latest()
            ->get()
            ->map(function ($ad) {
                $ad->seen = $ad->views->isNotEmpty() && $ad->views->first()?->seen_at !== null;

                return $ad;
            })
            ->sortBy([
                ['seen', 'asc'],
                ['created_at', 'desc'],
            ])
            ->values();

        return [
            'my_ads' => $myAds,
            'other_ads' => $otherAds,
        ];
    }

    public function markAsSeen($adId, $userId)
    {
        $ad = Ad::activeNow()->findOrFail($adId);

        return AdView::updateOrCreate(
            [
                'ad_id' => $ad->id,
                'user_id' => $userId,
            ],
            [
                'seen_at' => now(),
            ]
        );
    }

    protected function handleFiles(array $data): array
    {
        $path = public_path('Ads');

        if (! file_exists($path)) {
            mkdir($path, 0777, true);
        }

        if (request()->hasFile('media')) {
            $files = request()->file('media');
            $file = is_array($files) ? ($files[0] ?? null) : $files;
            if ($file) {
                $mediaName = time().'_media_'.$file->getClientOriginalName();
                $file->move($path, $mediaName);
                $data['media'] = "Ads/{$mediaName}";
            }
        }

        if (request()->hasFile('thumbnail')) {
            $thumbnail = request()->file('thumbnail');
            $thumbnailName = time().'_thumb_'.$thumbnail->getClientOriginalName();
            $thumbnail->move($path, $thumbnailName);
            $data['thumbnail'] = "Ads/{$thumbnailName}";
        }

        return $data;
    }

    public function activate($id, $userId = null)
    {
        $query = Ad::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $ad = $query->findOrFail($id);

        $ad->update([
            'is_active' => true,
            'activated_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);

        return $ad->fresh(['user', 'property']);
    }

    public function getAdsGroupedByUser($userId)
    {
        $ads = Ad::with([
            'user',
            'property',
            'views' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            },
            'comments',
        ])
            ->activeNow()
            ->where(fn ($q) => $q->where('approval_status', 'approved')->orWhere('user_id', $userId))
            ->latest()
            ->get()
            ->map(function ($ad) {
                $ad->seen = $ad->views->isNotEmpty() && $ad->views->first()?->seen_at !== null;

                return $ad;
            });

        $grouped = $ads->groupBy('user_id')
            ->map(function ($userAds) {
                $firstAd = $userAds->first();
                $user = $firstAd?->user;

                $sortedAds = $userAds->sortBy([
                    ['seen', 'asc'],
                    ['created_at', 'desc'],
                ])->values();

                $unseenAdsCount = $sortedAds->where('seen', false)->count();

                return [
                    'user_id' => $user?->id,
                    'user_name' => $user?->name,
                    'user_profile_image' => $user?->photo ? asset($user->photo) : null,
                    'has_unseen_ads' => $unseenAdsCount > 0,
                    'unseen_ads_count' => $unseenAdsCount,
                    'ads' => AdResource::collection($sortedAds)->resolve(),
                ];
            })
            ->sortByDesc('has_unseen_ads')
            ->sortByDesc('unseen_ads_count')
            ->values();

        return $grouped;
    }
}
