<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $students = Student::with(['academicRecords' => function ($query) use ($selectedAcademicYearId) {
            $query->where('academic_year_id', $selectedAcademicYearId);
        }])
            ->get()
            ->map(function ($s) {
                $record = $s->academicRecords->first();

                return [
                    'id' => $s->id,
                    'nisn' => $s->nisn,
                    'nama' => $s->nama,
                    'nipd' => $s->nipd,
                    'ttl' => $s->ttl,
                    'jk' => $s->jk,
                    'angkatan' => $s->angkatan,
                    'unit' => $s->unit,
                    'jenjang' => $record ? $record->jenjang : null,
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

    /**
     * Bulk-add students to a classroom for a given academic year.
     */
    public function addStudents(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer',
            'target_academic_year_id' => 'required|integer',
            'target_classroom_id' => 'required|integer',
        ]);

        $classroom = Classroom::find($validated['target_classroom_id']);
        if (! $classroom) {
            return redirect()->back()->with('error', 'Kelas tidak ditemukan.');
        }

        $count = 0;

        DB::beginTransaction();
        try {
            foreach ($validated['student_ids'] as $studentId) {
                StudentAcademicRecord::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'academic_year_id' => $validated['target_academic_year_id'],
                    ],
                    [
                        'classroom_id' => $classroom->id,
                        'jenjang' => $classroom->jenjang,
                        'tingkat' => $classroom->grade,
                        'student_status' => 'aktif',
                    ]
                );
                $count++;
            }

            DB::commit();

            return redirect()->back()->with('success', $count.' siswa berhasil ditambahkan ke kelas '.$classroom->name.'.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal menambahkan siswa: '.$e->getMessage());
        }
    }
}
