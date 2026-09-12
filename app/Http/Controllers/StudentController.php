<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Services\StudentImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StudentController extends Controller
{
    /**
     * Handle XLSX student import.
     */
    public function import(Request $request, StudentImportService $importService): RedirectResponse
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

        $summaryMsg = "Berhasil memproses {$result['total_students']} siswa ({$result['created_count']} baru, {$result['updated_count']} diperbarui), {$result['family_count']} data keluarga, {$result['siblings_count']} saudara kandung, dan {$result['academic_records_count']} catatan akademik.";

        return back()
            ->with('success', $summaryMsg)
            ->with('import_summary', $result);
    }

    /**
     * Display the student directory page.
     */
    public function index(Request $request): Response
    {
        $selectedYearId = $request->query('academic_year_id') ?? session('selected_academic_year_id');
        $selectedYear = $selectedYearId
            ? AcademicYear::find($selectedYearId)
            : (AcademicYear::current() ?? AcademicYear::first());

        if ($selectedYear && session('selected_academic_year_id') !== $selectedYear->id) {
            session(['selected_academic_year_id' => $selectedYear->id]);
        }

        $students = Student::with([
            'academicRecords' => function ($query) use ($selectedYear) {
                if ($selectedYear) {
                    $query->where('academic_year_id', $selectedYear->id);
                }
                $query->with('classroom');
            },
            'family',
            'siblings',
        ])
            ->when($selectedYear, function ($query) use ($selectedYear) {
                $query->whereHas('academicRecords', function ($q) use ($selectedYear) {
                    $q->where('academic_year_id', $selectedYear->id);
                });
            })
            ->orderBy('unit')
            ->orderBy('nama')
            ->get()
            ->map(function (Student $student) {
                $family = $student->family;
                $record = $student->academicRecords->first();
                $classroom = $record?->classroom;

                return [
                    'nisn' => $student->nisn,
                    'nama' => $student->nama,
                    'nipd' => $student->nipd,
                    'jk' => $student->jk,
                    'ttl' => $student->ttl,
                    'nik' => $student->nik,
                    'no_kk' => $student->no_kk,
                    'agama' => $student->agama,
                    'unit' => $student->unit,
                    'program' => $student->program,
                    'jenjang' => $record?->jenjang,
                    'tingkat' => $record?->tingkat,
                    'kelas' => $classroom?->name,
                    'student_status' => $record?->student_status ?? 'aktif',
                    'no_wa' => $student->no_wa,
                    'sekolah_asal' => $student->sekolah_asal,
                    'status_keluarga' => $student->status_keluarga,
                    'anak_ke' => $student->anak_ke,
                    'diterima_di_jenjang' => $student->diterima_di_jenjang,
                    'tanggal_diterima' => $student->tanggal_diterima ? $student->tanggal_diterima->format('Y-m-d') : null,
                    'formatted_tanggal_diterima' => $student->tanggal_diterima ? $student->tanggal_diterima->translatedFormat('d F Y') : null,
                    'jalan' => $student->jalan,
                    'rt_rw' => $student->rt_rw,
                    'dusun' => $student->dusun,
                    'desa' => $student->desa,
                    'kecamatan' => $student->kecamatan,
                    'kabupaten' => $student->kabupaten,
                    'provinsi' => $student->provinsi,
                    'ayah' => $family ? [
                        'nama' => $family->ayah_nama,
                        'tahun_lahir' => $family->ayah_tahun_lahir,
                        'pekerjaan' => $family->ayah_pekerjaan,
                        'penghasilan' => $family->ayah_penghasilan,
                    ] : null,
                    'ibu' => $family ? [
                        'nama' => $family->ibu_nama,
                        'tahun_lahir' => $family->ibu_tahun_lahir,
                        'pekerjaan' => $family->ibu_pekerjaan,
                        'penghasilan' => $family->ibu_penghasilan,
                    ] : null,
                    'wali' => $family && $family->wali_nama ? [
                        'nama' => $family->wali_nama,
                        'hubungan' => $family->wali_hubungan,
                        'pekerjaan' => $family->wali_pekerjaan,
                        'penghasilan' => $family->wali_penghasilan,
                    ] : null,
                    'saudara' => $student->siblings->map(fn ($s) => [
                        'nama' => $s->nama,
                        'tanggal_lahir' => $s->tanggal_lahir?->format('Y-m-d'),
                    ])->toArray(),
                    'bantuan' => [
                        'desil' => $record?->desil,
                        'pip' => $record?->status_pip ?? false,
                        'pip_keterangan' => $record?->pip_keterangan,
                        'kip' => $record?->status_kip ?? false,
                        'no_kip' => $record?->no_kip,
                    ],
                ];
            });

        return Inertia::render('Students', [
            'students' => $students,
            'selectedAcademicYear' => $selectedYear ? [
                'id' => $selectedYear->id,
                'name' => $selectedYear->name,
                'semester' => $selectedYear->semester,
                'is_active' => (bool) $selectedYear->is_active,
            ] : null,
        ]);
    }

    /**
     * Stream the XLSX import template as a download.
     */
    public function downloadTemplate(): HttpResponse
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('Sistem Manajemen Sekolah')
            ->setTitle('Template Import Data Siswa');

        $this->buildSheet1($spreadsheet);
        $this->buildSheet2($spreadsheet);
        $this->buildSheet3($spreadsheet);
        $this->buildSheet4($spreadsheet);
        $this->buildSheet5($spreadsheet);
        $this->buildSheetPanduan($spreadsheet);
        $spreadsheet->setActiveSheetIndex(0);

        ob_start();
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        $content = ob_get_clean();

        $filename = 'template_import_siswa_'.date('Ymd').'.xlsx';

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    // ----------------------------------------------------------------
    // XLSX Template Sheet Builders
    // ----------------------------------------------------------------

    private function buildSheet1(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('1. Data Siswa');

        $headers = [
            'A' => ['NISN', 12],
            'B' => ['Nama Lengkap', 30],
            'C' => ['NIPD', 12],
            'D' => ['NIK', 18],
            'E' => ['No KK', 18],
            'F' => ['Unit', 35],
            'G' => ['Program', 12],
            'H' => ['JK (L/P)', 10],
            'I' => ['Tempat Lahir', 20],
            'J' => ['Tanggal Lahir (YYYY-MM-DD)', 24],
            'K' => ['Agama', 12],
            'L' => ['No WA', 16],
            'M' => ['Sekolah Asal', 30],
            'N' => ['Status Keluarga', 20],
            'O' => ['Anak ke', 10],
            'P' => ['Diterima di Jenjang/Kelas', 25],
            'Q' => ['Tanggal Diterima (YYYY-MM-DD)', 30],
        ];

        $this->applySheetHeaders($sheet, $headers, '004B23');

        $sheet->fromArray([[
            '0051234001', 'Ahmad Fauzi Rahman', '10231001',
            '3313151503140001', '3313150101080001',
            'SDIT Ulil Albab Gondangrejo', 'Umum',
            'L', 'Karanganyar', '2014-03-15', 'Islam',
            '081234567001', 'TK Aisyiyah Colomadu',
            'Anak Kandung', '1', '7', '2024-07-15'
        ]], null, 'A2');

        $this->styleExampleRow($sheet, 2, 'A', 'Q');
        $sheet->freezePane('A2');
    }

    private function buildSheet2(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('2. Alamat');

        $headers = [
            'A' => ['NISN', 12],
            'B' => ['Jalan', 30],
            'C' => ['RT/RW', 14],
            'D' => ['Dusun', 18],
            'E' => ['Desa/Kelurahan', 20],
            'F' => ['Kecamatan', 20],
            'G' => ['Kabupaten/Kota', 20],
            'H' => ['Provinsi', 20],
        ];

        $this->applySheetHeaders($sheet, $headers, '1565C0');

        $sheet->fromArray([[
            '0051234001', 'Jl. Lawu No. 12', 'RT 03 / RW 05',
            'Ngemplak', 'Colomadu', 'Colomadu', 'Karanganyar', 'Jawa Tengah',
        ]], null, 'A2');

        $this->styleExampleRow($sheet, 2, 'A', 'H');
        $sheet->freezePane('A2');
    }

    private function buildSheet3(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('3. Data Keluarga');

        $headers = [
            'A' => ['NISN', 12],
            'B' => ['Nama Ayah', 25],
            'C' => ['NIK Ayah', 18],
            'D' => ['Thn Lahir Ayah', 16],
            'E' => ['Pendidikan Ayah', 18],
            'F' => ['Pekerjaan Ayah', 20],
            'G' => ['Penghasilan Ayah', 28],
            'H' => ['Nama Ibu', 25],
            'I' => ['NIK Ibu', 18],
            'J' => ['Thn Lahir Ibu', 16],
            'K' => ['Pendidikan Ibu', 18],
            'L' => ['Pekerjaan Ibu', 20],
            'M' => ['Penghasilan Ibu', 28],
            'N' => ['Nama Wali', 25],
            'O' => ['NIK Wali', 18],
            'P' => ['Thn Lahir Wali', 16],
            'Q' => ['Hubungan Wali', 18],
            'R' => ['Pendidikan Wali', 18],
            'S' => ['Pekerjaan Wali', 20],
            'T' => ['Penghasilan Wali', 28],
        ];

        $this->applySheetHeaders($sheet, $headers, '6A1E99');

        $sheet->fromArray([[
            '0051234001',
            'Fauzi Hidayat', '', '1980', 'SMA/SMK', 'Wiraswasta', 'Rp 3.000.000 - Rp 5.000.000',
            'Siti Rahmawati', '', '1984', 'SMA/SMK', 'Ibu Rumah Tangga', 'Kurang dari Rp 1.000.000',
            '', '', '', '', '', '', '',
        ]], null, 'A2');

        $this->styleExampleRow($sheet, 2, 'A', 'T');
        $sheet->freezePane('B2');
    }

    private function buildSheet4(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('4. Saudara Kandung');

        $headers = [
            'A' => ['NISN Siswa', 12],
            'B' => ['Nama Saudara', 30],
            'C' => ['Tanggal Lahir (YYYY-MM-DD)', 26],
        ];

        $this->applySheetHeaders($sheet, $headers, 'BF360C');

        $sheet->fromArray([
            ['0051234001', 'Aisyah Fauzia', '2016-05-12'],
            ['0051234001', 'Muhammad Farhan', '2019-08-23'],
        ], null, 'A2');

        $this->styleExampleRow($sheet, 2, 'A', 'C');
        $this->styleExampleRow($sheet, 3, 'A', 'C');
        $sheet->freezePane('A2');
    }

    private function buildSheet5(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('5. Data Akademik (per TA)');

        $headers = [
            'A' => ['NISN', 12],
            'B' => ['Tahun Ajaran', 14],
            'C' => ['Semester', 12],
            'D' => ['Jenjang (SD/SMP)', 16],
            'E' => ['Tingkat (1-9)', 14],
            'F' => ['Kelas/Rombel', 16],
            'G' => ['Status Siswa', 16],
            'H' => ['Desil (1-10)', 14],
            'I' => ['Status PIP (Ya/Tidak)', 20],
            'J' => ['Keterangan PIP', 28],
            'K' => ['Status KIP (Ya/Tidak)', 20],
            'L' => ['No KIP', 20],
        ];

        $this->applySheetHeaders($sheet, $headers, '004D40');

        $sheet->fromArray([[
            '0051234001', '2025/2026', 'Ganjil', 'SD', '5', '5A',
            'aktif', '3', 'Ya', 'Penerima PIP tahap 1 TA 2025/2026', 'Ya', '6071012345670001',
        ]], null, 'A2');

        $this->styleExampleRow($sheet, 2, 'A', 'L');
        $sheet->freezePane('A2');
    }

    private function buildSheetPanduan(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('0. Panduan');
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(72);

        $sheet->setCellValue('A1', 'PANDUAN PENGISIAN TEMPLATE IMPORT DATA SISWA');
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '004B23']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(40);

        $panduan = [
            ['', ''],
            ['', 'CARA PENGGUNAAN'],
            ['1.', 'Isi data mulai dari baris ke-2 pada setiap sheet. JANGAN mengubah baris header (baris 1).'],
            ['2.', 'Baris berwarna hijau muda adalah CONTOH. Hapus baris contoh sebelum mengisi data Anda.'],
            ['3.', 'Kolom NISN adalah kunci utama antar sheet. Pastikan NISN konsisten di semua sheet.'],
            ['4.', 'Urutan: Sheet 1 - Sheet 2 - Sheet 3 - Sheet 4 - Sheet 5.'],
            ['', ''],
            ['', 'DESKRIPSI SHEET'],
            ['1.', 'Sheet "1. Data Siswa" - Data identitas tetap siswa (tidak berubah meski naik kelas).'],
            ['2.', 'Sheet "2. Alamat" - Data alamat domisili siswa (satu baris per siswa).'],
            ['3.', 'Sheet "3. Data Keluarga" - Data orang tua / wali.'],
            ['4.', 'Sheet "4. Saudara Kandung" - Satu baris per saudara.'],
            ['5.', 'Sheet "5. Data Akademik" - Data DINAMIS per Tahun Ajaran (kelas, status, bantuan).'],
            ['', ''],
            ['', 'CATATAN PENTING'],
            ['*', 'Format tanggal: YYYY-MM-DD (contoh: 2014-03-15).'],
            ['*', 'NISN harus unik. Tidak boleh ada NISN ganda di Sheet 1.'],
            ['*', 'Data Akademik boleh lebih dari 1 baris per siswa (untuk Tahun Ajaran berbeda).'],
            ['', ''],
            ['', 'UNIT & PROGRAM'],
            ['*', 'SDIT Ulil Albab Gondangrejo  ->  Program: Umum'],
            ['*', 'SMPIT Ulil Albab Gondangrejo ->  Program: Fullday'],
            ['*', 'PPTQ Ulil Albab Gondangrejo  ->  Program: Boarding'],
        ];

        $row = 2;
        foreach ($panduan as [$no, $text]) {
            $sheet->setCellValue("A{$row}", $no);
            $sheet->setCellValue("B{$row}", $text);

            if ($no === '' && $text !== '') {
                $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']],
                ]);
            }

            $sheet->getRowDimension($row)->setRowHeight(18);
            $row++;
        }

        $sheet->getStyle('A2:B'.$row)->applyFromArray([
            'font' => ['size' => 10],
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
    }

    /**
     * Apply styled header row.
     *
     * @param  array<string, array{string, int}>  $headers
     */
    private function applySheetHeaders($sheet, array $headers, string $colorRgb): void
    {
        foreach ($headers as $col => [$label, $width]) {
            $sheet->setCellValue("{$col}1", $label);
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $lastCol = array_key_last($headers);
        $range = "A1:{$lastCol}1";

        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colorRgb]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(36);
    }

    /**
     * Style an example data row.
     */
    private function styleExampleRow($sheet, int $row, string $fromCol, string $toCol): void
    {
        $sheet->getStyle("{$fromCol}{$row}:{$toCol}{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']],
            'font' => ['italic' => true, 'color' => ['rgb' => '2E7D32'], 'size' => 9],
        ]);
    }
}
