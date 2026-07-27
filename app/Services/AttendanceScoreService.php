<?php

namespace App\Services;

use App\Enums\DailySummaryStatus;
use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Models\DailyAttendanceSummary;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use InvalidArgumentException;

class AttendanceScoreService
{
    public function calculate(
        int $userId,
        CarbonInterface|string $startDate,
        CarbonInterface|string $endDate,
    ): int {
        $start = CarbonImmutable::parse($startDate, 'Asia/Jakarta')->startOfDay();
        $end = CarbonImmutable::parse($endDate, 'Asia/Jakarta')->startOfDay();

        if ($start->gt($end)) {
            throw new InvalidArgumentException('Tanggal mulai tidak boleh setelah tanggal selesai.');
        }

        $user = User::query()->findOrFail($userId);
        $joinDate = CarbonImmutable::instance($user->join_date)->startOfDay();
        $effectiveStart = $start->max($joinDate);

        if ($effectiveStart->gt($end)) {
            return 100;
        }

        $nonWorkingDates = Holiday::query()
            ->whereDate('date', '>=', $effectiveStart)
            ->whereDate('date', '<=', $end)
            ->get(['date'])
            ->mapWithKeys(fn (Holiday $holiday): array => [$holiday->date->toDateString() => true])
            ->all();

        LeaveRequest::query()
            ->where('user_id', $userId)
            ->where('status', LeaveStatus::Approved)
            ->whereNotNull('approved_by')
            ->whereNotNull('approved_at')
            ->whereHas('approver', fn ($query) => $query->where('role', UserRole::HrAdmin))
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $effectiveStart)
            ->get(['start_date', 'end_date'])
            ->each(function (LeaveRequest $leave) use (&$nonWorkingDates, $effectiveStart, $end): void {
                $leaveStart = CarbonImmutable::instance($leave->start_date)->max($effectiveStart);
                $leaveEnd = CarbonImmutable::instance($leave->end_date)->min($end);

                foreach (CarbonPeriod::create($leaveStart, $leaveEnd) as $date) {
                    $nonWorkingDates[$date->toDateString()] = true;
                }
            });

        // ponytail: one in-memory pass per review period; aggregate in SQL if multi-year periods become common.
        $workingDates = [];

        foreach (CarbonPeriod::create($effectiveStart, $end) as $date) {
            $key = $date->toDateString();

            if ($date->isWeekday() && ! isset($nonWorkingDates[$key])) {
                $workingDates[$key] = true;
            }
        }

        $summaries = DailyAttendanceSummary::query()
            ->where('user_id', $userId)
            ->whereDate('date', '>=', $effectiveStart)
            ->whereDate('date', '<=', $end)
            ->get(['date', 'status'])
            ->filter(fn (DailyAttendanceSummary $summary): bool => isset($workingDates[$summary->date->toDateString()]));

        $presentDays = $summaries->whereIn('status', [
            DailySummaryStatus::Present,
            DailySummaryStatus::Late,
        ])->count();
        $lateDays = $summaries->where('status', DailySummaryStatus::Late)->count();
        $missingCheckoutDays = $summaries->where('status', DailySummaryStatus::MissingCheckout)->count();
        $alfaDays = max(0, count($workingDates) - $presentDays);

        return max(0, 100 - (2 * $lateDays + 5 * $missingCheckoutDays + 10 * $alfaDays));
    }
}
