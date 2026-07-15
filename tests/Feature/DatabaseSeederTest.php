<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_application_table_has_at_least_five_seeded_rows(): void
    {
        $this->seed();
        $this->seed();

        foreach ([
            'units',
            'positions',
            'users',
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
            $this->assertGreaterThanOrEqual(5, DB::table($table)->count(), "Table {$table} has fewer than five rows.");
        }
    }
}
