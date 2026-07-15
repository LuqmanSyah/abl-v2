<?php

namespace App\Filament\Resources\ReviewPeriods\Pages;

use App\Filament\Resources\ReviewPeriods\ReviewPeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReviewPeriod extends EditRecord
{
    protected static string $resource = ReviewPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
