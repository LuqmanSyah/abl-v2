<?php

namespace App\Filament\Resources\ApprovalChains\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ApprovalChainForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('module')
                    ->label('Modul')
                    ->unique(ignoreRecord: true)
                    ->helperText('training_request, mentoring, atau modul lain')
                    ->required(),
                TextInput::make('name')
                    ->label('Nama rantai')
                    ->required(),
                Repeater::make('steps')
                    ->label('Langkah persetujuan')
                    ->addActionLabel('Tambah langkah')
                    ->reorderable()
                    ->schema([
                        Select::make('role')
                            ->label('Peran')
                            ->options([
                                UserRole::Manager->value => 'Atasan (Manager)',
                                UserRole::Hr->value => 'HR',
                            ])
                            ->required(),
                        TextInput::make('label')
                            ->label('Label')
                            ->placeholder('Persetujuan Atasan')
                            ->required(),
                    ])
                    ->default([
                        ['role' => 'manager', 'label' => 'Persetujuan Atasan'],
                        ['role' => 'hr', 'label' => 'Verifikasi HR'],
                    ])
                    ->required()
                    ->itemLabel(fn (array $state): ?string => isset($state['label']) ? $state['label'] : null),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }
}
