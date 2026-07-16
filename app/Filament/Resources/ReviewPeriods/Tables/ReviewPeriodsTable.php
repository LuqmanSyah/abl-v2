<?php

namespace App\Filament\Resources\ReviewPeriods\Tables;

use App\Enums\UserRole;
use App\Models\EmployeeKpi;
use App\Models\ReviewPeriod;
use App\Services\MeritCalculator;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReviewPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('starts_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('kpi_weight')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('discipline_weight')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('manager_weight')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('review_360_weight')
                    ->label('Bobot umpan balik (%)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('base_bonus')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('calculate')
                    ->label('Hitung Merit')
                    ->icon('heroicon-o-calculator')
                    ->requiresConfirmation()
                    ->modalHeading('Hitung Ulang Merit')
                    ->modalDescription('Seluruh hasil merit Pegawai pada periode ini akan dihitung dari data terbaru.')
                    ->modalSubmitActionLabel('Hitung Merit')
                    ->modalWidth('md')
                    ->visible(fn (ReviewPeriod $record): bool => auth()->user()?->role === UserRole::Hr
                        && ! $record->hasPublishedMeritResults())
                    ->action(function (Action $action, ReviewPeriod $record): void {
                        try {
                            if ($record->fresh()->hasPublishedMeritResults()) {
                                throw new DomainException('Hasil merit yang telah dipublikasikan tidak dapat dihitung ulang.');
                            }

                            $employees = EmployeeKpi::with('employee')->where('review_period_id', $record->id)
                                ->get()->pluck('employee')->unique('id');
                            foreach ($employees as $employee) {
                                app(MeritCalculator::class)->calculate($record, $employee);
                            }
                        } catch (DomainException $exception) {
                            Notification::make()
                                ->title('Merit tidak dapat dihitung')
                                ->body($exception->getMessage())
                                ->warning()
                                ->send();
                            $action->failure();
                        }
                    })
                    ->successNotificationTitle('Merit berhasil dihitung'),
                EditAction::make(),
            ]);
    }
}
