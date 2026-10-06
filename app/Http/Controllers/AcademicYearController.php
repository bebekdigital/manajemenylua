<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\SchoolClass;
use App\Models\SchoolLevel;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AcademicYearController extends Controller
{
    /**
     * Display the Portal Management page with Academic Years.
     */
    public function index(): Response
    {
        $academicYears = AcademicYear::orderByDesc('name')
            ->orderByRaw("FIELD(semester, 'Genap', 'Ganjil')")
            ->get()
            ->map(fn (AcademicYear $year) => [
                'id' => $year->id,
                'name' => $year->name,
                'semester' => $year->semester,
                'start_date' => $year->start_date?->format('Y-m-d'),
                'end_date' => $year->end_date?->format('Y-m-d'),
                'is_active' => (bool) $year->is_active,
                'students_count' => $year->studentRecords()->count(),
                'classrooms_count' => $year->classrooms()->count(),
            ]);

        $users = User::orderBy('name')->get()->map(fn ($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'created_at' => $user->created_at?->format('Y-m-d H:i'),
        ]);

        $schoolLevels = SchoolLevel::with(['units.programs', 'units.classes'])->orderBy('order')->get();

        return Inertia::render('Portal', [
            'academicYears' => $academicYears,
            'users' => $users,
            'schoolLevels' => $schoolLevels,
        ]);
    }

    /**
     * Store a new Academic Year.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'in:Ganjil,Genap'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $exists = AcademicYear::where('name', $validated['name'])
            ->where('semester', $validated['semester'])
            ->exists();

        if ($exists) {
            return back()->with('error', "Tahun Ajaran {$validated['name']} {$validated['semester']} sudah ada.");
        }

        $academicYear = AcademicYear::create($validated);

        // Sync Classes
        $selected = collect($request->input('selected_classes', []));
        $masterClasses = SchoolClass::with('unit')->get();
        $validClassroomIds = [];

        foreach ($masterClasses as $mc) {
            $key = $mc->unit->name.'|'.$mc->name;
            if ($selected->contains($key)) {
                $jenjang = str_starts_with($mc->name, '7') || str_starts_with($mc->name, '8') || str_starts_with($mc->name, '9') ? 'SMP' : 'SD';
                $grade = (int) filter_var($mc->name, FILTER_SANITIZE_NUMBER_INT);
                if ($grade === 0) {
                    $grade = 1;
                }

                $classroom = Classroom::firstOrCreate([
                    'academic_year_id' => $academicYear->id,
                    'unit' => $mc->unit->name,
                    'name' => $mc->name,
                ], [
                    'jenjang' => $jenjang,
                    'grade' => $grade,
                    'capacity' => 30,
                ]);
                $validClassroomIds[] = $classroom->id;
            }
        }

        // Hapus semua kelas di TA ini yang TIDAk ada di list validClassroomIds DAN tidak punya siswa
        Classroom::where('academic_year_id', $academicYear->id)
            ->whereNotIn('id', $validClassroomIds)
            ->doesntHave('studentRecords')
            ->delete();

        return back()->with('success', "Tahun Ajaran {$validated['name']} {$validated['semester']} berhasil ditambahkan.");
    }

    /**
     * Update an existing Academic Year.
     */
    public function update(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'in:Ganjil,Genap'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $duplicate = AcademicYear::where('name', $validated['name'])
            ->where('semester', $validated['semester'])
            ->where('id', '!=', $academicYear->id)
            ->exists();

        if ($duplicate) {
            return back()->with('error', "Tahun Ajaran {$validated['name']} {$validated['semester']} sudah ada.");
        }

        $academicYear->update($validated);

        // Sync Classes
        $selected = collect($request->input('selected_classes', []));
        $masterClasses = SchoolClass::with('unit')->get();
        $validClassroomIds = [];

        foreach ($masterClasses as $mc) {
            $key = $mc->unit->name.'|'.$mc->name;
            if ($selected->contains($key)) {
                $jenjang = str_starts_with($mc->name, '7') || str_starts_with($mc->name, '8') || str_starts_with($mc->name, '9') ? 'SMP' : 'SD';
                $grade = (int) filter_var($mc->name, FILTER_SANITIZE_NUMBER_INT);
                if ($grade === 0) {
                    $grade = 1;
                }

                $classroom = Classroom::firstOrCreate([
                    'academic_year_id' => $academicYear->id,
                    'unit' => $mc->unit->name,
                    'name' => $mc->name,
                ], [
                    'jenjang' => $jenjang,
                    'grade' => $grade,
                    'capacity' => 30,
                ]);
                $validClassroomIds[] = $classroom->id;
            }
        }

        // Hapus semua kelas di TA ini yang TIDAk ada di list validClassroomIds DAN tidak punya siswa
        Classroom::where('academic_year_id', $academicYear->id)
            ->whereNotIn('id', $validClassroomIds)
            ->doesntHave('studentRecords')
            ->delete();

        return back()->with('success', 'Tahun Ajaran berhasil diperbarui.');
    }

    /**
     * Delete an Academic Year.
     */
    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        $studentsCount = $academicYear->studentRecords()->count();

        if ($studentsCount > 0) {
            return back()->with('error', "Tidak dapat menghapus TA ini karena masih memiliki {$studentsCount} catatan akademik siswa.");
        }

        $label = "{$academicYear->name} {$academicYear->semester}";
        $academicYear->delete();

        return back()->with('success', "Tahun Ajaran {$label} berhasil dihapus.");
    }
}
