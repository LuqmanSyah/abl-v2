<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Services\CareerGapService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerGoal extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'target_position_id'];

    protected static function booted(): void
    {
        static::saving(function (self $goal): void {
            if (auth()->user()?->role === UserRole::Employee && $goal->user_id !== auth()->id()) {
                throw new DomainException('Pegawai hanya dapat mengubah target karier sendiri.');
            }

            $employee = User::with('position')->find($goal->user_id);
            $target = Position::find($goal->target_position_id);

            if ($employee?->role !== UserRole::Employee || ! $employee->position || ! $target || $target->level <= $employee->position->level) {
                throw new DomainException('Jabatan tujuan harus lebih tinggi dari jabatan Pegawai saat ini.');
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function targetPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'target_position_id');
    }

    public function getGapSummaryAttribute(): string
    {
        return app(CareerGapService::class)->summary($this);
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
