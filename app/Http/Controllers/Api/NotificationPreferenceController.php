<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserNotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    /**
     * GET /api/notification-preferences
     *
     * Returns push_enabled + email_enabled for every supported type.
     * Defaults to true/true when no preference row exists yet.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $saved = UserNotificationPreference::where('user_id', $userId)
            ->get()->keyBy('type');

        $data = collect(UserNotificationPreference::TYPES)
            ->map(function (string $label, string $type) use ($saved) {
                $pref = $saved->get($type);

                return [
                    'type' => $type,
                    'label' => $label,
                    'push_enabled' => $pref ? (bool) $pref->push_enabled : true,
                    'email_enabled' => $pref ? (bool) $pref->email_enabled : true,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * PUT /api/notification-preferences/{type}
     *
     * Updates a single notification type preference.
     * Body: { "push_enabled": true/false, "email_enabled": true/false }
     */
    public function update(Request $request, string $type): JsonResponse
    {
        if (! array_key_exists($type, UserNotificationPreference::TYPES)) {
            return response()->json([
                'success' => false,
                'message' => str_replace(':type', $type, __('lang.unknown_notification_type')),
                'valid_types' => array_keys(UserNotificationPreference::TYPES),
            ], 422);
        }

        $request->validate([
            'push_enabled' => ['sometimes', 'boolean'],
            'email_enabled' => ['sometimes', 'boolean'],
        ]);

        $pref = UserNotificationPreference::firstOrNew([
            'user_id' => $request->user()->id,
            'type' => $type,
        ]);

        if ($request->has('push_enabled')) {
            $pref->push_enabled = $request->boolean('push_enabled');
        }
        if ($request->has('email_enabled')) {
            $pref->email_enabled = $request->boolean('email_enabled');
        }

        // Default unset field to true on first creation
        if (! $pref->exists) {
            $pref->push_enabled = $request->boolean('push_enabled', true);
            $pref->email_enabled = $request->boolean('email_enabled', true);
        }

        $pref->save();

        return response()->json([
            'success' => true,
            'message' => __('lang.preference_updated'),
            'data' => [
                'type' => $pref->type,
                'label' => UserNotificationPreference::TYPES[$type],
                'push_enabled' => (bool) $pref->push_enabled,
                'email_enabled' => (bool) $pref->email_enabled,
            ],
        ]);
    }
}
