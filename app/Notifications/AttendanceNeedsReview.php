<?php

namespace App\Notifications;

use App\Models\Attendance;
use App\Models\Concerns\HasDynamicChannels;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class AttendanceNeedsReview extends Notification implements ShouldQueue
{
    use HasDynamicChannels, Queueable;

    public function __construct(public Attendance $attendance)
    {
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(User $notifiable): array
    {
        $base = ['database', 'webpush'];
        if ($notifiable->role->value === 'hr') {
            $base[] = 'mail';
        }

        return $this->resolveChannels($notifiable, $base);
    }

    public function toWhatsApp(User $notifiable): string
    {
        return "Absensi Perlu Pemeriksaan\n"
            ."Absensi {$this->attendance->employee->name} untuk dinas {$this->attendance->dutyTrip->destination} memerlukan pemeriksaan.\n"
            .'Periksa: '.url("/hr/attendances/{$this->attendance->id}");
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
