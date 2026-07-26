<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;

abstract class RoleAwareResource extends Resource
{
    public static function canAccess(): bool
    {
        if (! parent::canAccess()) {
            return false;
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        return match (Filament::getCurrentPanel()?->getId() ?? 'admin') {
            'employee' => in_array($user->role, [UserRole::Employee, UserRole::Manager], true),
            'admin' => in_array($user->role, static::adminRoles(), true),
            default => false,
        };
    }

    /**
     * @return list<UserRole>
     */
    private static function adminRoles(): array
    {
        return match (static::class) {
            AttendanceResource::class,
            AttendanceRequestResource::class => [UserRole::Manager, UserRole::HrAdmin],

            PerformanceReviewResource::class,
            ReviewKpiDetailResource::class,
            PromotionResource::class => [UserRole::Manager, UserRole::HrAdmin, UserRole::Director],

            BranchOfficeResource::class,
            CareerPathResource::class,
            DepartmentResource::class,
            HolidayResource::class,
            IndividualDevelopmentPlanResource::class,
            KpiResource::class,
            LeaveRequestResource::class,
            PositionResource::class,
            SkillResource::class,
            UserSkillResource::class,
            WorkScheduleResource::class => [UserRole::HrAdmin],

            DailyAttendanceSummaryResource::class => [UserRole::HrAdmin, UserRole::Director],

            UserResource::class => [UserRole::ItAdmin],

            default => [],
        };
    }
}
