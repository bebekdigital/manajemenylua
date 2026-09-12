<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'jenjang',
                'tingkat',
                'kelas',
                'desil',
                'status_pip',
                'pip_keterangan',
                'status_kip',
                'no_kip',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('jenjang', 10)->nullable()->after('program');
            $table->unsignedTinyInteger('tingkat')->nullable()->after('jenjang');
            $table->string('kelas', 10)->nullable()->after('tingkat');
            $table->unsignedTinyInteger('desil')->nullable()->after('provinsi');
            $table->boolean('status_pip')->default(false)->after('desil');
            $table->string('pip_keterangan')->nullable()->after('status_pip');
            $table->boolean('status_kip')->default(false)->after('pip_keterangan');
            $table->string('no_kip')->nullable()->after('status_kip');
        });
    }
};
