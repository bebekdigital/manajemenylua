<?php

namespace App\Services;

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
                    'angkatan' => $this->cleanString($row['D'] ?? null),
                    'jenjang' => $this->cleanString($row['E'] ?? null),
                    'unit' => $this->cleanString($row['F'] ?? null) ?? 'SDIT Ulil Albab Gondangrejo',
                    'jk' => $this->parseGender($row['G'] ?? null),
                    'tempat_lahir' => $this->cleanString($row['H'] ?? null),
                    'tanggal_lahir' => $this->parseDate($row['I'] ?? null),
                    'nik' => $this->cleanString($row['J'] ?? null),
                    'agama' => $this->cleanString($row['K'] ?? null) ?? 'Islam',
                    'jalan' => $this->cleanString($row['L'] ?? null),
                    'rt' => $this->cleanString($row['M'] ?? null),
                    'rw' => $this->cleanString($row['N'] ?? null),
                    'dusun' => $this->cleanString($row['O'] ?? null),
                    'desa' => $this->cleanString($row['P'] ?? null),
                    'kecamatan' => $this->cleanString($row['Q'] ?? null),
                    'kabupaten' => $this->cleanString($row['R'] ?? null),
                    'provinsi' => $this->cleanString($row['S'] ?? null),
                    'jenis_tinggal' => $this->cleanString($row['T'] ?? null),
                    'alat_transportasi' => $this->cleanString($row['U'] ?? null),
                    'no_wa' => $this->cleanString($row['V'] ?? null),
                    'no_wa_2' => $this->cleanString($row['W'] ?? null),
                    'email' => $this->cleanString($row['X'] ?? null),
                    'status_kip' => strtolower($this->cleanString($row['AT'] ?? null) ?? '') === 'ya',
                    'no_kip' => $this->cleanString($row['AU'] ?? null),
                    'nama_di_kip' => $this->cleanString($row['AV'] ?? null),
                    'status_pip' => strtolower($this->cleanString($row['AW'] ?? null) ?? '') === 'ya',
                    'pip_keterangan' => $this->cleanString($row['AX'] ?? null),
                    'kebutuhan_khusus' => $this->cleanString($row['AY'] ?? null),
                    'sekolah_asal' => $this->cleanString($row['AZ'] ?? null),
                    'anak_ke' => $this->cleanInteger($row['BA'] ?? null),
                    'no_kk' => $this->cleanString($row['BB'] ?? null),
                    'jarak_rumah' => $this->cleanString($row['BC'] ?? null),
                    'desil' => $this->cleanInteger($row['BF'] ?? null),
                    'diterima_di_jenjang' => $this->cleanString($row['BG'] ?? null),
                    'tanggal_diterima' => $this->parseDate($row['BH'] ?? null),
                    'info_psb' => $this->cleanString($row['BI'] ?? null),
                    'status_registrasi' => $this->cleanString($row['BJ'] ?? null),
                ];

                // Filter by unit when specified
                if ($filterUnit && $filterUnit !== 'all') {
                    $u = strtoupper($studentData['unit'] ?? '');
                    if ($filterUnit === 'SDIT') {
                        if (! str_contains($u, 'SDIT') && ! str_contains($u, 'SD IT')) {
                            $skippedCount++;

                            continue;
                        }
                    } elseif ($filterUnit === 'SMPIT') {
                        if (! str_contains($u, 'SMPIT') && ! str_contains($u, 'SMP IT')) {
                            $skippedCount++;

                            continue;
                        }
                    } else {
                        // Fallback generic check
                        if (! str_contains($u, strtoupper($filterUnit))) {
                            $skippedCount++;

                            continue;
                        }
                    }
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
                $studentMap[$nisn]->jenjang_from_excel = $studentData['jenjang'] ?? 'SD';

                // Handle Family Data
                $familyData = [
                    'ayah_nama' => $this->cleanString($row['Y'] ?? null),
                    'ayah_tahun_lahir' => $this->cleanInteger($row['Z'] ?? null),
                    'ayah_pendidikan' => $this->cleanString($row['AA'] ?? null),
                    'ayah_pekerjaan' => $this->cleanString($row['AB'] ?? null),
                    'ayah_penghasilan' => $this->cleanString($row['AC'] ?? null),
                    'ayah_nik' => $this->cleanString($row['AD'] ?? null),
                    'ayah_status' => $this->cleanString($row['AE'] ?? null),
                    'ibu_nama' => $this->cleanString($row['AF'] ?? null),
                    'ibu_tahun_lahir' => $this->cleanInteger($row['AG'] ?? null),
                    'ibu_pendidikan' => $this->cleanString($row['AH'] ?? null),
                    'ibu_pekerjaan' => $this->cleanString($row['AI'] ?? null),
                    'ibu_penghasilan' => $this->cleanString($row['AJ'] ?? null),
                    'ibu_nik' => $this->cleanString($row['AK'] ?? null),
                    'ibu_status' => $this->cleanString($row['AL'] ?? null),
                    'wali_nama' => $this->cleanString($row['AM'] ?? null),
                    'wali_tahun_lahir' => $this->cleanInteger($row['AN'] ?? null),
                    'wali_pendidikan' => $this->cleanString($row['AO'] ?? null),
                    'wali_pekerjaan' => $this->cleanString($row['AP'] ?? null),
                    'wali_penghasilan' => $this->cleanString($row['AQ'] ?? null),
                    'wali_nik' => $this->cleanString($row['AR'] ?? null),
                    'wali_hubungan' => $this->cleanString($row['AS'] ?? null),
                    'has_bisnis' => strtolower($this->cleanString($row['BD'] ?? null) ?? '') === 'ya',
                    'jenis_bisnis' => $this->cleanString($row['BE'] ?? null),
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

            // Sheet 3 (Data Akademik) is no longer processed here.
            // Academic data will be handled in the new Pembelajaran menu.

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
