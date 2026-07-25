<?php

namespace App\Models;

use App\Console\Commands\AggregateDailyAttendance;
use App\Console\Commands\PopulateHolidaySummaries;
use App\Enums\DailySummaryStatus;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'name',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    protected static function booted(): void
    {
        static::updated(function (self $holiday): void {
            if (! $holiday->wasChanged('date')) {
                return;
            }

            $oldDate = $holiday->getOriginal('date');

            DailyAttendanceSummary::query()
                ->whereDate('date', $oldDate)
                ->where('status', DailySummaryStatus::Holiday)
                ->delete();

            app(AggregateDailyAttendance::class)->aggregate($oldDate);
        });

        static::saved(fn (self $holiday) => app(PopulateHolidaySummaries::class)->populate($holiday));
    }
}
