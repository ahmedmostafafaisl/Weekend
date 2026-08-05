<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\UniteReservation;
use App\Models\UserNotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the venue PROVIDER when a customer completes payment for a booking.
 */
class NewReservationReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly UniteReservation $reservation) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (UserNotificationPreference::pushEnabled($notifiable->id, 'new_reservation_received')) {
            $channels[] = FcmChannel::class;
        }
        if (UserNotificationPreference::emailEnabled($notifiable->id, 'new_reservation_received')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reservation = $this->reservation;
        $unite = $reservation->unite;
        $customer = $reservation->user;

        $mail = (new MailMessage)
            ->subject('New Booking Received – '.($unite->name ?? 'Your venue'))
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A customer has completed payment for a new booking at your venue.')
            ->line('**Customer:** '.($customer->name ?? '–'))
            ->line('**Venue:** '.($unite->name ?? '–'))
            ->line('**Date:** '.$reservation->reservation_date->format('D, d M Y'))
            ->line('**Period:** '.ucfirst(str_replace('_', ' ', $reservation->period_type)))
            ->line('**Amount:** '.number_format($reservation->price, 2).' EGP');

        if ($reservation->guest_count) {
            $mail->line('**Guests:** '.$reservation->guest_count);
        }

        if ($reservation->notes) {
            $mail->line('**Special requests:** '.$reservation->notes);
        }

        return $mail
            ->action('View in Dashboard', url('/'))
            ->line('Log in to your dashboard to manage this booking.');
    }

    public function toFcm(object $notifiable): array
    {
        $res = $this->reservation;
        $unite = $res->unite;

        return [
            'title' => 'New Booking Received 📋',
            'body' => ($res->user?->name ?? 'A customer').' booked '.($unite->name ?? 'your venue').' on '.$res->reservation_date?->format('d M Y'),
            'data' => [
                'type' => 'new_reservation',
                'reservation_id' => (string) $res->id,
                'reservation_date' => $res->reservation_date?->format('Y-m-d') ?? '',
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_reservation_received',
            'reservation_id' => $this->reservation->id,
            'customer_name' => $this->reservation->user?->name,
            'unite_name' => $this->reservation->unite?->name,
            'reservation_date' => $this->reservation->reservation_date?->format('Y-m-d'),
            'period_type' => $this->reservation->period_type,
            'amount' => $this->reservation->price,
            'guest_count' => $this->reservation->guest_count,
            'notes' => $this->reservation->notes,
        ];
    }
}
