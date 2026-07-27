<?php

namespace App\Models;

use App\Console\Commands\AggregateDailyAttendance;
use App\Console\Commands\PopulateHolidaySummaries;
use App\Enums\DailySummaryStatus;
use App\Events\AttendanceDataChanged;
use Carbon\CarbonImmutable;
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

            $oldDate = $holiday->getOriginal('date');
            static::rebuildSummaries($oldDate);
            static::dispatchMeritRecalculation($oldDate);
        });

        static::saved(function (self $holiday): void {
            app(PopulateHolidaySummaries::class)->populate($holiday);
            static::dispatchMeritRecalculation($holiday->date);
        });

        static::deleted(function (self $holiday): void {
            static::rebuildSummaries($holiday->date);
            static::dispatchMeritRecalculation($holiday->date);
        });
    }

    private static function rebuildSummaries(CarbonInterface|string $date): void
    {
        $date = CarbonImmutable::parse($date, 'Asia/Jakarta')->startOfDay();

        DailyAttendanceSummary::query()
            ->whereDate('date', $date)
            ->where('status', DailySummaryStatus::Holiday)
            ->delete();

        app(AggregateDailyAttendance::class)->aggregate(
            $date,
            finalize: $date->isBefore(CarbonImmutable::now('Asia/Jakarta')->startOfDay()),
        );
    }

    private static function dispatchMeritRecalculation(CarbonInterface|string $date): void
    {
        $date = $date instanceof CarbonInterface ? $date->toDateString() : $date;

        AttendanceDataChanged::dispatch(null, $date, $date);
    }
}
