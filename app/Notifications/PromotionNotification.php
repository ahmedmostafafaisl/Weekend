<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\UserNotificationPreference;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Generic promotion / broadcast notification.
 *
 * Sent by admins to selected users (all, customers, providers, or specific IDs).
 * Delivered via: database (always) + FCM push (if user has token) + mail (optional).
 */
class PromotionNotification extends Notification
{
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly bool $sendMail = false,
        public readonly ?string $actionUrl = null,
        public readonly ?string $imageUrl = null,
        public readonly ?string $promoCode = null,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (UserNotificationPreference::pushEnabled($notifiable->id, 'promotion')) {
            $channels[] = FcmChannel::class;
        }

        if ($this->sendMail && $notifiable->email
            && UserNotificationPreference::emailEnabled($notifiable->id, 'promotion')) {
            $channels[] = 'mail';
        }

        Log::info('[Broadcast] via() — channels resolved', [
            'user_id' => $notifiable->id,
            'email' => $notifiable->email,
            'channels' => $channels,
            'has_fcm' => ! empty($notifiable->fcm_token),
        ]);

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        Log::info('[Broadcast] mail channel — building mail message', [
            'user_id' => $notifiable->id,
            'email' => $notifiable->email,
            'title' => $this->title,
        ]);

        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->body);

        if ($this->promoCode) {
            $mail->line('**Use code:** `'.$this->promoCode.'`');
        }

        if ($this->actionUrl) {
            $mail->action('Open Weekend', $this->actionUrl);
        }

        return $mail->line('Thank you for using Weekend.');
    }

    public function toArray(object $notifiable): array
    {
        Log::info('[Broadcast] database channel — writing notification record', [
            'user_id' => $notifiable->id,
            'title' => $this->title,
        ]);

        return [
            'type' => 'promotion',
            'title' => $this->title,
            'body' => $this->body,
            'promo_code' => $this->promoCode,
            'action_url' => $this->actionUrl,
            'image_url' => $this->imageUrl,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $data = ['type' => 'promotion'];

        if ($this->promoCode) {
            $data['promo_code'] = $this->promoCode;
        }

        if ($this->actionUrl) {
            $data['action_url'] = $this->actionUrl;
        }

        return [
            'title' => $this->title,
            'body' => $this->body,
            'data' => $data,
        ];
    }
}
