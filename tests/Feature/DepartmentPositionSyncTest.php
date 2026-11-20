<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DepartmentPosition;
use App\Models\Employee;
use App\Models\EmployeePosition;
use App\Models\SchoolUnit;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DepartmentPositionSyncTest extends TestCase
{
    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->createMinimalSchema();

        $this->superadmin = User::factory()->create([
            'role' => 'superadmin',
        ]);
    }

    public function test_adding_position_in_organization_immediately_invalidates_staff_detail_dropdown_cache(): void
    {
        $dept = Department::create([
            'name' => 'SMP',
            'unit_group' => 'Satuan Pendidikan',
            'order' => 1,
        ]);

        SchoolUnit::create([
            'name' => 'SMP',
            'code' => 'SMP',
        ]);

        $dept->positions()->create([
            'name' => 'Kepala Sekolah',
            'level' => 2,
            'order' => 1,
        ]);

        $employee = Employee::create([
            'nama' => 'Budi Santoso',
            'nipy' => '1001',
        ]);

        // Warm the caches first via /staff/{id}
        $this->actingAs($this->superadmin)
            ->get("/staff/{$employee->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('StaffDetail')
                ->where('departments.0.positions', ['Kepala Sekolah'])
                ->where('schoolUnits.0.positions', ['Kepala Sekolah'])
            );

        // Add a new position directly via Tab Organisasi endpoint
        $this->actingAs($this->superadmin)
            ->post('/portal/department-positions', [
                'name' => 'Waka Kurikulum',
                'department_id' => $dept->id,
                'level' => 3,
                'order' => 2,
            ])
            ->assertRedirect();

        // Immediately check /staff/{id} without waiting 180s
        $this->actingAs($this->superadmin)
            ->get("/staff/{$employee->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('StaffDetail')
                ->where('departments.0.positions', ['Kepala Sekolah', 'Waka Kurikulum'])
                ->where('schoolUnits.0.positions', ['Kepala Sekolah', 'Waka Kurikulum'])
            );
    }

    public function test_updating_position_name_in_organization_cascades_to_employee_positions_and_users(): void
    {
        $smp = Department::create(['name' => 'SMP', 'unit_group' => 'Satuan Pendidikan', 'order' => 1]);
        $sma = Department::create(['name' => 'SMA', 'unit_group' => 'Satuan Pendidikan', 'order' => 2]);

        $smpPos = DepartmentPosition::create([
            'department_id' => $smp->id,
            'name' => 'Waka Kurikulum',
            'level' => 3,
            'order' => 1,
        ]);

        DepartmentPosition::create([
            'department_id' => $sma->id,
            'name' => 'Waka Kurikulum',
            'level' => 3,
            'order' => 1,
        ]);

        $empSmp = Employee::create(['nama' => 'Ahmad Fauzi', 'nipy' => '1002']);
        $empSma = Employee::create(['nama' => 'Rina Kartika', 'nipy' => '1003']);

        $empPosSmp = EmployeePosition::create([
            'employee_id' => $empSmp->id,
            'unit_kerja' => 'SMP',
            'jabatan' => 'Waka Kurikulum',
            'tmt_jabatan' => '2025-07-01',
        ]);

        $empPosSma = EmployeePosition::create([
            'employee_id' => $empSma->id,
            'unit_kerja' => 'SMA',
            'jabatan' => 'Waka Kurikulum',
            'tmt_jabatan' => '2025-07-01',
        ]);

        $userSmp = User::factory()->create([
            'role' => 'guru',
            'unit' => 'SMP',
            'jabatan' => 'Waka Kurikulum',
        ]);

        // Warm switcher cache and departments cache
        $this->actingAs($this->superadmin)->get("/staff/{$empSmp->id}")->assertOk();

        // Rename position on SMP only
        $this->actingAs($this->superadmin)
            ->put("/portal/department-positions/{$smpPos->id}", [
                'name' => 'Wakil Kepala Kurikulum',
                'department_id' => $smp->id,
                'level' => 3,
                'order' => 1,
            ])
            ->assertRedirect();

        $empPosSmp->refresh();
        $empPosSma->refresh();
        $userSmp->refresh();

        $this->assertSame('Wakil Kepala Kurikulum', $empPosSmp->jabatan);
        $this->assertSame('SMP', $empPosSmp->unit_kerja);
        $this->assertSame('Waka Kurikulum', $empPosSma->jabatan);
        $this->assertSame('Wakil Kepala Kurikulum', $userSmp->jabatan);

        $this->actingAs($this->superadmin)
            ->get("/staff/{$empSmp->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('employee.jabatan', 'Wakil Kepala Kurikulum')
                ->where('employee.riwayat_jabatan.0.jabatan', 'Wakil Kepala Kurikulum')
                ->where('departments.0.positions', ['Wakil Kepala Kurikulum'])
            );
    }

    public function test_moving_position_unit_and_renaming_department_cascades_to_employee_positions(): void
    {
        $deptOld = Department::create(['name' => 'Bidang Akademik', 'unit_group' => 'Yayasan', 'order' => 1]);
        $deptTarget = Department::create(['name' => 'Bidang Kurikulum', 'unit_group' => 'Yayasan', 'order' => 2]);

        $orgPos = DepartmentPosition::create([
            'department_id' => $deptOld->id,
            'name' => 'Staf Kurikulum',
            'level' => 4,
            'order' => 1,
        ]);

        $employee = Employee::create(['nama' => 'Siti Aminah', 'nipy' => '1004']);
        $empPos = EmployeePosition::create([
            'employee_id' => $employee->id,
            'unit_kerja' => 'Bidang Akademik',
            'jabatan' => 'Staf Kurikulum',
            'tmt_jabatan' => '2025-07-01',
        ]);

        // Move position to Bidang Kurikulum + rename it
        $this->actingAs($this->superadmin)
            ->put("/portal/department-positions/{$orgPos->id}", [
                'name' => 'Koordinator Kurikulum',
                'department_id' => $deptTarget->id,
                'level' => 4,
            ])
            ->assertRedirect();

        $empPos->refresh();
        $this->assertSame('Koordinator Kurikulum', $empPos->jabatan);
        $this->assertSame('Bidang Kurikulum', $empPos->unit_kerja);

        // Rename department itself
        $this->actingAs($this->superadmin)
            ->put("/portal/departments/{$deptTarget->id}", [
                'name' => 'Divisi Kurikulum',
                'unit_group' => 'Yayasan',
                'order' => 2,
            ])
            ->assertRedirect();

        $empPos->refresh();
        $this->assertSame('Divisi Kurikulum', $empPos->unit_kerja);
    }

    private function createMinimalSchema(): void
    {
        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('guru');
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(false);
            $table->string('nipy')->nullable();
            $table->string('status_kepegawaian')->nullable();
            $table->string('jk')->nullable();
            $table->string('ttl')->nullable();
            $table->string('unit')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('no_wa')->nullable();
            $table->string('foto')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('role');
            $table->string('module');
            $table->boolean('can_view')->default(true);
            $table->boolean('can_create')->default(true);
            $table->boolean('can_update')->default(true);
            $table->boolean('can_delete')->default(true);
            $table->boolean('can_export')->default(true);
            $table->boolean('can_import')->default(true);
            $table->timestamps();
        });

        Schema::create('academic_years', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('semester')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('school_units', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('unit_group')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('department_positions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('parent_id')->nullable();
            $table->string('name');
            $table->integer('level')->default(3);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('nipy')->nullable();
            $table->string('nama');
            $table->string('gelar_depan')->nullable();
            $table->string('gelar_belakang')->nullable();
            $table->string('jk')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('nik')->nullable();
            $table->date('tmt_pengangkatan')->nullable();
            $table->date('tst_pengangkatan')->nullable();
            $table->string('keaktifan_dapodik')->nullable();
            $table->string('unit_keaktifan_dapodik')->nullable();
            $table->string('status_keaktifan')->nullable();
            $table->string('keterangan_tidak_aktif')->nullable();
            $table->string('no_wa')->nullable();
            $table->string('email')->nullable();
            $table->string('jalan_domisili')->nullable();
            $table->string('rt_rw_domisili')->nullable();
            $table->string('dusun_domisili')->nullable();
            $table->string('desa_domisili')->nullable();
            $table->string('kecamatan_domisili')->nullable();
            $table->string('kab_domisili')->nullable();
            $table->string('provinsi_domisili')->nullable();
            $table->string('jalan_tinggal')->nullable();
            $table->string('rt_rw_tinggal')->nullable();
            $table->string('dusun_tinggal')->nullable();
            $table->string('desa_tinggal')->nullable();
            $table->string('kecamatan_tinggal')->nullable();
            $table->string('kabupaten_tinggal')->nullable();
            $table->string('provinsi_tinggal')->nullable();
            $table->string('status_rumah')->nullable();
            $table->string('kepemilikan_bpjs')->nullable();
            $table->string('penanggung_bpjs')->nullable();
            $table->string('skill')->nullable();
            $table->string('status_pernikahan')->nullable();
            $table->string('nama_suami_istri')->nullable();
            $table->string('ttl_suami_istri')->nullable();
            $table->string('pekerjaan_suami_istri')->nullable();
            $table->date('tanggal_menikah')->nullable();
            $table->integer('jumlah_anak')->nullable();
            $table->string('nama_ibu')->nullable();
            $table->string('nama_ayah')->nullable();
            $table->string('alamat_orangtua')->nullable();
            $table->string('kontak_darurat')->nullable();
            $table->string('hubungan_kontak_darurat')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_positions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('unit_kerja')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('jenis_kepegawaian')->nullable();
            $table->string('no_sk')->nullable();
            $table->date('tmt_jabatan')->nullable();
            $table->date('tst_jabatan')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('status_kepegawaian')->nullable();
            $table->date('tmt_kenaikan_jenjang')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_education_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('jenjang_pendidikan')->nullable();
            $table->string('jurusan')->nullable();
            $table->string('instansi_pendidikan')->nullable();
            $table->string('tahun_lulus')->nullable();
            $table->string('pembiayaan')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_training_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('bidang')->nullable();
            $table->string('keahlian')->nullable();
            $table->string('instansi_penyelenggara')->nullable();
            $table->string('tahun')->nullable();
            $table->string('pembiayaan')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('category')->nullable();
            $table->string('record_type')->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_permits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->date('tanggal')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('jenis')->nullable();
            $table->string('detail')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('lampiran_path')->nullable();
            $table->string('lampiran_nama')->nullable();
            $table->string('status')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }
}
