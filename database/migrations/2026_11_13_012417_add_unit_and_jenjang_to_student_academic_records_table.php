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
            if (! Schema::hasColumn('student_academic_records', 'unit')) {
                $table->string('unit', 100)->nullable()->after('classroom_id');
            }
            if (! Schema::hasColumn('student_academic_records', 'jenjang')) {
                $table->string('jenjang', 20)->nullable()->after('unit');
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
            if (Schema::hasColumn('student_academic_records', 'unit')) {
                $cols[] = 'unit';
            }
            if (Schema::hasColumn('student_academic_records', 'jenjang')) {
                $cols[] = 'jenjang';
            }
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
