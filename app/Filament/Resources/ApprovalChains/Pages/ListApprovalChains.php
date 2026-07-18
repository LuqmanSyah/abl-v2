<?php

namespace App\Filament\Resources\ApprovalChains\Pages;

use App\Filament\Resources\ApprovalChains\ApprovalChainResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListApprovalChains extends ListRecords
{
    protected static string $resource = ApprovalChainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
