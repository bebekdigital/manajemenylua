<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_academic_records', function (Blueprint $table) {
            $table->dropColumn('jenjang');
        });
    }

    public function down(): void
    {
        Schema::table('student_academic_records', function (Blueprint $table) {
            $table->string('jenjang', 10)->nullable()->after('classroom_id');
        });
    }
};
