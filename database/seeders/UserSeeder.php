<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\BranchOffice;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $reguler = WorkSchedule::where('name', 'Reguler')->first();
        $jkt = BranchOffice::where('code', 'JKT')->first();
        $bdg = BranchOffice::where('code', 'BDG')->first();

        $posHrManager = Position::where('title', 'HR Manager')->first();
        $posHrStaff = Position::where('title', 'HR Staff')->first();
        $posItManager = Position::where('title', 'IT Manager')->first();
        $posItStaff = Position::where('title', 'Software Engineer')->first();
        $posKepalaOps = Position::where('title', 'Kepala Operasional')->first();
        $posStafOps = Position::where('title', 'Staf Operasional')->first();

        // IT Admin (no manager constraint — role bypass via booted guard skips non-Employee)
        $itAdmin = User::create([
            'nip' => 'NIP001',
            'name' => 'Admin IT',
            'email' => 'it.admin@company.com',
            'password' => Hash::make('password'),
            'position_id' => $posItManager->id,
            'work_schedule_id' => $reguler->id,
            'branch_office_id' => $jkt->id,
            'join_date' => '2020-01-01',
            'status' => true,
            'role' => UserRole::ItAdmin,
        ]);

        // Director
        $director = User::create([
            'nip' => 'NIP002',
            'name' => 'Direktur Utama',
            'email' => 'direktur@company.com',
            'password' => Hash::make('password'),
            'position_id' => $posKepalaOps->id,
            'work_schedule_id' => $reguler->id,
            'branch_office_id' => $jkt->id,
            'join_date' => '2018-03-01',
            'status' => true,
            'role' => UserRole::Director,
        ]);

        // HR Admin
        $hrAdmin = User::create([
            'nip' => 'NIP003',
            'name' => 'Admin HR',
            'email' => 'hr.admin@company.com',
            'password' => Hash::make('password'),
            'position_id' => $posHrManager->id,
            'work_schedule_id' => $reguler->id,
            'branch_office_id' => $jkt->id,
            'join_date' => '2019-06-01',
            'status' => true,
            'role' => UserRole::HrAdmin,
        ]);

        // Manager 1 (IT)
        $manager1 = User::create([
            'nip' => 'NIP004',
            'name' => 'Budi Santoso',
            'email' => 'manager.it@company.com',
            'password' => Hash::make('password'),
            'position_id' => $posItManager->id,
            'work_schedule_id' => $reguler->id,
            'branch_office_id' => $jkt->id,
            'join_date' => '2020-08-01',
            'status' => true,
            'role' => UserRole::Manager,
        ]);

        // Manager 2 (Ops)
        $manager2 = User::create([
            'nip' => 'NIP005',
            'name' => 'Siti Rahayu',
            'email' => 'manager.ops@company.com',
            'password' => Hash::make('password'),
            'position_id' => $posKepalaOps->id,
            'work_schedule_id' => $reguler->id,
            'branch_office_id' => $bdg->id,
            'join_date' => '2021-01-15',
            'status' => true,
            'role' => UserRole::Manager,
        ]);

        // Employees (5) — must have manager_id pointing to a Manager
        $employees = [
            [
                'nip' => 'NIP006',
                'name' => 'Ahmad Fauzi',
                'email' => 'ahmad.fauzi@company.com',
                'position_id' => $posItStaff->id,
                'manager_id' => $manager1->id,
                'branch_office_id' => $jkt->id,
            ],
            [
                'nip' => 'NIP007',
                'name' => 'Dewi Lestari',
                'email' => 'dewi.lestari@company.com',
                'position_id' => $posItStaff->id,
                'manager_id' => $manager1->id,
                'branch_office_id' => $jkt->id,
            ],
            [
                'nip' => 'NIP008',
                'name' => 'Rizky Pratama',
                'email' => 'rizky.pratama@company.com',
                'position_id' => $posStafOps->id,
                'manager_id' => $manager2->id,
                'branch_office_id' => $bdg->id,
            ],
            [
                'nip' => 'NIP009',
                'name' => 'Rina Marlina',
                'email' => 'rina.marlina@company.com',
                'position_id' => $posHrStaff->id,
                'manager_id' => $manager1->id,
                'branch_office_id' => $jkt->id,
            ],
            [
                'nip' => 'NIP010',
                'name' => 'Doni Setiawan',
                'email' => 'doni.setiawan@company.com',
                'position_id' => $posStafOps->id,
                'manager_id' => $manager2->id,
                'branch_office_id' => $bdg->id,
            ],
        ];

        foreach ($employees as $data) {
            User::create(array_merge($data, [
                'password' => Hash::make('password'),
                'work_schedule_id' => $reguler->id,
                'join_date' => '2023-01-01',
                'status' => true,
                'role' => UserRole::Employee,
            ]));
        }
    }
}
