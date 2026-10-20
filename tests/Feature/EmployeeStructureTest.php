<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\EmployeeRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class EmployeeStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_and_records_attributes_and_dynamic_masa_kerja(): void
    {
        $employee = Employee::create([
            'nama' => 'Budi Santoso',
            'nik' => '3301010101010001',
            'tmt_pengangkatan' => Carbon::now()->subYears(5)->subMonths(3)->format('Y-m-d'),
        ]);

        $this->assertNotNull($employee->tmt_pengangkatan);
        $this->assertEquals('5 Tahun 3 Bulan', $employee->masa_kerja);

        $academicYear = AcademicYear::create([
            'name' => '2025/2026',
            'semester' => 'Ganjil',
        ]);

        $record = EmployeeRecord::create([
            'employee_id' => $employee->id,
            'academic_year_id' => $academicYear->id,
            'jenis_kepegawaian' => 'Tenaga Pendidik',
            'status_keaktifan' => 'Aktif',
            'jenjang_kepegawaian' => 'GTY',
            'tmt_kenaikan_jenjang' => '2023-07-01',
            'tst_jenjang' => '2027-06-30',
            'keaktifan_dapodik' => 'Aktif',
            'unit_keaktifan_dapodik' => 'SDIT Ulil Albab',
            'unit_kerja' => 'SDIT Ulil Albab',
            'jabatan' => 'Guru Kelas',
        ]);

        $employee->refresh();
        $this->assertEquals('3301010101010001', $employee->nik);
        $this->assertEquals(Carbon::now()->subYears(5)->subMonths(3)->format('Y-m-d'), $employee->tmt_pengangkatan?->format('Y-m-d'));

        $record->refresh();
        $this->assertEquals($employee->id, $record->employee_id);
        $this->assertEquals('2023-07-01', $record->tmt_kenaikan_jenjang?->format('Y-m-d'));
        $this->assertEquals('SDIT Ulil Albab', $record->unit_kerja);
        $this->assertEquals('Guru Kelas', $record->jabatan);
    }

    public function test_template_download_has_correct_structure(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/staff/template');
        $response->assertStatus(200);

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_template_');
        file_put_contents($tmpFile, $response->streamedContent());

        $spreadsheet = IOFactory::load($tmpFile);

        // Sheet 1: Data Pegawai
        $sheet1 = $spreadsheet->getSheetByName('1. Data Pegawai');
        $this->assertNotNull($sheet1);
        $this->assertEquals('NIK', $sheet1->getCell('F1')->getValue());
        $this->assertEquals('TMT Pengangkatan', $sheet1->getCell('G1')->getValue());
        $this->assertEquals('No WA', $sheet1->getCell('H1')->getValue());

        // Sheet 3: Riwayat Kepegawaian
        $sheet3 = $spreadsheet->getSheetByName('3. Riwayat Kepegawaian');
        $this->assertNotNull($sheet3);
        $this->assertEquals('TMT Kenaikan Jenjang', $sheet3->getCell('I1')->getValue());
        $this->assertEquals('TST Jenjang', $sheet3->getCell('J1')->getValue());
        $this->assertEquals('Keaktifan Dapodik', $sheet3->getCell('K1')->getValue());

        // Ensure Masa Kerja does NOT exist in sheet 3 headers
        $sheet3Headers = [];
        foreach (range('A', 'N') as $col) {
            $val = $sheet3->getCell("{$col}1")->getValue();
            if ($val) {
                $sheet3Headers[] = $val;
            }
        }
        $this->assertNotContains('Masa Kerja', $sheet3Headers);

        @unlink($tmpFile);
    }

    public function test_staff_update_saves_tmt_pengangkatan_and_kenaikan_jenjang(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::create([
            'name' => '2025/2026',
            'semester' => 'Ganjil',
        ]);

        $employee = Employee::create([
            'nama' => 'Ahmad Fauzi',
            'nik' => '3301150678900001',
        ]);

        $response = $this->actingAs($user)->put("/staff/{$employee->id}", [
            'nama' => 'Ahmad Fauzi, M.Pd',
            'nik' => '3301150678900001',
            'tmt_pengangkatan' => '2018-01-10',
            'academic_year_id' => $academicYear->id,
            'unit_kerja' => 'SMP IT Ulil Albab',
            'jabatan' => 'Kepala Lab',
            'jenis_kepegawaian' => 'Tenaga Kependidikan',
            'status_keaktifan' => 'Aktif',
            'jenjang_kepegawaian' => 'PTY',
            'tmt_kenaikan_jenjang' => '2022-01-10',
            'tst_jenjang' => '2026-01-10',
            'keaktifan_dapodik' => 'Aktif',
            'unit_keaktifan_dapodik' => 'SMP IT Ulil Albab',
        ]);

        $response->assertRedirect();

        $employee->refresh();
        $this->assertEquals('2018-01-10', $employee->tmt_pengangkatan?->format('Y-m-d'));
        $this->assertEquals('Ahmad Fauzi, M.Pd', $employee->nama);

        $record = EmployeeRecord::where('employee_id', $employee->id)
            ->where('academic_year_id', $academicYear->id)
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals('SMP IT Ulil Albab', $record->unit_kerja);
        $this->assertEquals('Kepala Lab', $record->jabatan);
        $this->assertEquals('2022-01-10', $record->tmt_kenaikan_jenjang?->format('Y-m-d'));
    }

    public function test_command_moves_tmt_to_tmt_pengangkatan(): void
    {
        $academicYear = AcademicYear::create([
            'name' => '2025/2026',
            'semester' => 'Ganjil',
        ]);

        $employee = Employee::create([
            'nama' => 'Dewi Lestari',
            'nik' => '3301019999990001',
            'tmt_pengangkatan' => null,
        ]);

        $record = EmployeeRecord::create([
            'employee_id' => $employee->id,
            'academic_year_id' => $academicYear->id,
            'jenis_kepegawaian' => 'Tenaga Pendidik',
            'status_keaktifan' => 'Aktif',
            'jenjang_kepegawaian' => 'GTY',
            'tmt_kenaikan_jenjang' => '2016-08-01',
            'tst_jenjang' => null,
        ]);

        $this->assertNull($employee->tmt_pengangkatan);
        $this->assertEquals('2016-08-01', $record->tmt_kenaikan_jenjang?->format('Y-m-d'));

        // Run artisan command
        $this->artisan('employee:move-tmt')
            ->assertExitCode(0);

        $employee->refresh();
        $record->refresh();

        // TMT pengangkatan is now populated
        $this->assertEquals('2016-08-01', $employee->tmt_pengangkatan?->format('Y-m-d'));

        // tmt_kenaikan_jenjang is cleared (moved)
        $this->assertNull($record->tmt_kenaikan_jenjang);
    }
}
