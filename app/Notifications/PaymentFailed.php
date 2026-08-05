<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\Payment;
use App\Models\UserNotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Payment $payment) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (UserNotificationPreference::pushEnabled($notifiable->id, 'payment_failed')) {
            $channels[] = FcmChannel::class;
        }
        if (UserNotificationPreference::emailEnabled($notifiable->id, 'payment_failed')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment Failed – Reference: '.$this->payment->reference_id)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Unfortunately your payment could not be processed.')
            ->line('**Reference:** '.$this->payment->reference_id)
            ->line('**Amount:** '.number_format($this->payment->amount, 2).' EGP')
            ->line('Please try again or contact support if the problem persists.')
            ->action('Try Again', url('/'))
            ->line('We apologise for the inconvenience.');
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'Payment Failed ❌',
            'body' => 'Your payment '.$this->payment->reference_id.' could not be processed. Please try again.',
            'data' => [
                'type' => 'payment_failed',
                'payment_id' => (string) $this->payment->id,
                'reference_id' => $this->payment->reference_id,
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_failed',
            'payment_id' => $this->payment->id,
            'reference_id' => $this->payment->reference_id,
            'amount' => $this->payment->amount,
            'reservation_id' => $this->payment->reservation_id,
        ];
    }
}
