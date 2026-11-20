<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PsbController extends Controller
{
    /**
     * Tampilkan halaman Ringkasan PSB (Sandingkan data SD/MI/PKBM Solo Raya dengan data siswa baru).
     */
    public function summary(Request $request): Response
    {
        $academicYears = AcademicYear::query()
            ->select(['id', 'name', 'semester', 'start_date', 'end_date'])
            ->get()
            ->sortBy([
                ['name', 'desc'],
                ['semester', 'asc'],
            ])
            ->values();

        // Kelompokkan rentang tanggal per nama Tahun Ajaran (misal '2025/2026')
        $yearRanges = $academicYears
            ->groupBy('name')
            ->map(function ($group, $name) {
                $minStart = $group->min('start_date');
                $maxEnd = $group->max('end_date');

                return [
                    'name' => $name,
                    'start_date' => $minStart ? Carbon::parse($minStart)->format('Y-m-d') : null,
                    'end_date' => $maxEnd ? Carbon::parse($maxEnd)->format('Y-m-d') : null,
                ];
            })
            ->sortByDesc('name')
            ->values();

        $currentYear = AcademicYear::current() ?? $academicYears->first();

        $students = Student::query()
            ->with([
                'currentRecord:id,student_id,classroom_id,unit,jenjang,program,tingkat',
                'currentRecord.classroom:id,name',
                'academicRecords' => function ($query) {
                    $query->select(['id', 'student_id', 'academic_year_id'])
                        ->with('academicYear:id,name,start_date')
                        ->orderBy('academic_year_id');
                },
            ])
            ->select([
                'id',
                'nisn',
                'nama',
                'jk',
                'unit',
                'unit_pesantren',
                'jenjang',
                'program',
                'angkatan',
                'status_keaktifan',
                'status_registrasi',
                'tanggal_diterima',
                'sekolah_asal',
                'npsn',
                'dusun',
                'desa',
                'kecamatan',
                'kabupaten',
                'info_psb',
            ])
            ->orderBy('nama')
            ->get()
            ->map(function (Student $student) use ($yearRanges) {
                $taMasuk = $this->resolveStudentEntryAcademicYear($student, $yearRanges);
                $statusRegistrasi = $student->status_registrasi ?: 'Siswa Baru';

                return [
                    'id' => $student->id,
                    'nisn' => $student->nisn,
                    'nama' => $student->nama,
                    'jk' => strtoupper(trim((string) ($student->jk ?? ''))),
                    'unit' => $student->unit ?? $student->currentRecord?->unit ?? '-',
                    'unit_pesantren' => $student->unit_pesantren,
                    'jenjang' => $student->jenjang ?? $student->currentRecord?->jenjang ?? '-',
                    'program' => $student->currentRecord?->program ?? $student->program ?? 'Umum',
                    'kelas' => $student->currentRecord?->classroom?->name ?? '-',
                    'angkatan' => $student->angkatan,
                    'status_keaktifan' => $student->status_keaktifan ?: 'Aktif',
                    'status_registrasi' => $statusRegistrasi,
                    'tanggal_diterima' => $student->tanggal_diterima?->format('Y-m-d'),
                    'ta_masuk' => $taMasuk,
                    'sekolah_asal' => $student->sekolah_asal,
                    'npsn' => $student->npsn ? trim((string) $student->npsn) : null,
                    'dusun' => $student->dusun,
                    'desa' => $student->desa,
                    'kecamatan' => $student->kecamatan,
                    'kabupaten' => $student->kabupaten,
                    'info_psb' => $student->info_psb,
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Psb/Summary', [
            'students' => $students,
            'academicYears' => $yearRanges->toArray(),
            'currentAcademicYearName' => $currentYear?->name ?? ($yearRanges->first()['name'] ?? null),
        ]);
    }

    /**
     * Tentukan Tahun Ajaran masuk siswa berdasarkan tanggal_diterima (prioritas),
     * riwayat akademik paling awal, atau konversi kalender Juli-Juni.
     *
     * @param  Collection<int, array{name: string, start_date: ?string, end_date: ?string}>  $yearRanges
     */
    private function resolveStudentEntryAcademicYear(Student $student, $yearRanges): ?string
    {
        if ($student->tanggal_diterima) {
            $dateStr = $student->tanggal_diterima->format('Y-m-d');

            foreach ($yearRanges as $range) {
                if (! empty($range['start_date']) && ! empty($range['end_date'])) {
                    if ($dateStr >= $range['start_date'] && $dateStr <= $range['end_date']) {
                        return $range['name'];
                    }
                }
            }

            // Fallback perhitungan Tahun Ajaran Indonesia (Juli - Juni)
            $year = (int) $student->tanggal_diterima->year;
            $month = (int) $student->tanggal_diterima->month;
            $startYear = $month >= 7 ? $year : ($year - 1);

            return $startYear.'/'.($startYear + 1);
        }

        // Fallback ke academic record paling awal jika tanggal_diterima kosong
        $earliestRecord = $student->academicRecords
            ->filter(fn ($r) => ! empty($r->academicYear?->name))
            ->sortBy(fn ($r) => $r->academicYear->name)
            ->first();

        if ($earliestRecord?->academicYear?->name) {
            return $earliestRecord->academicYear->name;
        }

        return null;
    }
}
