<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\EmployeeKpis\EmployeeKpiResource;
use App\Models\EmployeeKpi;
use App\Models\ReviewPeriod;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ManagerIncompleteKpiTable extends TableWidget
{
    protected static ?string $heading = 'Anggota Tim Belum Isi KPI';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $managerId = auth()->id();
        $period = ReviewPeriod::where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->first();

        return $table
            ->query(
                User::where('manager_id', $managerId)
                    ->where('is_active', true)
                    ->whereDoesntHave('employeeKpis', fn ($q) => $q->when(
                        $period,
                        fn ($q) => $q->where('review_period_id', $period->id)
                    ))
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Nama'),
                TextColumn::make('employee_number')
                    ->label('NIP'),
                TextColumn::make('position.name')
                    ->label('Jabatan'),
            ])
            ->recordUrl(fn (User $record): string => EmployeeKpiResource::getUrl('create', ['employee_id' => $record->id]))
            ->emptyStateHeading('Semua anggota sudah punya KPI')
            ->emptyStateDescription('Periode: ' . ($period?->name ?? 'tidak ada periode aktif'))
            ->paginated(false);
    }
}
