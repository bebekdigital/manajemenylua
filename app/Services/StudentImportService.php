<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentImportService
{
    /**
     * Import students from an uploaded XLSX/XLS file.
     *
     * @return array{
     *     success: bool,
     *     created_count: int,
     *     updated_count: int,
     *     total_students: int,
     *     academic_records_count: int,
     *     family_count: int,
     *     siblings_count: int,
     *     errors: array<string>,
     *     warnings: array<string>
     * }
     */
    public function import(UploadedFile|string $file, ?string $filterUnit = null): array
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);

        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $academicRecordsCount = 0;
        $familyCount = 0;
        $siblingsCount = 0;
        $errors = [];
        $warnings = [];

        DB::beginTransaction();

        try {
            // 1. Process Sheet 1: Data Siswa
            $sheetSiswa = $this->findSheet($spreadsheet, ['1. Data Siswa', 'Data Siswa', 'Siswa'], 1);
            if (! $sheetSiswa) {
                throw new \Exception('Sheet "1. Data Siswa" tidak ditemukan pada file Excel.');
            }

            /** @var array<string, Student> $studentMap [nisn => Student] */
            $studentMap = [];

            $rowsSiswa = $sheetSiswa->toArray(null, true, true, true);
            // Skip header (row 1)
            array_shift($rowsSiswa);

            foreach ($rowsSiswa as $rowIdx => $row) {
                $nisn = $this->cleanString($row['A'] ?? null);
                $nama = $this->cleanString($row['B'] ?? null);

                // Skip empty rows or sample headers
                if (empty($nisn) || empty($nama) || strtolower($nisn) === 'nisn') {
                    continue;
                }

                $studentData = [
                    'nama' => $nama,
                    'nipd' => $this->cleanString($row['C'] ?? null),
                    'jenjang' => $this->cleanString($row['D'] ?? null),
                    'unit' => $this->cleanString($row['E'] ?? null) ?? 'SDIT Ulil Albab Gondangrejo',
                    'jk' => $this->parseGender($row['F'] ?? null),
                    'tempat_lahir' => $this->cleanString($row['G'] ?? null),
                    'tanggal_lahir' => $this->parseDate($row['H'] ?? null),
                    'nik' => $this->cleanString($row['I'] ?? null),
                    'agama' => $this->cleanString($row['J'] ?? null) ?? 'Islam',
                    'jalan' => $this->cleanString($row['K'] ?? null),
                    'rt' => $this->cleanString($row['L'] ?? null),
                    'rw' => $this->cleanString($row['M'] ?? null),
                    'dusun' => $this->cleanString($row['N'] ?? null),
                    'desa' => $this->cleanString($row['O'] ?? null),
                    'kecamatan' => $this->cleanString($row['P'] ?? null),
                    'kabupaten' => $this->cleanString($row['Q'] ?? null),
                    'provinsi' => $this->cleanString($row['R'] ?? null),
                    'jenis_tinggal' => $this->cleanString($row['S'] ?? null),
                    'alat_transportasi' => $this->cleanString($row['T'] ?? null),
                    'no_wa' => $this->cleanString($row['U'] ?? null),
                    'email' => $this->cleanString($row['V'] ?? null),
                    'status_kip' => strtolower($this->cleanString($row['AP'] ?? null) ?? '') === 'ya',
                    'no_kip' => $this->cleanString($row['AQ'] ?? null),
                    'nama_di_kip' => $this->cleanString($row['AR'] ?? null),
                    'status_pip' => strtolower($this->cleanString($row['AS'] ?? null) ?? '') === 'ya',
                    'pip_keterangan' => $this->cleanString($row['AT'] ?? null),
                    'kebutuhan_khusus' => $this->cleanString($row['AU'] ?? null),
                    'sekolah_asal' => $this->cleanString($row['AV'] ?? null),
                    'anak_ke' => $this->cleanInteger($row['AW'] ?? null),
                    'no_kk' => $this->cleanString($row['AX'] ?? null),
                    'jarak_rumah' => $this->cleanString($row['AY'] ?? null),
                ];

                // Filter by unit when specified
                if ($filterUnit && $studentData['unit'] !== $filterUnit) {
                    $skippedCount++;

                    continue;
                }

                $student = Student::where('nisn', $nisn)->first();

                if ($student) {
                    $student->update($studentData);
                    $updatedCount++;
                } else {
                    $student = Student::create(array_merge(['nisn' => $nisn], $studentData));
                    $createdCount++;
                }

                $studentMap[$nisn] = $student;

                // Handle Family Data
                $familyData = [
                    'ayah_nama' => $this->cleanString($row['W'] ?? null),
                    'ayah_tahun_lahir' => $this->cleanInteger($row['X'] ?? null),
                    'ayah_pendidikan' => $this->cleanString($row['Y'] ?? null),
                    'ayah_pekerjaan' => $this->cleanString($row['Z'] ?? null),
                    'ayah_penghasilan' => $this->cleanString($row['AA'] ?? null),
                    'ayah_nik' => $this->cleanString($row['AB'] ?? null),
                    'ibu_nama' => $this->cleanString($row['AC'] ?? null),
                    'ibu_tahun_lahir' => $this->cleanInteger($row['AD'] ?? null),
                    'ibu_pendidikan' => $this->cleanString($row['AE'] ?? null),
                    'ibu_pekerjaan' => $this->cleanString($row['AF'] ?? null),
                    'ibu_penghasilan' => $this->cleanString($row['AG'] ?? null),
                    'ibu_nik' => $this->cleanString($row['AH'] ?? null),
                    'wali_nama' => $this->cleanString($row['AI'] ?? null),
                    'wali_tahun_lahir' => $this->cleanInteger($row['AJ'] ?? null),
                    'wali_pendidikan' => $this->cleanString($row['AK'] ?? null),
                    'wali_pekerjaan' => $this->cleanString($row['AL'] ?? null),
                    'wali_penghasilan' => $this->cleanString($row['AM'] ?? null),
                    'wali_nik' => $this->cleanString($row['AN'] ?? null),
                    'wali_hubungan' => $this->cleanString($row['AO'] ?? null),
                ];

                $hasAnyFamilyData = array_filter($familyData, fn ($val) => ! is_null($val) && $val !== '');
                if (! empty($hasAnyFamilyData)) {
                    $student->family()->updateOrCreate([], $familyData);
                    $familyCount++;
                }
            }

            // 2. Process Sheet 2: Saudara Kandung
            $sheetSaudara = $this->findSheet($spreadsheet, ['2. Data Saudara', 'Data Saudara', 'Saudara Kandung', 'Saudara'], 2);
            if ($sheetSaudara) {
                $rowsSaudara = $sheetSaudara->toArray(null, true, true, true);
                array_shift($rowsSaudara);

                // Group siblings by NISN
                $groupedSiblings = [];
                foreach ($rowsSaudara as $row) {
                    $nisn = $this->cleanString($row['A'] ?? null);
                    $namaSaudara = $this->cleanString($row['B'] ?? null);
                    if (empty($nisn) || empty($namaSaudara) || ! isset($studentMap[$nisn])) {
                        continue;
                    }

                    $groupedSiblings[$nisn][] = [
                        'nama' => $namaSaudara,
                        'tanggal_lahir' => $this->parseDate($row['C'] ?? null),
                    ];
                }

                foreach ($groupedSiblings as $nisn => $siblingsList) {
                    $student = $studentMap[$nisn];
                    $student->siblings()->delete();
                    foreach ($siblingsList as $sib) {
                        $student->siblings()->create($sib);
                        $siblingsCount++;
                    }
                }
            }

            // 3. Process Sheet 3: Data Akademik (per TA)
            $sheetAkademik = $this->findSheet($spreadsheet, ['3. Data Akademik (per TA)', 'Data Akademik', 'Akademik'], 3);
            if ($sheetAkademik) {
                $rowsAkademik = $sheetAkademik->toArray(null, true, true, true);
                array_shift($rowsAkademik);

                // Fetch or create academic years dynamically to avoid N+1 queries later
                $academicYears = [];

                foreach ($rowsAkademik as $row) {
                    $nisn = $this->cleanString($row['A'] ?? null);
                    if (empty($nisn) || ! isset($studentMap[$nisn])) {
                        continue;
                    }

                    $taName = $this->cleanString($row['C'] ?? null);
                    $semester = $this->cleanString($row['D'] ?? null);

                    if (empty($taName)) {
                        $activeYear = AcademicYear::current() ?? AcademicYear::first();
                        $taName = $activeYear?->name ?? '2025/2026';
                        $semester = $activeYear?->semester ?? 'Ganjil';
                    }

                    if (empty($semester)) {
                        $semester = 'Ganjil';
                    }

                    // Find or create AcademicYear
                    $academicYear = AcademicYear::where('name', $taName)
                        ->where('semester', $semester)
                        ->first();

                    if (! $academicYear) {
                        [$startDate, $endDate] = $this->guessAcademicYearDates($taName, $semester);

                        $academicYear = AcademicYear::create([
                            'name' => $taName,
                            'semester' => $semester,
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'is_active' => false,
                        ]);
                    }

                    $student = $studentMap[$nisn];
                    $program = $this->cleanString($row['E'] ?? null) ?? 'Umum';
                    $kelasName = $this->cleanString($row['F'] ?? null);
                    $tingkat = $this->cleanInteger($row['G'] ?? null) ?? 1;
                    $studentStatus = strtolower($this->cleanString($row['H'] ?? null) ?? 'aktif');

                    // Find or create classroom
                    $classroom = null;
                    if ($kelasName) {
                        $classroom = Classroom::where('academic_year_id', $academicYear->id)
                            ->where('unit', $student->unit)
                            ->where('name', $kelasName)
                            ->first();

                        if (! $classroom) {
                            $classroom = Classroom::create([
                                'academic_year_id' => $academicYear->id,
                                'unit' => $student->unit,
                                'jenjang' => $student->jenjang ?? 'SD',
                                'grade' => $tingkat,
                                'name' => $kelasName,
                                'capacity' => 30,
                            ]);
                        }
                    }

                    $student->academicRecords()->updateOrCreate(
                        [
                            'academic_year_id' => $academicYear->id,
                        ],
                        [
                            'classroom_id' => $classroom?->id,
                            'jenjang' => $student->jenjang ?? 'SD',
                            'tingkat' => $tingkat,
                            'program' => $program,
                            'student_status' => $studentStatus,
                        ]
                    );

                    $academicRecordsCount++;
                }
            }

            DB::commit();

            return [
                'success' => true,
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
                'skipped_count' => $skippedCount,
                'total_students' => $createdCount + $updatedCount,
                'academic_records_count' => $academicRecordsCount,
                'family_count' => $familyCount,
                'siblings_count' => $siblingsCount,
                'errors' => $errors,
                'warnings' => $warnings,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            return [
                'success' => false,
                'created_count' => 0,
                'updated_count' => 0,
                'skipped_count' => 0,
                'total_students' => 0,
                'academic_records_count' => 0,
                'family_count' => 0,
                'siblings_count' => 0,
                'errors' => [$e->getMessage()],
                'warnings' => $warnings,
            ];
        }
    }

    /**
     * Find sheet by aliases or 0-indexed position.
     */
    private function findSheet(Spreadsheet $spreadsheet, array $possibleNames, ?int $fallbackIndex = null): ?Worksheet
    {
        foreach ($possibleNames as $name) {
            $sheet = $spreadsheet->getSheetByName($name);
            if ($sheet) {
                return $sheet;
            }
        }

        if (! is_null($fallbackIndex) && $fallbackIndex < $spreadsheet->getSheetCount()) {
            return $spreadsheet->getSheet($fallbackIndex);
        }

        return null;
    }

    /**
     * Parse date value from string or Excel serial number.
     */
    private function parseDate(mixed $val): ?string
    {
        if (empty($val)) {
            return null;
        }

        if (is_numeric($val)) {
            try {
                $dateObj = ExcelDate::excelToDateTimeObject($val);

                return $dateObj->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        $str = trim((string) $val);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) {
            return $str;
        }

        $timestamp = strtotime($str);
        if ($timestamp !== false && $timestamp > 0) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    /**
     * Parse gender input.
     */
    private function parseGender(mixed $val): string
    {
        $val = strtoupper(trim((string) $val));
        if ($val === 'P' || str_starts_with($val, 'PEREMPUAN')) {
            return 'P';
        }

        return 'L';
    }

    /**
     * Parse boolean / status ya/tidak.
     */
    private function parseBoolean(mixed $val): bool
    {
        if (empty($val)) {
            return false;
        }

        $str = strtolower(trim((string) $val));

        return in_array($str, ['ya', 'y', 'true', '1', 'yes'], true);
    }

    /**
     * Clean string value.
     */
    private function cleanString(mixed $val): ?string
    {
        if (is_null($val)) {
            return null;
        }

        $str = trim((string) $val);

        return $str === '' ? null : $str;
    }

    /**
     * Clean integer value.
     */
    private function cleanInteger(mixed $val): ?int
    {
        if (is_null($val) || $val === '') {
            return null;
        }

        $clean = preg_replace('/[^\d]/', '', (string) $val);

        return $clean === '' ? null : (int) $clean;
    }

    /**
     * Guess start and end dates based on academic year name (e.g. "2025/2026") and semester.
     */
    private function guessAcademicYearDates(string $name, ?string $semester): array
    {
        $parts = explode('/', $name);
        $startYear = isset($parts[0]) && is_numeric(trim($parts[0])) ? (int) trim($parts[0]) : (int) date('Y');
        $endYear = isset($parts[1]) && is_numeric(trim($parts[1])) ? (int) trim($parts[1]) : $startYear + 1;

        if (strtolower($semester ?? '') === 'genap') {
            return [
                sprintf('%04d-01-01', $endYear),
                sprintf('%04d-06-30', $endYear),
            ];
        }

        return [
            sprintf('%04d-07-01', $startYear),
            sprintf('%04d-12-31', $startYear),
        ];
    }
}
