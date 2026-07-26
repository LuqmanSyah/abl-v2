<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\AttendanceRequestResource;
use App\Filament\Resources\AttendanceResource;
use App\Filament\Resources\BranchOfficeResource;
use App\Filament\Resources\DailyAttendanceSummaryResource;
use App\Filament\Resources\DepartmentResource;
use App\Filament\Resources\IndividualDevelopmentPlanResource;
use App\Filament\Resources\LeaveRequestResource;
use App\Filament\Resources\PerformanceReviewResource;
use App\Filament\Resources\PositionResource;
use App\Filament\Resources\PromotionResource;
use App\Filament\Resources\ReviewKpiDetailResource;
use App\Filament\Resources\SkillResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserSkillResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PanelArchitectureTest extends TestCase
{
    public function test_employee_panel_is_registered_with_employee_resources(): void
    {
        $panel = Filament::getPanel('employee');

        $this->assertSame('app', $panel->getPath());
        $this->assertTrue($panel->hasSpaMode());
        $this->assertEqualsCanonicalizing([
            AttendanceResource::class,
            AttendanceRequestResource::class,
            LeaveRequestResource::class,
            PerformanceReviewResource::class,
            ReviewKpiDetailResource::class,
            IndividualDevelopmentPlanResource::class,
            UserSkillResource::class,
        ], $panel->getResources());
    }

    public function test_panel_access_matches_role_matrix(): void
    {
        $employeePanel = Filament::getPanel('employee');
        $adminPanel = Filament::getPanel('admin');

        foreach (UserRole::cases() as $role) {
            $user = User::factory()->make(['role' => $role]);

            $this->assertSame(
                in_array($role, [UserRole::Employee, UserRole::Manager], true),
                $user->canAccessPanel($employeePanel),
            );
            $this->assertSame(
                $role !== UserRole::Employee,
                $user->canAccessPanel($adminPanel),
            );
        }
    }

    public function test_employee_resource_queries_are_scoped_to_authenticated_user(): void
    {
        $user = User::factory()->make()->forceFill(['id' => 42]);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('employee'));

        foreach ([
            AttendanceResource::class,
            AttendanceRequestResource::class,
            LeaveRequestResource::class,
            PerformanceReviewResource::class,
            IndividualDevelopmentPlanResource::class,
            UserSkillResource::class,
        ] as $resource) {
            $this->assertSame([42], $resource::getEloquentQuery()->getBindings());
        }

        $this->assertFalse(UserSkillResource::canCreate());
    }

    public function test_admin_resources_follow_stakeholder_role_matrix(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->actingAs(User::factory()->make(['role' => UserRole::Manager]));
        $this->assertTrue(AttendanceRequestResource::canAccess());
        $this->assertTrue(PerformanceReviewResource::canAccess());
        $this->assertFalse(AttendanceResource::canCreate());
        $this->assertFalse(BranchOfficeResource::canAccess());
        $this->assertFalse(LeaveRequestResource::canAccess());

        $this->actingAs(User::factory()->make(['role' => UserRole::HrAdmin]));
        $this->assertTrue(BranchOfficeResource::canAccess());
        $this->assertTrue(DepartmentResource::canAccess());
        $this->assertTrue(PositionResource::canAccess());
        $this->assertTrue(SkillResource::canAccess());
        $this->assertTrue(LeaveRequestResource::canAccess());
        $this->assertTrue(PromotionResource::canAccess());
        $this->assertTrue(DailyAttendanceSummaryResource::canAccess());
        $this->assertFalse(DailyAttendanceSummaryResource::canCreate());
        $this->assertFalse(UserResource::canAccess());

        $this->actingAs(User::factory()->make(['role' => UserRole::Director]));
        $this->assertTrue(PromotionResource::canAccess());
        $this->assertTrue(DailyAttendanceSummaryResource::canAccess());
        $this->assertFalse(AttendanceResource::canAccess());

        $this->actingAs(User::factory()->make(['role' => UserRole::ItAdmin]));
        $this->assertFalse(AttendanceResource::canAccess());
        $this->assertFalse(BranchOfficeResource::canAccess());
        $this->assertTrue(UserResource::canAccess());
    }

    public function test_manager_queries_only_records_assigned_to_them(): void
    {
        $manager = User::factory()->make(['role' => UserRole::Manager])->forceFill(['id' => 42]);
        $this->actingAs($manager);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        foreach ([
            AttendanceResource::class,
            AttendanceRequestResource::class,
            PerformanceReviewResource::class,
            PromotionResource::class,
        ] as $resource) {
            $this->assertSame([42], $resource::getEloquentQuery()->getBindings());
        }
    }

    public function test_shared_login_is_the_only_panel_entry_login(): void
    {
        $this->assertTrue(Route::has('login'));
        $this->assertFalse(Route::has('filament.employee.auth.login'));
        $this->assertFalse(Route::has('filament.admin.auth.login'));
        $this->get('/app')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_only_manager_sees_panel_switcher(): void
    {
        $employeePanel = Filament::getPanel('employee');
        $adminPanel = Filament::getPanel('admin');

        $this->actingAs(User::factory()->make(['role' => UserRole::Manager]));
        $this->assertTrue($employeePanel->getUserMenuItems()['admin-panel']->isVisible());
        $this->assertTrue($adminPanel->getUserMenuItems()['employee-panel']->isVisible());

        $this->actingAs(User::factory()->make(['role' => UserRole::Employee]));
        $this->assertArrayNotHasKey('admin-panel', $employeePanel->getUserMenuItems());

        $this->actingAs(User::factory()->make(['role' => UserRole::HrAdmin]));
        $this->assertArrayNotHasKey('employee-panel', $adminPanel->getUserMenuItems());
    }
}
