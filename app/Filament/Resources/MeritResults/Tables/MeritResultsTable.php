<?php

namespace App\Filament\Resources\MeritResults\Tables;

use App\Enums\UserRole;
use App\Models\MeritResult;
use App\Models\Training;
use App\Models\TrainingRequest;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MeritResultsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reviewPeriod.name')
                    ->label('Periode')
                    ->searchable(),
                TextColumn::make('employee.name')
                    ->label('Pegawai')
                    ->searchable(),
                TextColumn::make('kpi_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('discipline_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('manager_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('review_360_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_score')
                    ->label('Skor merit')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('estimated_bonus')
                    ->label('Estimasi bonus')
                    ->money('IDR')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('manager_verified_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('manager_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('hr_verified_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('hr_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
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
                ViewAction::make(),
                Action::make('recommend_training')
                    ->label('Rekomendasikan Pelatihan')
                    ->icon('heroicon-o-academic-cap')
                    ->color('success')
                    ->visible(fn (MeritResult $record): bool => auth()->user()?->role === UserRole::Manager
                        && $record->employee->manager_id === auth()->id()
                        && $record->employee->is_active)
                    ->modalHeading(fn (MeritResult $record): string => "Rekomendasi Pelatihan — {$record->employee->name}")
                    ->modalDescription('Rekomendasi langsung disetujui tanpa antrean verifikasi HR.')
                    ->modalContent(fn (MeritResult $record) => view(
                        'filament.resources.merit-results.recommend-training-breakdown',
                        ['breakdown' => $record->breakdownForManager(auth()->user())],
                    ))
                    ->schema([
                        Select::make('training_id')
                            ->label('Pelatihan')
                            ->options(fn (MeritResult $record): array => Training::query()
                                ->where('is_active', true)
                                ->whereDoesntHave('requests', fn (Builder $query) => $query->where('user_id', $record->employee_id))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            ->helperText('Hanya pelatihan aktif yang belum pernah diajukan pegawai.'),
                        Textarea::make('reason')
                            ->label('Alasan rekomendasi')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->modalSubmitActionLabel('Rekomendasikan')
                    ->action(function (MeritResult $record, array $data): void {
                        TrainingRequest::recommendByManager(
                            auth()->user(),
                            $record->employee,
                            Training::findOrFail($data['training_id']),
                            $record,
                            $data['reason'],
                        );
                    })
                    ->successNotificationTitle('Pelatihan direkomendasikan dan langsung disetujui'),
            ]);
    }
}
