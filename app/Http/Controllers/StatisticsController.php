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

        $selectedYearId = $request->query('academic_year_id') ?? session('selected_academic_year_id');
        $selectedYear = $selectedYearId
            ? AcademicYear::find($selectedYearId)
            : (AcademicYear::current() ?? AcademicYear::first());

        if ($selectedYear && session('selected_academic_year_id') !== $selectedYear->id) {
            session(['selected_academic_year_id' => $selectedYear->id]);
        }

        $studentsPerClass = [];

        if ($selectedYear) {
            $studentsPerClass = StudentAcademicRecord::query()
                ->leftJoin('students', 'students.id', '=', 'student_academic_records.student_id')
                ->where('student_academic_records.academic_year_id', $selectedYear->id)
                ->where('student_academic_records.student_status', 'aktif')
                ->whereNotNull('student_academic_records.classroom_id')
                ->select(
                    'student_academic_records.classroom_id',
                    DB::raw('COUNT(*) as total'),
                    DB::raw("SUM(CASE WHEN UPPER(COALESCE(students.jk, '')) = 'L' THEN 1 ELSE 0 END) as male"),
                    DB::raw("SUM(CASE WHEN UPPER(COALESCE(students.jk, '')) = 'P' THEN 1 ELSE 0 END) as female")
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
                })
                ->sortBy([
                    ['unit', 'asc'],
                    ['grade', 'asc'],
                    ['classroom_name', 'asc'],
                ])
                ->values()
                ->toArray();
        }

        $students = Student::query()
            ->with([
                'academicRecords' => function ($query) use ($selectedYear) {
                    if ($selectedYear) {
                        $query->where('academic_year_id', $selectedYear->id)->with('classroom:id,name,unit,jenjang,grade');
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
                $rawStatus = $student->status_keaktifan
                    ? (strtolower(trim($student->status_keaktifan)) === 'tidak aktif' ? 'tidak_aktif' : 'aktif')
                    : ($record?->student_status ?? 'aktif');

                $normalizedStatus = strtolower(trim((string) $rawStatus)) === 'aktif' ? 'aktif' : 'tidak_aktif';

                return [
                    'unit' => $student->unit ?? $record?->classroom?->unit ?? '-',
                    'jenjang' => $student->jenjang ?? $record?->classroom?->jenjang ?? '-',
                    'angkatan' => $student->angkatan,
                    'jk' => strtoupper(trim((string) ($student->jk ?? ''))),
                    'student_status' => $normalizedStatus,
                    'keterangan_tidak_aktif' => $student->keterangan_tidak_aktif ?? $record?->ket_tidak_aktif,
                    'program' => $record?->program ?? 'Umum',
                    'kelas' => $record?->classroom?->name,
                    'tanggal_diterima' => $student->tanggal_diterima ? $student->tanggal_diterima->format('Y-m-d') : null,
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Statistics/StudentStatistics', [
            'students' => $students,
            'studentsPerClass' => $studentsPerClass,
            'selectedAcademicYear' => $selectedYear ? [
                'id' => $selectedYear->id,
                'name' => $selectedYear->name,
                'semester' => $selectedYear->semester,
                'start_date' => $selectedYear->start_date ? $selectedYear->start_date->format('Y-m-d') : null,
                'end_date' => $selectedYear->end_date ? $selectedYear->end_date->format('Y-m-d') : null,
            ] : null,
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
