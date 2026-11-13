<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ubah student_status dari ENUM menjadi VARCHAR(50) dengan default 'Naik Kelas'
        DB::statement("ALTER TABLE student_academic_records MODIFY student_status VARCHAR(50) DEFAULT 'Naik Kelas'");

        // 2. Normalisasi data yang ada:
        // Pindahkan catatan mutasi / dropout / lulus ke ket_tidak_aktif jika belum terisi
        DB::table('student_academic_records')
            ->where('student_status', 'mutasi_keluar')
            ->whereNull('ket_tidak_aktif')
            ->update(['ket_tidak_aktif' => 'Mutasi Keluar']);

        DB::table('student_academic_records')
            ->where('student_status', 'dropout')
            ->whereNull('ket_tidak_aktif')
            ->update(['ket_tidak_aktif' => 'Drop Out']);

        DB::table('student_academic_records')
            ->where('student_status', 'lulus')
            ->whereNull('ket_tidak_aktif')
            ->update(['ket_tidak_aktif' => 'Lulus']);

        // Konversi mengulang -> Tinggal Kelas
        DB::table('student_academic_records')
            ->where('student_status', 'mengulang')
            ->update(['student_status' => 'Tinggal Kelas']);

        // Sisanya (aktif, mutasi_masuk, mutasi_keluar, dropout, lulus, null, '') -> Naik Kelas
        DB::table('student_academic_records')
            ->whereNotIn('student_status', ['Naik Kelas', 'Tinggal Kelas'])
            ->update(['student_status' => 'Naik Kelas']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke format enum lama
        DB::table('student_academic_records')
            ->where('student_status', 'Tinggal Kelas')
            ->update(['student_status' => 'mengulang']);

        DB::table('student_academic_records')
            ->where('student_status', 'Naik Kelas')
            ->update(['student_status' => 'aktif']);

        DB::statement("ALTER TABLE student_academic_records MODIFY student_status ENUM('aktif', 'mutasi_masuk', 'mutasi_keluar', 'lulus', 'mengulang', 'dropout') DEFAULT 'aktif'");
    }
};
