<?php

namespace App\Filament\Resources\ApprovalChains;

use App\Enums\UserRole;
use App\Filament\Resources\ApprovalChains\Pages\CreateApprovalChain;
use App\Filament\Resources\ApprovalChains\Pages\EditApprovalChain;
use App\Filament\Resources\ApprovalChains\Pages\ListApprovalChains;
use App\Filament\Resources\ApprovalChains\Schemas\ApprovalChainForm;
use App\Filament\Resources\ApprovalChains\Tables\ApprovalChainsTable;
use App\Models\ApprovalChain;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ApprovalChainResource extends Resource
{
    protected static ?string $model = ApprovalChain::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|UnitEnum|null $navigationGroup = 'Organisasi';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'rantai persetujuan';

    protected static ?string $pluralModelLabel = 'rantai persetujuan';

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === UserRole::Hr;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ApprovalChainForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApprovalChainsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApprovalChains::route('/'),
            'create' => CreateApprovalChain::route('/create'),
            'edit' => EditApprovalChain::route('/{record}/edit'),
        ];
    }
}
