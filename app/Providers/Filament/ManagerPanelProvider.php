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
use App\Filament\Widgets\ManagerIncompleteKpiTable;
use App\Filament\Widgets\ManagerPendingApprovalsTable;
use App\Filament\Widgets\ManagerStats;
use App\Filament\Widgets\ManagerTeamMeritTable;
use App\Filament\Widgets\ManagerTeamTripTable;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;

class ManagerPanelProvider extends RolePanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $this->basePanel(
            $panel->id('manager')->path('atasan')->brandName('Portal Atasan')
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
                ManagerStats::class,
                ManagerTeamMeritTable::class,
                ManagerPendingApprovalsTable::class,
                ManagerTeamTripTable::class,
                ManagerIncompleteKpiTable::class,
                AccountWidget::class,
            ])
            ->colors(['primary' => Color::Green])
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => view('pwa.register')->render());
    }
}
