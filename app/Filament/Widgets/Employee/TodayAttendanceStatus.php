<?php

namespace App\Filament\Widgets\Employee;

use App\Enums\AttendanceType;
use App\Filament\Resources\AttendanceResource;
use App\Models\Attendance;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodayAttendanceStatus extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $query = Attendance::query()
            ->where('user_id', auth()->id())
            ->whereDate('attendance_date', today());
        $checkIn = (clone $query)->where('type', AttendanceType::CheckIn)->oldest('recorded_at')->first();
        $checkOut = (clone $query)->where('type', AttendanceType::CheckOut)->latest('recorded_at')->first();

        return [
            Stat::make('Check-in Hari Ini', $checkIn?->recorded_at->format('H:i') ?? 'Belum')
                ->description('Buka form check-in')
                ->descriptionIcon('heroicon-o-arrow-right-start-on-rectangle')
                ->color($checkIn ? 'success' : 'gray')
                ->url(AttendanceResource::getUrl('create', ['type' => AttendanceType::CheckIn->value], panel: 'employee')),

            Stat::make('Check-out Hari Ini', $checkOut?->recorded_at->format('H:i') ?? 'Belum')
                ->description('Buka form check-out')
                ->descriptionIcon('heroicon-o-arrow-left-start-on-rectangle')
                ->color($checkOut ? 'success' : 'gray')
                ->url(AttendanceResource::getUrl('create', ['type' => AttendanceType::CheckOut->value], panel: 'employee')),
        ];
    }
}
