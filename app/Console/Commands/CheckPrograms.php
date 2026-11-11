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
        $years = DB::table('academic_years')->get();
        $this->table(['id', 'name', 'semester', 'is_active', 'start_date', 'end_date'], $years->map(fn($r) => (array)$r)->toArray());

        return 0;
    }
}
