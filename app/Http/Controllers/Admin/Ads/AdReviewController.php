<?php

namespace App\Http\Controllers\Admin\Ads;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Notifications\AdReviewed;
use Illuminate\Http\Request;

/**
 * Admin-facing ad approval workflow. Gated by the ads.review permission at
 * the route level (see routes/web.php) — any admin with this permission,
 * whether granted directly or via a role, can review ads regardless of
 * who created them.
 */
class AdReviewController extends Controller
{
    /**
     * List ads currently awaiting review — what an admin lands on after
     * clicking the "new ad needs review" notification, or navigating here
     * directly.
     */
    public function index()
    {
        $ads = Ad::with('user')
            ->where('approval_status', 'pending')
            ->latest()
            ->get();

        return view('dashboard.admin.ads.pending', compact('ads'));
    }

    /**
     * Show a single ad for review — the actual target of the notification's
     * deep link.
     */
    public function show(int $id)
    {
        $ad = Ad::with('user')->findOrFail($id);

        return view('dashboard.admin.ads.review', compact('ad'));
    }

    public function approve(int $id)
    {
        $ad = Ad::findOrFail($id);

        $ad->update([
            'approval_status' => 'approved',
            'reviewed_by_admin_id' => auth('admin')->id(),
            'reviewed_at' => now(),
            'rejection_note' => null,
        ]);

        if ($ad->user) {
            $ad->user->notify(new AdReviewed($ad));
        }

        return redirect()->route('admin.ads.pending')
            ->with('success', __('lang.ad_approved_successfully'));
    }

    public function reject(Request $request, int $id)
    {
        $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $ad = Ad::findOrFail($id);

        $ad->update([
            'approval_status' => 'rejected',
            'reviewed_by_admin_id' => auth('admin')->id(),
            'reviewed_at' => now(),
            'rejection_note' => $request->input('note'),
        ]);

        if ($ad->user) {
            $ad->user->notify(new AdReviewed($ad));
        }

        return redirect()->route('admin.ads.pending')
            ->with('success', __('lang.ad_rejected_successfully'));
    }
}
