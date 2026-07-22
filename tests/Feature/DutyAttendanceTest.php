<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\DutyTripStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Attendances\Pages\ListAttendances;
use App\Filament\Resources\DutyTrips\Pages\ListDutyTrips;
use App\Filament\Widgets\EmployeeActiveTripsTable;
use App\Models\DutyTrip;
use App\Models\User;
use App\Services\AttendanceRecorder;
use App\Support\GeoDistance;
use DomainException;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DutyAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_haversine_returns_distance_in_meters(): void
    {
        $this->assertSame(111, GeoDistance::meters(-6.1754, 106.8272, -6.1744, 106.8272));
    }

    public function test_only_assigned_manager_can_change_or_cancel_future_trip(): void
    {
        [$employee, $manager, $trip] = $this->trip();
        $trip->update(['starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2)]);
        $otherManager = User::factory()->create(['role' => UserRole::Manager]);

        $this->assertFalse($trip->canBeChangedBy($otherManager));
        $this->assertTrue($trip->canBeChangedBy($manager));

        try {
            $trip->cancel($otherManager);
            $this->fail('Atasan lain seharusnya ditolak.');
        } catch (DomainException) {
            $this->assertSame(DutyTripStatus::Approved, $trip->status);
        }

        $trip->cancel($manager);
        $this->assertSame(DutyTripStatus::Cancelled, $trip->fresh()->status);
    }

    public function test_manager_cannot_assign_another_managers_subordinate(): void
    {
        [$employee, $manager, $trip] = $this->trip();
        $otherManager = User::factory()->create(['role' => UserRole::Manager]);
        $otherEmployee = User::factory()->create(['role' => UserRole::Employee, 'manager_id' => $otherManager->id]);

        $this->expectException(DomainException::class);
        $trip->update(['employee_id' => $otherEmployee->id]);
    }

    public function test_attendance_is_valid_inside_radius_and_idempotent(): void
    {
        [$employee, $manager, $trip] = $this->trip();
        $payload = [
            'client_uuid' => '20f26f3e-b3b3-49f6-9bcb-c31ec9862217',
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
        ];

        $recorder = app(AttendanceRecorder::class);
        $first = $recorder->record($trip, $employee, $payload, 'attendance/photo.jpg');
        $second = $recorder->record($trip, $employee, $payload, 'attendance/duplicate.jpg');

        $this->assertSame(AttendanceStatus::Valid, $first->status);
        $this->assertTrue($first->is($second));
        $this->assertSame(DutyTripStatus::Approved, $trip->fresh()->status);
        $this->assertDatabaseCount('attendances', 1);

        $this->expectException(DomainException::class);
        $trip->update(['latitude' => -7.0]);
    }

    public function test_multi_day_trip_accepts_one_attendance_per_captured_day(): void
    {
        [$employee, $manager, $trip] = $this->trip();
        $trip->update([
            'starts_at' => now()->subDay()->startOfHour(),
            'ends_at' => now()->addDay()->endOfHour(),
        ]);
        $recorder = app(AttendanceRecorder::class);

        $first = $recorder->record($trip, $employee, [
            'client_uuid' => '20f26f3e-b3b3-49f6-9bcb-c31ec9862301',
            'captured_at' => now()->subDay()->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
        ], 'attendance/day-one.jpg');
        $second = $recorder->record($trip, $employee, [
            'client_uuid' => '20f26f3e-b3b3-49f6-9bcb-c31ec9862302',
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
        ], 'attendance/day-two.jpg');

        $this->assertFalse($first->is($second));
        $this->assertDatabaseCount('attendances', 2);
    }

    public function test_offline_duplicate_uses_captured_date(): void
    {
        [$employee, $manager, $trip] = $this->trip();
        $trip->update([
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->addDay(),
        ]);
        $payload = [
            'captured_at' => now()->subDay()->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
        ];
        $recorder = app(AttendanceRecorder::class);

        $first = $recorder->record($trip, $employee, [
            ...$payload,
            'client_uuid' => '20f26f3e-b3b3-49f6-9bcb-c31ec9862303',
        ], 'attendance/offline-one.jpg');
        $duplicate = $recorder->record($trip, $employee, [
            ...$payload,
            'client_uuid' => '20f26f3e-b3b3-49f6-9bcb-c31ec9862304',
        ], 'attendance/offline-two.jpg');

        $this->assertTrue($first->is($duplicate));
        $this->assertSame(now()->subDay()->toDateString(), $first->attendance_date->toDateString());
        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_outside_radius_and_poor_accuracy_are_flagged(): void
    {
        [$employee, $manager, $trip] = $this->trip();
        $attendance = app(AttendanceRecorder::class)->record($trip, $employee, [
            'client_uuid' => '20f26f3e-b3b3-49f6-9bcb-c31ec9862218',
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1800,
            'longitude' => 106.8272,
            'accuracy_meters' => 150,
        ], 'attendance/photo.jpg');

        $this->assertSame(AttendanceStatus::NeedsReview, $attendance->status);
        $this->assertTrue($attendance->mock_location_suspected);
    }

    public function test_outside_radius_and_late_attendance_are_classified(): void
    {
        [$employee, $manager, $trip] = $this->trip();
        $outside = app(AttendanceRecorder::class)->record($trip, $employee, [
            'client_uuid' => '20f26f3e-b3b3-49f6-9bcb-c31ec9862219',
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1800,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
        ], 'attendance/outside.jpg');

        [$lateEmployee, $lateManager, $lateTrip] = $this->trip();
        $lateTrip->update(['starts_at' => now()->subHours(3), 'ends_at' => now()->subHours(2)]);
        $late = app(AttendanceRecorder::class)->record($lateTrip, $lateEmployee, [
            'client_uuid' => '20f26f3e-b3b3-49f6-9bcb-c31ec9862220',
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
        ], 'attendance/late.jpg');

        $this->assertSame(AttendanceStatus::OutsideRadius, $outside->status);
        $this->assertSame(AttendanceStatus::Late, $late->status);
    }

    public function test_backdated_attendance_requires_review(): void
    {
        [$employee, $manager, $trip] = $this->trip();
        $trip->update([
            'starts_at' => now()->subDay()->startOfHour(),
            'ends_at' => now()->subDay()->addHour()->startOfHour(),
        ]);

        $attendance = app(AttendanceRecorder::class)->record($trip, $employee, [
            'client_uuid' => '20f26f3e-b3b3-49f6-9bcb-c31ec9862222',
            'captured_at' => $trip->starts_at->addMinutes(30)->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
        ], 'attendance/backdated.jpg');

        $this->assertSame(AttendanceStatus::NeedsReview, $attendance->status);
    }

    public function test_only_hr_can_verify_attendance_needing_review(): void
    {
        [$employee, $manager, $trip] = $this->trip();
        $attendance = app(AttendanceRecorder::class)->record($trip, $employee, [
            'client_uuid' => '20f26f3e-b3b3-49f6-9bcb-c31ec9862224',
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 150,
        ], 'attendance/review.jpg');
        $hr = User::factory()->create(['role' => UserRole::Hr]);

        try {
            $attendance->verifyByHr($manager);
            $this->fail('Atasan tidak boleh memverifikasi absensi.');
        } catch (DomainException $exception) {
            $this->assertSame('Absensi tidak dapat diverifikasi pengguna ini.', $exception->getMessage());
        }

        $this->assertSame(AttendanceStatus::NeedsReview, $attendance->fresh()->status);

        $attendance->verifyByHr($hr);

        $this->assertSame(AttendanceStatus::Valid, $attendance->fresh()->status);
        $this->assertSame('Akurasi GPS lebih dari 100 meter.', $attendance->fresh()->review_reason);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'attendance.verified',
            'subject_id' => $attendance->id,
            'user_id' => $hr->id,
        ]);
    }

    public function test_face_descriptor_must_contain_128_finite_numbers(): void
    {
        [$employee, $manager, $trip] = $this->trip();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Data pengenalan wajah tidak valid.');

        app(AttendanceRecorder::class)->record($trip, $employee, [
            'client_uuid' => '20f26f3e-b3b3-49f6-9bcb-c31ec9862305',
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
            'face_descriptor' => '[1,2,3]',
        ], 'attendance/invalid-face.jpg');
    }

    public function test_small_future_clock_difference_is_tolerated(): void
    {
        [$employee, $manager, $trip] = $this->trip();

        $attendance = app(AttendanceRecorder::class)->record($trip, $employee, [
            'client_uuid' => '20f26f3e-b3b3-49f6-9bcb-c31ec9862306',
            'captured_at' => now()->addMinutes(5)->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
        ], 'attendance/clock-tolerance.jpg');

        $this->assertSame(AttendanceStatus::Valid, $attendance->status);
        $this->assertNull($attendance->review_reason);
    }

    public function test_verify_attendance_button_is_only_visible_to_hr(): void
    {
        [$employee, $manager, $trip] = $this->trip();
        $attendance = app(AttendanceRecorder::class)->record($trip, $employee, [
            'client_uuid' => '20f26f3e-b3b3-49f6-9bcb-c31ec9862225',
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 150,
        ], 'attendance/review-button.jpg');
        $hr = User::factory()->create(['role' => UserRole::Hr]);

        filament()->setCurrentPanel('hr');
        $this->actingAs($hr);
        Livewire::test(ListAttendances::class)
            ->assertActionVisible(TestAction::make('verify')->table($attendance));
        Livewire::test(ListDutyTrips::class)
            ->assertActionVisible(TestAction::make('verify_attendance')->table($trip));

        filament()->setCurrentPanel('manager');
        $this->actingAs($manager);
        Livewire::test(ListAttendances::class)
            ->assertActionHidden(TestAction::make('verify')->table($attendance));
        Livewire::test(ListDutyTrips::class)
            ->assertActionHidden(TestAction::make('verify_attendance')->table($trip));
    }

    public function test_attendance_before_start_is_rejected_without_leaking_photo(): void
    {
        Storage::fake('local');
        [$employee, $manager, $trip] = $this->trip();
        $trip->update(['starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2)]);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        $this->actingAs($employee)->postJson(route('attendance.store', $trip), [
            'client_uuid' => '20f26f3e-b3b3-49f6-9bcb-c31ec9862223',
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
            'photo' => UploadedFile::fake()->createWithContent('photo.png', $png),
        ])->assertUnprocessable()->assertJsonPath('message', 'Absensi belum dibuka. Coba lagi saat jadwal dinas dimulai.');

        $this->assertDatabaseCount('attendances', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('attendance'));
    }

    public function test_inactive_employee_is_logged_out_from_attendance_route(): void
    {
        [$employee, $manager, $trip] = $this->trip();
        $employee->update(['is_active' => false]);

        $this->actingAs($employee)
            ->get(route('attendance.capture', $trip))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_sync_endpoint_is_idempotent(): void
    {
        Storage::fake('local');
        [$employee, $manager, $trip] = $this->trip();
        $payload = [
            'client_uuid' => '20f26f3e-b3b3-49f6-9bcb-c31ec9862221',
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
        ];
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        $this->actingAs($employee)
            ->postJson(route('attendance.store', $trip), [...$payload, 'photo' => UploadedFile::fake()->createWithContent('photo.png', $png)])
            ->assertCreated();
        $this->postJson(route('attendance.store', $trip), [...$payload, 'photo' => UploadedFile::fake()->createWithContent('photo.png', $png)])
            ->assertOk();

        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_manager_assigns_trip_and_employee_captures_attendance(): void
    {
        [$employee, $manager, $trip] = $this->trip();

        $this->actingAs($manager)
            ->get('/atasan/duty-trips/create')
            ->assertOk()
            ->assertSee('Pegawai yang ditugaskan');

        $this->actingAs($employee)
            ->get('/pegawai/duty-trips/create')
            ->assertRedirect('/pegawai')
            ->assertSessionHas('filament.notifications');

        $this->actingAs($employee)
            ->get(route('attendance.capture', $trip))
            ->assertOk()
            ->assertSee('Ambil lokasi dan simpan absensi')
            ->assertSee('duty_trip_id: tripId', false)
            ->assertSee('fetch(data.endpoint', false)
            ->assertSee("indexedDB.open('sdm-attendance', 2)", false)
            ->assertSee('error.retryable === false', false)
            ->assertSee('Penyimpanan luring tidak tersedia', false);
    }

    public function test_employee_active_trip_widget_generates_attendance_url(): void
    {
        [$employee, $manager, $trip] = $this->trip();

        filament()->setCurrentPanel('employee');
        $this->actingAs($employee);

        Livewire::test(EmployeeActiveTripsTable::class)
            ->assertSuccessful()
            ->assertSee(route('attendance.capture', $trip), false);
    }

    /** @return array{User, User, DutyTrip} */
    private function trip(): array
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'manager_id' => $manager->id]);
        $trip = DutyTrip::create([
            'employee_id' => $employee->id,
            'manager_id' => $manager->id,
            'destination' => 'Rapat koordinasi',
            'purpose' => 'Koordinasi proyek',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'location_name' => 'Monas',
            'address' => 'Jakarta Pusat',
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'radius_meters' => 100,
            'status' => DutyTripStatus::Approved,
            'approved_at' => now(),
        ]);

        return [$employee, $manager, $trip];
    }
}
