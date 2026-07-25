<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            ['name' => 'Tahun Baru 2026', 'date' => '2026-01-01'],
            ['name' => 'Isra Miraj Nabi Muhammad SAW', 'date' => '2026-01-27'],
            ['name' => 'Hari Raya Nyepi', 'date' => '2026-03-19'],
            ['name' => 'Hari Raya Idul Fitri', 'date' => '2026-03-20'],
            ['name' => 'Hari Raya Idul Fitri', 'date' => '2026-03-21'],
            ['name' => 'Wafat Yesus Kristus', 'date' => '2026-04-03'],
            ['name' => 'Hari Buruh Internasional', 'date' => '2026-05-01'],
            ['name' => 'Hari Raya Waisak', 'date' => '2026-05-12'],
            ['name' => 'Hari Kenaikan Yesus Kristus', 'date' => '2026-05-14'],
            ['name' => 'Hari Lahir Pancasila', 'date' => '2026-06-01'],
            ['name' => 'Hari Raya Idul Adha', 'date' => '2026-05-27'],
            ['name' => 'Tahun Baru Islam', 'date' => '2026-07-17'],
            ['name' => 'Hari Kemerdekaan RI', 'date' => '2026-08-17'],
            ['name' => 'Maulid Nabi Muhammad SAW', 'date' => '2026-09-25'],
            ['name' => 'Hari Raya Natal', 'date' => '2026-12-25'],
        ];

        foreach ($holidays as $data) {
            Holiday::create($data);
        }
    }
}
