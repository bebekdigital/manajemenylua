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
    public function import(UploadedFile|string $file): array
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);

        $createdCount = 0;
        $updatedCount = 0;
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
                    'nik' => $this->cleanString($row['D'] ?? null),
                    'no_kk' => $this->cleanString($row['E'] ?? null),
                    'unit' => $this->cleanString($row['F'] ?? null) ?? 'SDIT Ulil Albab Gondangrejo',
                    'program' => $this->cleanString($row['G'] ?? null) ?? 'Umum',
                    'jk' => $this->parseGender($row['H'] ?? null),
                    'tempat_lahir' => $this->cleanString($row['I'] ?? null),
                    'tanggal_lahir' => $this->parseDate($row['J'] ?? null),
                    'agama' => $this->cleanString($row['K'] ?? null) ?? 'Islam',
                    'no_wa' => $this->cleanString($row['L'] ?? null),
                    'sekolah_asal' => $this->cleanString($row['M'] ?? null),
                    'status_keluarga' => $this->cleanString($row['N'] ?? null),
                    'anak_ke' => $this->cleanInteger($row['O'] ?? null),
                    'diterima_di_jenjang' => $this->cleanString($row['P'] ?? null),
                    'tanggal_diterima' => $this->parseDate($row['Q'] ?? null),
                ];

                $student = Student::where('nisn', $nisn)->first();

                if ($student) {
                    $student->update($studentData);
                    $updatedCount++;
                } else {
                    $student = Student::create(array_merge(['nisn' => $nisn], $studentData));
                    $createdCount++;
                }

                $studentMap[$nisn] = $student;
            }

            // 2. Process Sheet 2: Alamat
            $sheetAlamat = $this->findSheet($spreadsheet, ['2. Alamat', 'Alamat'], 2);
            if ($sheetAlamat) {
                $rowsAlamat = $sheetAlamat->toArray(null, true, true, true);
                array_shift($rowsAlamat);

                foreach ($rowsAlamat as $row) {
                    $nisn = $this->cleanString($row['A'] ?? null);
                    if (empty($nisn) || ! isset($studentMap[$nisn])) {
                        continue;
                    }

                    $studentMap[$nisn]->update([
                        'jalan' => $this->cleanString($row['B'] ?? null),
                        'rt_rw' => $this->cleanString($row['C'] ?? null),
                        'dusun' => $this->cleanString($row['D'] ?? null),
                        'desa' => $this->cleanString($row['E'] ?? null),
                        'kecamatan' => $this->cleanString($row['F'] ?? null),
                        'kabupaten' => $this->cleanString($row['G'] ?? null),
                        'provinsi' => $this->cleanString($row['H'] ?? null),
                    ]);
                }
            }

            // 3. Process Sheet 3: Data Keluarga
            $sheetKeluarga = $this->findSheet($spreadsheet, ['3. Data Keluarga', 'Data Keluarga', 'Keluarga'], 3);
            if ($sheetKeluarga) {
                $rowsKeluarga = $sheetKeluarga->toArray(null, true, true, true);
                array_shift($rowsKeluarga);

                foreach ($rowsKeluarga as $row) {
                    $nisn = $this->cleanString($row['A'] ?? null);
                    if (empty($nisn) || ! isset($studentMap[$nisn])) {
                        continue;
                    }

                    $familyData = [
                        'ayah_nama' => $this->cleanString($row['B'] ?? null),
                        'ayah_nik' => $this->cleanString($row['C'] ?? null),
                        'ayah_tahun_lahir' => $this->cleanInteger($row['D'] ?? null),
                        'ayah_pendidikan' => $this->cleanString($row['E'] ?? null),
                        'ayah_pekerjaan' => $this->cleanString($row['F'] ?? null),
                        'ayah_penghasilan' => $this->cleanString($row['G'] ?? null),
                        'ibu_nama' => $this->cleanString($row['H'] ?? null),
                        'ibu_nik' => $this->cleanString($row['I'] ?? null),
                        'ibu_tahun_lahir' => $this->cleanInteger($row['J'] ?? null),
                        'ibu_pendidikan' => $this->cleanString($row['K'] ?? null),
                        'ibu_pekerjaan' => $this->cleanString($row['L'] ?? null),
                        'ibu_penghasilan' => $this->cleanString($row['M'] ?? null),
                        'wali_nama' => $this->cleanString($row['N'] ?? null),
                        'wali_nik' => $this->cleanString($row['O'] ?? null),
                        'wali_tahun_lahir' => $this->cleanInteger($row['P'] ?? null),
                        'wali_hubungan' => $this->cleanString($row['Q'] ?? null),
                        'wali_pendidikan' => $this->cleanString($row['R'] ?? null),
                        'wali_pekerjaan' => $this->cleanString($row['S'] ?? null),
                        'wali_penghasilan' => $this->cleanString($row['T'] ?? null),
                    ];

                    $hasAnyFamilyData = array_filter($familyData, fn ($val) => ! is_null($val) && $val !== '');
                    if (! empty($hasAnyFamilyData)) {
                        $studentMap[$nisn]->family()->updateOrCreate([], $familyData);
                        $familyCount++;
                    }
                }
            }

            // 4. Process Sheet 4: Saudara Kandung
            $sheetSaudara = $this->findSheet($spreadsheet, ['4. Saudara Kandung', 'Saudara Kandung', 'Saudara'], 4);
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

            // 5. Process Sheet 5: Data Akademik (per TA)
            $sheetAkademik = $this->findSheet($spreadsheet, ['5. Data Akademik (per TA)', 'Data Akademik', 'Akademik'], 5);
            if ($sheetAkademik) {
                $rowsAkademik = $sheetAkademik->toArray(null, true, true, true);
                array_shift($rowsAkademik);

                foreach ($rowsAkademik as $row) {
                    $nisn = $this->cleanString($row['A'] ?? null);
                    if (empty($nisn) || ! isset($studentMap[$nisn])) {
                        continue;
                    }

                    $taName = $this->cleanString($row['B'] ?? null);
                    $semester = $this->cleanString($row['C'] ?? null);

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
                    $jenjang = strtoupper($this->cleanString($row['D'] ?? null) ?? 'SD');
                    $tingkat = $this->cleanInteger($row['E'] ?? null) ?? 1;
                    $kelasName = $this->cleanString($row['F'] ?? null);
                    $studentStatus = strtolower($this->cleanString($row['G'] ?? null) ?? 'aktif');
                    $desil = $this->cleanInteger($row['H'] ?? null);
                    $statusPip = $this->parseBoolean($row['I'] ?? null);
                    $pipKeterangan = $this->cleanString($row['J'] ?? null);
                    $statusKip = $this->parseBoolean($row['K'] ?? null);
                    $noKip = $this->cleanString($row['L'] ?? null);

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
                                'jenjang' => $jenjang,
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
                            'jenjang' => $jenjang,
                            'tingkat' => $tingkat,
                            'student_status' => $studentStatus,
                            'desil' => $desil,
                            'status_pip' => $statusPip,
                            'pip_keterangan' => $pipKeterangan,
                            'status_kip' => $statusKip,
                            'no_kip' => $noKip,
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
