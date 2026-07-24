<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApprovalChainSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('approval_chains')->upsert([
            [
                'module' => 'training_request',
                'name' => 'Training Request - Manager ke HR',
                'steps' => json_encode([
                    ['role' => 'manager', 'label' => 'Persetujuan Atasan', 'order' => 1],
                    ['role' => 'hr', 'label' => 'Verifikasi HR', 'order' => 2],
                ]),
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'module' => 'mentoring',
                'name' => 'Mentoring - Manager',
                'steps' => json_encode([
                    ['role' => 'manager', 'label' => 'Persetujuan Atasan', 'order' => 1],
                ]),
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ],
        ], ['module'], ['name', 'steps', 'is_active', 'updated_at']);
    }
}
