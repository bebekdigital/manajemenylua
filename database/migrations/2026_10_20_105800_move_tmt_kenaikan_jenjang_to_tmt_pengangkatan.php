<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('employees') || ! Schema::hasTable('employee_records')) {
            return;
        }

        if (! Schema::hasColumn('employees', 'tmt_pengangkatan')) {
            return;
        }

        $sourceCol = Schema::hasColumn('employee_records', 'tmt_kenaikan_jenjang')
            ? 'tmt_kenaikan_jenjang'
            : (Schema::hasColumn('employee_records', 'tmt') ? 'tmt' : null);

        if (! $sourceCol) {
            return;
        }

        // Pindahkan data TMT paling awal dari employee_records ke employees.tmt_pengangkatan
        $employees = DB::table('employees')->get();
        foreach ($employees as $emp) {
            $earliestTmt = DB::table('employee_records')
                ->where('employee_id', $emp->id)
                ->whereNotNull($sourceCol)
                ->orderBy($sourceCol, 'asc')
                ->value($sourceCol);

            if ($earliestTmt) {
                DB::table('employees')
                    ->where('id', $emp->id)
                    ->update(['tmt_pengangkatan' => $earliestTmt]);
            }
        }

        // Kosongkan tmt_kenaikan_jenjang di employee_records karena data tersebut adalah TMT Pengangkatan
        DB::table('employee_records')
            ->whereNotNull($sourceCol)
            ->update([$sourceCol => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data migration does not need rollback
    }
};
