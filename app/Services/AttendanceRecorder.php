<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\DutyTripStatus;
use App\Models\Attendance;
use App\Models\DutyTrip;
use App\Models\User;
use App\Support\GeoDistance;
use Carbon\CarbonImmutable;
use DomainException;

class AttendanceRecorder
{
    /** @param array<string, mixed> $data */
    public function record(DutyTrip $trip, User $employee, array $data, string $photoPath): Attendance
    {
        if ($trip->employee_id !== $employee->id || $trip->status !== DutyTripStatus::Approved) {
            throw new DomainException('Absensi hanya tersedia untuk dinas yang telah disetujui.');
        }

        if ($existing = Attendance::where('client_uuid', $data['client_uuid'])->first()) {
            if ($existing->duty_trip_id !== $trip->id || $existing->employee_id !== $employee->id) {
                throw new DomainException('ID sinkronisasi telah digunakan.');
            }

            return $existing;
        }

        if ($existing = $trip->attendance()->first()) {
            return $existing;
        }

        $capturedAt = CarbonImmutable::parse($data['captured_at']);
        $distance = GeoDistance::meters(
            (float) $trip->latitude,
            (float) $trip->longitude,
            (float) $data['latitude'],
            (float) $data['longitude'],
        );
        $suspected = (bool) ($data['mock_location_suspected'] ?? false)
            || (isset($data['accuracy_meters']) && (int) $data['accuracy_meters'] > 100);

        $status = match (true) {
            $suspected => AttendanceStatus::NeedsReview,
            $distance > $trip->radius_meters => AttendanceStatus::OutsideRadius,
            $capturedAt->isBefore($trip->starts_at) || $capturedAt->isAfter($trip->ends_at) => AttendanceStatus::Late,
            default => AttendanceStatus::Valid,
        };

        return Attendance::create([
            ...$data,
            'duty_trip_id' => $trip->id,
            'employee_id' => $employee->id,
            'distance_meters' => $distance,
            'photo_path' => $photoPath,
            'status' => $status,
            'mock_location_suspected' => $suspected,
            'synced_at' => now(),
        ]);
    }
}
