<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
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
}
