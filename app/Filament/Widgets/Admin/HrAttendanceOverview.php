<?php

namespace App\Filament\Widgets\Admin;

use App\Enums\DailySummaryStatus;
use App\Enums\UserRole;
use App\Models\DailyAttendanceSummary;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class HrAttendanceOverview extends TableWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->role === UserRole::HrAdmin;
    }

    public function table(Table $table): Table
    {
        $present = [DailySummaryStatus::Present->value, DailySummaryStatus::Late->value];
        $tracked = [...$present, DailySummaryStatus::Alfa->value, DailySummaryStatus::MissingCheckout->value];

        return $table
            ->heading('Kehadiran Hari Ini per Departemen')
            ->query(DailyAttendanceSummary::query()
                ->join('users', 'users.id', '=', 'daily_attendance_summaries.user_id')
                ->join('positions', 'positions.id', '=', 'users.position_id')
                ->join('departments', 'departments.id', '=', 'positions.department_id')
                ->whereDate('daily_attendance_summaries.date', today())
                ->selectRaw(
                    'MIN(daily_attendance_summaries.id) AS id, departments.name AS department_name,
                    SUM(CASE WHEN daily_attendance_summaries.status IN (?, ?) THEN 1 ELSE 0 END) AS present_count,
                    SUM(CASE WHEN daily_attendance_summaries.status IN (?, ?, ?, ?) THEN 1 ELSE 0 END) AS tracked_count',
                    [...$present, ...$tracked],
                )
                ->groupBy('departments.id', 'departments.name'))
            ->columns([
                TextColumn::make('department_name')->label('Departemen'),
                TextColumn::make('present_count')->numeric()->label('Hadir'),
                TextColumn::make('tracked_count')->numeric()->label('Hari Kerja'),
                TextColumn::make('attendance_rate')
                    ->state(fn (DailyAttendanceSummary $record): float => $record->tracked_count
                        ? round($record->present_count / $record->tracked_count * 100, 1)
                        : 0)
                    ->suffix('%')
                    ->label('Tingkat Kehadiran'),
            ])
            ->defaultKeySort(false)
            ->paginated(false);
    }
}
