<?php

namespace App\Filament\Widgets;

use App\Enums\TrainingRequestStatus;
use App\Models\TrainingRequest;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class EmployeeTrainingMentoringTable extends TableWidget
{
    protected static ?string $heading = 'Riwayat Pengajuan Pelatihan';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TrainingRequest::with('training')
                    ->where('user_id', auth()->id())
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('training.name')
                    ->label('Pelatihan')
                    ->limit(40),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof TrainingRequestStatus ? $state->label() : (string) $state)
                    ->color(fn ($state): string => $state instanceof TrainingRequestStatus ? $state->color() : 'gray'),
                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y'),
            ])
            ->emptyStateHeading('Belum ada pengajuan')
            ->emptyStateDescription('Ajukan pelatihan baru untuk mulai')
            ->paginated(false);
    }
}
