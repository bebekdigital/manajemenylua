<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\RolePermission;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StatisticsController extends Controller
{
    /**
     * Display the statistics page with student counts, gender distribution, and classroom breakdown.
     */
    public function index(Request $request): Response
    {
        // Auto-fix for any incorrect jenjang in classrooms table
        Classroom::where('unit', 'LIKE', '%SMP%')->where('jenjang', 'SD')->update(['jenjang' => 'SMP']);
        Classroom::where('unit', 'LIKE', '%SD%')->where('jenjang', 'SMP')->update(['jenjang' => 'SD']);

        // 1. Ambil seluruh data tahun ajaran dari DB (database-agnostic)
        $allAcademicYears = AcademicYear::all(['id', 'name', 'semester', 'start_date', 'end_date'])
            ->sortBy([
                ['name', 'desc'],
                ['semester', 'asc'],
            ])
            ->values();

        $availableYearNames = $allAcademicYears->pluck('name')->unique()->values()->all();

        $currentYear = AcademicYear::current() ?? AcademicYear::first();
        $defaultYearName = $currentYear?->name ?? ($availableYearNames[0] ?? '2026/2027');
        $defaultSemester = 'Semua';

        // Tangkap parameter filter tahun ajaran dan semester dari request (default Semester: Semua)
        $requestedYearName = $request->query('academic_year') ?? $request->query('ta');
        $requestedSemester = $request->query('semester');

        $selectedYearName = ($requestedYearName && (in_array($requestedYearName, $availableYearNames) || empty($availableYearNames)))
            ? $requestedYearName
            : $defaultYearName;

        $allowedSemesters = ['Semua', 'Ganjil', 'Genap'];
        $selectedSemester = ($requestedSemester && in_array($requestedSemester, $allowedSemesters))
            ? $requestedSemester
            : $defaultSemester;

        session([
            'selected_academic_year_name' => $selectedYearName,
            'selected_semester' => $selectedSemester,
        ]);

        // Cari record Tahun Ajaran yang sesuai untuk query data kelas & siswa
        $matchingYears = $allAcademicYears->filter(function ($y) use ($selectedYearName, $selectedSemester) {
            if ($y->name !== $selectedYearName) {
                return false;
            }
            if ($selectedSemester !== 'Semua' && $y->semester !== $selectedSemester) {
                return false;
            }

            return true;
        });

        if ($matchingYears->isEmpty()) {
            $matchingYears = $allAcademicYears->filter(fn ($y) => $y->name === $selectedYearName);
        }

        $matchingYearIds = $matchingYears->pluck('id')->all();
        $firstMatch = $matchingYears->first();

        $selectedYearData = [
            'id' => $firstMatch?->id,
            'name' => $selectedYearName,
            'semester' => $selectedSemester,
            'start_date' => $matchingYears->min('start_date') ? (is_string($matchingYears->min('start_date')) ? $matchingYears->min('start_date') : $matchingYears->min('start_date')->format('Y-m-d')) : null,
            'end_date' => $matchingYears->max('end_date') ? (is_string($matchingYears->max('end_date')) ? $matchingYears->max('end_date') : $matchingYears->max('end_date')->format('Y-m-d')) : null,
        ];

        $studentsPerClass = [];

        if (! empty($matchingYearIds)) {
            // A. Ambil kelas yang sudah diplot (ber-classroom_id)
            $classedRecords = StudentAcademicRecord::query()
                ->leftJoin('students', 'students.id', '=', 'student_academic_records.student_id')
                ->whereIn('student_academic_records.academic_year_id', $matchingYearIds)
                ->where('student_academic_records.student_status', 'aktif')
                ->whereNotNull('student_academic_records.classroom_id')
                ->select(
                    'student_academic_records.classroom_id',
                    DB::raw('COUNT(DISTINCT student_academic_records.student_id) as total'),
                    DB::raw("COUNT(DISTINCT CASE WHEN UPPER(COALESCE(students.jk, '')) = 'L' THEN student_academic_records.student_id END) as male"),
                    DB::raw("COUNT(DISTINCT CASE WHEN UPPER(COALESCE(students.jk, '')) = 'P' THEN student_academic_records.student_id END) as female")
                )
                ->groupBy('student_academic_records.classroom_id')
                ->with('classroom:id,name,unit,jenjang,grade')
                ->get()
                ->map(function ($record) {
                    return [
                        'classroom_name' => $record->classroom?->name ?? '-',
                        'unit' => $record->classroom?->unit ?? '-',
                        'jenjang' => $record->classroom?->jenjang ?? '-',
                        'grade' => $record->classroom?->grade ?? 0,
                        'total' => (int) $record->total,
                        'male' => (int) ($record->male ?? 0),
                        'female' => (int) ($record->female ?? 0),
                    ];
                });

            // B. Ambil rekap per tingkat untuk siswa yang belum diplot ke rombel (classroom_id is NULL)
            $unclassedRecords = StudentAcademicRecord::query()
                ->leftJoin('students', 'students.id', '=', 'student_academic_records.student_id')
                ->whereIn('student_academic_records.academic_year_id', $matchingYearIds)
                ->where('student_academic_records.student_status', 'aktif')
                ->whereNull('student_academic_records.classroom_id')
                ->select(
                    'student_academic_records.tingkat',
                    'student_academic_records.unit',
                    'student_academic_records.jenjang',
                    DB::raw('COUNT(DISTINCT student_academic_records.student_id) as total'),
                    DB::raw("COUNT(DISTINCT CASE WHEN UPPER(COALESCE(students.jk, '')) = 'L' THEN student_academic_records.student_id END) as male"),
                    DB::raw("COUNT(DISTINCT CASE WHEN UPPER(COALESCE(students.jk, '')) = 'P' THEN student_academic_records.student_id END) as female")
                )
                ->groupBy('student_academic_records.tingkat', 'student_academic_records.unit', 'student_academic_records.jenjang')
                ->get()
                ->map(function ($record) {
                    $grade = (int) ($record->tingkat ?? 0);
                    $name = $grade > 0 ? "Kelas {$grade}" : 'Belum Diplot';

                    return [
                        'classroom_name' => $name,
                        'unit' => $record->unit ?? '-',
                        'jenjang' => $record->jenjang ?? '-',
                        'grade' => $grade,
                        'total' => (int) $record->total,
                        'male' => (int) ($record->male ?? 0),
                        'female' => (int) ($record->female ?? 0),
                    ];
                });

            $studentsPerClass = $classedRecords->concat($unclassedRecords)
                ->sortBy([
                    ['unit', 'asc'],
                    ['grade', 'asc'],
                    ['classroom_name', 'asc'],
                ])
                ->values()
                ->toArray();
        }

        $studentsQuery = Student::query();

        if (! empty($matchingYearIds)) {
            $studentsQuery->whereHas('academicRecords', function ($query) use ($matchingYearIds) {
                $query->whereIn('academic_year_id', $matchingYearIds);
            });
        }

        $students = $studentsQuery
            ->with([
                'academicRecords' => function ($query) use ($matchingYearIds) {
                    if (! empty($matchingYearIds)) {
                        $query->whereIn('academic_year_id', $matchingYearIds)->with('classroom:id,name,unit,jenjang,grade');
                    } else {
                        $query->latest('id')->limit(1)->with('classroom:id,name,unit,jenjang,grade');
                    }
                },
            ])
            ->orderBy('unit')
            ->orderBy('nama')
            ->get()
            ->map(function (Student $student) {
                $record = $student->academicRecords->first();

                // Cek status keaktifan dari master student dan catatan ket_tidak_aktif pada record semester
                $masterStatus = strtolower(trim((string) ($student->status_keaktifan ?: 'aktif')));
                $recordKet = $record?->ket_tidak_aktif;

                $isInactive = $masterStatus === 'tidak aktif'
                    || str_contains($masterStatus, 'tidak')
                    || $masterStatus === 'lulus'
                    || (! empty($recordKet) && strcasecmp($recordKet, 'aktif') !== 0);

                $normalizedStatus = $isInactive ? 'tidak_aktif' : 'aktif';
                $displayStatusKeaktifan = $student->status_keaktifan ?: ($normalizedStatus === 'aktif' ? 'Aktif' : 'Tidak Aktif');
                $keteranganTidakAktif = $recordKet ?: $student->keterangan_tidak_aktif;

                return [
                    'id' => $student->id,
                    'nama' => $student->nama,
                    'nisn' => $student->nisn,
                    'nipd' => $student->nipd,
                    'unit' => $record?->unit ?? $student->unit ?? $record?->classroom?->unit ?? '-',
                    'unit_pesantren' => $record?->unit_pesantren ?? $student->unit_pesantren,
                    'jenjang' => $record?->jenjang ?? $student->jenjang ?? $record?->classroom?->jenjang ?? '-',
                    'angkatan' => $student->angkatan,
                    'tingkat' => $record?->tingkat,
                    'jk' => strtoupper(trim((string) ($student->jk ?? ''))),
                    'student_status' => $normalizedStatus,
                    'status_keaktifan' => $displayStatusKeaktifan,
                    'keterangan_tidak_aktif' => $keteranganTidakAktif,
                    'program' => $record?->program ?: ($student->getAttribute('program') ?: 'Umum'),
                    'kelas' => $record?->classroom?->name ?? ($record?->tingkat ? "Kelas {$record->tingkat}" : '-'),
                    'tanggal_diterima' => $student->tanggal_diterima ? $student->tanggal_diterima->format('Y-m-d') : null,
                    'sekolah_asal' => $student->sekolah_asal,
                    'kecamatan' => $student->kecamatan,
                    'kabupaten' => $student->kabupaten,
                    'provinsi' => $student->provinsi,
                    'desa' => $student->desa,
                ];
            })
            ->values()
            ->toArray();

        $allStudentsForChart = Student::query()
            ->with('currentRecord:id,student_id,program')
            ->select(['id', 'nama', 'unit', 'unit_pesantren', 'jenjang', 'angkatan', 'tanggal_diterima'])
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'nama' => $s->nama,
                    'unit' => $s->unit,
                    'unit_pesantren' => $s->unit_pesantren,
                    'program' => $s->currentRecord?->program,
                    'jenjang' => $s->jenjang,
                    'angkatan' => $s->angkatan,
                    'tanggal_diterima' => $s->tanggal_diterima ? $s->tanggal_diterima->format('Y-m-d') : null,
                ];
            })
            ->toArray();

        return Inertia::render('Statistics/StudentStatistics', [
            'students' => $students,
            'allStudents' => $allStudentsForChart,
            'studentsPerClass' => $studentsPerClass,
            'selectedAcademicYear' => $selectedYearData,
            'currentAcademicYear' => $currentYear ? [
                'id' => $currentYear->id,
                'name' => $currentYear->name,
                'semester' => $currentYear->semester,
                'start_date' => $currentYear->start_date ? (is_string($currentYear->start_date) ? $currentYear->start_date : $currentYear->start_date->format('Y-m-d')) : null,
                'end_date' => $currentYear->end_date ? (is_string($currentYear->end_date) ? $currentYear->end_date : $currentYear->end_date->format('Y-m-d')) : null,
            ] : null,
            'availableAcademicYears' => $availableYearNames,
            'allAcademicYears' => $allAcademicYears->map(fn ($y) => [
                'id' => $y->id,
                'name' => $y->name,
                'semester' => $y->semester,
                'start_date' => $y->start_date ? (is_string($y->start_date) ? $y->start_date : $y->start_date->format('Y-m-d')) : null,
                'end_date' => $y->end_date ? (is_string($y->end_date) ? $y->end_date : $y->end_date->format('Y-m-d')) : null,
            ])->values()->toArray(),
        ]);
    }

    /**
     * Display the employee statistics page with counts by unit, gender, and status.
     */
    public function staffIndex(Request $request): Response
    {
        $user = auth()->user();
        $canView = $user?->role === 'superadmin'
            || RolePermission::canAccess($user, 'employee_directory', 'view')
            || (bool) ($user?->can_view_employee ?? false);

        if (! $canView) {
            abort(403, 'Akses ditolak: Anda tidak memiliki izin untuk melihat statistik pegawai.');
        }

        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status_keaktifan', 'aktif')->count();
        $inactiveEmployees = Employee::where('status_keaktifan', '!=', 'aktif')->count();

        // Rekap Jenis Kelamin
        $genderL = Employee::where('jk', 'L')->count();
        $genderP = Employee::where('jk', 'P')->count();

        // Rekap per Unit Kerja dari EmployeePosition
        $unitPositions = DB::table('employee_positions')
            ->select('unit_kerja', DB::raw('COUNT(DISTINCT employee_id) as total'))
            ->whereNotNull('unit_kerja')
            ->where('unit_kerja', '!=', '')
            ->groupBy('unit_kerja')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                return [
                    'unit' => $row->unit_kerja,
                    'total' => (int) $row->total,
                ];
            });

        // Rekap Jenis Kepegawaian (GTY, GTT, PTT, dsb)
        $employmentTypes = DB::table('employee_positions')
            ->select('jenis_kepegawaian', DB::raw('COUNT(DISTINCT employee_id) as total'))
            ->whereNotNull('jenis_kepegawaian')
            ->where('jenis_kepegawaian', '!=', '')
            ->groupBy('jenis_kepegawaian')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                return [
                    'type' => $row->jenis_kepegawaian,
                    'total' => (int) $row->total,
                ];
            });

        return Inertia::render('Statistics/EmployeeStatistics', [
            'metrics' => [
                'total' => $totalEmployees,
                'active' => $activeEmployees,
                'inactive' => $inactiveEmployees,
                'male' => $genderL,
                'female' => $genderP,
            ],
            'unitStats' => $unitPositions,
            'employmentTypeStats' => $employmentTypes,
        ]);
    }
}
