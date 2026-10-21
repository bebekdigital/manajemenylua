<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Employee;
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
     *     positions_count: int,
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
        $positionsCount = 0;
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
                $colNik = $colPegawai['nik'] ?? 'H';

                $nipy = $this->cleanString($row[$colNipy] ?? null);
                $nama = $this->cleanString($row[$colNama] ?? null);
                $nik = $this->cleanNik($row[$colNik] ?? null);

                if (empty($nama) || strtolower($nama) === 'nama' || (! empty($nik) && strtolower($nik) === 'nik')) {
                    continue; // Skip if empty name or header row
                }

                $employeeData = [
                    'nipy' => $nipy,
                    'nama' => $nama,
                    'gelar_depan' => $this->cleanString($row[$colPegawai['gelardepan'] ?? 'C'] ?? null),
                    'gelar_belakang' => $this->cleanString($row[$colPegawai['gelarbelakang'] ?? 'D'] ?? null),
                    'jk' => $this->parseGender($row[$colPegawai['jklp'] ?? ($colPegawai['jk'] ?? 'E')] ?? null),
                    'tempat_lahir' => $this->cleanString($row[$colPegawai['tempatlahir'] ?? 'F'] ?? null),
                    'tanggal_lahir' => $this->parseDate($row[$colPegawai['tanggallahir'] ?? 'G'] ?? null),
                    'tmt_pengangkatan' => $this->parseDate($row[$colPegawai['tmtpengangkatan'] ?? ($colPegawai['tmt'] ?? 'I')] ?? null),
                    'tst_pengangkatan' => $this->parseDate($row[$colPegawai['tstpengangkatan'] ?? ($colPegawai['tst'] ?? 'J')] ?? null),
                    'keaktifan_dapodik' => $this->cleanString($row[$colPegawai['keaktifandapodik'] ?? 'K'] ?? null),
                    'unit_keaktifan_dapodik' => $this->cleanString($row[$colPegawai['unitkeaktifandapodik'] ?? 'L'] ?? null),
                    'status_keaktifan' => $this->cleanString($row[$colPegawai['statuskeaktifan'] ?? 'M'] ?? 'Aktif') ?? 'Aktif',
                    'keterangan_tidak_aktif' => $this->cleanString($row[$colPegawai['keterangantidakaktif'] ?? 'N'] ?? null),
                    'no_wa' => $this->cleanString($row[$colPegawai['nowa'] ?? 'O'] ?? null),
                    'email' => $this->cleanString($row[$colPegawai['email'] ?? 'P'] ?? null),

                    // Alamat Domisili
                    'jalan_domisili' => $this->cleanString($row[$colPegawai['jalandomisili'] ?? 'Q'] ?? null),
                    'rt_rw_domisili' => $this->cleanString($row[$colPegawai['rtrwdomisili'] ?? 'R'] ?? null),
                    'dusun_domisili' => $this->cleanString($row[$colPegawai['dusundomisili'] ?? 'S'] ?? null),
                    'desa_domisili' => $this->cleanString($row[$colPegawai['desadomisili'] ?? 'T'] ?? null),
                    'kecamatan_domisili' => $this->cleanString($row[$colPegawai['kecamatandomisili'] ?? 'U'] ?? null),
                    'kab_domisili' => $this->cleanString($row[$colPegawai['kabdomisili'] ?? 'V'] ?? null),
                    'provinsi_domisili' => $this->cleanString($row[$colPegawai['provinsidomisili'] ?? 'W'] ?? null),

                    // Alamat Tinggal
                    'jalan_tinggal' => $this->cleanString($row[$colPegawai['jalantinggal'] ?? 'X'] ?? null),
                    'rt_rw_tinggal' => $this->cleanString($row[$colPegawai['rtrwtinggal'] ?? 'Y'] ?? null),
                    'dusun_tinggal' => $this->cleanString($row[$colPegawai['dusuntinggal'] ?? 'Z'] ?? null),
                    'desa_tinggal' => $this->cleanString($row[$colPegawai['desatinggal'] ?? 'AA'] ?? null),
                    'kecamatan_tinggal' => $this->cleanString($row[$colPegawai['kecamatantinggal'] ?? 'AB'] ?? null),
                    'kabupaten_tinggal' => $this->cleanString($row[$colPegawai['kabupatentinggal'] ?? 'AC'] ?? null),
                    'provinsi_tinggal' => $this->cleanString($row[$colPegawai['provinsitinggal'] ?? 'AD'] ?? null),

                    'status_rumah' => $this->cleanString($row[$colPegawai['statusrumah'] ?? 'AE'] ?? null),
                    'kepemilikan_bpjs' => $this->cleanString($row[$colPegawai['kepemilikanbpjsyatidak'] ?? ($colPegawai['kepemilikanbpjs'] ?? 'AF')] ?? null),
                    'penanggung_bpjs' => $this->cleanString($row[$colPegawai['penanggungbpjs'] ?? 'AG'] ?? null),
                    'skill' => $this->cleanString($row[$colPegawai['skill'] ?? 'AH'] ?? null),

                    // Keluarga
                    'status_pernikahan' => $this->cleanString($row[$colPegawai['statuspernikahan'] ?? 'AI'] ?? null),
                    'nama_suami_istri' => $this->cleanString($row[$colPegawai['namasuamiistri'] ?? 'AJ'] ?? null),
                    'ttl_suami_istri' => $this->cleanString($row[$colPegawai['ttlsuamiistri'] ?? 'AK'] ?? null),
                    'pekerjaan_suami_istri' => $this->cleanString($row[$colPegawai['pekerjaansuamiistri'] ?? 'AL'] ?? null),
                    'tanggal_menikah' => $this->parseDate($row[$colPegawai['tanggalmenikah'] ?? 'AM'] ?? null),
                    'jumlah_anak' => $this->parseInteger($row[$colPegawai['jumlahanak'] ?? 'AN'] ?? null),
                    'nama_ibu' => $this->cleanString($row[$colPegawai['namaibu'] ?? 'AO'] ?? null),
                    'nama_ayah' => $this->cleanString($row[$colPegawai['namaayah'] ?? 'AP'] ?? null),
                    'alamat_orangtua' => $this->cleanString($row[$colPegawai['alamatorangtua'] ?? 'AQ'] ?? null),
                    'kontak_darurat' => $this->cleanString($row[$colPegawai['kontakdarurat'] ?? 'AR'] ?? null),
                    'hubungan_kontak_darurat' => $this->cleanString($row[$colPegawai['hubungankontakdarurat'] ?? 'AS'] ?? null),
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

            // 3. Process Sheet 3: Riwayat Kepegawaian (Jenjang Kepegawaian murni tanggal TMT & TST)
            $sheetKepegawaian = $this->findSheet($spreadsheet, ['3. Riwayat Kepegawaian', 'Riwayat Kepegawaian', 'Kepegawaian'], 3);
            if ($sheetKepegawaian) {
                $rowsKepegawaian = $sheetKepegawaian->toArray(null, true, true, true);
                $headerKepegawaian = array_shift($rowsKepegawaian) ?? [];
                $colKepegawaian = $this->mapHeaders($headerKepegawaian);

                $recordsByNik = [];
                foreach ($rowsKepegawaian as $row) {
                    $colNik = $colKepegawaian['nik'] ?? 'A';
                    $nik = $this->cleanNik($row[$colNik] ?? null);
                    if (empty($nik) || strtolower($nik) === 'nik') {
                        continue;
                    }

                    if (! isset($recordsByNik[$nik])) {
                        $recordsByNik[$nik] = [];
                    }

                    $tmtKenaikan = $this->parseDate($row[$colKepegawaian['tmtkenaikanjenjang'] ?? ($colKepegawaian['tmt'] ?? 'C')] ?? null);
                    $tstKenaikan = $this->parseDate($row[$colKepegawaian['tstkenaikanjenjang'] ?? ($colKepegawaian['tstjenjang'] ?? ($colKepegawaian['tst'] ?? 'D'))] ?? null);
                    $jenjang = $this->cleanString($row[$colKepegawaian['jenjangkepegawaian'] ?? ($colKepegawaian['jenjang'] ?? 'E')] ?? null);

                    // Backward-compatibility if legacy file includes academic year
                    $academicYearId = null;
                    if (isset($colKepegawaian['tahunajaran'])) {
                        $tahunAjaran = $this->cleanString($row[$colKepegawaian['tahunajaran']] ?? null);
                        $semester = $this->cleanString($row[$colKepegawaian['semester'] ?? 'D'] ?? null);
                        if ($tahunAjaran) {
                            $academicYearId = AcademicYear::where('name', $tahunAjaran)
                                ->where('semester', $semester)
                                ->value('id');
                        }
                    }

                    if ($tmtKenaikan || $tstKenaikan || $jenjang) {
                        $recordsByNik[$nik][] = [
                            'academic_year_id' => $academicYearId,
                            'tmt_kenaikan_jenjang' => $tmtKenaikan,
                            'tst_kenaikan_jenjang' => $tstKenaikan,
                            'tst_jenjang' => $tstKenaikan,
                            'jenjang_kepegawaian' => $jenjang,
                        ];
                    }
                }

                foreach ($recordsByNik as $nik => $records) {
                    $employee = $employeeMap[$nik] ?? Employee::where('nik', $nik)->first();
                    if (! $employee) {
                        $warnings[] = "NIK {$nik} pada Sheet Kepegawaian tidak ditemukan.";

                        continue;
                    }

                    $employee->records()->delete();
                    foreach ($records as $rec) {
                        $employee->records()->create($rec);
                        $recordsCount++;
                    }
                }
            }

            // 4. Process Sheet 4: Riwayat Jabatan (NIK, Nama Lengkap, TMT Jabatan, TST Jabatan, Unit Kerja, Jabatan, Jenis Kepegawaian)
            $sheetJabatan = $this->findSheet($spreadsheet, ['4. Riwayat Jabatan', 'Riwayat Jabatan', 'Jabatan'], 4);
            if ($sheetJabatan) {
                $rowsJabatan = $sheetJabatan->toArray(null, true, true, true);
                $headerJabatan = array_shift($rowsJabatan) ?? [];
                $colJabatan = $this->mapHeaders($headerJabatan);

                $positionsByNik = [];
                foreach ($rowsJabatan as $row) {
                    $colNik = $colJabatan['nik'] ?? 'A';
                    $nik = $this->cleanNik($row[$colNik] ?? null);
                    if (empty($nik) || strtolower($nik) === 'nik') {
                        continue;
                    }

                    if (! isset($positionsByNik[$nik])) {
                        $positionsByNik[$nik] = [];
                    }

                    $tmtJabatan = $this->parseDate($row[$colJabatan['tmtjabatan'] ?? ($colJabatan['tmt'] ?? 'C')] ?? null);
                    $tstJabatan = $this->parseDate($row[$colJabatan['tstjabatan'] ?? ($colJabatan['tst'] ?? 'D')] ?? null);
                    $unitKerja = $this->cleanString($row[$colJabatan['unitkerja'] ?? ($colJabatan['unit'] ?? 'E')] ?? null);
                    $jabatan = $this->cleanString($row[$colJabatan['jabatan'] ?? 'F'] ?? null);
                    $jenisKepegawaian = $this->cleanString($row[$colJabatan['jeniskepegawaian'] ?? 'G'] ?? null);

                    if ($tmtJabatan || $tstJabatan || $unitKerja || $jabatan || $jenisKepegawaian) {
                        $positionsByNik[$nik][] = [
                            'tmt_jabatan' => $tmtJabatan,
                            'tst_jabatan' => $tstJabatan,
                            'unit_kerja' => $unitKerja,
                            'jabatan' => $jabatan,
                            'jenis_kepegawaian' => $jenisKepegawaian,
                        ];
                    }
                }

                foreach ($positionsByNik as $nik => $positions) {
                    $employee = $employeeMap[$nik] ?? Employee::where('nik', $nik)->first();
                    if (! $employee) {
                        $warnings[] = "NIK {$nik} pada Sheet Riwayat Jabatan tidak ditemukan.";

                        continue;
                    }

                    $employee->positions()->delete();
                    foreach ($positions as $pos) {
                        $employee->positions()->create($pos);
                        $positionsCount++;
                    }
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
                'positions_count' => 0,
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
            'positions_count' => $positionsCount,
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
