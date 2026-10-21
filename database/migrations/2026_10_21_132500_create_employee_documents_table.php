<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('category', 50)->index(); // ktp, kk, sk_pengangkatan, ijazah, sk_jenjang, sk_penugasan, lainnya
            $table->string('record_type', 50)->nullable()->index(); // record, position, education, employee
            $table->unsignedBigInteger('record_id')->nullable()->index();
            $table->string('title');
            $table->string('file_path', 500);
            $table->string('file_name');
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type', 100)->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
