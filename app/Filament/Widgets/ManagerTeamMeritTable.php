<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\MeritResult;
use App\Models\ReviewPeriod;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ManagerTeamMeritTable extends TableWidget
{
    protected static ?string $heading = 'Nilai Merit Tim';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $managerId = auth()->id();
        $period = ReviewPeriod::where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->first();

        return $table
            ->query(
                MeritResult::with('employee')
                    ->whereHas('employee', fn ($q) => $q->where('manager_id', $managerId))
                    ->when($period, fn ($q) => $q->where('review_period_id', $period->id))
            )
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Pegawai')
                    ->searchable(),
                TextColumn::make('kpi_score')
                    ->label('Skor KPI')
                    ->numeric(decimalPlaces: 2)
                    ->color(fn ($state): string => (float) $state >= 80 ? 'success' : ((float) $state >= 60 ? 'warning' : 'danger')),
                TextColumn::make('discipline_score')
                    ->label('Kedisiplinan')
                    ->numeric(decimalPlaces: 2)
                    ->color(fn ($state): string => (float) $state >= 80 ? 'success' : ((float) $state >= 60 ? 'warning' : 'danger')),
                TextColumn::make('total_score')
                    ->label('Total')
                    ->numeric(decimalPlaces: 2)
                    ->color(fn ($state): string => (float) $state >= 80 ? 'success' : ((float) $state >= 60 ? 'warning' : 'danger'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (MeritResult $record): string => $record->published_at ? 'Terpublikasi' : ($record->hr_verified_at ? 'Verifikasi HR' : ($record->manager_verified_at ? 'Verifikasi Manager' : 'Menunggu')))
                    ->color(fn (MeritResult $record): string => $record->published_at ? 'success' : ($record->hr_verified_at ? 'info' : ($record->manager_verified_at ? 'warning' : 'gray'))),
            ])
            ->defaultSort('total_score', 'desc')
            ->emptyStateHeading('Belum ada hasil merit')
            ->emptyStateDescription($period ? 'Periode ' . $period->name : 'Tidak ada periode aktif')
            ->paginated(false);
    }
}
