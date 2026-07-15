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

class EmployeeStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Dinas aktif', DutyTrip::where('employee_id', auth()->id())->where('status', DutyTripStatus::Approved)->count()),
            Stat::make('Dinas selesai', DutyTrip::where('employee_id', auth()->id())->where('status', DutyTripStatus::Completed)->count()),
            Stat::make('Riwayat absensi', Attendance::where('employee_id', auth()->id())->count()),
            Stat::make('Hasil merit terbit', MeritResult::where('employee_id', auth()->id())->whereNotNull('published_at')->count()),
            Stat::make('Pelatihan disetujui', TrainingRequest::where('user_id', auth()->id())->where('status', TrainingRequestStatus::Approved)->count()),
            Stat::make('Mentoring terjadwal', Mentoring::where('employee_id', auth()->id())->where('status', MentoringStatus::Approved)->count()),
        ];
    }
}
