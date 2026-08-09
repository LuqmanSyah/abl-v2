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
                throw new BusinessRuleException('Absensi dinas hanya tersedia untuk pegawai yang ditugaskan.');
            }

            $capturedAt = CarbonImmutable::parse($data['captured_at']);
            $attendanceDate = $capturedAt->toDateString();

            if ($existing = $trip->attendances()->whereDate('attendance_date', $attendanceDate)->lockForUpdate()->first()) {
                return $existing;
            }

            if ($trip->status !== DutyTripStatus::Approved) {
                throw new BusinessRuleException('Absensi dinas hanya tersedia untuk dinas aktif.');
            }

            $receivedAt = CarbonImmutable::now();

            if ($receivedAt->isBefore($trip->starts_at) || $capturedAt->isBefore($trip->starts_at)) {
                throw new BusinessRuleException('Absensi dinas belum dibuka. Coba lagi saat jadwal dinas dimulai.');
            }

            $distance = GeoDistance::meters(
                (float) $trip->latitude,
                (float) $trip->longitude,
                (float) $data['latitude'],
                (float) $data['longitude'],
            );
            $clockMismatch = abs($capturedAt->getTimestamp() - $receivedAt->getTimestamp())
                > config('hr.attendance_clock_tolerance_minutes') * 60;
            $accuracy = isset($data['accuracy_meters']) ? (int) $data['accuracy_meters'] : null;
            $inaccurate = $accuracy === null || $accuracy > config('hr.attendance_max_accuracy_meters');
            $status = match (true) {
                $inaccurate => AttendanceStatus::NeedsReview,
                $distance > $trip->radius_meters => AttendanceStatus::NeedsReview,
                $capturedAt->isAfter($trip->ends_at) => AttendanceStatus::Late,
                default => AttendanceStatus::Valid,
            };

            if ($clockMismatch) {
                $status = AttendanceStatus::NeedsReview;
            }

            $reasons = array_filter([
                $inaccurate ? 'Akurasi GPS tidak tersedia atau melewati batas.' : null,
                $clockMismatch ? 'Waktu perangkat melewati batas toleransi.' : null,
                $distance > $trip->radius_meters ? 'Lokasi berada di luar radius dinas.' : null,
                $capturedAt->isAfter($trip->ends_at) ? 'Absensi dinas dilakukan setelah jadwal berakhir.' : null,
            ]);

            $attendance = Attendance::create([
                'duty_trip_id' => $trip->id,
                'employee_id' => $employee->id,
                'attendance_date' => $attendanceDate,
                'captured_at' => $capturedAt,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'accuracy_meters' => $accuracy,
                'distance_meters' => $distance,
                'photo_path' => $photoPath,
                'status' => $status,
                'review_reason' => $reasons ? implode(' ', $reasons) : null,
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
