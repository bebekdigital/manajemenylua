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
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'tmt_pengangkatan')) {
                $table->date('tmt_pengangkatan')->nullable()->after('nik');
            }
        });

        Schema::table('employee_records', function (Blueprint $table) {
            if (Schema::hasColumn('employee_records', 'masa_kerja')) {
                $table->dropColumn('masa_kerja');
            }

            if (Schema::hasColumn('employee_records', 'tmt') && ! Schema::hasColumn('employee_records', 'tmt_kenaikan_jenjang')) {
                $table->renameColumn('tmt', 'tmt_kenaikan_jenjang');
            } elseif (! Schema::hasColumn('employee_records', 'tmt_kenaikan_jenjang')) {
                $table->date('tmt_kenaikan_jenjang')->nullable()->after('jenjang_kepegawaian');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'tmt_pengangkatan')) {
                $table->dropColumn('tmt_pengangkatan');
            }
        });

        Schema::table('employee_records', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_records', 'masa_kerja')) {
                $table->string('masa_kerja')->nullable()->after('tst_jenjang');
            }

            if (Schema::hasColumn('employee_records', 'tmt_kenaikan_jenjang') && ! Schema::hasColumn('employee_records', 'tmt')) {
                $table->renameColumn('tmt_kenaikan_jenjang', 'tmt');
            }
        });
    }
};
