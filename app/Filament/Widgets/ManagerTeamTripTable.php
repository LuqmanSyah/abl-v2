<?php

namespace App\Filament\Widgets;

use App\Enums\DutyTripStatus;
use App\Filament\Resources\DutyTrips\DutyTripResource;
use App\Models\DutyTrip;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ManagerTeamTripTable extends TableWidget
{
    protected static ?string $heading = 'Status Dinas Anggota Tim Hari Ini';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DutyTrip::where('manager_id', auth()->id())
                    ->whereDate('starts_at', '<=', today())
                    ->whereDate('ends_at', '>=', today())
            )
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Pegawai')
                    ->searchable(),
                TextColumn::make('destination')
                    ->label('Tujuan')
                    ->searchable(),
                TextColumn::make('location_name')
                    ->label('Lokasi'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof DutyTripStatus ? $state->label() : (string) $state)
                    ->color(fn ($state): string => $state instanceof DutyTripStatus ? $state->color() : 'gray'),
                TextColumn::make('attendances_count')
                    ->label('Absensi Dinas')
                    ->counts('attendances')
                    ->formatStateUsing(fn (DutyTrip $record): string => $record->attendances()->count() > 0 ? 'Sudah absen' : 'Belum absen')
                    ->color(fn (DutyTrip $record): string => $record->attendances()->count() > 0 ? 'success' : 'warning')
                    ->badge(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('Lihat Detail')
                        ->icon('heroicon-o-eye')
                        ->url(fn (DutyTrip $record): string => DutyTripResource::getUrl('view', [$record])),
                ]),
            ])
            ->emptyStateHeading('Semua anggota selesai bertugas')
            ->emptyStateDescription('Tidak ada dinas berjalan hari ini')
            ->paginated(false);
    }
}
