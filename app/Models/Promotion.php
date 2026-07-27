<?php

namespace App\Models;

use App\Enums\PromotionStatus;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Notifications\PromotionApproved;
use App\Notifications\PromotionAwaitingDirectorApproval;
use App\Notifications\PromotionProposed;
use App\Notifications\PromotionRejected;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class Promotion extends Model
{
    private bool $staleExpirationAuthorized = false;

    protected static function booted(): void
    {
        static::saving(function (self $promotion): void {
            $promotion->status ??= PromotionStatus::Proposed;

            if (! $promotion->exists && $promotion->status !== PromotionStatus::Proposed) {
                throw new BusinessRuleException('Promosi baru wajib dimulai dari status proposed.');
            }

            if ($promotion->exists && $promotion->isDirty('status')) {
                $previous = PromotionStatus::from($promotion->getRawOriginal('status'));
                $allowed = match ($previous) {
                    PromotionStatus::Proposed => [
                        PromotionStatus::ApprovedByHr,
                        PromotionStatus::Rejected,
                        PromotionStatus::Expired,
                    ],
                    PromotionStatus::ApprovedByHr => [
                        PromotionStatus::ApprovedByDirector,
                        PromotionStatus::Rejected,
                    ],
                    PromotionStatus::ApprovedByDirector => $promotion->staleExpirationAuthorized
                        ? [PromotionStatus::Expired]
                        : [],
                    PromotionStatus::Rejected,
                    PromotionStatus::Expired => [],
                };

                if (! in_array($promotion->status, $allowed, true)) {
                    throw new BusinessRuleException("Transisi promosi dari {$previous->value} ke {$promotion->status->value} tidak diizinkan.");
                }
            }

            if ($promotion->status === PromotionStatus::ApprovedByDirector && ! $promotion->effective_date) {
                throw new BusinessRuleException('Tanggal efektif wajib diisi sebelum persetujuan Director.');
            }

            $promotion->active_lifecycle = $promotion->applied_at === null
                && in_array($promotion->status, [
                    PromotionStatus::Proposed,
                    PromotionStatus::ApprovedByHr,
                    PromotionStatus::ApprovedByDirector,
                ], true)
                ? true
                : null;

            if ($promotion->active_lifecycle
                && (! $promotion->exists || $promotion->isDirty(['user_id', 'to_position_id', 'status', 'applied_at']))
                && self::query()
                    ->activeLifecycle()
                    ->where('user_id', $promotion->user_id)
                    ->where('to_position_id', $promotion->to_position_id)
                    ->when($promotion->exists, fn (Builder $query) => $query->whereKeyNot($promotion))
                    ->exists()) {
                throw new BusinessRuleException('Proposal aktif untuk karyawan dan posisi tujuan tersebut sudah ada.');
            }
        });

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
            if (! $promotion->wasChanged('status')) {
                return;
            }

            if ($promotion->status === PromotionStatus::ApprovedByHr) {
                Notification::send(
                    User::query()
                        ->where('role', UserRole::Director)
                        ->where('status', true)
                        ->get(),
                    new PromotionAwaitingDirectorApproval($promotion),
                );

                return;
            }

            if ($promotion->status === PromotionStatus::ApprovedByDirector) {
                $promotion->applyIfDue();

                if ($promotion->status !== PromotionStatus::ApprovedByDirector) {
                    return;
                }

                Notification::send(
                    collect([$promotion->user, $promotion->proposer])->unique('id'),
                    new PromotionApproved($promotion),
                );

                return;
            }

            if ($promotion->status === PromotionStatus::Rejected) {
                Notification::send(
                    collect([$promotion->user, $promotion->proposer])->unique('id'),
                    new PromotionRejected($promotion),
                );
            }
        });
    }

    public function scopeCandidatePool(Builder $query): void
    {
        $query
            ->where('status', PromotionStatus::Proposed)
            ->where('created_at', '>=', now()->subDays(30));
    }

    public function scopeActiveLifecycle(Builder $query): void
    {
        $query->where('active_lifecycle', true);
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
        'applied_at' => 'datetime',
        'active_lifecycle' => 'boolean',
    ];

    /** @param array<string, mixed> $attributes */
    public function transitionTo(PromotionStatus $status, array $attributes = []): self
    {
        DB::transaction(function () use ($attributes, $status): void {
            self::query()
                ->lockForUpdate()
                ->findOrFail($this->getKey())
                ->forceFill([
                    ...$attributes,
                    'status' => $status,
                ])
                ->save();
        });

        return $this->refresh();
    }

    public function applyIfDue(): bool
    {
        $applied = DB::transaction(function (): bool {
            $promotion = self::query()->lockForUpdate()->findOrFail($this->getKey());

            if ($promotion->status !== PromotionStatus::ApprovedByDirector
                || ! $promotion->effective_date
                || $promotion->effective_date->isFuture()
                || $promotion->applied_at) {
                return false;
            }

            $user = User::query()
                ->lockForUpdate()
                ->findOrFail($promotion->user_id);

            if ((int) $user->position_id !== (int) $promotion->from_position_id) {
                $promotion->staleExpirationAuthorized = true;

                try {
                    $promotion->update(['status' => PromotionStatus::Expired]);
                } finally {
                    $promotion->staleExpirationAuthorized = false;
                }

                return false;
            }

            $user->update(['position_id' => $promotion->to_position_id]);

            $promotion->forceFill([
                'applied_at' => now(),
                'active_lifecycle' => null,
            ])->saveQuietly();

            return true;
        });

        $this->refresh();

        return $applied;
    }

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
