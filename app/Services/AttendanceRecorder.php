<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\DutyTripStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Attendance;
use App\Models\DutyTrip;
use App\Models\User;
use App\Support\GeoDistance;
use Illuminate\Support\Facades\DB;

class AttendanceRecorder
{
    private const MAX_ACCURACY_METERS = 150;

    /** @param array<string, mixed> $data */
    public function record(DutyTrip $trip, User $employee, array $data, string $photoPath): Attendance
    {
        return DB::transaction(function () use ($trip, $employee, $data, $photoPath): Attendance {
            $trip = DutyTrip::query()->lockForUpdate()->findOrFail($trip->getKey());

            if ($trip->employee_id !== $employee->id || $trip->status !== DutyTripStatus::Active) {
                throw new BusinessRuleException('Absensi hanya tersedia untuk Pegawai yang ditugaskan.');
            }

            if ($existing = $trip->attendance()->lockForUpdate()->first()) {
                return $existing;
            }

            $receivedAt = now();
            if ($receivedAt->isBefore($trip->starts_at) || $receivedAt->isAfter($trip->ends_at)) {
                throw new BusinessRuleException('Absensi hanya tersedia selama jadwal dinas.');
            }

            $distance = GeoDistance::meters(
                (float) $trip->latitude,
                (float) $trip->longitude,
                (float) $data['latitude'],
                (float) $data['longitude'],
            );
            $outsideRadius = $distance > $trip->radius_meters;
            $poorAccuracy = $data['accuracy_meters'] > self::MAX_ACCURACY_METERS;
            $reasons = array_filter([
                $outsideRadius ? 'Lokasi berada di luar radius dinas.' : null,
                $poorAccuracy ? 'Akurasi GPS melebihi 150 meter.' : null,
            ]);

            return Attendance::create([
                'duty_trip_id' => $trip->id,
                'employee_id' => $employee->id,
                'received_at' => $receivedAt,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'accuracy_meters' => $data['accuracy_meters'],
                'distance_meters' => $distance,
                'photo_path' => $photoPath,
                'status' => $reasons ? AttendanceStatus::NeedsReview : AttendanceStatus::Valid,
                'review_reason' => $reasons ? implode(' ', $reasons) : null,
            ]);
        }, 3);
    }
}
