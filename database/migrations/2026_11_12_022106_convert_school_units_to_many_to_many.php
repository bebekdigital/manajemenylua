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
        // 1. Buat tabel pivot school_level_unit jika belum ada
        if (! Schema::hasTable('school_level_unit')) {
            Schema::create('school_level_unit', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_level_id')->constrained('school_levels')->cascadeOnDelete();
                $table->foreignId('school_unit_id')->constrained('school_units')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['school_level_id', 'school_unit_id']);
            });
        }

        // 2. Salin relasi yang ada dari school_units ke pivot table
        if (Schema::hasTable('school_units') && Schema::hasColumn('school_units', 'school_level_id')) {
            $existingUnits = DB::table('school_units')->whereNotNull('school_level_id')->get();
            foreach ($existingUnits as $unit) {
                DB::table('school_level_unit')->updateOrInsert([
                    'school_level_id' => $unit->school_level_id,
                    'school_unit_id' => $unit->id,
                ], [
                    'created_at' => $unit->created_at ?? now(),
                    'updated_at' => $unit->updated_at ?? now(),
                ]);
            }

            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                Schema::disableForeignKeyConstraints();
                Schema::create('school_units_temp', function (Blueprint $table) {
                    $table->id();
                    $table->string('name')->unique();
                    $table->timestamps();
                });
                DB::statement('INSERT INTO school_units_temp (id, name, created_at, updated_at) SELECT id, name, created_at, updated_at FROM school_units');
                Schema::drop('school_units');
                Schema::rename('school_units_temp', 'school_units');
                Schema::enableForeignKeyConstraints();
            } else {
                try {
                    Schema::table('school_units', function (Blueprint $table) {
                        $table->dropForeign(['school_level_id']);
                    });
                } catch (Throwable $e) {
                }

                Schema::table('school_units', function (Blueprint $table) {
                    $table->dropColumn('school_level_id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('school_units') && ! Schema::hasColumn('school_units', 'school_level_id')) {
            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                Schema::disableForeignKeyConstraints();
                Schema::create('school_units_temp', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('school_level_id')->nullable();
                    $table->string('name')->unique();
                    $table->timestamps();
                });
                DB::statement('INSERT INTO school_units_temp (id, name, created_at, updated_at) SELECT id, name, created_at, updated_at FROM school_units');
                Schema::drop('school_units');
                Schema::rename('school_units_temp', 'school_units');
                Schema::enableForeignKeyConstraints();
            } else {
                Schema::table('school_units', function (Blueprint $table) {
                    $table->foreignId('school_level_id')->nullable()->after('id');
                });
            }

            if (Schema::hasTable('school_level_unit')) {
                $pivots = DB::table('school_level_unit')->get();
                foreach ($pivots as $pivot) {
                    DB::table('school_units')
                        ->where('id', $pivot->school_unit_id)
                        ->update(['school_level_id' => $pivot->school_level_id]);
                }
            }
        }

        Schema::dropIfExists('school_level_unit');
    }
};
