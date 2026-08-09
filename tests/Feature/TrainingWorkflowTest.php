<?php

namespace Tests\Feature;

use App\Enums\TrainingRequestStatus;
use App\Enums\UserRole;
use App\Models\Training;
use App\Models\TrainingRequest;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_request_training(): void
    {
        [$employee, $manager] = $this->users();
        $training = Training::create(['name' => 'Test Training', 'type' => 'internal', 'is_active' => true]);

        $request = TrainingRequest::create([
            'user_id' => $employee->id,
            'manager_id' => $manager->id,
            'training_id' => $training->id,
            'reason' => 'Mau belajar',
            'status' => TrainingRequestStatus::PendingManager,
        ]);

        $this->assertTrue($request->exists);
        $this->assertSame(TrainingRequestStatus::PendingManager, $request->status);
    }

    public function test_manager_can_approve_training(): void
    {
        [$employee, $manager] = $this->users();
        $training = Training::create(['name' => 'Test', 'type' => 'internal', 'is_active' => true]);

        $request = TrainingRequest::create([
            'user_id' => $employee->id,
            'manager_id' => $manager->id,
            'training_id' => $training->id,
            'reason' => 'Test',
            'status' => TrainingRequestStatus::PendingManager,
        ]);

        $request->approveByManager($manager, 'Setuju');

        $this->assertSame(TrainingRequestStatus::PendingHr, $request->fresh()->status);
        $this->assertNotNull($request->fresh()->manager_decided_at);
    }

    public function test_other_manager_cannot_approve_training(): void
    {
        [$employee, $manager] = $this->users();
        $other = User::factory()->create(['role' => UserRole::Manager]);
        $training = Training::create(['name' => 'Test', 'type' => 'internal', 'is_active' => true]);

        $request = TrainingRequest::create([
            'user_id' => $employee->id,
            'manager_id' => $manager->id,
            'training_id' => $training->id,
            'reason' => 'Test',
            'status' => TrainingRequestStatus::PendingManager,
        ]);

        $this->expectException(DomainException::class);
        $request->approveByManager($other, 'Setuju');
    }

    public function test_manager_can_reject_training(): void
    {
        [$employee, $manager] = $this->users();
        $training = Training::create(['name' => 'Test', 'type' => 'internal', 'is_active' => true]);

        $request = TrainingRequest::create([
            'user_id' => $employee->id,
            'manager_id' => $manager->id,
            'training_id' => $training->id,
            'reason' => 'Test',
            'status' => TrainingRequestStatus::PendingManager,
        ]);

        $request->rejectByManager($manager, 'Tidak sesuai');

        $this->assertSame(TrainingRequestStatus::Rejected, $request->fresh()->status);
    }

    public function test_employee_can_resubmit_after_rejection(): void
    {
        [$employee, $manager] = $this->users();
        $training = Training::create(['name' => 'Test', 'type' => 'internal', 'is_active' => true]);

        $request = TrainingRequest::create([
            'user_id' => $employee->id,
            'manager_id' => $manager->id,
            'training_id' => $training->id,
            'reason' => 'Test',
            'status' => TrainingRequestStatus::Rejected,
        ]);

        $request->resubmit($employee, 'Alasan baru');

        $this->assertSame(TrainingRequestStatus::PendingManager, $request->fresh()->status);
    }

    public function test_hr_can_verify_training(): void
    {
        [$employee, $manager] = $this->users();
        $hr = User::factory()->create(['role' => UserRole::Hr]);
        $training = Training::create(['name' => 'Test', 'type' => 'internal', 'is_active' => true]);

        $request = TrainingRequest::create([
            'user_id' => $employee->id,
            'manager_id' => $manager->id,
            'training_id' => $training->id,
            'reason' => 'Test',
            'status' => TrainingRequestStatus::PendingHr,
        ]);

        $request->verifyByHr($hr);
        $this->assertSame(TrainingRequestStatus::Approved, $request->fresh()->status);
    }

    public function test_hr_can_reject_training_and_employee_can_resubmit(): void
    {
        [$employee, $manager] = $this->users();
        $hr = User::factory()->create(['role' => UserRole::Hr]);
        $training = Training::create(['name' => 'Test', 'type' => 'internal', 'is_active' => true]);
        $request = TrainingRequest::create([
            'user_id' => $employee->id,
            'manager_id' => $manager->id,
            'training_id' => $training->id,
            'reason' => 'Test',
            'status' => TrainingRequestStatus::PendingHr,
        ]);

        $request->rejectByHr($hr, 'Anggaran belum tersedia');

        $this->assertSame(TrainingRequestStatus::Rejected, $request->fresh()->status);
        $this->assertSame('Anggaran belum tersedia', $request->fresh()->hr_notes);

        $request->resubmit($employee, 'Diajukan untuk periode berikutnya');
        $this->assertSame(TrainingRequestStatus::PendingManager, $request->fresh()->status);
        $this->assertNull($request->fresh()->hr_notes);
    }

    public function test_expired_training_cannot_be_requested(): void
    {
        [$employee, $manager] = $this->users();
        $training = Training::create([
            'name' => 'Kedaluwarsa',
            'type' => 'internal',
            'ends_at' => now()->subMinute(),
            'is_active' => true,
        ]);

        $this->assertFalse(Training::available()->whereKey($training)->exists());
        $this->expectException(DomainException::class);
        TrainingRequest::create([
            'user_id' => $employee->id,
            'manager_id' => $manager->id,
            'training_id' => $training->id,
            'reason' => 'Terlambat',
        ]);
    }

    public function test_training_cannot_be_completed_before_it_ends(): void
    {
        [$employee, $manager] = $this->users();
        $hr = User::factory()->create(['role' => UserRole::Hr]);
        $training = Training::create([
            'name' => 'Mendatang',
            'type' => 'internal',
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);
        $request = TrainingRequest::create([
            'user_id' => $employee->id,
            'manager_id' => $manager->id,
            'training_id' => $training->id,
            'reason' => 'Test',
            'status' => TrainingRequestStatus::PendingHr,
        ]);
        $request->verifyByHr($hr);

        $this->expectException(DomainException::class);
        $request->complete($hr, 'Terlalu cepat');
    }

    public function test_employee_cannot_resubmit_after_hr_verify(): void
    {
        [$employee, $manager] = $this->users();
        $training = Training::create(['name' => 'Test', 'type' => 'internal', 'is_active' => true]);

        $request = TrainingRequest::create([
            'user_id' => $employee->id,
            'manager_id' => $manager->id,
            'training_id' => $training->id,
            'reason' => 'Test',
            'status' => TrainingRequestStatus::PendingHr,
        ]);

        $this->expectException(DomainException::class);
        $request->resubmit($employee, 'Coba lagi');
    }

    /** @return array{0: User, 1: User} */
    private function users(): array
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
            'manager_id' => $manager->id,
        ]);

        return [$employee, $manager];
    }
}
