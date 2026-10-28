<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Jabatan dikosongkan karena akan dibuat sendiri oleh pengguna.
     */
    public function run(): void
    {
        $departments = [
            ['name' => 'Badan Pembina & Pengurus Yayasan', 'order' => 0],
            ['name' => 'Bidang Pendidikan', 'order' => 1],
            ['name' => 'Bidang Sumber Daya Manusia (SDM)', 'order' => 2],
            ['name' => 'Bidang Keuangan & Administrasi', 'order' => 3],
            ['name' => 'Bidang Sarana, Prasarana & Aset', 'order' => 4],
            ['name' => 'Bidang Dakwah, Sosial & Humas', 'order' => 5],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['name' => $dept['name']], ['order' => $dept['order']]);
        }
    }
}
