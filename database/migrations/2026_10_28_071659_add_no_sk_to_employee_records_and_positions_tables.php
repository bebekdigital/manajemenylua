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
        Schema::table('employee_records', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_records', 'no_sk')) {
                $table->string('no_sk', 100)->nullable()->after('jenjang_kepegawaian');
            }
        });

        Schema::table('employee_positions', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_positions', 'no_sk')) {
                $table->string('no_sk', 100)->nullable()->after('jenis_kepegawaian');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_records', function (Blueprint $table) {
            if (Schema::hasColumn('employee_records', 'no_sk')) {
                $table->dropColumn('no_sk');
            }
        });

        Schema::table('employee_positions', function (Blueprint $table) {
            if (Schema::hasColumn('employee_positions', 'no_sk')) {
                $table->dropColumn('no_sk');
            }
        });
    }
};
