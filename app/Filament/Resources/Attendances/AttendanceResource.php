<?php

namespace App\Filament\Resources\Attendances;

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Attendances\Pages\ListAttendances;
use App\Filament\Resources\Attendances\Pages\ViewAttendance;
use App\Models\Attendance;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?string $modelLabel = 'absensi';

    protected static ?string $pluralModelLabel = 'absensi';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        return match ($user->role) {
            UserRole::Employee => $query->where('employee_id', $user->id),
            UserRole::Manager => $query->whereHas(
                'dutyTrip',
                fn (Builder $query) => $query->where('manager_id', $user->id),
            ),
            UserRole::Hr => $query,
        };
    }

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function canView(Model $record): bool
    {
        return static::getEloquentQuery()->whereKey($record)->exists();
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

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('employee.name')->label('Pegawai'),
            TextEntry::make('dutyTrip.location_name')->label('Lokasi dinas'),
            TextEntry::make('received_at')->label('Waktu server')->dateTime(),
            TextEntry::make('status')
                ->badge()
                ->formatStateUsing(fn (AttendanceStatus $state): string => $state->label())
                ->color(fn (AttendanceStatus $state): string => $state->color()),
            TextEntry::make('distance_meters')->label('Jarak')->suffix(' meter'),
            TextEntry::make('accuracy_meters')->label('Akurasi GPS')->suffix(' meter'),
            TextEntry::make('review_reason')->label('Alasan pemeriksaan')->placeholder('-'),
            ImageEntry::make('photo_path')
                ->label('Foto')
                ->getStateUsing(fn (Attendance $record): string => route('attendance.photo', $record))
                ->imageHeight(240),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')->label('Pegawai')->searchable(),
                TextColumn::make('dutyTrip.location_name')->label('Lokasi')->searchable(),
                TextColumn::make('received_at')->label('Waktu')->dateTime()->sortable(),
                TextColumn::make('distance_meters')->label('Jarak')->suffix(' m')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (AttendanceStatus $state): string => $state->label())
                    ->color(fn (AttendanceStatus $state): string => $state->color()),
            ])
            ->filters([
                SelectFilter::make('status')->options(AttendanceStatus::options()),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('verify')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Attendance $record): bool => auth()->user()?->role === UserRole::Hr
                        && $record->status === AttendanceStatus::NeedsReview)
                    ->action(fn (Attendance $record) => $record->verifyByHr(auth()->user())),
            ])
            ->defaultSort('received_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttendances::route('/'),
            'view' => ViewAttendance::route('/{record}'),
        ];
    }
}
