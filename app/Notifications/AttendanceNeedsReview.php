<?php

namespace App\Notifications;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class AttendanceNeedsReview extends Notification
{
    use Queueable;

    public function __construct(public Attendance $attendance) {}

    /** @return list<string> */
    public function via(User $notifiable): array
    {
        $channels = ['database', 'webpush'];
        if ($notifiable->role->value === 'hr') {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toWebPush(mixed $notifiable, mixed $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Absensi Perlu Pemeriksaan')
            ->body("Absensi {$this->attendance->employee->name} untuk dinas {$this->attendance->dutyTrip->destination} memerlukan pemeriksaan.")
            ->icon('/icons/icon-192.png')
            ->data(['url' => url("/hr/attendances/{$this->attendance->id}")]);
    }

    public function toDatabase(User $notifiable): array
    {
        return [
            'title' => 'Absensi Perlu Pemeriksaan',
            'body' => "Absensi {$this->attendance->employee->name} untuk dinas {$this->attendance->dutyTrip->destination} memerlukan pemeriksaan.",
            'url' => url("/hr/attendances/{$this->attendance->id}"),
            'icon' => 'heroicon-o-exclamation-triangle',
        ];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Absensi Memerlukan Pemeriksaan')
            ->greeting("Halo {$notifiable->name},")
            ->line("Absensi {$this->attendance->employee->name} memerlukan pemeriksaan:")
            ->line("Dinas: {$this->attendance->dutyTrip->destination}")
            ->line("Status: {$this->attendance->status->label()}")
            ->action('Periksa Absensi', url("/hr/attendances/{$this->attendance->id}"));
    }
}
