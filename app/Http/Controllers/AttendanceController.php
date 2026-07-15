<?php

namespace App\Http\Controllers;

use App\Enums\DutyTripStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\DutyTrip;
use App\Services\AttendanceRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function show(Request $request, DutyTrip $dutyTrip): View
    {
        abort_unless($this->canAttend($request, $dutyTrip), 403);

        return view('attendance.capture', ['trip' => $dutyTrip->load('employee', 'attendance')]);
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

        if ($existing = $dutyTrip->attendance()->first()) {
            return response()->json(['message' => 'Absensi sudah tercatat.', 'attendance' => $existing]);
        }

        $photoPath = $request->file('photo')->store('attendance', 'local');
        $attendance = $recorder->record($dutyTrip, $request->user(), $data, $photoPath);

        return response()->json(['message' => 'Absensi tersimpan.', 'attendance' => $attendance], 201);
    }

    public function photo(Request $request, Attendance $attendance): StreamedResponse
    {
        $user = $request->user();
        $allowed = match ($user->role) {
            UserRole::Employee => $attendance->employee_id === $user->id,
            UserRole::Manager => $attendance->dutyTrip()->where('manager_id', $user->id)->exists(),
            UserRole::Hr => true,
        };
        abort_unless($allowed, 403);

        return Storage::disk('local')->response($attendance->photo_path);
    }

    private function canAttend(Request $request, DutyTrip $trip): bool
    {
        return $request->user()?->role === UserRole::Employee
            && $trip->employee_id === $request->user()->id
            && $trip->status === DutyTripStatus::Approved;
    }
}
