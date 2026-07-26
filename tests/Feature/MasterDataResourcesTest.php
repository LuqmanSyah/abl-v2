<?php

namespace Tests\Feature;

use App\Enums\DailySummaryStatus;
use App\Enums\UserRole;
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
            ->assertMountedActionModalSeeHtml('Peta lokasi kantor')
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

        Livewire::test(ListUsers::class)
            ->callAction('create', data: [
                'nip' => 'EMP-001',
                'name' => 'New Employee',
                'email' => 'new.employee@example.com',
                'password' => 'password',
                'position_id' => $position->id,
                'work_schedule_id' => $schedule->id,
                'branch_office_id' => $branch->id,
                'join_date' => '2026-08-01',
                'role' => UserRole::Employee->value,
                'status' => true,
            ])
            ->assertHasNoFormErrors();

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
}
