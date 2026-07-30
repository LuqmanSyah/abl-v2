<?php

namespace App\Filament\Resources\DutyTrips;

use App\Enums\DutyTripStatus;
use App\Enums\UserRole;
use App\Filament\Forms\Components\MapPicker;
use App\Filament\Resources\DutyTrips\Pages\CreateDutyTrip;
use App\Filament\Resources\DutyTrips\Pages\EditDutyTrip;
use App\Filament\Resources\DutyTrips\Pages\ListDutyTrips;
use App\Filament\Resources\DutyTrips\Pages\ViewDutyTrip;
use App\Models\DutyTrip;
use App\Models\User;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

class DutyTripResource extends Resource
{
    protected static ?string $model = DutyTrip::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?string $modelLabel = 'perjalanan dinas';

    protected static ?string $pluralModelLabel = 'perjalanan dinas';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
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
        return auth()->user()?->role === UserRole::Manager;
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof DutyTrip && $record->canBeChangedBy(auth()->user());
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('employee_ids')
                ->label('Pegawai')
                ->options(fn () => User::query()
                    ->where('role', UserRole::Employee)
                    ->where('manager_id', auth()->id())
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->multiple()
                ->searchable()
                ->preload()
                ->required()
                ->visibleOn('create'),
            Select::make('employee_id')
                ->label('Pegawai')
                ->relationship('employee', 'name', fn (Builder $query) => $query
                    ->where('role', UserRole::Employee)
                    ->where('manager_id', auth()->id())
                    ->where('is_active', true))
                ->searchable()
                ->preload()
                ->required()
                ->visibleOn('edit'),
            TextInput::make('location_name')->label('Lokasi')->required(),
            Textarea::make('address')->label('Alamat')->required()->columnSpanFull(),
            MapPicker::make('map_picker')
                ->label('Pilih titik pada peta')
                ->dehydrated(false)
                ->columnSpanFull(),
            TextInput::make('latitude')->numeric()->required(),
            TextInput::make('longitude')->numeric()->required(),
            TextInput::make('radius_meters')->label('Radius (meter)')->numeric()->minValue(1)->default(100)->required(),
            DatePicker::make('starts_at')
                ->label('Mulai')
                ->dehydrateStateUsing(fn (string $state): Carbon => Carbon::parse($state)->startOfDay())
                ->required(),
            DatePicker::make('ends_at')
                ->label('Selesai')
                ->afterOrEqual('starts_at')
                ->dehydrateStateUsing(fn (string $state): Carbon => Carbon::parse($state)->endOfDay())
                ->required(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('employee.name')->label('Pegawai'),
            TextEntry::make('manager.name')->label('Atasan'),
            TextEntry::make('location_name')->label('Lokasi'),
            TextEntry::make('address')->label('Alamat'),
            TextEntry::make('starts_at')->label('Mulai')->date(),
            TextEntry::make('ends_at')->label('Selesai')->date(),
            TextEntry::make('radius_meters')->label('Radius')->suffix(' meter'),
            TextEntry::make('status')
                ->badge()
                ->formatStateUsing(fn (DutyTripStatus $state): string => $state->label())
                ->color(fn (DutyTripStatus $state): string => $state->color()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')->label('Pegawai')->searchable(),
                TextColumn::make('location_name')->label('Lokasi')->searchable(),
                TextColumn::make('starts_at')->label('Mulai')->date()->sortable(),
                TextColumn::make('ends_at')->label('Selesai')->date()->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (DutyTripStatus $state): string => $state->label())
                    ->color(fn (DutyTripStatus $state): string => $state->color()),
            ])
            ->filters([
                SelectFilter::make('status')->options(DutyTripStatus::options()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (DutyTrip $record): bool => static::canEdit($record)),
            ])
            ->defaultSort('starts_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDutyTrips::route('/'),
            'create' => CreateDutyTrip::route('/create'),
            'view' => ViewDutyTrip::route('/{record}'),
            'edit' => EditDutyTrip::route('/{record}/edit'),
        ];
    }
}
