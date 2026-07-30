<?php

namespace App\Filament\Widgets;

use App\Enums\AttendanceStatus;
use App\Enums\DutyTripStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Attendances\AttendanceResource;
use App\Filament\Resources\DevelopmentRequests\DevelopmentRequestResource;
use App\Filament\Resources\DutyTrips\DutyTripResource;
use App\Filament\Resources\EmployeeKpis\EmployeeKpiResource;
use App\Filament\Resources\ReviewPeriods\ReviewPeriodResource;
use App\Models\Attendance;
use App\Models\DevelopmentRequest;
use App\Models\DutyTrip;
use App\Models\EmployeeKpi;
use App\Models\ReviewPeriod;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AppStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return match (auth()->user()->role) {
            UserRole::Employee => $this->employeeStats(),
            UserRole::Manager => $this->managerStats(),
            UserRole::Hr => $this->hrStats(),
        };
    }

    private function employeeStats(): array
    {
        $userId = auth()->id();

        return [
            Stat::make('Dinas Aktif', DutyTrip::where('employee_id', $userId)
                ->where('status', DutyTripStatus::Active)
                ->where('ends_at', '>=', now())
                ->count())
                ->url(DutyTripResource::getUrl('index')),
            Stat::make('KPI', EmployeeKpi::where('employee_id', $userId)->count())
                ->url(EmployeeKpiResource::getUrl('index')),
            Stat::make('Pengajuan Menunggu', DevelopmentRequest::where('employee_id', $userId)
                ->where('status', DevelopmentRequest::STATUS_PENDING)
                ->count())
                ->url(DevelopmentRequestResource::getUrl('index')),
        ];
    }

    private function managerStats(): array
    {
        $managerId = auth()->id();
        $period = ReviewPeriod::where('starts_at', '<=', today())
            ->where('ends_at', '>=', today())
            ->whereNull('published_at')
            ->first();
        $missingKpi = $period
            ? User::where('manager_id', $managerId)
                ->where('is_active', true)
                ->whereDoesntHave(
                    'employeeKpis',
                    fn ($query) => $query->where('review_period_id', $period->id),
                )
                ->count()
            : 0;

        return [
            Stat::make('Dinas Aktif', DutyTrip::where('manager_id', $managerId)
                ->where('status', DutyTripStatus::Active)
                ->where('ends_at', '>=', now())
                ->count())
                ->url(DutyTripResource::getUrl('index')),
            Stat::make('Pegawai Belum Punya KPI', $missingKpi)
                ->url(EmployeeKpiResource::getUrl('index')),
            Stat::make('Pengajuan Menunggu', DevelopmentRequest::where('manager_id', $managerId)
                ->where('status', DevelopmentRequest::STATUS_PENDING)
                ->count())
                ->url(DevelopmentRequestResource::getUrl('index')),
        ];
    }

    private function hrStats(): array
    {
        return [
            Stat::make('Absensi Perlu Diperiksa', Attendance::where('status', AttendanceStatus::NeedsReview)->count())
                ->url(AttendanceResource::getUrl('index')),
            Stat::make('Periode Belum Dipublikasikan', ReviewPeriod::whereNull('published_at')->count())
                ->url(ReviewPeriodResource::getUrl('index')),
            Stat::make('Pengajuan Aktif', DevelopmentRequest::whereIn('status', [
                DevelopmentRequest::STATUS_PENDING,
                DevelopmentRequest::STATUS_APPROVED,
            ])->count())
                ->url(DevelopmentRequestResource::getUrl('index')),
        ];
    }
}
