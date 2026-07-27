<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Notifications\MeritScorePublished;
use App\Services\MeritScoreService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class PerformanceReview extends Model
{
    private bool $statusTransitionAuthorized = false;

    protected static function booted(): void
    {
        static::saving(function (self $review): void {
            $previous = $review->exists
                ? ReviewStatus::from($review->getRawOriginal('status'))
                : null;

            if ($previous === ReviewStatus::Locked && $review->isDirty()) {
                throw new BusinessRuleException('Rapor terkunci tidak dapat diubah.');
            }

            $review->status ??= ReviewStatus::Draft;

            if (! $review->exists && $review->status !== ReviewStatus::Draft) {
                throw new BusinessRuleException('Review baru wajib dimulai dari status draft.');
            }

            if (! $review->exists) {
                $kpis = Kpi::query()->get(['weight']);

                if ($kpis->isEmpty() || round((float) $kpis->sum('weight'), 2) !== 100.0) {
                    throw new BusinessRuleException('Total bobot master KPI harus 100 sebelum membuat review.');
                }
            }

            if ($previous !== ReviewStatus::Locked) {
                $managerId = User::query()
                    ->whereKey($review->user_id)
                    ->whereHas('manager', fn (Builder $query) => $query
                        ->where('role', UserRole::Manager)
                        ->where('status', true))
                    ->value('manager_id');

                if (! $managerId) {
                    throw new BusinessRuleException('Review wajib memiliki atasan langsung aktif sebagai reviewer.');
                }

                $review->reviewer_id = $managerId;
            }

            if ($review->exists && $review->isDirty('status')) {
                $next = match ($previous) {
                    ReviewStatus::Draft => ReviewStatus::Submitted,
                    ReviewStatus::Submitted => ReviewStatus::Approved,
                    ReviewStatus::Approved => ReviewStatus::Locked,
                    ReviewStatus::Locked => null,
                };

                if ($review->status !== $next) {
                    throw new BusinessRuleException("Transisi review dari {$previous->value} ke {$review->status->value} tidak diizinkan.");
                }
            }

            if (in_array($review->status, [ReviewStatus::Approved, ReviewStatus::Locked], true)
                && ($review->attendance_score === null
                    || $review->manager_kpi_score === null
                    || $review->final_merit_score === null
                    || $review->grade === null)) {
                throw new BusinessRuleException('Review published wajib memiliki skor merit dan grade lengkap.');
            }

            if (! $review->exists || ! $review->isDirty('status')) {
                return;
            }

            if (! $review->statusTransitionAuthorized) {
                throw new BusinessRuleException('Status review hanya dapat diubah melalui aksi workflow.');
            }

            if ($review->status === ReviewStatus::Submitted) {
                $details = $review->reviewKpiDetails()->get(['manager_score', 'weight']);

                if ($details->isEmpty() || $details->contains(fn (ReviewKpiDetail $detail): bool => $detail->manager_score === null)) {
                    throw new BusinessRuleException('Semua nilai KPI manager wajib diisi sebelum rapor disubmit.');
                }

                if (round((float) $details->sum('weight'), 2) !== 100.0) {
                    throw new BusinessRuleException('Total bobot KPI harus 100 sebelum rapor disubmit.');
                }
            }
        });

        static::created(function (self $review): void {
            Kpi::query()
                ->get(['id', 'weight'])
                ->each(fn (Kpi $kpi) => $review->reviewKpiDetails()->firstOrCreate(
                    ['kpi_id' => $kpi->id],
                    ['weight' => $kpi->weight],
                ));
        });

        static::updated(function (self $review): void {
            if ($review->wasChanged('status') && $review->status === ReviewStatus::Approved) {
                $review->user->notify(new MeritScorePublished($review));
            }
        });
    }

    public function submit(User $actor): self
    {
        return $this->transitionTo($actor, ReviewStatus::Submitted);
    }

    public function approve(User $actor): self
    {
        return $this->transitionTo($actor, ReviewStatus::Approved);
    }

    public function lock(User $actor): self
    {
        return $this->transitionTo($actor, ReviewStatus::Locked);
    }

    private function transitionTo(User $actor, ReviewStatus $status): self
    {
        DB::transaction(function () use ($actor, $status): void {
            $review = self::query()->lockForUpdate()->findOrFail($this->getKey());
            $users = User::query()
                ->whereKey([$review->user_id, $actor->getKey()])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $employee = $users->get($review->user_id);
            $freshActor = $users->get($actor->getKey());

            if (! $employee || ! $freshActor) {
                throw (new ModelNotFoundException)->setModel(User::class);
            }

            $review->ensureCanTransition($freshActor, $employee, $status);

            if ($status === ReviewStatus::Approved) {
                app(MeritScoreService::class)->calculate($review);
            }

            $review->statusTransitionAuthorized = true;

            try {
                $review->update(['status' => $status]);
            } finally {
                $review->statusTransitionAuthorized = false;
            }
        });

        return $this->refresh();
    }

    private function ensureCanTransition(User $actor, User $employee, ReviewStatus $status): void
    {
        $allowed = match ($status) {
            ReviewStatus::Submitted => $this->status === ReviewStatus::Draft
                && $actor->status
                && $actor->role === UserRole::Manager
                && $employee->manager_id === $actor->id,
            ReviewStatus::Approved => $this->status === ReviewStatus::Submitted
                && $actor->status
                && $actor->role === UserRole::HrAdmin,
            ReviewStatus::Locked => $this->status === ReviewStatus::Approved
                && $actor->status
                && $actor->role === UserRole::Director,
            ReviewStatus::Draft => false,
        };

        if (! $allowed) {
            throw new BusinessRuleException(match ($status) {
                ReviewStatus::Submitted => 'Hanya Manager langsung aktif yang dapat submit review draft.',
                ReviewStatus::Approved => 'Hanya HR aktif yang dapat menyetujui review submitted.',
                ReviewStatus::Locked => 'Hanya Director aktif yang dapat mengunci review approved.',
                ReviewStatus::Draft => 'Review tidak dapat dikembalikan ke draft.',
            });
        }
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

    public function scopePublished(Builder $query): void
    {
        $query->whereIn('status', [ReviewStatus::Approved, ReviewStatus::Locked]);
    }

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
