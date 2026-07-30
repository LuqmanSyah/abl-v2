<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\DevelopmentRequests\DevelopmentRequestResource;
use App\Filament\Resources\DutyTrips\DutyTripResource;
use App\Filament\Resources\EmployeeKpis\EmployeeKpiResource;
use App\Filament\Resources\Positions\PositionResource;
use App\Filament\Resources\Units\UnitResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AppPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_uses_one_panel_for_all_active_roles(): void
    {
        $this->assertSame(['app'], array_keys(filament()->getPanels()));
        $this->get('/')->assertRedirect('/app');
        $this->get('/login')->assertRedirect('/app/login');
        $this->get('/app/login')->assertOk();
        $this->get('/pegawai')->assertNotFound();
        $this->get('/atasan')->assertNotFound();
        $this->get('/hr')->assertNotFound();

        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertTrue($user->canAccessPanel(filament()->getPanel('app')));
        }

        $inactive = User::factory()->create(['is_active' => false]);
        $this->assertFalse($inactive->canAccessPanel(filament()->getPanel('app')));
    }

    public function test_role_controls_menu_and_actions_in_shared_panel(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
            'manager_id' => $manager->id,
        ]);

        $this->actingAs($employee);
        $this->assertFalse(UserResource::canViewAny());
        $this->assertFalse(UnitResource::canViewAny());
        $this->assertFalse(PositionResource::canViewAny());
        $this->assertFalse(DutyTripResource::canCreate());
        $this->assertFalse(EmployeeKpiResource::canCreate());
        $this->assertTrue(DevelopmentRequestResource::canCreate());

        $this->actingAs($manager);
        $this->assertTrue(DutyTripResource::canCreate());
        $this->assertTrue(EmployeeKpiResource::canCreate());
        $this->assertFalse(DevelopmentRequestResource::canCreate());

        $this->actingAs(User::factory()->create(['role' => UserRole::Hr]));
        $this->assertTrue(UserResource::canViewAny());
        $this->assertTrue(UnitResource::canViewAny());
        $this->assertTrue(PositionResource::canViewAny());

        $this->assertTrue(Route::has('filament.app.resources.duty-trips.index'));
        $this->assertTrue(Route::has('filament.app.resources.development-requests.index'));
        $this->assertFalse(Route::has('filament.employee.pages.dashboard'));
    }
}
