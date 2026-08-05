<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\Ad;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to every admin with the ads.review permission when a user (customer
 * or provider) creates a new ad. Admins don't have a UserNotificationPreference
 * system (that model is scoped to the users table only) — sent
 * unconditionally via database + push, matching the same simpler pattern
 * already used for other admin-facing notifications like ReservationCancelled.
 */
class AdPendingApproval extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Ad $ad) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ad = $this->ad;

        return (new MailMessage)
            ->subject('New Ad Awaiting Review')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A new ad has been submitted and needs your review before it can be shown to users.')
            ->line('**Title:** '.($ad->title ?? '–'))
            ->line('**Submitted by:** '.($ad->user?->name ?? '–'))
            ->when($ad->description, fn ($mail) => $mail->line('**Description:** '.$ad->description))
            ->action('Review Ad', url('/admin/ads/'.$ad->id.'/review'))
            ->line('The ad will not be visible to anyone until it is approved.');
    }

    public function toFcm(object $notifiable): array
    {
        $ad = $this->ad;

        return [
            'title' => 'New Ad Needs Review 📋',
            'body' => ($ad->user?->name ?? 'A user').' submitted an ad: "'.($ad->title ?? '').'"',
            'data' => [
                'type' => 'ad_pending_approval',
                'ad_id' => (string) $ad->id,
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $ad = $this->ad;

        return [
            'type' => 'ad_pending_approval',
            'ad_id' => $ad->id,
            'ad_title' => $ad->title,
            'submitted_by' => $ad->user?->name,
            'submitted_by_id' => $ad->user_id,
        ];
    }
}
