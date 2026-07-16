<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\DutyTripStatus;
use App\Enums\MentoringStatus;
use App\Enums\TrainingRequestStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\DutyTrip;
use App\Models\Mentoring;
use App\Models\Position;
use App\Models\ReviewPeriod;
use App\Models\Training;
use App\Models\TrainingRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OperationsReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_report_is_scoped_filtered_and_exported_safely(): void
    {
        [$hr, $first, $second, $firstUnit] = $this->employees();

        $this->actingAs($first)->get(route('hr.reports.index'))->assertForbidden();

        $this->actingAs($hr)
            ->get(route('hr.reports.index', ['unit_id' => $firstUnit->id]))
            ->assertOk()
            ->assertSee('Kembali ke Panel HR')
            ->assertDontSee('← Kembali ke panel HR')
            ->assertSee($first->name)
            ->assertDontSee($second->name);

        $response = $this->get(route('hr.reports.export', ['unit_id' => $firstUnit->id]))->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString($first->name, $csv);
        $this->assertStringNotContainsString($second->name, $csv);
        $this->assertStringContainsString("'=1+1", $csv);
    }

    public function test_private_photo_and_retention_respect_access(): void
    {
        Storage::fake('local');
        [$hr, $employee, $other] = $this->employees();
        $trip = DutyTrip::create([
            'employee_id' => $employee->id,
            'manager_id' => $employee->manager_id,
            'destination' => 'Audit',
            'purpose' => 'Audit',
            'starts_at' => now()->subYears(2),
            'ends_at' => now()->subYears(2)->addHour(),
            'location_name' => 'Kantor',
            'address' => 'Jakarta',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius_meters' => 100,
            'status' => DutyTripStatus::Completed,
        ]);
        Storage::disk('local')->put('attendance/private.jpg', 'photo');
        $attendance = Attendance::create([
            'client_uuid' => '40f26f3e-b3b3-49f6-9bcb-c31ec9862201',
            'duty_trip_id' => $trip->id,
            'employee_id' => $employee->id,
            'captured_at' => now()->subYears(2),
            'latitude' => -6.2,
            'longitude' => 106.8,
            'distance_meters' => 0,
            'photo_path' => 'attendance/private.jpg',
            'status' => AttendanceStatus::Valid,
        ]);

        $this->actingAs($other)->get(route('attendance.photo', $attendance))->assertForbidden();
        $this->actingAs($employee)->get(route('attendance.photo', $attendance))->assertOk();

        Artisan::call('attendance:purge-photos', ['--days' => 365]);
        Storage::disk('local')->assertMissing('attendance/private.jpg');
        $this->get(route('attendance.photo', $attendance))->assertNotFound();
    }

    public function test_report_period_scopes_development_activity(): void
    {
        [$hr, $employee] = $this->employees();
        $period = ReviewPeriod::create([
            'name' => 'Periode aktif', 'starts_at' => today(), 'ends_at' => today()->addDays(10),
            'kpi_weight' => 40, 'discipline_weight' => 20, 'manager_weight' => 20,
            'review_360_weight' => 20, 'base_bonus' => 0, 'is_active' => true,
        ]);
        $firstTraining = Training::create(['name' => 'Dalam periode', 'type' => 'internal', 'is_active' => true]);
        $secondTraining = Training::create(['name' => 'Luar periode', 'type' => 'internal', 'is_active' => true]);
        foreach ([[$firstTraining, now()->addDay()], [$secondTraining, now()->addDays(20)]] as [$training, $requestedAt]) {
            TrainingRequest::create([
                'user_id' => $employee->id, 'training_id' => $training->id, 'manager_id' => $employee->manager_id,
                'status' => TrainingRequestStatus::Completed, 'reason' => 'Pengembangan', 'requested_at' => $requestedAt,
            ]);
            Mentoring::create([
                'employee_id' => $employee->id, 'manager_id' => $employee->manager_id,
                'status' => MentoringStatus::Completed, 'topic' => $training->name,
                'target' => 'Target', 'requested_at' => $requestedAt,
            ]);
        }

        $response = $this->actingAs($hr)->get(route('hr.reports.index', ['review_period_id' => $period->id]))->assertOk();
        $row = $response->viewData('rows')->firstWhere('name', $employee->name);

        $this->assertSame(1, $row['training_count']);
        $this->assertSame(1, $row['completed_training_count']);
        $this->assertSame(1, $row['mentoring_count']);
        $this->assertSame(1, $row['completed_mentoring_count']);
    }

    /** @return array{User, User, User, Unit} */
    private function employees(): array
    {
        $firstUnit = Unit::create(['name' => 'Satu', 'code' => 'SATU']);
        $secondUnit = Unit::create(['name' => 'Dua', 'code' => 'DUA']);
        $firstPosition = Position::create(['unit_id' => $firstUnit->id, 'name' => 'Staf Satu', 'level' => 1]);
        $secondPosition = Position::create(['unit_id' => $secondUnit->id, 'name' => 'Staf Dua', 'level' => 1]);
        $firstManager = User::factory()->create(['role' => UserRole::Manager, 'unit_id' => $firstUnit->id]);
        $secondManager = User::factory()->create(['role' => UserRole::Manager, 'unit_id' => $secondUnit->id]);
        $first = User::factory()->create([
            'name' => 'Pegawai Satu', 'employee_number' => '=1+1', 'role' => UserRole::Employee,
            'unit_id' => $firstUnit->id, 'position_id' => $firstPosition->id, 'manager_id' => $firstManager->id,
        ]);
        $second = User::factory()->create([
            'name' => 'Pegawai Dua', 'role' => UserRole::Employee,
            'unit_id' => $secondUnit->id, 'position_id' => $secondPosition->id, 'manager_id' => $secondManager->id,
        ]);
        $hr = User::factory()->create(['role' => UserRole::Hr]);

        return [$hr, $first, $second, $firstUnit];
    }
}
