<?php

namespace App\Filament\Widgets;

use App\Enums\DutyTripStatus;
use App\Filament\Resources\Attendances\AttendanceResource;
use App\Filament\Resources\DutyTrips\DutyTripResource;
use App\Models\DutyTrip;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class EmployeeActiveTripsTable extends TableWidget
{
    protected static ?string $heading = 'Dinas Aktif Hari Ini';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DutyTrip::where('employee_id', auth()->id())
                    ->where('status', DutyTripStatus::Approved)
                    ->whereDate('starts_at', '<=', today())
                    ->whereDate('ends_at', '>=', today())
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
                TextColumn::make('attendances_count')
                    ->label('Absensi')
                    ->counts('attendances')
                    ->formatStateUsing(fn (DutyTrip $record): string => $record->attendances()->count() > 0 ? 'Sudah absen' : 'Belum absen')
                    ->color(fn (DutyTrip $record): string => $record->attendances()->count() > 0 ? 'success' : 'warning')
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
                        ->visible(fn (DutyTrip $record): bool => $record->attendances()->count() === 0)
                        ->url(fn (DutyTrip $record): string => route('attendance.capture', ['duty_trip' => $record->id])),
                ]),
            ])
            ->emptyStateHeading('Tidak ada dinas aktif hari ini')
            ->emptyStateDescription('Dinas yang sedang berjalan akan muncul di sini')
            ->paginated(false);
    }
}
