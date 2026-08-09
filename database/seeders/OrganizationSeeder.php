<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('units')->upsert([
            ['code' => 'SDM', 'name' => 'Sumber Daya Manusia', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'OPS', 'name' => 'Operasional', 'created_at' => $now, 'updated_at' => $now],
        ], ['code'], ['name', 'updated_at']);

        $units = DB::table('units')->pluck('id', 'code');
        DB::table('positions')->upsert([
            ['unit_id' => $units['SDM'], 'name' => 'Admin SDM', 'level' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['unit_id' => $units['OPS'], 'name' => 'Staf Operasional', 'level' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['unit_id' => $units['OPS'], 'name' => 'Kepala Bagian', 'level' => 2, 'created_at' => $now, 'updated_at' => $now],
        ], ['unit_id', 'name'], ['level', 'updated_at']);
    }
}
