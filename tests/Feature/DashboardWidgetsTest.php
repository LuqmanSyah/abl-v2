<?php

namespace Tests\Feature;

use App\Enums\AttendanceRequestStatus;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\DailySummaryStatus;
use App\Enums\FlowType;
use App\Enums\UserRole;
use App\Filament\Widgets\Admin\AttendanceDropAlert;
use App\Filament\Widgets\Admin\CandidatePoolTable;
use App\Filament\Widgets\Admin\HrAttendanceOverview;
use App\Filament\Widgets\Admin\MeritDistribution;
use App\Filament\Widgets\Admin\PendingApprovals;
use App\Filament\Widgets\Employee\ActiveDutyTrips;
use App\Filament\Widgets\Employee\CareerReadiness;
use App\Filament\Widgets\Employee\IdpProgress;
use App\Filament\Widgets\Employee\LatestMeritGrade;
use App\Filament\Widgets\Employee\TodayAttendanceStatus;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\BranchOffice;
use App\Models\CareerPath;
use App\Models\DailyAttendanceSummary;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    private User $otherEmployee;

    private User $manager;

    private User $hr;

    private User $director;

    private Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $this->position = Position::create(['department_id' => $department->id, 'title' => 'Engineer', 'level' => 1]);
        $schedule = WorkSchedule::create([
            'name' => 'Regular',
            'check_in_time' => '08:00',
            'check_out_time' => '17:00',
            'late_tolerance_minutes' => 15,
            'alfa_cutoff_minutes' => 120,
        ]);
        $branch = BranchOffice::create([
            'name' => 'Jakarta',
            'code' => 'JKT',
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'allowed_radius_meters' => 100,
        ]);
        $organization = [
            'position_id' => $this->position->id,
            'work_schedule_id' => $schedule->id,
            'branch_office_id' => $branch->id,
        ];

        $this->manager = User::factory()->create([...$organization, 'role' => UserRole::Manager]);
        $this->employee = User::factory()->create([...$organization, 'manager_id' => $this->manager->id]);
        $this->otherEmployee = User::factory()->create([...$organization, 'manager_id' => $this->manager->id]);
        $this->hr = User::factory()->create([...$organization, 'role' => UserRole::HrAdmin]);
        $this->director = User::factory()->create([...$organization, 'role' => UserRole::Director]);
    }

    public function test_panels_discover_expected_widgets(): void
    {
        $this->assertEqualsCanonicalizing([
            TodayAttendanceStatus::class,
            ActiveDutyTrips::class,
            LatestMeritGrade::class,
            IdpProgress::class,
            CareerReadiness::class,
        ], Filament::getPanel('employee')->getWidgets());
        $this->assertEqualsCanonicalizing([
            HrAttendanceOverview::class,
            PendingApprovals::class,
            CandidatePoolTable::class,
            MeritDistribution::class,
            AttendanceDropAlert::class,
        ], Filament::getPanel('admin')->getWidgets());
    }

    public function test_widgets_render_for_intended_roles(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('employee'));
        $this->actingAs($this->employee);
        Livewire::test(TodayAttendanceStatus::class)->assertSee('Kantor');
        Livewire::test(ActiveDutyTrips::class)->assertSee('Tugas Luar Aktif');
        Livewire::test(LatestMeritGrade::class)->assertSee('Merit Terakhir');
        Livewire::test(IdpProgress::class)->assertSee('Progress IDP');
        Livewire::test(CareerReadiness::class)->assertSee('Career Readiness');

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->manager);
        $this->assertTrue(PendingApprovals::canView());
        $this->assertFalse(HrAttendanceOverview::canView());
        $this->assertFalse(CandidatePoolTable::canView());
        Livewire::test(PendingApprovals::class)->assertSee('Izin Dinas Pending');

        DailyAttendanceSummary::create([
            'user_id' => $this->employee->id,
            'date' => today(),
            'status' => DailySummaryStatus::Present,
            'late_minutes' => 0,
        ]);
        $this->actingAs($this->hr);
        $this->assertTrue(HrAttendanceOverview::canView());
        $this->assertTrue(CandidatePoolTable::canView());
        $this->assertTrue(AttendanceDropAlert::canView());
        $this->assertTrue(PendingApprovals::canView());
        Livewire::test(PendingApprovals::class)->assertSee('Cuti Pending');
        Livewire::test(HrAttendanceOverview::class)
            ->assertSee('Kehadiran Hari Ini')
            ->assertSee('Technology')
            ->assertSee('100%');
        Livewire::test(CandidatePoolTable::class)->assertSee('Candidate Pool 30 Hari');
        Livewire::test(AttendanceDropAlert::class)->assertSee('Tingkat Kehadiran Bulan Ini');

        $this->actingAs($this->director);
        $this->assertTrue(CandidatePoolTable::canView());
        $this->assertTrue(MeritDistribution::canView());
        $this->assertFalse(AttendanceDropAlert::canView());
        Livewire::test(CandidatePoolTable::class)->assertSee('Candidate Pool 30 Hari');
        Livewire::test(MeritDistribution::class)->assertSee('Distribusi Grade Merit');
    }

    public function test_active_duty_widget_only_shows_authenticated_employee_records(): void
    {
        $this->duty($this->employee, 'Client A');
        $this->duty($this->otherEmployee, 'Client B');

        Filament::setCurrentPanel(Filament::getPanel('employee'));
        $this->actingAs($this->employee);

        Livewire::test(ActiveDutyTrips::class)
            ->assertSee('Client A')
            ->assertDontSee('Client B');
    }

    public function test_career_widget_shows_every_path_for_employee_position(): void
    {
        $senior = Position::create([
            'department_id' => $this->position->department_id,
            'title' => 'Senior Engineer',
            'level' => 2,
        ]);
        $lead = Position::create([
            'department_id' => $this->position->department_id,
            'title' => 'Tech Lead',
            'level' => 3,
        ]);
        $other = Position::create([
            'department_id' => $this->position->department_id,
            'title' => 'Product Owner',
            'level' => 2,
        ]);
        foreach ([$senior, $lead] as $target) {
            CareerPath::create([
                'current_position_id' => $this->position->id,
                'next_position_id' => $target->id,
                'min_experience_months' => 12,
                'min_merit_grade' => 'B',
            ]);
        }
        CareerPath::create([
            'current_position_id' => $other->id,
            'next_position_id' => $lead->id,
            'min_experience_months' => 12,
            'min_merit_grade' => 'B',
        ]);

        Filament::setCurrentPanel(Filament::getPanel('employee'));
        $this->actingAs($this->employee);

        Livewire::test(CareerReadiness::class)
            ->assertSee('Senior Engineer')
            ->assertSee('Tech Lead')
            ->assertDontSee('Product Owner');
    }

    public function test_today_attendance_widget_keeps_sessions_separate(): void
    {
        $this->travelTo(now()->setDate(2026, 8, 1)->setTime(10, 0));
        $duty = $this->duty($this->employee, 'Client A');
        $base = [
            'user_id' => $this->employee->id,
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'distance_to_target_meters' => 10,
            'address_snapshot' => 'Jakarta',
            'photo_path' => 'attendance/test.jpg',
            'status' => AttendanceStatus::Normal,
        ];

        Attendance::create($base + [
            'type' => AttendanceType::CheckIn,
            'recorded_at' => '2026-08-01 08:00:00',
        ]);
        Attendance::create($base + [
            'type' => AttendanceType::CheckOut,
            'recorded_at' => '2026-08-01 09:00:00',
        ]);
        Attendance::create($base + [
            'attendance_request_id' => $duty->id,
            'type' => AttendanceType::CheckIn,
            'recorded_at' => '2026-08-01 08:30:00',
        ]);
        $this->travelTo(now()->addHours(2));

        Filament::setCurrentPanel(Filament::getPanel('employee'));
        $this->actingAs($this->employee);

        Livewire::test(TodayAttendanceStatus::class)
            ->assertSee('Kantor')
            ->assertSee('Masuk 08:00 · Pulang 09:00')
            ->assertSee('Tugas Luar: Client A')
            ->assertSee('Masuk 08:30 · Pulang Belum')
            ->assertSee('Belum check-out');
    }

    private function duty(User $user, string $destination): AttendanceRequest
    {
        return AttendanceRequest::create([
            'user_id' => $user->id,
            'created_by' => $this->manager->id,
            'flow_type' => FlowType::TopDown,
            'destination_name' => $destination,
            'destination_address' => 'Jakarta',
            'target_latitude' => -6.1754,
            'target_longitude' => 106.8272,
            'allowed_radius_meters' => 100,
            'duty_start_datetime' => now()->subHour(),
            'duty_end_datetime' => now()->addHour(),
            'reason' => 'Meeting',
            'status' => AttendanceRequestStatus::Approved,
            'approved_by' => $this->manager->id,
        ]);
    }
}
