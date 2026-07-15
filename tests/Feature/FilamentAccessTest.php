<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Positions\PositionResource;
use App\Filament\Resources\ReviewPeriods\ReviewPeriodResource;
use App\Filament\Resources\Trainings\TrainingResource;
use App\Filament\Resources\Units\UnitResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\EmployeeStats;
use App\Filament\Widgets\HrStats;
use App\Filament\Widgets\ManagerStats;
use App\Models\Position;
use App\Models\ReviewPeriod;
use App\Models\Training;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FilamentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_panel_login_pages_use_one_login_page(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/pegawai/login')->assertRedirect('/login');
        $this->get('/atasan/login')->assertRedirect('/login');
        $this->get('/hr/login')->assertRedirect('/login');
    }

    public function test_login_redirects_each_role_to_its_panel(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'password' => 'password',
            ]);

            $this->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])->assertRedirect('/'.$role->value);

            auth()->logout();
        }
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
            'password' => 'password',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
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

    public function test_invalid_organization_assignments_are_rejected(): void
    {
        $firstUnit = Unit::create(['name' => 'Satu', 'code' => 'SATU']);
        $secondUnit = Unit::create(['name' => 'Dua', 'code' => 'DUA']);
        $otherPosition = Position::create(['unit_id' => $secondUnit->id, 'name' => 'Staf Dua', 'level' => 1]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'unit_id' => $firstUnit->id]);
        $notManager = User::factory()->create(['role' => UserRole::Employee]);

        try {
            $employee->update(['manager_id' => $notManager->id]);
            $this->fail('Pegawai non-Atasan tidak boleh dipilih sebagai atasan langsung.');
        } catch (\DomainException $exception) {
            $this->assertSame('Atasan langsung harus pengguna aktif dengan peran Atasan.', $exception->getMessage());
        }

        $employee->refresh();
        $this->expectException(\DomainException::class);
        $employee->update(['position_id' => $otherPosition->id]);
    }

    public function test_manager_with_subordinates_cannot_be_deactivated_or_change_role(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'manager_id' => $manager->id]);

        foreach ([['is_active' => false], ['role' => UserRole::Hr]] as $attributes) {
            try {
                $manager->update($attributes);
                $this->fail('Atasan dengan bawahan tidak boleh dinonaktifkan atau diubah perannya.');
            } catch (\DomainException $exception) {
                $this->assertSame('Atasan yang masih memiliki bawahan tidak dapat dinonaktifkan atau diubah perannya.', $exception->getMessage());
            }

            $manager->refresh();
        }

        $employee->update(['manager_id' => null]);
        $manager->update(['is_active' => false]);

        $this->assertFalse($manager->is_active);
    }

    public function test_historical_master_data_cannot_be_hard_deleted(): void
    {
        $hr = User::factory()->create(['role' => UserRole::Hr]);
        $employee = User::factory()->create();
        $period = ReviewPeriod::create([
            'name' => 'Semester', 'starts_at' => today(), 'ends_at' => today()->addMonth(),
            'kpi_weight' => 40, 'discipline_weight' => 20, 'manager_weight' => 20,
            'review_360_weight' => 20, 'base_bonus' => 0, 'is_active' => true,
        ]);
        $training = Training::create(['name' => 'Pelatihan', 'type' => 'internal', 'is_active' => true]);
        $this->actingAs($hr);

        $this->assertFalse(UserResource::canDelete($employee));
        $this->assertFalse(ReviewPeriodResource::canDelete($period));
        $this->assertFalse(TrainingResource::canDelete($training));
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
        $this->assertTrue(Route::has('filament.employee.resources.employee-kpis.index'));
        $this->assertTrue(Route::has('filament.employee.resources.performance-reviews.index'));
        $this->assertTrue(Route::has('filament.employee.resources.merit-results.index'));
        $this->assertFalse(Route::has('filament.employee.resources.users.index'));
        $this->assertTrue(Route::has('filament.manager.resources.attendances.index'));
        $this->assertFalse(Route::has('filament.manager.resources.duty-locations.index'));
        $this->assertTrue(Route::has('filament.hr.resources.users.index'));
        $this->assertTrue(Route::has('filament.hr.resources.duty-locations.index'));
        $this->assertTrue(Route::has('filament.hr.resources.review-periods.index'));
        $this->assertTrue(Route::has('filament.hr.resources.kpi-indicators.index'));
    }

    public function test_role_resource_pages_render(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'manager_id' => $manager->id]);
        $hr = User::factory()->create(['role' => UserRole::Hr]);

        $this->actingAs($employee)->get('/pegawai')->assertOk()->assertSee('portal-filament.css', false);
        $this->assertContains(EmployeeStats::class, filament()->getPanel('employee')->getWidgets());
        $this->actingAs($employee)->get('/pegawai/duty-trips/create')->assertForbidden();
        $this->actingAs($employee)->get('/pegawai/employee-kpis')->assertOk();
        $this->actingAs($employee)->get('/pegawai/performance-reviews/create')->assertOk();
        $this->actingAs($employee)->get('/pegawai/merit-results')->assertOk();
        $this->actingAs($manager)->get('/atasan')->assertOk();
        $this->assertContains(ManagerStats::class, filament()->getPanel('manager')->getWidgets());
        $this->actingAs($manager)->get('/atasan/duty-trips/create')->assertOk();
        $this->actingAs($manager)->get('/atasan/employee-kpis/create')->assertOk();
        $this->actingAs($manager)->get('/atasan/performance-reviews/create')->assertOk();
        $this->actingAs($manager)->get('/atasan/merit-results')->assertOk();
        $this->actingAs($hr)->get('/hr')->assertOk();
        $this->assertContains(HrStats::class, filament()->getPanel('hr')->getWidgets());
        $this->actingAs($hr)->get('/hr/duty-locations/create')->assertOk();
        $this->actingAs($hr)->get('/hr/review-periods/create')->assertOk();
        $this->actingAs($hr)->get('/hr/kpi-indicators/create')->assertOk();
        $this->actingAs($hr)->get('/hr/merit-results')->assertOk();
    }
}
