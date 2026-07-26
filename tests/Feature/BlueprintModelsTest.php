<?php

namespace Tests\Feature;

use App\Enums\AttendanceRequestStatus;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\DailySummaryStatus;
use App\Enums\FlowType;
use App\Enums\IdpStatus;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Enums\PromotionStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\BranchOffice;
use App\Models\CareerPath;
use App\Models\DailyAttendanceSummary;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\IndividualDevelopmentPlan;
use App\Models\Kpi;
use App\Models\LeaveRequest;
use App\Models\PerformanceReview;
use App\Models\Position;
use App\Models\PositionSkill;
use App\Models\Promotion;
use App\Models\ReviewKpiDetail;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserSkill;
use App\Models\WorkSchedule;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlueprintModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_blueprint_models_cast_attributes_and_resolve_relationships(): void
    {
        $department = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $position = Position::create(['department_id' => $department->id, 'title' => 'Engineer', 'level' => 1]);
        $nextPosition = Position::create(['department_id' => $department->id, 'title' => 'Senior Engineer', 'level' => 2]);
        $schedule = WorkSchedule::create([
            'name' => 'Regular',
            'check_in_time' => '08:00',
            'check_out_time' => '17:00',
            'late_tolerance_minutes' => 15,
            'alfa_cutoff_minutes' => 60,
        ]);
        $branch = BranchOffice::create([
            'name' => 'Jakarta',
            'code' => 'JKT',
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'allowed_radius_meters' => 100,
        ]);
        $skill = Skill::create(['name' => 'Laravel', 'category' => 'Technical']);
        $positionSkill = PositionSkill::create([
            'position_id' => $position->id,
            'skill_id' => $skill->id,
            'min_required_level' => 3,
        ]);
        $manager = User::factory()->create([
            'position_id' => $nextPosition->id,
            'work_schedule_id' => $schedule->id,
            'branch_office_id' => $branch->id,
            'role' => UserRole::Manager,
        ]);
        $employee = User::factory()->create([
            'position_id' => $position->id,
            'work_schedule_id' => $schedule->id,
            'branch_office_id' => $branch->id,
            'manager_id' => $manager->id,
        ]);
        $userSkill = UserSkill::create(['user_id' => $employee->id, 'skill_id' => $skill->id, 'current_level' => 2]);
        $holiday = Holiday::create(['name' => 'New Year', 'date' => '2026-01-01']);
        $leave = LeaveRequest::create([
            'user_id' => $employee->id,
            'type' => LeaveType::PaidLeave,
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-04',
            'reason' => 'Family',
            'status' => LeaveStatus::Approved,
            'approved_by' => $manager->id,
            'approved_at' => now(),
        ]);
        $request = AttendanceRequest::create([
            'user_id' => $employee->id,
            'created_by' => $manager->id,
            'flow_type' => FlowType::TopDown,
            'destination_name' => 'Client Office',
            'destination_address' => 'Jakarta',
            'target_latitude' => -6.1754,
            'target_longitude' => 106.8272,
            'allowed_radius_meters' => 100,
            'duty_start_datetime' => '2026-08-01 08:00:00',
            'duty_end_datetime' => '2026-08-01 17:00:00',
            'reason' => 'Meeting',
            'status' => AttendanceRequestStatus::Approved,
            'approved_by' => $manager->id,
        ]);
        $checkIn = Attendance::create([
            'user_id' => $employee->id,
            'attendance_request_id' => $request->id,
            'type' => AttendanceType::CheckIn,
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'distance_to_target_meters' => 10,
            'address_snapshot' => 'Client Office',
            'photo_path' => 'attendance/check-in.jpg',
            'status' => AttendanceStatus::Normal,
            'recorded_at' => '2026-08-01 08:00:00',
        ]);
        $checkOut = Attendance::create([
            ...$checkIn->only([
                'user_id',
                'attendance_request_id',
                'latitude',
                'longitude',
                'distance_to_target_meters',
                'address_snapshot',
            ]),
            'type' => AttendanceType::CheckOut,
            'photo_path' => 'attendance/check-out.jpg',
            'status' => AttendanceStatus::Normal,
            'recorded_at' => '2026-08-01 17:00:00',
        ]);
        $summary = DailyAttendanceSummary::updateOrCreate(
            ['user_id' => $employee->id, 'date' => '2026-08-01 00:00:00'],
            [
                'attendance_request_id' => $request->id,
                'check_in_id' => $checkIn->id,
                'check_out_id' => $checkOut->id,
                'status' => DailySummaryStatus::Present,
                'late_minutes' => 0,
            ],
        );
        $kpi = Kpi::create(['name' => 'Delivery', 'category' => 'Performance', 'weight' => 100]);
        $review = PerformanceReview::create([
            'user_id' => $employee->id,
            'reviewer_id' => $manager->id,
            'period' => '2026-H2',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'attendance_score' => 100,
            'manager_kpi_score' => 90,
            'final_merit_score' => 95,
            'grade' => 'A',
            'status' => ReviewStatus::Approved,
        ]);
        $detail = ReviewKpiDetail::create([
            'performance_review_id' => $review->id,
            'kpi_id' => $kpi->id,
            'self_score' => 90,
            'manager_score' => 90,
            'weight' => 100,
            'subtotal_score' => 90,
        ]);
        $careerPath = CareerPath::create([
            'current_position_id' => $position->id,
            'next_position_id' => $nextPosition->id,
            'min_experience_months' => 24,
            'min_merit_grade' => 'B',
        ]);
        $plan = IndividualDevelopmentPlan::create([
            'user_id' => $employee->id,
            'mentor_id' => $manager->id,
            'title' => 'Senior Engineer',
            'action_plan' => 'Complete advanced training',
            'progress_percentage' => 20,
            'target_completion_date' => '2026-12-31',
            'status' => IdpStatus::Active,
        ]);
        $promotion = Promotion::create([
            'user_id' => $employee->id,
            'from_position_id' => $position->id,
            'to_position_id' => $nextPosition->id,
            'proposed_by' => $manager->id,
            'readiness_score' => 85,
            'status' => PromotionStatus::Proposed,
        ]);

        $this->assertTrue($department->positions->contains($position));
        $this->assertTrue($position->department->is($department));
        $this->assertTrue($position->positionSkills->contains($positionSkill));
        $this->assertTrue($position->users->contains($employee));
        $this->assertTrue($schedule->users->contains($employee));
        $this->assertTrue($branch->users->contains($employee));
        $this->assertTrue($skill->positionSkills->contains($positionSkill));
        $this->assertTrue($skill->userSkills->contains($userSkill));
        $this->assertTrue($positionSkill->position->is($position));
        $this->assertTrue($positionSkill->skill->is($skill));
        $this->assertTrue($userSkill->user->is($employee));
        $this->assertTrue($userSkill->skill->is($skill));
        $this->assertTrue($employee->position->is($position));
        $this->assertTrue($employee->workSchedule->is($schedule));
        $this->assertTrue($employee->branchOffice->is($branch));
        $this->assertTrue($employee->manager->is($manager));
        $this->assertTrue($employee->userSkills->contains($userSkill));
        $this->assertTrue($employee->leaveRequests->contains($leave));
        $this->assertTrue($employee->attendanceRequests->contains($request));
        $this->assertTrue($employee->attendances->contains($checkIn));
        $this->assertTrue($employee->dailyAttendanceSummaries->contains($summary));
        $this->assertTrue($employee->performanceReviews->contains($review));
        $this->assertTrue($employee->individualDevelopmentPlans->contains($plan));
        $this->assertTrue($employee->promotions->contains($promotion));
        $this->assertTrue($leave->user->is($employee));
        $this->assertTrue($leave->approver->is($manager));
        $this->assertTrue($request->user->is($employee));
        $this->assertTrue($request->creator->is($manager));
        $this->assertTrue($request->approver->is($manager));
        $this->assertTrue($request->attendances->contains($checkIn));
        $this->assertTrue($request->dailyAttendanceSummaries->contains($summary));
        $this->assertTrue($checkIn->user->is($employee));
        $this->assertTrue($checkIn->attendanceRequest->is($request));
        $this->assertTrue($summary->user->is($employee));
        $this->assertTrue($summary->attendanceRequest->is($request));
        $this->assertTrue($summary->checkIn->is($checkIn));
        $this->assertTrue($summary->checkOut->is($checkOut));
        $this->assertTrue($kpi->reviewKpiDetails->contains($detail));
        $this->assertTrue($review->user->is($employee));
        $this->assertTrue($review->reviewer->is($manager));
        $this->assertTrue($review->reviewKpiDetails->contains($detail));
        $this->assertTrue($detail->performanceReview->is($review));
        $this->assertTrue($detail->kpi->is($kpi));
        $this->assertTrue($careerPath->currentPosition->is($position));
        $this->assertTrue($careerPath->nextPosition->is($nextPosition));
        $this->assertTrue($plan->user->is($employee));
        $this->assertTrue($plan->mentor->is($manager));
        $this->assertTrue($promotion->user->is($employee));
        $this->assertTrue($promotion->proposer->is($manager));
        $this->assertTrue($promotion->fromPosition->is($position));
        $this->assertTrue($promotion->toPosition->is($nextPosition));
        $this->assertSame('2026-01-01', $holiday->date->toDateString());
        $this->assertSame(UserRole::Employee, $employee->role);
        $this->assertSame(LeaveType::PaidLeave, $leave->type);
        $this->assertSame(AttendanceStatus::Normal, $checkIn->status);
        $this->assertSame(DailySummaryStatus::Present, $summary->status);
        $this->assertSame(ReviewStatus::Approved, $review->status);
        $this->assertSame(IdpStatus::Active, $plan->status);
        $this->assertSame(PromotionStatus::Proposed, $promotion->status);

        $employeePanel = Panel::make()->id('employee');
        $adminPanel = Panel::make()->id('admin');
        $this->assertTrue($employee->canAccessPanel($employeePanel));
        $this->assertFalse($employee->canAccessPanel($adminPanel));
        $this->assertTrue($manager->canAccessPanel($employeePanel));
        $this->assertTrue($manager->canAccessPanel($adminPanel));
        $this->assertFalse($employee->forceFill(['status' => false])->canAccessPanel($employeePanel));
    }

    public function test_manager_with_subordinates_cannot_be_disabled(): void
    {
        $department = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $position = Position::create(['department_id' => $department->id, 'title' => 'Engineer', 'level' => 1]);
        $schedule = WorkSchedule::create([
            'name' => 'Regular',
            'check_in_time' => '08:00',
            'check_out_time' => '17:00',
            'late_tolerance_minutes' => 15,
            'alfa_cutoff_minutes' => 60,
        ]);
        $branch = BranchOffice::create([
            'name' => 'Jakarta',
            'code' => 'JKT',
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'allowed_radius_meters' => 100,
        ]);
        $organization = [
            'position_id' => $position->id,
            'work_schedule_id' => $schedule->id,
            'branch_office_id' => $branch->id,
        ];
        $manager = User::factory()->create([...$organization, 'role' => UserRole::Manager]);
        User::factory()->create([...$organization, 'manager_id' => $manager->id]);

        $this->expectException(BusinessRuleException::class);

        $manager->update(['status' => false]);
    }
}
