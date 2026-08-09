<?php

namespace App\Filament\Resources\ReviewPeriods\Tables;

use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Models\ReviewPeriod;
use App\Services\MeritBatchCalculator;
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

                        $summary = app(MeritBatchCalculator::class)->calculate($record);
                        $errorCount = count($summary['errors']);

                        if ($summary['processed'] === 0) {
                            $message = $errorCount
                                ? "Tidak ada hasil merit yang dapat dihitung. {$errorCount} Pegawai dilewati."
                                : 'Data merit belum tersedia: tidak ada Pegawai aktif.';

                            throw new BusinessRuleException($message);
                        }

                        $notification = Notification::make();
                        if ($errorCount) {
                            $notification
                                ->title("Merit selesai: {$summary['processed']} berhasil, {$errorCount} dilewati")
                                ->warning();
                        } else {
                            $notification->title('Merit berhasil dihitung')->success();
                        }

                        $notification->send();
                    })
                    ->successNotification(null),
                EditAction::make(),
            ]);
    }
}
