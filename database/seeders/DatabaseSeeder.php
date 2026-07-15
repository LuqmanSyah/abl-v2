<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Position;
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

        $position = Position::firstOrCreate(
            ['unit_id' => $unit->id, 'name' => 'Admin SDM'],
            ['level' => 10],
        );

        User::updateOrCreate(
            ['email' => 'hr@example.com'],
            [
                'name' => 'Admin SDM',
                'password' => 'password',
                'role' => UserRole::Hr,
                'unit_id' => $unit->id,
                'position_id' => $position->id,
                'is_active' => true,
            ],
        );
    }
}
