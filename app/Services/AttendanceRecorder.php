<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\DutyTripStatus;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\DutyTrip;
use App\Models\User;
use App\Notifications\AttendanceNeedsReview;
use App\Support\GeoDistance;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

class AttendanceRecorder
{
    /** @param array<string, mixed> $data */
    public function record(DutyTrip $trip, User $employee, array $data, string $photoPath): Attendance
    {
        return DB::transaction(function () use ($trip, $employee, $data, $photoPath): Attendance {
            $trip = DutyTrip::query()->lockForUpdate()->findOrFail($trip->getKey());

            if ($trip->employee_id !== $employee->id) {
                throw new BusinessRuleException('Absensi hanya tersedia untuk pegawai yang ditugaskan.');
            }

            $capturedAt = CarbonImmutable::parse($data['captured_at']);
            $attendanceDate = $capturedAt->toDateString();

            if ($existing = $trip->attendances()->whereDate('attendance_date', $attendanceDate)->lockForUpdate()->first()) {
                return $existing;
            }

            if ($trip->status !== DutyTripStatus::Approved) {
                throw new BusinessRuleException('Absensi hanya tersedia untuk dinas aktif.');
            }

            $receivedAt = CarbonImmutable::now();

            if ($receivedAt->isBefore($trip->starts_at) || $capturedAt->isBefore($trip->starts_at)) {
                throw new BusinessRuleException('Absensi belum dibuka. Coba lagi saat jadwal dinas dimulai.');
            }

            $distance = GeoDistance::meters(
                (float) $trip->latitude,
                (float) $trip->longitude,
                (float) $data['latitude'],
                (float) $data['longitude'],
            );
            $clockMismatch = abs($capturedAt->getTimestamp() - $receivedAt->getTimestamp())
                > config('hr.attendance_clock_tolerance_minutes') * 60;
            $mockLocation = (bool) ($data['mock_location_suspected'] ?? false);
            $suspected = $mockLocation || $clockMismatch;

            $status = match (true) {
                $distance > $trip->radius_meters => AttendanceStatus::NeedsReview,
                $capturedAt->isAfter($trip->ends_at) => AttendanceStatus::Late,
                default => AttendanceStatus::Valid,
            };

            if ($suspected) {
                $status = AttendanceStatus::NeedsReview;
            }

            $reasons = array_filter([
                $mockLocation ? 'Perangkat mendeteksi lokasi palsu.' : null,
                $clockMismatch ? 'Waktu perangkat melewati batas toleransi.' : null,
                $distance > $trip->radius_meters ? 'Lokasi berada di luar radius dinas.' : null,
                $capturedAt->isAfter($trip->ends_at) ? 'Absensi dilakukan setelah jadwal dinas berakhir.' : null,
            ]);

            $attendance = Attendance::create([
                'duty_trip_id' => $trip->id,
                'employee_id' => $employee->id,
                'attendance_date' => $attendanceDate,
                'captured_at' => $capturedAt,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'accuracy_meters' => $data['accuracy_meters'] ?? null,
                'distance_meters' => $distance,
                'photo_path' => $photoPath,
                'status' => $status,
                'review_reason' => $reasons ? implode(' ', $reasons) : null,
                'mock_location_suspected' => $suspected,
            ]);

            ActivityLog::record('attendance.created', $attendance, $employee);

            if ($attendance->status === AttendanceStatus::NeedsReview) {
                try {
                    $hrUsers = User::where('role', UserRole::Hr)->where('is_active', true)->get();
                    Notification::send($hrUsers, new AttendanceNeedsReview($attendance));
                } catch (Throwable $exception) {
                    report($exception);
                }
            }

            return $attendance;
        }, 3);
    }
}
