<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\Resources\Attendances\Pages\ListAttendances;
use App\Filament\Resources\DevelopmentPlans\Pages\ListDevelopmentPlans;
use App\Filament\Resources\DevelopmentRequests\Pages\ListDevelopmentRequests;
use App\Filament\Resources\DutyTrips\Pages\ListDutyTrips;
use App\Filament\Resources\EmployeeKpis\Pages\ListEmployeeKpis;
use App\Filament\Resources\MeritResults\Pages\ListMeritResults;
use App\Filament\Resources\Positions\Pages\ListPositions;
use App\Filament\Resources\ReviewPeriods\Pages\ListReviewPeriods;
use App\Filament\Resources\Units\Pages\ListUnits;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\ReviewPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UiSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_render_every_shared_panel_list(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Hr]));
        filament()->setCurrentPanel(filament()->getPanel('app'));

        foreach ([
            ListUsers::class,
            ListUnits::class,
            ListPositions::class,
            ListDutyTrips::class,
            ListAttendances::class,
            ListReviewPeriods::class,
            ListEmployeeKpis::class,
            ListMeritResults::class,
            ListDevelopmentPlans::class,
            ListDevelopmentRequests::class,
            ListActivityLogs::class,
        ] as $page) {
            Livewire::test($page)->assertSuccessful();
        }

        $period = ReviewPeriod::create([
            'name' => 'Semester',
            'starts_at' => today()->startOfYear(),
            'ends_at' => today()->endOfYear(),
        ]);
        Livewire::test(ListUsers::class)
            ->callAction('export', ['review_period_id' => $period->id])
            ->assertFileDownloaded('laporan-sdm-'.today()->toDateString().'.csv');
    }
}
