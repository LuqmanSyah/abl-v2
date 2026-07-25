<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceReview extends Model
{
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
