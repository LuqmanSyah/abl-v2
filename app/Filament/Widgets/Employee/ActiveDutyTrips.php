<?php

namespace App\Filament\Widgets\Employee;

use App\Enums\AttendanceRequestStatus;
use App\Models\AttendanceRequest;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ActiveDutyTrips extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Tugas Luar Aktif')
            ->query(AttendanceRequest::query()
                ->where('user_id', auth()->id())
                ->where('status', AttendanceRequestStatus::Approved)
                ->where('duty_start_datetime', '<=', now())
                ->where('duty_end_datetime', '>=', now()))
            ->columns([
                TextColumn::make('destination_name')->label('Tujuan'),
                TextColumn::make('duty_start_datetime')->dateTime('d M Y H:i')->label('Mulai'),
                TextColumn::make('duty_end_datetime')->dateTime('d M Y H:i')->label('Selesai'),
            ])
            ->paginated(false);
    }
}
