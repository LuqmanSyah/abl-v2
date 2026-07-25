<?php

namespace Database\Seeders;

use App\Models\CareerPath;
use App\Models\Position;
use Illuminate\Database\Seeder;

class CareerPathSeeder extends Seeder
{
    public function run(): void
    {
        $paths = [
            // IT career ladder
            [
                'current_title' => 'IT Support',
                'next_title' => 'Software Engineer',
                'min_experience_months' => 24,
                'min_merit_grade' => 'B',
            ],
            [
                'current_title' => 'Software Engineer',
                'next_title' => 'IT Manager',
                'min_experience_months' => 36,
                'min_merit_grade' => 'A',
            ],
            // HR career ladder
            [
                'current_title' => 'HR Staff',
                'next_title' => 'HR Manager',
                'min_experience_months' => 30,
                'min_merit_grade' => 'B',
            ],
            // Ops career ladder
            [
                'current_title' => 'Staf Operasional',
                'next_title' => 'Supervisor Operasional',
                'min_experience_months' => 24,
                'min_merit_grade' => 'B',
            ],
            [
                'current_title' => 'Supervisor Operasional',
                'next_title' => 'Kepala Operasional',
                'min_experience_months' => 36,
                'min_merit_grade' => 'A',
            ],
        ];

        $positions = Position::all()->keyBy('title');

        foreach ($paths as $data) {
            CareerPath::create([
                'current_position_id' => $positions[$data['current_title']]->id,
                'next_position_id' => $positions[$data['next_title']]->id,
                'min_experience_months' => $data['min_experience_months'],
                'min_merit_grade' => $data['min_merit_grade'],
            ]);
        }
    }
}
