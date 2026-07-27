<?php

namespace App\Filament\Resources\AttendanceRequestResource\Pages;

use App\Enums\AttendanceRequestStatus;
use App\Enums\FlowType;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Filament\Resources\AttendanceRequestResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAttendanceRequest extends CreateRecord
{
    protected static string $resource = AttendanceRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $actor = Auth::user();

        if (! $actor instanceof User) {
            throw new BusinessRuleException('Pengguna tidak valid.');
        }

        if (Filament::getCurrentPanel()?->getId() === 'employee') {
            if ($actor->role !== UserRole::Employee) {
                throw new BusinessRuleException('Tugas luar bottom-up hanya dapat dibuat Employee.');
            }

            return [
                ...$data,
                'user_id' => $actor->id,
                'created_by' => $actor->id,
                'flow_type' => FlowType::BottomUp->value,
                'status' => AttendanceRequestStatus::Pending->value,
                'approved_by' => null,
            ];
        }

        if ($actor->role !== UserRole::Manager
            || User::query()->where('manager_id', $actor->id)->find($data['user_id']) === null) {
            throw new BusinessRuleException('Manager hanya dapat memberi tugas kepada bawahan langsung.');
        }

        return [
            ...$data,
            'created_by' => $actor->id,
            'flow_type' => FlowType::TopDown->value,
            'status' => AttendanceRequestStatus::Pending->value,
            'approved_by' => null,
        ];
    }
}
