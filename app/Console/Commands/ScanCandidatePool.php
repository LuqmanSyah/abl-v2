<?php

namespace App\Console\Commands;

use App\Enums\PromotionStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\CareerPath;
use App\Models\Promotion;
use App\Models\User;
use App\Services\ReadinessScoreService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;

class ScanCandidatePool extends Command
{
    protected $signature = 'career:scan-candidates';

    protected $description = 'Create promotion proposals for qualified employees';

    public function handle(ReadinessScoreService $readinessScore): int
    {
        $created = 0;

        // ponytail: monthly N-per-candidate scan; batch only if employee volume makes runtime measurable.
        CareerPath::query()->with('nextPosition')->eachById(function (CareerPath $path) use ($readinessScore, &$created): void {
            User::query()
                ->where('status', true)
                ->where('position_id', $path->current_position_id)
                ->whereNotNull('manager_id')
                ->eachById(function (User $user) use ($path, $readinessScore, &$created): void {
                    $readiness = $readinessScore->calculate($user, $path->nextPosition);
                    $grade = $user->performanceReviews()
                        ->published()
                        ->whereNotNull('grade')
                        ->latest('end_date')
                        ->value('grade');

                    if ($readiness < 80
                        || $user->join_date->copy()->addMonths($path->min_experience_months)->isFuture()
                        || ! $this->gradeMeets((string) $grade, $path->min_merit_grade)
                        || Promotion::query()
                            ->activeLifecycle()
                            ->where('user_id', $user->id)
                            ->where('to_position_id', $path->next_position_id)
                            ->exists()) {
                        return;
                    }

                    try {
                        Promotion::create([
                            'user_id' => $user->id,
                            'from_position_id' => $path->current_position_id,
                            'to_position_id' => $path->next_position_id,
                            'proposed_by' => $user->manager_id,
                            'readiness_score' => $readiness,
                            'status' => PromotionStatus::Proposed,
                        ]);
                        $created++;
                    } catch (BusinessRuleException|QueryException $exception) {
                        if (! Promotion::query()
                            ->activeLifecycle()
                            ->where('user_id', $user->id)
                            ->where('to_position_id', $path->next_position_id)
                            ->exists()) {
                            throw $exception;
                        }
                    }
                });
        });

        $this->info("{$created} promotion candidate(s) created.");

        return self::SUCCESS;
    }

    private function gradeMeets(string $grade, string $minimum): bool
    {
        $rank = ['D' => 1, 'C' => 2, 'B' => 3, 'A' => 4];

        return ($rank[strtoupper($grade)] ?? 0) >= ($rank[strtoupper($minimum)] ?? 5);
    }
}
