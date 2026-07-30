<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_organization_and_demo_users_are_seeded_idempotently(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(2, DB::table('units')->count());
        $this->assertSame(2, DB::table('positions')->count());
        $this->assertSame(7, DB::table('users')->count());

        foreach ([
            'duty_trips',
            'attendances',
            'review_periods',
            'employee_kpis',
            'merit_results',
            'development_plans',
            'development_requests',
            'activity_logs',
        ] as $table) {
            $this->assertSame(0, DB::table($table)->count(), "{$table} must be empty.");
        }
    }
}
