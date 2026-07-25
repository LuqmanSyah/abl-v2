<?php

namespace App\Console\Commands;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\DailySummaryStatus;
use App\Models\Attendance;
use App\Models\DailyAttendanceSummary;
use App\Models\Holiday;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;

class AggregateDailyAttendance extends Command
{
    protected $signature = 'attendance:aggregate {--date= : Date in YYYY-MM-DD format}';

    protected $description = 'Aggregate daily attendance summaries';

    public function handle(): int
    {
        $date = CarbonImmutable::parse($this->option('date') ?? today('Asia/Jakarta'), 'Asia/Jakarta');
        $this->aggregate($date);

        $this->info("Attendance {$date->toDateString()} aggregated.");

        return self::SUCCESS;
    }

    public function aggregate(CarbonInterface|string $date): void
    {
        $date = CarbonImmutable::parse($date, 'Asia/Jakarta')->startOfDay();
        $isHoliday = Holiday::query()->whereDate('date', $date)->exists();

        // ponytail: per-user queries; batch by date if nightly runtime becomes measurable.
        User::query()
            ->where('status', true)
            ->with('workSchedule')
            ->eachById(fn (User $user) => $this->aggregateUser($user, $date, $isHoliday));
    }

    private function aggregateUser(User $user, CarbonImmutable $date, bool $isHoliday): void
    {
        $summary = DailyAttendanceSummary::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->first();

        if ($summary && in_array($summary->status, [
            DailySummaryStatus::Leave,
            DailySummaryStatus::Holiday,
        ], true)) {
            return;
        }

        if ($isHoliday) {
            $this->saveSummary($user, $date, DailySummaryStatus::Holiday);

            return;
        }

        $attendances = Attendance::query()
            ->with('attendanceRequest')
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $date)
            ->orderBy('recorded_at')
            ->get();

        $checkIn = $attendances->first(fn (Attendance $attendance): bool => $attendance->attendance_request_id !== null
            && $attendance->type === AttendanceType::CheckIn
            && in_array($attendance->status, [AttendanceStatus::Normal, AttendanceStatus::Late], true));

        $checkIn ??= $attendances->first(fn (Attendance $attendance): bool => $attendance->attendance_request_id === null
            && $attendance->type === AttendanceType::CheckIn
            && in_array($attendance->status, [
                AttendanceStatus::Normal,
                AttendanceStatus::Late,
                AttendanceStatus::Alfa,
            ], true));

        if (! $checkIn) {
            $this->saveSummary($user, $date, DailySummaryStatus::Alfa);

            return;
        }

        $checkOut = $attendances->first(fn (Attendance $attendance): bool => $attendance->attendance_request_id === $checkIn->attendance_request_id
            && $attendance->type === AttendanceType::CheckOut
            && $attendance->recorded_at->gte($checkIn->recorded_at));

        $status = match (true) {
            $checkIn->status === AttendanceStatus::Alfa => DailySummaryStatus::Alfa,
            ! $checkOut || $checkOut->status !== AttendanceStatus::Normal => DailySummaryStatus::MissingCheckout,
            $checkIn->status === AttendanceStatus::Late => DailySummaryStatus::Late,
            default => DailySummaryStatus::Present,
        };

        $scheduledStart = $checkIn->attendanceRequest
            ? $date->setTimeFromTimeString($checkIn->attendanceRequest->duty_start_datetime->format('H:i:s'))
            : $date->setTimeFromTimeString($user->workSchedule->check_in_time);

        $lateMinutes = max(0, (int) floor(
            ($checkIn->recorded_at->timestamp - $scheduledStart->timestamp) / 60,
        ));

        $this->saveSummary(
            $user,
            $date,
            $status,
            $checkIn,
            $checkOut,
            $lateMinutes,
        );
    }

    private function saveSummary(
        User $user,
        CarbonImmutable $date,
        DailySummaryStatus $status,
        ?Attendance $checkIn = null,
        ?Attendance $checkOut = null,
        int $lateMinutes = 0,
    ): void {
        DailyAttendanceSummary::updateOrCreate(
            ['user_id' => $user->id, 'date' => $date],
            [
                'attendance_request_id' => $checkIn?->attendance_request_id,
                'check_in_id' => $checkIn?->id,
                'check_out_id' => $checkOut?->id,
                'status' => $status,
                'late_minutes' => $lateMinutes,
            ],
        );
    }
}
