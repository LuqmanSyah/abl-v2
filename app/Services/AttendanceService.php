<?php

namespace App\Services;

use App\Enums\AttendanceRequestStatus;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Exceptions\BusinessRuleException;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\User;
use App\Support\GeoDistance;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Throwable;

class AttendanceService
{
    public function __construct(private readonly GoogleMapsService $googleMaps) {}

    /** @SuppressWarnings("php:S107") */
    public function record(
        User $user,
        AttendanceType $type,
        float $latitude,
        float $longitude,
        string $photoPath,
        ?AttendanceRequest $request = null,
        ?string $exceptionReason = null,
        ?CarbonInterface $recordedAt = null,
    ): Attendance {
        $recordedAt = CarbonImmutable::parse($recordedAt ?? now());
        $this->validateInput($latitude, $longitude, $photoPath);

        $user->loadMissing(['branchOffice', 'workSchedule']);
        $branch = $user->branchOffice;

        if (! $branch || ! $user->workSchedule) {
            throw new BusinessRuleException('Kantor cabang dan jadwal kerja wajib dikonfigurasi.');
        }

        if ($request) {
            $this->validateRequest($request, $user, $recordedAt);
        }

        if ($type === AttendanceType::CheckIn) {
            $this->validateCheckInWindow($user, $request, $recordedAt);
        } else {
            $this->validateCheckOutSequence($user, $request, $recordedAt);
        }

        [$targetLatitude, $targetLongitude, $radius, $address] = $request
            ? [
                (float) $request->target_latitude,
                (float) $request->target_longitude,
                $request->allowed_radius_meters,
                $request->destination_address,
            ]
            : [
                (float) $branch->latitude,
                (float) $branch->longitude,
                $branch->allowed_radius_meters,
                $branch->name,
            ];

        $distance = GeoDistance::meters(
            $latitude,
            $longitude,
            $targetLatitude,
            $targetLongitude,
        );

        if ($type === AttendanceType::CheckOut
            && $request
            && $distance > $radius
            && $this->canCheckOutAtBranch($request, $recordedAt)) {
            $branchDistance = GeoDistance::meters(
                $latitude,
                $longitude,
                (float) $branch->latitude,
                (float) $branch->longitude,
            );

            if ($branchDistance <= $branch->allowed_radius_meters) {
                $distance = $branchDistance;
                $radius = $branch->allowed_radius_meters;
                $address = $branch->name;
            }
        }

        $outsideRadius = $distance > $radius;

        if ($outsideRadius && $type === AttendanceType::CheckIn) {
            throw new BusinessRuleException('Lokasi check-in berada di luar radius yang diizinkan.');
        }

        if ($outsideRadius && blank($exceptionReason)) {
            throw new BusinessRuleException('Alasan dan foto wajib untuk check-out di luar radius.');
        }

        $status = match (true) {
            $outsideRadius => AttendanceStatus::PendingVerification,
            $type === AttendanceType::CheckIn => $this->checkInStatus($user, $request, $recordedAt),
            default => AttendanceStatus::Normal,
        };

        try {
            $address = $this->googleMaps->reverseGeocode($latitude, $longitude);
        } catch (Throwable) {
            // Keep known target address when reverse geocoding is unavailable.
        }

        return Attendance::create([
            'user_id' => $user->id,
            'attendance_request_id' => $request?->id,
            'type' => $type,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'distance_to_target_meters' => $distance,
            'is_fallback' => false,
            'address_snapshot' => $address,
            'photo_path' => $photoPath,
            'is_radius_exception' => $outsideRadius,
            'exception_reason' => $outsideRadius ? $exceptionReason : null,
            'status' => $status,
            'recorded_at' => $recordedAt,
        ]);
    }

    private function validateInput(float $latitude, float $longitude, string $photoPath): void
    {
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw new BusinessRuleException('Koordinat GPS tidak valid.');
        }

        if (blank($photoPath)) {
            throw new BusinessRuleException('Foto presensi wajib diunggah.');
        }
    }

    private function validateRequest(AttendanceRequest $request, User $user, CarbonImmutable $recordedAt): void
    {
        if ($request->user_id !== $user->id || $request->status !== AttendanceRequestStatus::Approved) {
            throw new BusinessRuleException('Izin tugas luar belum disetujui atau bukan milik pengguna.');
        }

        if ($recordedAt->toDateString() < $request->duty_start_datetime->toDateString()
            || $recordedAt->toDateString() > $request->duty_end_datetime->toDateString()) {
            throw new BusinessRuleException('Presensi berada di luar rentang tanggal tugas luar.');
        }
    }

    private function validateCheckInWindow(
        User $user,
        ?AttendanceRequest $request,
        CarbonImmutable $recordedAt,
    ): void {
        $start = $this->scheduledStart($user, $request, $recordedAt);

        if ($recordedAt->lt($start->subMinutes(90))) {
            throw new BusinessRuleException('Check-in baru dibuka 90 menit sebelum jam masuk.');
        }
    }

    private function validateCheckOutSequence(
        User $user,
        ?AttendanceRequest $request,
        CarbonImmutable $recordedAt,
    ): void {
        $sessionKey = $request ? 'request:'.$request->id : 'office';
        $session = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $recordedAt)
            ->where('session_key', $sessionKey);

        if (! (clone $session)
            ->where('type', AttendanceType::CheckIn)
            ->where('recorded_at', '<', $recordedAt)
            ->exists()) {
            throw new BusinessRuleException('Check-out wajib memiliki check-in lebih awal pada sesi yang sama.');
        }

        if ((clone $session)->where('type', AttendanceType::CheckOut)->exists()) {
            throw new BusinessRuleException('Check-out untuk sesi ini sudah tercatat hari ini.');
        }
    }

    private function checkInStatus(
        User $user,
        ?AttendanceRequest $request,
        CarbonImmutable $recordedAt,
    ): AttendanceStatus {
        $start = $this->scheduledStart($user, $request, $recordedAt);

        return match (true) {
            $recordedAt->gt($start->addMinutes($user->workSchedule->alfa_cutoff_minutes)) => AttendanceStatus::Alfa,
            $recordedAt->gt($start->addMinutes($user->workSchedule->late_tolerance_minutes)) => AttendanceStatus::Late,
            default => AttendanceStatus::Normal,
        };
    }

    private function scheduledStart(
        User $user,
        ?AttendanceRequest $request,
        CarbonImmutable $recordedAt,
    ): CarbonImmutable {
        $time = $request
            ? $request->duty_start_datetime->format('H:i:s')
            : $user->workSchedule->check_in_time;

        return $recordedAt->setTimeFromTimeString($time);
    }

    private function canCheckOutAtBranch(AttendanceRequest $request, CarbonImmutable $recordedAt): bool
    {
        return $recordedAt->isSameDay($request->duty_end_datetime)
            && $recordedAt->gte($request->duty_end_datetime);
    }
}
