<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\EmployeeRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeImportService
{
    /**
     * Import employees from an uploaded XLSX/XLS file.
     *
     * @return array{
     *     success: bool,
     *     created_count: int,
     *     updated_count: int,
     *     total_employees: int,
     *     education_count: int,
     *     records_count: int,
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
        $educationCount = 0;
        $recordsCount = 0;
        $errors = [];
        $warnings = [];

        DB::beginTransaction();

        try {
            // 1. Process Sheet 1: Data Pegawai
            $sheetPegawai = $this->findSheet($spreadsheet, ['1. Data Pegawai', 'Data Pegawai', 'Pegawai'], 1);
            if (! $sheetPegawai) {
                throw new \Exception('Sheet "1. Data Pegawai" tidak ditemukan pada file Excel.');
            }

            /** @var array<string, Employee> $employeeMap [nik => Employee] */
            $employeeMap = [];

            $rowsPegawai = $sheetPegawai->toArray(null, true, true, true);
            $headerPegawai = array_shift($rowsPegawai) ?? [];
            $colPegawai = $this->mapHeaders($headerPegawai);

            foreach ($rowsPegawai as $rowIdx => $row) {
                $colNipy = $colPegawai['nipy'] ?? 'A';
                $colNama = $colPegawai['namalengkap'] ?? ($colPegawai['nama'] ?? 'B');
                $colNik = $colPegawai['nik'] ?? 'F';

                $nipy = $this->cleanString($row[$colNipy] ?? null);
                $nama = $this->cleanString($row[$colNama] ?? null);
                $nik = $this->cleanNik($row[$colNik] ?? null);

                if (empty($nama) || strtolower($nama) === 'nama' || (! empty($nik) && strtolower($nik) === 'nik')) {
                    continue; // Skip if empty name or header row
                }

                $employeeData = [
                    'nipy' => $nipy,
                    'nama' => $nama,
                    'jk' => $this->parseGender($row[$colPegawai['jklp'] ?? ($colPegawai['jk'] ?? 'C')] ?? null),
                    'tempat_lahir' => $this->cleanString($row[$colPegawai['tempatlahir'] ?? 'D'] ?? null),
                    'tanggal_lahir' => $this->parseDate($row[$colPegawai['tanggallahir'] ?? 'E'] ?? null),
                    'tmt_pengangkatan' => $this->parseDate($row[$colPegawai['tmtpengangkatan'] ?? ($colPegawai['tmt'] ?? 'G')] ?? null),
                    'no_wa' => $this->cleanString($row[$colPegawai['nowa'] ?? 'H'] ?? null),
                    'email' => $this->cleanString($row[$colPegawai['email'] ?? 'I'] ?? null),

                    // Alamat Domisili
                    'jalan_domisili' => $this->cleanString($row[$colPegawai['jalandomisili'] ?? 'J'] ?? null),
                    'rt_rw_domisili' => $this->cleanString($row[$colPegawai['rtrwdomisili'] ?? 'K'] ?? null),
                    'dusun_domisili' => $this->cleanString($row[$colPegawai['dusundomisili'] ?? 'L'] ?? null),
                    'desa_domisili' => $this->cleanString($row[$colPegawai['desadomisili'] ?? 'M'] ?? null),
                    'kecamatan_domisili' => $this->cleanString($row[$colPegawai['kecamatandomisili'] ?? 'N'] ?? null),
                    'kab_domisili' => $this->cleanString($row[$colPegawai['kabdomisili'] ?? 'O'] ?? null),
                    'provinsi_domisili' => $this->cleanString($row[$colPegawai['provinsidomisili'] ?? 'P'] ?? null),

                    // Alamat Tinggal
                    'jalan_tinggal' => $this->cleanString($row[$colPegawai['jalantinggal'] ?? 'Q'] ?? null),
                    'rt_rw_tinggal' => $this->cleanString($row[$colPegawai['rtrwtinggal'] ?? 'R'] ?? null),
                    'dusun_tinggal' => $this->cleanString($row[$colPegawai['dusuntinggal'] ?? 'S'] ?? null),
                    'desa_tinggal' => $this->cleanString($row[$colPegawai['desatinggal'] ?? 'T'] ?? null),
                    'kecamatan_tinggal' => $this->cleanString($row[$colPegawai['kecamatantinggal'] ?? 'U'] ?? null),
                    'kabupaten_tinggal' => $this->cleanString($row[$colPegawai['kabupatentinggal'] ?? 'V'] ?? null),
                    'provinsi_tinggal' => $this->cleanString($row[$colPegawai['provinsitinggal'] ?? 'W'] ?? null),

                    'status_rumah' => $this->cleanString($row[$colPegawai['statusrumah'] ?? 'X'] ?? null),
                    'kepemilikan_bpjs' => $this->cleanString($row[$colPegawai['kepemilikanbpjsyatidak'] ?? ($colPegawai['kepemilikanbpjs'] ?? 'Y')] ?? null),
                    'penanggung_bpjs' => $this->cleanString($row[$colPegawai['penanggungbpjs'] ?? 'Z'] ?? null),
                    'skill' => $this->cleanString($row[$colPegawai['skill'] ?? 'AA'] ?? null),

                    // Keluarga
                    'status_pernikahan' => $this->cleanString($row[$colPegawai['statuspernikahan'] ?? 'AB'] ?? null),
                    'nama_suami_istri' => $this->cleanString($row[$colPegawai['namasuamiistri'] ?? 'AC'] ?? null),
                    'ttl_suami_istri' => $this->cleanString($row[$colPegawai['ttlsuamiistri'] ?? 'AD'] ?? null),
                    'pekerjaan_suami_istri' => $this->cleanString($row[$colPegawai['pekerjaansuamiistri'] ?? 'AE'] ?? null),
                    'tanggal_menikah' => $this->parseDate($row[$colPegawai['tanggalmenikah'] ?? 'AF'] ?? null),
                    'jumlah_anak' => $this->parseInteger($row[$colPegawai['jumlahanak'] ?? 'AG'] ?? null),
                    'nama_ibu' => $this->cleanString($row[$colPegawai['namaibu'] ?? 'AH'] ?? null),
                    'nama_ayah' => $this->cleanString($row[$colPegawai['namaayah'] ?? 'AI'] ?? null),
                    'alamat_orangtua' => $this->cleanString($row[$colPegawai['alamatorangtua'] ?? 'AJ'] ?? null),
                    'kontak_darurat' => $this->cleanString($row[$colPegawai['kontakdarurat'] ?? 'AK'] ?? null),
                    'hubungan_kontak_darurat' => $this->cleanString($row[$colPegawai['hubungankontakdarurat'] ?? 'AL'] ?? null),
                ];

                $existing = null;
                if (! empty($nik)) {
                    $existing = Employee::where('nik', $nik)->first();
                } else {
                    $existing = Employee::where('nama', $nama)->whereNull('nik')->first();
                }

                if ($existing) {
                    $existing->update($employeeData);
                    $updatedCount++;
                    $employee = $existing;
                } else {
                    $employee = Employee::create(array_merge(['nik' => empty($nik) ? null : $nik], $employeeData));
                    $createdCount++;
                }

                if (! empty($nik)) {
                    $employeeMap[$nik] = $employee;
                }
            }

            // 2. Process Sheet 2: Riwayat Pendidikan
            $sheetPendidikan = $this->findSheet($spreadsheet, ['2. Riwayat Pendidikan', 'Riwayat Pendidikan', 'Pendidikan'], 2);
            if ($sheetPendidikan) {
                $rowsPendidikan = $sheetPendidikan->toArray(null, true, true, true);
                array_shift($rowsPendidikan);

                // Group by nik, then replace all education records for each employee
                $pendidikanByNik = [];
                foreach ($rowsPendidikan as $row) {
                    $nik = $this->cleanNik($row['A'] ?? null);
                    if (empty($nik) || strtolower($nik) === 'nik') {
                        continue;
                    }
                    if (! isset($pendidikanByNik[$nik])) {
                        $pendidikanByNik[$nik] = [];
                    }
                    $pendidikanByNik[$nik][] = [
                        'jenjang_pendidikan' => $this->cleanString($row['C'] ?? null),
                        'jurusan' => $this->cleanString($row['D'] ?? null),
                        'instansi_pendidikan' => $this->cleanString($row['E'] ?? null),
                        'tahun_lulus' => $this->cleanString($row['F'] ?? null),
                        'pembiayaan' => $this->cleanString($row['G'] ?? null),
                    ];
                }

                foreach ($pendidikanByNik as $nik => $records) {
                    $employee = $employeeMap[$nik] ?? Employee::where('nik', $nik)->first();
                    if (! $employee) {
                        $warnings[] = "NIK {$nik} pada Sheet Pendidikan tidak ditemukan.";

                        continue;
                    }
                    $employee->educationHistories()->delete();
                    foreach ($records as $rec) {
                        $employee->educationHistories()->create($rec);
                        $educationCount++;
                    }
                }
            }

            // 3. Process Sheet 3: Riwayat Kepegawaian
            $sheetKepegawaian = $this->findSheet($spreadsheet, ['3. Riwayat Kepegawaian', 'Riwayat Kepegawaian', 'Kepegawaian'], 3);
            if ($sheetKepegawaian) {
                $rowsKepegawaian = $sheetKepegawaian->toArray(null, true, true, true);
                $headerKepegawaian = array_shift($rowsKepegawaian) ?? [];
                $colKepegawaian = $this->mapHeaders($headerKepegawaian);

                foreach ($rowsKepegawaian as $row) {
                    $colNik = $colKepegawaian['nik'] ?? 'A';
                    $nik = $this->cleanNik($row[$colNik] ?? null);
                    if (empty($nik) || strtolower($nik) === 'nik') {
                        continue;
                    }

                    $employee = $employeeMap[$nik] ?? Employee::where('nik', $nik)->first();
                    if (! $employee) {
                        $warnings[] = "NIK {$nik} pada Sheet Kepegawaian tidak ditemukan.";

                        continue;
                    }

                    $colTahun = $colKepegawaian['tahunajaran'] ?? 'C';
                    $colSemester = $colKepegawaian['semester'] ?? 'D';
                    $tahunAjaran = $this->cleanString($row[$colTahun] ?? null);
                    $semester = $this->cleanString($row[$colSemester] ?? null);

                    $academicYear = null;
                    if ($tahunAjaran) {
                        $academicYear = AcademicYear::where('name', $tahunAjaran)
                            ->where('semester', $semester)
                            ->first();
                        if (! $academicYear) {
                            $warnings[] = "Tahun Ajaran '{$tahunAjaran} {$semester}' tidak ditemukan untuk NIK {$nik}.";

                            continue;
                        }
                    } else {
                        $warnings[] = "Tahun Ajaran kosong pada baris NIK {$nik}, dilewati.";

                        continue;
                    }

                    EmployeeRecord::updateOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'academic_year_id' => $academicYear->id,
                        ],
                        [
                            'jenis_kepegawaian' => $this->cleanString($row[$colKepegawaian['jeniskepegawaian'] ?? 'E'] ?? null),
                            'status_keaktifan' => $this->cleanString($row[$colKepegawaian['statuskeaktifan'] ?? 'F'] ?? null),
                            'keterangan_tidak_aktif' => $this->cleanString($row[$colKepegawaian['keterangantidakaktif'] ?? 'G'] ?? null),
                            'jenjang_kepegawaian' => $this->cleanString($row[$colKepegawaian['jenjangkepegawaian'] ?? 'H'] ?? null),
                            'tmt_kenaikan_jenjang' => $this->parseDate($row[$colKepegawaian['tmtkenaikanjenjang'] ?? ($colKepegawaian['tmt'] ?? 'I')] ?? null),
                            'tst_jenjang' => $this->parseDate($row[$colKepegawaian['tstjenjang'] ?? ($colKepegawaian['tst'] ?? 'J')] ?? null),
                            'keaktifan_dapodik' => $this->cleanString($row[$colKepegawaian['keaktifandapodik'] ?? 'K'] ?? null),
                            'unit_keaktifan_dapodik' => $this->cleanString($row[$colKepegawaian['unitkeaktifandapodik'] ?? 'L'] ?? null),
                            'unit_kerja' => $this->cleanString($row[$colKepegawaian['unitkerja'] ?? 'M'] ?? null),
                            'jabatan' => $this->cleanString($row[$colKepegawaian['jabatan'] ?? 'N'] ?? null),
                        ]
                    );
                    $recordsCount++;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'created_count' => 0,
                'updated_count' => 0,
                'total_employees' => 0,
                'education_count' => 0,
                'records_count' => 0,
                'errors' => [$e->getMessage()],
                'warnings' => [],
            ];
        }

        return [
            'success' => true,
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
            'total_employees' => $createdCount + $updatedCount,
            'education_count' => $educationCount,
            'records_count' => $recordsCount,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    private function findSheet(Spreadsheet $spreadsheet, array $names, int $fallbackIndex): ?Worksheet
    {
        foreach ($names as $name) {
            try {
                $sheet = $spreadsheet->getSheetByName($name);
                if ($sheet) {
                    return $sheet;
                }
            } catch (\Exception) {
            }
        }

        try {
            return $spreadsheet->getSheet($fallbackIndex - 1);
        } catch (\Exception) {
            return null;
        }
    }

    private function cleanString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $str = trim((string) $value);

        return $str !== '' ? $str : null;
    }

    private function parseGender(mixed $value): ?string
    {
        $str = $this->cleanString($value);
        if (! $str) {
            return null;
        }

        $upper = strtoupper($str);
        if ($upper === 'L' || str_contains(strtolower($str), 'laki')) {
            return 'L';
        }
        if ($upper === 'P' || str_contains(strtolower($str), 'perempuan')) {
            return 'P';
        }

        return $str;
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                $date = ExcelDate::excelToDateTimeObject((float) $value);

                return $date->format('Y-m-d');
            } catch (\Exception) {
                return null;
            }
        }

        $str = trim((string) $value);
        if (empty($str)) {
            return null;
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'm/d/Y'] as $format) {
            $dt = \DateTime::createFromFormat($format, $str);
            if ($dt) {
                return $dt->format('Y-m-d');
            }
        }

        return null;
    }

    private function parseInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Clean NIK value to handle copy-paste anomalies (spaces, scientific notation, etc.)
     */
    private function cleanNik(mixed $val): ?string
    {
        if (empty($val)) {
            return null;
        }

        // Handle scientific notation if it somehow survived
        if (is_float($val) || (is_string($val) && preg_match('/^\d+\.\d+E\+\d+$/i', $val))) {
            $val = sprintf('%.0f', (float) $val);
        }

        $str = trim((string) $val);
        // Remove apostrophes, spaces, dashes, dots
        $str = preg_replace('/[^\d]/', '', $str);

        return $str === '' ? null : $str;
    }

    /**
     * Build a lookup map of [normalized_header_title => column_letter].
     *
     * @param  array<string, mixed>  $headerRow
     * @return array<string, string>
     */
    private function mapHeaders(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $col => $title) {
            if (! is_string($title)) {
                continue;
            }
            $normalized = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '', $title)));
            if ($normalized !== '') {
                $map[$normalized] = (string) $col;
            }
        }

        return $map;
    }
}
