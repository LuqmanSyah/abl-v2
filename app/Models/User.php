<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            if ($user->position_id && Position::whereKey($user->position_id)->where('unit_id', $user->unit_id)->doesntExist()) {
                throw new BusinessRuleException('Jabatan harus berasal dari unit kerja yang dipilih.');
            }

            if ($user->exists && $user->subordinates()->exists()
                && (($user->isDirty('role') && $user->role !== UserRole::Manager)
                    || ($user->isDirty('is_active') && ! $user->is_active))) {
                throw new BusinessRuleException('Atasan yang masih memiliki bawahan tidak dapat dinonaktifkan atau diubah perannya.');
            }

            if (! $user->manager_id) {
                return;
            }

            if ($user->role !== UserRole::Employee || $user->manager_id === $user->id
                || self::whereKey($user->manager_id)->where('role', UserRole::Manager)->where('is_active', true)->doesntExist()) {
                throw new BusinessRuleException('Atasan langsung harus pengguna aktif dengan peran Atasan.');
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'unit_id',
        'position_id',
        'manager_id',
        'employee_number',
        'phone',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && match ($panel->getId()) {
            'employee' => $this->role === UserRole::Employee,
            'manager' => $this->role === UserRole::Manager,
            'hr' => $this->role === UserRole::Hr,
            default => false,
        };
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    public function dutyTrips(): HasMany
    {
        return $this->hasMany(DutyTrip::class, 'employee_id');
    }

    public function managedDutyTrips(): HasMany
    {
        return $this->hasMany(DutyTrip::class, 'manager_id');
    }

    public function employeeKpis(): HasMany
    {
        return $this->hasMany(EmployeeKpi::class, 'employee_id');
    }

    public function meritResults(): HasMany
    {
        return $this->hasMany(MeritResult::class, 'employee_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }

    public function competencies(): HasMany
    {
        return $this->hasMany(EmployeeCompetency::class);
    }

    public function careerGoal(): HasOne
    {
        return $this->hasOne(CareerGoal::class);
    }

    public function trainingRequests(): HasMany
    {
        return $this->hasMany(TrainingRequest::class);
    }

    public function mentorings(): HasMany
    {
        return $this->hasMany(Mentoring::class, 'employee_id');
    }
}
