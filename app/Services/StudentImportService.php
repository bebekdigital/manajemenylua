<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\SchoolUnit;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
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
            $headerRow = $rowsSiswa[1] ?? [];
            $isNewTemplate = isset($headerRow['G']) && str_contains(strtolower((string) $headerRow['G']), 'program');
            $hasNpsnColumn = isset($headerRow['BC']) && str_contains(strtoupper((string) $headerRow['BC']), 'NPSN');

            // Skip header (row 1)
            array_shift($rowsSiswa);

            // Ambil master PPTQ dan program yang beririsan dengan PPTQ di Tab Akademik
            $pptqUnit = SchoolUnit::where('name', 'like', '%PPTQ%')->first();
            $pptqPrograms = $pptqUnit
                ? $pptqUnit->programs->pluck('name')->map(fn ($p) => strtolower(trim($p)))->toArray()
                : ['boarding smp', 'boarding sma'];
            $pptqUnitName = $pptqUnit?->name ?? 'PPTQ Ulil Albab';

            foreach ($rowsSiswa as $rowIdx => $row) {
                $nisn = $this->cleanString($row['A'] ?? null);
                $nama = $this->cleanString($row['B'] ?? null);

                // Skip empty rows or sample headers
                if (empty($nisn) || empty($nama) || strtolower($nisn) === 'nisn') {
                    continue;
                }

                $rawUnit = $this->cleanString($row['F'] ?? null);
                $rawProgram = $isNewTemplate ? $this->cleanString($row['G'] ?? null) : null;
                $rawUnitPesantren = $isNewTemplate ? $this->cleanString($row['H'] ?? null) : null;

                // Jika kolom program kosong, ambil program default dari unit tersebut di Tab Akademik
                if (empty($rawProgram) && ! empty($rawUnit)) {
                    $uModel = SchoolUnit::where('name', $rawUnit)->first();
                    $rawProgram = $uModel?->programs()->value('name')
                        ?? (str_contains(strtoupper($rawUnit), 'SD') ? 'Umum SDIT' : null);
                }

                $progLower = strtolower(trim((string) $rawProgram));
                // Cek apakah program beririsan dengan PPTQ di Tab Akademik
                $isPptqIntersect = ! empty($progLower) && (
                    in_array($progLower, $pptqPrograms) ||
                    str_contains($progLower, 'boarding') ||
                    str_contains($progLower, 'pptq')
                );

                // Jika program beririsan dengan PPTQ, unit_pesantren otomatis terisi PPTQ
                $finalUnitPesantren = $isPptqIntersect
                    ? $pptqUnitName
                    : ($rawUnitPesantren && stripos($rawUnitPesantren, 'PPTQ') !== false ? $pptqUnitName : null);

                // Mapping kolom data berdasarkan template baru (berisi Program & Unit Pesantren) atau lama
                $colJk = $isNewTemplate ? ($row['I'] ?? null) : ($row['G'] ?? null);
                $colTptLahir = $isNewTemplate ? ($row['J'] ?? null) : ($row['H'] ?? null);
                $colTglLahir = $isNewTemplate ? ($row['K'] ?? null) : ($row['I'] ?? null);
                $colNik = $isNewTemplate ? ($row['L'] ?? null) : ($row['J'] ?? null);
                $colAgama = $isNewTemplate ? ($row['M'] ?? null) : ($row['K'] ?? null);
                $colJalan = $isNewTemplate ? ($row['N'] ?? null) : ($row['L'] ?? null);
                $colRt = $isNewTemplate ? ($row['O'] ?? null) : ($row['M'] ?? null);
                $colRw = $isNewTemplate ? ($row['P'] ?? null) : ($row['N'] ?? null);
                $colDusun = $isNewTemplate ? ($row['Q'] ?? null) : ($row['O'] ?? null);
                $colDesa = $isNewTemplate ? ($row['R'] ?? null) : ($row['P'] ?? null);
                $colKec = $isNewTemplate ? ($row['S'] ?? null) : ($row['Q'] ?? null);
                $colKab = $isNewTemplate ? ($row['T'] ?? null) : ($row['R'] ?? null);
                $colProv = $isNewTemplate ? ($row['U'] ?? null) : ($row['S'] ?? null);
                $colJenisTinggal = $isNewTemplate ? ($row['V'] ?? null) : ($row['T'] ?? null);
                $colTransport = $isNewTemplate ? ($row['W'] ?? null) : ($row['U'] ?? null);
                $colWa1 = $isNewTemplate ? ($row['X'] ?? null) : ($row['V'] ?? null);
                $colWa2 = $isNewTemplate ? ($row['Y'] ?? null) : ($row['W'] ?? null);
                $colEmail = $isNewTemplate ? ($row['Z'] ?? null) : ($row['X'] ?? null);

                // Data Ayah, Ibu, Wali
                $colAyahNama = $isNewTemplate ? ($row['AA'] ?? null) : ($row['Y'] ?? null);
                $colAyahThn = $isNewTemplate ? ($row['AB'] ?? null) : ($row['Z'] ?? null);
                $colAyahPdd = $isNewTemplate ? ($row['AC'] ?? null) : ($row['AA'] ?? null);
                $colAyahPkr = $isNewTemplate ? ($row['AD'] ?? null) : ($row['AB'] ?? null);
                $colAyahGaji = $isNewTemplate ? ($row['AE'] ?? null) : ($row['AC'] ?? null);
                $colAyahNik = $isNewTemplate ? ($row['AF'] ?? null) : ($row['AD'] ?? null);
                $colAyahStatus = $isNewTemplate ? ($row['AG'] ?? null) : ($row['AE'] ?? null);

                $colIbuNama = $isNewTemplate ? ($row['AH'] ?? null) : ($row['AF'] ?? null);
                $colIbuThn = $isNewTemplate ? ($row['AI'] ?? null) : ($row['AG'] ?? null);
                $colIbuPdd = $isNewTemplate ? ($row['AJ'] ?? null) : ($row['AH'] ?? null);
                $colIbuPkr = $isNewTemplate ? ($row['AK'] ?? null) : ($row['AI'] ?? null);
                $colIbuGaji = $isNewTemplate ? ($row['AL'] ?? null) : ($row['AJ'] ?? null);
                $colIbuNik = $isNewTemplate ? ($row['AM'] ?? null) : ($row['AK'] ?? null);
                $colIbuStatus = $isNewTemplate ? ($row['AN'] ?? null) : ($row['AL'] ?? null);

                $colWaliNama = $isNewTemplate ? ($row['AO'] ?? null) : ($row['AM'] ?? null);
                $colWaliThn = $isNewTemplate ? ($row['AP'] ?? null) : ($row['AN'] ?? null);
                $colWaliPdd = $isNewTemplate ? ($row['AQ'] ?? null) : ($row['AO'] ?? null);
                $colWaliPkr = $isNewTemplate ? ($row['AR'] ?? null) : ($row['AP'] ?? null);
                $colWaliGaji = $isNewTemplate ? ($row['AS'] ?? null) : ($row['AQ'] ?? null);
                $colWaliNik = $isNewTemplate ? ($row['AT'] ?? null) : ($row['AR'] ?? null);
                $colWaliHub = $isNewTemplate ? ($row['AU'] ?? null) : ($row['AS'] ?? null);

                // KIP & PIP
                $colKipStatus = $isNewTemplate ? ($row['AV'] ?? null) : ($row['AT'] ?? null);
                $colKipNo = $isNewTemplate ? ($row['AW'] ?? null) : ($row['AU'] ?? null);
                $colKipNama = $isNewTemplate ? ($row['AX'] ?? null) : ($row['AV'] ?? null);
                $colPipStatus = $isNewTemplate ? ($row['AY'] ?? null) : ($row['AW'] ?? null);
                $colPipKet = $isNewTemplate ? ($row['AZ'] ?? null) : ($row['AX'] ?? null);

                // Identitas Lanjutan
                $colKebutuhan = $isNewTemplate ? ($row['BA'] ?? null) : ($row['AY'] ?? null);
                if ($hasNpsnColumn) {
                    $colSekolahAsal = $row['BB'] ?? null;
                    $colNpsn = $row['BC'] ?? null;
                    $colAnakKe = $row['BD'] ?? null;
                    $colNoKk = $row['BE'] ?? null;
                    $colJarak = $row['BF'] ?? null;
                    $colHasBisnis = $row['BG'] ?? null;
                    $colJenisBisnis = $row['BH'] ?? null;
                    $colDesil = $row['BI'] ?? null;
                    $colDiterimaJenjang = $row['BJ'] ?? null;
                    $colTglDiterima = $row['BK'] ?? null;
                    $colInfoPsb = $row['BL'] ?? null;
                    $colStatusRegistrasi = $row['BM'] ?? null;
                    $colStatusKeaktifan = $row['BN'] ?? null;
                    $colKetTidakAktif = $row['BO'] ?? null;
                    $colTglTidakAktif = $row['BP'] ?? null;
                } elseif ($isNewTemplate) {
                    $colSekolahAsal = $row['BB'] ?? null;
                    $colNpsn = null;
                    $colAnakKe = $row['BC'] ?? null;
                    $colNoKk = $row['BD'] ?? null;
                    $colJarak = $row['BE'] ?? null;
                    $colHasBisnis = $row['BF'] ?? null;
                    $colJenisBisnis = $row['BG'] ?? null;
                    $colDesil = $row['BH'] ?? null;
                    $colDiterimaJenjang = $row['BI'] ?? null;
                    $colTglDiterima = $row['BJ'] ?? null;
                    $colInfoPsb = $row['BK'] ?? null;
                    $colStatusRegistrasi = $row['BL'] ?? null;
                    $colStatusKeaktifan = $row['BM'] ?? null;
                    $colKetTidakAktif = $row['BN'] ?? null;
                    $colTglTidakAktif = $row['BO'] ?? null;
                } else {
                    $colSekolahAsal = $row['AZ'] ?? null;
                    $colNpsn = null;
                    $colAnakKe = $row['BA'] ?? null;
                    $colNoKk = $row['BB'] ?? null;
                    $colJarak = $row['BC'] ?? null;
                    $colHasBisnis = $row['BD'] ?? null;
                    $colJenisBisnis = $row['BE'] ?? null;
                    $colDesil = $row['BF'] ?? null;
                    $colDiterimaJenjang = $row['BG'] ?? null;
                    $colTglDiterima = $row['BH'] ?? null;
                    $colInfoPsb = $row['BI'] ?? null;
                    $colStatusRegistrasi = $row['BJ'] ?? null;
                    $colStatusKeaktifan = $row['BK'] ?? null;
                    $colKetTidakAktif = $row['BL'] ?? null;
                    $colTglTidakAktif = $row['BM'] ?? null;
                }

                $studentData = [
                    'nama' => $nama,
                    'nipd' => $this->cleanString($row['C'] ?? null),
                    'angkatan' => $this->cleanString($row['D'] ?? null),
                    'jenjang' => $this->cleanString($row['E'] ?? null),
                    'unit' => $rawUnit ?? 'SDIT Ulil Albab',
                    'program' => $rawProgram,
                    'unit_pesantren' => $finalUnitPesantren,
                    'jk' => $this->parseGender($colJk),
                    'tempat_lahir' => $this->cleanString($colTptLahir),
                    'tanggal_lahir' => $this->parseDate($colTglLahir),
                    'nik' => $this->cleanString($colNik),
                    'agama' => $this->cleanString($colAgama) ?? 'Islam',
                    'jalan' => $this->cleanString($colJalan),
                    'rt' => $this->cleanString($colRt),
                    'rw' => $this->cleanString($colRw),
                    'dusun' => $this->cleanString($colDusun),
                    'desa' => $this->cleanString($colDesa),
                    'kecamatan' => $this->cleanString($colKec),
                    'kabupaten' => $this->cleanString($colKab),
                    'provinsi' => $this->cleanString($colProv),
                    'jenis_tinggal' => $this->cleanString($colJenisTinggal),
                    'alat_transportasi' => $this->cleanString($colTransport),
                    'no_wa' => $this->cleanString($colWa1),
                    'no_wa_2' => $this->cleanString($colWa2),
                    'email' => $this->cleanString($colEmail),
                    'status_kip' => strtolower($this->cleanString($colKipStatus) ?? '') === 'ya',
                    'no_kip' => $this->cleanString($colKipNo),
                    'nama_di_kip' => $this->cleanString($colKipNama),
                    'status_pip' => strtolower($this->cleanString($colPipStatus) ?? '') === 'ya',
                    'pip_keterangan' => $this->cleanString($colPipKet),
                    'kebutuhan_khusus' => $this->cleanString($colKebutuhan),
                    'sekolah_asal' => $this->cleanString($colSekolahAsal),
                    'npsn' => $this->cleanString($colNpsn),
                    'anak_ke' => $this->cleanInteger($colAnakKe),
                    'no_kk' => $this->cleanString($colNoKk),
                    'jarak_rumah' => $this->cleanString($colJarak),
                    'desil' => $this->cleanInteger($colDesil),
                    'diterima_di_jenjang' => $this->cleanString($colDiterimaJenjang),
                    'tanggal_diterima' => $this->parseDate($colTglDiterima),
                    'info_psb' => $this->cleanString($colInfoPsb),
                    'status_registrasi' => $this->cleanString($colStatusRegistrasi),
                    'status_keaktifan' => (function () use ($colStatusKeaktifan) {
                        $raw = strtolower(trim((string) $colStatusKeaktifan));
                        if ($raw === 'lulus') {
                            return 'Lulus';
                        }
                        if (in_array($raw, ['tidak aktif', 'tidak_aktif', 'non-aktif', 'non_aktif'])) {
                            return 'Tidak Aktif';
                        }

                        return 'Aktif';
                    })(),
                    'keterangan_tidak_aktif' => (function () use ($colStatusKeaktifan, $colKetTidakAktif) {
                        $raw = strtolower(trim((string) $colStatusKeaktifan));
                        if ($raw === 'lulus') {
                            return 'Lulus';
                        }
                        if (in_array($raw, ['tidak aktif', 'tidak_aktif', 'non-aktif', 'non_aktif'])) {
                            return $this->cleanString($colKetTidakAktif) ?: 'Lainnya';
                        }

                        return null;
                    })(),
                    'tanggal_tidak_aktif' => (function () use ($colStatusKeaktifan, $colTglTidakAktif) {
                        $raw = strtolower(trim((string) $colStatusKeaktifan));
                        if ($raw === 'lulus' || in_array($raw, ['tidak aktif', 'tidak_aktif', 'non-aktif', 'non_aktif'])) {
                            return $this->parseDate($colTglTidakAktif) ?: now()->toDateString();
                        }

                        return null;
                    })(),
                    'ayah_nama' => $this->cleanString($colAyahNama),
                    'ayah_tahun_lahir' => $this->cleanInteger($colAyahThn),
                    'ayah_pendidikan' => $this->cleanString($colAyahPdd),
                    'ayah_pekerjaan' => $this->cleanString($colAyahPkr),
                    'ayah_penghasilan' => $this->cleanString($colAyahGaji),
                    'ayah_nik' => $this->cleanString($colAyahNik),
                    'ayah_status' => $this->cleanString($colAyahStatus),
                    'ibu_nama' => $this->cleanString($colIbuNama),
                    'ibu_tahun_lahir' => $this->cleanInteger($colIbuThn),
                    'ibu_pendidikan' => $this->cleanString($colIbuPdd),
                    'ibu_pekerjaan' => $this->cleanString($colIbuPkr),
                    'ibu_penghasilan' => $this->cleanString($colIbuGaji),
                    'ibu_nik' => $this->cleanString($colIbuNik),
                    'ibu_status' => $this->cleanString($colIbuStatus),
                    'wali_nama' => $this->cleanString($colWaliNama),
                    'wali_tahun_lahir' => $this->cleanInteger($colWaliThn),
                    'wali_pendidikan' => $this->cleanString($colWaliPdd),
                    'wali_pekerjaan' => $this->cleanString($colWaliPkr),
                    'wali_penghasilan' => $this->cleanString($colWaliGaji),
                    'wali_nik' => $this->cleanString($colWaliNik),
                    'wali_hubungan' => $this->cleanString($colWaliHub),
                    'has_bisnis' => strtolower($this->cleanString($colHasBisnis) ?? '') === 'ya',
                    'jenis_bisnis' => $this->cleanString($colJenisBisnis),
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

                // Otomatis sinkronkan ke riwayat akademik Tahun Ajaran Aktif
                // Admin tidak perlu lagi melakukan pemetaan riwayat manual untuk siswa baru/impor
                $activeYear = AcademicYear::current();
                if ($activeYear) {
                    $classroomId = null;
                    $tingkat = null;
                    $kelasInput = $this->cleanString($colDiterimaJenjang ?? ($row['BG'] ?? null));
                    if ($kelasInput) {
                        $matchingClass = Classroom::where('academic_year_id', $activeYear->id)
                            ->where(function ($q) use ($kelasInput) {
                                $q->where('name', $kelasInput)
                                    ->orWhere('name', 'like', "%{$kelasInput}%");
                            })
                            ->first();
                        if ($matchingClass) {
                            $classroomId = $matchingClass->id;
                            $tingkat = $matchingClass->grade;
                        }
                    }

                    $calc = StudentStatusService::calculateStatus($student, $activeYear);

                    $record = StudentAcademicRecord::where('student_id', $student->id)
                        ->where('academic_year_id', $activeYear->id)
                        ->first();

                    $recordData = [
                        'unit' => $student->unit ?? $studentData['unit'],
                        'jenjang' => $student->jenjang ?? $studentData['jenjang'],
                        'program' => $studentData['program'] ?? $rawProgram ?? $student->program,
                        'unit_pesantren' => $studentData['unit_pesantren'] ?? $finalUnitPesantren ?? $student->unit_pesantren,
                        'student_status' => $calc['status'],
                        'ket_tidak_aktif' => $calc['ket_tidak_aktif'],
                        'tanggal_tidak_aktif' => $calc['tanggal_tidak_aktif'],
                    ];

                    if ($classroomId) {
                        $recordData['classroom_id'] = $classroomId;
                        $recordData['tingkat'] = $tingkat;
                    }

                    if ($record) {
                        $record->update(array_filter($recordData, fn ($v) => ! is_null($v)));
                    } else {
                        StudentAcademicRecord::create(array_merge([
                            'student_id' => $student->id,
                            'academic_year_id' => $activeYear->id,
                        ], $recordData));
                    }
                    $academicRecordsCount++;
                }

                // Handle Family Data
                $familyData = array_filter([
                    'ayah_nama' => $studentData['ayah_nama'],
                    'ayah_tahun_lahir' => $studentData['ayah_tahun_lahir'],
                    'ayah_pendidikan' => $studentData['ayah_pendidikan'],
                    'ayah_pekerjaan' => $studentData['ayah_pekerjaan'],
                    'ayah_penghasilan' => $studentData['ayah_penghasilan'],
                    'ayah_nik' => $studentData['ayah_nik'],
                    'ayah_status' => $studentData['ayah_status'],
                    'ibu_nama' => $studentData['ibu_nama'],
                    'ibu_tahun_lahir' => $studentData['ibu_tahun_lahir'],
                    'ibu_pendidikan' => $studentData['ibu_pendidikan'],
                    'ibu_pekerjaan' => $studentData['ibu_pekerjaan'],
                    'ibu_penghasilan' => $studentData['ibu_penghasilan'],
                    'ibu_nik' => $studentData['ibu_nik'],
                    'ibu_status' => $studentData['ibu_status'],
                    'wali_nama' => $studentData['wali_nama'],
                    'wali_tahun_lahir' => $studentData['wali_tahun_lahir'],
                    'wali_pendidikan' => $studentData['wali_pendidikan'],
                    'wali_pekerjaan' => $studentData['wali_pekerjaan'],
                    'wali_penghasilan' => $studentData['wali_penghasilan'],
                    'wali_nik' => $studentData['wali_nik'],
                    'wali_hubungan' => $studentData['wali_hubungan'],
                ], fn ($v) => ! is_null($v));

                if (! empty($familyData)) {
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
