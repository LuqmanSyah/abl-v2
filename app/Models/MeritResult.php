<?php

namespace App\Models;

use App\Enums\UserRole;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class MeritResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_period_id', 'employee_id', 'kpi_score', 'discipline_score', 'manager_score',
        'review_360_score', 'total_score', 'estimated_bonus', 'manager_verified_by',
        'manager_verified_at', 'hr_verified_by', 'hr_verified_at', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'kpi_score' => 'decimal:2', 'discipline_score' => 'decimal:2', 'manager_score' => 'decimal:2',
            'review_360_score' => 'decimal:2', 'total_score' => 'decimal:2', 'estimated_bonus' => 'decimal:2',
            'manager_verified_at' => 'datetime', 'hr_verified_at' => 'datetime', 'published_at' => 'datetime',
        ];
    }

    public function reviewPeriod(): BelongsTo
    {
        return $this->belongsTo(ReviewPeriod::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function verifyByManager(User $manager): void
    {
        DB::transaction(function () use ($manager): void {
            $result = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($result->employee->manager_id !== $manager->id || $manager->role !== UserRole::Manager
                || $result->manager_verified_at || $result->published_at) {
                throw new DomainException('Hasil merit tidak dapat diverifikasi pengguna ini.');
            }

            $result->update(['manager_verified_by' => $manager->id, 'manager_verified_at' => now()]);
            ActivityLog::record('merit.manager_verified', $result, $manager);
            $this->setRawAttributes($result->getAttributes(), true);
        }, 3);
    }

    public function verifyByHr(User $hr): void
    {
        DB::transaction(function () use ($hr): void {
            $result = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($hr->role !== UserRole::Hr || ! $result->manager_verified_at || $result->published_at) {
                throw new DomainException('Verifikasi Atasan wajib selesai sebelum verifikasi HR.');
            }

            $result->update(['hr_verified_by' => $hr->id, 'hr_verified_at' => now(), 'published_at' => now()]);
            ActivityLog::record('merit.hr_published', $result, $hr);
            $this->setRawAttributes($result->getAttributes(), true);
        }, 3);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            UserRole::Employee => $query->where('employee_id', $user->id)->whereNotNull('published_at'),
            UserRole::Manager => $query->whereHas('employee', fn (Builder $query) => $query->where('manager_id', $user->id)),
            UserRole::Hr => $query,
        };
    }
}
