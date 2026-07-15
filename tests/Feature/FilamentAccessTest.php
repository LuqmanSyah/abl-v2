<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Positions\PositionResource;
use App\Filament\Resources\Units\UnitResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\EmployeeStats;
use App\Filament\Widgets\HrStats;
use App\Filament\Widgets\ManagerStats;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FilamentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_available(): void
    {
        $this->get('/pegawai/login')->assertOk();
        $this->get('/atasan/login')->assertOk();
        $this->get('/hr/login')->assertOk();
    }

    public function test_only_hr_can_access_organization_resources(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $this->actingAs($employee);

        $this->assertFalse(UserResource::canViewAny());
        $this->assertFalse(UnitResource::canViewAny());
        $this->assertFalse(PositionResource::canViewAny());

        $hr = User::factory()->create(['role' => UserRole::Hr]);
        $this->actingAs($hr);

        $this->assertTrue(UserResource::canViewAny());
        $this->assertTrue(UnitResource::canViewAny());
        $this->assertTrue(PositionResource::canViewAny());
    }

    public function test_inactive_user_cannot_access_panel(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->assertFalse($user->canAccessPanel(filament()->getPanel('employee')));
    }

    public function test_each_role_can_only_access_its_panel(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $hr = User::factory()->create(['role' => UserRole::Hr]);

        $this->assertTrue($employee->canAccessPanel(filament()->getPanel('employee')));
        $this->assertFalse($employee->canAccessPanel(filament()->getPanel('manager')));
        $this->assertTrue($manager->canAccessPanel(filament()->getPanel('manager')));
        $this->assertFalse($manager->canAccessPanel(filament()->getPanel('hr')));
        $this->assertTrue($hr->canAccessPanel(filament()->getPanel('hr')));
        $this->assertFalse($hr->canAccessPanel(filament()->getPanel('employee')));
    }

    public function test_panels_only_register_resources_needed_by_the_role(): void
    {
        $this->assertTrue(Route::has('filament.employee.resources.duty-trips.index'));
        $this->assertFalse(Route::has('filament.employee.resources.users.index'));
        $this->assertTrue(Route::has('filament.manager.resources.attendances.index'));
        $this->assertFalse(Route::has('filament.manager.resources.duty-locations.index'));
        $this->assertTrue(Route::has('filament.hr.resources.users.index'));
        $this->assertTrue(Route::has('filament.hr.resources.duty-locations.index'));
    }

    public function test_role_resource_pages_render(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'manager_id' => $manager->id]);
        $hr = User::factory()->create(['role' => UserRole::Hr]);

        $this->actingAs($employee)->get('/pegawai')->assertOk();
        $this->assertContains(EmployeeStats::class, filament()->getPanel('employee')->getWidgets());
        $this->actingAs($employee)->get('/pegawai/duty-trips/create')->assertOk();
        $this->actingAs($manager)->get('/atasan')->assertOk();
        $this->assertContains(ManagerStats::class, filament()->getPanel('manager')->getWidgets());
        $this->actingAs($manager)->get('/atasan/duty-trips')->assertOk();
        $this->actingAs($hr)->get('/hr')->assertOk();
        $this->assertContains(HrStats::class, filament()->getPanel('hr')->getWidgets());
        $this->actingAs($hr)->get('/hr/duty-locations/create')->assertOk();
    }
}
