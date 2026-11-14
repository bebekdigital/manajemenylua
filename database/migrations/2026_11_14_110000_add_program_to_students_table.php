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
            if (! Schema::hasColumn('students', 'program')) {
                $table->string('program', 100)->nullable()->after('unit');
            }
        });

        // Sinkronisasi data program yang sudah ada di riwayat akademik ke tabel students
        if (Schema::hasTable('student_academic_records') && Schema::hasColumn('student_academic_records', 'program')) {
            DB::statement("
                UPDATE students s
                INNER JOIN (
                    SELECT student_id, program
                    FROM student_academic_records
                    WHERE program IS NOT NULL AND program != ''
                    ORDER BY id DESC
                ) r ON s.id = r.student_id
                SET s.program = r.program
                WHERE s.program IS NULL OR s.program = ''
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'program')) {
                $table->dropColumn('program');
            }
        });
    }
};
