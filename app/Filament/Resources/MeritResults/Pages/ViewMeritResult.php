<?php

namespace App\Filament\Resources\MeritResults\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\MeritResults\MeritResultResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewMeritResult extends ViewRecord
{
    protected static string $resource = MeritResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('verify_manager')
                ->label('Verifikasi Atasan')->color('success')->requiresConfirmation()
                ->visible(fn (): bool => auth()->user()->role === UserRole::Manager && ! $this->record->manager_verified_at)
                ->action(fn () => $this->record->verifyByManager(auth()->user())),
            Action::make('verify_hr')
                ->label('Verifikasi dan Publikasikan')->color('success')->requiresConfirmation()
                ->visible(fn (): bool => auth()->user()->role === UserRole::Hr && $this->record->manager_verified_at && ! $this->record->hr_verified_at)
                ->action(fn () => $this->record->verifyByHr(auth()->user())),
        ];
    }
}
