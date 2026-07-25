<?php

namespace App\Filament\Resources;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Filament\Resources\LeaveRequestResource\Pages;
use App\Models\LeaveRequest;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string | UnitEnum | null $navigationGroup = 'Kehadiran';

    protected static ?string $modelLabel = 'Pengajuan Cuti';

    protected static ?string $pluralModelLabel = 'Pengajuan Cuti';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->relationship('user', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->label('Karyawan'),

            Select::make('type')
                ->options(LeaveType::class)
                ->required()
                ->label('Jenis Cuti'),

            DatePicker::make('start_date')
                ->required()
                ->label('Tanggal Mulai'),

            DatePicker::make('end_date')
                ->required()
                ->afterOrEqual('start_date')
                ->label('Tanggal Selesai'),

            Textarea::make('reason')
                ->required()
                ->maxLength(500)
                ->label('Alasan'),

            Select::make('status')
                ->options(LeaveStatus::class)
                ->default(LeaveStatus::Pending->value)
                ->required()
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

                TextColumn::make('type')
                    ->badge()
                    ->label('Jenis'),

                TextColumn::make('start_date')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Mulai'),

                TextColumn::make('end_date')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Selesai'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (LeaveStatus $state) => match ($state) {
                        LeaveStatus::Pending => 'warning',
                        LeaveStatus::Approved => 'success',
                        LeaveStatus::Rejected => 'danger',
                    })
                    ->label('Status'),

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
                    ->options(LeaveStatus::class)
                    ->label('Status'),

                SelectFilter::make('type')
                    ->options(LeaveType::class)
                    ->label('Jenis'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (LeaveRequest $record) => $record->status === LeaveStatus::Pending)
                    ->action(function (LeaveRequest $record): void {
                        $record->update([
                            'status' => LeaveStatus::Approved,
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                    }),

                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (LeaveRequest $record) => $record->status === LeaveStatus::Pending)
                    ->action(function (LeaveRequest $record): void {
                        $record->update([
                            'status' => LeaveStatus::Rejected,
                        ]);
                    }),

                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'edit' => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}
