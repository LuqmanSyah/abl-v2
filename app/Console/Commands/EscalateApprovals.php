<?php

namespace App\Console\Commands;

use App\Enums\MentoringStatus;
use App\Enums\TrainingRequestStatus;
use App\Models\Mentoring;
use App\Models\TrainingRequest;
use App\Models\User;
use App\Notifications\TrainingPending;
use Illuminate\Console\Command;

class EscalateApprovals extends Command
{
    protected $signature = 'approval:escalate';
    protected $description = 'Escalate pending approvals >3 days to next level';

    public function handle(): int
    {
        $escalated = 0;

        $escalated += $this->escalateTrainingRequests();
        $escalated += $this->escalateMentoringRequests();

        $this->info("{$escalated} approvals escalated.");

        return 0;
    }

    private function escalateTrainingRequests(): int
    {
        $cutoff = now()->subDays(3);
        $count = 0;

        TrainingRequest::where('status', TrainingRequestStatus::PendingManager)
            ->where('requested_at', '<', $cutoff)
            ->each(function (TrainingRequest $request) use (&$count): void {
                $manager = User::whereKey($request->manager_id)->where('is_active', true)->first();
                $hrUsers = User::where('role', 'hr')->where('is_active', true)->get();

                foreach ($hrUsers as $hr) {
                    $hr->notify(new TrainingPending($request));
                }

                activity()
                    ->performedOn($request)
                    ->withProperties(['action' => 'escalated', 'reason' => 'pending > 3 days'])
                    ->log('training.escalated');

                $count++;
            });

        return $count;
    }

    private function escalateMentoringRequests(): int
    {
        $cutoff = now()->subDays(3);
        $count = 0;

        Mentoring::where('status', MentoringStatus::Pending)
            ->where('requested_at', '<', $cutoff)
            ->each(function (Mentoring $mentoring) use (&$count): void {
                $hrUsers = User::where('role', 'hr')->where('is_active', true)->get();

                foreach ($hrUsers as $hr) {
                    $hr->notify(new \App\Notifications\MentoringPending($mentoring));
                }

                activity()
                    ->performedOn($mentoring)
                    ->withProperties(['action' => 'escalated', 'reason' => 'pending > 3 days'])
                    ->log('mentoring.escalated');

                $count++;
            });

        return $count;
    }
}
