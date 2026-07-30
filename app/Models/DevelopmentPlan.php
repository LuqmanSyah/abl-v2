<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevelopmentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'target',
        'current_gap',
        'recommended_action',
        'review_date',
    ];

    protected function casts(): array
    {
        return ['review_date' => 'date'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $plan): void {
            if (User::whereKey($plan->employee_id)
                ->where('role', UserRole::Employee)
                ->where('is_active', true)
                ->doesntExist()) {
                throw new BusinessRuleException('Rencana pengembangan hanya untuk Pegawai aktif.');
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            UserRole::Employee => $query->where('employee_id', $user->id),
            UserRole::Manager => $query->whereHas(
                'employee',
                fn (Builder $query) => $query->where('manager_id', $user->id),
            ),
            UserRole::Hr => $query,
        };
    }
}
