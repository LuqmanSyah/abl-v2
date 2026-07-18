<?php

namespace App\Notifications;

use App\Models\Concerns\HasDynamicChannels;
use App\Models\DutyTrip;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class AttendanceReminder extends Notification
{
    use HasDynamicChannels, Queueable;

    public function __construct(public DutyTrip $trip) {}

    /** @return list<string> */
    public function via(User $notifiable): array
    {
        return $this->resolveChannels($notifiable, ['database', 'mail', 'webpush']);
    }

    public function toWhatsApp(User $notifiable): string
    {
        return "Absensi Dinas\n"
            ."Jangan lupa absen hari ini untuk dinas {$this->trip->destination}.\n"
            ."Lokasi: {$this->trip->location_name}\n"
            ."Absen: " . url("/pegawai/dinas/{$this->trip->id}/absensi");
    }

    public function toWebPush(mixed $notifiable, mixed $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Absensi Dinas')
            ->body("Jangan lupa absen hari ini untuk dinas {$this->trip->destination}.")
            ->icon('/icons/icon-192.png')
            ->data(['url' => url("/pegawai/dinas/{$this->trip->id}/absensi")]);
    }

    public function toDatabase(User $notifiable): array
    {
        return [
            'title' => 'Absensi Dinass',
            'body' => "Jangan lupa absen hari ini untuk dinas {$this->trip->destination}.",
            'url' => url("/pegawai/dinas/{$this->trip->id}/absensi"),
            'icon' => 'heroicon-o-camera',
        ];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Absensi Dinas: {$this->trip->destination}")
            ->greeting("Halo {$notifiable->name},")
            ->line("Hari ini Anda memiliki dinas {$this->trip->destination} dan belum melakukan absensi.")
            ->line("Lokasi: {$this->trip->location_name}")
            ->line("Jadwal: {$this->trip->starts_at->translatedFormat('H:i')} – {$this->trip->ends_at->translatedFormat('H:i')}")
            ->action('Absen Sekarang', url("/pegawai/dinas/{$this->trip->id}/absensi"));
    }
}
