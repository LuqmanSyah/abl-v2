<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class DevelopmentRequest extends Model
{
    use HasFactory;

    public const TYPE_TRAINING = 'training';

    public const TYPE_MENTORING = 'mentoring';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'employee_id',
        'manager_id',
        'type',
        'title',
        'reason',
        'scheduled_at',
    ];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $request): void {
            $request->status = self::STATUS_PENDING;

            if (! in_array($request->type, [self::TYPE_TRAINING, self::TYPE_MENTORING], true)
                || User::whereKey($request->employee_id)
                    ->where('role', UserRole::Employee)
                    ->where('manager_id', $request->manager_id)
                    ->where('is_active', true)
                    ->doesntExist()
                || User::whereKey($request->manager_id)
                    ->where('role', UserRole::Manager)
                    ->where('is_active', true)
                    ->doesntExist()) {
                throw new BusinessRuleException('Pengajuan pengembangan tidak valid.');
            }

            if (auth()->user()?->role === UserRole::Employee && $request->employee_id !== auth()->id()) {
                throw new BusinessRuleException('Pegawai hanya dapat mengajukan untuk dirinya sendiri.');
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function approve(User $manager, ?string $notes = null): void
    {
        $this->transition($manager, self::STATUS_APPROVED, $notes);
    }

    public function reject(User $manager, string $notes): void
    {
        if (blank($notes)) {
            throw new BusinessRuleException('Alasan penolakan wajib diisi.');
        }

        $this->transition($manager, self::STATUS_REJECTED, $notes);
    }

    public function complete(User $actor, ?string $notes = null): void
    {
        $this->transition($actor, self::STATUS_COMPLETED, $notes);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            UserRole::Employee => $query->where('employee_id', $user->id),
            UserRole::Manager => $query->where('manager_id', $user->id),
            UserRole::Hr => $query,
        };
    }

    private function transition(User $actor, string $target, ?string $notes): void
    {
        DB::transaction(function () use ($actor, $target, $notes): void {
            $request = self::query()->lockForUpdate()->findOrFail($this->id);
            $managerAction = $actor->role === UserRole::Manager && $request->manager_id === $actor->id;
            $valid = match ($target) {
                self::STATUS_APPROVED, self::STATUS_REJECTED => $request->status === self::STATUS_PENDING && $managerAction,
                self::STATUS_COMPLETED => $request->status === self::STATUS_APPROVED
                    && ($managerAction || $actor->role === UserRole::Hr),
                default => false,
            };

            if (! $valid) {
                throw new BusinessRuleException('Status pengajuan tidak dapat diubah pengguna ini.');
            }

            $request->forceFill(['status' => $target, 'manager_notes' => $notes])->save();
            ActivityLog::record("development.{$target}", $request, $actor);
            $this->setRawAttributes($request->getAttributes(), true);
        }, 3);
    }
}
