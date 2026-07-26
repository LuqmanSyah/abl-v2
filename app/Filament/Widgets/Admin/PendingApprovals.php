<?php

namespace App\Filament\Widgets\Admin;

use App\Enums\AttendanceRequestStatus;
use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Filament\Resources\AttendanceRequestResource;
use App\Models\AttendanceRequest;
use App\Models\LeaveRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class PendingApprovals extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Manager;
    }

    protected function getStats(): array
    {
        $subordinate = fn (Builder $query): Builder => $query->where('manager_id', auth()->id());
        $attendance = AttendanceRequest::query()
            ->where('status', AttendanceRequestStatus::Pending)
            ->whereHas('user', $subordinate)
            ->count();
        $leave = LeaveRequest::query()
            ->where('status', LeaveStatus::Pending)
            ->whereHas('user', $subordinate)
            ->count();

        return [
            Stat::make('Izin Dinas Pending', $attendance)
                ->description('Pengajuan bawahan menunggu keputusan')
                ->color($attendance ? 'warning' : 'success')
                ->url(AttendanceRequestResource::getUrl(panel: 'admin')),

            Stat::make('Cuti Pending', $leave)
                ->description('Pengajuan bawahan untuk dipantau')
                ->color($leave ? 'warning' : 'success'),
        ];
    }
}
