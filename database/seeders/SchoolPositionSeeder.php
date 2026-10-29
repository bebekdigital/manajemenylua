<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DepartmentPosition;
use App\Models\SchoolUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SchoolPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! Schema::hasTable('departments') || ! Schema::hasTable('department_positions')) {
            return;
        }

        $defaultPositions = [
            'Kepala Sekolah',
            'Wakil Kepala Sekolah',
            'Guru Kelas',
            'Guru Mata Pelajaran',
            'Guru',
            'Wali Kelas',
            'Tenaga Administrasi (TU)',
            'Bendahara Unit',
        ];

        $units = SchoolUnit::all();

        // Cari parent Ketua Badan Pelaksana jika ada
        $leader = DepartmentPosition::where('name', 'like', '%Ketua Badan Pelaksana%')->first();
        $leaderId = $leader?->id;

        foreach ($units as $unit) {
            $dept = Department::firstOrCreate(
                ['name' => $unit->name],
                ['order' => 10]
            );

            $kepala = DepartmentPosition::where('department_id', $dept->id)
                ->where('name', 'Kepala Sekolah')
                ->first();
            $kepalaId = $kepala?->id;

            foreach ($defaultPositions as $order => $posName) {
                $isKepala = strcasecmp($posName, 'Kepala Sekolah') === 0;
                $isWakil = stripos($posName, 'Wakil Kepala') !== false;

                $level = $isKepala ? 3 : ($isWakil ? 3 : 4);
                $parentId = $isKepala ? $leaderId : $kepalaId;

                $pos = DepartmentPosition::firstOrCreate(
                    [
                        'department_id' => $dept->id,
                        'name' => $posName,
                    ],
                    [
                        'level' => $level,
                        'parent_id' => $parentId,
                        'order' => $order,
                    ]
                );

                if ($isKepala && ! $kepalaId) {
                    $kepalaId = $pos->id;
                }
            }
        }
    }
}
