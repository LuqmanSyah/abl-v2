<?php

namespace App\Notifications;

use App\Enums\UserRole;
use App\Models\Promotion;
use App\Models\User;
use NotificationChannels\WebPush\WebPushMessage;

class PromotionRejected extends WorkflowNotification
{
    public function __construct(public Promotion $promotion) {}

    public function toWebPush(mixed $notifiable, mixed $notification): WebPushMessage
    {
        $payload = $this->payloadFor($notifiable);

        return (new WebPushMessage)
            ->title($payload['title'])
            ->body($payload['body'])
            ->icon('/icons/icon-192.svg')
            ->data(['url' => $payload['url']]);
    }

    public function toDatabase(mixed $notifiable): array
    {
        return $this->payloadFor($notifiable);
    }

    protected function payload(): array
    {
        return $this->payloadFor(null);
    }

    /** @return array{title: string, body: string, url: string, icon: string} */
    private function payloadFor(mixed $notifiable): array
    {
        return [
            'title' => 'Promosi Ditolak',
            'body' => "Usulan promosi {$this->promotion->user->name} ke {$this->promotion->toPosition->title} ditolak.",
            'url' => $notifiable instanceof User && $notifiable->role === UserRole::Employee
                ? url('/app')
                : url('/admin/promotions'),
            'icon' => 'heroicon-o-x-circle',
        ];
    }
}
