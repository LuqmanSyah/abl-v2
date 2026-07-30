<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\Resources\Attendances\Pages\ListAttendances;
use App\Filament\Resources\DevelopmentPlans\Pages\ListDevelopmentPlans;
use App\Filament\Resources\DevelopmentRequests\Pages\ListDevelopmentRequests;
use App\Filament\Resources\DutyTrips\Pages\CreateDutyTrip;
use App\Filament\Resources\DutyTrips\Pages\ListDutyTrips;
use App\Filament\Resources\EmployeeKpis\Pages\ListEmployeeKpis;
use App\Filament\Resources\MeritResults\Pages\ListMeritResults;
use App\Filament\Resources\Positions\Pages\ListPositions;
use App\Filament\Resources\ReviewPeriods\Pages\ListReviewPeriods;
use App\Filament\Resources\Units\Pages\ListUnits;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\DutyTrip;
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

    public function test_manager_can_assign_one_trip_to_multiple_employees_with_dates_only(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $employees = User::factory()->count(3)->create([
            'role' => UserRole::Employee,
            'manager_id' => $manager->id,
            'is_active' => true,
        ]);

        $this->actingAs($manager);
        filament()->setCurrentPanel(filament()->getPanel('app'));

        Livewire::test(CreateDutyTrip::class)
            ->assertSuccessful()
            ->assertSee('Cari alamat')
            ->fillForm([
                'employee_ids' => $employees->modelKeys(),
                'location_name' => 'Kantor Cabang',
                'address' => 'Jakarta',
                'latitude' => -6.2,
                'longitude' => 106.8166,
                'radius_meters' => 100,
                'starts_at' => today()->addDay()->toDateString(),
                'ends_at' => today()->addDay()->toDateString(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $trips = DutyTrip::query()->orderBy('employee_id')->get();

        $this->assertSame($employees->modelKeys(), $trips->pluck('employee_id')->all());
        $this->assertTrue($trips->every(fn (DutyTrip $trip): bool => $trip->starts_at->format('H:i:s') === '00:00:00'
            && $trip->ends_at->format('H:i:s') === '23:59:59'));
    }
}
