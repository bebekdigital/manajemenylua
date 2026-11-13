<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('students:sync-academic-records {--inspect : Hanya inspect data tanpa mengubah DB}')]
#[Description('Sinkronisasi dan backfill data riwayat akademik siswa per tahun ajaran')]
class SyncStudentAcademicRecordsCommand extends Command
{
    public function handle()
    {
        $this->info('Checking Academic Years...');
        $years = AcademicYear::orderBy('start_date')->get();
        foreach ($years as $y) {
            $this->line("ID: {$y->id} | Name: {$y->name} | Semester: {$y->semester} | Dates: {$y->start_date?->format('Y-m-d')} s/d {$y->end_date?->format('Y-m-d')}");
        }

        $this->info("\nChecking Students Angkatan & Jenjang Distribution...");
        $studentsDist = Student::query()
            ->selectRaw('jenjang, angkatan, status_keaktifan, count(*) as count')
            ->groupBy('jenjang', 'angkatan', 'status_keaktifan')
            ->orderBy('jenjang')
            ->orderBy('angkatan')
            ->get();
        foreach ($studentsDist as $d) {
            $this->line("Jenjang: {$d->jenjang} | Angkatan: {$d->angkatan} | Status: {$d->status_keaktifan} | Count: {$d->count}");
        }

        $this->info("\nChecking Existing StudentAcademicRecords...");
        $recordDist = StudentAcademicRecord::query()
            ->selectRaw('academic_year_id, tingkat, student_status, count(*) as count')
            ->groupBy('academic_year_id', 'tingkat', 'student_status')
            ->orderBy('academic_year_id')
            ->get();
        foreach ($recordDist as $r) {
            $this->line("TA ID: {$r->academic_year_id} | Tingkat: {$r->tingkat} | Status: {$r->student_status} | Count: {$r->count}");
        }

        return 0;
    }
}
