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
        // 1. Pastikan tabel departments & department_positions ada
        if (Schema::hasTable('departments') && Schema::hasTable('department_positions')) {
            if (Schema::hasTable('school_units')) {
                $schoolUnits = DB::table('school_units')->get();

                foreach ($schoolUnits as $unit) {
                    // Cari atau buat department dengan nama yang sama dengan school_unit
                    $dept = DB::table('departments')->where('name', $unit->name)->first();
                    if (! $dept) {
                        $deptId = DB::table('departments')->insertGetId([
                            'name' => $unit->name,
                            'order' => 10,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $deptId = $dept->id;
                    }

                    // Cari parent untuk posisi di unit ini (Ketua Badan Pelaksana untuk Kepala Sekolah)
                    $parentLeader = DB::table('department_positions')
                        ->where('name', 'like', '%Ketua Badan Pelaksana%')
                        ->first();
                    $parentLeaderId = $parentLeader ? $parentLeader->id : null;

                    // Cari apakah di unit ini sudah ada jabatan 'Kepala Sekolah'
                    $kepalaSekolah = DB::table('department_positions')
                        ->where('department_id', $deptId)
                        ->where('name', 'Kepala Sekolah')
                        ->first();

                    $kepalaSekolahId = $kepalaSekolah ? $kepalaSekolah->id : null;

                    // Ambil posisi yang terhubung dari school_unit_positions jika ada
                    $positionsToMigrate = [];
                    if (Schema::hasTable('school_unit_positions') && Schema::hasTable('school_positions')) {
                        $positionsToMigrate = DB::table('school_unit_positions')
                            ->join('school_positions', 'school_unit_positions.school_position_id', '=', 'school_positions.id')
                            ->where('school_unit_positions.school_unit_id', $unit->id)
                            ->select('school_positions.name')
                            ->pluck('name')
                            ->toArray();
                    }

                    // Jika tidak ada data pivot tapi ada school_positions master, ambil semua school_positions
                    if (empty($positionsToMigrate) && Schema::hasTable('school_positions')) {
                        $positionsToMigrate = DB::table('school_positions')->pluck('name')->toArray();
                    }

                    // Standard default jika kosong sama sekali
                    if (empty($positionsToMigrate)) {
                        $positionsToMigrate = [
                            'Kepala Sekolah',
                            'Wakil Kepala Sekolah',
                            'Guru Kelas',
                            'Guru Mata Pelajaran',
                            'Guru',
                            'Wali Kelas',
                            'Tenaga Administrasi (TU)',
                            'Bendahara Unit',
                        ];
                    }

                    // Migrasikan setiap posisi ke department_positions
                    foreach ($positionsToMigrate as $posName) {
                        $exists = DB::table('department_positions')
                            ->where('department_id', $deptId)
                            ->where('name', $posName)
                            ->exists();

                        if (! $exists) {
                            $isKepala = strcasecmp($posName, 'Kepala Sekolah') === 0;
                            $isWakil = stripos($posName, 'Wakil Kepala') !== false;

                            $level = $isKepala ? 3 : ($isWakil ? 3 : 4);
                            $parentId = $isKepala ? $parentLeaderId : $kepalaSekolahId;

                            $newPosId = DB::table('department_positions')->insertGetId([
                                'department_id' => $deptId,
                                'name' => $posName,
                                'level' => $level,
                                'parent_id' => $parentId,
                                'order' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            if ($isKepala && ! $kepalaSekolahId) {
                                $kepalaSekolahId = $newPosId;
                            }
                        }
                    }
                }
            }
        }

        // 2. Drop tabel school_unit_positions dan school_positions
        Schema::dropIfExists('school_unit_positions');
        Schema::dropIfExists('school_positions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('school_positions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('school_unit_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_unit_id')->constrained('school_units')->cascadeOnDelete();
            $table->foreignId('school_position_id')->constrained('school_positions')->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->unique(['school_unit_id', 'school_position_id']);
        });
    }
};
