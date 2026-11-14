<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StudentStatusService
{
    /**
     * Hitung status keaktifan siswa pada satu Tahun Ajaran / Semester tertentu.
     *
     * @return array{status: string, ket_tidak_aktif: ?string, tanggal_tidak_aktif: ?string}
     */
    public static function calculateStatus(Student $student, AcademicYear $academicYear): array
    {
        $startDate = $academicYear->start_date?->format('Y-m-d');
        $endDate = $academicYear->end_date?->format('Y-m-d');

        if (! $startDate || ! $endDate) {
            $yearName = $academicYear->name ?? '';
            if (preg_match('/(\d{4})\s*[\/-]\s*(\d{4})/', $yearName, $m)) {
                $y1 = $m[1];
                $y2 = $m[2];
                $sem = strtolower((string) ($academicYear->semester ?? ''));
                if ($sem === 'ganjil') {
                    $startDate = $startDate ?: "{$y1}-07-01";
                    $endDate = $endDate ?: "{$y1}-12-31";
                } elseif ($sem === 'genap') {
                    $startDate = $startDate ?: "{$y2}-01-01";
                    $endDate = $endDate ?: "{$y2}-06-30";
                } else {
                    $startDate = $startDate ?: "{$y1}-07-01";
                    $endDate = $endDate ?: "{$y2}-06-30";
                }
            } else {
                $startDate = $startDate ?: '1970-01-01';
                $endDate = $endDate ?: '1970-12-31';
            }
        }

        $masterKeaktifan = trim((string) ($student->status_keaktifan ?: 'Aktif'));
        $masterKet = trim((string) ($student->keterangan_tidak_aktif ?? ''));
        $tglTidakAktif = $student->tanggal_tidak_aktif ? Carbon::parse($student->tanggal_tidak_aktif)->format('Y-m-d') : null;
        $hasInactiveState = ! empty($masterKet) || strcasecmp($masterKeaktifan, 'Aktif') !== 0;

        if (! $hasInactiveState) {
            return [
                'status' => 'Aktif',
                'ket_tidak_aktif' => null,
                'tanggal_tidak_aktif' => null,
            ];
        }

        $ketSemester = $masterKet ?: (strcasecmp($masterKeaktifan, 'Lulus') === 0 ? 'Lulus' : 'Tidak Aktif');

        if ($tglTidakAktif) {
            if ($tglTidakAktif <= $startDate) {
                return [
                    'status' => 'Tidak Aktif',
                    'ket_tidak_aktif' => $ketSemester,
                    'tanggal_tidak_aktif' => $tglTidakAktif,
                ];
            } elseif ($tglTidakAktif > $endDate) {
                return [
                    'status' => 'Aktif',
                    'ket_tidak_aktif' => null,
                    'tanggal_tidak_aktif' => null,
                ];
            } else {
                return [
                    'status' => 'Tidak Aktif-Sebagian',
                    'ket_tidak_aktif' => $ketSemester,
                    'tanggal_tidak_aktif' => $tglTidakAktif,
                ];
            }
        }

        if (strcasecmp($masterKeaktifan, 'Lulus') === 0 || strcasecmp($masterKet, 'Lulus') === 0) {
            return [
                'status' => 'Lulus',
                'ket_tidak_aktif' => 'Lulus',
                'tanggal_tidak_aktif' => null,
            ];
        }

        return [
            'status' => 'Tidak Aktif',
            'ket_tidak_aktif' => $ketSemester,
            'tanggal_tidak_aktif' => null,
        ];
    }

    /**
     * Sinkronkan status seluruh riwayat akademik untuk 1 orang siswa.
     */
    public static function syncForStudent(Student|int $student): void
    {
        $studentModel = $student instanceof Student
            ? $student
            : Student::with('academicRecords.academicYear')->find($student);

        if (! $studentModel) {
            return;
        }

        if (! $studentModel->relationLoaded('academicRecords')) {
            $studentModel->load('academicRecords.academicYear');
        }

        foreach ($studentModel->academicRecords as $record) {
            if (! $record->academicYear) {
                continue;
            }

            $calc = self::calculateStatus($studentModel, $record->academicYear);

            DB::table('student_academic_records')
                ->where('id', $record->id)
                ->update([
                    'student_status' => $calc['status'],
                    'ket_tidak_aktif' => $calc['ket_tidak_aktif'],
                    'tanggal_tidak_aktif' => $calc['tanggal_tidak_aktif'],
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Sinkronkan status untuk seluruh data student_academic_records di sistem.
     */
    public static function syncAll(): int
    {
        $updatedCount = 0;

        Student::with('academicRecords.academicYear')
            ->chunk(100, function ($students) use (&$updatedCount) {
                foreach ($students as $student) {
                    foreach ($student->academicRecords as $record) {
                        if (! $record->academicYear) {
                            continue;
                        }

                        $calc = self::calculateStatus($student, $record->academicYear);

                        DB::table('student_academic_records')
                            ->where('id', $record->id)
                            ->update([
                                'student_status' => $calc['status'],
                                'ket_tidak_aktif' => $calc['ket_tidak_aktif'],
                                'tanggal_tidak_aktif' => $calc['tanggal_tidak_aktif'],
                                'updated_at' => now(),
                            ]);

                        $updatedCount++;
                    }
                }
            });

        return $updatedCount;
    }
}
