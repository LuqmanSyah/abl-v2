<?php

namespace App\Console\Commands;

use App\Enums\DailySummaryStatus;
use App\Models\DailyAttendanceSummary;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Console\Command;

class PopulateHolidaySummaries extends Command
{
    protected $signature = 'attendance:populate-holidays {--date= : Holiday date in YYYY-MM-DD format}';

    protected $description = 'Populate holiday attendance summaries for active users';

    public function handle(): int
    {
        Holiday::query()
            ->when($this->option('date'), fn ($query, string $date) => $query->whereDate('date', $date))
            ->eachById(fn (Holiday $holiday) => $this->populate($holiday));

        return self::SUCCESS;
    }

    public function populate(Holiday $holiday): void
    {
        User::query()
            ->where('status', true)
            ->select('id')
            ->eachById(fn (User $user) => DailyAttendanceSummary::updateOrCreate(
                ['user_id' => $user->id, 'date' => $holiday->date->startOfDay()],
                [
                    'attendance_request_id' => null,
                    'check_in_id' => null,
                    'check_out_id' => null,
                    'status' => DailySummaryStatus::Holiday,
                    'late_minutes' => 0,
                ],
            ));
    }
}
