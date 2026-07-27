<?php

namespace Tests\Feature;

use App\Enums\DailySummaryStatus;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Filament\Resources\BranchOfficeResource\Pages\ListBranchOffices;
use App\Filament\Resources\DailyAttendanceSummaryResource\Pages\ListDailyAttendanceSummaries;
use App\Filament\Resources\DepartmentResource\Pages\ListDepartments;
use App\Filament\Resources\HolidayResource\Pages\ListHolidays;
use App\Filament\Resources\PositionResource\Pages\ListPositions;
use App\Filament\Resources\SkillResource\Pages\ListSkills;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\WorkScheduleResource\Pages\ListWorkSchedules;
use App\Models\BranchOffice;
use App\Models\DailyAttendanceSummary;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\Position;
use App\Models\Skill;
use App\Models\User;
use App\Models\WorkSchedule;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class MasterDataResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_data_resources_create_records_from_admin_panel(): void
    {
        config(['services.google_maps.key' => 'test-key']);

        $department = Department::create(['name' => 'Human Resources', 'code' => 'HR']);
        $position = Position::create(['department_id' => $department->id, 'title' => 'HR Admin', 'level' => 1]);
        $schedule = WorkSchedule::create([
            'name' => 'Admin',
            'check_in_time' => '08:00',
            'check_out_time' => '17:00',
            'late_tolerance_minutes' => 15,
            'alfa_cutoff_minutes' => 120,
        ]);
        $branch = BranchOffice::create([
            'name' => 'Kantor Pusat',
            'code' => 'HQ',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'allowed_radius_meters' => 100,
        ]);

        $hrAdmin = User::factory()->create([
            'position_id' => $position->id,
            'work_schedule_id' => $schedule->id,
            'branch_office_id' => $branch->id,
            'role' => UserRole::HrAdmin,
        ]);
        $this->actingAs($hrAdmin);
        Livewire::test(ListDailyAttendanceSummaries::class)
            ->assertSee('Rekap Kehadiran');

        Livewire::test(ListHolidays::class)
            ->callAction('create', data: [
                'name' => 'Hari Kemerdekaan',
                'date' => '2026-08-17',
            ])
            ->assertHasNoFormErrors();

        Livewire::test(ListBranchOffices::class)
            ->mountAction('create')
            ->fillForm([
                'name' => 'Kantor Jakarta',
                'code' => 'JKT',
                'latitude' => -6.1754,
                'longitude' => 106.8272,
                'allowed_radius_meters' => 100,
            ])
            ->assertMountedActionModalSee('Cari Lokasi')
            ->assertMountedActionModalSeeHtml('map-place-autocomplete')
            ->assertMountedActionModalSeeHtml('PlaceAutocompleteElement')
            ->assertMountedActionModalSee('Klik peta atau geser marker untuk memilih lokasi.')
            ->assertMountedActionModalSeeHtml('async init()')
            ->assertMountedActionModalSeeHtml('__ablGoogleMapsReady')
            ->callMountedAction()
            ->assertHasNoFormErrors();

        Livewire::test(ListWorkSchedules::class)
            ->callAction('create', data: [
                'name' => 'Reguler',
                'check_in_time' => '08:00',
                'check_out_time' => '17:00',
                'late_tolerance_minutes' => 15,
                'alfa_cutoff_minutes' => 120,
            ])
            ->assertHasNoFormErrors();

        Livewire::test(ListDepartments::class)
            ->callAction('create', data: [
                'name' => 'Operations',
                'code' => 'OPS',
            ])
            ->assertHasNoFormErrors();

        $requiredSkill = Skill::create(['name' => 'Leadership', 'category' => 'Behavioral']);

        Livewire::test(ListPositions::class)
            ->callAction('create', data: [
                'department_id' => $department->id,
                'title' => 'HR Officer',
                'level' => 2,
                'positionSkills' => [[
                    'skill_id' => $requiredSkill->id,
                    'min_required_level' => 3,
                ]],
            ])
            ->assertHasNoFormErrors();

        Livewire::test(ListSkills::class)
            ->callAction('create', data: [
                'name' => 'Recruitment',
                'category' => 'Functional',
            ])
            ->assertHasNoFormErrors();

        $this->actingAs(User::factory()->create([
            'position_id' => $position->id,
            'work_schedule_id' => $schedule->id,
            'branch_office_id' => $branch->id,
            'role' => UserRole::ItAdmin,
        ]));
        $manager = User::factory()->create([
            'position_id' => $position->id,
            'work_schedule_id' => $schedule->id,
            'branch_office_id' => $branch->id,
            'role' => UserRole::Manager,
        ]);

        Livewire::test(ListUsers::class)
            ->callAction('create', data: [
                'nip' => 'EMP-001',
                'name' => 'New Employee',
                'email' => 'new.employee@example.com',
                'password' => 'password',
                'position_id' => $position->id,
                'work_schedule_id' => $schedule->id,
                'branch_office_id' => $branch->id,
                'manager_id' => $manager->id,
                'join_date' => '2026-08-01',
                'role' => UserRole::Employee->value,
                'status' => true,
            ])
            ->assertHasNoFormErrors();

        Livewire::test(ListUsers::class)
            ->callAction('create', data: [
                'nip' => 'EMP-002',
                'name' => 'Employee Without Manager',
                'email' => 'no.manager@example.com',
                'password' => 'password',
                'position_id' => $position->id,
                'work_schedule_id' => $schedule->id,
                'branch_office_id' => $branch->id,
                'join_date' => '2026-08-01',
                'role' => UserRole::Employee->value,
                'status' => true,
            ])
            ->assertHasFormErrors(['manager_id' => 'required']);

        $employee = User::query()->where('nip', 'EMP-001')->firstOrFail();
        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('edit')->table($employee), data: ['manager_id' => null])
            ->assertHasFormErrors(['manager_id' => 'required']);

        $this->assertTrue(Holiday::query()->whereDate('date', '2026-08-17')->exists());
        $this->assertDatabaseHas(BranchOffice::class, ['code' => 'JKT']);
        $this->assertDatabaseHas(WorkSchedule::class, ['name' => 'Reguler']);
        $this->assertDatabaseHas(Department::class, ['code' => 'OPS']);
        $this->assertDatabaseHas(Position::class, ['title' => 'HR Officer']);
        $this->assertDatabaseHas('position_skills', [
            'position_id' => Position::query()->where('title', 'HR Officer')->value('id'),
            'skill_id' => $requiredSkill->id,
            'min_required_level' => 3,
        ]);
        $this->assertDatabaseHas(Skill::class, ['name' => 'Recruitment']);
        $this->assertDatabaseHas(User::class, ['nip' => 'EMP-001']);

        $this->actingAs($hrAdmin);
        $holiday = Holiday::query()->whereDate('date', '2026-08-17')->firstOrFail();
        Livewire::test(ListHolidays::class)
            ->callAction(TestAction::make('delete')->table($holiday));

        $this->assertModelMissing($holiday);
        $this->assertFalse(DailyAttendanceSummary::query()
            ->whereDate('date', '2026-08-17')
            ->where('status', DailySummaryStatus::Holiday)
            ->exists());

        Livewire::test(ListBranchOffices::class)
            ->assertActionExists(TestAction::make('edit')->table(BranchOffice::first()));
    }

    public function test_work_schedule_rejects_checkout_before_checkin(): void
    {
        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('Jam pulang harus setelah jam masuk.');

        WorkSchedule::create([
            'name' => 'Terbalik',
            'check_in_time' => '17:00',
            'check_out_time' => '08:00',
            'late_tolerance_minutes' => 15,
            'alfa_cutoff_minutes' => 120,
        ]);
    }

    public function test_active_employee_without_manager_is_rejected_on_create_and_update(): void
    {
        $this->seed();
        $employee = User::query()->where('role', UserRole::Employee)->firstOrFail();

        try {
            User::create([
                ...$employee->only([
                    'position_id',
                    'work_schedule_id',
                    'branch_office_id',
                    'join_date',
                    'status',
                    'role',
                ]),
                'nip' => 'EMP-NO-MANAGER',
                'name' => 'Employee Without Manager',
                'email' => 'employee.no.manager@example.com',
                'password' => 'password',
            ]);
            $this->fail('Creating an active Employee without a manager should fail.');
        } catch (BusinessRuleException $exception) {
            $this->assertSame('Employee aktif wajib memiliki atasan langsung.', $exception->getMessage());
        }

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('Employee aktif wajib memiliki atasan langsung.');

        $employee->update(['manager_id' => null]);
    }

    public function test_employee_assignment_and_manager_changes_share_a_transactional_invariant(): void
    {
        $this->seed();
        $template = User::query()->where('role', UserRole::Employee)->firstOrFail();
        $organization = $template->only([
            'position_id',
            'work_schedule_id',
            'branch_office_id',
        ]);
        $firstManager = User::factory()->create([
            ...$organization,
            'role' => UserRole::Manager,
        ]);
        $secondManager = User::factory()->create([
            ...$organization,
            'role' => UserRole::Manager,
        ]);
        $employee = User::factory()->create([
            ...$organization,
            'role' => UserRole::Employee,
            'manager_id' => $firstManager->id,
        ]);
        $assignmentTransactionLevel = 0;

        User::saving(function (User $user) use ($employee, &$assignmentTransactionLevel): void {
            if ($user->is($employee)) {
                $assignmentTransactionLevel = DB::transactionLevel();
            }
        });

        try {
            $firstManager->update(['status' => false]);
            $this->fail('Deactivating a Manager with a subordinate should fail.');
        } catch (BusinessRuleException $exception) {
            $this->assertSame(
                'Manager yang masih memiliki bawahan tidak dapat dinonaktifkan atau diubah perannya.',
                $exception->getMessage(),
            );
        }

        $employee->update(['manager_id' => $secondManager->id]);
        $firstManager->update(['status' => false]);

        $this->assertGreaterThan(0, $assignmentTransactionLevel);
        $this->assertFalse($firstManager->fresh()->status);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('Manager yang masih memiliki bawahan tidak dapat dinonaktifkan atau diubah perannya.');

        $secondManager->update(['role' => UserRole::HrAdmin]);
    }
}
