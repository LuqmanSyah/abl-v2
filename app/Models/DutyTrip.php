<?php

namespace App\Models;

use App\Enums\DutyTripStatus;
use App\Enums\UserRole;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DutyTrip extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'manager_id', 'duty_location_id', 'destination', 'purpose',
        'starts_at', 'ends_at', 'location_name', 'address', 'latitude', 'longitude',
        'radius_meters', 'supporting_document_path', 'status', 'rejection_reason', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime', 'ends_at' => 'datetime', 'approved_at' => 'datetime',
            'latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'status' => DutyTripStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $trip): void {
            $locked = [
                'duty_location_id', 'location_name', 'address', 'latitude', 'longitude', 'radius_meters',
            ];

            if (in_array($trip->getRawOriginal('status'), [DutyTripStatus::Approved->value, DutyTripStatus::Completed->value], true)
                && $trip->isDirty($locked)) {
                throw new DomainException('Lokasi dinas yang disetujui tidak dapat diubah.');
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

    public function dutyLocation(): BelongsTo
    {
        return $this->belongsTo(DutyLocation::class);
    }

    public function attendance(): HasOne
    {
        return $this->hasOne(Attendance::class);
    }

    public function approve(User $manager): void
    {
        if ($this->status !== DutyTripStatus::Pending || $this->manager_id !== $manager->id) {
            throw new DomainException('Pengajuan tidak dapat disetujui oleh pengguna ini.');
        }

        $this->update(['status' => DutyTripStatus::Approved, 'approved_at' => now(), 'rejection_reason' => null]);
    }

    public function reject(User $manager, string $reason): void
    {
        if ($this->status !== DutyTripStatus::Pending || $this->manager_id !== $manager->id) {
            throw new DomainException('Pengajuan tidak dapat ditolak oleh pengguna ini.');
        }

        $this->update(['status' => DutyTripStatus::Rejected, 'rejection_reason' => $reason]);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            UserRole::Employee => $query->where('employee_id', $user->id),
            UserRole::Manager => $query->where('manager_id', $user->id),
            UserRole::Hr => $query,
        };
    }
}
