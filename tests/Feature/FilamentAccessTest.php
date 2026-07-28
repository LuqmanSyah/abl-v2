<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\DutyTripStatus;
use App\Enums\ReviewType;
use App\Enums\UserRole;
use App\Filament\Resources\Attendances\AttendanceResource;
use App\Filament\Resources\Attendances\Pages\ListAttendances;
use App\Filament\Resources\Attendances\Pages\ViewAttendance;
use App\Filament\Resources\CareerGoals\CareerGoalResource;
use App\Filament\Resources\CareerGoals\Pages\ListCareerGoals;
use App\Filament\Resources\DutyTrips\DutyTripResource;
use App\Filament\Resources\DutyTrips\Pages\ListDutyTrips;
use App\Filament\Resources\DutyTrips\Pages\ViewDutyTrip;
use App\Filament\Resources\EmployeeCompetencies\EmployeeCompetencyResource;
use App\Filament\Resources\EmployeeCompetencies\Pages\ListEmployeeCompetencies;
use App\Filament\Resources\EmployeeKpis\EmployeeKpiResource;
use App\Filament\Resources\EmployeeKpis\Pages\ListEmployeeKpis;
use App\Filament\Resources\EmployeeKpis\Pages\ViewEmployeeKpi;
use App\Filament\Resources\Mentorings\MentoringResource;
use App\Filament\Resources\Mentorings\Pages\ListMentorings;
use App\Filament\Resources\MeritResults\MeritResultResource;
use App\Filament\Resources\MeritResults\Pages\ListMeritResults;
use App\Filament\Resources\MeritResults\Pages\ViewMeritResult;
use App\Filament\Resources\PerformanceReviews\Pages\ListPerformanceReviews;
use App\Filament\Resources\PerformanceReviews\Pages\ViewPerformanceReview;
use App\Filament\Resources\PerformanceReviews\PerformanceReviewResource;
use App\Filament\Resources\Positions\PositionResource;
use App\Filament\Resources\ReviewPeriods\ReviewPeriodResource;
use App\Filament\Resources\TrainingRequests\Pages\ListTrainingRequests;
use App\Filament\Resources\TrainingRequests\TrainingRequestResource;
use App\Filament\Resources\Trainings\Pages\ListTrainings;
use App\Filament\Resources\Trainings\TrainingResource;
use App\Filament\Resources\Units\UnitResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\EmployeeStats;
use App\Filament\Widgets\HrStats;
use App\Filament\Widgets\ManagerStats;
use App\Models\Attendance;
use App\Models\DutyTrip;
use App\Models\EmployeeKpi;
use App\Models\KpiIndicator;
use App\Models\MeritResult;
use App\Models\PerformanceReview;
use App\Models\Position;
use App\Models\ReviewPeriod;
use App\Models\Training;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
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

        $this->actingAs($employee)->get('/hr')
            ->assertRedirect('/pegawai')
            ->assertSessionHas('filament.notifications');
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

    public function test_shared_resource_navigation_uses_formal_role_labels(): void
    {
        $expected = [
            UserRole::Employee->value => [
                DutyTripResource::class => 'Pelaksanaan Dinas',
                AttendanceResource::class => 'Riwayat Absensi',
                EmployeeKpiResource::class => 'Capaian KPI',
                PerformanceReviewResource::class => 'Umpan Balik Kinerja',
                MeritResultResource::class => 'Hasil Merit',
                EmployeeCompetencyResource::class => 'Profil Kompetensi',
                CareerGoalResource::class => 'Rencana Karier',
                TrainingResource::class => 'Katalog Pelatihan',
                TrainingRequestResource::class => 'Pengajuan Pelatihan',
                MentoringResource::class => 'Pengajuan Mentoring',
            ],
            UserRole::Manager->value => [
                DutyTripResource::class => 'Pengelolaan Dinas',
                AttendanceResource::class => 'Monitoring Absensi',
                EmployeeKpiResource::class => 'Pengelolaan KPI',
                PerformanceReviewResource::class => 'Umpan Balik Kinerja',
                MeritResultResource::class => 'Verifikasi Merit',
                EmployeeCompetencyResource::class => 'Monitoring Kompetensi',
                CareerGoalResource::class => 'Monitoring Karier',
                TrainingResource::class => 'Katalog Pelatihan',
                TrainingRequestResource::class => 'Persetujuan Pelatihan',
                MentoringResource::class => 'Pengelolaan Mentoring',
            ],
            UserRole::Hr->value => [
                DutyTripResource::class => 'Monitoring Dinas',
                AttendanceResource::class => 'Monitoring Absensi',
                EmployeeKpiResource::class => 'Monitoring KPI',
                PerformanceReviewResource::class => 'Umpan Balik Kinerja',
                MeritResultResource::class => 'Publikasi Merit',
                EmployeeCompetencyResource::class => 'Pengelolaan Kompetensi Pegawai',
                CareerGoalResource::class => 'Monitoring Karier',
                TrainingResource::class => 'Pengelolaan Pelatihan',
                TrainingRequestResource::class => 'Verifikasi Pelatihan',
                MentoringResource::class => 'Monitoring Mentoring',
            ],
        ];

        foreach (UserRole::cases() as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));

            foreach ($expected[$role->value] as $resource => $label) {
                $this->assertSame($label, $resource::getNavigationLabel());
                $this->assertStringNotContainsString('360', $label);
            }
        }
    }

    public function test_kpi_bulk_delete_is_only_available_to_manager(): void
    {
        foreach (UserRole::cases() as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));

            $this->assertSame($role === UserRole::Manager, EmployeeKpiResource::canDeleteAny());
        }
    }

    public function test_create_buttons_follow_each_role_access(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $users = [
            'employee' => User::factory()->create([
                'role' => UserRole::Employee,
                'manager_id' => $manager->id,
            ]),
            'manager' => $manager,
            'hr' => User::factory()->create(['role' => UserRole::Hr]),
        ];
        $expectations = [
            'employee' => [
                ListDutyTrips::class => false,
                ListEmployeeKpis::class => false,
                ListPerformanceReviews::class => true,
                ListEmployeeCompetencies::class => false,
                ListCareerGoals::class => true,
                ListTrainings::class => false,
                ListTrainingRequests::class => true,
                ListMentorings::class => true,
            ],
            'manager' => [
                ListDutyTrips::class => true,
                ListEmployeeKpis::class => true,
                ListPerformanceReviews::class => true,
                ListEmployeeCompetencies::class => false,
                ListCareerGoals::class => false,
                ListTrainings::class => false,
                ListTrainingRequests::class => false,
                ListMentorings::class => false,
            ],
            'hr' => [
                ListDutyTrips::class => false,
                ListEmployeeKpis::class => false,
                ListPerformanceReviews::class => false,
                ListEmployeeCompetencies::class => true,
                ListCareerGoals::class => false,
                ListTrainings::class => true,
                ListTrainingRequests::class => false,
                ListMentorings::class => false,
            ],
        ];

        foreach ($users as $panel => $user) {
            filament()->setCurrentPanel($panel);
            $this->actingAs($user);

            foreach ($expectations[$panel] as $page => $isVisible) {
                $component = Livewire::test($page);

                $isVisible
                    ? $component->assertActionVisible('create')
                    : $component->assertActionHidden('create');
            }

            Livewire::test(ListAttendances::class)
                ->assertActionDoesNotExist('create');
            Livewire::test(ListMeritResults::class)
                ->assertActionDoesNotExist('create');
        }
    }

    public function test_detail_pages_render_grouped_information(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
            'manager_id' => $manager->id,
        ]);
        $hr = User::factory()->create(['role' => UserRole::Hr]);
        $period = ReviewPeriod::create([
            'name' => 'Semester Detail',
            'starts_at' => today()->startOfMonth(),
            'ends_at' => today()->endOfMonth(),
            'kpi_weight' => 40,
            'discipline_weight' => 20,
            'manager_weight' => 20,
            'review_360_weight' => 20,
        ]);
        $indicator = KpiIndicator::create([
            'review_period_id' => $period->id,
            'name' => 'Kualitas hasil',
            'weight' => 100,
        ]);
        $kpi = EmployeeKpi::create([
            'review_period_id' => $period->id,
            'kpi_indicator_id' => $indicator->id,
            'employee_id' => $employee->id,
            'manager_id' => $manager->id,
            'target' => 100,
            'achievement' => 80,
        ]);
        $review = PerformanceReview::create([
            'review_period_id' => $period->id,
            'reviewer_id' => $manager->id,
            'reviewee_id' => $employee->id,
            'type' => ReviewType::ManagerToEmployee,
            'score' => 4,
            'submitted_at' => now(),
        ]);
        $trip = DutyTrip::create([
            'employee_id' => $employee->id,
            'manager_id' => $manager->id,
            'destination' => 'Kunjungan detail',
            'purpose' => 'Verifikasi tampilan',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'location_name' => 'Kantor tujuan',
            'address' => 'Jakarta',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius_meters' => 100,
            'status' => DutyTripStatus::Approved,
            'approved_at' => now(),
        ]);
        $attendance = Attendance::create([
            'duty_trip_id' => $trip->id,
            'employee_id' => $employee->id,
            'captured_at' => now(),
            'latitude' => -6.2,
            'longitude' => 106.8,
            'accuracy_meters' => 10,
            'distance_meters' => 5,
            'photo_path' => 'attendance/detail.jpg',
            'status' => AttendanceStatus::Valid,
        ]);
        $merit = MeritResult::create([
            'review_period_id' => $period->id,
            'employee_id' => $employee->id,
            'kpi_score' => 80,
            'discipline_score' => 100,
            'manager_score' => 80,
            'review_360_score' => 80,
            'total_score' => 84,
            'estimated_bonus' => 840000,
            'manager_verified_by' => $manager->id,
            'manager_verified_at' => now(),
        ]);

        filament()->setCurrentPanel('hr');
        $this->actingAs($hr);

        Livewire::test(ViewDutyTrip::class, ['record' => $trip->getRouteKey()])
            ->assertSee('Informasi penugasan');
        Livewire::test(ViewAttendance::class, ['record' => $attendance->getRouteKey()])
            ->assertSee('Lokasi dan bukti');
        Livewire::test(ViewEmployeeKpi::class, ['record' => $kpi->getRouteKey()])
            ->assertSee('Target dan capaian')
            ->assertActionHidden('edit');
        Livewire::test(ViewPerformanceReview::class, ['record' => $review->getRouteKey()])
            ->assertSee('Hasil penilaian');
        Livewire::test(ViewMeritResult::class, ['record' => $merit->getRouteKey()])
            ->assertSee('Status verifikasi');

        filament()->setCurrentPanel('manager');
        $this->actingAs($manager);
        Livewire::test(ViewEmployeeKpi::class, ['record' => $kpi->getRouteKey()])
            ->assertActionVisible('edit');

        filament()->setCurrentPanel('employee');
        $this->actingAs($employee);
        Livewire::test(ViewEmployeeKpi::class, ['record' => $kpi->getRouteKey()])
            ->assertActionHidden('edit');
    }

    public function test_role_resource_pages_render(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'manager_id' => $manager->id]);
        $hr = User::factory()->create(['role' => UserRole::Hr]);

        $this->actingAs($employee)->get('/pegawai')->assertOk()->assertSee('portal-filament.css', false);
        $this->assertContains(EmployeeStats::class, filament()->getPanel('employee')->getWidgets());
        $this->actingAs($employee)->get('/pegawai/duty-trips/create')
            ->assertRedirect('/pegawai')
            ->assertSessionHas('filament.notifications');
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
