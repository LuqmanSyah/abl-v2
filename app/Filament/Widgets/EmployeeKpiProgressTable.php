<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\EmployeeKpis\EmployeeKpiResource;
use App\Models\EmployeeKpi;
use App\Models\ReviewPeriod;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class EmployeeKpiProgressTable extends TableWidget
{
    protected static ?string $heading = 'Progress KPI';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $userId = auth()->id();
        $period = ReviewPeriod::where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->first();

        return $table
            ->query(
                EmployeeKpi::with('indicator')
                    ->where('employee_id', $userId)
                    ->when($period, fn ($q) => $q->where('review_period_id', $period->id))
            )
            ->columns([
                TextColumn::make('indicator.name')
                    ->label('Indikator')
                    ->searchable(),
                TextColumn::make('target')
                    ->label('Target')
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('achievement')
                    ->label('Capaian')
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('percentage')
                    ->label('Persentase')
                    ->getStateUsing(fn (EmployeeKpi $record): string => number_format(min((float) $record->achievement / max((float) $record->target, 0.01) * 100, 120), 1) . '%')
                    ->color(fn (EmployeeKpi $record): string => (float) $record->achievement >= (float) $record->target ? 'success' : 'warning')
                    ->badge(),
            ])
            ->recordUrl(fn (EmployeeKpi $record): string => EmployeeKpiResource::getUrl('view', [$record]))
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Belum ada KPI')
            ->emptyStateDescription('Periode: ' . ($period?->name ?? 'tidak ada periode aktif'))
            ->paginated(false);
    }
}
