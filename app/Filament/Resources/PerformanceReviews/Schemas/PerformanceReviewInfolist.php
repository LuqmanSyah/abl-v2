<?php

namespace App\Filament\Resources\PerformanceReviews\Schemas;

use App\Enums\ReviewType;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PerformanceReviewInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('reviewPeriod.name')
                    ->label('Periode'),
                TextEntry::make('reviewer.name')
                    ->label('Penilai'),
                TextEntry::make('reviewee.name')
                    ->label('Pegawai yang dinilai'),
                TextEntry::make('type')
                    ->label('Jenis penilaian')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof ReviewType ? $state->label() : (string) $state),
                TextEntry::make('score')
                    ->label('Nilai')
                    ->numeric()
                    ->suffix('/5'),
                TextEntry::make('comments')
                    ->label('Komentar')
                    ->placeholder('Tidak ada komentar.')
                    ->columnSpanFull(),
                TextEntry::make('submitted_at')
                    ->label('Dikirim pada')
                    ->dateTime(),
            ]);
    }
}
