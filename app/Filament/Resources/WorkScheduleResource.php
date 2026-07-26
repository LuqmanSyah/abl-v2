<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkScheduleResource\Pages;
use App\Models\WorkSchedule;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class WorkScheduleResource extends RoleAwareResource
{
    protected static ?string $model = WorkSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $modelLabel = 'Jadwal Kerja';

    protected static ?string $pluralModelLabel = 'Jadwal Kerja';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->label('Nama'),

            TimePicker::make('check_in_time')
                ->required()
                ->seconds(false)
                ->label('Jam Masuk'),

            TimePicker::make('check_out_time')
                ->required()
                ->seconds(false)
                ->label('Jam Pulang'),

            TextInput::make('late_tolerance_minutes')
                ->required()
                ->integer()
                ->minValue(0)
                ->suffix('menit')
                ->label('Toleransi Terlambat'),

            TextInput::make('alfa_cutoff_minutes')
                ->required()
                ->integer()
                ->minValue(1)
                ->suffix('menit')
                ->label('Batas Alfa'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Nama'),

                TextColumn::make('check_in_time')
                    ->time('H:i')
                    ->label('Jam Masuk'),

                TextColumn::make('check_out_time')
                    ->time('H:i')
                    ->label('Jam Pulang'),

                TextColumn::make('late_tolerance_minutes')
                    ->numeric()
                    ->suffix(' menit')
                    ->label('Toleransi Terlambat'),

                TextColumn::make('alfa_cutoff_minutes')
                    ->numeric()
                    ->suffix(' menit')
                    ->label('Batas Alfa'),
            ])
            ->defaultSort('name')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkSchedules::route('/'),
        ];
    }
}
