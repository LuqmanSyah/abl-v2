<?php

namespace App\Models;

use DomainException;
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
                throw new DomainException('Indikator KPI pada periode terpublikasi tidak dapat diubah.');
            }

            $used = self::where('review_period_id', $indicator->review_period_id)
                ->when($indicator->exists, fn ($query) => $query->whereKeyNot($indicator->id))
                ->sum('weight');
            if ($used + $indicator->weight > 100) {
                throw new DomainException('Total bobot indikator KPI tidak boleh melebihi 100%.');
            }
        });

        static::deleting(function (self $indicator): void {
            if (MeritResult::where('review_period_id', $indicator->review_period_id)->whereNotNull('published_at')->exists()) {
                throw new DomainException('Indikator KPI pada periode terpublikasi tidak dapat dihapus.');
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
