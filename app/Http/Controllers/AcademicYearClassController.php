<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class AcademicYearClassController extends Controller
{
    public function generate(AcademicYear $academicYear)
    {
        $schoolClasses = SchoolClass::with('unit')->get();
        $count = 0;

        foreach ($schoolClasses as $sc) {
            $unitName = $sc->unit->name;
            $name = $sc->name;
            
            $jenjang = str_starts_with($name, '7') || str_starts_with($name, '8') || str_starts_with($name, '9') ? 'SMP' : 'SD';
            $grade = (int) filter_var($name, FILTER_SANITIZE_NUMBER_INT);
            if ($grade === 0) $grade = 1;

            $exists = Classroom::where('academic_year_id', $academicYear->id)
                ->where('unit', $unitName)
                ->where('name', $name)
                ->exists();
                
            if (!$exists) {
                Classroom::create([
                    'academic_year_id' => $academicYear->id,
                    'unit' => $unitName,
                    'jenjang' => $jenjang,
                    'grade' => $grade,
                    'name' => $name,
                    'capacity' => 30
                ]);
                $count++;
            }
        }

        return back()->with('success', "Berhasil men-generate $count kelas baru untuk TA {$academicYear->name} {$academicYear->semester}.");
    }
}
