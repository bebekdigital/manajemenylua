<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\SchoolProgram;
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
                'grade' => $c->grade,
                'unit' => $c->unit,
                'academic_year_id' => $c->academic_year_id,
            ]);

        // Get currently selected academic year from session or active year
        $selectedAcademicYearId = session('selected_academic_year_id');
        if (! $selectedAcademicYearId) {
            $activeYear = AcademicYear::where('is_active', true)->first();
            $selectedAcademicYearId = $activeYear ? $activeYear->id : ($academicYears->first()['id'] ?? null);
        }

        // Fetch students with their academic records for the selected TA
        $students = Student::with(['academicRecords' => function ($query) use ($selectedAcademicYearId) {
            $query->where('academic_year_id', $selectedAcademicYearId)
                ->with('classroom');
        }])
            ->get()
            ->map(function ($s) {
                $record = $s->academicRecords->first();
                $classroom = $record?->classroom;

                return [
                    'id' => $s->id,
                    'nisn' => $s->nisn,
                    'nama' => $s->nama,
                    'nipd' => $s->nipd,
                    'jk' => $s->jk,
                    'angkatan' => $s->angkatan,
                    'unit' => $s->unit,
                    'tingkat' => $classroom ? $classroom->grade : null,
                    'kelas' => $classroom ? $classroom->name : null,
                    'program' => $record ? $record->program : null,
                    'student_status' => $record ? $record->student_status : null,
                    'ket_tidak_aktif' => $record ? $record->ket_tidak_aktif : null,
                    'tanggal_tidak_aktif' => $record?->tanggal_tidak_aktif?->format('Y-m-d'),
                    'classroom_id' => $record ? $record->classroom_id : null,
                ];
            });

        // Get programs from Pengaturan
        $programs = SchoolProgram::orderBy('name')->pluck('name')->toArray();

        return Inertia::render('Pembelajaran/Classrooms', [
            'classrooms' => $classrooms,
            'academicYears' => $academicYears,
            'initialAcademicYearId' => (int) $selectedAcademicYearId,
            'students' => $students,
            'programs' => $programs,
        ]);
    }

    /**
     * Bulk-add students to a classroom for a given academic year.
     */
    public function addStudents(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'string',
            'target_academic_year_id' => 'required|integer',
            'target_classroom_id' => 'required|integer',
            'program' => 'nullable|string',
            'student_status' => 'nullable|string',
        ]);

        $classroom = Classroom::find($validated['target_classroom_id']);
        if (! $classroom) {
            return redirect()->back()->with('error', 'Kelas tidak ditemukan.');
        }

        $status = $validated['student_status'] ?? 'aktif';
        $program = $validated['program'] ?? 'Umum';
        $count = 0;

        DB::beginTransaction();
        try {
            foreach ($validated['student_ids'] as $studentId) {
                StudentAcademicRecord::updateOrCreate(
                    [
                        'student_nisn' => $studentId,
                        'academic_year_id' => $validated['target_academic_year_id'],
                    ],
                    [
                        'classroom_id' => $classroom->id,
                        'tingkat' => $classroom->grade,
                        'program' => $program,
                        'student_status' => $status,
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

    /**
     * Bulk-remove students from a classroom for a given academic year.
     */
    public function removeStudents(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'string',
            'academic_year_id' => 'required|integer',
        ]);

        $count = StudentAcademicRecord::whereIn('student_nisn', $validated['student_ids'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->delete();

        return redirect()->back()->with('success', $count.' siswa berhasil dihapus dari kelas.');
    }
}
