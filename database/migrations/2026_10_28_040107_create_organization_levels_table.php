<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('organization_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('level')->unique();
            $table->string('name');
            $table->string('subtitle')->nullable();
            $table->timestamps();
        });

        // Seed 5 default levels
        $defaultLevels = [
            [
                'level' => 1,
                'name' => 'Dewan Pembina & Pengawas Yayasan',
                'subtitle' => 'Pemegang otoritas tertinggi, arahan strategis, dan pengawasan yayasan',
            ],
            [
                'level' => 2,
                'name' => 'Pengurus Harian & Direksi Yayasan',
                'subtitle' => 'Pimpinan harian & direksi: Ketua Umum, Sekretaris, Bendahara, Direktur Eksekutif',
            ],
            [
                'level' => 3,
                'name' => 'Kepala Bidang Yayasan',
                'subtitle' => 'Pimpinan bidang fungsional: Pendidikan, SDM, Keuangan, Sarpras, Dakwah & Humas',
            ],
            [
                'level' => 4,
                'name' => 'Koordinator & Kepala Bagian / Unit',
                'subtitle' => 'Koordinator operasional program kerja, pengawas sekolah, dan penjamin teknis',
            ],
            [
                'level' => 5,
                'name' => 'Staf Pelaksana & Tim Teknis',
                'subtitle' => 'Staf pelaksana administrasi, logistik, pengadaan, dan teknis operasional harian',
            ],
        ];

        foreach ($defaultLevels as $lvl) {
            DB::table('organization_levels')->updateOrInsert(
                ['level' => $lvl['level']],
                array_merge($lvl, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_levels');
    }
};
