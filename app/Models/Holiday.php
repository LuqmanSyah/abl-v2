<?php

namespace App\Models;

use App\Console\Commands\AggregateDailyAttendance;
use App\Console\Commands\PopulateHolidaySummaries;
use App\Enums\DailySummaryStatus;
use Carbon\CarbonInterface;
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

            static::rebuildSummaries($holiday->getOriginal('date'));
        });

        static::saved(fn (self $holiday) => app(PopulateHolidaySummaries::class)->populate($holiday));
        static::deleted(fn (self $holiday) => static::rebuildSummaries($holiday->date));
    }

    private static function rebuildSummaries(CarbonInterface|string $date): void
    {
        DailyAttendanceSummary::query()
            ->whereDate('date', $date)
            ->where('status', DailySummaryStatus::Holiday)
            ->delete();

        app(AggregateDailyAttendance::class)->aggregate($date);
    }
}
