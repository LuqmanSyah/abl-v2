<?php

namespace App\Filament\Resources\MeritResults\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MeritResultInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ringkasan merit')
                    ->description('Hasil akhir perhitungan merit pegawai.')
                    ->icon('heroicon-o-trophy')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('reviewPeriod.name')
                            ->label('Periode'),
                        TextEntry::make('employee.name')
                            ->label('Pegawai'),
                        TextEntry::make('total_score')
                            ->label('Skor merit')
                            ->numeric(),
                        TextEntry::make('estimated_bonus')
                            ->label('Estimasi bonus')
                            ->money('IDR'),
                    ])
                    ->columnSpanFull(),
                Section::make('Komponen penilaian')
                    ->description('Nilai pembentuk skor merit sebelum bobot periode diterapkan.')
                    ->icon('heroicon-o-chart-pie')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('kpi_score')
                            ->label('Nilai KPI')
                            ->numeric(),
                        TextEntry::make('discipline_score')
                            ->label('Nilai kedisiplinan')
                            ->numeric(),
                        TextEntry::make('manager_score')
                            ->label('Nilai Atasan')
                            ->numeric(),
                        TextEntry::make('review_360_score')
                            ->label('Nilai umpan balik kinerja')
                            ->numeric(),
                    ])
                    ->columnSpanFull(),
                Section::make('Status verifikasi')
                    ->description('Tahapan pemeriksaan sebelum hasil dapat dilihat Pegawai.')
                    ->icon('heroicon-o-check-badge')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('managerVerifier.name')
                            ->label('Verifikator Atasan')
                            ->placeholder('-'),
                        TextEntry::make('manager_verified_at')
                            ->label('Diverifikasi Atasan')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('hrVerifier.name')
                            ->label('Verifikator HR')
                            ->placeholder('-'),
                        TextEntry::make('hr_verified_at')
                            ->label('Diverifikasi HR')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('published_at')
                            ->label('Dipublikasikan')
                            ->dateTime()
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Riwayat data')
                    ->icon('heroicon-o-clock')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat pada')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label('Diperbarui pada')
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),
            ]);
    }
}
