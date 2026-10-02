import sys
import os

controller_content = """<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\SchoolProgram;
use App\Models\SchoolUnit;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class StudentMappingController extends Controller
{
    public function index(Request $request)
    {
        $academicYears = AcademicYear::orderByDesc('name')->orderByRaw("FIELD(semester, 'Genap', 'Ganjil')")->get();
        $units = SchoolUnit::orderBy('order')->get();
        
        return Inertia::render('Students/Mapping', [
            'academicYears' => $academicYears,
            'units' => $units,
        ]);
    }

    public function getClasses(Request $request)
    {
        $academicYearId = $request->query('academic_year_id');
        $unit = $request->query('unit');
        
        $query = Classroom::query();
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }
        if ($unit) {
            $query->where('unit', $unit);
        }
        
        return response()->json([
            'classes' => $query->orderBy('grade')->orderBy('name')->get()
        ]);
    }

    public function getStudents(Request $request)
    {
        $academicYearId = $request->query('academic_year_id');
        $classroomId = $request->query('classroom_id');
        
        $records = StudentAcademicRecord::with(['student'])
            ->where('academic_year_id', $academicYearId)
            ->where('classroom_id', $classroomId)
            ->get();
            
        $students = $records->map(function($r) {
            return [
                'id' => $r->student->id,
                'nisn' => $r->student->nisn,
                'nama' => $r->student->nama,
                'jk' => $r->student->jk,
                'status' => $r->student_status,
            ];
        });
        
        return response()->json([
            'students' => $students
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'target_academic_year_id' => 'required|exists:academic_years,id',
            'target_classroom_id' => 'required|exists:classrooms,id',
            'status' => 'required|string',
            'keterangan' => 'nullable|string',
        ]);

        $targetClassroom = Classroom::findOrFail($validated['target_classroom_id']);
        
        DB::beginTransaction();
        try {
            foreach ($validated['student_ids'] as $studentId) {
                // Ensure only 1 record per student per academic year exists
                StudentAcademicRecord::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'academic_year_id' => $validated['target_academic_year_id'],
                    ],
                    [
                        'classroom_id' => $validated['target_classroom_id'],
                        'jenjang' => $targetClassroom->jenjang,
                        'tingkat' => $targetClassroom->grade,
                        'student_status' => $validated['status'],
                        'ket_tidak_aktif' => $validated['keterangan'] ?? null,
                    ]
                );
                
                // Update unit in students table if they move unit
                Student::where('id', $studentId)->update([
                    'unit' => $targetClassroom->unit,
                    'kelas' => $targetClassroom->name,
                    'jenjang' => $targetClassroom->jenjang,
                    'tingkat' => $targetClassroom->grade
                ]);
            }
            DB::commit();
            return redirect()->route('students.index')->with('success', count($validated['student_ids']) . ' siswa berhasil dipetakan ke kelas baru.');
        } catch (\\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memetakan siswa: ' . $e->getMessage());
        }
    }
}
"""

with open('app/Http/Controllers/StudentMappingController.php', 'w', encoding='utf-8') as f:
    f.write(controller_content)

routes_path = 'routes/web.php'
with open(routes_path, 'r', encoding='utf-8') as f:
    routes = f.read()

if "use App\\Http\\Controllers\\StudentMappingController;" not in routes:
    routes = routes.replace("use App\\Http\\Controllers\\StudentController;", "use App\\Http\\Controllers\\StudentController;\nuse App\\Http\\Controllers\\StudentMappingController;")
    
if "Route::get('/students/mapping'" not in routes:
    # insert after Route::get('/students/export'
    # Wait, let's insert inside the auth middleware group
    search_str = "Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');"
    insert_str = """Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');

    Route::get('/students/mapping', [StudentMappingController::class, 'index'])->name('students.mapping.index');
    Route::post('/students/mapping', [StudentMappingController::class, 'store'])->name('students.mapping.store');
    Route::get('/api/students/mapping/classes', [StudentMappingController::class, 'getClasses'])->name('students.mapping.classes');
    Route::get('/api/students/mapping/students', [StudentMappingController::class, 'getStudents'])->name('students.mapping.students');"""
    
    routes = routes.replace(search_str, insert_str)
    with open(routes_path, 'w', encoding='utf-8') as f:
        f.write(routes)

print("Created controller and routes")
