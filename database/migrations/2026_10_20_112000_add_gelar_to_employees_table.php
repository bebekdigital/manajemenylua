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
            if (! Schema::hasColumn('employees', 'gelar_depan')) {
                $table->string('gelar_depan', 50)->nullable()->after('nama');
            }
            if (! Schema::hasColumn('employees', 'gelar_belakang')) {
                $table->string('gelar_belakang', 50)->nullable()->after('gelar_depan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'gelar_depan')) {
                $table->dropColumn('gelar_depan');
            }
            if (Schema::hasColumn('employees', 'gelar_belakang')) {
                $table->dropColumn('gelar_belakang');
            }
        });
    }
};
