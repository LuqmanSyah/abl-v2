<?php

namespace App\Filament\Widgets\Employee;

use App\Enums\AttendanceRequestStatus;
use App\Enums\AttendanceType;
use App\Filament\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodayAttendanceStatus extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $attendances = Attendance::query()
            ->where('user_id', auth()->id())
            ->whereDate('attendance_date', today())
            ->orderBy('recorded_at')
            ->get()
            ->groupBy('session_key');
        $sessions = collect([[
            'key' => 'office',
            'label' => 'Kantor',
            'request_id' => null,
        ]]);

        AttendanceRequest::query()
            ->where('user_id', auth()->id())
            ->where('status', AttendanceRequestStatus::Approved)
            ->whereDate('duty_start_datetime', '<=', today())
            ->whereDate('duty_end_datetime', '>=', today())
            ->orderBy('duty_start_datetime')
            ->each(fn (AttendanceRequest $request) => $sessions->push([
                'key' => "request:{$request->id}",
                'label' => "Tugas Luar: {$request->destination_name}",
                'request_id' => $request->id,
            ]));

        return $sessions->map(function (array $session) use ($attendances): Stat {
            $records = $attendances->get($session['key'], collect());
            $checkIn = $records->first(fn (Attendance $attendance) => $attendance->type === AttendanceType::CheckIn);
            $checkOut = $records->last(fn (Attendance $attendance) => $attendance->type === AttendanceType::CheckOut);
            $nextType = $checkIn && ! $checkOut ? AttendanceType::CheckOut : AttendanceType::CheckIn;

            return Stat::make(
                $session['label'],
                sprintf(
                    'Masuk %s · Pulang %s',
                    $checkIn?->recorded_at->format('H:i') ?? 'Belum',
                    $checkOut?->recorded_at->format('H:i') ?? 'Belum',
                ),
            )
                ->description(match (true) {
                    ! $checkIn => 'Belum check-in',
                    ! $checkOut => 'Belum check-out',
                    default => 'Lengkap',
                })
                ->color($checkIn && $checkOut ? 'success' : 'warning')
                ->url($checkIn && $checkOut ? null : AttendanceResource::getUrl('create', [
                    'type' => $nextType->value,
                    'attendance_request_id' => $session['request_id'],
                ], panel: 'employee'));
        })->all();
    }
}
