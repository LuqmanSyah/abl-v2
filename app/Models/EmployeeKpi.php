<?php

namespace App\Models;

use App\Enums\UserRole;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeKpi extends Model
{
    use HasFactory;

    protected $fillable = ['review_period_id', 'kpi_indicator_id', 'employee_id', 'manager_id', 'target', 'achievement', 'notes'];

    protected function casts(): array
    {
        return ['target' => 'decimal:2', 'achievement' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $kpi): void {
            if (KpiIndicator::whereKey($kpi->kpi_indicator_id)->where('review_period_id', $kpi->review_period_id)->doesntExist()) {
                throw new DomainException('Indikator KPI bukan bagian periode terpilih.');
            }
            if (User::whereKey($kpi->employee_id)->where('manager_id', $kpi->manager_id)->doesntExist()) {
                throw new DomainException('Pegawai bukan bawahan Atasan terpilih.');
            }
        });
    }

    public function reviewPeriod(): BelongsTo
    {
        return $this->belongsTo(ReviewPeriod::class);
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(KpiIndicator::class, 'kpi_indicator_id');
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
