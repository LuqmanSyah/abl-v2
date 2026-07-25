<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Enums\AttendanceType;
use App\Filament\Resources\AttendanceResource;
use App\Models\AttendanceRequest;
use App\Services\AttendanceService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(AttendanceService::class)->record(
                user: auth()->user(),
                type: AttendanceType::from($data['type']),
                latitude: (float) $data['latitude'],
                longitude: (float) $data['longitude'],
                photoPath: $data['photo_path'],
                request: filled($data['attendance_request_id'] ?? null)
                    ? AttendanceRequest::findOrFail($data['attendance_request_id'])
                    : null,
                exceptionReason: $data['exception_reason'] ?? null,
            );
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($data['photo_path']);

            throw $exception;
        }
    }
}
