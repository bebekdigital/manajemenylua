import sys
import re

file_path = 'app/Http/Controllers/AcademicYearController.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Add imports if missing
if "use App\\Models\\SchoolClass;" not in content:
    content = content.replace("use App\\Models\\SchoolLevel;", "use App\\Models\\SchoolLevel;\nuse App\\Models\\SchoolClass;\nuse App\\Models\\Classroom;")

sync_logic = r'''
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
'''

# Update store method
store_search = r'''        AcademicYear::create($validated);

        return back()->with('success', "Tahun Ajaran {$validated['name']} {$validated['semester']} berhasil ditambahkan.");'''

store_replace = r'''        $academicYear = AcademicYear::create($validated);
''' + sync_logic + r'''
        return back()->with('success', "Tahun Ajaran {$validated['name']} {$validated['semester']} berhasil ditambahkan.");'''

content = content.replace(store_search, store_replace)

# Update update method
update_search = r'''        $academicYear->update($validated);

        return back()->with('success', 'Tahun Ajaran berhasil diperbarui.');'''

update_replace = r'''        $academicYear->update($validated);
''' + sync_logic + r'''
        return back()->with('success', 'Tahun Ajaran berhasil diperbarui.');'''

content = content.replace(update_search, update_replace)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated AcademicYearController.php")
