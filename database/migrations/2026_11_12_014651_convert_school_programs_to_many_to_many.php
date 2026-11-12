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
        // 1. Buat tabel pivot school_unit_program jika belum ada
        if (! Schema::hasTable('school_unit_program')) {
            Schema::create('school_unit_program', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_unit_id')->constrained()->cascadeOnDelete();
                $table->foreignId('school_program_id')->constrained('school_programs')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['school_unit_id', 'school_program_id']);
            });
        }

        // 2. Migrasikan data yang ada dari school_programs ke pivot table
        if (Schema::hasTable('school_programs') && Schema::hasColumn('school_programs', 'school_unit_id')) {
            $existingRows = DB::table('school_programs')->orderBy('id')->get();
            $masterMap = []; // lowercase_name => master_id

            foreach ($existingRows as $row) {
                $cleanName = trim($row->name);
                $key = strtolower($cleanName);

                if (! isset($masterMap[$key])) {
                    $masterMap[$key] = $row->id;
                }

                $masterId = $masterMap[$key];

                if (! empty($row->school_unit_id)) {
                    DB::table('school_unit_program')->updateOrInsert([
                        'school_unit_id' => $row->school_unit_id,
                        'school_program_id' => $masterId,
                    ], [
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ]);
                }
            }

            // Hapus duplikat program di tabel master (pertahankan baris pertama yang menjadi masterId)
            $keepIds = array_values($masterMap);
            if (! empty($keepIds)) {
                DB::table('school_programs')->whereNotIn('id', $keepIds)->delete();
            }

            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                Schema::disableForeignKeyConstraints();
                Schema::create('school_programs_temp', function (Blueprint $table) {
                    $table->id();
                    $table->string('name')->unique();
                    $table->timestamps();
                });
                DB::statement('INSERT INTO school_programs_temp (id, name, created_at, updated_at) SELECT id, name, created_at, updated_at FROM school_programs');
                Schema::drop('school_programs');
                Schema::rename('school_programs_temp', 'school_programs');
                Schema::enableForeignKeyConstraints();
            } else {
                try {
                    Schema::table('school_programs', function (Blueprint $table) {
                        $table->dropForeign(['school_unit_id']);
                    });
                } catch (Throwable $e) {
                }

                try {
                    Schema::table('school_programs', function (Blueprint $table) {
                        $table->dropUnique(['school_unit_id', 'name']);
                    });
                } catch (Throwable $e) {
                }

                Schema::table('school_programs', function (Blueprint $table) {
                    $table->dropColumn('school_unit_id');
                    $table->unique('name');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('school_programs') && ! Schema::hasColumn('school_programs', 'school_unit_id')) {
            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                Schema::disableForeignKeyConstraints();
                Schema::create('school_programs_temp', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('school_unit_id')->nullable();
                    $table->string('name');
                    $table->timestamps();
                });
                DB::statement('INSERT INTO school_programs_temp (id, name, created_at, updated_at) SELECT id, name, created_at, updated_at FROM school_programs');
                Schema::drop('school_programs');
                Schema::rename('school_programs_temp', 'school_programs');
                Schema::enableForeignKeyConstraints();
            } else {
                Schema::table('school_programs', function (Blueprint $table) {
                    $table->dropUnique(['name']);
                    $table->foreignId('school_unit_id')->nullable()->after('id');
                });
            }

            if (Schema::hasTable('school_unit_program')) {
                $pivots = DB::table('school_unit_program')->get();
                foreach ($pivots as $pivot) {
                    DB::table('school_programs')
                        ->where('id', $pivot->school_program_id)
                        ->update(['school_unit_id' => $pivot->school_unit_id]);
                }
            }
        }

        Schema::dropIfExists('school_unit_program');
    }
};
