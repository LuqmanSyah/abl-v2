<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceRequestStatus;
use App\Enums\FlowType;
use App\Enums\UserRole;
use App\Filament\Resources\AttendanceRequestResource\Pages;
use App\Models\AttendanceRequest;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class AttendanceRequestResource extends RoleAwareResource
{
    private const DATE_TIME_FORMAT = 'd M Y H:i';

    protected static ?string $model = AttendanceRequest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static string|UnitEnum|null $navigationGroup = 'Kehadiran';

    protected static ?string $modelLabel = 'Izin Tugas Luar';

    protected static ?string $pluralModelLabel = 'Izin Tugas Luar';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->relationship(
                    name: 'user',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query) => Auth::user() instanceof User
                        && Auth::user()->role === UserRole::Manager
                        ? $query->where('manager_id', Auth::id())
                        : $query,
                )
                ->default(fn () => Auth::id())
                ->disabled(fn (): bool => Filament::getCurrentPanel()?->getId() === 'employee')
                ->dehydrated()
                ->required()
                ->searchable()
                ->preload()
                ->label('Karyawan'),

            Select::make('created_by')
                ->relationship('creator', 'name')
                ->default(fn () => Auth::id())
                ->disabled()
                ->dehydrated(false)
                ->label('Dibuat Oleh'),

            Select::make('flow_type')
                ->options(FlowType::class)
                ->default(fn (): string => Filament::getCurrentPanel()?->getId() === 'employee'
                    ? FlowType::BottomUp->value
                    : FlowType::TopDown->value)
                ->disabled()
                ->dehydrated(false)
                ->label('Tipe Alur'),

            TextInput::make('destination_name')
                ->required()
                ->maxLength(255)
                ->label('Nama Tujuan'),

            Textarea::make('destination_address')
                ->required()
                ->maxLength(500)
                ->label('Alamat Tujuan'),

            TextInput::make('target_latitude')
                ->required()
                ->numeric()
                ->minValue(-90)
                ->maxValue(90)
                ->step(0.0000001)
                ->live(debounce: 500)
                ->label('Latitude'),

            TextInput::make('target_longitude')
                ->required()
                ->numeric()
                ->minValue(-180)
                ->maxValue(180)
                ->step(0.0000001)
                ->live(debounce: 500)
                ->label('Longitude'),

            View::make('filament.forms.components.map-picker')
                ->viewData([
                    'latitudeStatePath' => 'data.target_latitude',
                    'longitudeStatePath' => 'data.target_longitude',
                ])
                ->columnSpanFull(),

            TextInput::make('allowed_radius_meters')
                ->required()
                ->numeric()
                ->default(200)
                ->label('Radius (meter)'),

            DateTimePicker::make('duty_start_datetime')
                ->required()
                ->label('Mulai Tugas'),

            DateTimePicker::make('duty_end_datetime')
                ->required()
                ->afterOrEqual('duty_start_datetime')
                ->label('Selesai Tugas'),

            Textarea::make('reason')
                ->required()
                ->maxLength(500)
                ->label('Alasan'),

            Select::make('status')
                ->options(AttendanceRequestStatus::class)
                ->default(AttendanceRequestStatus::Pending->value)
                ->required()
                ->disabled()
                ->dehydrated(false)
                ->label('Status'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->sortable()
                    ->searchable()
                    ->label('Karyawan'),

                TextColumn::make('flow_type')
                    ->badge()
                    ->label('Alur'),

                TextColumn::make('destination_name')
                    ->limit(30)
                    ->label('Tujuan'),

                TextColumn::make('duty_start_datetime')
                    ->dateTime(self::DATE_TIME_FORMAT)
                    ->sortable()
                    ->label('Mulai'),

                TextColumn::make('duty_end_datetime')
                    ->dateTime(self::DATE_TIME_FORMAT)
                    ->sortable()
                    ->label('Selesai'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (AttendanceRequestStatus $state) => match ($state) {
                        AttendanceRequestStatus::Pending => 'warning',
                        AttendanceRequestStatus::Approved => 'success',
                        AttendanceRequestStatus::Rejected => 'danger',
                        AttendanceRequestStatus::Cancelled => 'gray',
                    })
                    ->label('Status'),

                TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('approver.name')
                    ->label('Disetujui Oleh')
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->dateTime(self::DATE_TIME_FORMAT)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Dibuat'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(AttendanceRequestStatus::class)
                    ->label('Status'),

                SelectFilter::make('flow_type')
                    ->options(FlowType::class)
                    ->label('Tipe Alur'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (AttendanceRequest $record): bool => $record->canBeDecidedBy(Auth::user()))
                    ->action(fn (AttendanceRequest $record) => $record->approve(Auth::user())),

                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (AttendanceRequest $record): bool => $record->canBeDecidedBy(Auth::user()))
                    ->action(fn (AttendanceRequest $record) => $record->reject(Auth::user())),

                Action::make('cancel')
                    ->label('Batalkan')
                    ->icon('heroicon-o-no-symbol')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (AttendanceRequest $record): bool => $record->canBeCancelledBy(Auth::user()))
                    ->action(fn (AttendanceRequest $record) => $record->cancel(Auth::user())),

                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (AttendanceRequest $record): bool => static::canEdit($record)),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Filament::getCurrentPanel()?->getId() === 'employee') {
            return $query->whereBelongsTo(Auth::user());
        }

        $user = Auth::user();

        return $user instanceof User && $user->role === UserRole::Manager
            ? $query->whereHas('user', fn (Builder $query) => $query->where('manager_id', $user->id))
            : $query;
    }

    public static function canEdit(Model $record): bool
    {
        if (! parent::canEdit($record)
            || ! $record instanceof AttendanceRequest
            || $record->status !== AttendanceRequestStatus::Pending) {
            return false;
        }

        if (Filament::getCurrentPanel()?->getId() === 'employee') {
            return $record->user_id === Auth::id();
        }

        $actor = Auth::user();

        return $actor instanceof User
            && $actor->role === UserRole::Manager
            && $record->user->manager_id === $actor->id;
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record) && parent::canDelete($record);
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        $actor = Auth::user();

        if (! parent::canCreate() || ! $actor instanceof User) {
            return false;
        }

        return Filament::getCurrentPanel()?->getId() === 'employee'
            ? $actor->role === UserRole::Employee
            : $actor->role === UserRole::Manager;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendanceRequests::route('/'),
            'create' => Pages\CreateAttendanceRequest::route('/create'),
            'edit' => Pages\EditAttendanceRequest::route('/{record}/edit'),
        ];
    }
}
