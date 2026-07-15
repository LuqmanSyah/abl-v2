<?php

namespace App\Models;

use App\Enums\UserRole;
use DomainException;
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
            if ($competency->level < 1 || $competency->level > 5) {
                throw new DomainException('Level kompetensi Pegawai harus 1 sampai 5.');
            }
            if (User::whereKey($competency->user_id)->where('role', UserRole::Employee)->doesntExist()) {
                throw new DomainException('Kompetensi hanya dapat dicatat untuk Pegawai.');
            }
        });
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
