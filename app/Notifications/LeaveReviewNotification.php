<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\UniteReservation;
use App\Models\UserNotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly UniteReservation $reservation) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (UserNotificationPreference::pushEnabled($notifiable->id, 'leave_review')) {
            $channels[] = FcmChannel::class;
        }
        if (UserNotificationPreference::emailEnabled($notifiable->id, 'leave_review')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reservation = $this->reservation;
        $unite = $reservation->unite;

        return (new MailMessage)
            ->subject('How was your stay at '.($unite->name ?? 'your venue').'?')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('We hope you enjoyed your visit on '.$reservation->reservation_date->format('D, d M Y').'.')
            ->line('**Venue:** '.($unite->name ?? '–'))
            ->line('Your feedback helps other customers and helps '.($unite->name ?? 'this venue').' improve.')
            ->action('Leave a Review', url('/'))
            ->line('Thank you for booking with Weekend.');
    }

    public function toFcm(object $notifiable): array
    {
        $res = $this->reservation;
        $unite = $res->unite;

        return [
            'title' => 'How was your stay? ⭐',
            'body' => 'Tell us about your experience at '.($unite->name ?? 'your venue'),
            'data' => [
                'type' => 'leave_review',
                'reservation_id' => (string) $res->id,
                'unite_id' => (string) $res->unite_id,
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'leave_review',
            'reservation_id' => $this->reservation->id,
            'unite_id' => $this->reservation->unite_id,
            'unite_name' => $this->reservation->unite?->name,
            'reservation_date' => $this->reservation->reservation_date?->format('Y-m-d'),
        ];
    }
}
