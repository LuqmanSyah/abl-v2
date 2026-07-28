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

class EmployeeActiveTripsTable extends TableWidget
{
    protected static ?string $heading = 'Peringatan Absensi Dinas Hari Ini';

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DutyTrip::where('employee_id', auth()->id())
                    ->where('status', DutyTripStatus::Approved)
                    ->whereDate('starts_at', '<=', today())
                    ->whereDate('ends_at', '>=', today())
                    ->withExists([
                        'attendances as attended_today' => fn ($query) => $query->whereDate('attendance_date', today()),
                    ])
            )
            ->columns([
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
                    ->label('Absensi')
                    ->getStateUsing(fn (DutyTrip $record): string => $record->attended_today ? 'Sudah absen hari ini' : 'Belum absen hari ini')
                    ->color(fn (DutyTrip $record): string => $record->attended_today ? 'success' : 'danger')
                    ->badge(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view_trip')
                        ->label('Buka Dinas')
                        ->icon('heroicon-o-arrow-right')
                        ->url(fn (DutyTrip $record): string => DutyTripResource::getUrl('view', [$record])),
                    Action::make('attend')
                        ->label('Absen Sekarang')
                        ->icon('heroicon-o-map-pin')
                        ->color('success')
                        ->visible(fn (DutyTrip $record): bool => ! $record->attended_today)
                        ->url(fn (DutyTrip $record): string => route('attendance.capture', $record)),
                ]),
            ])
            ->emptyStateHeading('Tidak ada dinas aktif hari ini')
            ->emptyStateDescription('Dinas yang sedang berjalan akan muncul di sini')
            ->paginated(false);
    }
}
