<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use Inertia\Inertia;
use Inertia\Response;

class PembelajaranController extends Controller
{
    public function classrooms(): Response
    {
        $academicYears = AcademicYear::orderByDesc('name')
            ->orderByRaw("FIELD(semester, 'Genap', 'Ganjil')")
            ->get()
            ->map(fn (AcademicYear $year) => [
                'id' => $year->id,
                'name' => $year->name,
                'semester' => $year->semester,
                'is_active' => (bool) $year->is_active,
            ]);

        $classrooms = Classroom::orderBy('jenjang')
            ->orderBy('name')
            ->get()
            ->map(fn (Classroom $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'jenjang' => $c->jenjang,
                'unit' => $c->unit,
                'tingkat' => $c->tingkat,
                'academic_year_id' => $c->academic_year_id,
            ]);

        // Get currently selected academic year from session or active year
        $selectedAcademicYearId = session('selected_academic_year_id');
        if (! $selectedAcademicYearId) {
            $activeYear = AcademicYear::where('is_active', true)->first();
            $selectedAcademicYearId = $activeYear ? $activeYear->id : ($academicYears->first()['id'] ?? null);
        }

        // Fetch students and their mapping for the selected academic year
        $students = \App\Models\Student::select('id', 'nisn', 'nama', 'jk', 'angkatan', 'unit')
            ->with(['academicRecords' => function ($query) use ($selectedAcademicYearId) {
                $query->where('academic_year_id', $selectedAcademicYearId);
            }])
            ->get()
            ->map(function ($s) {
                $record = $s->academicRecords->first();

                return [
                    'id' => $s->id,
                    'nisn' => $s->nisn,
                    'nama' => $s->nama,
                    'jk' => $s->jk,
                    'angkatan' => $s->angkatan,
                    'unit' => $s->unit,
                    'student_status' => $record ? $record->student_status : 'aktif',
                    'classroom_id' => $record ? $record->classroom_id : null,
                ];
            });

        return Inertia::render('Pembelajaran/Classrooms', [
            'classrooms' => $classrooms,
            'academicYears' => $academicYears,
            'initialAcademicYearId' => (int) $selectedAcademicYearId,
            'students' => $students,
        ]);
    }
}
