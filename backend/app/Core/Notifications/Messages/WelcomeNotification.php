<?php

namespace App\Core\Notifications\Messages;

use App\Core\Auth\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Enviada quando o RH cria a conta de acesso de um colaborador.
 */
class WelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    public function via(User $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $url = (new ResetPassword($this->token))->toMail($notifiable)->actionUrl;

        return (new MailMessage)
            ->subject(__('notifications.welcome.subject', ['company' => tenant()?->name]))
            ->greeting(__('notifications.welcome.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.welcome.line'))
            ->action(__('notifications.welcome.action'), $url);
    }

    public function toArray(User $notifiable): array
    {
        return [
            'kind' => 'welcome',
            'title' => __('notifications.welcome.title'),
            'body' => __('notifications.welcome.body', ['company' => tenant()?->name]),
            'link' => '/perfil',
        ];
    }
}
