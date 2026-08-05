<?php

namespace App\Http\Controllers\Admin\Broadcast;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PromotionNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BroadcastNotificationController extends Controller
{
    // ── Dashboard page ────────────────────────────────────────────────────────

    public function index()
    {
        $stats = [
            'total' => User::count(),
            'customers' => User::where('type', 'customer')->count(),
            'providers' => User::where('type', 'provider')->count(),
            'with_fcm' => User::whereNotNull('fcm_token')->count(),
        ];

        // All users for the specific-users picker (id, name, email, type, fcm)
        $allUsers = User::where('status', 'active')
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'type', 'fcm_token'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'type' => $u->type,
                'has_fcm' => ! empty($u->fcm_token),
            ]);

        // Broadcast history — each distinct send grouped by title+body+sent_at minute
        $history = DB::table('notifications')
            ->where('type', 'App\\Notifications\\PromotionNotification')
            ->selectRaw("
                JSON_UNQUOTE(JSON_EXTRACT(data, '$.title')) as title,
                JSON_UNQUOTE(JSON_EXTRACT(data, '$.body'))  as body,
                JSON_UNQUOTE(JSON_EXTRACT(data, '$.promo_code')) as promo_code,
                COUNT(*) as sent_to,
                SUM(CASE WHEN read_at IS NOT NULL THEN 1 ELSE 0 END) as read_count,
                MIN(created_at) as sent_at
            ")
            ->groupBy('title', 'body', 'promo_code')
            ->orderByDesc('sent_at')
            ->limit(30)
            ->get();

        return view('dashboard.admin.broadcast.index', compact('stats', 'allUsers', 'history'));
    }

    // ── Send broadcast ────────────────────────────────────────────────────────

    public function send(Request $request)
    {
        Log::info('[Broadcast] send() called', ['input' => $request->except('_token')]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:500'],
            'audience' => ['required', 'in:all,customers,providers,with_fcm,specific'],
            'user_ids' => ['required_if:audience,specific', 'array'],
            'user_ids.*' => ['exists:users,id'],
            'send_mail' => ['nullable', 'in:0,1,true,false'],
            // promo_code: validate existence only when a non-empty value is provided
            'promo_code' => ['nullable', 'string', 'max:50'],
            'action_url' => ['nullable', 'string', 'max:500'],
        ]);

        Log::info('[Broadcast] validation passed', ['data' => $data]);

        // Verify promo code exists if provided (soft check — warn but don't block)
        if (! empty($data['promo_code'])) {
            $promoExists = \App\Models\PromoCode::where('code', strtoupper(trim($data['promo_code'])))->exists();
            if (! $promoExists) {
                Log::warning('[Broadcast] promo code not found, sending without it', [
                    'promo_code' => $data['promo_code'],
                ]);
                $data['promo_code'] = null;
            } else {
                $data['promo_code'] = strtoupper(trim($data['promo_code']));
            }
        }

        $users = $this->resolveAudience($data['audience'], $data['user_ids'] ?? []);

        if ($users->isEmpty()) {
            return back()->with('error', __('lang.no_users_matched_audience'));
        }

        $notification = new PromotionNotification(
            title: $data['title'],
            body: $data['body'],
            sendMail: (bool) ($data['send_mail'] ?? false),
            actionUrl: $data['action_url'] ?? null,
            promoCode: $data['promo_code'] ?? null,
        );

        $total = $users->count();
        $succeeded = 0;
        $failed = 0;
        $errors = [];

        Log::info('[Broadcast] ── START ──', [
            'title' => $data['title'],
            'body' => $data['body'],
            'audience' => $data['audience'],
            'total' => $total,
            'admin_id' => auth('admin')->id(),
            'send_mail' => ! empty($data['send_mail']),
        ]);

        foreach ($users as $user) {
            try {
                Log::info('[Broadcast] → notifying user', [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'type' => $user->type,
                    'has_fcm' => ! empty($user->fcm_token),
                ]);

                $user->notify($notification);
                $succeeded++;

                Log::info('[Broadcast] ✓ user notified', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "User #{$user->id} ({$user->email}): ".$e->getMessage();

                Log::error('[Broadcast] ✗ failed for user', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info('[Broadcast] ── END ──', [
            'title' => $data['title'],
            'total' => $total,
            'succeeded' => $succeeded,
            'failed' => $failed,
            'errors' => $errors,
        ]);

        // PRG (Post/Redirect/Get) — redirect prevents browser re-submission on refresh
        if ($failed === 0) {
            $message = "✓ Notification sent to all {$succeeded} user(s) successfully.";

            return redirect()->route('admin.broadcast.index')->with('success', $message);
        } elseif ($succeeded === 0) {
            $message = "✗ Broadcast failed for all {$total} users. Check storage/logs/laravel.log.";

            return redirect()->route('admin.broadcast.index')->with('error', $message)->with('broadcast_errors', $errors);
        } else {
            $message = "⚠ Sent to {$succeeded}/{$total} users. {$failed} failed — check logs.";

            return redirect()->route('admin.broadcast.index')->with('warning', $message)->with('broadcast_errors', $errors);
        }
    }

    // ── API: search users (for specific picker autocomplete) ──────────────────

    public function searchUsers(Request $request): JsonResponse
    {
        $term = $request->get('q', '');
        $type = $request->get('type');   // customer | provider | null = both

        $query = User::where('status', 'active')
            ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
            )
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'email', 'type', 'fcm_token']);

        return response()->json($query->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'type' => $u->type,
            'has_fcm' => ! empty($u->fcm_token),
        ]));
    }

    // ── API test endpoint ─────────────────────────────────────────────────────

    public function test(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'email' => ['nullable', 'email', 'exists:users,email'],
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:500'],
            'send_mail' => ['boolean'],
            'promo_code' => ['nullable', 'string', 'max:50'],
            'action_url' => ['nullable', 'url'],
        ]);

        if (empty($data['user_id']) && empty($data['email'])) {
            return response()->json(['success' => false, 'message' => __('lang.provide_user_id_or_email')], 422);
        }

        $user = isset($data['user_id'])
            ? User::find($data['user_id'])
            : User::where('email', $data['email'])->first();

        if (! $user) {
            return response()->json(['success' => false, 'message' => __('lang.user_not_found')], 404);
        }

        $user->notify(new PromotionNotification(
            title: $data['title'],
            body: $data['body'],
            sendMail: (bool) ($data['send_mail'] ?? false),
            promoCode: $data['promo_code'] ?? null,
            actionUrl: $data['action_url'] ?? null,
        ));

        return response()->json([
            'success' => true,
            'message' => __('lang.test_notification_dispatched'),
            'sent_to' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'type' => $user->type,
                'has_fcm' => ! empty($user->fcm_token),
            ],
            'channels' => [
                'database' => true,
                'fcm' => ! empty($user->fcm_token),
                'mail' => (bool) ($data['send_mail'] ?? false),
            ],
            'notification' => [
                'title' => $data['title'],
                'body' => $data['body'],
                'promo_code' => $data['promo_code'] ?? null,
                'action_url' => $data['action_url'] ?? null,
            ],
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resolveAudience(string $audience, array $userIds = [])
    {
        // unique() prevents duplicate notifications if a user appears in multiple segments
        return match ($audience) {
            'customers' => User::where('type', 'customer')->where('status', 'active')->get()->unique('id'),
            'providers' => User::where('type', 'provider')->where('status', 'active')->get()->unique('id'),
            'with_fcm' => User::whereNotNull('fcm_token')->where('status', 'active')->get()->unique('id'),
            'specific' => User::whereIn('id', $userIds)->where('status', 'active')->get()->unique('id'),
            default => User::where('status', 'active')->get()->unique('id'),
        };
    }
}
