<?php

namespace App\Core\Notifications\Messages;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificação in-app simples (título, texto, link). Usada pelo core e pelo seeder demo;
 * os módulos podem criar as suas próprias notificações com o mesmo formato (kind/title/body/link).
 */
class GenericNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $kind,
        private readonly string $title,
        private readonly string $body,
        private readonly ?string $link = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['kind' => $this->kind, 'title' => $this->title, 'body' => $this->body, 'link' => $this->link];
    }
}
