<?php

namespace App\Filament\Resources\PerformanceReviews\Schemas;

use App\Enums\ReviewType;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PerformanceReviewInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi penilaian')
                    ->description('Pihak yang terlibat dan waktu pengiriman penilaian.')
                    ->icon('heroicon-o-user-group')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('reviewPeriod.name')
                            ->label('Periode'),
                        TextEntry::make('type')
                            ->label('Jenis penilaian')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof ReviewType ? $state->label() : (string) $state),
                        TextEntry::make('reviewer.name')
                            ->label('Penilai'),
                        TextEntry::make('reviewee.name')
                            ->label('Pegawai yang dinilai'),
                        TextEntry::make('submitted_at')
                            ->label('Dikirim pada')
                            ->dateTime()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Hasil penilaian')
                    ->icon('heroicon-o-star')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('score')
                            ->label('Nilai')
                            ->numeric()
                            ->suffix('/5'),
                        TextEntry::make('comments')
                            ->label('Komentar')
                            ->placeholder('Tidak ada komentar.')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
