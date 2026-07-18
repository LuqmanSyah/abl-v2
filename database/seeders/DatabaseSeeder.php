<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\DutyTripStatus;
use App\Enums\MentoringStatus;
use App\Enums\ReviewType;
use App\Enums\TrainingRequestStatus;
use App\Enums\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DB::table('approval_chains')->upsert([
            [
                'module' => 'training_request',
                'name' => 'Training Request — Manager → HR',
                'steps' => json_encode([
                    ['role' => 'manager', 'label' => 'Persetujuan Atasan', 'order' => 1],
                    ['role' => 'hr', 'label' => 'Verifikasi HR', 'order' => 2],
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'module' => 'mentoring',
                'name' => 'Mentoring — Manager',
                'steps' => json_encode([
                    ['role' => 'manager', 'label' => 'Persetujuan Atasan', 'order' => 1],
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['module'], ['name', 'steps', 'is_active', 'updated_at']);

        DB::transaction(function (): void {
            $now = now();
            $upsert = function (string $table, array $key, array $values = []) use ($now): int {
                DB::table($table)->updateOrInsert($key, [
                    ...$values,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                return (int) DB::table($table)->where($key)->value('id');
            };

            $unitIds = [];
            foreach ([
                ['code' => 'SDM', 'name' => 'Sumber Daya Manusia'],
                ['code' => 'OPS', 'name' => 'Operasional'],
                ['code' => 'TI', 'name' => 'Teknologi Informasi'],
                ['code' => 'KEU', 'name' => 'Keuangan'],
                ['code' => 'HUM', 'name' => 'Hubungan Masyarakat'],
            ] as $unit) {
                $unitIds[$unit['code']] = $upsert('units', ['code' => $unit['code']], ['name' => $unit['name']]);
            }

            $positionIds = [];
            foreach ([
                ['key' => 'hr', 'unit' => 'SDM', 'name' => 'Admin SDM', 'level' => 10],
                ['key' => 'manager', 'unit' => 'OPS', 'name' => 'Kepala Bagian', 'level' => 8],
                ['key' => 'staff', 'unit' => 'OPS', 'name' => 'Staf Operasional', 'level' => 3],
                ['key' => 'analyst', 'unit' => 'TI', 'name' => 'Analis Sistem', 'level' => 4],
                ['key' => 'auditor', 'unit' => 'KEU', 'name' => 'Auditor', 'level' => 5],
            ] as $position) {
                $positionIds[$position['key']] = $upsert('positions', [
                    'unit_id' => $unitIds[$position['unit']],
                    'name' => $position['name'],
                ], ['level' => $position['level']]);
            }

            $password = Hash::make('password');
            $hrId = $upsert('users', ['email' => 'hr@example.com'], [
                'name' => 'Admin SDM',
                'email_verified_at' => $now,
                'password' => $password,
                'role' => UserRole::Hr->value,
                'unit_id' => $unitIds['SDM'],
                'position_id' => $positionIds['hr'],
                'employee_number' => 'HR-001',
                'phone' => '081200000001',
                'is_active' => true,
            ]);
            $managerId = $upsert('users', ['email' => 'atasan@example.com'], [
                'name' => 'Atasan Demo',
                'email_verified_at' => $now,
                'password' => $password,
                'role' => UserRole::Manager->value,
                'unit_id' => $unitIds['OPS'],
                'position_id' => $positionIds['manager'],
                'employee_number' => 'MGR-001',
                'phone' => '081200000002',
                'is_active' => true,
            ]);

            $employeeIds = [];
            $employeePositions = ['staff', 'analyst', 'auditor', 'staff', 'analyst'];
            $employeeUnits = ['OPS', 'TI', 'KEU', 'OPS', 'TI'];
            foreach (range(1, 5) as $index) {
                $suffix = $index === 1 ? '' : (string) $index;
                $employeeIds[] = $upsert('users', ['email' => "pegawai{$suffix}@example.com"], [
                    'name' => "Pegawai Demo {$index}",
                    'email_verified_at' => $now,
                    'password' => $password,
                    'role' => UserRole::Employee->value,
                    'unit_id' => $unitIds[$employeeUnits[$index - 1]],
                    'position_id' => $positionIds[$employeePositions[$index - 1]],
                    'manager_id' => $managerId,
                    'employee_number' => sprintf('PGW-%03d', $index),
                    'phone' => sprintf('0812000001%02d', $index),
                    'is_active' => true,
                ]);
            }

            $locationIds = [];
            foreach ([
                ['name' => 'Kantor Pusat Jakarta', 'address' => 'Jakarta Pusat', 'latitude' => -6.1753924, 'longitude' => 106.8271528],
                ['name' => 'Kantor Bandung', 'address' => 'Kota Bandung', 'latitude' => -6.9174639, 'longitude' => 107.6191228],
                ['name' => 'Kantor Surabaya', 'address' => 'Kota Surabaya', 'latitude' => -7.2574719, 'longitude' => 112.7520883],
                ['name' => 'Kantor Yogyakarta', 'address' => 'Kota Yogyakarta', 'latitude' => -7.7955798, 'longitude' => 110.3694896],
                ['name' => 'Kantor Semarang', 'address' => 'Kota Semarang', 'latitude' => -6.9666670, 'longitude' => 110.4166640],
            ] as $location) {
                $locationIds[] = $upsert('duty_locations', ['name' => $location['name']], [
                    'address' => $location['address'],
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude'],
                    'radius_meters' => 150,
                    'is_active' => true,
                ]);
            }

            $tripIds = [];
            foreach (range(1, 5) as $offset) {
                $location = DB::table('duty_locations')->find($locationIds[$offset - 1]);
                $startsAt = "2026-08-0{$offset} 08:00:00";
                $tripIds[] = $upsert('duty_trips', [
                    'employee_id' => $employeeIds[$offset - 1],
                    'destination' => $location->name,
                    'starts_at' => $startsAt,
                ], [
                    'manager_id' => $managerId,
                    'duty_location_id' => $location->id,
                    'purpose' => "Kunjungan kerja {$offset}",
                    'ends_at' => "2026-08-0{$offset} 17:00:00",
                    'location_name' => $location->name,
                    'address' => $location->address,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'radius_meters' => $location->radius_meters,
                    'status' => DutyTripStatus::Approved->value,
                    'approved_at' => $now,
                ]);
            }

            foreach (range(1, 5) as $index) {
                $upsert('attendances', ['client_uuid' => sprintf('00000000-0000-4000-8000-%012d', $index)], [
                    'duty_trip_id' => $tripIds[$index - 1],
                    'employee_id' => $employeeIds[$index - 1],
                    'captured_at' => "2026-08-0{$index} 08:05:00",
                    'latitude' => DB::table('duty_locations')->where('id', $locationIds[$index - 1])->value('latitude'),
                    'longitude' => DB::table('duty_locations')->where('id', $locationIds[$index - 1])->value('longitude'),
                    'accuracy_meters' => 5,
                    'distance_meters' => 10,
                    'photo_path' => "attendance/demo-{$index}.jpg",
                    'status' => AttendanceStatus::Valid->value,
                    'mock_location_suspected' => false,
                    'synced_at' => $now,
                ]);
            }

            $periodIds = [];
            foreach (range(1, 5) as $index) {
                $periodIds[] = $upsert('review_periods', ['name' => "Periode Penilaian {$index}"], [
                    'starts_at' => sprintf('2026-%02d-01', $index),
                    'ends_at' => sprintf('2026-%02d-28', $index),
                    'kpi_weight' => 40,
                    'discipline_weight' => 20,
                    'manager_weight' => 20,
                    'review_360_weight' => 20,
                    'base_bonus' => 1_000_000,
                    'is_active' => $index === 5,
                ]);
            }

            $indicatorIds = [];
            foreach (range(1, 5) as $index) {
                $indicatorIds[] = $upsert('kpi_indicators', [
                    'review_period_id' => $periodIds[$index - 1],
                    'name' => "Indikator KPI {$index}",
                ], [
                    'description' => "Indikator kinerja demo {$index}",
                    'unit' => 'persen',
                    'weight' => 100,
                ]);

                $upsert('employee_kpis', [
                    'kpi_indicator_id' => $indicatorIds[$index - 1],
                    'employee_id' => $employeeIds[$index - 1],
                ], [
                    'review_period_id' => $periodIds[$index - 1],
                    'manager_id' => $managerId,
                    'target' => 100,
                    'achievement' => 80 + $index,
                    'notes' => "Capaian KPI demo {$index}",
                ]);

                $upsert('performance_reviews', [
                    'review_period_id' => $periodIds[$index - 1],
                    'reviewer_id' => $managerId,
                    'reviewee_id' => $employeeIds[$index - 1],
                    'type' => ReviewType::ManagerToEmployee->value,
                ], [
                    'score' => min(5, 3 + $index % 3),
                    'comments' => "Penilaian demo {$index}",
                    'submitted_at' => $now,
                ]);

                $score = 75 + $index;
                $upsert('merit_results', [
                    'review_period_id' => $periodIds[$index - 1],
                    'employee_id' => $employeeIds[$index - 1],
                ], [
                    'kpi_score' => $score,
                    'discipline_score' => $score,
                    'manager_score' => $score,
                    'review_360_score' => $score,
                    'total_score' => $score,
                    'estimated_bonus' => $score * 10_000,
                    'manager_verified_by' => $managerId,
                    'manager_verified_at' => $now,
                    'hr_verified_by' => $hrId,
                    'hr_verified_at' => $now,
                    'published_at' => $now,
                ]);
            }

            $competencyIds = [];
            foreach (['Kepemimpinan', 'Komunikasi', 'Analisis Data', 'Manajemen Waktu', 'Kerja Sama'] as $index => $name) {
                $competencyIds[] = $upsert('competencies', ['name' => $name], [
                    'description' => 'Kompetensi demo '.($index + 1),
                ]);
            }

            foreach (range(1, 5) as $index) {
                $upsert('position_competency', [
                    'position_id' => $positionIds['manager'],
                    'competency_id' => $competencyIds[$index - 1],
                ], ['required_level' => 4]);
                $upsert('employee_competencies', [
                    'user_id' => $employeeIds[$index - 1],
                    'competency_id' => $competencyIds[$index - 1],
                ], [
                    'level' => 3,
                    'assessed_at' => '2026-07-15',
                    'notes' => "Asesmen demo {$index}",
                ]);
                $upsert('career_goals', ['user_id' => $employeeIds[$index - 1]], [
                    'target_position_id' => $positionIds['manager'],
                ]);
            }

            $trainingIds = [];
            foreach (range(1, 5) as $index) {
                $trainingIds[] = $upsert('trainings', ['name' => "Pelatihan Kompetensi {$index}"], [
                    'competency_id' => $competencyIds[$index - 1],
                    'provider' => 'Internal',
                    'type' => 'internal',
                    'description' => "Pelatihan demo {$index}",
                    'starts_at' => "2026-09-0{$index} 09:00:00",
                    'ends_at' => "2026-09-0{$index} 16:00:00",
                    'is_active' => true,
                ]);
                $upsert('training_requests', [
                    'user_id' => $employeeIds[$index - 1],
                    'training_id' => $trainingIds[$index - 1],
                ], [
                    'manager_id' => $managerId,
                    'status' => TrainingRequestStatus::Approved->value,
                    'reason' => "Pengembangan kompetensi {$index}",
                    'manager_notes' => 'Disetujui untuk data demo',
                    'requested_at' => $now,
                    'manager_decided_at' => $now,
                    'hr_verified_by' => $hrId,
                    'hr_verified_at' => $now,
                ]);
                $mentoringId = $upsert('mentorings', [
                    'employee_id' => $employeeIds[$index - 1],
                    'topic' => "Mentoring Kompetensi {$index}",
                ], [
                    'manager_id' => $managerId,
                    'status' => MentoringStatus::Approved->value,
                    'target' => "Peningkatan kompetensi {$index}",
                    'requested_at' => $now,
                    'scheduled_at' => "2026-08-1{$index} 10:00:00",
                    'manager_notes' => 'Jadwal data demo',
                ]);

                DB::table('activity_logs')->updateOrInsert([
                    'action' => 'mentoring.approved',
                    'subject_type' => 'App\\Models\\Mentoring',
                    'subject_id' => $mentoringId,
                ], [
                    'user_id' => $managerId,
                    'data' => json_encode(['source' => 'seeder']),
                    'created_at' => $now,
                ]);
            }
        });
    }
}
