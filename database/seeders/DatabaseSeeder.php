<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            PositionSeeder::class,
            WorkScheduleSeeder::class,
            BranchOfficeSeeder::class,
            SkillSeeder::class,
            PositionSkillSeeder::class,
            HolidaySeeder::class,
            UserSeeder::class,
            KpiSeeder::class,
            CareerPathSeeder::class,
        ]);
    }
}
