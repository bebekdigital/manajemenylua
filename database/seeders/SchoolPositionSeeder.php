<?php

namespace Database\Seeders;

use App\Models\SchoolPosition;
use App\Models\SchoolUnit;
use Illuminate\Database\Seeder;

class SchoolPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultPositions = [
            'Kepala Sekolah',
            'Wakil Kepala Sekolah',
            'Wali Kelas',
            'Guru Mata Pelajaran',
            'Guru Kelas',
            'Tenaga Administrasi (TU)',
            'Bendahara Unit',
        ];

        $units = SchoolUnit::all();

        foreach ($units as $unit) {
            foreach ($defaultPositions as $order => $posName) {
                SchoolPosition::firstOrCreate([
                    'school_unit_id' => $unit->id,
                    'name' => $posName,
                ], [
                    'order' => $order,
                ]);
            }
        }
    }
}
