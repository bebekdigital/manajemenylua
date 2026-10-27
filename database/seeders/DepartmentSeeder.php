<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DepartmentPosition;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Pimpinan & Pengurus Yayasan (Level 1 & 2)
        $deptYayasan = Department::firstOrCreate(
            ['name' => 'Badan Pembina & Pengurus Yayasan'],
            ['order' => 0]
        );

        $posL1KetuaPembina = DepartmentPosition::updateOrCreate(
            ['name' => 'Ketua Dewan Pembina Yayasan'],
            ['department_id' => $deptYayasan->id, 'level' => 1, 'order' => 1]
        );

        DepartmentPosition::updateOrCreate(
            ['name' => 'Anggota Dewan Pembina Yayasan'],
            ['department_id' => $deptYayasan->id, 'level' => 1, 'order' => 2, 'parent_id' => $posL1KetuaPembina->id]
        );

        DepartmentPosition::updateOrCreate(
            ['name' => 'Ketua Dewan Pengawas Yayasan'],
            ['department_id' => $deptYayasan->id, 'level' => 1, 'order' => 3, 'parent_id' => $posL1KetuaPembina->id]
        );

        // Level 2: Pengurus Harian
        $posL2KetuaUmum = DepartmentPosition::updateOrCreate(
            ['name' => 'Ketua Umum Yayasan'],
            ['department_id' => $deptYayasan->id, 'level' => 2, 'order' => 1, 'parent_id' => $posL1KetuaPembina->id]
        );

        DepartmentPosition::updateOrCreate(
            ['name' => 'Sekretaris Umum Yayasan'],
            ['department_id' => $deptYayasan->id, 'level' => 2, 'order' => 2, 'parent_id' => $posL2KetuaUmum->id]
        );

        $posL2BendaharaUmum = DepartmentPosition::updateOrCreate(
            ['name' => 'Bendahara Umum Yayasan'],
            ['department_id' => $deptYayasan->id, 'level' => 2, 'order' => 3, 'parent_id' => $posL2KetuaUmum->id]
        );

        $posL2Direktur = DepartmentPosition::updateOrCreate(
            ['name' => 'Direktur Eksekutif Yayasan'],
            ['department_id' => $deptYayasan->id, 'level' => 2, 'order' => 4, 'parent_id' => $posL2KetuaUmum->id]
        );

        // 2. Bidang Pendidikan
        $deptPendidikan = Department::firstOrCreate(['name' => 'Bidang Pendidikan'], ['order' => 1]);
        $kabidPendidikan = DepartmentPosition::updateOrCreate(
            ['name' => 'Kepala Bidang Pendidikan'],
            ['department_id' => $deptPendidikan->id, 'level' => 3, 'order' => 1, 'parent_id' => $posL2Direktur->id]
        );
        $koorKurikulum = DepartmentPosition::updateOrCreate(
            ['name' => 'Koordinator Kurikulum'],
            ['department_id' => $deptPendidikan->id, 'level' => 4, 'order' => 2, 'parent_id' => $kabidPendidikan->id]
        );
        $pengawas = DepartmentPosition::updateOrCreate(
            ['name' => 'Pengawas Sekolah'],
            ['department_id' => $deptPendidikan->id, 'level' => 4, 'order' => 3, 'parent_id' => $kabidPendidikan->id]
        );
        DepartmentPosition::updateOrCreate(
            ['name' => 'Penjamin Mutu Pendidikan'],
            ['department_id' => $deptPendidikan->id, 'level' => 5, 'order' => 4, 'parent_id' => $koorKurikulum->id]
        );

        // 3. Bidang SDM
        $deptSdm = Department::firstOrCreate(['name' => 'Bidang Sumber Daya Manusia (SDM)'], ['order' => 2]);
        $kabidSdm = DepartmentPosition::updateOrCreate(
            ['name' => 'Kepala Bidang SDM'],
            ['department_id' => $deptSdm->id, 'level' => 3, 'order' => 1, 'parent_id' => $posL2Direktur->id]
        );
        DepartmentPosition::updateOrCreate(
            ['name' => 'Staff Kepegawaian & Diklat'],
            ['department_id' => $deptSdm->id, 'level' => 5, 'order' => 2, 'parent_id' => $kabidSdm->id]
        );
        DepartmentPosition::updateOrCreate(
            ['name' => 'Staff Rekrutmen & Penilaian'],
            ['department_id' => $deptSdm->id, 'level' => 5, 'order' => 3, 'parent_id' => $kabidSdm->id]
        );

        // 4. Bidang Keuangan & Administrasi
        $deptKeuangan = Department::firstOrCreate(['name' => 'Bidang Keuangan & Administrasi'], ['order' => 3]);
        $kabidKeuangan = DepartmentPosition::updateOrCreate(
            ['name' => 'Kepala Bidang Keuangan'],
            ['department_id' => $deptKeuangan->id, 'level' => 3, 'order' => 1, 'parent_id' => $posL2BendaharaUmum->id]
        );
        $bendahara = DepartmentPosition::updateOrCreate(
            ['name' => 'Bendahara Yayasan'],
            ['department_id' => $deptKeuangan->id, 'level' => 4, 'order' => 2, 'parent_id' => $kabidKeuangan->id]
        );
        DepartmentPosition::updateOrCreate(
            ['name' => 'Staff Akuntansi & Pajak'],
            ['department_id' => $deptKeuangan->id, 'level' => 5, 'order' => 3, 'parent_id' => $bendahara->id]
        );
        DepartmentPosition::updateOrCreate(
            ['name' => 'Staff Administrasi Umum'],
            ['department_id' => $deptKeuangan->id, 'level' => 5, 'order' => 4, 'parent_id' => $kabidKeuangan->id]
        );

        // 5. Bidang Sarpras
        $deptSarpras = Department::firstOrCreate(['name' => 'Bidang Sarana, Prasarana & Aset'], ['order' => 4]);
        $kabidSarpras = DepartmentPosition::updateOrCreate(
            ['name' => 'Kepala Bidang Sarpras'],
            ['department_id' => $deptSarpras->id, 'level' => 3, 'order' => 1, 'parent_id' => $posL2Direktur->id]
        );
        $koorSarpras = DepartmentPosition::updateOrCreate(
            ['name' => 'Koordinator Pemeliharaan & Aset'],
            ['department_id' => $deptSarpras->id, 'level' => 4, 'order' => 2, 'parent_id' => $kabidSarpras->id]
        );
        DepartmentPosition::updateOrCreate(
            ['name' => 'Staff Logistik & Pengadaan'],
            ['department_id' => $deptSarpras->id, 'level' => 5, 'order' => 3, 'parent_id' => $koorSarpras->id]
        );

        // 6. Bidang Dakwah & Humas
        $deptDakwah = Department::firstOrCreate(['name' => 'Bidang Dakwah, Sosial & Humas'], ['order' => 5]);
        $kabidDakwah = DepartmentPosition::updateOrCreate(
            ['name' => 'Kepala Bidang Dakwah & Humas'],
            ['department_id' => $deptDakwah->id, 'level' => 3, 'order' => 1, 'parent_id' => $posL2KetuaUmum->id]
        );
        $koorHumas = DepartmentPosition::updateOrCreate(
            ['name' => 'Koordinator Hubungan Masyarakat & Kemitraan'],
            ['department_id' => $deptDakwah->id, 'level' => 4, 'order' => 2, 'parent_id' => $kabidDakwah->id]
        );
        DepartmentPosition::updateOrCreate(
            ['name' => 'Pembina Keasramaan & Keislaman'],
            ['department_id' => $deptDakwah->id, 'level' => 5, 'order' => 3, 'parent_id' => $koorHumas->id]
        );
    }
}
