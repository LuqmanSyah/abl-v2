<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\PositionSkill;
use App\Models\Skill;
use Illuminate\Database\Seeder;

class PositionSkillSeeder extends Seeder
{
    public function run(): void
    {
        $positionSkills = [
            // Software Engineer: Laravel (3), PostgreSQL (3), Analisis Data (2)
            ['position_title' => 'Software Engineer', 'skill_name' => 'Laravel', 'min_required_level' => 3],
            ['position_title' => 'Software Engineer', 'skill_name' => 'PostgreSQL', 'min_required_level' => 3],
            ['position_title' => 'Software Engineer', 'skill_name' => 'Analisis Data', 'min_required_level' => 2],

            // IT Manager: Project Management (3), Kepemimpinan (3)
            ['position_title' => 'IT Manager', 'skill_name' => 'Project Management', 'min_required_level' => 3],
            ['position_title' => 'IT Manager', 'skill_name' => 'Kepemimpinan', 'min_required_level' => 3],

            // HR Manager: Komunikasi Efektif (3), Kepemimpinan (2)
            ['position_title' => 'HR Manager', 'skill_name' => 'Komunikasi Efektif', 'min_required_level' => 3],
            ['position_title' => 'HR Manager', 'skill_name' => 'Kepemimpinan', 'min_required_level' => 2],

            // Finance Manager: Akuntansi Dasar (4), Analisis Data (3)
            ['position_title' => 'Finance Manager', 'skill_name' => 'Akuntansi Dasar', 'min_required_level' => 4],
            ['position_title' => 'Finance Manager', 'skill_name' => 'Analisis Data', 'min_required_level' => 3],

            // Kepala Operasional: Project Management (4), Negosiasi (3)
            ['position_title' => 'Kepala Operasional', 'skill_name' => 'Project Management', 'min_required_level' => 4],
            ['position_title' => 'Kepala Operasional', 'skill_name' => 'Negosiasi', 'min_required_level' => 3],
        ];

        $positions = Position::all()->keyBy('title');
        $skills = Skill::all()->keyBy('name');

        foreach ($positionSkills as $data) {
            PositionSkill::create([
                'position_id' => $positions[$data['position_title']]->id,
                'skill_id' => $skills[$data['skill_name']]->id,
                'min_required_level' => $data['min_required_level'],
            ]);
        }
    }
}
