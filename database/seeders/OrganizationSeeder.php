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
            ['name' => 'Sumber Daya Manusia', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Operasional', 'created_at' => $now, 'updated_at' => $now],
        ], ['name'], ['updated_at']);

        $units = DB::table('units')->pluck('id', 'name');
        DB::table('positions')->upsert([
            ['unit_id' => $units['Sumber Daya Manusia'], 'name' => 'Admin SDM', 'level' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['unit_id' => $units['Operasional'], 'name' => 'Kepala Bagian', 'level' => 8, 'created_at' => $now, 'updated_at' => $now],
        ], ['unit_id', 'name'], ['level', 'updated_at']);
    }
}
