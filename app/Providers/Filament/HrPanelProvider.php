<?php

namespace App\Providers\Filament;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\Resources\ApprovalChains\ApprovalChainResource;
use App\Filament\Resources\Attendances\AttendanceResource;
use App\Filament\Resources\CareerGoals\CareerGoalResource;
use App\Filament\Resources\Competencies\CompetencyResource;
use App\Filament\Resources\DutyLocations\DutyLocationResource;
use App\Filament\Resources\DutyTrips\DutyTripResource;
use App\Filament\Resources\EmployeeCompetencies\EmployeeCompetencyResource;
use App\Filament\Resources\EmployeeKpis\EmployeeKpiResource;
use App\Filament\Resources\KpiIndicators\KpiIndicatorResource;
use App\Filament\Resources\Mentorings\MentoringResource;
use App\Filament\Resources\MeritResults\MeritResultResource;
use App\Filament\Resources\PerformanceReviews\PerformanceReviewResource;
use App\Filament\Resources\PositionCompetencies\PositionCompetencyResource;
use App\Filament\Resources\Positions\PositionResource;
use App\Filament\Resources\ReviewPeriods\ReviewPeriodResource;
use App\Filament\Resources\TrainingRequests\TrainingRequestResource;
use App\Filament\Resources\Trainings\TrainingResource;
use App\Filament\Resources\Units\UnitResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\HrActiveTripsTable;
use App\Filament\Widgets\HrAttendanceDropAlert;
use App\Filament\Widgets\HrAttendanceStats;
use App\Filament\Widgets\HrMeritPerUnitTable;
use App\Filament\Widgets\HrStats;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;

class HrPanelProvider extends RolePanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $this->basePanel(
            $panel->default()->id('hr')->path('hr')->brandName('Portal SDM/HR')
        )
            ->resources([
                UserResource::class,
                UnitResource::class,
                PositionResource::class,
                DutyLocationResource::class,
                DutyTripResource::class,
                AttendanceResource::class,
                ReviewPeriodResource::class,
                KpiIndicatorResource::class,
                EmployeeKpiResource::class,
                PerformanceReviewResource::class,
                MeritResultResource::class,
                CompetencyResource::class,
                PositionCompetencyResource::class,
                EmployeeCompetencyResource::class,
                CareerGoalResource::class,
                TrainingResource::class,
                TrainingRequestResource::class,
                MentoringResource::class,
                ActivityLogResource::class,
                ApprovalChainResource::class,
            ])
            ->navigationGroups([
                'Organisasi',
                'Operasional',
                'Kinerja',
                'Pengembangan',
                'Laporan & Audit',
            ])
            ->widgets([
                HrStats::class,
                HrActiveTripsTable::class,
                HrAttendanceStats::class,
                HrMeritPerUnitTable::class,
                HrAttendanceDropAlert::class,
                AccountWidget::class,
            ])
            ->navigationItems([
                NavigationItem::make('Laporan SDM')
                    ->icon('heroicon-o-chart-bar')
                    ->group('Laporan & Audit')
                    ->sort(10)
                    ->url(fn (): string => route('hr.reports.index')),
            ])
            ->colors(['primary' => Color::Amber]);
    }
}
