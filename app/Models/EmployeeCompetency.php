<?php

namespace App\Models;

use App\Enums\CompetencyLevel;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCompetency extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'competency_id', 'level', 'assessed_at', 'notes'];

    protected function casts(): array
    {
        return ['assessed_at' => 'date'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $competency): void {
            if (! CompetencyLevel::tryFrom((int) $competency->level)) {
                throw new BusinessRuleException('Level kompetensi Pegawai harus 1 sampai 5.');
            }
            if ($competency->assessed_at?->isFuture()) {
                throw new BusinessRuleException('Tanggal penilaian kompetensi tidak boleh di masa depan.');
            }
            if (User::whereKey($competency->user_id)->where('role', UserRole::Employee)->doesntExist()) {
                throw new BusinessRuleException('Kompetensi hanya dapat dicatat untuk Pegawai.');
            }
        });

        static::created(fn (self $competency) => ActivityLog::record('competency.created', $competency, data: [
            'values' => $competency->only($competency->getFillable()),
        ]));

        static::updated(function (self $competency): void {
            $changes = collect($competency->getChanges())
                ->except('updated_at')
                ->mapWithKeys(fn (mixed $value, string $field): array => [
                    $field => ['old' => $competency->getRawOriginal($field), 'new' => $value],
                ])
                ->all();

            if ($changes) {
                ActivityLog::record('competency.updated', $competency, data: ['changes' => $changes]);
            }
        });

        static::deleted(fn (self $competency) => ActivityLog::record('competency.deleted', $competency, data: [
            'values' => $competency->only($competency->getFillable()),
        ]));
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            UserRole::Employee => $query->where('user_id', $user->id),
            UserRole::Manager => $query->whereHas('employee', fn (Builder $query) => $query->where('manager_id', $user->id)),
            UserRole::Hr => $query,
        };
    }
}
