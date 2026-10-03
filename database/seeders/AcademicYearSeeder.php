<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        AcademicYear::create([
            'name' => '2025/2026',
            'semester' => 'Ganjil',
            'start_date' => '2025-07-14',
            'end_date' => '2025-12-20',
        ]);

        AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'Genap',
            'start_date' => '2025-01-06',
            'end_date' => '2025-06-21',
        ]);

        AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'Ganjil',
            'start_date' => '2024-07-15',
            'end_date' => '2024-12-21',
        ]);
    }
}
