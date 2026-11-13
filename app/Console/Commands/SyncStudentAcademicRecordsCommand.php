<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('students:sync-academic-records {--inspect : Hanya inspect data tanpa mengubah DB} {--test-stats : Tes eksekusi StatisticsController} {--nisn= : Periksa data siswa tertentu berdasarkan NISN}')]
#[Description('Sinkronisasi dan backfill data riwayat akademik siswa per tahun ajaran')]
class SyncStudentAcademicRecordsCommand extends Command
{
    public function handle()
    {
        if ($nisn = $this->option('nisn')) {
            $student = Student::where('nisn', $nisn)->first();
            if (! $student) {
                $this->error("Siswa dengan NISN {$nisn} tidak ditemukan.");

                return 1;
            }
            $this->info("=== Data Siswa: {$student->nama} (NISN: {$student->nisn}) ===");
            $this->line("Status Keaktifan: {$student->status_keaktifan}");
            $this->line("Keterangan Tidak Aktif: {$student->keterangan_tidak_aktif}");
            $this->line("Tanggal Tidak Aktif: {$student->tanggal_tidak_aktif}");
            $this->line("Angkatan: {$student->angkatan}");
            $this->line("Tanggal Diterima: {$student->tanggal_diterima}");
            $this->line("Unit: {$student->unit} | Pesantren: {$student->unit_pesantren}");

            $this->info('--- Riwayat di StudentAcademicRecord: ---');
            $records = StudentAcademicRecord::where('student_id', $student->id)
                ->with('academicYear')
                ->get();
            foreach ($records as $r) {
                $this->line("TA: {$r->academicYear?->name} {$r->academicYear?->semester} (ID: {$r->academic_year_id}) | Tingkat: {$r->tingkat} | Status: '{$r->student_status}' | Ket: '{$r->ket_tidak_aktif}' | Program: '{$r->program}'");
            }

            return 0;
        }
        if ($this->option('test-stats')) {
            $this->info('Checking Programs in StudentAcademicRecord:');
            $distinct = StudentAcademicRecord::select('program', DB::raw('count(*) as c'))->groupBy('program')->get();
            foreach ($distinct as $d) {
                $this->line("program: '{$d->program}' | Count: {$d->c}");
            }
            $sample = StudentAcademicRecord::select('id', 'student_id', 'program', 'unit_pesantren')->take(5)->get();
            foreach ($sample as $s) {
                $this->line("Record ID: {$s->id} | Student: {$s->student_id} | Program: {$s->program} | Pesantren: {$s->unit_pesantren}");
            }

            return 0;
        }

        $inspectOnly = (bool) $this->option('inspect');

        $this->info('Memulai sinkronisasi riwayat akademik siswa...');

        $academicYears = AcademicYear::orderBy('start_date')->get();
        if ($academicYears->isEmpty()) {
            $this->error('Tidak ada data Tahun Ajaran di database.');

            return 1;
        }

        $students = Student::all();
        $this->info("Total data siswa: {$students->count()}");

        $created = 0;
        $updated = 0;
        $deleted = 0;
        $skipped = 0;

        foreach ($students as $student) {
            $entryYear = $this->determineEntryYear($student);
            if (! $entryYear) {
                $this->warn("Siswa {$student->nama} (ID: {$student->id}) tidak dapat ditentukan tahun masuknya. Dilewati.");
                $skipped++;

                continue;
            }

            $baseGrade = $this->determineBaseGrade($student);
            $maxGrade = $baseGrade + ($student->jenjang === 'SD' ? 5 : 2); // SMP: 7-9, SMA: 10-12, SD: 1-6

            foreach ($academicYears as $year) {
                // Parse tahun ajaran utama (contoh: '2024/2025' -> 2024)
                $schoolYear = (int) explode('/', (string) $year->name)[0];

                // Jika tahun ajaran sebelum siswa masuk sekolah, siswa belum bersekolah
                if ($schoolYear < $entryYear) {
                    if (! $inspectOnly) {
                        $del = StudentAcademicRecord::where('student_id', $student->id)
                            ->where('academic_year_id', $year->id)
                            ->delete();
                        $deleted += $del;
                    }

                    continue;
                }

                $yearOffset = $schoolYear - $entryYear;
                $tingkat = $baseGrade + $yearOffset;

                // Jika sudah melebihi jenjang, siswa sudah lulus di TA tersebut
                $isAlumni = $tingkat > $maxGrade;
                $actualTingkat = min($tingkat, $maxGrade);

                // Tentukan status keaktifan di TA ini
                $studentStatus = 'aktif';
                $isInactiveGlobally = strtolower(trim((string) $student->status_keaktifan)) === 'tidak aktif';

                if ($isAlumni) {
                    $studentStatus = 'lulus';
                } elseif ($isInactiveGlobally) {
                    $ketLower = strtolower(trim((string) $student->keterangan_tidak_aktif));
                    $targetInactiveStatus = 'dropout'; // ENUM database: 'aktif','mutasi_masuk','mutasi_keluar','lulus','mengulang','dropout'
                    if (str_contains($ketLower, 'mutasi')) {
                        $targetInactiveStatus = 'mutasi_keluar';
                    } elseif (str_contains($ketLower, 'lulus')) {
                        $targetInactiveStatus = 'lulus';
                    } elseif (str_contains($ketLower, 'keluar') || str_contains($ketLower, 'drop')) {
                        $targetInactiveStatus = 'dropout';
                    } elseif (str_contains($ketLower, 'ulang')) {
                        $targetInactiveStatus = 'mengulang';
                    }

                    if ($student->tanggal_tidak_aktif) {
                        $tglTidakAktif = Carbon::parse($student->tanggal_tidak_aktif);
                        if ($year->end_date && $year->end_date->lt($tglTidakAktif)) {
                            $studentStatus = 'aktif';
                        } else {
                            $studentStatus = $targetInactiveStatus;
                        }
                    } else {
                        // Jika tidak ada tanggal_tidak_aktif: aktif di tahun lampau, tidak aktif di TA aktif sekarang (2026/2027)
                        $studentStatus = ($schoolYear >= 2026) ? $targetInactiveStatus : 'aktif';
                    }
                }

                if ($inspectOnly) {
                    continue;
                }

                $existingRecord = StudentAcademicRecord::where('student_id', $student->id)
                    ->where('academic_year_id', $year->id)
                    ->first();

                // Tentukan program: pertahankan yang ada jika valid, jika kosong tentukan berdasarkan pesantren & jenjang
                $program = $existingRecord?->program;
                if (! $program) {
                    $hasPesantren = ! empty($student->unit_pesantren) && stripos($student->unit_pesantren, 'PPTQ') !== false;
                    if ($student->jenjang === 'SMP') {
                        $program = $hasPesantren ? 'Boarding SMP' : 'Fullday SMP';
                    } elseif ($student->jenjang === 'SMA') {
                        $program = $hasPesantren ? 'Boarding SMA' : 'Fullday SMA';
                    } else {
                        $program = 'Umum SDIT';
                    }
                }

                $recordData = [
                    'student_id' => $student->id,
                    'academic_year_id' => $year->id,
                    'unit' => $student->unit,
                    'jenjang' => $student->jenjang,
                    'tingkat' => $actualTingkat,
                    'program' => $program,
                    'unit_pesantren' => $student->unit_pesantren,
                    'student_status' => $studentStatus,
                    'ket_tidak_aktif' => $studentStatus === 'aktif' ? null : $student->keterangan_tidak_aktif,
                    'tanggal_tidak_aktif' => $studentStatus === 'aktif' ? null : $student->tanggal_tidak_aktif,
                    'desil' => $student->desil,
                    'status_pip' => (bool) ($student->status_pip ?? false),
                    'pip_keterangan' => $student->pip_keterangan,
                    'status_kip' => (bool) ($student->status_kip ?? false),
                    'no_kip' => $student->no_kip,
                ];

                if ($existingRecord) {
                    $recordData['classroom_id'] = $existingRecord->classroom_id;
                    $existingRecord->update($recordData);
                    $updated++;
                } else {
                    $recordData['classroom_id'] = null;
                    StudentAcademicRecord::create($recordData);
                    $created++;
                }
            }
        }

        if ($inspectOnly) {
            $this->info("Mode inspect selesai. Ditemukan {$students->count()} siswa.");

            return 0;
        }

        $this->info("Sinkronisasi Selesai! Record Baru: {$created}, Diperbarui: {$updated}, Dihapus (sebelum masuk): {$deleted}, Dilewati: {$skipped}");

        $this->printSummary();

        return 0;
    }

