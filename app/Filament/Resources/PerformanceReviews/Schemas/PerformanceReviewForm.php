<?php

namespace App\Filament\Resources\PerformanceReviews\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class PerformanceReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('review_period_id')
                    ->label('Periode')
                    ->relationship('reviewPeriod', 'name', fn (Builder $query) => $query
                        ->where('is_active', true)
                        ->whereDate('starts_at', '<=', today())
                        ->whereDate('ends_at', '>=', today())
                        ->whereDoesntHave('meritResults', fn (Builder $query) => $query->whereNotNull('published_at')))
                    ->searchable()->preload()
                    ->required(),
                Hidden::make('reviewer_id'),
                Select::make('reviewee_id')
                    ->label('Pegawai yang dinilai')
                    ->relationship('reviewee', 'name', function (Builder $query): Builder {
                        $user = auth()->user();
                        if ($user->role === UserRole::Manager) {
                            return $query->where('manager_id', $user->id);
                        }

                        return $query->whereKeyNot($user->id)->where(function (Builder $query) use ($user): void {
                            $query->whereKey($user->manager_id)->orWhere('unit_id', $user->unit_id);
                        });
                    })
                    ->searchable()->preload()
                    ->helperText(fn (): ?string => auth()->user()->role === UserRole::Employee
                        ? 'Penilaian kepada Atasan menjadi umpan balik kualitatif HR dan tidak masuk skor merit Pegawai.'
                        : null)
                    ->required(),
                Hidden::make('type'),
                TextInput::make('score')
                    ->label('Nilai (1–5)')
                    ->required()
                    ->numeric()->minValue(1)->maxValue(5),
                Textarea::make('comments')
                    ->label('Komentar')
                    ->columnSpanFull(),
                Hidden::make('submitted_at'),
            ]);
    }
}
