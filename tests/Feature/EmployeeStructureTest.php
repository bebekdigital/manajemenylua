<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeePosition;
use App\Models\EmployeeRecord;
use App\Models\SchoolLevel;
use App\Models\SchoolUnit;
use App\Models\User;
use App\Services\EmployeeImportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class EmployeeStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_and_records_attributes_and_dynamic_masa_kerja(): void
    {
        $employee = Employee::create([
            'nama' => 'Budi Santoso',
            'gelar_depan' => 'Dr.',
            'gelar_belakang' => 'M.Pd.',
            'nik' => '3301010101010001',
            'tmt_pengangkatan' => Carbon::now()->subYears(5)->subMonths(3)->format('Y-m-d'),
            'tst_pengangkatan' => null,
            'keaktifan_dapodik' => 'Aktif',
            'unit_keaktifan_dapodik' => 'SDIT Ulil Albab',
            'status_keaktifan' => 'Aktif',
        ]);

        $this->assertNotNull($employee->tmt_pengangkatan);
        // TST null: calculated from now() - tmt_pengangkatan
        $this->assertEquals('5 Tahun 3 Bulan', $employee->masa_kerja);
        $this->assertEquals('Dr. Budi Santoso, M.Pd.', $employee->nama_lengkap_gelar);
        $this->assertEquals('Aktif', $employee->keaktifan_dapodik);
        $this->assertEquals('SDIT Ulil Albab', $employee->unit_keaktifan_dapodik);

        // When TST is set: calculated from TST - TMT
        $employee->update([
            'tst_pengangkatan' => Carbon::parse($employee->tmt_pengangkatan)->addYears(2)->addMonths(4)->format('Y-m-d'),
        ]);
        $this->assertEquals('2 Tahun 4 Bulan', $employee->fresh()->masa_kerja);

        $record = EmployeeRecord::create([
            'employee_id' => $employee->id,
            'jenjang_kepegawaian' => 'GTY',
            'tmt_kenaikan_jenjang' => '2023-07-01',
            'tst_kenaikan_jenjang' => '2027-06-30',
        ]);

        $position = EmployeePosition::create([
            'employee_id' => $employee->id,
            'unit_kerja' => 'SDIT Ulil Albab',
            'jabatan' => 'Guru Kelas',
            'jenis_kepegawaian' => 'Tenaga Pendidik',
            'tmt_jabatan' => '2023-07-01',
            'tst_jabatan' => '2027-06-30',
        ]);

        $employee->refresh();
        $this->assertEquals('3301010101010001', $employee->nik);
        $this->assertEquals('Dr.', $employee->gelar_depan);
        $this->assertEquals('M.Pd.', $employee->gelar_belakang);
        $this->assertEquals(Carbon::now()->subYears(5)->subMonths(3)->format('Y-m-d'), $employee->tmt_pengangkatan?->format('Y-m-d'));

        $record->refresh();
        $this->assertEquals($employee->id, $record->employee_id);
        $this->assertEquals('GTY', $record->jenjang_kepegawaian);
        $this->assertEquals('2023-07-01', $record->tmt_kenaikan_jenjang?->format('Y-m-d'));
        $this->assertEquals('2027-06-30', $record->tst_kenaikan_jenjang?->format('Y-m-d'));

        $position->refresh();
        $this->assertEquals($employee->id, $position->employee_id);
        $this->assertEquals('SDIT Ulil Albab', $position->unit_kerja);
        $this->assertEquals('Guru Kelas', $position->jabatan);
        $this->assertEquals('Tenaga Pendidik', $position->jenis_kepegawaian);
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
        $this->assertEquals('Nama Lengkap', $sheet1->getCell('B1')->getValue());
        $this->assertEquals('Gelar Depan', $sheet1->getCell('C1')->getValue());
        $this->assertEquals('Gelar Belakang', $sheet1->getCell('D1')->getValue());
        $this->assertEquals('JK (L/P)', $sheet1->getCell('E1')->getValue());
        $this->assertEquals('NIK', $sheet1->getCell('H1')->getValue());
        $this->assertEquals('TMT Pengangkatan', $sheet1->getCell('I1')->getValue());
        $this->assertEquals('TST Pengangkatan', $sheet1->getCell('J1')->getValue());
        $this->assertEquals('Keaktifan Dapodik', $sheet1->getCell('K1')->getValue());
        $this->assertEquals('Unit Keaktifan Dapodik', $sheet1->getCell('L1')->getValue());
        $this->assertEquals('Status Keaktifan', $sheet1->getCell('M1')->getValue());
        $this->assertEquals('Keterangan Tidak Aktif', $sheet1->getCell('N1')->getValue());
        $this->assertEquals('No WA', $sheet1->getCell('O1')->getValue());
        $this->assertEquals('Kepemilikan BPJS (Ya/Tidak)', $sheet1->getCell('AF1')->getValue());
        $this->assertEquals('Penanggung BPJS', $sheet1->getCell('AG1')->getValue());

        // Sheet 3: Riwayat Kepegawaian (Tersisa NIK, Nama Lengkap, TMT Kenaikan Jenjang, TST Kenaikan Jenjang, Jenjang Kepegawaian)
        $sheet3 = $spreadsheet->getSheetByName('3. Riwayat Kepegawaian');
        $this->assertNotNull($sheet3);
        $this->assertEquals('NIK', $sheet3->getCell('A1')->getValue());
        $this->assertEquals('Nama Lengkap', $sheet3->getCell('B1')->getValue());
        $this->assertEquals('TMT Kenaikan Jenjang', $sheet3->getCell('C1')->getValue());
        $this->assertEquals('TST Kenaikan Jenjang', $sheet3->getCell('D1')->getValue());
        $this->assertEquals('Jenjang Kepegawaian', $sheet3->getCell('E1')->getValue());

        // Ensure Tahun Ajaran & Semester do NOT exist in sheet 3 headers
        $sheet3Headers = [];
        foreach (range('A', 'Z') as $col) {
            $val = $sheet3->getCell("{$col}1")->getValue();
            if ($val) {
                $sheet3Headers[] = $val;
            }
        }
        $this->assertNotContains('Tahun Ajaran', $sheet3Headers);
        $this->assertNotContains('Semester', $sheet3Headers);

        // Sheet 4: Riwayat Jabatan (NIK, Nama Lengkap, TMT Jabatan, TST Jabatan, Unit Kerja, Jabatan, Jenis Kepegawaian)
        $sheet4 = $spreadsheet->getSheetByName('4. Riwayat Jabatan');
        $this->assertNotNull($sheet4);
        $this->assertEquals('NIK', $sheet4->getCell('A1')->getValue());
        $this->assertEquals('Nama Lengkap', $sheet4->getCell('B1')->getValue());
        $this->assertEquals('TMT Jabatan', $sheet4->getCell('C1')->getValue());
        $this->assertEquals('TST Jabatan', $sheet4->getCell('D1')->getValue());
        $this->assertEquals('Unit Kerja', $sheet4->getCell('E1')->getValue());
        $this->assertEquals('Jabatan', $sheet4->getCell('F1')->getValue());
        $this->assertEquals('Jenis Kepegawaian', $sheet4->getCell('G1')->getValue());

        @unlink($tmpFile);
    }

    public function test_staff_update_saves_tmt_pengangkatan_and_positions(): void
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'nama' => 'Ahmad Fauzi',
            'nik' => '3301150678900001',
        ]);

        $response = $this->actingAs($user)->put("/staff/{$employee->id}", [
            'nama' => 'Ahmad Fauzi',
            'gelar_depan' => 'Ust.',
            'gelar_belakang' => 'S.Pd.I',
            'nik' => '3301150678900001',
            'kepemilikan_bpjs' => 'Ya',
            'penanggung_bpjs' => 'Yayasan',
            'tmt_pengangkatan' => '2018-01-10',
            'tst_pengangkatan' => '2028-01-10',
            'status_keaktifan' => 'Aktif',
            'keaktifan_dapodik' => 'Aktif',
            'unit_keaktifan_dapodik' => 'SMP IT Ulil Albab',
            'unit_kerja' => 'SMP IT Ulil Albab',
            'jabatan' => 'Kepala Lab',
            'jenis_kepegawaian' => 'Tenaga Kependidikan',
            'tmt_jabatan' => '2020-07-01',
            'jenjang_kepegawaian' => 'PTY',
            'tmt_kenaikan_jenjang' => '2022-01-10',
            'tst_kenaikan_jenjang' => '2026-01-10',
        ]);

        $response->assertRedirect();

        $employee->refresh();
        $this->assertEquals('2018-01-10', $employee->tmt_pengangkatan?->format('Y-m-d'));
        $this->assertEquals('2028-01-10', $employee->tst_pengangkatan?->format('Y-m-d'));
        $this->assertEquals('Ahmad Fauzi', $employee->nama);
        $this->assertEquals('Ust.', $employee->gelar_depan);
        $this->assertEquals('S.Pd.I', $employee->gelar_belakang);
        $this->assertEquals('Ust. Ahmad Fauzi, S.Pd.I', $employee->nama_lengkap_gelar);
        $this->assertEquals('Ya', $employee->kepemilikan_bpjs);
        $this->assertEquals('Yayasan', $employee->penanggung_bpjs);
        $this->assertEquals('Aktif', $employee->status_keaktifan);
        $this->assertEquals('SMP IT Ulil Albab', $employee->unit_keaktifan_dapodik);

        $record = EmployeeRecord::where('employee_id', $employee->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals('PTY', $record->jenjang_kepegawaian);
        $this->assertEquals('2022-01-10', $record->tmt_kenaikan_jenjang?->format('Y-m-d'));

        $position = EmployeePosition::where('employee_id', $employee->id)->first();
        $this->assertNotNull($position);
        $this->assertEquals('SMP IT Ulil Albab', $position->unit_kerja);
        $this->assertEquals('Kepala Lab', $position->jabatan);
        $this->assertEquals('2020-07-01', $position->tmt_jabatan?->format('Y-m-d'));
    }

    public function test_command_moves_tmt_to_tmt_pengangkatan(): void
    {
        $employee = Employee::create([
            'nama' => 'Dewi Lestari',
            'nik' => '3301019999990001',
            'tmt_pengangkatan' => null,
        ]);

        $record = EmployeeRecord::create([
            'employee_id' => $employee->id,
            'jenjang_kepegawaian' => 'GTY',
            'tmt_kenaikan_jenjang' => '2016-08-01',
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

    public function test_employee_import_handles_all_four_sheets(): void
    {
        $spreadsheet = new Spreadsheet;

        // Sheet 1: Data Pegawai
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('1. Data Pegawai');
        $sheet1->fromArray([
            ['NIPY', 'Nama Lengkap', 'Gelar Depan', 'Gelar Belakang', 'JK (L/P)', 'Tempat Lahir', 'Tanggal Lahir', 'NIK', 'TMT Pengangkatan', 'TST Pengangkatan', 'Keaktifan Dapodik', 'Unit Keaktifan Dapodik', 'Status Keaktifan', 'Keterangan Tidak Aktif', 'No WA'],
            ['1002', 'Siti Nurhaliza', 'Prof. Dr.', 'M.Si.', 'P', 'Solo', '1980-05-12', '3301018005120002', '2010-09-01', '2030-09-01', 'Aktif', 'SDIT Ulil Albab', 'Aktif', '-', '081234567891'],
        ]);

        // Sheet 3: Riwayat Kepegawaian (Jenjang)
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('3. Riwayat Kepegawaian');
        $sheet3->fromArray([
            ['NIK', 'Nama Lengkap', 'TMT Kenaikan Jenjang', 'TST Kenaikan Jenjang', 'Jenjang Kepegawaian'],
            ['3301018005120002', 'Siti Nurhaliza', '2020-07-01', '', 'GTY'],
        ]);

        // Sheet 4: Riwayat Jabatan
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('4. Riwayat Jabatan');
        $sheet4->fromArray([
            ['NIK', 'Nama Lengkap', 'TMT Jabatan', 'TST Jabatan', 'Unit Kerja', 'Jabatan', 'Jenis Kepegawaian'],
            ['3301018005120002', 'Siti Nurhaliza', '2020-07-01', '', 'SDIT Ulil Albab', 'Kepala Sekolah', 'Tenaga Pendidik'],
        ]);

        $tmpFile = tempnam(sys_get_temp_dir(), 'import_test_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpFile);

        $importService = app(EmployeeImportService::class);
        $result = $importService->import($tmpFile);

        $this->assertEquals(1, $result['created_count']);
        $this->assertEquals(1, $result['records_count']);
        $this->assertEquals(1, $result['positions_count']);

        $employee = Employee::where('nik', '3301018005120002')->first();
        $this->assertNotNull($employee);
        $this->assertEquals('Siti Nurhaliza', $employee->nama);
        $this->assertEquals('Prof. Dr.', $employee->gelar_depan);
        $this->assertEquals('M.Si.', $employee->gelar_belakang);
        $this->assertEquals('2010-09-01', $employee->tmt_pengangkatan?->format('Y-m-d'));
        $this->assertEquals('2030-09-01', $employee->tst_pengangkatan?->format('Y-m-d'));
        $this->assertEquals('Aktif', $employee->keaktifan_dapodik);
        $this->assertEquals('SDIT Ulil Albab', $employee->unit_keaktifan_dapodik);
        $this->assertEquals('Aktif', $employee->status_keaktifan);

        $record = EmployeeRecord::where('employee_id', $employee->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals('GTY', $record->jenjang_kepegawaian);
        $this->assertEquals('2020-07-01', $record->tmt_kenaikan_jenjang?->format('Y-m-d'));

        $position = EmployeePosition::where('employee_id', $employee->id)->first();
        $this->assertNotNull($position);
        $this->assertEquals('Kepala Sekolah', $position->jabatan);
        $this->assertEquals('SDIT Ulil Albab', $position->unit_kerja);
        $this->assertEquals('Tenaga Pendidik', $position->jenis_kepegawaian);

        @unlink($tmpFile);
    }

    public function test_database_columns_match_template_and_er_has_no_academic_year_id(): void
    {
        // 1. Employee records (ER) should NOT have academic_year_id or moved columns
        $this->assertFalse(Schema::hasColumn('employee_records', 'academic_year_id'));
        $this->assertFalse(Schema::hasColumn('employee_records', 'unit_kerja'));
        $this->assertFalse(Schema::hasColumn('employee_records', 'jabatan'));
        $this->assertFalse(Schema::hasColumn('employee_records', 'jenis_kepegawaian'));
        $this->assertFalse(Schema::hasColumn('employee_records', 'status_keaktifan'));
        $this->assertFalse(Schema::hasColumn('employee_records', 'keaktifan_dapodik'));
        $this->assertFalse(Schema::hasColumn('employee_records', 'unit_keaktifan_dapodik'));
        $this->assertFalse(Schema::hasColumn('employee_records', 'keterangan_tidak_aktif'));
        $this->assertFalse(Schema::hasColumn('employee_records', 'tst_jenjang'));
        $this->assertFalse(Schema::hasColumn('employee_records', 'masa_kerja'));

        // ER should have: id, employee_id, tmt_kenaikan_jenjang, tst_kenaikan_jenjang, jenjang_kepegawaian, timestamps
        $this->assertTrue(Schema::hasColumn('employee_records', 'tmt_kenaikan_jenjang'));
        $this->assertTrue(Schema::hasColumn('employee_records', 'tst_kenaikan_jenjang'));
        $this->assertTrue(Schema::hasColumn('employee_records', 'jenjang_kepegawaian'));
        $this->assertTrue(Schema::hasColumn('employee_records', 'no_sk'));

        // 2. Employee positions (EP) should have position columns
        $this->assertTrue(Schema::hasTable('employee_positions'));
        $this->assertTrue(Schema::hasColumn('employee_positions', 'employee_id'));
        $this->assertTrue(Schema::hasColumn('employee_positions', 'unit_kerja'));
        $this->assertTrue(Schema::hasColumn('employee_positions', 'jabatan'));
        $this->assertTrue(Schema::hasColumn('employee_positions', 'jenis_kepegawaian'));
        $this->assertTrue(Schema::hasColumn('employee_positions', 'no_sk'));
        $this->assertTrue(Schema::hasColumn('employee_positions', 'tmt_jabatan'));
        $this->assertTrue(Schema::hasColumn('employee_positions', 'tst_jabatan'));

        // 3. Employees should have static pengangkatan, dapodik, and keaktifan columns
        $this->assertTrue(Schema::hasColumn('employees', 'tmt_pengangkatan'));
        $this->assertTrue(Schema::hasColumn('employees', 'tst_pengangkatan'));
        $this->assertTrue(Schema::hasColumn('employees', 'keaktifan_dapodik'));
        $this->assertTrue(Schema::hasColumn('employees', 'unit_keaktifan_dapodik'));
        $this->assertTrue(Schema::hasColumn('employees', 'status_keaktifan'));
        $this->assertTrue(Schema::hasColumn('employees', 'keterangan_tidak_aktif'));
    }

    public function test_migration_preserves_and_transfers_existing_data(): void
    {
        $migration = require database_path('migrations/2026_10_21_120000_remove_academic_year_and_clean_employee_records.php');

        // Rollback to legacy state
        $migration->down();

        // Insert legacy data
        $empId = DB::table('employees')->insertGetId([
            'nama' => 'Ustadz Salman',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_records')->insert([
            'employee_id' => $empId,
            'academic_year_id' => null,
            'jenis_kepegawaian' => 'Tenaga Pendidik',
            'status_keaktifan' => 'Tidak Aktif',
            'keterangan_tidak_aktif' => 'Cuti Belajar',
            'jenjang_kepegawaian' => 'GTY Tingkat 2',
            'tmt_kenaikan_jenjang' => '2019-01-01',
            'tst_jenjang' => '2023-01-01',
            'keaktifan_dapodik' => 'Aktif',
            'unit_keaktifan_dapodik' => 'SMA IT Ulil Albab',
            'unit_kerja' => 'SMA IT Ulil Albab',
            'jabatan' => 'Wakil Kepala Sekolah',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Run migration up()
        $migration->up();

        // 1. Verify data transferred to employees
        $emp = DB::table('employees')->where('id', $empId)->first();
        $this->assertNotNull($emp);
        $this->assertEquals('Tidak Aktif', $emp->status_keaktifan);
        $this->assertEquals('Cuti Belajar', $emp->keterangan_tidak_aktif);
        $this->assertEquals('Aktif', $emp->keaktifan_dapodik);
        $this->assertEquals('SMA IT Ulil Albab', $emp->unit_keaktifan_dapodik);

        // 2. Verify data transferred to employee_positions
        $pos = DB::table('employee_positions')->where('employee_id', $empId)->first();
        $this->assertNotNull($pos);
        $this->assertEquals('SMA IT Ulil Albab', $pos->unit_kerja);
        $this->assertEquals('Wakil Kepala Sekolah', $pos->jabatan);
        $this->assertEquals('Tenaga Pendidik', $pos->jenis_kepegawaian);
        $this->assertEquals('2019-01-01', $pos->tmt_jabatan);
        $this->assertEquals('2023-01-01', $pos->tst_jabatan);

        // 3. Verify data retained in employee_records
        $rec = DB::table('employee_records')->where('employee_id', $empId)->first();
        $this->assertNotNull($rec);
        $this->assertEquals('GTY Tingkat 2', $rec->jenjang_kepegawaian);
        $this->assertEquals('2019-01-01', $rec->tmt_kenaikan_jenjang);
        $this->assertEquals('2023-01-01', $rec->tst_kenaikan_jenjang);
    }

    public function test_staff_detail_page_includes_school_units_and_departments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = Employee::create(['nama' => 'Ahmad Dahlan', 'nik' => '3301010101019999']);

        $level = SchoolLevel::create(['name' => 'SD', 'order' => 1]);
        $unit = SchoolUnit::create(['school_level_id' => $level->id, 'name' => 'SDIT Ulil Albab']);
        $deptSD = Department::create(['name' => 'SDIT Ulil Albab']);
        $deptSD->positions()->create(['name' => 'Guru Kelas']);

        $dept = Department::create(['name' => 'Bidang Sarpras']);
        $dept->positions()->create(['name' => 'Kepala Bidang']);

        $response = $this->actingAs($admin)->get(route('staff.show', $employee));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('StaffDetail')
            ->has('schoolUnits', 1)
            ->where('schoolUnits.0.name', 'SDIT Ulil Albab')
            ->where('schoolUnits.0.positions.0', 'Guru Kelas')
            ->has('departments', 2)
            ->where('departments.0.name', 'Bidang Sarpras')
            ->where('departments.0.positions.0', 'Kepala Bidang')
        );
    }

    public function test_staff_update_saves_and_returns_no_sk_in_records_and_positions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = Employee::create(['nama' => 'Zaid bin Haritsah', 'nik' => '3301010101018888']);

        $response = $this->actingAs($admin)->put(route('staff.update', $employee), [
            'nama' => 'Zaid bin Haritsah',
            'nik' => '3301010101018888',
            'riwayat_kepegawaian' => [
                [
                    'jenjang_kepegawaian' => 'GTY',
                    'no_sk' => 'SK-JENJANG-2026/001',
                    'tmt_kenaikan_jenjang' => '2026-01-01',
                    'tst_kenaikan_jenjang' => null,
                ],
            ],
            'riwayat_jabatan' => [
                [
                    'unit_kerja' => 'SDIT Ulil Albab',
                    'jabatan' => 'Guru Kelas',
                    'jenis_kepegawaian' => 'Tenaga Pendidik',
                    'no_sk' => 'SK-JABATAN-2026/009',
                    'tmt_jabatan' => '2026-01-01',
                    'tst_jabatan' => null,
                ],
            ],
        ]);

        $response->assertRedirect();

        $record = EmployeeRecord::where('employee_id', $employee->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals('SK-JENJANG-2026/001', $record->no_sk);

        $position = EmployeePosition::where('employee_id', $employee->id)->first();
        $this->assertNotNull($position);
        $this->assertEquals('SK-JABATAN-2026/009', $position->no_sk);

        // Verify show page returns no_sk in props
        $showResponse = $this->actingAs($admin)->get(route('staff.show', $employee));
        $showResponse->assertOk();
        $showResponse->assertInertia(fn ($page) => $page
            ->component('StaffDetail')
            ->where('employee.riwayat_kepegawaian.0.no_sk', 'SK-JENJANG-2026/001')
            ->where('employee.riwayat_jabatan.0.no_sk', 'SK-JABATAN-2026/009')
        );
    }

    public function test_staff_update_saves_and_returns_riwayat_pelatihan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = Employee::create(['nama' => 'Salman Al Farisi', 'nik' => '3301010101017777']);

        $response = $this->actingAs($admin)->put(route('staff.update', $employee), [
            'nama' => 'Salman Al Farisi',
            'nik' => '3301010101017777',
            'riwayat_pelatihan' => [
                [
                    'bidang' => 'Teknologi Informasi',
                    'keahlian' => 'Web Development Laravel',
                    'instansi_penyelenggara' => 'Balai Diklat Keagamaan',
                    'tahun' => '2024',
                    'pembiayaan' => 'Beasiswa Yayasan',
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('employee_training_histories', [
            'employee_id' => $employee->id,
            'bidang' => 'Teknologi Informasi',
            'keahlian' => 'Web Development Laravel',
            'instansi_penyelenggara' => 'Balai Diklat Keagamaan',
            'tahun' => '2024',
            'pembiayaan' => 'Beasiswa Yayasan',
        ]);

        // Verify show page returns riwayat_pelatihan in props
        $showResponse = $this->actingAs($admin)->get(route('staff.show', $employee));
        $showResponse->assertOk();
        $showResponse->assertInertia(fn ($page) => $page
            ->component('StaffDetail')
            ->has('employee.riwayat_pelatihan', 1)
            ->where('employee.riwayat_pelatihan.0.bidang', 'Teknologi Informasi')
            ->where('employee.riwayat_pelatihan.0.keahlian', 'Web Development Laravel')
            ->where('employee.riwayat_pelatihan.0.instansi_penyelenggara', 'Balai Diklat Keagamaan')
            ->where('employee.riwayat_pelatihan.0.tahun', '2024')
            ->where('employee.riwayat_pelatihan.0.pembiayaan', 'Beasiswa Yayasan')
        );
    }
}
