<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $password = Hash::make('password');
        $units = DB::table('units')->pluck('id', 'code');
        $positions = DB::table('positions')->pluck('id', 'name');

        DB::table('users')->updateOrInsert(['email' => 'hr@example.com'], [
            'name' => 'Admin SDM', 'email_verified_at' => $now, 'password' => $password,
            'role' => UserRole::Hr->value, 'unit_id' => $units['SDM'], 'position_id' => $positions['Admin SDM'],
            'employee_number' => 'HR-001', 'phone' => '081200000001', 'is_active' => true,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('users')->updateOrInsert(['email' => 'atasan@example.com'], [
            'name' => 'Atasan Demo', 'email_verified_at' => $now, 'password' => $password,
            'role' => UserRole::Manager->value, 'unit_id' => $units['OPS'], 'position_id' => $positions['Kepala Bagian'],
            'employee_number' => 'MGR-001', 'phone' => '081200000002', 'is_active' => true,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $managerId = DB::table('users')->where('email', 'atasan@example.com')->value('id');
        foreach (range(1, 5) as $index) {
            $suffix = $index === 1 ? '' : (string) $index;
            DB::table('users')->updateOrInsert(['email' => "pegawai{$suffix}@example.com"], [
                'name' => "Pegawai Demo {$index}", 'email_verified_at' => $now, 'password' => $password,
                'role' => UserRole::Employee->value, 'unit_id' => $units['OPS'], 'position_id' => $positions['Kepala Bagian'],
                'manager_id' => $managerId, 'employee_number' => sprintf('PGW-%03d', $index),
                'phone' => sprintf('0812000001%02d', $index), 'is_active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }
}
