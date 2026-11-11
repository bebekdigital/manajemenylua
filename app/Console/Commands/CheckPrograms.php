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
        $status = DB::table('students')->selectRaw('status_keaktifan, count(1) as total')->groupBy('status_keaktifan')->get();
        $this->table(['status_keaktifan', 'total'], $status->map(fn($r) => (array)$r)->toArray());

        $unitAndStatus = DB::table('students')->selectRaw('unit, status_keaktifan, count(1) as total')->groupBy('unit', 'status_keaktifan')->get();
        $this->table(['unit', 'status_keaktifan', 'total'], $unitAndStatus->map(fn($r) => (array)$r)->toArray());

        return 0;
    }
}
