<?php

namespace App\Filament\Widgets;

use App\Enums\DutyTripStatus;
use App\Enums\MentoringStatus;
use App\Enums\TrainingRequestStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\DutyTrip;
use App\Models\Mentoring;
use App\Models\MeritResult;
use App\Models\TrainingRequest;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HrStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Pegawai aktif', User::where('role', UserRole::Employee)->where('is_active', true)->count()),
            Stat::make('Dinas aktif', DutyTrip::where('status', DutyTripStatus::Approved)->count()),
            Stat::make('Absensi hari ini', Attendance::whereDate('captured_at', today())->count()),
            Stat::make('Merit perlu verifikasi HR', MeritResult::whereNotNull('manager_verified_at')->whereNull('hr_verified_at')->count()),
            Stat::make('Pelatihan perlu verifikasi', TrainingRequest::where('status', TrainingRequestStatus::PendingHr)->count()),
            Stat::make('Mentoring aktif', Mentoring::where('status', MentoringStatus::Approved)->count()),
        ];
    }
}
