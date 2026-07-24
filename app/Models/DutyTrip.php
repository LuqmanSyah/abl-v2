<?php

namespace App\Models;

use App\Enums\DutyTripStatus;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Notifications\TripAssigned;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        $validateAssignment = function (self $trip): void {
            $validManager = User::whereKey($trip->manager_id)->where('role', UserRole::Manager)->exists();
            $validEmployee = User::whereKey($trip->employee_id)
                ->where('role', UserRole::Employee)
                ->where('manager_id', $trip->manager_id)
                ->exists();

            if (! $validManager || ! $validEmployee) {
                throw new BusinessRuleException('Pegawai harus merupakan bawahan langsung Atasan pemberi tugas.');
            }
        };

        static::creating($validateAssignment);
        static::created(function (self $trip): void {
            ActivityLog::record('duty_trip.assigned', $trip);
            $trip->employee->notify(new TripAssigned($trip));
        });

        static::updating(function (self $trip) use ($validateAssignment): void {
            if ($trip->isDirty(['employee_id', 'manager_id'])) {
                $validateAssignment($trip);
            }

            $locked = [
                'duty_location_id', 'location_name', 'address', 'latitude', 'longitude', 'radius_meters',
            ];

            if ($trip->attendances()->exists()
                && $trip->isDirty($locked)) {
                throw new BusinessRuleException('Lokasi dinas yang telah selesai tidak dapat diubah.');
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

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function canBeChangedBy(User $manager): bool
    {
        return $manager->role === UserRole::Manager
            && $this->manager_id === $manager->id
            && $this->status === DutyTripStatus::Approved
            && $this->starts_at->isFuture()
            && ! $this->attendances()->whereDate('captured_at', today())->exists();
    }

    public function cancel(User $manager): void
    {
        if (! $this->canBeChangedBy($manager)) {
            throw new BusinessRuleException('Perintah dinas tidak dapat dibatalkan pengguna ini.');
        }

        $this->update(['status' => DutyTripStatus::Cancelled]);
        ActivityLog::record('duty_trip.cancelled', $this, $manager);
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
