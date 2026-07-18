<?php

namespace App\Notifications;

use App\Models\Mentoring;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class MentoringScheduled extends Notification
{
    use Queueable;

    public function __construct(public Mentoring $mentoring) {}

    /** @return list<string> */
    public function via(User $notifiable): array
    {
        return ['database', 'webpush'];
    }

    public function toWebPush(mixed $notifiable, mixed $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Mentoring Dijadwalkan')
            ->body("Mentoring {$this->mentoring->topic} dengan {$this->mentoring->manager->name} dijadwalkan pada {$this->mentoring->scheduled_at->translatedFormat('d M Y, H:i')}.")
            ->icon('/icons/icon-192.png')
            ->data(['url' => url("/pegawai/mentorings/{$this->mentoring->id}")]);
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
