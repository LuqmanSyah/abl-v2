<?php

namespace App\Filament\Resources\PerformanceReviews\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PerformanceReviewInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('reviewPeriod.name')
                    ->label('Review period'),
                TextEntry::make('reviewer.name')
                    ->label('Reviewer'),
                TextEntry::make('reviewee.name')
                    ->label('Reviewee'),
                TextEntry::make('type')
                    ->badge(),
                TextEntry::make('score')
                    ->numeric(),
                TextEntry::make('comments')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('submitted_at')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
