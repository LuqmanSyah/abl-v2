<?php

namespace App\Filament\Resources\ReviewPeriods\Pages;

use App\Filament\Resources\ReviewPeriods\ReviewPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReviewPeriods extends ListRecords
{
    protected static string $resource = ReviewPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
