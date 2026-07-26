<?php

namespace App\Filament\Resources;

use App\Enums\DailySummaryStatus;
use App\Filament\Resources\DailyAttendanceSummaryResource\Pages;
use App\Models\DailyAttendanceSummary;
use App\Models\Department;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class DailyAttendanceSummaryResource extends RoleAwareResource
{
    protected static ?string $model = DailyAttendanceSummary::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-date-range';

    protected static string|UnitEnum|null $navigationGroup = 'Kehadiran';

    protected static ?string $modelLabel = 'Rekap Kehadiran';

    protected static ?string $pluralModelLabel = 'Rekap Kehadiran';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')->date('d M Y')->sortable()->label('Tanggal'),
                TextColumn::make('user.name')->searchable()->sortable()->label('Karyawan'),
                TextColumn::make('user.position.department.name')->label('Departemen'),
                TextColumn::make('attendanceRequest.destination_name')
                    ->placeholder('Kantor biasa')
                    ->label('Sesi'),
                TextColumn::make('checkIn.recorded_at')->time('H:i')->placeholder('-')->label('Check-in'),
                TextColumn::make('checkOut.recorded_at')->time('H:i')->placeholder('-')->label('Check-out'),
                TextColumn::make('status')->badge()->label('Status'),
                TextColumn::make('late_minutes')->numeric()->suffix(' menit')->label('Terlambat'),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Karyawan'),
                SelectFilter::make('status')
                    ->options(DailySummaryStatus::class)
                    ->label('Status'),
                Filter::make('department')
                    ->schema([
                        Select::make('department_id')
                            ->options(fn () => Department::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->label('Departemen'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['department_id'] ?? null, fn (Builder $query, int|string $departmentId) => $query
                            ->whereHas('user.position', fn (Builder $query) => $query
                                ->where('department_id', $departmentId)))),
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date) => $query
                            ->whereDate('date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date) => $query
                            ->whereDate('date', '<=', $date))),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyAttendanceSummaries::route('/'),
        ];
    }
}
