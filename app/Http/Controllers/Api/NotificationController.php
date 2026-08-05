<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * GET /api/notifications
     * Paginated list of all notifications for the auth user.
     * Query: per_page (1-50), unread_only (bool)
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->notifications();

        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        $perPage = min((int) ($request->per_page ?? 20), 50);
        $notifications = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'data' => collect($notifications->items())->map(fn ($n) => $this->formatNotification($n)),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * Derive a human-readable title + body for every stored notification type.
     * The FCM channel stores title/body separately; the database channel stores
     * only the raw data fields. We reconstruct title & body from those fields
     * so the mobile list screen and push notification detail match.
     */
    private function formatNotification(object $n): array
    {
        $d = is_array($n->data) ? $n->data : json_decode($n->data, true);
        $type = $d['type'] ?? '';

        [$title, $body] = match ($type) {

            'new_reservation_received' => [
                'حجز جديد 📋',
                ($d['customer_name'] ?? 'عميل').' حجز '
                    .($d['unite_name'] ?? 'المنشأة')
                    .' في '.($d['reservation_date'] ?? ''),
            ],

            'reservation_confirmed' => [
                'تم تأكيد الحجز ✅',
                ($d['unite_name'] ?? 'المنشأة').' — '.($d['reservation_date'] ?? ''),
            ],

            'reservation_cancelled',
            'reservation_cancelled_provider' => [
                'تم إلغاء الحجز ❌',
                ($d['cancel_reason'] ?? '') !== ''
                    ? ($d['unite_name'] ?? '').' — '.($d['cancel_reason'] ?? '')
                    : ($d['unite_name'] ?? 'الحجز').' تم إلغاؤه',
            ],

            'reservation_pending_approval' => [
                'طلب حجز جديد 📋',
                ($d['customer_name'] ?? 'عميل').' يريد حجز '
                    .($d['unite_name'] ?? 'المنشأة')
                    .' في '.($d['reservation_date'] ?? ''),
            ],

            'payment_failed' => [
                'فشل الدفع ❌',
                'رقم المرجع: '.($d['reference_id'] ?? '').' — يرجى المحاولة مرة أخرى.',
            ],

            'subscription_activated' => [
                'تم تفعيل الاشتراك 🎉',
                match ($d['package_type'] ?? '') {
                    'property' => 'تم تفعيل اشتراك العقار بنجاح.',
                    'ad' => 'تم تفعيل اشتراك الإعلان بنجاح.',
                    default => 'تم تفعيل اشتراكك بنجاح.',
                },
            ],

            'promotion' => [
                $d['title'] ?? 'عرض جديد',
                $d['body'] ?? '',
            ],

            'leave_review' => [
                'قيّم تجربتك ⭐',
                'كيف كانت تجربتك في '.($d['unite_name'] ?? 'المنشأة').'؟ شاركنا رأيك.',
            ],

            default => [
                'إشعار',
                '',
            ],
        };

        // Inject title + body into data so both top-level fields and data{} have them
        $d['title'] = $title;
        $d['body'] = $body;

        return [
            'id' => $n->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $d,
            'is_read' => ! is_null($n->read_at),
            'read_at' => $n->read_at,
            'created_at' => $n->created_at,
        ];
    }

    /**
     * GET /api/notifications/unread-count
     * Lightweight endpoint for mobile badge — returns just the count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * POST /api/notifications/{id}/read
     * Mark a single notification as read.
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => __('lang.notification_marked_read'),
        ]);
    }

    /**
     * POST /api/notifications/read-all
     * Mark all unread notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $count = $request->user()->unreadNotifications()->count();
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "Marked {$count} notification(s) as read.",
            'marked' => $count,
        ]);
    }

    /**
     * DELETE /api/notifications/{id}
     * Delete a single notification.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => __('lang.notification_deleted_msg'),
        ]);
    }
}
