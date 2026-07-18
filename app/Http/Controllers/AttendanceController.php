<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\DutyTripStatus;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Models\Attendance;
use App\Models\DutyTrip;
use App\Services\AttendanceRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AttendanceController extends Controller
{
    public function show(Request $request, DutyTrip $dutyTrip): View
    {
        abort_unless($this->canAttend($request, $dutyTrip), 403);

        return view('attendance.capture', ['trip' => $dutyTrip->load('employee', 'attendances')]);
    }

    public function store(Request $request, DutyTrip $dutyTrip, AttendanceRecorder $recorder): JsonResponse
    {
        abort_unless($this->canAttend($request, $dutyTrip), 403);

        $data = $request->validate([
            'client_uuid' => ['required', 'uuid'],
            'captured_at' => ['required', 'date'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['nullable', 'integer', 'min:0'],
            'mock_location_suspected' => ['nullable', 'boolean'],
            'photo' => ['required', 'image', 'max:5120'],
        ]);

        if ($existing = Attendance::where('client_uuid', $data['client_uuid'])->first()) {
            abort_unless($existing->employee_id === $request->user()->id && $existing->duty_trip_id === $dutyTrip->id, 409);

            return response()->json(['message' => 'Absensi sudah tersinkronisasi.', 'attendance' => $existing]);
        }

        $photoPath = $request->file('photo')->store('attendance', 'local');

        try {
            $attendance = $recorder->record($dutyTrip, $request->user(), $data, $photoPath);
        } catch (BusinessRuleException $exception) {
            Storage::disk('local')->delete($photoPath);

            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($photoPath);

            throw $exception;
        }

        $created = $attendance->photo_path === $photoPath;
        if (! $created) {
            Storage::disk('local')->delete($photoPath);
        }

        $message = $attendance->status === AttendanceStatus::Valid
            ? 'Absensi berhasil disimpan.'
            : 'Absensi tersimpan dengan status: '.$attendance->status->label().'.';

        return response()->json(['message' => $message, 'attendance' => $attendance], $created ? 201 : 200);
    }

    public function photo(Request $request, Attendance $attendance): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user->is_active, 403);
        $allowed = match ($user->role) {
            UserRole::Employee => $attendance->employee_id === $user->id,
            UserRole::Manager => $attendance->dutyTrip()->where('manager_id', $user->id)->exists(),
            UserRole::Hr => true,
        };
        abort_unless($allowed, 403);
        abort_unless($attendance->photo_path && Storage::disk('local')->exists($attendance->photo_path), 404);

        return Storage::disk('local')->response($attendance->photo_path);
    }

    private function canAttend(Request $request, DutyTrip $trip): bool
    {
        return $request->user()?->role === UserRole::Employee
            && $request->user()->is_active
            && $trip->employee_id === $request->user()->id
            && in_array($trip->status, [DutyTripStatus::Approved, DutyTripStatus::Completed], true);
    }
}
