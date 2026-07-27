<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Exceptions\BusinessRuleException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewKpiDetail extends Model
{
    protected static function booted(): void
    {
        static::saving(function (self $detail): void {
            self::guardDraft($detail);
            $detail->weight ??= $detail->kpi()->value('weight');
            $detail->subtotal_score = $detail->manager_score === null
                ? null
                : round((float) $detail->manager_score * (float) $detail->weight / 100, 2);
        });

        static::deleting(function (self $detail): void {
            self::guardDraft($detail);
        });
    }

    protected $fillable = [
        'performance_review_id',
        'kpi_id',
        'self_score',
        'self_notes',
        'manager_score',
        'manager_notes',
        'weight',
        'subtotal_score',
    ];

    protected $casts = [
        'self_score' => 'decimal:2',
        'manager_score' => 'decimal:2',
        'weight' => 'decimal:2',
        'subtotal_score' => 'decimal:2',
    ];

    public function performanceReview(): BelongsTo
    {
        return $this->belongsTo(PerformanceReview::class);
    }

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(Kpi::class);
    }

    private static function guardDraft(self $detail): void
    {
        $status = PerformanceReview::query()
            ->whereKey($detail->performance_review_id)
            ->value('status');

        if ($status !== ReviewStatus::Draft && $status !== ReviewStatus::Draft->value) {
            throw new BusinessRuleException('Detail KPI hanya dapat diubah saat review berstatus draft.');
        }
    }
}
