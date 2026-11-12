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
        if (Schema::hasTable('students') && ! Schema::hasColumn('students', 'unit_pesantren')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('unit_pesantren', 100)->nullable()->after('unit');
            });
        }

        if (Schema::hasTable('student_academic_records') && ! Schema::hasColumn('student_academic_records', 'unit_pesantren')) {
            Schema::table('student_academic_records', function (Blueprint $table) {
                $table->string('unit_pesantren', 100)->nullable()->after('program');
            });
        }

        // Migrasikan data siswa boarding ke PPTQ Ulil Albab
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'unit_pesantren')) {
            // 1. Siswa yang unitnya PPTQ dan jenjangnya SMP
            DB::table('students')
                ->where('unit', 'like', '%PPTQ%')
                ->where('jenjang', 'like', '%SMP%')
                ->update([
                    'unit' => 'SMP IT Tahfidzul Quran Ulil Albab Karanganyar',
                    'unit_pesantren' => 'PPTQ Ulil Albab',
                ]);

            // 2. Siswa yang programnya Boarding
            DB::table('students')
                ->where(function ($q) {
                    $q->where('program', 'like', '%Boarding%')
                        ->orWhere('program', 'like', '%boarding%');
                })
                ->update(['unit_pesantren' => 'PPTQ Ulil Albab']);
        }

        if (Schema::hasTable('student_academic_records') && Schema::hasColumn('student_academic_records', 'unit_pesantren')) {
            DB::table('student_academic_records')
                ->where(function ($q) {
                    $q->where('program', 'like', '%Boarding%')
                        ->orWhere('program', 'like', '%boarding%');
                })
                ->update(['unit_pesantren' => 'PPTQ Ulil Albab']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'unit_pesantren')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('unit_pesantren');
            });
        }

        if (Schema::hasTable('student_academic_records') && Schema::hasColumn('student_academic_records', 'unit_pesantren')) {
            Schema::table('student_academic_records', function (Blueprint $table) {
                $table->dropColumn('unit_pesantren');
            });
        }
    }
};
