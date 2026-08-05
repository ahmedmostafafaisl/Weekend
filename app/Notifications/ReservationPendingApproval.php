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
 * Sent to the venue PROVIDER when a new reservation needs their approval.
 * Contains direct accept / reject links so they can act from the email.
 */
class ReservationPendingApproval extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly UniteReservation $reservation) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (UserNotificationPreference::pushEnabled($notifiable->id, 'reservation_pending_approval')) {
            $channels[] = FcmChannel::class;
        }
        if (UserNotificationPreference::emailEnabled($notifiable->id, 'reservation_pending_approval')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $res = $this->reservation;
        $unite = $res->unite;
        $customer = $res->user;

        $acceptUrl = url("/api/reservations/{$res->id}/approve");
        $rejectUrl = url("/api/reservations/{$res->id}/reject");

        return (new MailMessage)
            ->subject('New Booking Request — Approval Required for '.($unite->name ?? 'Your Venue'))
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A customer has requested to book your venue and is waiting for your approval.')
            ->line('**Customer:** '.($customer->name ?? '–').' ('.$customer->email.')')
            ->line('**Venue:** '.($unite->name ?? '–'))
            ->line('**Date:** '.$res->reservation_date?->format('D, d M Y'))
            ->line('**Period:** '.ucfirst(str_replace('_', ' ', $res->period_type)))
            ->line('**Time:** '.(substr($res->from_time ?? '', 0, 5).' – '.substr($res->to_time ?? '', 0, 5)))
            ->line('**Amount:** '.number_format($res->price, 2).' SAR')
            ->when($res->guest_count, fn ($m) => $m->line('**Guests:** '.$res->guest_count))
            ->when($res->notes, fn ($m) => $m->line('**Special requests:** '.$res->notes))
            ->action('✅ Accept Booking', $acceptUrl)
            ->line('Or to reject: '.$rejectUrl)
            ->line('⚠️ Payment will only be charged after you accept. If you reject, the customer will not be charged.')
            ->line('This request will expire if not actioned within 48 hours.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'reservation_pending_approval',
            'reservation_id' => $this->reservation->id,
            'unite_name' => $this->reservation->unite?->name,
            'customer_name' => $this->reservation->user?->name,
            'reservation_date' => $this->reservation->reservation_date?->format('Y-m-d'),
            'period_type' => $this->reservation->period_type,
            'amount' => $this->reservation->price,
            'guest_count' => $this->reservation->guest_count,
            'notes' => $this->reservation->notes,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $res = $this->reservation;

        return [
            'title' => 'New Booking Request 📋',
            'body' => ($res->user?->name ?? 'A customer').' wants to book '.
                       ($res->unite?->name ?? 'your venue').' on '.
                       $res->reservation_date?->format('d M Y').'. Tap to accept or reject.',
            'data' => [
                'type' => 'reservation_pending_approval',
                'reservation_id' => (string) $res->id,
                'action' => 'approve_or_reject',
            ],
        ];
    }
}
