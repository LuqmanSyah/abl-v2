<?php

namespace Tests\Feature;

use App\Enums\AttendanceRequestStatus;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\FlowType;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Enums\PromotionStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\LeaveRequest;
use App\Models\PerformanceReview;
use App\Models\Position;
use App\Models\Promotion;
use App\Models\User;
use App\Notifications\AttendanceRequestApproved;
use App\Notifications\AttendanceRequestAssigned;
use App\Notifications\CheckOutExceptionPending;
use App\Notifications\LeaveRequestApproved;
use App\Notifications\LeaveRequestRejected;
use App\Notifications\MeritScorePublished;
use App\Notifications\PromotionApproved;
use App\Notifications\PromotionProposed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WorkflowNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_blueprint_workflows_send_database_and_web_push_notifications(): void
    {
        $this->seed();
        Notification::fake();

        $employee = User::query()->where('role', UserRole::Employee)->firstOrFail();
        $manager = $employee->manager;
        $hr = User::query()->where('role', UserRole::HrAdmin)->firstOrFail();

        $assigned = $this->attendanceRequest($employee, $manager, FlowType::TopDown, '2027-01-01');
        $approved = $this->attendanceRequest($employee, $employee, FlowType::BottomUp, '2027-01-02');
        $approved->update([
            'status' => AttendanceRequestStatus::Approved,
            'approved_by' => $manager->id,
        ]);

        $leaveApproved = $this->leaveRequest($employee, '2027-01-03');
        $leaveApproved->update([
            'status' => LeaveStatus::Approved,
            'approved_by' => $hr->id,
            'approved_at' => now(),
        ]);
        $leaveRejected = $this->leaveRequest($employee, '2027-01-04');
        $leaveRejected->update(['status' => LeaveStatus::Rejected]);

        $attendance = Attendance::create([
            'user_id' => $employee->id,
            'type' => AttendanceType::CheckOut,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'distance_to_target_meters' => 500,
            'address_snapshot' => 'Lokasi klien',
            'photo_path' => 'attendance/out.jpg',
            'is_radius_exception' => true,
            'exception_reason' => 'Selesai di lokasi klien.',
            'status' => AttendanceStatus::PendingVerification,
            'recorded_at' => '2027-01-05 17:00:00',
        ]);

        $review = PerformanceReview::create([
            'user_id' => $employee->id,
            'reviewer_id' => $manager->id,
            'period' => '2027-H1',
            'start_date' => '2027-01-01',
            'end_date' => '2027-06-30',
            'status' => ReviewStatus::Draft,
        ]);
        $review->update(['status' => ReviewStatus::Approved]);

        $targetPosition = Position::query()->whereKeyNot($employee->position_id)->firstOrFail();
        $promotion = Promotion::create([
            'user_id' => $employee->id,
            'from_position_id' => $employee->position_id,
            'to_position_id' => $targetPosition->id,
            'proposed_by' => $manager->id,
            'readiness_score' => 90,
            'status' => PromotionStatus::Proposed,
        ]);
        $promotion->update(['status' => PromotionStatus::ApprovedByDirector]);

        Notification::assertSentTo($employee, AttendanceRequestAssigned::class);
        Notification::assertSentTo($employee, AttendanceRequestApproved::class);
        Notification::assertSentTo($employee, LeaveRequestApproved::class);
        Notification::assertSentTo($employee, LeaveRequestRejected::class);
        Notification::assertSentTo($manager, CheckOutExceptionPending::class);
        Notification::assertSentTo($hr, CheckOutExceptionPending::class);
        Notification::assertSentTo($employee, MeritScorePublished::class);
        Notification::assertSentTo($hr, PromotionProposed::class);
        Notification::assertSentTo($employee, PromotionApproved::class);

        $notification = new AttendanceRequestAssigned($assigned);
        $this->assertSame(['database', 'webpush'], $notification->via($employee));
        $this->assertSame('Tugas Luar Baru', $notification->toDatabase($employee)['title']);
        $this->assertTrue(method_exists($employee, 'updatePushSubscription'));
        $this->assertSame(AttendanceStatus::PendingVerification, $attendance->status);
    }

    private function attendanceRequest(User $employee, User $creator, FlowType $flow, string $date): AttendanceRequest
    {
        return AttendanceRequest::create([
            'user_id' => $employee->id,
            'created_by' => $creator->id,
            'flow_type' => $flow,
            'destination_name' => 'Kantor Klien',
            'destination_address' => 'Jakarta',
            'target_latitude' => -6.1754,
            'target_longitude' => 106.8272,
            'allowed_radius_meters' => 100,
            'duty_start_datetime' => "{$date} 08:00:00",
            'duty_end_datetime' => "{$date} 17:00:00",
            'reason' => 'Pertemuan klien',
            'status' => AttendanceRequestStatus::Pending,
        ]);
    }

    private function leaveRequest(User $employee, string $date): LeaveRequest
    {
        return LeaveRequest::create([
            'user_id' => $employee->id,
            'type' => LeaveType::PaidLeave,
            'start_date' => $date,
            'end_date' => $date,
            'reason' => 'Keperluan keluarga',
            'status' => LeaveStatus::Pending,
        ]);
    }
}
