<?php

namespace App\Notifications;

use App\Models\TrainingRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TrainingPending extends Notification
{
    use Queueable;

    public function __construct(public TrainingRequest $request) {}

    /** @return list<string> */
    public function via(User $notifiable): array
    {
        return ['database'];
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
