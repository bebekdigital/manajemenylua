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

            // Identitas
            $table->string('nisn', 20)->unique();
            $table->string('nama');
            $table->string('nipd', 20)->nullable();
            $table->string('nik', 20)->nullable();
            $table->string('no_kk', 20)->nullable();
            $table->string('unit')->nullable();
            $table->string('program')->default('Umum');
            $table->string('jenjang', 10)->nullable();
            $table->unsignedTinyInteger('tingkat')->nullable();
            $table->string('kelas', 10)->nullable();
            $table->enum('jk', ['L', 'P']);
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('agama', 20)->default('Islam');
            $table->string('no_wa', 20)->nullable();
            $table->string('sekolah_asal')->nullable();

            // Alamat
            $table->string('jalan')->nullable();
            $table->string('rt_rw', 20)->nullable();
            $table->string('dusun')->nullable();
            $table->string('desa')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kabupaten')->nullable();
            $table->string('provinsi')->nullable();

            // Kesejahteraan
            $table->unsignedTinyInteger('desil')->nullable();
            $table->boolean('status_pip')->default(false);
            $table->string('pip_keterangan')->nullable();
            $table->boolean('status_kip')->default(false);
            $table->string('no_kip')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
