<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\RolePermission;
use App\Models\StudentAcademicRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StatisticsController extends Controller
{
    /**
     * Display the statistics page with student counts per classroom.
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
                ->where('academic_year_id', $selectedYear->id)
                ->where('student_status', 'aktif')
                ->whereNotNull('classroom_id')
                ->select('classroom_id', DB::raw('COUNT(*) as total'))
                ->groupBy('classroom_id')
                ->with('classroom:id,name,unit,jenjang,grade')
                ->get()
                ->map(function ($record) {
                    return [
                        'classroom_name' => $record->classroom?->name ?? '-',
                        'unit' => $record->classroom?->unit ?? '-',
                        'jenjang' => $record->classroom?->jenjang ?? '-',
                        'grade' => $record->classroom?->grade ?? 0,
                        'total' => $record->total,
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

        return Inertia::render('Statistics/StudentStatistics', [
            'studentsPerClass' => $studentsPerClass,
            'selectedAcademicYear' => $selectedYear,
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
