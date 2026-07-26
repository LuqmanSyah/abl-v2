<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Exceptions\BusinessRuleException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceReview extends Model
{
    protected static function booted(): void
    {
        static::saving(function (self $review): void {
            if ($review->status !== ReviewStatus::Submitted || ! $review->isDirty('status')) {
                return;
            }

            $details = $review->reviewKpiDetails()->get(['manager_score', 'weight']);

            if ($details->isEmpty() || $details->contains(fn (ReviewKpiDetail $detail): bool => $detail->manager_score === null)) {
                throw new BusinessRuleException('Semua nilai KPI manager wajib diisi sebelum rapor disubmit.');
            }

            if (round((float) $details->sum('weight'), 2) !== 100.0) {
                throw new BusinessRuleException('Total bobot KPI harus 100 sebelum rapor disubmit.');
            }
        });

        static::created(function (self $review): void {
            Kpi::query()
                ->get(['id', 'weight'])
                ->each(fn (Kpi $kpi) => $review->reviewKpiDetails()->create([
                    'kpi_id' => $kpi->id,
                    'weight' => $kpi->weight,
                ]));
        });
    }

    protected $fillable = [
        'user_id',
        'reviewer_id',
        'period',
        'start_date',
        'end_date',
        'attendance_score',
        'manager_kpi_score',
        'final_merit_score',
        'grade',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'attendance_score' => 'decimal:2',
        'manager_kpi_score' => 'decimal:2',
        'final_merit_score' => 'decimal:2',
        'status' => ReviewStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewKpiDetails(): HasMany
    {
        return $this->hasMany(ReviewKpiDetail::class);
    }
}
