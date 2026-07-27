<?php

namespace App\Listeners;

use App\Console\Commands\AggregateDailyAttendance;
use App\Enums\ReviewStatus;
use App\Events\AttendanceDataChanged;
use App\Models\PerformanceReview;
use App\Services\MeritScoreService;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecalculateMeritOnChange implements ShouldQueue
{
    public function __construct(
        private MeritScoreService $meritScore,
        private AggregateDailyAttendance $aggregateDailyAttendance,
    ) {}

    public function handle(AttendanceDataChanged $event): void
    {
        if ($event->aggregateAttendance) {
            $this->aggregateDailyAttendance->aggregate($event->startDate, finalize: false);
        }

        PerformanceReview::query()
            ->when($event->userId !== null, fn ($query) => $query->where('user_id', $event->userId))
            ->whereDate('start_date', '<=', $event->endDate)
            ->whereDate('end_date', '>=', $event->startDate)
            ->where('status', ReviewStatus::Approved)
            ->eachById(fn (PerformanceReview $review) => $this->meritScore->calculate($review));
    }
}
