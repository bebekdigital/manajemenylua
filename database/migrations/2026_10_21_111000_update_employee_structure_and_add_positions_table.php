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
        // 1. Update employees table with new static columns from Sheet 1
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'tst_pengangkatan')) {
                $table->date('tst_pengangkatan')->nullable()->after('tmt_pengangkatan');
            }
            if (! Schema::hasColumn('employees', 'keaktifan_dapodik')) {
                $table->string('keaktifan_dapodik', 50)->nullable()->after('tst_pengangkatan');
            }
            if (! Schema::hasColumn('employees', 'unit_keaktifan_dapodik')) {
                $table->string('unit_keaktifan_dapodik', 100)->nullable()->after('keaktifan_dapodik');
            }
            if (! Schema::hasColumn('employees', 'status_keaktifan')) {
                $table->string('status_keaktifan', 50)->default('Aktif')->nullable()->after('unit_keaktifan_dapodik');
            }
            if (! Schema::hasColumn('employees', 'keterangan_tidak_aktif')) {
                $table->string('keterangan_tidak_aktif', 255)->nullable()->after('status_keaktifan');
            }
        });

        // 2. Create employee_positions table (Sheet 4: Riwayat Jabatan)
        if (! Schema::hasTable('employee_positions')) {
            Schema::create('employee_positions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->string('unit_kerja')->nullable();
                $table->string('jabatan')->nullable();
                $table->string('jenis_kepegawaian')->nullable();
                $table->date('tmt_jabatan')->nullable();
                $table->date('tst_jabatan')->nullable();
                $table->timestamps();
            });
        }

        // 3. Update employee_records table (Sheet 3: Riwayat Kepegawaian / Jenjang)
        Schema::table('employee_records', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_records', 'tst_kenaikan_jenjang')) {
                $table->date('tst_kenaikan_jenjang')->nullable()->after('tmt_kenaikan_jenjang');
            }
        });

        // Copy existing tst_jenjang to tst_kenaikan_jenjang if present
        if (Schema::hasColumn('employee_records', 'tst_jenjang')) {
            DB::table('employee_records')
                ->whereNotNull('tst_jenjang')
                ->whereNull('tst_kenaikan_jenjang')
                ->update(['tst_kenaikan_jenjang' => DB::raw('tst_jenjang')]);
        }

        // Make academic_year_id nullable if it exists
        if (Schema::hasColumn('employee_records', 'academic_year_id')) {
            Schema::table('employee_records', function (Blueprint $table) {
                $table->unsignedBigInteger('academic_year_id')->nullable()->change();
            });
        }

        // Migrate existing status, dapodik, and position data
        if (Schema::hasTable('employee_records')) {
            $records = DB::table('employee_records')->get();
            foreach ($records as $record) {
                // Update employee status & dapodik if not already set
                DB::table('employees')
                    ->where('id', $record->employee_id)
                    ->whereNull('keaktifan_dapodik')
                    ->update([
                        'keaktifan_dapodik' => $record->keaktifan_dapodik ?? null,
                        'unit_keaktifan_dapodik' => $record->unit_keaktifan_dapodik ?? null,
                        'status_keaktifan' => $record->status_keaktifan ?? 'Aktif',
                        'keterangan_tidak_aktif' => $record->keterangan_tidak_aktif ?? null,
                    ]);

                // Populate employee_positions from existing record if unit_kerja or jabatan is present
                if (! empty($record->unit_kerja) || ! empty($record->jabatan)) {
                    $exists = DB::table('employee_positions')
                        ->where('employee_id', $record->employee_id)
                        ->where('unit_kerja', $record->unit_kerja)
                        ->where('jabatan', $record->jabatan)
                        ->exists();

                    if (! $exists) {
                        DB::table('employee_positions')->insert([
                            'employee_id' => $record->employee_id,
                            'unit_kerja' => $record->unit_kerja,
                            'jabatan' => $record->jabatan,
                            'jenis_kepegawaian' => $record->jenis_kepegawaian ?? null,
                            'tmt_jabatan' => $record->tmt_kenaikan_jenjang ?? null,
                            'tst_jabatan' => $record->tst_jenjang ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_positions');

        Schema::table('employee_records', function (Blueprint $table) {
            if (Schema::hasColumn('employee_records', 'tst_kenaikan_jenjang')) {
                $table->dropColumn('tst_kenaikan_jenjang');
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            $cols = ['tst_pengangkatan', 'keaktifan_dapodik', 'unit_keaktifan_dapodik', 'status_keaktifan', 'keterangan_tidak_aktif'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('employees', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
