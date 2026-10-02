<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\SchoolLevel;
use App\Models\SchoolClass;
use App\Models\Classroom;
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

        foreach ($masterClasses as $mc) {
            $key = $mc->unit->name . '|' . $mc->name;
            if ($selected->contains($key)) {
                $jenjang = str_starts_with($mc->name, '7') || str_starts_with($mc->name, '8') || str_starts_with($mc->name, '9') ? 'SMP' : 'SD';
                $grade = (int) filter_var($mc->name, FILTER_SANITIZE_NUMBER_INT);
                if ($grade === 0) $grade = 1;

                Classroom::firstOrCreate([
                    'academic_year_id' => $academicYear->id,
                    'unit' => $mc->unit->name,
                    'name' => $mc->name,
                ], [
                    'jenjang' => $jenjang,
                    'grade' => $grade,
                    'capacity' => 30
                ]);
            } else {
                $classroom = Classroom::where('academic_year_id', $academicYear->id)
                    ->where('unit', $mc->unit->name)
                    ->where('name', $mc->name)
                    ->first();
                    
                if ($classroom && $classroom->studentRecords()->count() === 0) {
                    $classroom->delete();
                }
            }
        }

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

        foreach ($masterClasses as $mc) {
            $key = $mc->unit->name . '|' . $mc->name;
            if ($selected->contains($key)) {
                $jenjang = str_starts_with($mc->name, '7') || str_starts_with($mc->name, '8') || str_starts_with($mc->name, '9') ? 'SMP' : 'SD';
                $grade = (int) filter_var($mc->name, FILTER_SANITIZE_NUMBER_INT);
                if ($grade === 0) $grade = 1;

                Classroom::firstOrCreate([
                    'academic_year_id' => $academicYear->id,
                    'unit' => $mc->unit->name,
                    'name' => $mc->name,
                ], [
                    'jenjang' => $jenjang,
                    'grade' => $grade,
                    'capacity' => 30
                ]);
            } else {
                $classroom = Classroom::where('academic_year_id', $academicYear->id)
                    ->where('unit', $mc->unit->name)
                    ->where('name', $mc->name)
                    ->first();
                    
                if ($classroom && $classroom->studentRecords()->count() === 0) {
                    $classroom->delete();
                }
            }
        }

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

    /**
     * Set an Academic Year as the active one.
     */
    public function setActive(AcademicYear $academicYear): RedirectResponse
    {
        // Deactivate all other academic years
        AcademicYear::where('id', '!=', $academicYear->id)->update(['is_active' => false]);

        $academicYear->update(['is_active' => true]);

        // Update session to match the newly active TA
        session(['selected_academic_year_id' => $academicYear->id]);

        return back()->with('success', "TA {$academicYear->name} {$academicYear->semester} ditetapkan sebagai Tahun Ajaran Aktif.");
    }
}
