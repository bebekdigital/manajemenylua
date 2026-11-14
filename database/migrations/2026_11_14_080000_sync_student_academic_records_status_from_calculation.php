<?php

use App\Services\StudentStatusService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ubah default student_status menjadi 'Aktif' (bukan 'Naik Kelas')
        DB::statement("ALTER TABLE student_academic_records MODIFY student_status VARCHAR(50) DEFAULT 'Aktif'");

        // 2. Kalkulasi dan perbarui seluruh baris student_academic_records dari data logika sistem
        StudentStatusService::syncAll();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE student_academic_records MODIFY student_status VARCHAR(50) DEFAULT 'Naik Kelas'");
    }
};
