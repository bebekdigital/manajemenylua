<?php

use App\Services\StudentStatusService;
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
        // 1. Ubah default student_status menjadi 'Aktif' (bukan 'Naik Kelas')
        Schema::table('student_academic_records', function (Blueprint $table) {
            $table->string('student_status', 50)->default('Aktif')->change();
        });

        // 2. Kalkulasi dan perbarui seluruh baris student_academic_records dari data logika sistem
        StudentStatusService::syncAll();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_academic_records', function (Blueprint $table) {
            $table->string('student_status', 50)->default('Naik Kelas')->change();
        });
    }
};
