<?php

namespace App\Console\Commands;

use App\Models\ReviewPeriod;
use App\Services\MeritBatchCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculateMerit extends Command
{
    protected $signature = 'merit:calculate {--period= : ID ReviewPeriod}';

    protected $description = 'Hitung merit untuk semua periode aktif';

    public function handle(MeritBatchCalculator $calculator): int
    {
        $query = ReviewPeriod::query();
        if ($periodId = $this->option('period')) {
            $query->whereKey($periodId);
        } else {
            $query->where('is_active', true)->whereDate('ends_at', '>=', now()->subMonth());
        }

        $periods = $query->get();
        if ($periods->isEmpty()) {
            $this->warn('Tidak ada periode yang perlu dihitung.');

            return 0;
        }

        $count = 0;
        $errors = 0;

        foreach ($periods as $period) {
            $summary = $calculator->calculate($period);
            $count += $summary['created'];
            $errors += count($summary['errors']);

            foreach ($summary['errors'] as $employeeId => $message) {
                Log::warning("Merit skip {$employeeId}: {$message}");
            }
        }

        $this->info("Merit selesai: {$count} dibuat, {$errors} dilewati.");

        return 0;
    }
}
