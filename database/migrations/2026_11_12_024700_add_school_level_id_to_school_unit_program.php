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
        if (Schema::hasTable('school_unit_program') && ! Schema::hasColumn('school_unit_program', 'school_level_id')) {
            Schema::table('school_unit_program', function (Blueprint $table) {
                $table->foreignId('school_level_id')->nullable()->after('id')->constrained('school_levels')->cascadeOnDelete();
            });

            // Isi school_level_id untuk data yang sudah ada berdasarkan school_level_unit
            $pivots = DB::table('school_unit_program')->get();
            $defaultLevelId = DB::table('school_levels')->orderBy('order')->orderBy('id')->value('id');

            foreach ($pivots as $pivot) {
                // Ambil level pertama dari school_level_unit yang sesuai dengan unit_id
                $assignedLevelId = DB::table('school_level_unit')
                    ->where('school_unit_id', $pivot->school_unit_id)
                    ->orderBy('school_level_id', 'asc')
                    ->value('school_level_id');

                $finalLevelId = $assignedLevelId ?: $defaultLevelId;

                if ($finalLevelId) {
                    DB::table('school_unit_program')
                        ->where('id', $pivot->id)
                        ->update(['school_level_id' => $finalLevelId]);
                }
            }

            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                Schema::disableForeignKeyConstraints();
                Schema::create('school_unit_program_temp', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('school_level_id')->constrained('school_levels')->cascadeOnDelete();
                    $table->foreignId('school_unit_id')->constrained('school_units')->cascadeOnDelete();
                    $table->foreignId('school_program_id')->constrained('school_programs')->cascadeOnDelete();
                    $table->timestamps();

                    $table->unique(['school_level_id', 'school_unit_id', 'school_program_id'], 'lvl_unit_prog_unique');
                });

                DB::statement('INSERT INTO school_unit_program_temp (id, school_level_id, school_unit_id, school_program_id, created_at, updated_at) SELECT id, school_level_id, school_unit_id, school_program_id, created_at, updated_at FROM school_unit_program');
                Schema::drop('school_unit_program');
                Schema::rename('school_unit_program_temp', 'school_unit_program');
                Schema::enableForeignKeyConstraints();
            } else {
                try {
                    Schema::table('school_unit_program', function (Blueprint $table) {
                        $table->dropUnique(['school_unit_id', 'school_program_id']);
                    });
                } catch (Throwable $e) {
                }

                Schema::table('school_unit_program', function (Blueprint $table) {
                    $table->unique(['school_level_id', 'school_unit_id', 'school_program_id'], 'lvl_unit_prog_unique');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('school_unit_program') && Schema::hasColumn('school_unit_program', 'school_level_id')) {
            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                Schema::disableForeignKeyConstraints();
                Schema::create('school_unit_program_temp', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('school_unit_id')->constrained('school_units')->cascadeOnDelete();
                    $table->foreignId('school_program_id')->constrained('school_programs')->cascadeOnDelete();
                    $table->timestamps();

                    $table->unique(['school_unit_id', 'school_program_id']);
                });

                DB::statement('INSERT INTO school_unit_program_temp (id, school_unit_id, school_program_id, created_at, updated_at) SELECT id, school_unit_id, school_program_id, created_at, updated_at FROM school_unit_program');
                Schema::drop('school_unit_program');
                Schema::rename('school_unit_program_temp', 'school_unit_program');
                Schema::enableForeignKeyConstraints();
            } else {
                try {
                    Schema::table('school_unit_program', function (Blueprint $table) {
                        $table->dropUnique('lvl_unit_prog_unique');
                    });
                } catch (Throwable $e) {
                }

                Schema::table('school_unit_program', function (Blueprint $table) {
                    $table->dropForeign(['school_level_id']);
                    $table->dropColumn('school_level_id');
                    $table->unique(['school_unit_id', 'school_program_id']);
                });
            }
        }
    }
};
