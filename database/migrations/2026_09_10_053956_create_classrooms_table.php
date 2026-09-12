<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('unit'); // SDIT / SMPIT / PPTQ
            $table->string('jenjang', 10); // SD / SMP
            $table->unsignedTinyInteger('grade'); // 1-12
            $table->string('name'); // "5A", "7B", "9-Tahfidz"
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->timestamps();

            $table->unique(['academic_year_id', 'unit', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
