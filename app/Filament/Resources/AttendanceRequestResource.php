<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceRequestStatus;
use App\Enums\FlowType;
use App\Filament\Resources\AttendanceRequestResource\Pages;
use App\Models\AttendanceRequest;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AttendanceRequestResource extends RoleAwareResource
{
    protected static ?string $model = AttendanceRequest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static string|UnitEnum|null $navigationGroup = 'Kehadiran';

    protected static ?string $modelLabel = 'Izin Tugas Luar';

    protected static ?string $pluralModelLabel = 'Izin Tugas Luar';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->relationship('user', 'name')
                ->default(fn () => auth()->id())
                ->disabled(fn (): bool => Filament::getCurrentPanel()?->getId() === 'employee')
                ->dehydrated()
                ->required()
                ->searchable()
                ->preload()
                ->label('Karyawan'),

            Select::make('created_by')
                ->relationship('creator', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->default(fn () => auth()->id())
                ->disabled(fn (): bool => Filament::getCurrentPanel()?->getId() === 'employee')
                ->dehydrated()
                ->label('Dibuat Oleh'),

            Select::make('flow_type')
                ->options(FlowType::class)
                ->default(FlowType::BottomUp->value)
                ->disabled(fn (): bool => Filament::getCurrentPanel()?->getId() === 'employee')
                ->dehydrated()
                ->required()
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
                ->label('Latitude'),

            TextInput::make('target_longitude')
                ->required()
                ->numeric()
                ->label('Longitude'),

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
                ->dehydrated()
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
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->label('Mulai'),

                TextColumn::make('duty_end_datetime')
                    ->dateTime('d M Y H:i')
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
                    ->dateTime('d M Y H:i')
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
                    ->visible(fn (AttendanceRequest $record): bool => Filament::getCurrentPanel()?->getId() !== 'employee'
                        && $record->status === AttendanceRequestStatus::Pending)
                    ->action(function (AttendanceRequest $record): void {
                        $record->update([
                            'status' => AttendanceRequestStatus::Approved,
                            'approved_by' => auth()->id(),
                        ]);
                    }),

                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (AttendanceRequest $record): bool => Filament::getCurrentPanel()?->getId() !== 'employee'
                        && $record->status === AttendanceRequestStatus::Pending)
                    ->action(function (AttendanceRequest $record): void {
                        $record->update([
                            'status' => AttendanceRequestStatus::Rejected,
                        ]);
                    }),

                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (AttendanceRequest $record): bool => Filament::getCurrentPanel()?->getId() !== 'employee'
                        || $record->status === AttendanceRequestStatus::Pending),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->hidden(fn (): bool => Filament::getCurrentPanel()?->getId() === 'employee'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return Filament::getCurrentPanel()?->getId() === 'employee'
            ? $query->whereBelongsTo(auth()->user())
            : $query;
    }

    public static function canEdit(Model $record): bool
    {
        return parent::canEdit($record)
            && (Filament::getCurrentPanel()?->getId() !== 'employee'
                || ($record instanceof AttendanceRequest
                    && $record->user_id === auth()->id()
                    && $record->status === AttendanceRequestStatus::Pending));
    }

    public static function canDelete(Model $record): bool
    {
        return Filament::getCurrentPanel()?->getId() !== 'employee' && parent::canDelete($record);
    }

    public static function canDeleteAny(): bool
    {
        return Filament::getCurrentPanel()?->getId() !== 'employee' && parent::canDeleteAny();
    }

    public static function getRelations(): array
    {
        return [];
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
