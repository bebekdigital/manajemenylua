<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'tanggal_lulus')) {
                $table->date('tanggal_lulus')->nullable()->after('tanggal_tidak_aktif');
            }
        });

        // Migrasi data lama yang menggunakan keterangan_tidak_aktif = 'Lulus' atau tanggal_tidak_aktif untuk status Lulus
        DB::table('students')
            ->where('status_keaktifan', 'Lulus')
            ->orWhere('keterangan_tidak_aktif', 'Lulus')
            ->update([
                'status_keaktifan' => 'Lulus',
                'tanggal_lulus' => DB::raw('COALESCE(tanggal_lulus, tanggal_tidak_aktif)'),
                'keterangan_tidak_aktif' => null,
                'tanggal_tidak_aktif' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'tanggal_lulus')) {
                $table->dropColumn('tanggal_lulus');
            }
        });
    }
};
