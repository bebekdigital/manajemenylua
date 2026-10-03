<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            // Sesuai dengan Template Dokumen Excel (A-BI)
            $table->string('nama');
            $table->string('nisn', 20)->unique();
            $table->string('nipd', 20)->nullable();
            $table->string('jenjang', 10)->nullable();
            $table->string('unit')->nullable();
            $table->string('jk', 10)->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('nik', 20)->nullable();
            $table->string('agama', 20)->default('Islam');
            $table->string('jalan')->nullable();
            $table->string('rt_rw', 20)->nullable();
            $table->string('dusun')->nullable();
            $table->string('desa')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kabupaten')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('jenis_tinggal')->nullable();
            $table->string('alat_transportasi')->nullable();
            $table->string('no_wa', 20)->nullable();
            $table->string('no_wa_2', 20)->nullable();
            $table->string('email')->nullable();

            // Data Ayah
            $table->string('ayah_nama')->nullable();
            $table->unsignedSmallInteger('ayah_tahun_lahir')->nullable();
            $table->string('ayah_pendidikan')->nullable();
            $table->string('ayah_pekerjaan')->nullable();
            $table->string('ayah_penghasilan')->nullable();
            $table->string('ayah_nik', 20)->nullable();
            $table->string('ayah_status')->nullable();

            // Data Ibu
            $table->string('ibu_nama')->nullable();
            $table->unsignedSmallInteger('ibu_tahun_lahir')->nullable();
            $table->string('ibu_pendidikan')->nullable();
            $table->string('ibu_pekerjaan')->nullable();
            $table->string('ibu_penghasilan')->nullable();
            $table->string('ibu_nik', 20)->nullable();
            $table->string('ibu_status')->nullable();

            // Data Wali
            $table->string('wali_nama')->nullable();
            $table->unsignedSmallInteger('wali_tahun_lahir')->nullable();
            $table->string('wali_pendidikan')->nullable();
            $table->string('wali_pekerjaan')->nullable();
            $table->string('wali_penghasilan')->nullable();
            $table->string('wali_nik', 20)->nullable();
            $table->string('wali_hubungan')->nullable();

            // Kesejahteraan & Bantuan
            $table->boolean('status_kip')->default(false);
            $table->string('no_kip')->nullable();
            $table->string('nama_di_kip')->nullable();
            $table->boolean('status_pip')->default(false);
            $table->string('pip_keterangan')->nullable();
            $table->string('kebutuhan_khusus')->nullable();

            // Registrasi & Latar Belakang
            $table->string('sekolah_asal')->nullable();
            $table->unsignedTinyInteger('anak_ke')->nullable();
            $table->string('no_kk', 20)->nullable();
            $table->string('jarak_rumah')->nullable();
            $table->boolean('has_bisnis')->default(false);
            $table->string('jenis_bisnis')->nullable();
            $table->unsignedTinyInteger('desil')->nullable();
            $table->string('diterima_di_jenjang')->nullable();
            $table->date('tanggal_diterima')->nullable();
            $table->string('info_psb')->nullable();
            $table->string('status_registrasi')->nullable();

            // Kolom Internal Aplikasi (Tidak ada di template Excel)
            $table->string('angkatan')->nullable();
            $table->string('program')->default('Umum');
            $table->unsignedTinyInteger('tingkat')->nullable();
            $table->string('kelas', 10)->nullable();
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('status_keluarga')->nullable();
            $table->string('foto')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
