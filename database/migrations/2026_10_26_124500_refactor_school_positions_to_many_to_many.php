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
        // 1. Pastikan tabel master school_positions ada
        if (! Schema::hasTable('school_positions')) {
            Schema::create('school_positions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        // 2. Pastikan tabel pivot school_unit_positions ada
        if (! Schema::hasTable('school_unit_positions')) {
            Schema::create('school_unit_positions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_unit_id')->constrained('school_units')->cascadeOnDelete();
                $table->foreignId('school_position_id')->constrained('school_positions')->cascadeOnDelete();
                $table->integer('order')->default(0);
                $table->timestamps();

                $table->unique(['school_unit_id', 'school_position_id']);
            });
        }

        // 3. Jika school_positions masih memiliki kolom school_unit_id (skema lama)
        if (Schema::hasColumn('school_positions', 'school_unit_id')) {
            $existing = DB::table('school_positions')->get();
            $masterMap = [];

            foreach ($existing as $row) {
                $name = trim($row->name);
                if (! isset($masterMap[$name])) {
                    $masterMap[$name] = $row->id;
                }

                $masterId = $masterMap[$name];
                $unitId = $row->school_unit_id;

                if ($unitId) {
                    DB::table('school_unit_positions')->updateOrInsert(
                        ['school_unit_id' => $unitId, 'school_position_id' => $masterId],
                        ['order' => $row->order ?? 0, 'created_at' => now(), 'updated_at' => now()]
                    );
                }
            }

            // Hapus duplikat dari school_positions
            foreach ($existing as $row) {
                $name = trim($row->name);
                if (isset($masterMap[$name]) && $masterMap[$name] !== $row->id) {
                    DB::table('school_positions')->where('id', $row->id)->delete();
                }
            }

            Schema::table('school_positions', function (Blueprint $table) {
                $table->dropForeign(['school_unit_id']);
                $table->dropColumn('school_unit_id');
                if (Schema::hasColumn('school_positions', 'order')) {
                    $table->dropColumn('order');
                }
                $table->unique('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_unit_positions');
    }
};
