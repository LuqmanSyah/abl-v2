<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeKpi extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_period_id',
        'employee_id',
        'manager_id',
        'name',
        'target',
        'achievement',
        'notes',
    ];

    protected function casts(): array
    {
        return ['target' => 'decimal:2', 'achievement' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $kpi): void {
            $periodIds = array_filter([
                $kpi->review_period_id,
                $kpi->exists ? $kpi->getRawOriginal('review_period_id') : null,
            ]);

            if (ReviewPeriod::whereKey($periodIds)->whereNotNull('published_at')->exists()) {
                throw new BusinessRuleException('KPI pada periode terpublikasi tidak dapat diubah.');
            }

            if ((float) $kpi->target <= 0 || (float) $kpi->achievement < 0) {
                throw new BusinessRuleException('Target harus lebih dari 0 dan capaian tidak boleh negatif.');
            }

            if (User::whereKey($kpi->employee_id)
                ->where('role', UserRole::Employee)
                ->where('manager_id', $kpi->manager_id)
                ->where('is_active', true)
                ->doesntExist()) {
                throw new BusinessRuleException('Pegawai bukan bawahan aktif Atasan terpilih.');
            }
        });

        static::deleting(function (self $kpi): void {
            if ($kpi->reviewPeriod()->whereNotNull('published_at')->exists()) {
                throw new BusinessRuleException('KPI pada periode terpublikasi tidak dapat dihapus.');
            }
        });
    }

    public function reviewPeriod(): BelongsTo
    {
        return $this->belongsTo(ReviewPeriod::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            UserRole::Employee => $query->where('employee_id', $user->id),
            UserRole::Manager => $query->where('manager_id', $user->id),
            UserRole::Hr => $query,
        };
    }
}
