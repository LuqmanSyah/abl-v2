<?php

namespace App\Notifications;

use App\Models\TrainingRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class TrainingPending extends Notification
{
    use Queueable;

    public function __construct(public TrainingRequest $request) {}

    /** @return list<string> */
    public function via(User $notifiable): array
    {
        return ['database', 'webpush'];
    }

    public function toWebPush(mixed $notifiable, mixed $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Pengajuan Pelatihan')
            ->body("{$this->request->employee->name} mengajukan pelatihan {$this->request->training->name}.")
            ->icon('/icons/icon-192.png')
            ->data(['url' => url("/atasan/training-requests/{$this->request->id}")]);
    }

    public function toDatabase(User $notifiable): array
    {
        return [
            'title' => 'Pengajuan Pelatihan',
            'body' => "{$this->request->employee->name} mengajukan pelatihan {$this->request->training->name}.",
            'url' => url("/atasan/training-requests/{$this->request->id}"),
            'icon' => 'heroicon-o-book-open',
        ];
    }
}
