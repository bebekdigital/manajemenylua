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
        $this->info("=== student_academic_records ===");
        $records = DB::table('student_academic_records')
            ->select('program', DB::raw('count(1) as total'))
            ->groupBy('program')
            ->get();
        $this->table(['program', 'total'], $records->map(fn($r) => (array)$r)->toArray());

        $this->info("=== students ===");
        if (\Illuminate\Support\Facades\Schema::hasColumn('students', 'program')) {
            $students = DB::table('students')
                ->select('program', DB::raw('count(1) as total'))
                ->groupBy('program')
                ->get();
            $this->table(['program', 'total'], $students->map(fn($r) => (array)$r)->toArray());
        } else {
            $this->info("Column program does not exist in students table.");
        }

        $this->info("=== school_programs ===");
        $schoolPrograms = DB::table('school_programs')->get();
        $this->table(['id', 'school_unit_id', 'name'], $schoolPrograms->map(fn($r) => ['id' => $r->id, 'school_unit_id' => $r->school_unit_id, 'name' => $r->name])->toArray());

        return 0;
    }
}
