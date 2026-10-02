<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\SchoolClass;
use App\Models\SchoolUnit;
use Illuminate\Http\Request;

class AcademicYearClassController extends Controller
{
    public function getActiveClasses($id)
    {
        $units = SchoolUnit::with('classes')->orderBy('id')->get();
        
        $activeClassrooms = [];
        if ($id != 0) {
            $activeClassrooms = Classroom::where('academic_year_id', $id)
                ->get(['unit', 'name'])
                ->map(fn($c) => $c->unit . '|' . $c->name)
                ->toArray();
        }

        return response()->json([
            'units' => $units,
            'active_classrooms' => $activeClassrooms,
        ]);
    }

    public function syncActiveClasses(Request $request, AcademicYear $academicYear)
    {
        $validated = $request->validate([
            'selected_classes' => 'array',
            'selected_classes.*' => 'string'
        ]);

        $selected = collect($validated['selected_classes'] ?? []);
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

        return redirect()->back()->with('success', 'Kelas aktif untuk TA ' . $academicYear->name . ' berhasil diperbarui.');
    }
}
