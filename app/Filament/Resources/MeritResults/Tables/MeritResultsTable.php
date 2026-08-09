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
                    ->label('Nilai KPI')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('discipline_score')
                    ->label('Nilai kepatuhan dinas')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('manager_score')
                    ->label('Nilai Atasan')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('review_360_score')
                    ->label('Nilai umpan balik rekan')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_score')
                    ->label('Skor merit')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('estimated_bonus')
                    ->label('Simulasi bonus')
                    ->money('IDR')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('calculated_at')
                    ->label('Di-update')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('manager_verified_at')
                    ->label('Verifikasi Atasan')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('hr_verified_at')
                    ->label('Verifikasi HR')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label('Publikasi')
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
            ->recordActions([
                ViewAction::make(),
                Action::make('recommend_training')
                    ->label('Rekomendasikan Pelatihan')
                    ->icon('heroicon-o-academic-cap')
                    ->color('success')
                    ->visible(fn (MeritResult $record): bool => auth()->user()?->role === UserRole::Manager
                        && $record->employee->manager_id === auth()->id()
                        && $record->employee->is_active
                        && $record->published_at !== null)
                    ->modalHeading(fn (MeritResult $record): string => "Rekomendasi Pelatihan — {$record->employee->name}")
                    ->modalDescription('Rekomendasi langsung disetujui tanpa antrean verifikasi HR.')
                    ->modalWidth('5xl')
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
