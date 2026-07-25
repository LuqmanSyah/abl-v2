<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            // HR Department
            ['department_code' => 'HR', 'title' => 'HR Manager', 'level' => 4],
            ['department_code' => 'HR', 'title' => 'HR Staff', 'level' => 2],

            // IT Department
            ['department_code' => 'IT', 'title' => 'IT Manager', 'level' => 4],
            ['department_code' => 'IT', 'title' => 'Software Engineer', 'level' => 3],
            ['department_code' => 'IT', 'title' => 'IT Support', 'level' => 2],

            // Finance Department
            ['department_code' => 'FIN', 'title' => 'Finance Manager', 'level' => 4],
            ['department_code' => 'FIN', 'title' => 'Akuntan', 'level' => 3],

            // Operations Department
            ['department_code' => 'OPS', 'title' => 'Kepala Operasional', 'level' => 5],
            ['department_code' => 'OPS', 'title' => 'Supervisor Operasional', 'level' => 4],
            ['department_code' => 'OPS', 'title' => 'Staf Operasional', 'level' => 2],
        ];

        $departments = Department::all()->keyBy('code');

        foreach ($positions as $data) {
            Position::create([
                'department_id' => $departments[$data['department_code']]->id,
                'title' => $data['title'],
                'level' => $data['level'],
            ]);
        }
    }
}
