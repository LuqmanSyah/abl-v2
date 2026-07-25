<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Human Resources', 'code' => 'HR'],
            ['name' => 'Teknologi Informasi', 'code' => 'IT'],
            ['name' => 'Keuangan', 'code' => 'FIN'],
            ['name' => 'Operasional', 'code' => 'OPS'],
            ['name' => 'Pemasaran', 'code' => 'MKT'],
        ];

        foreach ($departments as $data) {
            Department::create($data);
        }
    }
}
