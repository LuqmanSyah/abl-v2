<?php

namespace App\Filament\Widgets;

use App\Enums\DutyTripStatus;
use App\Enums\MentoringStatus;
use App\Enums\TrainingRequestStatus;
use App\Models\Attendance;
use App\Models\DutyTrip;
use App\Models\Mentoring;
use App\Models\MeritResult;
use App\Models\TrainingRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ManagerStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Dinas aktif', DutyTrip::where('manager_id', auth()->id())->where('status', DutyTripStatus::Approved)->count()),
            Stat::make('Total tugas bawahan', DutyTrip::where('manager_id', auth()->id())->count()),
            Stat::make('Absensi bawahan', Attendance::whereHas('dutyTrip', fn ($query) => $query->where('manager_id', auth()->id()))->count()),
            Stat::make('Merit perlu verifikasi', MeritResult::whereHas('employee', fn ($query) => $query->where('manager_id', auth()->id()))->whereNull('manager_verified_at')->count()),
            Stat::make('Pelatihan perlu persetujuan', TrainingRequest::where('manager_id', auth()->id())->where('status', TrainingRequestStatus::PendingManager)->count()),
            Stat::make('Mentoring perlu jadwal', Mentoring::where('manager_id', auth()->id())->where('status', MentoringStatus::Pending)->count()),
        ];
    }
}
