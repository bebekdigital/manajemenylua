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
        Schema::table('student_academic_records', function (Blueprint $table) {
            if (! Schema::hasColumn('student_academic_records', 'desil')) {
                $table->unsignedTinyInteger('desil')->nullable()->after('tanggal_tidak_aktif');
            }
            if (! Schema::hasColumn('student_academic_records', 'status_pip')) {
                $table->boolean('status_pip')->default(false)->after('desil');
            }
            if (! Schema::hasColumn('student_academic_records', 'pip_keterangan')) {
                $table->string('pip_keterangan')->nullable()->after('status_pip');
            }
            if (! Schema::hasColumn('student_academic_records', 'status_kip')) {
                $table->boolean('status_kip')->default(false)->after('pip_keterangan');
            }
            if (! Schema::hasColumn('student_academic_records', 'no_kip')) {
                $table->string('no_kip')->nullable()->after('status_kip');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_academic_records', function (Blueprint $table) {
            $cols = [];
            foreach (['desil', 'status_pip', 'pip_keterangan', 'status_kip', 'no_kip'] as $col) {
                if (Schema::hasColumn('student_academic_records', $col)) {
                    $cols[] = $col;
                }
            }
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
