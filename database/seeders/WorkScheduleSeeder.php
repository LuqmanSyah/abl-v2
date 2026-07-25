<?php

namespace Database\Seeders;

use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;

class WorkScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $schedules = [
            [
                'name' => 'Reguler',
                'check_in_time' => '08:00:00',
                'check_out_time' => '17:00:00',
                'late_tolerance_minutes' => 15,
                'alfa_cutoff_minutes' => 120,
            ],
            [
                'name' => 'Shift Pagi',
                'check_in_time' => '07:00:00',
                'check_out_time' => '16:00:00',
                'late_tolerance_minutes' => 10,
                'alfa_cutoff_minutes' => 90,
            ],
            [
                'name' => 'Shift Sore',
                'check_in_time' => '14:00:00',
                'check_out_time' => '22:00:00',
                'late_tolerance_minutes' => 10,
                'alfa_cutoff_minutes' => 90,
            ],
        ];

        foreach ($schedules as $data) {
            WorkSchedule::create($data);
        }
    }
}
