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
        Schema::table('users', function (Blueprint $table) {
            $table->string('nipy')->nullable()->after('role');
            $table->string('status_kepegawaian')->nullable()->after('nipy'); // PTY, PTTY, Honorer
            $table->string('jk', 1)->nullable()->after('status_kepegawaian'); // L / P
            $table->string('ttl')->nullable()->after('jk'); // Tempat, Tanggal Lahir
            $table->string('unit')->nullable()->after('ttl');
            $table->string('jabatan')->nullable()->after('unit');
            $table->string('no_wa')->nullable()->after('jabatan');
            $table->string('foto')->nullable()->after('no_wa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nipy', 'status_kepegawaian', 'jk', 'ttl', 'unit', 'jabatan', 'no_wa', 'foto']);
        });
    }
};
