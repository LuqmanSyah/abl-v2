<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\DutyTripStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DutyAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $managerId = DB::table('users')->where('email', 'atasan@example.com')->value('id');
        $employeeIds = DB::table('users')->whereIn('email', ['pegawai@example.com', 'pegawai2@example.com'])
            ->orderBy('email')->pluck('id')->values();

        foreach ([
            [
                'name' => 'Kantor Pusat Jakarta', 'address' => 'Jakarta Pusat',
                'latitude' => -6.1753924, 'longitude' => 106.8271528,
                'radius_meters' => 150, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'name' => 'Kantor Bandung', 'address' => 'Kota Bandung',
                'latitude' => -6.9174639, 'longitude' => 107.6191228,
                'radius_meters' => 150, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ],
        ] as $location) {
            DB::table('duty_locations')->updateOrInsert(['name' => $location['name']], $location);
        }

        $locations = DB::table('duty_locations')->whereIn('name', ['Kantor Pusat Jakarta', 'Kantor Bandung'])
            ->orderBy('name', 'desc')->get()->values();
        $tripIds = [];

        foreach ($locations as $index => $location) {
            $startsAt = sprintf('2026-08-0%d 08:00:00', $index + 1);
            DB::table('duty_trips')->updateOrInsert([
                'employee_id' => $employeeIds[$index], 'destination' => $location->name, 'starts_at' => $startsAt,
            ], [
                'manager_id' => $managerId, 'duty_location_id' => $location->id,
                'purpose' => 'Kunjungan kerja '.($index + 1),
                'ends_at' => sprintf('2026-08-0%d 17:00:00', $index + 1),
                'location_name' => $location->name, 'address' => $location->address,
                'latitude' => $location->latitude, 'longitude' => $location->longitude,
                'radius_meters' => $location->radius_meters, 'status' => DutyTripStatus::Approved->value,
                'approved_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $tripIds[] = DB::table('duty_trips')->where([
                'employee_id' => $employeeIds[$index], 'destination' => $location->name, 'starts_at' => $startsAt,
            ])->value('id');
        }

        foreach (range(0, 1) as $index) {
            $date = sprintf('2026-08-0%d', $index + 1);
            DB::table('attendances')->updateOrInsert([
                'client_uuid' => sprintf('00000000-0000-4000-8000-%012d', $index + 1),
            ], [
                'duty_trip_id' => $tripIds[$index], 'employee_id' => $employeeIds[$index],
                'attendance_date' => $date, 'captured_at' => "{$date} 08:05:00",
                'latitude' => $locations[$index]->latitude, 'longitude' => $locations[$index]->longitude,
                'accuracy_meters' => 5, 'distance_meters' => 10,
                'photo_path' => 'attendance/demo-'.($index + 1).'.jpg',
                'status' => AttendanceStatus::Valid->value, 'mock_location_suspected' => false,
                'synced_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }
}
