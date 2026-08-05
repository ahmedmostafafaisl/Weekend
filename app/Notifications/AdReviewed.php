<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\Ad;
use App\Models\UserNotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the ad's owner (a regular User — customer or provider) once an
 * admin has reviewed it, covering both outcomes: approved and rejected.
 * $this->ad->approval_status and $this->ad->rejection_note reflect the
 * actual decision at the time this notification was dispatched.
 */
class AdReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Ad $ad) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (UserNotificationPreference::pushEnabled($notifiable->id, 'ad_reviewed')) {
            $channels[] = FcmChannel::class;
        }
        if (UserNotificationPreference::emailEnabled($notifiable->id, 'ad_reviewed')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ad = $this->ad;
        $isApproved = $ad->approval_status === 'approved';

        $mail = (new MailMessage)
            ->subject($isApproved ? 'Your Ad Has Been Approved ✅' : 'Your Ad Was Not Approved')
            ->greeting('Hello '.$notifiable->name.',');

        if ($isApproved) {
            $mail->line('Good news — your ad has been reviewed and approved.')
                ->line('**Title:** '.($ad->title ?? '–'))
                ->line('It is now visible to other users.')
                ->action('View Your Ad', url('/'));
        } else {
            $mail->line('Your ad was reviewed and could not be approved at this time.')
                ->line('**Title:** '.($ad->title ?? '–'));

            if ($ad->rejection_note) {
                $mail->line('**Reason:** '.$ad->rejection_note);
            }

            $mail->line('You\'re welcome to update it and submit it again.')
                ->action('Edit Your Ad', url('/'));
        }

        return $mail;
    }

    public function toFcm(object $notifiable): array
    {
        $ad = $this->ad;
        $isApproved = $ad->approval_status === 'approved';

        return [
            'title' => $isApproved ? 'Ad Approved ✅' : 'Ad Not Approved ❌',
            'body' => $isApproved
                ? 'Your ad "'.($ad->title ?? '').'" is now live.'
                : 'Your ad "'.($ad->title ?? '').'" was not approved.'.($ad->rejection_note ? ' Reason: '.$ad->rejection_note : ''),
            'data' => [
                'type' => 'ad_reviewed',
                'ad_id' => (string) $ad->id,
                'approval_status' => $ad->approval_status,
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $ad = $this->ad;

        return [
            'type' => 'ad_reviewed',
            'ad_id' => $ad->id,
            'ad_title' => $ad->title,
            'approval_status' => $ad->approval_status,
            'rejection_note' => $ad->rejection_note,
        ];
    }
}
