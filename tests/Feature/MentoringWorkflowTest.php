<?php

namespace Tests\Feature;

use App\Enums\MentoringStatus;
use App\Enums\UserRole;
use App\Models\Mentoring;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentoringWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_request_mentoring(): void
    {
        [$employee, $manager] = $this->users();

        $mentoring = Mentoring::create([
            'employee_id' => $employee->id,
            'manager_id' => $manager->id,
            'topic' => 'Belajar leadership',
            'target' => 'Jadi leader',
            'requested_at' => now()->addDay(),
        ]);

        $this->assertTrue($mentoring->exists);
        $this->assertSame(MentoringStatus::Pending, $mentoring->status);
    }

    public function test_employee_cannot_request_past_date(): void
    {
        [$employee, $manager] = $this->users();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Jadwal mentoring yang diajukan tidak boleh lampau');

        Mentoring::create([
            'employee_id' => $employee->id,
            'manager_id' => $manager->id,
            'topic' => 'Test',
            'target' => 'Test',
            'requested_at' => now()->subDay(),
        ]);
    }

    public function test_manager_can_schedule_mentoring(): void
    {
        [$employee, $manager] = $this->users();
        $mentoring = Mentoring::create([
            'employee_id' => $employee->id,
            'manager_id' => $manager->id,
            'topic' => 'Test',
            'target' => 'Test',
            'status' => MentoringStatus::Pending,
            'requested_at' => now()->addDay(),
        ]);

        $mentoring->approve($manager, now()->addDays(2)->toDateTimeString(), 'Siap');

        $this->assertSame(MentoringStatus::Approved, $mentoring->fresh()->status);
    }

    public function test_other_manager_cannot_approve_mentoring(): void
    {
        [$employee, $manager] = $this->users();
        $other = User::factory()->create(['role' => UserRole::Manager]);
        $mentoring = Mentoring::create([
            'employee_id' => $employee->id,
            'manager_id' => $manager->id,
            'topic' => 'Test',
            'target' => 'Test',
            'status' => MentoringStatus::Pending,
            'requested_at' => now()->addDay(),
        ]);

        $this->expectException(DomainException::class);
        $mentoring->approve($other, now()->addDays(2)->toDateTimeString(), 'Siap');
    }

    public function test_manager_can_reject_mentoring(): void
    {
        [$employee, $manager] = $this->users();
        $mentoring = Mentoring::create([
            'employee_id' => $employee->id,
            'manager_id' => $manager->id,
            'topic' => 'Test',
            'target' => 'Test',
            'status' => MentoringStatus::Pending,
            'requested_at' => now()->addDay(),
        ]);

        $mentoring->reject($manager, 'Sibuk');

        $this->assertSame(MentoringStatus::Rejected, $mentoring->fresh()->status);
    }

    public function test_manager_can_complete_mentoring(): void
    {
        [$employee, $manager] = $this->users();
        $mentoring = Mentoring::create([
            'employee_id' => $employee->id,
            'manager_id' => $manager->id,
            'topic' => 'Test',
            'target' => 'Test',
            'status' => MentoringStatus::Approved,
            'requested_at' => now()->subDay(),
        ]);

        $mentoring->complete($manager, 'Berhasil', 'Lanjutkan');

        $this->assertSame(MentoringStatus::Completed, $mentoring->fresh()->status);
        $this->assertNotNull($mentoring->fresh()->completed_at);
    }

    public function test_employee_cannot_approve_own_mentoring(): void
    {
        [$employee, $manager] = $this->users();
        $mentoring = Mentoring::create([
            'employee_id' => $employee->id,
            'manager_id' => $manager->id,
            'topic' => 'Test',
            'target' => 'Test',
            'status' => MentoringStatus::Pending,
            'requested_at' => now()->addDay(),
        ]);

        $this->expectException(DomainException::class);
        $mentoring->approve($employee, now()->addDays(2)->toDateTimeString(), 'Saya setuju');
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
