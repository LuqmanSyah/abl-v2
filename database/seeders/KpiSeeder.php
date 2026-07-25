<?php

namespace Database\Seeders;

use App\Models\Kpi;
use Illuminate\Database\Seeder;

class KpiSeeder extends Seeder
{
    public function run(): void
    {
        $kpis = [
            ['name' => 'Kualitas Pekerjaan', 'category' => 'Kinerja', 'weight' => 25],
            ['name' => 'Produktivitas & Efisiensi', 'category' => 'Kinerja', 'weight' => 20],
            ['name' => 'Kehadiran & Kedisiplinan', 'category' => 'Perilaku', 'weight' => 15],
            ['name' => 'Kerjasama Tim', 'category' => 'Perilaku', 'weight' => 15],
            ['name' => 'Inisiatif & Inovasi', 'category' => 'Pengembangan', 'weight' => 10],
            ['name' => 'Komunikasi', 'category' => 'Perilaku', 'weight' => 10],
            ['name' => 'Pengembangan Diri', 'category' => 'Pengembangan', 'weight' => 5],
        ];

        foreach ($kpis as $data) {
            Kpi::create($data);
        }
    }
}
