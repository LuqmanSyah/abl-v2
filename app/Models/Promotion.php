<?php

namespace App\Models;

use App\Enums\PromotionStatus;
use App\Enums\UserRole;
use App\Notifications\PromotionApproved;
use App\Notifications\PromotionProposed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Notification;

class Promotion extends Model
{
    protected static function booted(): void
    {
        static::created(function (self $promotion): void {
            if ($promotion->status === PromotionStatus::Proposed) {
                Notification::send(
                    User::query()
                        ->where('role', UserRole::HrAdmin)
                        ->where('status', true)
                        ->get(),
                    new PromotionProposed($promotion),
                );
            }
        });

        static::updated(function (self $promotion): void {
            if ($promotion->wasChanged('status')
                && $promotion->status === PromotionStatus::ApprovedByDirector) {
                $promotion->user->notify(new PromotionApproved($promotion));
            }
        });
    }

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
