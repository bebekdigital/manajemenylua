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
        Schema::create('employee_permits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('tanggal')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('jenis', 100);
            $table->string('detail', 255)->nullable();
            $table->text('keterangan')->nullable();
            $table->string('lampiran_path', 255)->nullable();
            $table->string('lampiran_nama', 255)->nullable();
            $table->string('status', 50)->default('diajukan');
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_permits');
    }
};
