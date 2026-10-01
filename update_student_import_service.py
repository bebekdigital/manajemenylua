import sys, re

with open('app/Services/StudentImportService.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Update Data Siswa mappings
# Remove 'program'
content = re.sub(r"\s*'program' => \$this->cleanString\(\$row\['F'\] \?\? null\) \?\? 'Umum',", '', content)

# Shift all columns from G to AZ down by 1
def shift_char(char):
    if len(char) == 1:
        return chr(ord(char)-1)
    else: # two letters
        if char == 'AA': return 'Z'
        else: return f'A{chr(ord(char[1])-1)}'

# We'll use a regex to find all $row['<COL>'] where COL is >= G
def replace_col(match):
    col = match.group(1)
    if (len(col) == 1 and col >= 'G') or len(col) == 2:
        return f"\$row['{shift_char(col)}']"
    return match.group(0)

# We need to apply this only to the first block (lines 75-155 approx).
parts = content.split('// 2. Process Sheet 2: Saudara Kandung')
parts[0] = re.sub(r"\$row\['([A-Z]+)'\]", replace_col, parts[0])
content = '// 2. Process Sheet 2: Saudara Kandung'.join(parts)

# Now update Sheet 3 processing
old_sheet3 = r'''                    $student = $studentMap[$nisn];
                    $kelasName = $this->cleanString($row['E'] ?? null);
                    $tingkat = $this->cleanInteger($row['F'] ?? null) ?? 1;
                    $studentStatus = strtolower($this->cleanString($row['G'] ?? null) ?? 'aktif');'''
new_sheet3 = r'''                    $student = $studentMap[$nisn];
                    $program = $this->cleanString($row['E'] ?? null) ?? 'Umum';
                    $kelasName = $this->cleanString($row['F'] ?? null);
                    $tingkat = $this->cleanInteger($row['G'] ?? null) ?? 1;
                    $studentStatus = strtolower($this->cleanString($row['H'] ?? null) ?? 'aktif');'''
content = content.replace(old_sheet3, new_sheet3)

# And add program to updateOrCreate array
old_update = r'''                        [
                            'classroom_id' => $classroom?->id,
                            'jenjang' => $student->jenjang ?? 'SD',
                            'tingkat' => $tingkat,
                            'student_status' => $studentStatus,
                        ]'''
new_update = r'''                        [
                            'classroom_id' => $classroom?->id,
                            'jenjang' => $student->jenjang ?? 'SD',
                            'tingkat' => $tingkat,
                            'program' => $program,
                            'student_status' => $studentStatus,
                        ]'''
content = content.replace(old_update, new_update)

with open('app/Services/StudentImportService.php', 'w', encoding='utf-8') as f:
    f.write(content)
print('Done update student import service')
