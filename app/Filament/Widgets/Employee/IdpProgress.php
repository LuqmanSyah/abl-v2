<?php

namespace App\Filament\Widgets\Employee;

use App\Enums\IdpStatus;
use App\Models\IndividualDevelopmentPlan;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class IdpProgress extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Progress IDP')
            ->query(IndividualDevelopmentPlan::query()
                ->where('user_id', auth()->id())
                ->where('status', IdpStatus::Active))
            ->columns([
                TextColumn::make('title')->label('Program'),
                TextColumn::make('progress_percentage')->suffix('%')->label('Progress'),
                TextColumn::make('target_completion_date')->date('d M Y')->label('Target'),
            ])
            ->paginated(false);
    }
}
