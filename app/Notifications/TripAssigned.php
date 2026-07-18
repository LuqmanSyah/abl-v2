<?php

namespace App\Notifications;

use App\Models\DutyTrip;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TripAssigned extends Notification
{
    use Queueable;

    public function __construct(public DutyTrip $trip) {}

    /** @return list<string> */
    public function via(User $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(User $notifiable): array
    {
        return [
            'title' => 'Perintah Dinas Baru',
            'body' => "Anda ditugaskan {$this->trip->destination} oleh {$this->trip->manager->name}.",
            'url' => url("/pegawai/dinas/{$this->trip->id}"),
            'icon' => 'heroicon-o-truck',
        ];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Perintah Dinas: {$this->trip->destination}")
            ->greeting("Halo {$notifiable->name},")
            ->line("Anda mendapat tugas dinas baru:")
            ->line("Tujuan: {$this->trip->destination}")
            ->line("Lokasi: {$this->trip->location_name}")
            ->line("Jadwal: {$this->trip->starts_at->translatedFormat('d M Y, H:i')} – {$this->trip->ends_at->translatedFormat('d M Y, H:i')}")
            ->action('Lihat Dinas', url("/pegawai/dinas/{$this->trip->id}"));
    }
}
