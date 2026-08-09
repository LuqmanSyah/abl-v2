<?php

namespace App\Models;

use App\Exceptions\BusinessRuleException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiIndicator extends Model
{
    use HasFactory;

    protected $fillable = ['review_period_id', 'name', 'description', 'unit', 'weight'];

    protected static function booted(): void
    {
        static::saving(function (self $indicator): void {
            $periodIds = array_unique(array_filter([
                $indicator->review_period_id,
                $indicator->exists ? $indicator->getRawOriginal('review_period_id') : null,
            ]));

            if (MeritResult::whereIn('review_period_id', $periodIds)->whereNotNull('published_at')->exists()) {
                throw new BusinessRuleException('Indikator KPI pada periode terpublikasi tidak dapat diubah.');
            }
            if (ReviewPeriod::whereIn('id', $periodIds)->whereDate('ends_at', '<', today())->exists()) {
                throw new BusinessRuleException('Indikator KPI pada periode yang telah selesai tidak dapat diubah.');
            }
            if ($indicator->exists && $indicator->isDirty('review_period_id') && $indicator->employeeKpis()->exists()) {
                throw new BusinessRuleException('Periode indikator KPI yang sudah digunakan tidak dapat diubah.');
            }

            $used = self::where('review_period_id', $indicator->review_period_id)
                ->when($indicator->exists, fn ($query) => $query->whereKeyNot($indicator->id))
                ->sum('weight');
            if ($used + $indicator->weight > 100) {
                throw new BusinessRuleException('Total bobot indikator KPI tidak boleh melebihi 100%.');
            }
        });

        static::deleting(function (self $indicator): void {
            if (MeritResult::where('review_period_id', $indicator->review_period_id)->whereNotNull('published_at')->exists()) {
                throw new BusinessRuleException('Indikator KPI pada periode terpublikasi tidak dapat dihapus.');
            }
            if ($indicator->reviewPeriod->hasEnded()) {
                throw new BusinessRuleException('Indikator KPI pada periode yang telah selesai tidak dapat dihapus.');
            }
            if ($indicator->employeeKpis()->exists()) {
                throw new BusinessRuleException('Indikator KPI yang sudah digunakan tidak dapat dihapus.');
            }
        });
    }

    public function reviewPeriod(): BelongsTo
    {
        return $this->belongsTo(ReviewPeriod::class);
    }

    public function employeeKpis(): HasMany
    {
        return $this->hasMany(EmployeeKpi::class);
    }
}
