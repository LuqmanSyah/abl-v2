<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

abstract class WorkflowNotification extends Notification
{
    use Queueable;

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['database', 'webpush'];
    }

    public function toWebPush(mixed $notifiable, mixed $notification): WebPushMessage
    {
        $payload = $this->payload();

        return (new WebPushMessage)
            ->title($payload['title'])
            ->body($payload['body'])
            ->icon('/icons/icon-192.svg')
            ->data(['url' => $payload['url']]);
    }

    /** @return array{title: string, body: string, url: string, icon: string} */
    public function toDatabase(mixed $notifiable): array
    {
        return $this->payload();
    }

    /** @return array{title: string, body: string, url: string, icon: string} */
    abstract protected function payload(): array;
}
