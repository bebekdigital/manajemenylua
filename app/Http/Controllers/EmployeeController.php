<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\EmployeeRecord;
use App\Services\EmployeeImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeeController extends Controller
{
    /**
     * Display the employee directory page.
     */
    public function index(Request $request): Response
    {
        $selectedYearId = $request->query('academic_year_id') ?? session('selected_academic_year_id');
        $selectedYear = $selectedYearId
            ? AcademicYear::find($selectedYearId)
            : (AcademicYear::current() ?? AcademicYear::first());

        $employees = Employee::with([
            'records' => function ($query) use ($selectedYear) {
                if ($selectedYear) {
                    $query->where('academic_year_id', $selectedYear->id);
                }
            },
            'educationHistories',
        ])
            ->orderBy('nama')
            ->get()
            ->map(function (Employee $employee) {
                $record = $employee->records->first();

                return $this->mapEmployeeForList($employee, $record);
            });

        return Inertia::render('Staff', [
            'employees' => $employees,
            'selectedAcademicYear' => $selectedYear ? [
                'id' => $selectedYear->id,
                'name' => $selectedYear->name,
                'semester' => $selectedYear->semester,

            ] : null,
        ]);
    }

    /**
     * Display the employee detail page.
     */
    public function show(string $nipy, Request $request): Response
    {
        $selectedYearId = $request->query('academic_year_id') ?? session('selected_academic_year_id');
        $selectedYear = $selectedYearId
            ? AcademicYear::find($selectedYearId)
            : (AcademicYear::current() ?? AcademicYear::first());

        $employee = Employee::with([
            'educationHistories',
            'records.academicYear',
        ])->where('nipy', $nipy)->firstOrFail();

        $currentRecord = $employee->records
            ->when($selectedYear, fn ($c) => $c->where('academic_year_id', $selectedYear->id))
            ->first();

        $allYears = AcademicYear::orderByDesc('name')->orderByDesc('semester')->get();

        $employeeData = [
            'nipy' => $employee->nipy,
            'nama' => $employee->nama,
            'jk' => $employee->jk,
            'ttl' => $employee->ttl,
            'tempat_lahir' => $employee->tempat_lahir,
            'tanggal_lahir' => $employee->tanggal_lahir?->format('Y-m-d'),
            'nik' => $employee->nik,
            'no_wa' => $employee->no_wa,
            'email' => $employee->email,

            // Alamat Domisili
            'jalan_domisili' => $employee->jalan_domisili,
            'rt_rw_domisili' => $employee->rt_rw_domisili,
            'dusun_domisili' => $employee->dusun_domisili,
            'desa_domisili' => $employee->desa_domisili,
            'kecamatan_domisili' => $employee->kecamatan_domisili,
            'kab_domisili' => $employee->kab_domisili,
            'provinsi_domisili' => $employee->provinsi_domisili,

            // Alamat Tinggal
            'jalan_tinggal' => $employee->jalan_tinggal,
            'rt_rw_tinggal' => $employee->rt_rw_tinggal,
            'dusun_tinggal' => $employee->dusun_tinggal,
            'desa_tinggal' => $employee->desa_tinggal,
            'kecamatan_tinggal' => $employee->kecamatan_tinggal,
            'provinsi_tinggal' => $employee->provinsi_tinggal,
            'status_rumah' => $employee->status_rumah,
            'kepemilikan_bpjs' => $employee->kepemilikan_bpjs,
            'penanggung_bpjs' => $employee->penanggung_bpjs,

            // Profil & Keluarga
            'skill' => $employee->skill,
            'status_pernikahan' => $employee->status_pernikahan,
            'nama_suami_istri' => $employee->nama_suami_istri,
            'ttl_suami_istri' => $employee->ttl_suami_istri,
            'pekerjaan_suami_istri' => $employee->pekerjaan_suami_istri,
            'tanggal_menikah' => $employee->tanggal_menikah?->format('Y-m-d'),
            'formatted_tanggal_menikah' => $employee->tanggal_menikah?->translatedFormat('d F Y'),
            'jumlah_anak' => $employee->jumlah_anak,
            'nama_ibu' => $employee->nama_ibu,
            'nama_ayah' => $employee->nama_ayah,
            'alamat_orangtua' => $employee->alamat_orangtua,
            'kontak_darurat' => $employee->kontak_darurat,
            'hubungan_kontak_darurat' => $employee->hubungan_kontak_darurat,

            // Kepegawaian aktif
            'unit_kerja' => $currentRecord?->unit_kerja,
            'jabatan' => $currentRecord?->jabatan,
            'jenis_kepegawaian' => $currentRecord?->jenis_kepegawaian,
            'status_keaktifan' => $currentRecord?->status_keaktifan,
            'keterangan_tidak_aktif' => $currentRecord?->keterangan_tidak_aktif,
            'jenjang_kepegawaian' => $currentRecord?->jenjang_kepegawaian,

            // Semua riwayat kepegawaian (untuk tab)
            'riwayat_kepegawaian' => $employee->records->map(fn ($r) => [
                'id' => $r->id,
                'tahun_ajaran' => $r->academicYear?->name,
                'semester' => $r->academicYear?->semester,
                'jenis_kepegawaian' => $r->jenis_kepegawaian,
                'status_keaktifan' => $r->status_keaktifan,
                'keterangan_tidak_aktif' => $r->keterangan_tidak_aktif,
                'jenjang_kepegawaian' => $r->jenjang_kepegawaian,
                'tmt' => $r->tmt?->format('Y-m-d'),
                'formatted_tmt' => $r->tmt?->translatedFormat('d F Y'),
                'tst_jenjang' => $r->tst_jenjang?->format('Y-m-d'),
                'formatted_tst_jenjang' => $r->tst_jenjang?->translatedFormat('d F Y'),
                'masa_kerja' => $r->masa_kerja,
                'keaktifan_dapodik' => $r->keaktifan_dapodik,
                'unit_keaktifan_dapodik' => $r->unit_keaktifan_dapodik,
                'unit_kerja' => $r->unit_kerja,
                'jabatan' => $r->jabatan,
            ])->sortByDesc('tahun_ajaran')->values()->toArray(),

            // Riwayat Pendidikan
            'riwayat_pendidikan' => $employee->educationHistories->map(fn ($e) => [
                'id' => $e->id,
                'jenjang_pendidikan' => $e->jenjang_pendidikan,
                'jurusan' => $e->jurusan,
                'instansi_pendidikan' => $e->instansi_pendidikan,
                'tahun_lulus' => $e->tahun_lulus,
                'pembiayaan' => $e->pembiayaan,
            ])->toArray(),
        ];

        return Inertia::render('StaffDetail', [
            'employee' => $employeeData,
            'selectedAcademicYear' => $selectedYear ? [
                'id' => $selectedYear->id,
                'name' => $selectedYear->name,
                'semester' => $selectedYear->semester,

            ] : null,
            'allAcademicYears' => $allYears->map(fn ($y) => [
                'id' => $y->id,
                'name' => $y->name,
                'semester' => $y->semester,
            ])->toArray(),
        ]);
    }

    /**
     * Handle XLSX employee import.
     */
    public function import(Request $request, EmployeeImportService $importService): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'file.required' => 'File Excel template wajib diunggah.',
            'file.mimes' => 'Format file harus berupa .xlsx atau .xls.',
            'file.max' => 'Ukuran file tidak boleh melebihi 10MB.',
        ]);

        $result = $importService->import($request->file('file'));

        if (! $result['success']) {
            return back()->with('error', 'Gagal mengimpor data: '.($result['errors'][0] ?? 'Terjadi kesalahan sistem.'));
        }

        $summaryMsg = "Berhasil memproses {$result['total_employees']} pegawai ({$result['created_count']} baru, {$result['updated_count']} diperbarui), {$result['education_count']} riwayat pendidikan, dan {$result['records_count']} catatan kepegawaian.";

        return back()
            ->with('success', $summaryMsg)
            ->with('import_summary', $result);
    }

    /**
     * Download the XLSX template file.
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        $spreadsheet = $this->buildTemplate();

        $tmpFile = tempnam(sys_get_temp_dir(), 'employee_template_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpFile);

        return response()->download($tmpFile, 'Template_Data_Pegawai.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * @return array<string, mixed>
     */
    private function mapEmployeeForList(Employee $employee, ?EmployeeRecord $record): array
    {
        return [
            'nipy' => $employee->nipy,
            'nama' => $employee->nama,
            'jk' => $employee->jk,
            'ttl' => $employee->ttl,
            'nik' => $employee->nik,
            'no_wa' => $employee->no_wa,
            'email' => $employee->email,
            'unit_kerja' => $record?->unit_kerja,
            'jabatan' => $record?->jabatan,
            'status_keaktifan' => $record?->status_keaktifan,
            'jenjang_kepegawaian' => $record?->jenjang_kepegawaian,
            'status_pernikahan' => $employee->status_pernikahan,
            'skill' => $employee->skill,
        ];
    }

    private function buildTemplate(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;

        $headerFill = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A6B3C']];
        $headerFont = ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10];
        $exampleFill = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']];
        $borderStyle = [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'CCCCCC'],
            ],
        ];

        // ---- Sheet 1: Data Pegawai ----
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('1. Data Pegawai');

        $headers1 = [
            'A' => 'NIPY',
            'B' => 'Nama Lengkap',
            'C' => 'JK (L/P)',
            'D' => 'Tempat Lahir',
            'E' => 'Tanggal Lahir',
            'F' => 'NIK',
            'G' => 'No WA',
            'H' => 'Email',
            'I' => 'Jalan Domisili',
            'J' => 'RT/RW Domisili',
            'K' => 'Dusun Domisili',
            'L' => 'Desa Domisili',
            'M' => 'Kecamatan Domisili',
            'N' => 'Kab Domisili',
            'O' => 'Provinsi Domisili',
            'P' => 'Jalan Tinggal',
            'Q' => 'RT/RW Tinggal',
            'R' => 'Dusun Tinggal',
            'S' => 'Desa Tinggal',
            'T' => 'Kecamatan Tinggal',
            'U' => 'Kabupaten Tinggal',
            'V' => 'Provinsi Tinggal',
            'W' => 'Status Rumah',
            'X' => 'Kepemilikan BPJS (Ya/Tidak)',
            'Y' => 'Penanggung BPJS',
            'Z' => 'Skill',
            'AA' => 'Status Pernikahan',
            'AB' => 'Nama Suami/Istri',
            'AC' => 'TTL Suami/Istri',
            'AD' => 'Pekerjaan Suami/Istri',
            'AE' => 'Tanggal Menikah',
            'AF' => 'Jumlah Anak',
            'AG' => 'Nama Ibu',
            'AH' => 'Nama Ayah',
            'AI' => 'Alamat Orangtua',
            'AJ' => 'Kontak Darurat',
            'AK' => 'Hubungan Kontak Darurat',
        ];

        foreach ($headers1 as $col => $label) {
            $sheet1->setCellValue("{$col}1", $label);
            $sheet1->getStyle("{$col}1")->applyFromArray([
                'fill' => $headerFill,
                'font' => $headerFont,
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet1->getColumnDimension($col)->setWidth(20);
        }

        // Format NIK column as Text explicitly so "0001/2" is preserved
        $sheet1->getStyle('F2:F1000')->getNumberFormat()->setFormatCode('@');
        $sheet1->getStyle('A2:A1000')->getNumberFormat()->setFormatCode('@'); // NIPY
        $sheet1->getStyle('G2:G1000')->getNumberFormat()->setFormatCode('@'); // No WA

        // Format dates
        $sheet1->getStyle('E2:E1000')->getNumberFormat()->setFormatCode('yyyy/mm/dd');
        $sheet1->getStyle('AE2:AE1000')->getNumberFormat()->setFormatCode('yyyy/mm/dd');

        // Example row
        $example1 = [
            'A' => '1001', 'B' => 'Ahmad Fauzi, S.Pd', 'C' => 'L', 'D' => 'Semarang', 'E' => '1985/06/15',
            'F' => '3301150678900001', 'G' => '081234567890', 'H' => 'ahmad@sekolah.sch.id',
            'I' => 'Jl. Merdeka No. 10', 'J' => '002/005', 'K' => 'Gondang', 'L' => 'Gondangrejo',
            'M' => 'Gondangrejo', 'N' => 'Karanganyar', 'O' => 'Jawa Tengah',
            'P' => 'Jl. Merdeka No. 10', 'Q' => '002/005', 'R' => 'Gondang', 'S' => 'Gondangrejo',
            'T' => 'Gondangrejo', 'U' => 'Jawa Tengah', 'V' => 'Milik Sendiri',
            'W' => 'Ya', 'X' => 'Pribadi',
            'Y' => 'Matematika, MS Office', 'Z' => 'Menikah', 'AA' => 'Siti Rahayu', 'AB' => 'Solo, 10/03/1987',
            'AC' => 'Guru', 'AD' => '20/12/2010', 'AE' => '2',
            'AF' => 'Siti Aminah', 'AG' => 'Budi Santoso', 'AH' => 'Jl. Damai No. 5, Semarang',
            'AI' => '082345678901', 'AJ' => 'Saudara Kandung',
        ];

        foreach ($example1 as $col => $val) {
            $sheet1->setCellValue("{$col}2", $val);
            $sheet1->getStyle("{$col}2")->applyFromArray(['fill' => $exampleFill]);
        }

        $lastCol1 = 'AJ';
        $sheet1->getStyle("A1:{$lastCol1}1")->applyFromArray(['borders' => $borderStyle]);
        $sheet1->getStyle("A2:{$lastCol1}2")->applyFromArray(['borders' => $borderStyle]);

        // ---- Sheet 2: Riwayat Pendidikan ----
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('2. Riwayat Pendidikan');

        $headers2 = [
            'A' => 'NIK',
            'B' => 'Nama Lengkap',
            'C' => 'Jenjang Pendidikan',
            'D' => 'Jurusan',
            'E' => 'Instansi Pendidikan',
            'F' => 'Tahun Lulus',
            'G' => 'Pembiayaan',
        ];

        foreach ($headers2 as $col => $label) {
            $sheet2->setCellValue("{$col}1", $label);
            $sheet2->getStyle("{$col}1")->applyFromArray([
                'fill' => $headerFill,
                'font' => $headerFont,
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet2->getColumnDimension($col)->setWidth(22);
        }

        $sheet2->getStyle('A2:A1000')->getNumberFormat()->setFormatCode('@');

        $example2 = [
            'A' => '3301150678900001', 'B' => 'Ahmad Fauzi, S.Pd', 'C' => 'S1',
            'D' => 'Pendidikan Matematika', 'E' => 'UNS Surakarta', 'F' => '2008', 'G' => 'Beasiswa',
        ];

        foreach ($example2 as $col => $val) {
            $sheet2->setCellValue("{$col}2", $val);
            $sheet2->getStyle("{$col}2")->applyFromArray(['fill' => $exampleFill]);
        }

        $sheet2->getStyle('A1:G1')->applyFromArray(['borders' => $borderStyle]);
        $sheet2->getStyle('A2:G2')->applyFromArray(['borders' => $borderStyle]);

        // ---- Sheet 3: Riwayat Kepegawaian ----
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('3. Riwayat Kepegawaian');

        $headers3 = [
            'A' => 'NIK',
            'B' => 'Nama Lengkap',
            'C' => 'Tahun Ajaran',
            'D' => 'Semester',
            'E' => 'Jenis Kepegawaian',
            'F' => 'Status Keaktifan',
            'G' => 'Keterangan Tidak Aktif',
            'H' => 'Jenjang Kepegawaian',
            'I' => 'TMT',
            'J' => 'TST Jenjang',
            'K' => 'Masa Kerja',
            'L' => 'Keaktifan Dapodik',
            'M' => 'Unit Keaktifan Dapodik',
            'N' => 'Unit Kerja',
            'O' => 'Jabatan',
        ];

        foreach ($headers3 as $col => $label) {
            $sheet3->setCellValue("{$col}1", $label);
            $sheet3->getStyle("{$col}1")->applyFromArray([
                'fill' => $headerFill,
                'font' => $headerFont,
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet3->getColumnDimension($col)->setWidth(20);
        }

        $sheet3->getStyle('A2:A1000')->getNumberFormat()->setFormatCode('@');

        $example3 = [
            'A' => '3301150678900001', 'B' => 'Ahmad Fauzi, S.Pd', 'C' => '2025/2026', 'D' => 'Ganjil',
            'E' => 'Tenaga Pendidik', 'F' => 'Aktif', 'G' => '-', 'H' => 'GTY', 'I' => '01/07/2015', 'J' => '',
            'K' => '11 Tahun', 'L' => 'Aktif', 'M' => 'SDIT Ulil Albab', 'N' => 'SDIT Ulil Albab', 'O' => 'Guru Kelas',
        ];

        foreach ($example3 as $col => $val) {
            $sheet3->setCellValue("{$col}2", $val);
            $sheet3->getStyle("{$col}2")->applyFromArray(['fill' => $exampleFill]);
        }

        $sheet3->getStyle('A1:O1')->applyFromArray(['borders' => $borderStyle]);
        $sheet3->getStyle('A2:O2')->applyFromArray(['borders' => $borderStyle]);

        // Set active sheet to sheet 1
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }
}
