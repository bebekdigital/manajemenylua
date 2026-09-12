<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_families', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            // Data Ayah
            $table->string('ayah_nama')->nullable();
            $table->string('ayah_nik', 20)->nullable();
            $table->unsignedSmallInteger('ayah_tahun_lahir')->nullable();
            $table->string('ayah_pendidikan')->nullable();
            $table->string('ayah_pekerjaan')->nullable();
            $table->string('ayah_penghasilan')->nullable();

            // Data Ibu
            $table->string('ibu_nama')->nullable();
            $table->string('ibu_nik', 20)->nullable();
            $table->unsignedSmallInteger('ibu_tahun_lahir')->nullable();
            $table->string('ibu_pendidikan')->nullable();
            $table->string('ibu_pekerjaan')->nullable();
            $table->string('ibu_penghasilan')->nullable();

            // Data Wali (opsional)
            $table->string('wali_nama')->nullable();
            $table->string('wali_nik', 20)->nullable();
            $table->unsignedSmallInteger('wali_tahun_lahir')->nullable();
            $table->string('wali_hubungan')->nullable();
            $table->string('wali_pendidikan')->nullable();
            $table->string('wali_pekerjaan')->nullable();
            $table->string('wali_penghasilan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_families');
    }
};
