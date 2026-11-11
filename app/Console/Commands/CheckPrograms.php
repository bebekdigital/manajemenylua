<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckPrograms extends Command
{
    protected $signature = 'check:programs';
    protected $description = 'Check program values';

    public function handle()
    {
        $this->info("Distributions of unit:");
        $units = DB::table('students')->selectRaw('unit, count(1) as total')->groupBy('unit')->get();
        $this->table(['unit', 'total'], $units->map(fn($r) => (array)$r)->toArray());

        $this->info("Distributions of jenis_tinggal:");
        $jt = DB::table('students')->selectRaw('jenis_tinggal, count(1) as total')->groupBy('jenis_tinggal')->get();
        $this->table(['jenis_tinggal', 'total'], $jt->map(fn($r) => (array)$r)->toArray());

        return 0;
    }
}
