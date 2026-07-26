<?php

namespace App\Filament\Widgets\Admin;

use App\Enums\AttendanceRequestStatus;
use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Filament\Resources\AttendanceRequestResource;
use App\Filament\Resources\LeaveRequestResource;
use App\Models\AttendanceRequest;
use App\Models\LeaveRequest;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PendingApprovals extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && in_array($user->role, [UserRole::Manager, UserRole::HrAdmin], true);
    }

    protected function getStats(): array
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->role === UserRole::HrAdmin) {
            $leave = LeaveRequest::query()
                ->where('status', LeaveStatus::Pending)
                ->count();

            return [
                Stat::make('Cuti Pending', $leave)
                    ->description('Pengajuan menunggu verifikasi HR')
                    ->color($leave ? 'warning' : 'success')
                    ->url(LeaveRequestResource::getUrl(panel: 'admin')),
            ];
        }

        $subordinate = fn (Builder $query): Builder => $query->where('manager_id', $user->id);
        $attendance = AttendanceRequest::query()
            ->where('status', AttendanceRequestStatus::Pending)
            ->whereHas('user', $subordinate)
            ->count();

        return [
            Stat::make('Izin Dinas Pending', $attendance)
                ->description('Pengajuan bawahan menunggu keputusan')
                ->color($attendance ? 'warning' : 'success')
                ->url(AttendanceRequestResource::getUrl(panel: 'admin')),
        ];
    }
}
