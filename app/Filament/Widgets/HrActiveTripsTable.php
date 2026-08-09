<?php

namespace App\Filament\Widgets;

use App\Enums\DutyTripStatus;
use App\Filament\Resources\Attendances\AttendanceResource;
use App\Models\DutyTrip;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class HrActiveTripsTable extends TableWidget
{
    protected static ?string $heading = 'Pegawai Sedang Dinas';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DutyTrip::with(['employee', 'attendances' => fn ($q) => $q->latest()])
                    ->where('status', DutyTripStatus::Approved)
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
                TextColumn::make('starts_at')
                    ->label('Mulai')
                    ->dateTime('d M Y'),
                TextColumn::make('ends_at')
                    ->label('Selesai')
                    ->dateTime('d M Y'),
                TextColumn::make('attendance_status')
                    ->label('Status Absensi Dinas')
                    ->getStateUsing(function (DutyTrip $record): string {
                        $latest = $record->attendances->first();

                        return $latest ? $latest->status->label() : 'Belum absen';
                    })
                    ->badge()
                    ->color(function (DutyTrip $record): string {
                        $latest = $record->attendances->first();

                        return $latest?->status->color() ?? 'warning';
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view_attendance')
                        ->label('Lihat Absensi Dinas')
                        ->icon('heroicon-o-eye')
                        ->url(fn (DutyTrip $record): string => AttendanceResource::getUrl('index', [
                            'tableFilters[duty_trip_id][value]' => $record->id,
                        ])),
                ]),
            ])
            ->emptyStateHeading('Tidak ada pegawai sedang dinas')
            ->emptyStateDescription('Semua pegawai sedang di kantor')
            ->paginated(false);
    }
}
