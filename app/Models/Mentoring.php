<?php

namespace App\Models;

use App\Enums\MentoringStatus;
use App\Enums\UserRole;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mentoring extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'manager_id', 'status', 'topic', 'target', 'requested_at', 'scheduled_at',
        'manager_notes', 'result', 'follow_up', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => MentoringStatus::class,
            'requested_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $mentoring): void {
            $mentoring->status ??= MentoringStatus::Pending;

            if ($mentoring->status === MentoringStatus::Pending
                && (! $mentoring->requested_at || $mentoring->requested_at->isPast())) {
                throw new DomainException('Jadwal mentoring yang diajukan tidak boleh lampau.');
            }

            if (auth()->user()?->role === UserRole::Employee && $mentoring->employee_id !== auth()->id()) {
                throw new DomainException('Pegawai hanya dapat mengajukan mentoring untuk dirinya sendiri.');
            }

            if (User::whereKey($mentoring->employee_id)->where('role', UserRole::Employee)->where('manager_id', $mentoring->manager_id)->doesntExist()
                || User::whereKey($mentoring->manager_id)->where('role', UserRole::Manager)->doesntExist()) {
                throw new DomainException('Mentoring harus diajukan kepada Atasan langsung.');
            }
        });
        static::created(fn (self $mentoring) => ActivityLog::record('mentoring.requested', $mentoring));
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function approve(User $manager, mixed $scheduledAt, ?string $notes = null): void
    {
        $this->guardManager($manager, MentoringStatus::Pending);
        $scheduledAt = CarbonImmutable::parse($scheduledAt);
        if ($scheduledAt->isPast()) {
            throw new DomainException('Jadwal mentoring yang disetujui tidak boleh lampau.');
        }
        $this->update([
            'status' => MentoringStatus::Approved,
            'scheduled_at' => $scheduledAt,
            'manager_notes' => $notes,
        ]);
        ActivityLog::record('mentoring.approved', $this, $manager);
    }

    public function reject(User $manager, string $notes): void
    {
        $this->guardManager($manager, MentoringStatus::Pending);
        $this->update(['status' => MentoringStatus::Rejected, 'manager_notes' => $notes]);
        ActivityLog::record('mentoring.rejected', $this, $manager);
    }

    public function complete(User $manager, string $result, string $followUp): void
    {
        $this->guardManager($manager, MentoringStatus::Approved);
        $this->update([
            'status' => MentoringStatus::Completed,
            'result' => $result,
            'follow_up' => $followUp,
            'completed_at' => now(),
        ]);
        ActivityLog::record('mentoring.completed', $this, $manager);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            UserRole::Employee => $query->where('employee_id', $user->id),
            UserRole::Manager => $query->where('manager_id', $user->id),
            UserRole::Hr => $query,
        };
    }

    private function guardManager(User $manager, MentoringStatus $status): void
    {
        if ($manager->role !== UserRole::Manager || $this->manager_id !== $manager->id || $this->status !== $status) {
            throw new DomainException('Mentoring tidak dapat diproses pengguna ini.');
        }
    }
}
