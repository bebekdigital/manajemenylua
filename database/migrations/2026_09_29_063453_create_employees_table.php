<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id(); // Primary Key recommended by Laravel

            // Kolom disesuaikan dengan urutan Template Excel Sheet 1
            $table->string('nipy')->nullable(); // NIPY bisa null jika NIK jadi acuan utama, atau biarkan ada
            $table->string('nama');
            $table->string('jk', 1)->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();

            // NIK sebagai UNIQUE DATA
            $table->string('nik', 50)->unique();

            $table->string('no_wa')->nullable();
            $table->string('email')->nullable();

            // Alamat Domisili
            $table->string('jalan_domisili')->nullable();
            $table->string('rt_rw_domisili')->nullable();
            $table->string('dusun_domisili')->nullable();
            $table->string('desa_domisili')->nullable();
            $table->string('kecamatan_domisili')->nullable();
            $table->string('kab_domisili')->nullable();
            $table->string('provinsi_domisili')->nullable();

            // Alamat Tinggal
            $table->string('jalan_tinggal')->nullable();
            $table->string('rt_rw_tinggal')->nullable();
            $table->string('dusun_tinggal')->nullable();
            $table->string('desa_tinggal')->nullable();
            $table->string('kecamatan_tinggal')->nullable();
            $table->string('provinsi_tinggal')->nullable();

            $table->string('status_rumah')->nullable();
            $table->string('kepemilikan_bpjs')->nullable(); // Ya/Tidak
            $table->string('penanggung_bpjs')->nullable();
            $table->string('skill')->nullable();
            $table->string('status_pernikahan')->nullable();
            $table->string('nama_suami_istri')->nullable();
            $table->string('ttl_suami_istri')->nullable();
            $table->string('pekerjaan_suami_istri')->nullable();
            $table->date('tanggal_menikah')->nullable();
            $table->integer('jumlah_anak')->nullable();
            $table->string('nama_ibu')->nullable();
            $table->string('nama_ayah')->nullable();
            $table->string('alamat_orangtua')->nullable();
            $table->string('kontak_darurat')->nullable();
            $table->string('hubungan_kontak_darurat')->nullable();

            $table->string('foto')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
