<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Enums\UserRole;
use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'riwayat aktivitas';

    protected static ?string $pluralModelLabel = 'riwayat aktivitas';

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === UserRole::Hr;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime()->sortable(),
                TextColumn::make('user.name')->label('Pengguna')->placeholder('Sistem')->searchable(),
                TextColumn::make('action')->label('Aksi')->badge()->searchable(),
                TextColumn::make('subject_type')->label('Jenis data')->formatStateUsing(fn (string $state): string => class_basename($state)),
                TextColumn::make('subject_id')->label('ID data'),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListActivityLogs::route('/')];
    }
}
