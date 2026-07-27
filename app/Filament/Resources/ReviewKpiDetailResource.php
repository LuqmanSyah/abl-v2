<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Models\ReviewKpiDetail;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ReviewKpiDetailResource extends RoleAwareResource
{
    protected static ?string $model = ReviewKpiDetail::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return $schema->components(static::formComponents());
    }

    /**
     * @return array<Component>
     */
    public static function formComponents(): array
    {
        return [
            Select::make('kpi_id')
                ->relationship('kpi', 'name')
                ->disabled()
                ->required()
                ->label('KPI'),

            TextInput::make('weight')
                ->disabled()
                ->suffix('%')
                ->label('Bobot'),

            TextInput::make('self_score')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->disabled(fn (): bool => Filament::getCurrentPanel()?->getId() !== 'employee')
                ->label('Nilai Diri'),

            Textarea::make('self_notes')
                ->maxLength(1000)
                ->disabled(fn (): bool => Filament::getCurrentPanel()?->getId() !== 'employee')
                ->label('Catatan Diri'),

            TextInput::make('manager_score')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->disabled(fn (): bool => ! static::isManager())
                ->label('Nilai Manager'),

            Textarea::make('manager_notes')
                ->maxLength(1000)
                ->disabled(fn (): bool => ! static::isManager())
                ->label('Catatan Manager'),

            TextInput::make('subtotal_score')
                ->disabled()
                ->suffix(' poin')
                ->label('Subtotal'),
        ];
    }

    private static function isManager(): bool
    {
        $user = Auth::user();

        return Filament::getCurrentPanel()?->getId() === 'admin'
            && $user instanceof User
            && $user->role === UserRole::Manager;
    }
}
