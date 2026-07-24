<?php

namespace Database\Seeders;

use App\Enums\ReviewType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PerformanceSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $managerId = DB::table('users')->where('email', 'atasan@example.com')->value('id');
        $hrId = DB::table('users')->where('email', 'hr@example.com')->value('id');
        $employeeIds = DB::table('users')->whereIn('email', ['pegawai@example.com', 'pegawai2@example.com'])
            ->orderBy('email')->pluck('id')->values();
        $periodIds = [];

        foreach (range(1, 2) as $index) {
            $name = "Periode Penilaian {$index}";
            DB::table('review_periods')->updateOrInsert(['name' => $name], [
                'starts_at' => sprintf('2026-%02d-01', $index),
                'ends_at' => sprintf('2026-%02d-28', $index),
                'kpi_weight' => 40, 'discipline_weight' => 20, 'manager_weight' => 20,
                'review_360_weight' => 20, 'base_bonus' => 1_000_000,
                'is_active' => $index === 2, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $periodIds[] = DB::table('review_periods')->where('name', $name)->value('id');
        }

        foreach (range(0, 1) as $index) {
            $number = $index + 1;
            $indicatorName = "Indikator KPI {$number}";
            DB::table('kpi_indicators')->updateOrInsert([
                'review_period_id' => $periodIds[$index], 'name' => $indicatorName,
            ], [
                'description' => "Indikator kinerja demo {$number}", 'unit' => 'persen',
                'weight' => 100, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $indicatorId = DB::table('kpi_indicators')->where([
                'review_period_id' => $periodIds[$index], 'name' => $indicatorName,
            ])->value('id');

            DB::table('employee_kpis')->updateOrInsert([
                'kpi_indicator_id' => $indicatorId, 'employee_id' => $employeeIds[$index],
            ], [
                'review_period_id' => $periodIds[$index], 'manager_id' => $managerId,
                'target' => 100, 'achievement' => 80 + $number, 'notes' => "Capaian KPI demo {$number}",
                'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('performance_reviews')->updateOrInsert([
                'review_period_id' => $periodIds[$index], 'reviewer_id' => $managerId,
                'reviewee_id' => $employeeIds[$index], 'type' => ReviewType::ManagerToEmployee->value,
            ], [
                'score' => 4, 'comments' => "Penilaian demo {$number}", 'submitted_at' => $now,
                'created_at' => $now, 'updated_at' => $now,
            ]);

            $score = 75 + $number;
            DB::table('merit_results')->updateOrInsert([
                'review_period_id' => $periodIds[$index], 'employee_id' => $employeeIds[$index],
            ], [
                'kpi_score' => $score, 'discipline_score' => $score, 'manager_score' => $score,
                'review_360_score' => $score, 'total_score' => $score,
                'estimated_bonus' => $score * 10_000, 'manager_verified_by' => $managerId,
                'manager_verified_at' => $now, 'hr_verified_by' => $hrId,
                'hr_verified_at' => $now, 'published_at' => $now,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }
}
