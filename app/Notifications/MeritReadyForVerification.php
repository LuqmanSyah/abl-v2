<?php

namespace App\Notifications;

use App\Models\MeritResult;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MeritReadyForVerification extends Notification
{
    use Queueable;

    public function __construct(public MeritResult $merit) {}

    /** @return list<string> */
    public function via(User $notifiable): array
    {
        return ['database'];
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
