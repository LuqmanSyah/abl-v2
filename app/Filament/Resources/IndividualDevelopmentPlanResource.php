<?php

namespace App\Filament\Resources;

use App\Enums\IdpStatus;
use App\Filament\Resources\IndividualDevelopmentPlanResource\Pages;
use App\Models\IndividualDevelopmentPlan;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class IndividualDevelopmentPlanResource extends Resource
{
    protected static ?string $model = IndividualDevelopmentPlan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|UnitEnum|null $navigationGroup = 'Pembinaan Karir';

    protected static ?string $modelLabel = 'Rencana Pengembangan';

    protected static ?string $pluralModelLabel = 'Rencana Pengembangan';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->relationship('user', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->label('Karyawan'),

            Select::make('mentor_id')
                ->relationship('mentor', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->label('Mentor'),

            TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->label('Judul'),

            Textarea::make('action_plan')
                ->required()
                ->columnSpanFull()
                ->label('Rencana Aksi'),

            TextInput::make('progress_percentage')
                ->required()
                ->integer()
                ->minValue(0)
                ->maxValue(100)
                ->suffix('%')
                ->default(0)
                ->label('Progress'),

            DatePicker::make('target_completion_date')
                ->required()
                ->label('Target Selesai'),

            Select::make('status')
                ->options(IdpStatus::class)
                ->default(IdpStatus::Active->value)
                ->required()
                ->label('Status'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Karyawan'),

                TextColumn::make('mentor.name')
                    ->searchable()
                    ->label('Mentor'),

                TextColumn::make('title')
                    ->searchable()
                    ->label('Judul'),

                TextColumn::make('progress_percentage')
                    ->numeric()
                    ->suffix('%')
                    ->sortable()
                    ->label('Progress'),

                TextColumn::make('target_completion_date')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Target'),

                TextColumn::make('status')
                    ->badge()
                    ->label('Status'),
            ])
            ->defaultSort('target_completion_date')
            ->filters([
                SelectFilter::make('status')
                    ->options(IdpStatus::class)
                    ->label('Status'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIndividualDevelopmentPlans::route('/'),
        ];
    }
}
