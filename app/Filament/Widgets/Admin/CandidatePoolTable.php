<?php

namespace App\Filament\Widgets\Admin;

use App\Enums\UserRole;
use App\Models\Promotion;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class CandidatePoolTable extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && in_array($user->role, [UserRole::HrAdmin, UserRole::Director], true);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Candidate Pool 30 Hari')
            ->query(Promotion::query()->candidatePool()->with(['user', 'fromPosition', 'toPosition']))
            ->columns([
                TextColumn::make('user.name')->searchable()->label('Karyawan'),
                TextColumn::make('fromPosition.title')->label('Posisi Asal'),
                TextColumn::make('toPosition.title')->label('Posisi Target'),
                TextColumn::make('readiness_score')->suffix('%')->label('Readiness'),
                TextColumn::make('created_at')->date('d M Y')->label('Diusulkan'),
            ])
            ->paginated(false);
    }
}
