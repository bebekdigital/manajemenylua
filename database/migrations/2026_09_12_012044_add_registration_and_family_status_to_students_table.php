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
        Schema::table('students', function (Blueprint $table) {
            $table->string('status_keluarga')->nullable()->after('agama');
            $table->integer('anak_ke')->nullable()->after('status_keluarga');
            $table->string('diterima_di_jenjang')->nullable()->after('provinsi');
            $table->date('tanggal_diterima')->nullable()->after('diterima_di_jenjang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'status_keluarga',
                'anak_ke',
                'diterima_di_jenjang',
                'tanggal_diterima',
            ]);
        });
    }
};
