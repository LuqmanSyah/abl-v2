<?php

namespace App\Notifications;

use App\Models\ReviewPeriod;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class KpiDeadlineReminder extends Notification
{
    use Queueable;

    public function __construct(public ReviewPeriod $period) {}

    /** @return list<string> */
    public function via(User $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(User $notifiable): array
    {
        return [
            'title' => 'KPI Belum Diisi',
            'body' => "Periode {$this->period->name} memiliki {$this->period->kpiWeight}% bobot KPI. Segera isi target KPI bawahan Anda.",
            'url' => url('/atasan/employee-kpis'),
            'icon' => 'heroicon-o-chart-bar-square',
        ];
    }
}
