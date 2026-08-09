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

    protected $fillable = ['review_period_id', 'kpi_indicator_id', 'employee_id', 'manager_id', 'target', 'achievement', 'notes'];

    protected function casts(): array
    {
        return ['target' => 'decimal:2', 'achievement' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $kpi): void {
            $periodIds = array_unique(array_filter([
                $kpi->review_period_id,
                $kpi->exists ? $kpi->getRawOriginal('review_period_id') : null,
            ]));

            if ((! $kpi->exists || $kpi->isDirty($kpi->getFillable()))
                && MeritResult::whereIn('review_period_id', $periodIds)->whereNotNull('published_at')->exists()) {
                throw new BusinessRuleException('KPI dengan hasil merit terpublikasi tidak dapat diubah.');
            }
            if (ReviewPeriod::whereIn('id', $periodIds)->whereDate('ends_at', '<', today())->exists()) {
                throw new BusinessRuleException('KPI pada periode yang telah selesai tidak dapat diubah.');
            }
            if (ReviewPeriod::whereKey($kpi->review_period_id)->where('is_active', true)->doesntExist()) {
                throw new BusinessRuleException('Periode KPI tidak aktif.');
            }

            if ((float) $kpi->target <= 0) {
                throw new BusinessRuleException('Target KPI harus lebih dari 0.');
            }
            if ((float) $kpi->achievement < 0) {
                throw new BusinessRuleException('Capaian KPI tidak boleh negatif.');
            }
            if (KpiIndicator::whereKey($kpi->kpi_indicator_id)->where('review_period_id', $kpi->review_period_id)->doesntExist()) {
                throw new BusinessRuleException('Indikator KPI bukan bagian periode terpilih.');
            }
            if (User::whereKey($kpi->employee_id)->where('manager_id', $kpi->manager_id)->doesntExist()) {
                throw new BusinessRuleException('Pegawai bukan bawahan Atasan terpilih.');
            }
        });

        static::created(fn (self $kpi) => ActivityLog::record('kpi.created', $kpi, data: [
            'values' => $kpi->only($kpi->getFillable()),
        ]));

        static::updated(function (self $kpi): void {
            $changes = collect($kpi->getChanges())
                ->except('updated_at')
                ->mapWithKeys(fn (mixed $value, string $field): array => [
                    $field => ['old' => $kpi->getRawOriginal($field), 'new' => $value],
                ])
                ->all();

            if ($changes) {
                ActivityLog::record('kpi.updated', $kpi, data: ['changes' => $changes]);
            }
        });

        static::deleting(function (self $kpi): void {
            if ($kpi->reviewPeriod->hasPublishedMeritResults()) {
                throw new BusinessRuleException('KPI dengan hasil merit terpublikasi tidak dapat dihapus.');
            }
            if ($kpi->reviewPeriod->hasEnded()) {
                throw new BusinessRuleException('KPI pada periode yang telah selesai tidak dapat dihapus.');
            }
        });

        static::deleted(fn (self $kpi) => ActivityLog::record('kpi.deleted', $kpi, data: [
            'values' => $kpi->only($kpi->getFillable()),
        ]));
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

    public function hasPublishedMeritResult(bool $original = false): bool
    {
        return MeritResult::where('review_period_id', $original ? $this->getRawOriginal('review_period_id') : $this->review_period_id)
            ->where('employee_id', $original ? $this->getRawOriginal('employee_id') : $this->employee_id)
            ->whereNotNull('published_at')
            ->exists();
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
