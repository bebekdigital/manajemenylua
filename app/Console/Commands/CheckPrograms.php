<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use Illuminate\Console\Command;

class CheckPrograms extends Command
{
    protected $signature = 'check:programs';
    protected $description = 'Check program values';

    public function handle()
    {
        $current = AcademicYear::current();
        $this->info("AcademicYear::current(): " . ($current ? json_encode($current->toArray()) : 'NULL'));

        return 0;
    }
}
