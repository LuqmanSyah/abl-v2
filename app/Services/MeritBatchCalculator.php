<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\ReviewPeriod;
use App\Models\User;
use DomainException;

class MeritBatchCalculator
{
    public function __construct(private MeritCalculator $calculator) {}

    /** @return array{created: int, processed: int, errors: array<int, string>} */
    public function calculate(ReviewPeriod $period): array
    {
        $created = 0;
        $processed = 0;
        $errors = [];

        User::where('role', UserRole::Employee)
            ->where('is_active', true)
            ->eachById(function (User $employee) use ($period, &$created, &$processed, &$errors): void {
                try {
                    $result = $this->calculator->calculate($period, $employee);
                    $processed++;
                    $created += (int) $result->wasRecentlyCreated;
                } catch (DomainException $exception) {
                    $errors[$employee->id] = $exception->getMessage();
                }
            });

        return compact('created', 'processed', 'errors');
    }
}
