<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MoveEmployeeTmtCommand extends Command
{
    protected $signature = 'employee:move-tmt {--keep : Jangan kosongkan kolom tmt_kenaikan_jenjang setelah dipindahkan}';

    protected $description = 'Pindahkan seluruh data TMT dari employee_records ke TMT Pengangkatan di tabel employees sesuai data masing-masing pegawai.';

    public function handle(): int
    {
        $this->info('Memulai pemindahan data TMT ke TMT Pengangkatan...');

        if (! Schema::hasTable('employees') || ! Schema::hasTable('employee_records')) {
            $this->error('Tabel employees atau employee_records tidak ditemukan.');

            return self::FAILURE;
        }

        if (! Schema::hasColumn('employees', 'tmt_pengangkatan')) {
            $this->error('Kolom tmt_pengangkatan belum ada di tabel employees. Jalankan "php artisan migrate" terlebih dahulu.');

            return self::FAILURE;
        }

        $sourceCol = Schema::hasColumn('employee_records', 'tmt_kenaikan_jenjang')
            ? 'tmt_kenaikan_jenjang'
            : (Schema::hasColumn('employee_records', 'tmt') ? 'tmt' : null);

        if (! $sourceCol) {
            $this->error('Kolom sumber TMT tidak ditemukan pada tabel employee_records.');

            return self::FAILURE;
        }

        $employees = DB::table('employees')->get();
        $updatedCount = 0;

        foreach ($employees as $emp) {
            $earliestTmt = DB::table('employee_records')
                ->where('employee_id', $emp->id)
                ->whereNotNull($sourceCol)
                ->orderBy($sourceCol, 'asc')
                ->value($sourceCol);

            if ($earliestTmt) {
                DB::table('employees')
                    ->where('id', $emp->id)
                    ->update([
                        'tmt_pengangkatan' => $earliestTmt,
                    ]);
                $updatedCount++;
                $this->line(" - Pegawai ID {$emp->id} ({$emp->nama}): TMT Pengangkatan diisi {$earliestTmt}");
            }
        }

        if (! $this->option('keep')) {
            $clearedCount = DB::table('employee_records')
                ->whereNotNull($sourceCol)
                ->update([$sourceCol => null]);
            $this->info("Kolom {$sourceCol} pada {$clearedCount} baris di employee_records telah dikosongkan (dipindahkan).");
        } else {
            $this->info("Opsi --keep aktif: Kolom {$sourceCol} pada employee_records tetap dipertahankan.");
        }

        $this->info("Selesai! Berhasil memindahkan data TMT Pengangkatan untuk {$updatedCount} pegawai.");

        return self::SUCCESS;
    }
}
