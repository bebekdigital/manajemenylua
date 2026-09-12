<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GenerateStudentTemplate extends Command
{
    protected $signature = 'students:generate-template {--path= : Output path for the template file}';

    protected $description = 'Generate XLSX import template for student data';

    public function handle(): int
    {
        $outputPath = $this->option('path')
            ?? storage_path('app/templates/template_import_siswa.xlsx');

        // Ensure directory exists
        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('Sistem Manajemen Sekolah')
            ->setTitle('Template Import Data Siswa')
            ->setDescription('Template Excel untuk import data siswa ke dalam sistem');

        $this->buildSheet1($spreadsheet);
        $this->buildSheet2($spreadsheet);
        $this->buildSheet3($spreadsheet);
        $this->buildSheet4($spreadsheet);
        $this->buildSheet5($spreadsheet);
        $this->buildSheetPanduan($spreadsheet);

        // Set Sheet 1 as active
        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $writer->save($outputPath);

        $this->info("Template berhasil dibuat: {$outputPath}");

        return Command::SUCCESS;
    }

    // ----------------------------------------------------------------
    // SHEET 1 — Data Siswa (Master / Identitas)
    // ----------------------------------------------------------------
    private function buildSheet1(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('1. Data Siswa');

        $headers = [
            'A' => ['NISN', 10],
            'B' => ['Nama Lengkap', 30],
            'C' => ['NIPD', 12],
            'D' => ['NIK', 18],
            'E' => ['No KK', 18],
            'F' => ['Unit', 35],
            'G' => ['Program', 12],
            'H' => ['JK (L/P)', 10],
            'I' => ['Tempat Lahir', 20],
            'J' => ['Tanggal Lahir (YYYY-MM-DD)', 22],
            'K' => ['Agama', 12],
            'L' => ['No WA', 16],
            'M' => ['Sekolah Asal', 30],
        ];

        $this->applyHeaders($sheet, $headers, 'FF004B23');

        // Data validation hints
        $notes = [
            'F2' => "Pilihan:\n- SDIT Ulil Albab Gondangrejo\n- SMPIT Ulil Albab Gondangrejo\n- PPTQ Ulil Albab Gondangrejo",
            'G2' => "Pilihan:\n- Umum (untuk SDIT)\n- Fullday (untuk SMPIT)\n- Boarding (untuk PPTQ)",
            'H2' => 'L = Laki-laki, P = Perempuan',
            'J2' => 'Format: YYYY-MM-DD\nContoh: 2014-03-15',
            'K2' => 'Pilihan: Islam, Kristen, Katolik, Hindu, Buddha, Konghucu',
        ];

        foreach ($notes as $cell => $note) {
            $sheet->getComment($cell)->getText()->createTextRun($note);
        }

        // Example data row
        $example = [
            '0051234001', 'Ahmad Fauzi Rahman', '10231001',
            '3313151503140001', '3313150101080001',
            'SDIT Ulil Albab Gondangrejo', 'Umum',
            'L', 'Karanganyar', '2014-03-15', 'Islam',
            '081234567001', 'TK Aisyiyah Colomadu',
        ];
        $sheet->fromArray([$example], null, 'A2');
        $this->styleExampleRow($sheet, 2, 'A', 'M');

        $sheet->freezePane('A2');
    }

    // ----------------------------------------------------------------
    // SHEET 2 — Alamat
    // ----------------------------------------------------------------
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

        $this->applyHeaders($sheet, $headers, 'FF1565C0');

        $example = [
            '0051234001', 'Jl. Lawu No. 12', 'RT 03 / RW 05',
            'Ngemplak', 'Colomadu', 'Colomadu', 'Karanganyar', 'Jawa Tengah',
        ];
        $sheet->fromArray([$example], null, 'A2');
        $this->styleExampleRow($sheet, 2, 'A', 'H');

        $sheet->freezePane('A2');
    }

    // ----------------------------------------------------------------
    // SHEET 3 — Data Keluarga
    // ----------------------------------------------------------------
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

        $this->applyHeaders($sheet, $headers, 'FF6A1E99');

        $penghasilanNote = "Pilihan:\n- Kurang dari Rp 1.000.000\n- Rp 1.000.000 - Rp 3.000.000\n- Rp 3.000.000 - Rp 5.000.000\n- Rp 5.000.000 - Rp 10.000.000\n- Rp 10.000.000 ke atas";
        foreach (['G2', 'M2', 'T2'] as $cell) {
            $sheet->getComment($cell)->getText()->createTextRun($penghasilanNote);
        }

        $example = [
            '0051234001',
            'Fauzi Hidayat', '', '1980', 'SMA/SMK', 'Wiraswasta', 'Rp 3.000.000 - Rp 5.000.000',
            'Siti Rahmawati', '', '1984', 'SMA/SMK', 'Ibu Rumah Tangga', 'Kurang dari Rp 1.000.000',
            '', '', '', '', '', '', '',
        ];
        $sheet->fromArray([$example], null, 'A2');
        $this->styleExampleRow($sheet, 2, 'A', 'T');

        $sheet->freezePane('B2');
    }

    // ----------------------------------------------------------------
    // SHEET 4 — Saudara Kandung
    // ----------------------------------------------------------------
    private function buildSheet4(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('4. Saudara Kandung');

        $headers = [
            'A' => ['NISN Siswa', 12],
            'B' => ['Nama Saudara', 30],
            'C' => ['Tanggal Lahir (YYYY-MM-DD)', 26],
        ];

        $this->applyHeaders($sheet, $headers, 'FFBF360C');

        $examples = [
            ['0051234001', 'Aisyah Fauzia', '2016-05-12'],
            ['0051234001', 'Muhammad Farhan', '2019-08-23'],
        ];
        $sheet->fromArray($examples, null, 'A2');
        $this->styleExampleRow($sheet, 2, 'A', 'C');
        $this->styleExampleRow($sheet, 3, 'A', 'C');

        $sheet->getComment('A2')->getText()->createTextRun(
            "Satu baris per saudara kandung.\nJika siswa punya 3 saudara, buat 3 baris dengan NISN yang sama."
        );

        $sheet->freezePane('A2');
    }

    // ----------------------------------------------------------------
    // SHEET 5 — Data Akademik per Tahun Ajaran
    // ----------------------------------------------------------------
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

        $this->applyHeaders($sheet, $headers, 'FF004D40');

        $notes = [
            'B2' => 'Format: 2025/2026',
            'C2' => "Pilihan:\n- Ganjil\n- Genap",
            'D2' => "Pilihan:\n- SD\n- SMP",
            'G2' => "Pilihan:\n- aktif\n- mutasi_masuk\n- mutasi_keluar\n- lulus\n- mengulang\n- dropout",
            'H2' => 'Angka 1-10 (Desil P-DAST dari DTKS)\n1 = termiskin, 10 = terkaya',
            'I2' => 'Ketik: Ya atau Tidak',
            'K2' => 'Ketik: Ya atau Tidak',
        ];

        foreach ($notes as $cell => $note) {
            $sheet->getComment($cell)->getText()->createTextRun($note);
        }

        $example = [
            '0051234001', '2025/2026', 'Ganjil', 'SD', '5', '5A',
            'aktif', '3', 'Ya', 'Penerima PIP tahap 1 TA 2025/2026', 'Ya', '6071012345670001',
        ];
        $sheet->fromArray([$example], null, 'A2');
        $this->styleExampleRow($sheet, 2, 'A', 'L');

        $sheet->freezePane('A2');
    }

    // ----------------------------------------------------------------
    // SHEET 6 — Panduan Pengisian
    // ----------------------------------------------------------------
    private function buildSheetPanduan(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('0. Panduan');
        $spreadsheet->setActiveSheetIndex($spreadsheet->getIndex($sheet));

        // Move panduan to first position
        $spreadsheet->setActiveSheetIndexByName('0. Panduan');

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(70);

        // Title
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
            ['', '📋 CARA PENGGUNAAN'],
            ['1.', 'Isi data mulai dari baris ke-2 pada setiap sheet. JANGAN mengubah atau menghapus baris header (baris 1).'],
            ['2.', 'Baris berwarna hijau muda adalah CONTOH. Hapus atau timpa baris contoh sebelum mengisi data Anda.'],
            ['3.', 'Kolom NISN adalah kunci utama yang menghubungkan data antar sheet. Pastikan NISN konsisten.'],
            ['4.', 'Urutan pengisian yang disarankan: Sheet 1 → Sheet 2 → Sheet 3 → Sheet 4 → Sheet 5.'],
            ['', ''],
            ['', '📄 DESKRIPSI SHEET'],
            ['1.', 'Sheet "1. Data Siswa" — Data identitas tetap siswa (tidak berubah meski naik kelas).'],
            ['2.', 'Sheet "2. Alamat" — Data alamat domisili siswa (satu baris per siswa).'],
            ['3.', 'Sheet "3. Data Keluarga" — Data orang tua/wali (Ayah, Ibu, Wali opsional).'],
            ['4.', 'Sheet "4. Saudara Kandung" — Data saudara kandung. Satu baris per saudara.'],
            ['5.', 'Sheet "5. Data Akademik" — Data DINAMIS per Tahun Ajaran (kelas, status, bantuan).'],
            ['', ''],
            ['', '⚠️  CATATAN PENTING'],
            ['•', 'Format tanggal: YYYY-MM-DD (contoh: 2014-03-15). Jika salah format, data akan gagal diimport.'],
            ['•', 'NISN harus unik. Pastikan tidak ada NISN yang sama di Sheet 1.'],
            ['•', 'Kolom Unit harus persis sama dengan pilihan yang tersedia (lihat komentar di sel).'],
            ['•', 'Data Akademik (Sheet 5) dapat diisi lebih dari 1 baris per siswa untuk Tahun Ajaran berbeda.'],
            ['•', 'Kolom yang tidak wajib boleh dikosongkan.'],
            ['', ''],
            ['', '🏫 UNIT & PROGRAM YANG BERLAKU'],
            ['•', 'SDIT Ulil Albab Gondangrejo → Program: Umum'],
            ['•', 'SMPIT Ulil Albab Gondangrejo → Program: Fullday'],
            ['•', 'PPTQ Ulil Albab Gondangrejo → Program: Boarding'],
        ];

        $row = 2;
        foreach ($panduan as [$no, $text]) {
            $sheet->setCellValue("A{$row}", $no);
            $sheet->setCellValue("B{$row}", $text);

            if (in_array($no, ['', '📋 CARA PENGGUNAAN', '📄 DESKRIPSI SHEET', '⚠️  CATATAN PENTING', '🏫 UNIT & PROGRAM YANG BERLAKU'])) {
                if ($text !== '') {
                    $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']],
                    ]);
                }
            }

            $sheet->getRowDimension($row)->setRowHeight(18);
            $row++;
        }

        $sheet->getStyle('A2:B'.$row)->applyFromArray([
            'font' => ['size' => 10],
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Reorder: Panduan first
        $spreadsheet->setActiveSheetIndex(0);
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /**
     * Apply styled header row to a sheet.
     *
     * @param  array<string, array{string, int}>  $headers  [col => [label, width]]
     */
    private function applyHeaders($sheet, array $headers, string $colorRgb): void
    {
        foreach ($headers as $col => [$label, $width]) {
            $sheet->setCellValue("{$col}1", $label);
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $lastCol = array_key_last($headers);
        $range = "A1:{$lastCol}1";

        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => ltrim($colorRgb, 'FF')]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(36);
    }

    /**
     * Style an example data row with a light highlight.
     */
    private function styleExampleRow($sheet, int $row, string $fromCol, string $toCol): void
    {
        $sheet->getStyle("{$fromCol}{$row}:{$toCol}{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']],
            'font' => ['italic' => true, 'color' => ['rgb' => '2E7D32'], 'size' => 9],
        ]);
    }
}
