<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewKpiDetail extends Model
{
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
}
