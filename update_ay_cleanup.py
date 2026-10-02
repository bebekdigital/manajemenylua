import sys
import re

file_path = 'app/Http/Controllers/AcademicYearController.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Define the old logic we want to replace
old_sync_logic_regex = r'// Sync Classes\s+\$selected = collect.*?}\s+}\s+}'
old_sync_logic_regex_compiled = re.compile(old_sync_logic_regex, re.DOTALL)

new_sync_logic = r'''// Sync Classes
        $selected = collect($request->input('selected_classes', []));
        $masterClasses = SchoolClass::with('unit')->get();
        $validClassroomIds = [];

        foreach ($masterClasses as $mc) {
            $key = $mc->unit->name . '|' . $mc->name;
            if ($selected->contains($key)) {
                $jenjang = str_starts_with($mc->name, '7') || str_starts_with($mc->name, '8') || str_starts_with($mc->name, '9') ? 'SMP' : 'SD';
                $grade = (int) filter_var($mc->name, FILTER_SANITIZE_NUMBER_INT);
                if ($grade === 0) $grade = 1;

                $classroom = Classroom::firstOrCreate([
                    'academic_year_id' => $academicYear->id,
                    'unit' => $mc->unit->name,
                    'name' => $mc->name,
                ], [
                    'jenjang' => $jenjang,
                    'grade' => $grade,
                    'capacity' => 30
                ]);
                $validClassroomIds[] = $classroom->id;
            }
        }

        // Hapus semua kelas di TA ini yang TIDAk ada di list validClassroomIds DAN tidak punya siswa
        Classroom::where('academic_year_id', $academicYear->id)
            ->whereNotIn('id', $validClassroomIds)
            ->doesntHave('studentRecords')
            ->delete();'''

# Replace all occurrences
content = old_sync_logic_regex_compiled.sub(new_sync_logic, content)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated sync logic in AcademicYearController.php")
