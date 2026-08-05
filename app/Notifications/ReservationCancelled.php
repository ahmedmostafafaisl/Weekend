<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\UniteReservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly UniteReservation $reservation,
        public readonly float $refundAmount = 0,
        public readonly bool $isProvider = false,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', FcmChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $res = $this->reservation;
        $unite = $res->unite;
        $date = $res->reservation_date?->format('D, d M Y');
        $period = ucfirst(str_replace('_', ' ', $res->period_type));

        if ($this->isProvider) {
            return (new MailMessage)
                ->subject('Booking Cancelled – '.($unite->name ?? 'Your Venue'))
                ->greeting('Hello '.$notifiable->name.',')
                ->line('A reservation at your venue has been cancelled.')
                ->line('**Venue:** '.($unite->name ?? '–'))
                ->line('**Date:** '.$date)
                ->line('**Period:** '.$period)
                ->line('**Customer:** '.($res->user?->name ?? '–'))
                ->line('The slot is now available for new bookings.')
                ->action('View Dashboard', route('unites.show', $res->unite_id))
                ->line('Thank you for hosting with Weekend.');
        }

        $mail = (new MailMessage)
            ->subject('Booking Cancelled – '.($unite->name ?? 'Your Reservation'))
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your reservation has been cancelled.')
            ->line('**Venue:** '.($unite->name ?? '–'))
            ->line('**Date:** '.$date)
            ->line('**Period:** '.$period);

        if ($this->refundAmount > 0) {
            $mail->line('**Refund:** '.number_format($this->refundAmount, 2).' SAR has been initiated and will appear within 3–5 business days.');
        } else {
            $mail->line('**Refund:** No refund is applicable based on the venue\'s cancellation policy.');
        }

        return $mail
            ->line('**Reference:** '.($res->payment?->reference_id ?? '–'))
            ->action('Browse Venues', url('/'))
            ->line('Thank you for using Weekend.');
    }

    public function toArray(object $notifiable): array
    {
        $res = $this->reservation;

        return [
            'type' => $this->isProvider ? 'reservation_cancelled_provider' : 'reservation_cancelled',
            'reservation_id' => $res->id,
            'unite_name' => $res->unite?->name,
            'reservation_date' => $res->reservation_date?->format('Y-m-d'),
            'period_type' => $res->period_type,
            'refund_amount' => $this->refundAmount,
            'reference_id' => $res->payment?->reference_id,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $res = $this->reservation;
        $unite = $res->unite;

        if ($this->isProvider) {
            return [
                'title' => 'Booking Cancelled ❌',
                'body' => ($res->user?->name ?? 'A customer').' cancelled their booking at '.($unite->name ?? 'your venue').' on '.$res->reservation_date?->format('d M Y'),
                'data' => [
                    'type' => 'reservation_cancelled_provider',
                    'reservation_id' => (string) $res->id,
                ],
            ];
        }

        $body = 'Your booking at '.($unite->name ?? 'the venue').' on '.$res->reservation_date?->format('d M Y').' has been cancelled.';
        if ($this->refundAmount > 0) {
            $body .= ' Refund: '.number_format($this->refundAmount, 2).' SAR.';
        }

        return [
            'title' => 'Booking Cancelled ❌',
            'body' => $body,
            'data' => [
                'type' => 'reservation_cancelled',
                'reservation_id' => (string) $res->id,
                'refund_amount' => (string) $this->refundAmount,
            ],
        ];
    }
}
