<?php

namespace Database\Seeders;

use App\Models\BranchOffice;
use Illuminate\Database\Seeder;

class BranchOfficeSeeder extends Seeder
{
    public function run(): void
    {
        $offices = [
            [
                'name' => 'Kantor Pusat Jakarta',
                'code' => 'JKT',
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'allowed_radius_meters' => 100,
            ],
            [
                'name' => 'Kantor Cabang Bandung',
                'code' => 'BDG',
                'latitude' => -6.9147,
                'longitude' => 107.6098,
                'allowed_radius_meters' => 100,
            ],
            [
                'name' => 'Kantor Cabang Surabaya',
                'code' => 'SBY',
                'latitude' => -7.2575,
                'longitude' => 112.7521,
                'allowed_radius_meters' => 150,
            ],
        ];

        foreach ($offices as $data) {
            BranchOffice::create($data);
        }
    }
}
