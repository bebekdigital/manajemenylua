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
        $units = DB::table('school_units')->get();
        $this->table(['id', 'name'], $units->map(fn($r) => ['id' => $r->id, 'name' => $r->name])->toArray());

        $progs = DB::table('school_programs')->get();
        $this->table(['id', 'school_unit_id', 'name'], $progs->map(fn($r) => ['id' => $r->id, 'school_unit_id' => $r->school_unit_id, 'name' => $r->name])->toArray());

        return 0;
    }
}
