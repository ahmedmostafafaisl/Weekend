<?php

namespace App\Http\Controllers\Admin\Ads;

use App\Http\Controllers\Controller;
use App\Http\Resources\Ads\AdCommentResource;
use App\Models\Ad;
use App\Models\AdComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdCommentController extends Controller
{
    /**
     * GET /api/ads/{ad}/comments
     *
     * Public — any user (or guest) can see visible comments on an ad.
     * The ad owner also sees hidden comments (with is_visible = false).
     *
     * Query params:
     *   per_page — 1-50 (default 20)
     */
    public function index(Request $request, int $adId): JsonResponse
    {
        $ad = Ad::findOrFail($adId);
        $user = $request->user();

        $query = $ad->comments()->with('user');

        // Ad owner sees all comments (including disabled ones)
        // Everyone else only sees visible comments
        $isOwner = $user && $user->id === $ad->user_id;
        if (! $isOwner) {
            $query->where('is_visible', true);
        }

        $perPage = min((int) ($request->per_page ?? 20), 50);
        $comments = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'is_owner' => $isOwner,
            'data' => AdCommentResource::collection($comments->items()),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    /**
     * POST /api/ads/{ad}/comments
     *
     * Any authenticated user can comment on any active ad.
     *
     * Body: { "body": "Great venue!" }
     */
    public function store(Request $request, int $adId): JsonResponse
    {
        $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:500'],
        ]);

        $ad = Ad::findOrFail($adId);

        $comment = AdComment::create([
            'ad_id' => $ad->id,
            'user_id' => $request->user()->id,
            'body' => $request->body,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comment posted.',
            'data' => new AdCommentResource($comment->load('user')),
        ], 201);
    }

    /**
     * DELETE /api/ads/{ad}/comments/{comment}
     *
     * The comment author can delete their own comment.
     * The ad owner can also delete any comment on their ad.
     */
    public function destroy(Request $request, int $adId, int $commentId): JsonResponse
    {
        $ad = Ad::findOrFail($adId);
        $comment = AdComment::where('ad_id', $ad->id)->findOrFail($commentId);
        $user = $request->user();

        $isAuthor = $comment->user_id === $user->id;
        $isAdOwner = $ad->user_id === $user->id;

        if (! $isAuthor && ! $isAdOwner) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own comments.',
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted.',
        ]);
    }

    /**
     * PATCH /api/ads/{ad}/comments/{comment}/toggle
     *
     * Only the AD OWNER can toggle a comment's visibility.
     * Hidden comments (is_visible = false) are invisible to all other users.
     */
    public function toggle(Request $request, int $adId, int $commentId): JsonResponse
    {
        $ad = Ad::findOrFail($adId);
        $comment = AdComment::where('ad_id', $ad->id)->findOrFail($commentId);

        if ($ad->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the ad owner can hide or show comments.',
            ], 403);
        }

        $comment->update(['is_visible' => ! $comment->is_visible]);

        return response()->json([
            'success' => true,
            'is_visible' => $comment->is_visible,
            'message' => $comment->is_visible ? 'Comment is now visible.' : 'Comment hidden.',
            'data' => new AdCommentResource($comment->load('user')),
        ]);
    }

    /**
     * GET /api/ads/{ad}/comments/my
     *
     * Authenticated user sees only their own comments on this ad
     * (both visible and hidden — it's their own content).
     */
    public function myComments(Request $request, int $adId): JsonResponse
    {
        $ad = Ad::findOrFail($adId);

        $comments = AdComment::where('ad_id', $ad->id)
            ->where('user_id', $request->user()->id)
            ->with('user')
            ->latest()
            ->paginate(min((int) ($request->per_page ?? 20), 50));

        return response()->json([
            'success' => true,
            'data' => AdCommentResource::collection($comments->items()),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }
}
