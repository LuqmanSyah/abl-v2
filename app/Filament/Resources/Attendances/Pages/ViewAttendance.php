<?php

namespace App\Filament\Resources\Attendances\Pages;

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Attendances\AttendanceResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewAttendance extends ViewRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('verify')
                ->label('Verifikasi Absensi')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Verifikasi Absensi')
                ->modalDescription('Status absensi akan diubah menjadi Valid dan dipakai dalam perhitungan kedisiplinan.')
                ->modalSubmitActionLabel('Verifikasi Absensi')
                ->modalWidth('md')
                ->visible(fn (): bool => auth()->user()?->role === UserRole::Hr
                    && $this->record->status === AttendanceStatus::NeedsReview)
                ->action(fn () => $this->record->verifyByHr(auth()->user()))
                ->successNotificationTitle('Absensi berhasil diverifikasi'),
        ];
    }
}
