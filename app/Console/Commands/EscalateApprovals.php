<?php

namespace App\Console\Commands;

use App\Enums\MentoringStatus;
use App\Enums\TrainingRequestStatus;
use App\Models\ApprovalChain;
use App\Models\Mentoring;
use App\Models\TrainingRequest;
use App\Models\User;
use App\Notifications\MentoringPending;
use App\Notifications\TrainingPending;
use Illuminate\Console\Command;

class EscalateApprovals extends Command
{
    protected $signature = 'approval:escalate';
    protected $description = 'Escalate pending approvals >3 days to next approval step';

    public function handle(): int
    {
        $escalated = 0;

        $escalated += $this->escalateModule(
            'training_request',
            TrainingRequest::class,
            TrainingRequestStatus::PendingManager,
            'requested_at',
        );

        $escalated += $this->escalateModule(
            'mentoring',
            Mentoring::class,
            MentoringStatus::Pending,
            'requested_at',
        );

        $this->info("{$escalated} approvals escalated.");

        return 0;
    }

    private function escalateModule(
        string $module,
        string $modelClass,
        mixed $pendingStatus,
        string $dateColumn,
    ): int {
        $chain = ApprovalChain::forModule($module);
        if (! $chain) {
            $this->warn("No active approval chain for module: {$module}");

            return 0;
        }

        $roles = $chain->getStepRoles();
        $cutoff = now()->subDays(3);
        $count = 0;

        $modelClass::where('status', $pendingStatus)
            ->where($dateColumn, '<', $cutoff)
            ->each(function ($request) use ($roles, $module, &$count): void {
                $nextRole = $roles[1] ?? null;

                $users = match ($nextRole) {
                    'manager' => User::whereKey($request->manager_id)->where('is_active', true)->get(),
                    'hr' => User::where('role', 'hr')->where('is_active', true)->get(),
                    default => User::where('role', 'hr')->where('is_active', true)->get(),
                };

                $notification = $module === 'training_request'
                    ? new TrainingPending($request)
                    : new MentoringPending($request);

                foreach ($users as $user) {
                    $user->notify($notification);
                }

                activity()
                    ->performedOn($request)
                    ->withProperties([
                        'action' => 'escalated',
                        'reason' => 'pending > 3 days',
                        'next_role' => $nextRole,
                    ])
                    ->log("{$module}.escalated");

                $count++;
            });

        return $count;
    }
}
