<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'jenjang')) {
                $table->string('jenjang', 10)->nullable()->after('no_kk');
            }
            if (! Schema::hasColumn('students', 'desil')) {
                $table->unsignedTinyInteger('desil')->nullable()->after('nama_di_kip');
            }
            if (! Schema::hasColumn('students', 'status_pip')) {
                $table->boolean('status_pip')->default(false)->after('desil');
            }
            if (! Schema::hasColumn('students', 'pip_keterangan')) {
                $table->string('pip_keterangan')->nullable()->after('status_pip');
            }
            if (! Schema::hasColumn('students', 'status_kip')) {
                $table->boolean('status_kip')->default(false)->after('pip_keterangan');
            }
            if (! Schema::hasColumn('students', 'no_kip')) {
                $table->string('no_kip')->nullable()->after('status_kip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['jenjang', 'desil', 'status_pip', 'pip_keterangan', 'status_kip', 'no_kip']);
        });
    }
};
