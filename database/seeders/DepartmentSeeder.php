<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Bidang Pendidikan',
                'order' => 1,
                'positions' => [
                    'Kepala Bidang Pendidikan',
                    'Koordinator Kurikulum',
                    'Pengawas Sekolah',
                    'Penjamin Mutu Pendidikan',
                ],
            ],
            [
                'name' => 'Bidang Sumber Daya Manusia (SDM)',
                'order' => 2,
                'positions' => [
                    'Kepala Bidang SDM',
                    'Staff Kepegawaian & Diklat',
                    'Staff Rekrutmen & Penilaian',
                ],
            ],
            [
                'name' => 'Bidang Keuangan & Administrasi',
                'order' => 3,
                'positions' => [
                    'Kepala Bidang Keuangan',
                    'Bendahara Yayasan',
                    'Staff Akuntansi & Pajak',
                    'Staff Administrasi Umum',
                ],
            ],
            [
                'name' => 'Bidang Sarana, Prasarana & Aset',
                'order' => 4,
                'positions' => [
                    'Kepala Bidang Sarpras',
                    'Koordinator Pemeliharaan & Aset',
                    'Staff Logistik & Pengadaan',
                ],
            ],
            [
                'name' => 'Bidang Dakwah, Sosial & Humas',
                'order' => 5,
                'positions' => [
                    'Kepala Bidang Dakwah & Humas',
                    'Koordinator Hubungan Masyarakat & Kemitraan',
                    'Pembina Keasramaan & Keislaman',
                ],
            ],
        ];

        foreach ($departments as $deptData) {
            $department = Department::firstOrCreate(
                ['name' => $deptData['name']],
                ['order' => $deptData['order']]
            );

            foreach ($deptData['positions'] as $idx => $posName) {
                $department->positions()->firstOrCreate(
                    ['name' => $posName],
                    ['order' => $idx + 1]
                );
            }
        }
    }
}
