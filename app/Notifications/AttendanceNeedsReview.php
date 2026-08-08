<?php

namespace App\Notifications;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AttendanceNeedsReview extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Attendance $attendance)
    {
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(User $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(User $notifiable): array
    {
        return [
            'title' => 'Absensi Dinas Perlu Pemeriksaan',
            'body' => "Absensi dinas {$this->attendance->employee->name} untuk {$this->attendance->dutyTrip->destination} memerlukan pemeriksaan.",
            'url' => url("/hr/attendances/{$this->attendance->id}"),
            'icon' => 'heroicon-o-exclamation-triangle',
        ];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Absensi Dinas Memerlukan Pemeriksaan')
            ->greeting("Halo {$notifiable->name},")
            ->line("Absensi dinas {$this->attendance->employee->name} memerlukan pemeriksaan:")
            ->line("Dinas: {$this->attendance->dutyTrip->destination}")
            ->line("Status: {$this->attendance->status->label()}")
            ->action('Periksa Absensi Dinas', url("/hr/attendances/{$this->attendance->id}"));
    }
}
