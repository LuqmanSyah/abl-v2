<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            ['name' => 'Laravel', 'category' => 'Technical'],
            ['name' => 'PostgreSQL', 'category' => 'Technical'],
            ['name' => 'Project Management', 'category' => 'Manajerial'],
            ['name' => 'Akuntansi Dasar', 'category' => 'Fungsional'],
            ['name' => 'Komunikasi Efektif', 'category' => 'Soft Skill'],
            ['name' => 'Analisis Data', 'category' => 'Technical'],
            ['name' => 'Kepemimpinan', 'category' => 'Manajerial'],
            ['name' => 'Negosiasi', 'category' => 'Soft Skill'],
        ];

        foreach ($skills as $data) {
            Skill::create($data);
        }
    }
}
