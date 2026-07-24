<?php

namespace Database\Seeders;

use App\Enums\MentoringStatus;
use App\Enums\TrainingRequestStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CareerDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $managerId = DB::table('users')->where('email', 'atasan@example.com')->value('id');
        $hrId = DB::table('users')->where('email', 'hr@example.com')->value('id');
        $positionId = DB::table('positions')->where('name', 'Kepala Bagian')->value('id');
        $employeeIds = DB::table('users')->whereIn('email', ['pegawai@example.com', 'pegawai2@example.com'])
            ->orderBy('email')->pluck('id')->values();

        foreach ([
            ['name' => 'Kepemimpinan', 'description' => 'Kompetensi kepemimpinan', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Komunikasi', 'description' => 'Kompetensi komunikasi', 'created_at' => $now, 'updated_at' => $now],
        ] as $competency) {
            DB::table('competencies')->updateOrInsert(['name' => $competency['name']], $competency);
        }
        $competencyIds = DB::table('competencies')->whereIn('name', ['Kepemimpinan', 'Komunikasi'])
            ->orderBy('name')->pluck('id')->values();

        foreach (range(0, 1) as $index) {
            $number = $index + 1;
            DB::table('position_competency')->updateOrInsert([
                'position_id' => $positionId, 'competency_id' => $competencyIds[$index],
            ], ['required_level' => 4, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('employee_competencies')->updateOrInsert([
                'user_id' => $employeeIds[$index], 'competency_id' => $competencyIds[$index],
            ], [
                'level' => 3, 'assessed_at' => '2026-07-15', 'notes' => "Asesmen demo {$number}",
                'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('career_goals')->updateOrInsert(['user_id' => $employeeIds[$index]], [
                'target_position_id' => $positionId, 'created_at' => $now, 'updated_at' => $now,
            ]);

            $trainingName = "Pelatihan Kompetensi {$number}";
            DB::table('trainings')->updateOrInsert(['name' => $trainingName], [
                'competency_id' => $competencyIds[$index], 'provider' => 'Internal', 'type' => 'internal',
                'description' => "Pelatihan demo {$number}",
                'starts_at' => sprintf('2026-09-0%d 09:00:00', $number),
                'ends_at' => sprintf('2026-09-0%d 16:00:00', $number),
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $trainingId = DB::table('trainings')->where('name', $trainingName)->value('id');
            DB::table('training_requests')->updateOrInsert([
                'user_id' => $employeeIds[$index], 'training_id' => $trainingId,
            ], [
                'manager_id' => $managerId, 'status' => TrainingRequestStatus::Approved->value,
                'reason' => "Pengembangan kompetensi {$number}", 'manager_notes' => 'Disetujui untuk data demo',
                'requested_at' => $now, 'manager_decided_at' => $now,
                'hr_verified_by' => $hrId, 'hr_verified_at' => $now,
                'created_at' => $now, 'updated_at' => $now,
            ]);

            $topic = "Mentoring Kompetensi {$number}";
            DB::table('mentorings')->updateOrInsert([
                'employee_id' => $employeeIds[$index], 'topic' => $topic,
            ], [
                'manager_id' => $managerId, 'status' => MentoringStatus::Approved->value,
                'target' => "Peningkatan kompetensi {$number}", 'requested_at' => $now,
                'scheduled_at' => sprintf('2026-08-1%d 10:00:00', $number),
                'manager_notes' => 'Jadwal data demo', 'created_at' => $now, 'updated_at' => $now,
            ]);
            $mentoringId = DB::table('mentorings')->where([
                'employee_id' => $employeeIds[$index], 'topic' => $topic,
            ])->value('id');
            DB::table('activity_logs')->updateOrInsert([
                'action' => 'mentoring.approved', 'subject_type' => 'App\\Models\\Mentoring',
                'subject_id' => $mentoringId,
            ], [
                'user_id' => $managerId, 'data' => json_encode(['source' => 'seeder']), 'created_at' => $now,
            ]);
        }
    }
}
