<?php

namespace App\Filament\Resources\ReviewPeriods\Tables;

use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Models\EmployeeKpi;
use App\Models\ReviewPeriod;
use App\Services\MeritCalculator;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
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
                    ->label('Bobot umpan balik rekan (%)')
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
                    ->action(function (ReviewPeriod $record): void {
                        if ($record->fresh()->hasPublishedMeritResults()) {
                            throw new BusinessRuleException('Hasil merit yang telah dipublikasikan tidak dapat dihitung ulang.');
                        }

                        $employees = EmployeeKpi::with('employee')->where('review_period_id', $record->id)
                            ->get()->pluck('employee')->unique('id');
                        if ($employees->isEmpty()) {
                            throw new BusinessRuleException('Data merit belum tersedia: belum ada KPI Pegawai pada periode ini.');
                        }

                        foreach ($employees as $employee) {
                            app(MeritCalculator::class)->calculate($record, $employee);
                        }
                    })
                    ->successNotificationTitle('Merit berhasil dihitung'),
                EditAction::make(),
            ]);
    }
}
