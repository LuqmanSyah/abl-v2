<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\CareerGoal;
use App\Models\Competency;
use App\Models\EmployeeCompetency;
use App\Models\Position;
use App\Models\PositionCompetency;
use App\Models\Training;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $unit = Unit::firstOrCreate(
            ['code' => 'SDM'],
            ['name' => 'Sumber Daya Manusia'],
        );

        $hrPosition = Position::firstOrCreate(
            ['unit_id' => $unit->id, 'name' => 'Admin SDM'],
            ['level' => 10],
        );

        $managerPosition = Position::firstOrCreate(
            ['unit_id' => $unit->id, 'name' => 'Kepala Bagian'],
            ['level' => 8],
        );

        $employeePosition = Position::firstOrCreate(
            ['unit_id' => $unit->id, 'name' => 'Staf'],
            ['level' => 3],
        );

        $employee = User::updateOrCreate(
            ['email' => 'hr@example.com'],
            [
                'name' => 'Admin SDM',
                'password' => 'password',
                'role' => UserRole::Hr,
                'unit_id' => $unit->id,
                'position_id' => $hrPosition->id,
                'is_active' => true,
            ],
        );

        $leadership = Competency::firstOrCreate(['name' => 'Kepemimpinan'], ['description' => 'Kemampuan memimpin tim dan mengambil keputusan.']);
        $communication = Competency::firstOrCreate(['name' => 'Komunikasi'], ['description' => 'Kemampuan menyampaikan informasi secara efektif.']);
        PositionCompetency::updateOrCreate(
            ['position_id' => $managerPosition->id, 'competency_id' => $leadership->id],
            ['required_level' => 4],
        );
        PositionCompetency::updateOrCreate(
            ['position_id' => $managerPosition->id, 'competency_id' => $communication->id],
            ['required_level' => 4],
        );
        EmployeeCompetency::updateOrCreate(
            ['user_id' => $employee->id, 'competency_id' => $leadership->id],
            ['level' => 2, 'assessed_at' => today(), 'notes' => 'Data demo'],
        );
        EmployeeCompetency::updateOrCreate(
            ['user_id' => $employee->id, 'competency_id' => $communication->id],
            ['level' => 3, 'assessed_at' => today(), 'notes' => 'Data demo'],
        );
        CareerGoal::updateOrCreate(
            ['user_id' => $employee->id],
            ['target_position_id' => $managerPosition->id],
        );
        Training::firstOrCreate(
            ['name' => 'Dasar Kepemimpinan'],
            ['competency_id' => $leadership->id, 'provider' => 'Internal', 'type' => 'internal', 'is_active' => true],
        );

        $manager = User::updateOrCreate(
            ['email' => 'atasan@example.com'],
            [
                'name' => 'Atasan Demo',
                'password' => 'password',
                'role' => UserRole::Manager,
                'unit_id' => $unit->id,
                'position_id' => $managerPosition->id,
                'is_active' => true,
            ],
        );

        User::updateOrCreate(
            ['email' => 'pegawai@example.com'],
            [
                'name' => 'Pegawai Demo',
                'password' => 'password',
                'role' => UserRole::Employee,
                'unit_id' => $unit->id,
                'position_id' => $employeePosition->id,
                'manager_id' => $manager->id,
                'is_active' => true,
            ],
        );
    }
}
