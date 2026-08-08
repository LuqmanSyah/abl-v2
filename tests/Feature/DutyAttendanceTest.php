<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\DutyTripStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Attendances\Pages\ListAttendances;
use App\Filament\Widgets\EmployeeActiveTripsTable;
use App\Filament\Widgets\HrActiveTripsTable;
use App\Filament\Widgets\HrAttendanceDropAlert;
use App\Models\DutyTrip;
use App\Models\User;
use App\Notifications\AttendanceNeedsReview;
use App\Services\AttendanceRecorder;
use App\Support\GeoDistance;
use DomainException;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
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
            'captured_at' => now()->subDay()->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
        ], 'attendance/day-one.jpg');
        $second = $recorder->record($trip, $employee, [
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
        ], 'attendance/day-two.jpg');

        $this->assertFalse($first->is($second));
        $this->assertDatabaseCount('attendances', 2);
    }

    public function test_duplicate_uses_captured_date(): void
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

        $first = $recorder->record($trip, $employee, $payload, 'attendance/first.jpg');
        $duplicate = $recorder->record($trip, $employee, $payload, 'attendance/duplicate.jpg');

        $this->assertTrue($first->is($duplicate));
        $this->assertSame(now()->subDay()->toDateString(), $first->attendance_date->toDateString());
        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_gps_accuracy_boundary_and_missing_value_control_review(): void
    {
        config()->set('hr.attendance_max_accuracy_meters', 150);
        [$employee, $manager, $trip] = $this->trip();
        $attendance = app(AttendanceRecorder::class)->record($trip, $employee, [
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 150,
        ], 'attendance/photo.jpg');

        $this->assertSame(AttendanceStatus::Valid, $attendance->status);
        $this->assertNull($attendance->review_reason);

        [$inaccurateEmployee, , $inaccurateTrip] = $this->trip();
        $inaccurate = app(AttendanceRecorder::class)->record($inaccurateTrip, $inaccurateEmployee, [
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 151,
        ], 'attendance/inaccurate.jpg');

        [$missingEmployee, , $missingTrip] = $this->trip();
        $missing = app(AttendanceRecorder::class)->record($missingTrip, $missingEmployee, [
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
        ], 'attendance/missing-accuracy.jpg');

        $this->assertSame(AttendanceStatus::NeedsReview, $inaccurate->status);
        $this->assertSame('Akurasi GPS tidak tersedia atau melewati batas.', $inaccurate->review_reason);
        $this->assertSame(AttendanceStatus::NeedsReview, $missing->status);
        $this->assertSame('Akurasi GPS tidak tersedia atau melewati batas.', $missing->review_reason);
    }

    public function test_outside_radius_requires_review_and_late_attendance_is_classified(): void
    {
        [$employee, $manager, $trip] = $this->trip();
        $outside = app(AttendanceRecorder::class)->record($trip, $employee, [
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1800,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
        ], 'attendance/outside.jpg');

        [$lateEmployee, $lateManager, $lateTrip] = $this->trip();
        $lateTrip->update(['starts_at' => now()->subHours(3), 'ends_at' => now()->subHours(2)]);
        $late = app(AttendanceRecorder::class)->record($lateTrip, $lateEmployee, [
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
        ], 'attendance/late.jpg');

        $this->assertSame(AttendanceStatus::NeedsReview, $outside->status);
        $this->assertSame('Lokasi berada di luar radius dinas.', $outside->review_reason);
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
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1800,
            'longitude' => 106.8272,
            'accuracy_meters' => 150,
        ], 'attendance/review.jpg');
        $hr = User::factory()->create(['role' => UserRole::Hr]);

        try {
            $attendance->verifyByHr($manager);
            $this->fail('Atasan tidak boleh memverifikasi absensi.');
        } catch (DomainException $exception) {
            $this->assertSame('Absensi dinas tidak dapat diverifikasi pengguna ini.', $exception->getMessage());
        }

        $this->assertSame(AttendanceStatus::NeedsReview, $attendance->fresh()->status);

        $attendance->verifyByHr($hr);

        $this->assertSame(AttendanceStatus::Valid, $attendance->fresh()->status);
        $this->assertSame('Lokasi berada di luar radius dinas.', $attendance->fresh()->review_reason);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'attendance.verified',
            'subject_id' => $attendance->id,
            'user_id' => $hr->id,
        ]);
    }

    public function test_small_future_clock_difference_is_tolerated(): void
    {
        [$employee, $manager, $trip] = $this->trip();

        $attendance = app(AttendanceRecorder::class)->record($trip, $employee, [
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
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1800,
            'longitude' => 106.8272,
            'accuracy_meters' => 150,
        ], 'attendance/review-button.jpg');
        $hr = User::factory()->create(['role' => UserRole::Hr]);

        filament()->setCurrentPanel('hr');
        $this->actingAs($hr);
        Livewire::test(ListAttendances::class)
            ->assertActionVisible(TestAction::make('verify')->table($attendance));
        filament()->setCurrentPanel('manager');
        $this->actingAs($manager);
        Livewire::test(ListAttendances::class)
            ->assertActionHidden(TestAction::make('verify')->table($attendance));
    }

    public function test_attendance_needing_review_notifies_only_active_hr(): void
    {
        Notification::fake();
        [$employee, $manager, $trip] = $this->trip();
        $hr = User::factory()->create(['role' => UserRole::Hr]);
        $inactiveHr = User::factory()->create(['role' => UserRole::Hr, 'is_active' => false]);

        app(AttendanceRecorder::class)->record($trip, $employee, [
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1800,
            'longitude' => 106.8272,
            'accuracy_meters' => 150,
        ], 'attendance/review-notification.jpg');

        Notification::assertSentTo($hr, AttendanceNeedsReview::class);
        Notification::assertNotSentTo($inactiveHr, AttendanceNeedsReview::class);
        Notification::assertNotSentTo($manager, AttendanceNeedsReview::class);
    }

    public function test_hr_active_trip_widget_handles_outside_radius_status(): void
    {
        [$employee, $manager, $trip] = $this->trip();
        $hr = User::factory()->create(['role' => UserRole::Hr]);
        app(AttendanceRecorder::class)->record($trip, $employee, [
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1800,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
        ], 'attendance/outside-widget.jpg');

        filament()->setCurrentPanel('hr');
        $this->actingAs($hr);

        Livewire::test(HrActiveTripsTable::class)
            ->assertSuccessful()
            ->assertSee('Memerlukan Pemeriksaan');
    }

    public function test_hr_attendance_drop_alert_uses_constant_query_count(): void
    {
        $this->trip();
        $this->trip();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $method = new \ReflectionMethod(HrAttendanceDropAlert::class, 'getStats');
        $method->invoke(new HrAttendanceDropAlert);

        $this->assertCount(1, DB::getQueryLog());
        DB::disableQueryLog();
    }

    public function test_attendance_before_start_is_rejected_without_leaking_photo(): void
    {
        Storage::fake('local');
        [$employee, $manager, $trip] = $this->trip();
        $trip->update(['starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2)]);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        $this->actingAs($employee)->postJson(route('attendance.store', $trip), [
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
            'photo' => UploadedFile::fake()->createWithContent('photo.png', $png),
        ])->assertUnprocessable()->assertJsonPath('message', 'Absensi dinas belum dibuka. Coba lagi saat jadwal dinas dimulai.');

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

    public function test_attendance_endpoint_is_idempotent(): void
    {
        Storage::fake('local');
        [$employee, $manager, $trip] = $this->trip();
        $payload = [
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
            ->assertSee('Ambil lokasi dan simpan absensi dinas')
            ->assertSee('fetch(endpoint', false)
            ->assertSee('Perangkat sedang luring', false)
            ->assertDontSee('indexedDB', false)
            ->assertDontSee('serviceWorker', false)
            ->assertDontSee('FaceVerification', false);
    }

    public function test_employee_active_trip_widget_warns_until_attendance_is_recorded(): void
    {
        [$employee, $manager, $trip] = $this->trip();

        filament()->setCurrentPanel('employee');
        $this->actingAs($employee);

        Livewire::test(EmployeeActiveTripsTable::class)
            ->assertSuccessful()
            ->assertSee('Belum absen hari ini')
            ->assertSee(route('attendance.capture', $trip), false);

        app(AttendanceRecorder::class)->record($trip, $employee, [
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
        ], 'attendance/widget.jpg');

        Livewire::test(EmployeeActiveTripsTable::class)
            ->assertSuccessful()
            ->assertSee('Sudah absen hari ini')
            ->assertDontSee(route('attendance.capture', $trip), false);
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
