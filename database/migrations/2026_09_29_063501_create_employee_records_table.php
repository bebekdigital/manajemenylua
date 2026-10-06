<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();

            // Kolom disesuaikan dengan urutan Template Excel Sheet 3
            $table->string('jenis_kepegawaian')->nullable();
            $table->string('status_keaktifan')->nullable();
            $table->string('keterangan_tidak_aktif')->nullable();
            $table->string('jenjang_kepegawaian')->nullable();
            $table->date('tmt')->nullable();
            $table->date('tst_jenjang')->nullable();
            $table->string('masa_kerja')->nullable();
            $table->string('keaktifan_dapodik')->nullable();
            $table->string('unit_keaktifan_dapodik')->nullable();
            $table->string('unit_kerja')->nullable();
            $table->string('jabatan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_records');
    }
};
