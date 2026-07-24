<?php

namespace App\Console\Commands;

use App\Models\ReviewPeriod;
use App\Models\User;
use App\Notifications\KpiDeadlineReminder;
use Illuminate\Console\Command;

class RemindKpi extends Command
{
    protected $signature = 'merit:remind-kpi {--days=7 : Jumlah hari sebelum deadline mulai reminder}';
    protected $description = 'Kirim pengingat KPI ke atasan yang belum input KPI bawahan';

    public function handle(): int
    {
        $daysBeforeDeadline = max(1, (int) $this->option('days'));
        $cutoff = now()->addDays($daysBeforeDeadline);

        $periods = ReviewPeriod::where('is_active', true)
            ->where('ends_at', '<=', $cutoff)
            ->get();

        if ($periods->isEmpty()) {
            $this->info('Tidak ada periode dengan deadline mendekati.');

            return 0;
        }

        $sent = 0;

        foreach ($periods as $period) {
            $managers = User::where('role', \App\Enums\UserRole::Manager)
                ->whereHas('subordinates', fn ($q) => $q
                    ->where('is_active', true)
                    ->whereDoesntHave('employeeKpis', fn ($q) => $q
                        ->where('review_period_id', $period->id)
                    )
                )
                ->get();

            foreach ($managers as $manager) {
                $manager->notify(new KpiDeadlineReminder($period));
                $sent++;
            }
        }

        $this->info("{$sent} pengingat KPI terkirim ke atasan.");

        return 0;
    }
}
