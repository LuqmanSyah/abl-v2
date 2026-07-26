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
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, Notifiable;

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            if ($user->exists && $user->subordinates()->exists()
                && (($user->isDirty('role') && $user->role !== UserRole::Manager)
                    || ($user->isDirty('status') && ! $user->status))) {
                throw new BusinessRuleException('Manager yang masih memiliki bawahan tidak dapat dinonaktifkan atau diubah perannya.');
            }

            if (! $user->manager_id) {
                return;
            }

            if ($user->role !== UserRole::Employee || $user->manager_id === $user->id
                || self::query()
                    ->whereKey($user->manager_id)
                    ->where('role', UserRole::Manager->value)
                    ->where('status', true)
                    ->doesntExist()) {
                throw new BusinessRuleException('Atasan langsung harus pengguna aktif dengan peran Manager.');
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nip',
        'name',
        'email',
        'password',
        'position_id',
        'work_schedule_id',
        'branch_office_id',
        'manager_id',
        'join_date',
        'status',
        'role',
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

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'join_date' => 'date',
        'status' => 'boolean',
        'role' => UserRole::class,
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->status) {
            return false;
        }

        return match ($panel->getId()) {
            'employee' => in_array($this->role, [UserRole::Employee, UserRole::Manager], true),
            'admin' => $this->role !== UserRole::Employee,
            default => false,
        };
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    public function branchOffice(): BelongsTo
    {
        return $this->belongsTo(BranchOffice::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    public function userSkills(): HasMany
    {
        return $this->hasMany(UserSkill::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function attendanceRequests(): HasMany
    {
        return $this->hasMany(AttendanceRequest::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function dailyAttendanceSummaries(): HasMany
    {
        return $this->hasMany(DailyAttendanceSummary::class);
    }

    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class);
    }

    public function individualDevelopmentPlans(): HasMany
    {
        return $this->hasMany(IndividualDevelopmentPlan::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }
}
