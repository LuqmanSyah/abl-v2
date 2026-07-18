<?php

namespace App\Console\Commands;

use App\Models\ReviewPeriod;
use App\Models\User;
use App\Services\MeritCalculator;
use DomainException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculateMerit extends Command
{
    protected $signature = 'merit:calculate {--period= : ID ReviewPeriod}';
    protected $description = 'Hitung merit untuk semua periode aktif';

    public function handle(MeritCalculator $calculator): int
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
            $employees = User::whereRelation('dutyTrips', fn ($q) => $q
                ->whereBetween('starts_at', [$period->starts_at, $period->ends_at])
            )->orWhereHas('employeeKpis', fn ($q) => $q
                ->where('review_period_id', $period->id)
            )->where('role', \App\Enums\UserRole::Employee)->get();

            foreach ($employees as $employee) {
                try {
                    $result = $calculator->calculate($period, $employee);
                    if ($result->wasRecentlyCreated) {
                        $count++;
                    }
                } catch (DomainException $e) {
                    $errors++;
                    Log::info("Merit skip {$employee->id}: {$e->getMessage()}");
                }
            }
        }

        $this->info("Merit selesai: {$count} dibuat, {$errors} dilewati.");

        return 0;
    }
}
