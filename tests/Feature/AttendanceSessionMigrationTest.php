<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AttendanceSessionMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_duplicate_sessions_are_removed_before_unique_index(): void
    {
        $this->seed();
        $user = User::query()->where('role', UserRole::Employee)->firstOrFail();
        $migration = require database_path(
            'migrations/2026_07_26_000006_add_attendance_session_constraint.php',
        );
        $migration->down();
        $row = [
            'user_id' => $user->id,
            'attendance_request_id' => null,
            'type' => AttendanceType::CheckIn->value,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'distance_to_target_meters' => 0,
            'is_fallback' => false,
            'address_snapshot' => 'Kantor',
            'photo_path' => 'attendance/legacy.jpg',
            'is_radius_exception' => false,
            'exception_reason' => null,
            'status' => AttendanceStatus::Normal->value,
            'recorded_at' => '2030-09-01 08:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $keptId = DB::table('attendances')->insertGetId($row);
        $removedId = DB::table('attendances')->insertGetId($row);
        DB::table('daily_attendance_summaries')->insert([
            'user_id' => $user->id,
            'date' => '2030-09-01',
            'check_in_id' => $removedId,
            'status' => 'present',
            'late_minutes' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration->up();

        $this->assertDatabaseHas('attendances', ['id' => $keptId]);
        $this->assertDatabaseMissing('attendances', ['id' => $removedId]);
        $this->assertDatabaseHas('daily_attendance_summaries', ['check_in_id' => $keptId]);
    }
}
