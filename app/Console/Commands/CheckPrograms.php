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
        $this->info("Student count: " . DB::table('students')->count());
        $this->info("Student Academic Record count: " . DB::table('student_academic_records')->count());
        $this->info("Sample student_academic_records with program:");
        $sample = DB::table('student_academic_records')
            ->select('id', 'student_id', 'classroom_id', 'program')
            ->limit(10)
            ->get();
        $this->table(['id', 'student_id', 'classroom_id', 'program'], $sample->map(fn($r) => (array)$r)->toArray());

        $this->info("Distinct programs in academic records:");
        $distinct = DB::table('student_academic_records')->selectRaw('COALESCE(program, "NULL") as prog, count(1) as total')->groupBy('prog')->get();
        $this->table(['prog', 'total'], $distinct->map(fn($r) => (array)$r)->toArray());

        $this->info("Classrooms:");
        $classes = DB::table('classrooms')->select('id', 'name', 'unit', 'jenjang')->get();
        $this->table(['id', 'name', 'unit', 'jenjang'], $classes->map(fn($r) => (array)$r)->toArray());

        return 0;
    }
}
