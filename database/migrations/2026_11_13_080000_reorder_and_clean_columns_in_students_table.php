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
        if (! Schema::hasTable('students')) {
            return;
        }

        // 1. Data Preservation: Salin nilai rt_rw ke rt dan rw jika rt / rw masih kosong
        if (Schema::hasColumn('students', 'rt_rw')) {
            DB::table('students')
                ->whereNotNull('rt_rw')
                ->where('rt_rw', '!=', '')
                ->where(function ($q) {
                    $q->whereNull('rt')->orWhere('rt', '')
                        ->orWhereNull('rw')->orWhere('rw', '');
                })
                ->get()
                ->each(function ($student) {
                    if (preg_match('/(?:RT\s*)?(\d+)\s*[\/\-]\s*(?:RW\s*)?(\d+)/i', $student->rt_rw, $matches)) {
                        DB::table('students')->where('id', $student->id)->update([
                            'rt' => $student->rt ?: $matches[1],
                            'rw' => $student->rw ?: $matches[2],
                        ]);
                    }
                });

            // Hapus kolom rt_rw
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('rt_rw');
            });
        }

        // 2. Reorder columns pada MySQL
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE `students`
                MODIFY COLUMN `angkatan` VARCHAR(255) NULL AFTER `nipd`,
                MODIFY COLUMN `rt` VARCHAR(5) NULL AFTER `jalan`,
                MODIFY COLUMN `rw` VARCHAR(5) NULL AFTER `rt`,
                MODIFY COLUMN `status_keaktifan` VARCHAR(50) DEFAULT 'Aktif' NULL AFTER `status_registrasi`,
                MODIFY COLUMN `keterangan_tidak_aktif` VARCHAR(255) NULL AFTER `status_keaktifan`,
                MODIFY COLUMN `tanggal_tidak_aktif` DATE NULL AFTER `keterangan_tidak_aktif`
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        if (! Schema::hasColumn('students', 'rt_rw')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('rt_rw', 20)->nullable()->after('jalan');
            });
        }
    }
};
