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
        Schema::table('student_families', function (Blueprint $table) {
            // Status hidup/meninggal ayah & ibu
            $table->string('ayah_status')->nullable()->after('ayah_penghasilan'); // 'Hidup' | 'Meninggal'
            $table->string('ibu_status')->nullable()->after('ibu_penghasilan');   // 'Hidup' | 'Meninggal'

            // Kepemilikan bisnis/usaha keluarga
            $table->boolean('has_bisnis')->default(false)->after('ibu_status');
            $table->string('jenis_bisnis')->nullable()->after('has_bisnis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_families', function (Blueprint $table) {
            $table->dropColumn(['ayah_status', 'ibu_status', 'has_bisnis', 'jenis_bisnis']);
        });
    }
};
