<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
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