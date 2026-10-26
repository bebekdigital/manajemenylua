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

        // 1. Buat master positions (hanya 1 id per nama posisi)
        $positionMap = [];
        foreach ($defaultPositions as $posName) {
            $pos = SchoolPosition::firstOrCreate(['name' => $posName]);
            $positionMap[$posName] = $pos->id;
        }

        // 2. Hubungkan ke unit sekolah
        foreach ($units as $unit) {
            foreach ($defaultPositions as $order => $posName) {
                $posId = $positionMap[$posName];
                $unit->positions()->syncWithoutDetaching([
                    $posId => ['order' => $order],
                ]);
            }
        }
    }
}
