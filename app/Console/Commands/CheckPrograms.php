<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckPrograms extends Command
{
    protected $signature = 'check:programs';
    protected $description = 'Check program values';

    public function handle()
    {
        $cols = Schema::getColumnListing('students');
        $this->info("Students columns: " . implode(', ', $cols));
        
        $firstStudent = DB::table('students')->first();
        $this->info("First student data: " . json_encode($firstStudent, JSON_PRETTY_PRINT));

        return 0;
    }
}
