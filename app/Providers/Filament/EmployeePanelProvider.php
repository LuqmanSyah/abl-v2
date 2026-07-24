<?php

namespace App\Providers\Filament;

use App\Filament\Resources\Attendances\AttendanceResource;
use App\Filament\Resources\CareerGoals\CareerGoalResource;
use App\Filament\Resources\DutyTrips\DutyTripResource;
use App\Filament\Resources\EmployeeCompetencies\EmployeeCompetencyResource;
use App\Filament\Resources\EmployeeKpis\EmployeeKpiResource;
use App\Filament\Resources\Mentorings\MentoringResource;
use App\Filament\Resources\MeritResults\MeritResultResource;
use App\Filament\Resources\PerformanceReviews\PerformanceReviewResource;
use App\Filament\Resources\TrainingRequests\TrainingRequestResource;
use App\Filament\Resources\Trainings\TrainingResource;
use App\Filament\Widgets\EmployeeActiveTripsTable;
use App\Filament\Widgets\EmployeeKpiProgressTable;
use App\Filament\Widgets\EmployeeLatestMerit;
use App\Filament\Widgets\EmployeeStats;
use App\Filament\Widgets\EmployeeTrainingMentoringTable;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;

class EmployeePanelProvider extends RolePanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $this->basePanel(
            $panel->id('employee')->path('pegawai')->brandName('Portal Pegawai')
        )
            ->resources([
                DutyTripResource::class,
                AttendanceResource::class,
                EmployeeKpiResource::class,
                PerformanceReviewResource::class,
                MeritResultResource::class,
                EmployeeCompetencyResource::class,
                CareerGoalResource::class,
                TrainingResource::class,
                TrainingRequestResource::class,
                MentoringResource::class,
            ])
            ->navigationGroups([
                'Operasional',
                'Kinerja',
                'Pengembangan',
            ])
            ->widgets([
                EmployeeStats::class,
                EmployeeLatestMerit::class,
                EmployeeKpiProgressTable::class,
                EmployeeActiveTripsTable::class,
                EmployeeTrainingMentoringTable::class,
                AccountWidget::class,
            ])
            ->colors(['primary' => Color::Blue])
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => view('pwa.register')->render());
    }
}
