<?php

namespace App\Notifications;

use App\Models\MeritResult;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeritPublished extends Notification
{
    use Queueable;

    public function __construct(public MeritResult $merit) {}

    /** @return list<string> */
    public function via(User $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(User $notifiable): array
    {
        return [
            'title' => 'Hasil Merit Telah Dipublikasikan',
            'body' => "Periode {$this->merit->reviewPeriod->name}: skor total {$this->merit->total_score}, simulasi bonus Rp ".number_format($this->merit->estimated_bonus, 0, ',', '.').' (bukan payroll).',
            'url' => url("/pegawai/merit-results/{$this->merit->id}"),
            'icon' => 'heroicon-o-document-chart-bar',
        ];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Hasil Merit Telah Dipublikasikan')
            ->greeting("Halo {$notifiable->name},")
            ->line("Hasil merit periode {$this->merit->reviewPeriod->name} sudah dipublikasikan.")
            ->line("Skor total: {$this->merit->total_score}")
            ->line('Simulasi bonus: Rp '.number_format($this->merit->estimated_bonus, 0, ',', '.').' (bukan payroll)')
            ->action('Lihat Hasil Merit', url("/pegawai/merit-results/{$this->merit->id}"));
    }
}
