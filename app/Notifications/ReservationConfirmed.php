<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\UniteReservation;
use App\Models\UserNotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly UniteReservation $reservation) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (UserNotificationPreference::pushEnabled($notifiable->id, 'reservation_confirmed')) {
            $channels[] = FcmChannel::class;
        }
        if (UserNotificationPreference::emailEnabled($notifiable->id, 'reservation_confirmed')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reservation = $this->reservation;
        $unite = $reservation->unite;

        return (new MailMessage)
            ->subject('Booking Confirmed – '.($unite->name ?? 'Your reservation'))
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your booking has been confirmed and payment received.')
            ->line('**Venue:** '.($unite->name ?? '–'))
            ->line('**Date:** '.$reservation->reservation_date->format('D, d M Y'))
            ->line('**Period:** '.ucfirst(str_replace('_', ' ', $reservation->period_type)))
            ->line('**Amount paid:** '.number_format($reservation->price, 2).' EGP')
            ->line('**Reference:** '.($reservation->payment?->reference_id ?? '–'))
            ->action('View Booking', url('/'))
            ->line('Thank you for booking with Weekend.');
    }

    public function toFcm(object $notifiable): array
    {
        $res = $this->reservation;
        $unite = $res->unite;

        return [
            'title' => 'Booking Confirmed ✅',
            'body' => ($unite->name ?? 'Your venue').' on '.$res->reservation_date?->format('d M Y'),
            'data' => [
                'type' => 'reservation_confirmed',
                'reservation_id' => (string) $res->id,
                'reservation_date' => $res->reservation_date?->format('Y-m-d') ?? '',
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'reservation_confirmed',
            'reservation_id' => $this->reservation->id,
            'unite_name' => $this->reservation->unite?->name,
            'reservation_date' => $this->reservation->reservation_date?->format('Y-m-d'),
            'period_type' => $this->reservation->period_type,
            'amount' => $this->reservation->price,
            'reference_id' => $this->reservation->payment?->reference_id,
        ];
    }
}
