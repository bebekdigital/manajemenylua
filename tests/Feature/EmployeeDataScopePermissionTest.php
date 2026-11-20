<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeePosition;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployeeDataScopePermissionTest extends TestCase
{
    private User $superadmin;

    private Employee $smpHeadEmp;

    private Employee $smpPeerEmp;

    private Employee $smaPeerEmp;

    private User $smpHeadUser;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->createMinimalSchema();

        $this->superadmin = User::factory()->create([
            'name' => 'Superadmin',
            'role' => 'superadmin',
        ]);

        $this->smpHeadEmp = Employee::create(['nama' => 'Kepala SMP', 'nipy' => '4001']);
        EmployeePosition::create([
            'employee_id' => $this->smpHeadEmp->id,
            'unit_kerja' => 'SMP',
            'jabatan' => 'Kepala Sekolah',
            'tmt_jabatan' => '2024-07-01',
            'tst_jabatan' => null,
        ]);

        $this->smpPeerEmp = Employee::create(['nama' => 'Guru SMP Satu', 'nipy' => '4002']);
        EmployeePosition::create([
            'employee_id' => $this->smpPeerEmp->id,
            'unit_kerja' => 'SMP',
            'jabatan' => 'Guru',
            'tmt_jabatan' => '2024-07-01',
            'tst_jabatan' => null,
        ]);

        $this->smaPeerEmp = Employee::create(['nama' => 'Guru SMA Satu', 'nipy' => '4003']);
        EmployeePosition::create([
            'employee_id' => $this->smaPeerEmp->id,
            'unit_kerja' => 'SMA',
            'jabatan' => 'Guru',
            'tmt_jabatan' => '2024-07-01',
            'tst_jabatan' => null,
        ]);

        $this->smpHeadUser = User::factory()->create([
            'name' => 'Kepala SMP',
            'username' => 'kepalasmp40',
            'nipy' => '4001',
            'role' => 'Kepala Sekolah',
            'unit' => 'SMP',
            'jabatan' => 'Kepala Sekolah',
        ]);
    }

    public function test_role_permission_controller_saves_and_returns_employee_data_scope(): void
    {
        $this->actingAs($this->superadmin)
            ->post('/portal/permissions', [
                'role' => 'Kepala Sekolah',
                'permissions' => [
                    [
                        'group' => 'employee_detail_history',
                        'name' => 'Data Pegawai',
                        'data_scope' => 'unit',
                        'can_view' => true,
                        'can_create' => true,
                        'can_update' => true,
                        'can_delete' => false,
                        'can_export' => true,
                        'can_import' => false,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('role_permissions', [
            'role' => 'Kepala Sekolah',
            'feature_group' => 'employee_detail_history',
            'data_scope' => 'unit',
        ]);

        $this->actingAs($this->superadmin)
            ->getJson('/portal/permissions/matrix?role=Kepala%20Sekolah')
            ->assertOk()
            ->assertJsonFragment([
                'group' => 'employee_detail_history',
                'data_scope' => 'unit',
            ]);
    }

    public function test_cumulative_scopes_enforce_self_unit_and_all_visibility(): void
    {
        // 1. Scope = self (only own data)
        RolePermission::updateOrCreate(
            ['role' => 'Kepala Sekolah', 'feature_group' => 'employee_detail_history'],
            [
                'feature_name' => 'Data Pegawai',
                'data_scope' => RolePermission::SCOPE_SELF,
                'can_view' => true,
                'can_update' => true,
            ]
        );

        $this->actingAs($this->smpHeadUser)
            ->get('/staff')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Staff')
                ->where('isTeacher', true)
                ->where('employees', fn ($rows): bool => collect($rows)->pluck('id')->all() === [$this->smpHeadEmp->id])
            );

        $this->actingAs($this->smpHeadUser)->get("/staff/{$this->smpHeadEmp->id}")->assertOk();
        $this->actingAs($this->smpHeadUser)->get("/staff/{$this->smpPeerEmp->id}")->assertForbidden();
        $this->actingAs($this->smpHeadUser)->get("/staff/{$this->smaPeerEmp->id}")->assertForbidden();

        // 2. Scope = unit (own data + same unit SMP, blocks SMA)
        RolePermission::where('role', 'Kepala Sekolah')
            ->where('feature_group', 'employee_detail_history')
            ->update(['data_scope' => RolePermission::SCOPE_UNIT]);

        $this->actingAs($this->smpHeadUser)
            ->get('/staff')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Staff')
                ->where('isTeacher', false)
                ->where('employees', function ($rows): bool {
                    $ids = collect($rows)->pluck('id')->sort()->values()->all();

                    return $ids === [$this->smpHeadEmp->id, $this->smpPeerEmp->id];
                })
            );

        $this->actingAs($this->smpHeadUser)
            ->get("/staff/{$this->smpPeerEmp->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('allEmployees', function ($rows): bool {
                    $ids = collect($rows)->pluck('id')->sort()->values()->all();

                    return $ids === [$this->smpHeadEmp->id, $this->smpPeerEmp->id];
                })
            );

        $this->actingAs($this->smpHeadUser)->get("/staff/{$this->smaPeerEmp->id}")->assertForbidden();

        // 3. Scope = all (own data + same unit + other units)
        RolePermission::where('role', 'Kepala Sekolah')
            ->where('feature_group', 'employee_detail_history')
            ->update(['data_scope' => RolePermission::SCOPE_ALL]);

        $this->actingAs($this->smpHeadUser)
            ->get('/staff')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Staff')
                ->where('employees', function ($rows): bool {
                    $ids = collect($rows)->pluck('id')->sort()->values()->all();

                    return $ids === [$this->smpHeadEmp->id, $this->smpPeerEmp->id, $this->smaPeerEmp->id];
                })
            );

        $this->actingAs($this->smpHeadUser)->get("/staff/{$this->smaPeerEmp->id}")->assertOk();
    }

    private function createMinimalSchema(): void
    {
        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('guru');
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(false);
            $table->string('nipy')->nullable();
            $table->string('unit')->nullable();
            $table->string('jabatan')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('role');
            $table->string('feature_group');
            $table->string('feature_name');
            $table->string('data_scope', 20)->default('all');
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

        Schema::create('school_units', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
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
