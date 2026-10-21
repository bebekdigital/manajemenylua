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

        // 4. Safely migrate/preserve existing data from employee_records to employees and employee_positions
        if (Schema::hasTable('employee_records')) {
            // A. Copy tst_jenjang -> tst_kenaikan_jenjang if present
            if (Schema::hasColumn('employee_records', 'tst_jenjang')) {
                DB::table('employee_records')
                    ->whereNotNull('tst_jenjang')
                    ->whereNull('tst_kenaikan_jenjang')
                    ->update(['tst_kenaikan_jenjang' => DB::raw('tst_jenjang')]);
            }

            // B. Copy tmt -> tmt_kenaikan_jenjang if present
            if (Schema::hasColumn('employee_records', 'tmt')) {
                DB::table('employee_records')
                    ->whereNotNull('tmt')
                    ->whereNull('tmt_kenaikan_jenjang')
                    ->update(['tmt_kenaikan_jenjang' => DB::raw('tmt')]);
            }

            $records = DB::table('employee_records')->get();
            foreach ($records as $record) {
                // Move/sync status & dapodik to employees if available in record
                $updateEmpData = [];
                if (property_exists($record, 'keaktifan_dapodik') && ! empty($record->keaktifan_dapodik)) {
                    $updateEmpData['keaktifan_dapodik'] = $record->keaktifan_dapodik;
                }
                if (property_exists($record, 'unit_keaktifan_dapodik') && ! empty($record->unit_keaktifan_dapodik)) {
                    $updateEmpData['unit_keaktifan_dapodik'] = $record->unit_keaktifan_dapodik;
                }
                if (property_exists($record, 'status_keaktifan') && ! empty($record->status_keaktifan)) {
                    $updateEmpData['status_keaktifan'] = $record->status_keaktifan;
                }
                if (property_exists($record, 'keterangan_tidak_aktif') && ! empty($record->keterangan_tidak_aktif)) {
                    $updateEmpData['keterangan_tidak_aktif'] = $record->keterangan_tidak_aktif;
                }

                if (! empty($updateEmpData)) {
                    DB::table('employees')
                        ->where('id', $record->employee_id)
                        ->update($updateEmpData);
                }

                // Move position data (unit_kerja, jabatan, jenis_kepegawaian) to employee_positions
                $unitKerja = $record->unit_kerja ?? null;
                $jabatan = $record->jabatan ?? null;
                $jenisKepegawaian = $record->jenis_kepegawaian ?? null;
                $tmtJabatan = $record->tmt_kenaikan_jenjang ?? ($record->tmt ?? null);
                $tstJabatan = $record->tst_kenaikan_jenjang ?? ($record->tst_jenjang ?? null);

                if (! empty($unitKerja) || ! empty($jabatan) || ! empty($jenisKepegawaian)) {
                    $exists = DB::table('employee_positions')
                        ->where('employee_id', $record->employee_id)
                        ->where('unit_kerja', $unitKerja)
                        ->where('jabatan', $jabatan)
                        ->exists();

                    if (! $exists) {
                        DB::table('employee_positions')->insert([
                            'employee_id' => $record->employee_id,
                            'unit_kerja' => $unitKerja,
                            'jabatan' => $jabatan,
                            'jenis_kepegawaian' => $jenisKepegawaian,
                            'tmt_jabatan' => $tmtJabatan,
                            'tst_jabatan' => $tstJabatan,
                            'created_at' => $record->created_at ?? now(),
                            'updated_at' => $record->updated_at ?? now(),
                        ]);
                    }
                }
            }
        }

        // 5. Drop foreign key and moved columns from employee_records
        if (DB::getDriverName() === 'sqlite') {
            Schema::disableForeignKeyConstraints();

            Schema::create('employee_records_new', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->date('tmt_kenaikan_jenjang')->nullable();
                $table->date('tst_kenaikan_jenjang')->nullable();
                $table->string('jenjang_kepegawaian')->nullable();
                $table->timestamps();
            });

            $oldRecords = DB::table('employee_records')->get();
            if ($oldRecords->isNotEmpty()) {
                DB::table('employee_records_new')->insert(
                    $oldRecords->map(fn ($r) => [
                        'id' => $r->id,
                        'employee_id' => $r->employee_id,
                        'tmt_kenaikan_jenjang' => $r->tmt_kenaikan_jenjang ?? null,
                        'tst_kenaikan_jenjang' => $r->tst_kenaikan_jenjang ?? null,
                        'jenjang_kepegawaian' => $r->jenjang_kepegawaian ?? null,
                        'created_at' => $r->created_at ?? now(),
                        'updated_at' => $r->updated_at ?? now(),
                    ])->toArray()
                );
            }

            Schema::drop('employee_records');
            Schema::rename('employee_records_new', 'employee_records');

            Schema::enableForeignKeyConstraints();
        } else {
            // MySQL and others
            if (Schema::hasColumn('employee_records', 'academic_year_id')) {
                try {
                    Schema::table('employee_records', function (Blueprint $table) {
                        $table->dropForeign(['academic_year_id']);
                    });
                } catch (Throwable $e) {
                    // Ignore if foreign key was already dropped or has different name
                }
            }

            $columnsToDrop = [
                'academic_year_id',
                'jenis_kepegawaian',
                'status_keaktifan',
                'keterangan_tidak_aktif',
                'keaktifan_dapodik',
                'unit_keaktifan_dapodik',
                'unit_kerja',
                'jabatan',
                'tst_jenjang',
                'tmt',
                'masa_kerja',
            ];

            $existingColumnsToDrop = array_filter(
                $columnsToDrop,
                fn ($col) => Schema::hasColumn('employee_records', $col)
            );

            if (! empty($existingColumnsToDrop)) {
                Schema::table('employee_records', function (Blueprint $table) use ($existingColumnsToDrop) {
                    $table->dropColumn(array_values($existingColumnsToDrop));
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add dropped columns to employee_records if missing
        Schema::table('employee_records', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_records', 'academic_year_id')) {
                $table->unsignedBigInteger('academic_year_id')->nullable()->after('employee_id');
            }
            if (! Schema::hasColumn('employee_records', 'jenis_kepegawaian')) {
                $table->string('jenis_kepegawaian')->nullable();
            }
            if (! Schema::hasColumn('employee_records', 'status_keaktifan')) {
                $table->string('status_keaktifan')->nullable();
            }
            if (! Schema::hasColumn('employee_records', 'keterangan_tidak_aktif')) {
                $table->string('keterangan_tidak_aktif')->nullable();
            }
            if (! Schema::hasColumn('employee_records', 'tst_jenjang')) {
                $table->date('tst_jenjang')->nullable();
            }
            if (! Schema::hasColumn('employee_records', 'keaktifan_dapodik')) {
                $table->string('keaktifan_dapodik')->nullable();
            }
            if (! Schema::hasColumn('employee_records', 'unit_keaktifan_dapodik')) {
                $table->string('unit_keaktifan_dapodik')->nullable();
            }
            if (! Schema::hasColumn('employee_records', 'unit_kerja')) {
                $table->string('unit_kerja')->nullable();
            }
            if (! Schema::hasColumn('employee_records', 'jabatan')) {
                $table->string('jabatan')->nullable();
            }
        });

        Schema::dropIfExists('employee_positions');

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
