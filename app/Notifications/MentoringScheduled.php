<?php

namespace App\Notifications;

use App\Models\Mentoring;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MentoringScheduled extends Notification
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
            'title' => 'Mentoring Dijadwalkan',
            'body' => "Mentoring {$this->mentoring->topic} dengan {$this->mentoring->manager->name} dijadwalkan pada {$this->mentoring->scheduled_at->translatedFormat('d M Y, H:i')}.",
            'url' => url("/pegawai/mentorings/{$this->mentoring->id}"),
            'icon' => 'heroicon-o-calendar',
        ];
    }
}