    private function printSummary(): void
    {
        $this->info("\n--- Ringkasan Data Riwayat Akademik per Tahun Ajaran ---");
        $summary = DB::table('student_academic_records')
            ->join('academic_years', 'academic_years.id', '=', 'student_academic_records.academic_year_id')
            ->select(
                'academic_years.name as ta_name',
                'academic_years.semester',
                'student_academic_records.tingkat',
                'student_academic_records.student_status',
                DB::raw('count(*) as count')
            )
            ->groupBy('academic_years.name', 'academic_years.semester', 'student_academic_records.tingkat', 'student_academic_records.student_status')
            ->orderBy('academic_years.name')
            ->orderBy('academic_years.semester')
            ->orderBy('student_academic_records.tingkat')
            ->get();

        foreach ($summary as $s) {
            $this->line("TA: {$s->ta_name} ({$s->semester}) | Tingkat: {$s->tingkat} | Status: {$s->student_status} | Siswa: {$s->count}");
        }
    }

    private function determineEntryYear(Student $student): ?int
    {
        // 1. Dari tanggal_diterima
        if ($student->tanggal_diterima) {
            $year = (int) Carbon::parse($student->tanggal_diterima)->year;
            if ($year >= 2010 && $year <= 2030) {
                return $year;
            }
        }

        // 2. Dari angkatan
        if ($student->angkatan) {
            $val = (int) $student->angkatan;
            if ($val >= 2010 && $val <= 2030) {
                return $val;
            }
            // Khusus SMP Ulil Albab: Angkatan 8 = 2024, Angkatan 9 = 2025, dst (2016 + Angkatan)
            if ($student->jenjang === 'SMP' && $val >= 1 && $val <= 30) {
                return 2016 + $val;
            }
            if ($student->jenjang === 'SMA' && $val >= 1 && $val <= 30) {
                return 2023 + $val;
            }
        }

        // 3. Dari awalan NIPD (misal 08.340 -> 2024, 09.379 -> 2025)
        if ($student->nipd && preg_match('/^(\d{2})\./', $student->nipd, $m)) {
            $prefix = (int) $m[1];
            if ($student->jenjang === 'SMP') {
                return 2016 + $prefix;
            }
        }

        return null;
    }

    private function determineBaseGrade(Student $student): int
    {
        return match (strtoupper((string) $student->jenjang)) {
            'SD' => 1,
            'SMA', 'SMK', 'MA' => 10,
            default => 7, // SMP / MTs
        };
    }
}
