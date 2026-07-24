<?php

namespace App\Notifications;

use App\Models\MeritResult;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class MeritReadyForVerification extends Notification
{
    use Queueable;

    public function __construct(public MeritResult $merit) {}

    /** @return list<string> */
    public function via(User $notifiable): array
    {
        return ['database', 'webpush'];
    }

    public function toWebPush(mixed $notifiable, mixed $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Hasil Merit Siap Diverifikasi')
            ->body("Hasil merit {$this->merit->employee->name} periode {$this->merit->reviewPeriod->name} siap diverifikasi.")
            ->icon('/icons/icon-192.png')
            ->data(['url' => url("/atasan/merit-results/{$this->merit->id}")]);
    }

    public function toDatabase(User $notifiable): array
    {
        return [
            'title' => 'Hasil Merit Siap Diverifikasi',
            'body' => "Hasil merit {$this->merit->employee->name} periode {$this->merit->reviewPeriod->name} siap diverifikasi.",
            'url' => url("/atasan/merit-results/{$this->merit->id}"),
            'icon' => 'heroicon-o-clipboard-document-check',
        ];
    }
}
