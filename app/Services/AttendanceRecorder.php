<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\DutyTripStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\DutyTrip;
use App\Models\User;
use App\Support\GeoDistance;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

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

            if ($existing = Attendance::where('client_uuid', $data['client_uuid'])->first()) {
                if ($existing->duty_trip_id !== $trip->id || $existing->employee_id !== $employee->id) {
                    throw new BusinessRuleException('ID sinkronisasi telah digunakan.');
                }

                return $existing;
            }

            $today = CarbonImmutable::today();
            if ($existing = $trip->attendances()->whereDate('captured_at', $today)->first()) {
                return $existing;
            }

            if ($trip->status !== DutyTripStatus::Approved) {
                throw new BusinessRuleException('Absensi hanya tersedia untuk dinas aktif.');
            }

            $capturedAt = CarbonImmutable::parse($data['captured_at']);
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
            $suspected = (bool) ($data['mock_location_suspected'] ?? false)
                || (isset($data['accuracy_meters']) && (int) $data['accuracy_meters'] > 100)
                || $clockMismatch;

            $status = match (true) {
                $suspected => AttendanceStatus::NeedsReview,
                $distance > $trip->radius_meters => AttendanceStatus::OutsideRadius,
                $capturedAt->isAfter($trip->ends_at) => AttendanceStatus::Late,
                default => AttendanceStatus::Valid,
            };

            $attendance = Attendance::create([
                ...$data,
                'duty_trip_id' => $trip->id,
                'employee_id' => $employee->id,
                'distance_meters' => $distance,
                'photo_path' => $photoPath,
                'status' => $status,
                'mock_location_suspected' => $suspected,
                'synced_at' => $receivedAt,
            ]);

            ActivityLog::record('attendance.created', $attendance, $employee);

            return $attendance;
        }, 3);
    }
}
