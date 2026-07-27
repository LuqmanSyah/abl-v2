<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserSkillResource\Pages;
use App\Models\UserSkill;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class UserSkillResource extends RoleAwareResource
{
    protected static ?string $model = UserSkill::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static string|UnitEnum|null $navigationGroup = 'Pembinaan Karir';

    protected static ?string $modelLabel = 'Keahlian';

    protected static ?string $pluralModelLabel = 'Keahlian';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->relationship('user', 'name')
                ->default(fn () => Auth::id())
                ->disabled(fn (): bool => Filament::getCurrentPanel()?->getId() === 'employee')
                ->dehydrated()
                ->required()
                ->searchable()
                ->preload()
                ->label('Karyawan'),

            Select::make('skill_id')
                ->relationship('skill', 'name')
                ->scopedUnique(
                    ignoreRecord: true,
                    modifyQueryUsing: fn (Builder $query, Get $get): Builder => $query
                        ->where('user_id', $get('user_id')),
                )
                ->required()
                ->searchable()
                ->preload()
                ->label('Keahlian'),

            TextInput::make('current_level')
                ->required()
                ->integer()
                ->minValue(0)
                ->maxValue(255)
                ->label('Level Saat Ini'),
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

                TextColumn::make('skill.name')
                    ->searchable()
                    ->sortable()
                    ->label('Keahlian'),

                TextColumn::make('current_level')
                    ->numeric()
                    ->sortable()
                    ->label('Level Saat Ini'),
            ])
            ->defaultSort('skill.name')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return Filament::getCurrentPanel()?->getId() === 'employee'
            ? $query->whereBelongsTo(Auth::user())
            : $query;
    }

    public static function canCreate(): bool
    {
        return Filament::getCurrentPanel()?->getId() !== 'employee' && parent::canCreate();
    }

    public static function canEdit(Model $record): bool
    {
        return Filament::getCurrentPanel()?->getId() !== 'employee' && parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        return Filament::getCurrentPanel()?->getId() !== 'employee' && parent::canDelete($record);
    }

    public static function canDeleteAny(): bool
    {
        return Filament::getCurrentPanel()?->getId() !== 'employee' && parent::canDeleteAny();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserSkills::route('/'),
        ];
    }
}
