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
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'status_keaktifan')) {
                $table->string('status_keaktifan', 50)->default('Aktif')->nullable();
            }
            if (! Schema::hasColumn('students', 'keterangan_tidak_aktif')) {
                $table->string('keterangan_tidak_aktif', 255)->nullable();
            }
            if (! Schema::hasColumn('students', 'tanggal_tidak_aktif')) {
                $table->date('tanggal_tidak_aktif')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('students', 'status_keaktifan')) {
                $cols[] = 'status_keaktifan';
            }
            if (Schema::hasColumn('students', 'keterangan_tidak_aktif')) {
                $cols[] = 'keterangan_tidak_aktif';
            }
            if (Schema::hasColumn('students', 'tanggal_tidak_aktif')) {
                $cols[] = 'tanggal_tidak_aktif';
            }
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
