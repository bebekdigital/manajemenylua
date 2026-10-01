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
            $table->string('jenis_tinggal')->nullable();
            $table->string('alat_transportasi')->nullable();
            $table->string('email')->nullable();
            $table->string('nama_di_kip')->nullable();
            $table->string('kebutuhan_khusus')->nullable();
            $table->string('jarak_rumah')->nullable();
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'jenis_tinggal',
                'alat_transportasi',
                'email',
                'nama_di_kip',
                'kebutuhan_khusus',
                'jarak_rumah',
                'rt',
                'rw',
            ]);
        });
    }
};
