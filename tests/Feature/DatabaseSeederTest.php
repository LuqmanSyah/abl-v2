<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_data_is_idempotent_and_limited_to_two_rows_except_users(): void
    {
        $this->seed();
        $this->seed();

        foreach ([
            'units',
            'positions',
            'approval_chains',
            'duty_locations',
            'duty_trips',
            'attendances',
            'review_periods',
            'kpi_indicators',
            'employee_kpis',
            'performance_reviews',
            'merit_results',
            'competencies',
            'position_competency',
            'employee_competencies',
            'career_goals',
            'trainings',
            'training_requests',
            'mentorings',
            'activity_logs',
        ] as $table) {
            $this->assertSame(2, DB::table($table)->count(), "Table {$table} must contain exactly two seeded rows.");
        }

        $this->assertSame(7, DB::table('users')->count());
    }
}
