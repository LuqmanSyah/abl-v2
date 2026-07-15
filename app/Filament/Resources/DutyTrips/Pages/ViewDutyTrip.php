<?php

namespace App\Filament\Resources\DutyTrips\Pages;

use App\Enums\DutyTripStatus;
use App\Enums\UserRole;
use App\Filament\Resources\DutyTrips\DutyTripResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;

class ViewDutyTrip extends ViewRecord
{
    protected static string $resource = DutyTripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => DutyTripResource::canEdit($this->record)),
            Action::make('approve')
                ->label('Setujui')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => auth()->user()->role === UserRole::Manager && $this->record->status === DutyTripStatus::Pending)
                ->action(fn () => $this->record->approve(auth()->user())),
            Action::make('reject')
                ->label('Tolak')
                ->color('danger')
                ->schema([Textarea::make('reason')->label('Alasan')->required()])
                ->visible(fn (): bool => auth()->user()->role === UserRole::Manager && $this->record->status === DutyTripStatus::Pending)
                ->action(fn (array $data) => $this->record->reject(auth()->user(), $data['reason'])),
            Action::make('attendance')
                ->label('Lakukan Absensi')
                ->url(fn (): string => route('attendance.capture', $this->record))
                ->visible(fn (): bool => auth()->user()->role === UserRole::Employee
                    && $this->record->status === DutyTripStatus::Approved
                    && ! $this->record->attendance()->exists()),
        ];
    }
}
