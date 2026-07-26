<?php

namespace App\Models;

use App\Enums\PromotionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    public function scopeCandidatePool(Builder $query): void
    {
        $query
            ->where('status', PromotionStatus::Proposed)
            ->where('created_at', '>=', now()->subDays(30));
    }

    protected $fillable = [
        'user_id',
        'from_position_id',
        'to_position_id',
        'proposed_by',
        'readiness_score',
        'status',
        'effective_date',
    ];

    protected $casts = [
        'readiness_score' => 'decimal:2',
        'status' => PromotionStatus::class,
        'effective_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function fromPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'from_position_id');
    }

    public function toPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'to_position_id');
    }
}
