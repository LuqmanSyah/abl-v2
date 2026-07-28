<?php

namespace App\Filament\Resources\DutyTrips\Pages;

use App\Enums\DutyTripStatus;
use App\Enums\UserRole;
use App\Filament\Resources\DutyTrips\DutyTripResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDutyTrip extends ViewRecord
{
    protected static string $resource = DutyTripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => DutyTripResource::canEdit($this->record)),
            Action::make('cancel')
                ->label('Batalkan Tugas')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Batalkan Perintah Dinas')
                ->modalDescription('Perintah dinas akan dibatalkan dan Pegawai tidak dapat melakukan absensi.')
                ->modalSubmitActionLabel('Batalkan Perintah')
                ->modalWidth('md')
                ->visible(fn (): bool => $this->record->canBeChangedBy(auth()->user()))
                ->action(fn () => $this->record->cancel(auth()->user())),
            Action::make('attendance')
                ->label('Lakukan Absensi')
                ->url(fn (): string => route('attendance.capture', $this->record))
                ->visible(fn (): bool => auth()->user()->role === UserRole::Employee
                    && $this->record->employee_id === auth()->id()
                    && $this->record->status === DutyTripStatus::Approved
                    && ! $this->record->attendances()->whereDate('captured_at', today())->exists()),
        ];
    }
}
