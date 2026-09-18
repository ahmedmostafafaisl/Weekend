<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $url)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Log the attempt so any mail config issue is visible in
        // storage/logs/laravel.log immediately (no queue required).
        Log::info('ResetPasswordNotification: building mail', [
            'recipient'    => $notifiable->email,
            'reset_url'    => $this->url,
            'mailer'       => config('mail.default'),
            'from_address' => config('mail.from.address'),
            'host'         => config('mail.mailers.smtp.host'),
            'port'         => config('mail.mailers.smtp.port'),
        ]);

        return (new MailMessage)
            ->subject(__('lang.reset_password_subject'))
            ->greeting(__('lang.reset_password_greeting'))
            ->line(__('lang.reset_password_line_1'))
            ->action(__('lang.reset_password_action'), $this->url)
            ->line(__('lang.reset_password_line_2'))
            ->line(__('lang.reset_password_line_3'))
            ->salutation(__('lang.reset_password_salutation'));
    }
}