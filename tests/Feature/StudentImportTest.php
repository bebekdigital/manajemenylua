<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_rejects_non_excel_file(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->post('/students/import', [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_imports_students_from_valid_multi_sheet_xlsx(): void
    {
        // Build a test XLSX file with all 5 sheets
        $spreadsheet = new Spreadsheet;

        // Sheet 1: 1. Data Siswa
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('1. Data Siswa');
        $sheet1->fromArray([
            ['NISN', 'Nama Lengkap', 'NIPD', 'NIK', 'No KK', 'Unit', 'Program', 'JK (L/P)', 'Tempat Lahir', 'Tanggal Lahir (YYYY-MM-DD)', 'Agama', 'No WA', 'Sekolah Asal'],
            ['0099990001', 'Zaki Al-Fatih', '10239001', '3313150101140099', '3313150101080099', 'SDIT Ulil Albab Gondangrejo', 'Umum', 'L', 'Karanganyar', '2014-05-20', 'Islam', '081234567999', 'TK Permata'],
            ['0099990002', 'Naila Khansa', '10239002', '3313150202140099', '3313150101080099', 'SDIT Ulil Albab Gondangrejo', 'Umum', 'P', 'Surakarta', '2014-09-10', 'Islam', '081234567998', 'TK Aisyiyah'],
        ]);

        // Sheet 2: 2. Alamat
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('2. Alamat');
        $sheet2->fromArray([
            ['NISN', 'Jalan', 'RT/RW', 'Dusun', 'Desa/Kelurahan', 'Kecamatan', 'Kabupaten/Kota', 'Provinsi'],
            ['0099990001', 'Jl. Merbabu No. 10', 'RT 01 / RW 02', 'Jetis', 'Gondangrejo', 'Gondangrejo', 'Karanganyar', 'Jawa Tengah'],
            ['0099990002', 'Jl. Lawu No. 50', 'RT 03 / RW 01', 'Dukuh', 'Colomadu', 'Colomadu', 'Karanganyar', 'Jawa Tengah'],
        ]);

        // Sheet 3: 3. Data Keluarga
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('3. Data Keluarga');
        $sheet3->fromArray([
            ['NISN', 'Nama Ayah', 'NIK Ayah', 'Thn Lahir Ayah', 'Pendidikan Ayah', 'Pekerjaan Ayah', 'Penghasilan Ayah', 'Nama Ibu', 'NIK Ibu', 'Thn Lahir Ibu', 'Pendidikan Ibu', 'Pekerjaan Ibu', 'Penghasilan Ibu', 'Nama Wali', 'NIK Wali', 'Thn Lahir Wali', 'Hubungan Wali', 'Pendidikan Wali', 'Pekerjaan Wali', 'Penghasilan Wali'],
            ['0099990001', 'Sulaiman Fatih', '3313150101750001', '1975', 'S1', 'Wiraswasta', 'Rp 5.000.000 - Rp 10.000.000', 'Khadijah', '3313150101800001', '1980', 'S1', 'Guru', 'Rp 3.000.000 - Rp 5.000.000', '', '', '', '', '', '', ''],
        ]);

        // Sheet 4: 4. Saudara Kandung
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('4. Saudara Kandung');
        $sheet4->fromArray([
            ['NISN Siswa', 'Nama Saudara', 'Tanggal Lahir (YYYY-MM-DD)'],
            ['0099990001', 'Hasan Al-Fatih', '2017-08-14'],
            ['0099990001', 'Husain Al-Fatih', '2020-03-22'],
        ]);

        // Sheet 5: 5. Data Akademik (per TA)
        $sheet5 = $spreadsheet->createSheet();
        $sheet5->setTitle('5. Data Akademik (per TA)');
        $sheet5->fromArray([
            ['NISN', 'Tahun Ajaran', 'Semester', 'Jenjang (SD/SMP)', 'Tingkat (1-9)', 'Kelas/Rombel', 'Status Siswa', 'Desil (1-10)', 'Status PIP (Ya/Tidak)', 'Keterangan PIP', 'Status KIP (Ya/Tidak)', 'No KIP'],
            ['0099990001', '2025/2026', 'Ganjil', 'SD', '5', '5A', 'aktif', '2', 'Ya', 'Tahap 1', 'Ya', '6071012399990001'],
            ['0099990002', '2025/2026', 'Ganjil', 'SD', '5', '5B', 'aktif', '5', 'Tidak', '', 'Tidak', ''],
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_import_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $uploadedFile = new UploadedFile(
            $tempPath,
            'import_siswa_test.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->post('/students/import', [
            'file' => $uploadedFile,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('import_summary');

        // Check Student 1 inserted
        $student1 = Student::where('nisn', '0099990001')->first();
        $this->assertNotNull($student1);
        $this->assertSame('Zaki Al-Fatih', $student1->nama);
        $this->assertSame('Jl. Merbabu No. 10', $student1->jalan);
        $this->assertSame('Karanganyar', $student1->kabupaten);

        // Check Family
        $this->assertNotNull($student1->family);
        $this->assertSame('Sulaiman Fatih', $student1->family->ayah_nama);
        $this->assertSame('Khadijah', $student1->family->ibu_nama);

        // Check Siblings
        $this->assertCount(2, $student1->siblings);

        // Check Academic Record
        $activeYear = AcademicYear::where('name', '2025/2026')->where('semester', 'Ganjil')->first();
        $record1 = $student1->academicRecords()->where('academic_year_id', $activeYear->id)->first();
        $this->assertNotNull($record1);
        $this->assertSame('SD', $record1->jenjang);
        $this->assertSame(5, $record1->tingkat);
        $this->assertTrue($record1->status_pip);
        $this->assertSame('5A', $record1->classroom?->name);

        // Check Student 2 inserted
        $student2 = Student::where('nisn', '0099990002')->first();
        $this->assertNotNull($student2);
        $this->assertSame('Naila Khansa', $student2->nama);
        $this->assertSame('P', $student2->jk);

        // Clean up temp file
        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }
}
