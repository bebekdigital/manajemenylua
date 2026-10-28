<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_academic_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();

            // Data akademik
            $table->string('jenjang', 10)->nullable(); // SD / SMP
            $table->unsignedTinyInteger('tingkat')->nullable(); // 1-12
            $table->enum('student_status', [
                'aktif',
                'mutasi_masuk',
                'mutasi_keluar',
                'lulus',
                'mengulang',
                'dropout',
            ])->default('aktif');

            // Kesejahteraan
            $table->unsignedTinyInteger('desil')->nullable();
            $table->boolean('status_pip')->default(false);
            $table->string('pip_keterangan')->nullable();
            $table->boolean('status_kip')->default(false);
            $table->string('no_kip')->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_academic_records');
    }
};
