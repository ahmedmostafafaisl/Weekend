<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\Subscription;
use App\Models\UserNotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionActivated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Subscription $subscription) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (UserNotificationPreference::pushEnabled($notifiable->id, 'subscription_activated')) {
            $channels[] = FcmChannel::class;
        }
        if (UserNotificationPreference::emailEnabled($notifiable->id, 'subscription_activated')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $sub = $this->subscription;
        $package = $sub->type === 'ad' ? $sub->adPackage : $sub->propertyPackage;

        return (new MailMessage)
            ->subject('Subscription Activated — '.($package?->name ?? 'Your plan'))
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your subscription has been activated and payment received.')
            ->line('**Plan:** '.($package?->name ?? '—'))
            ->line('**Type:** '.ucfirst($sub->type))
            ->line('**Amount paid:** '.number_format($sub->amount, 2).' EGP')
            ->when($sub->start_date, fn ($m) => $m->line('**Valid from:** '.$sub->start_date->format('d M Y')))
            ->when($sub->end_date, fn ($m) => $m->line('**Valid until:** '.$sub->end_date->format('d M Y')))
            ->when($sub->count, fn ($m) => $m->line('**Ad credits:** '.$sub->count))
            ->action('Go to Dashboard', url('/'))
            ->line('Thank you for subscribing to Weekend.');
    }

    public function toFcm(object $notifiable): array
    {
        $sub = $this->subscription;

        return [
            'title' => 'Subscription Activated 🎉',
            'body' => 'Your '.($sub->type ?? '').' subscription is now active.',
            'data' => [
                'type' => 'subscription_activated',
                'subscription_id' => (string) $sub->id,
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription_activated',
            'subscription_id' => $this->subscription->id,
            'package_type' => $this->subscription->type,
            'amount' => $this->subscription->amount,
            'start_date' => $this->subscription->start_date?->format('Y-m-d'),
            'end_date' => $this->subscription->end_date?->format('Y-m-d'),
        ];
    }
}
