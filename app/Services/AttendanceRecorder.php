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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonException;
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

            if ($existing = Attendance::where('client_uuid', $data['client_uuid'])->first()) {
                if ($existing->duty_trip_id !== $trip->id || $existing->employee_id !== $employee->id) {
                    throw new BusinessRuleException('ID sinkronisasi telah digunakan.');
                }

                return $existing;
            }

            $capturedAt = CarbonImmutable::parse($data['captured_at']);
            $attendanceDate = $capturedAt->toDateString();

            if ($existing = $trip->attendances()->whereDate('attendance_date', $attendanceDate)->first()) {
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
            $poorAccuracy = isset($data['accuracy_meters']) && (int) $data['accuracy_meters'] > 100;
            $suspected = $mockLocation || $poorAccuracy || $clockMismatch;

            $status = match (true) {
                $distance > $trip->radius_meters => AttendanceStatus::OutsideRadius,
                $capturedAt->isAfter($trip->ends_at) => AttendanceStatus::Late,
                default => AttendanceStatus::Valid,
            };

            if ($suspected) {
                $status = AttendanceStatus::NeedsReview;
            }

            $reasons = array_filter([
                $mockLocation ? 'Perangkat mendeteksi lokasi palsu.' : null,
                $poorAccuracy ? 'Akurasi GPS lebih dari 100 meter.' : null,
                $clockMismatch ? 'Waktu perangkat melewati batas toleransi.' : null,
                $distance > $trip->radius_meters ? 'Lokasi berada di luar radius dinas.' : null,
                $capturedAt->isAfter($trip->ends_at) ? 'Absensi dilakukan setelah jadwal dinas berakhir.' : null,
            ]);

            $faceData = $this->validatedFaceDescriptor($data['face_descriptor'] ?? null);
            $faceDescriptorPath = null;

            if ($faceData !== null) {
                $filename = ($data['client_uuid'] ?? (string) Str::uuid()) . '.json';
                $faceDescriptorPath = 'face-descriptors/' . $filename;
                Storage::disk('local')->put($faceDescriptorPath, json_encode($faceData));
            }

            $attendance = Attendance::create([
                ...$data,
                'duty_trip_id' => $trip->id,
                'employee_id' => $employee->id,
                'attendance_date' => $attendanceDate,
                'distance_meters' => $distance,
                'photo_path' => $photoPath,
                'face_descriptor_path' => $faceDescriptorPath,
                'status' => $status,
                'review_reason' => $reasons ? implode(' ', $reasons) : null,
                'mock_location_suspected' => $suspected,
                'synced_at' => $receivedAt,
            ]);

            if ($faceData !== null) {
                $previous = Attendance::where('duty_trip_id', $trip->id)
                    ->where('employee_id', $employee->id)
                    ->whereNotNull('face_descriptor_path')
                    ->where('id', '!=', $attendance->id)
                    ->latest('captured_at')
                    ->first();

                if ($previous && $previous->face_descriptor_path) {
                    $prevContent = Storage::disk('local')->get($previous->face_descriptor_path);
                    $prev = $prevContent !== false ? json_decode($prevContent, true) : null;
                    $curr = $faceData;

                    if (is_array($prev) && is_array($curr) && count($prev) === count($curr)) {
                        $sum = 0;
                        foreach ($prev as $i => $v) {
                            $sum += ($v - $curr[$i]) ** 2;
                        }
                        $distance = sqrt($sum);

                        if ($distance > 0.6) {
                            $attendance->update([
                                'status' => $attendance->status !== AttendanceStatus::NeedsReview
                                    ? AttendanceStatus::NeedsReview
                                    : $attendance->status,
                                'review_reason' => trim($attendance->review_reason.' Data pengenalan wajah tidak cocok dengan absensi sebelumnya.'),
                            ]);
                            $attendance = $attendance->fresh();
                        }
                    }
                }
            }

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

    private function validatedFaceDescriptor(mixed $descriptor): ?array
    {
        if ($descriptor === null || $descriptor === '') {
            return null;
        }

        if (! is_string($descriptor) || strlen($descriptor) > 8192) {
            throw new BusinessRuleException('Data pengenalan wajah tidak valid.');
        }

        try {
            $values = json_decode($descriptor, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new BusinessRuleException('Data pengenalan wajah tidak valid.');
        }

        if (! is_array($values) || count($values) !== 128) {
            throw new BusinessRuleException('Data pengenalan wajah tidak valid.');
        }

        foreach ($values as $value) {
            if (! is_numeric($value) || ! is_finite((float) $value)) {
                throw new BusinessRuleException('Data pengenalan wajah tidak valid.');
            }
        }

        return array_map('floatval', $values);
    }
}
