<?php

namespace App\Models;

use App\Enums\DutyTripStatus;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DutyTrip extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'manager_id',
        'location_name',
        'address',
        'latitude',
        'longitude',
        'radius_meters',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'status' => DutyTripStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $trip): void {
            $validManager = User::whereKey($trip->manager_id)
                ->where('role', UserRole::Manager)
                ->where('is_active', true)
                ->exists();
            $validEmployee = User::whereKey($trip->employee_id)
                ->where('role', UserRole::Employee)
                ->where('manager_id', $trip->manager_id)
                ->where('is_active', true)
                ->exists();

            if (! $validManager || ! $validEmployee) {
                throw new BusinessRuleException('Pegawai harus merupakan bawahan aktif Atasan pemberi tugas.');
            }

            if ($trip->ends_at->lte($trip->starts_at)) {
                throw new BusinessRuleException('Tanggal selesai tidak boleh sebelum tanggal mulai.');
            }

            if ($trip->exists && $trip->attendances()->exists() && $trip->isDirty([
                'employee_id',
                'manager_id',
                'location_name',
                'address',
                'latitude',
                'longitude',
                'radius_meters',
                'starts_at',
                'ends_at',
            ])) {
                throw new BusinessRuleException('Dinas yang sudah memiliki absensi tidak dapat diubah.');
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

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function canBeChangedBy(User $manager): bool
    {
        return $manager->role === UserRole::Manager
            && $this->manager_id === $manager->id
            && $this->status === DutyTripStatus::Active
            && $this->starts_at->isFuture()
            && ! $this->attendances()->exists();
    }

    public function cancel(User $manager): void
    {
        if (! $this->canBeChangedBy($manager)) {
            throw new BusinessRuleException('Perintah dinas tidak dapat dibatalkan pengguna ini.');
        }

        $this->update(['status' => DutyTripStatus::Cancelled]);
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
