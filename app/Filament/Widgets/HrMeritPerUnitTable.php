<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\MeritResults\MeritResultResource;
use App\Models\MeritResult;
use App\Models\ReviewPeriod;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class HrMeritPerUnitTable extends TableWidget
{
    protected static ?string $heading = 'Rata-rata Nilai Merit per Unit';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $period = ReviewPeriod::where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->first();

        return $table
            ->records(function () use ($period): array {
                if (! $period) {
                    return [];
                }

                $rows = MeritResult::selectRaw('units.name as unit_name')
                    ->selectRaw('ROUND(AVG(merit_results.total_score), 2) as avg_score')
                    ->selectRaw('COUNT(merit_results.id) as employee_count')
                    ->join('users', 'merit_results.employee_id', '=', 'users.id')
                    ->join('units', 'users.unit_id', '=', 'units.id')
                    ->where('merit_results.review_period_id', $period->id)
                    ->whereNotNull('merit_results.published_at')
                    ->groupBy('units.id', 'units.name')
                    ->orderBy('avg_score', 'desc')
                    ->get()
                    ->map(fn ($row, int $i): array => [
                        'key' => (string) $i,
                        'unit_name' => $row->unit_name,
                        'avg_score' => (float) $row->avg_score,
                        'employee_count' => (int) $row->employee_count,
                    ])
                    ->all();

                return $rows;
            })
            ->columns([
                TextColumn::make('unit_name')
                    ->label('Unit Kerja')
                    ->searchable(),
                TextColumn::make('avg_score')
                    ->label('Rata-rata Total')
                    ->numeric(decimalPlaces: 2)
                    ->color(fn ($state): string => (float) $state >= 80 ? 'success' : ((float) $state >= 60 ? 'warning' : 'danger'))
                    ->sortable(),
                TextColumn::make('employee_count')
                    ->label('Pegawai Dinilai')
                    ->numeric(),
            ])
            ->recordUrl(fn (array $record): string => MeritResultResource::getUrl('index'))
            ->emptyStateHeading('Belum ada data merit')
            ->emptyStateDescription($period ? 'Periode ' . $period->name : 'Tidak ada periode aktif')
            ->paginated(false);
    }
}
