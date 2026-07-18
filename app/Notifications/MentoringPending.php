<?php

namespace App\Notifications;

use App\Models\Mentoring;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MentoringPending extends Notification
{
    use Queueable;

    public function __construct(public Mentoring $mentoring) {}

    /** @return list<string> */
    public function via(User $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(User $notifiable): array
    {
        return [
            'title' => 'Mentoring Perlu Persetujuan',
            'body' => "{$this->mentoring->employee->name} mengajukan mentoring: {$this->mentoring->topic}.",
            'url' => url("/atasan/mentorings/{$this->mentoring->id}"),
            'icon' => 'heroicon-o-academic-cap',
        ];
    }
}
