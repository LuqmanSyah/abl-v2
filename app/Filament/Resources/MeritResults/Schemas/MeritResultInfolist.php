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
                    ->description('Skor akhir dan simulasi bonus pegawai. Simulasi tidak terhubung ke payroll.')
                    ->icon('heroicon-o-trophy')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('reviewPeriod.name')
                            ->label('Periode'),
                        TextEntry::make('employee.name')
                            ->label('Pegawai'),
                        TextEntry::make('total_score')
                            ->label('Total skor merit')
                            ->numeric()
                            ->color(fn ($state): string => match (true) {
                                $state >= 80 => 'success',
                                $state >= 60 => 'warning',
                                default => 'danger',
                            }),
                        TextEntry::make('estimated_bonus')
                            ->label('Simulasi bonus')
                            ->money('IDR')
                            ->numeric(),
                    ])
                    ->columnSpanFull(),
                Section::make('Komponen Nilai')
                    ->description('Rincian nilai berdasarkan KPI, kepatuhan dinas, penilaian atasan, dan umpan balik rekan.')
                    ->icon('heroicon-o-chart-bar')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('kpi_score')
                            ->label('Nilai KPI')
                            ->numeric()
                            ->suffix(fn ($record): string => ' × '.($record->reviewPeriod->kpi_weight ?? 0).'%'),
                        TextEntry::make('discipline_score')
                            ->label('Nilai kepatuhan dinas')
                            ->numeric()
                            ->suffix(fn ($record): string => ' × '.($record->reviewPeriod->discipline_weight ?? 0).'%'),
                        TextEntry::make('manager_score')
                            ->label('Nilai atasan')
                            ->numeric()
                            ->suffix(fn ($record): string => ' × '.($record->reviewPeriod->manager_weight ?? 0).'%'),
                        TextEntry::make('review_360_score')
                            ->label('Nilai umpan balik rekan')
                            ->numeric()
                            ->suffix(fn ($record): string => ' × '.($record->reviewPeriod->review_360_weight ?? 0).'%'),
                    ])
                    ->columnSpanFull(),
                Section::make('Status verifikasi')
                    ->description('Riwayat persetujuan berjenjang dari Atasan dan HR.')
                    ->icon('heroicon-o-check-badge')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('managerVerifier.name')
                            ->label('Verifikator Atasan')
                            ->placeholder('-'),
                        TextEntry::make('manager_verified_at')
                            ->label('Waktu verifikasi Atasan')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('hrVerifier.name')
                            ->label('Verifikator HR')
                            ->placeholder('-'),
                        TextEntry::make('hr_verified_at')
                            ->label('Waktu verifikasi HR')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('published_at')
                            ->label('Waktu publikasi')
                            ->dateTime()
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Riwayat')
                    ->icon('heroicon-o-clock')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('calculated_at')
                            ->label('Terakhir di-update')
                            ->dateTime()
                            ->placeholder('-'),
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
