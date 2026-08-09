<?php

namespace App\Filament\Widgets;

use App\Enums\MentoringStatus;
use App\Filament\Resources\Mentorings\MentoringResource;
use App\Models\Mentoring;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ManagerPendingApprovalsTable extends TableWidget
{
    protected static ?string $heading = 'Persetujuan Mentoring Tertunda';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Mentoring::where('manager_id', auth()->id())
                    ->where('status', MentoringStatus::Pending)
            )
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Pegawai'),
                TextColumn::make('topic')
                    ->label('Topik')
                    ->limit(50),
                TextColumn::make('requested_at')
                    ->label('Tanggal Diinginkan')
                    ->dateTime('d M Y'),
                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('approve')
                        ->label('Atur Jadwal')
                        ->icon('heroicon-o-calendar')
                        ->color('primary')
                        ->url(fn (): string => MentoringResource::getUrl('index')),
                ]),
            ])
            ->emptyStateHeading('Tidak ada permintaan mentoring')
            ->emptyStateDescription('Permintaan mentoring anggota tim akan muncul di sini')
            ->paginated(false);
    }
}
