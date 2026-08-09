<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_master_data_and_bootstrap_users_are_seeded_idempotently(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(2, DB::table('units')->count());
        $this->assertSame(3, DB::table('positions')->count());
        $this->assertSame(1, DB::table('positions')->where('name', 'Admin SDM')->value('level'));
        $this->assertSame(1, DB::table('positions')->where('name', 'Staf Operasional')->value('level'));
        $this->assertSame(2, DB::table('positions')->where('name', 'Kepala Bagian')->value('level'));

        $this->assertSame(7, DB::table('users')->count());
        $this->assertSame('Kepala Bagian', DB::table('users')->join('positions', 'users.position_id', '=', 'positions.id')->where('email', 'atasan@example.com')->value('positions.name'));
        $this->assertSame('Staf Operasional', DB::table('users')->join('positions', 'users.position_id', '=', 'positions.id')->where('email', 'pegawai@example.com')->value('positions.name'));

        foreach ([
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
            $this->assertSame(0, DB::table($table)->count(), "Table {$table} must not contain seeded rows.");
        }

        $this->post('/login', [
            'email' => 'hr@example.com',
            'password' => 'password',
        ])->assertRedirect('/hr');
    }
}
