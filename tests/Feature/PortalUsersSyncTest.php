<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DepartmentPosition;
use App\Models\Employee;
use App\Models\EmployeePosition;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PortalUsersSyncTest extends TestCase
{
    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->createMinimalSchema();

        $this->superadmin = User::factory()->create([
            'name' => 'Administrator Yayasan',
            'username' => 'superadmin',
            'role' => 'superadmin',
            'password' => Hash::make('CustomAdminPass99!'),
            'must_change_password' => false,
        ]);
    }

    public function test_portal_users_role_uses_active_position_and_syncs_when_organization_updates(): void
    {
        $dept = Department::create([
            'name' => 'SMP',
            'unit_group' => 'Satuan Pendidikan',
            'order' => 1,
        ]);

        $orgPosition = DepartmentPosition::create([
            'department_id' => $dept->id,
            'name' => 'Waka Kurikulum',
            'level' => 3,
            'order' => 1,
        ]);

        $employee = Employee::create([
            'nama' => 'Ahmad Fauzi',
            'nipy' => '2001',
            'tanggal_lahir' => '1990-05-12',
        ]);

        // Active position (no TST)
        EmployeePosition::create([
            'employee_id' => $employee->id,
            'unit_kerja' => 'SMP',
            'jabatan' => 'Waka Kurikulum',
            'tmt_jabatan' => '2024-07-01',
            'tst_jabatan' => null,
        ]);

        // Newer dated position that is already finished (has TST) — must NOT override active position
        EmployeePosition::create([
            'employee_id' => $employee->id,
            'unit_kerja' => 'SMP',
            'jabatan' => 'Plt. Kepala Sekolah',
            'tmt_jabatan' => '2025-01-01',
            'tst_jabatan' => '2025-06-30',
        ]);

        $staffUser = User::factory()->create([
            'name' => 'Ahmad Fauzi',
            'username' => 'ahmadfauzi12',
            'nipy' => '2001',
            'role' => 'Guru', // stale stored role
            'unit' => 'SMP',
            'jabatan' => 'Waka Kurikulum',
            'password' => Hash::make('19900512'),
            'must_change_password' => false,
        ]);

        $this->actingAs($this->superadmin)
            ->get('/portal')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal')
                ->where('users', function ($users) use ($staffUser): bool {
                    $row = collect($users)->firstWhere('id', $staffUser->id);

                    return $row !== null && $row['role'] === 'Waka Kurikulum';
                })
            );

        // Rename position in Tab Organisasi -> role column & users.role must both update
        $this->actingAs($this->superadmin)
            ->put("/portal/department-positions/{$orgPosition->id}", [
                'name' => 'Wakil Kepala Kurikulum',
                'department_id' => $dept->id,
                'level' => 3,
            ])
            ->assertRedirect();

        $staffUser->refresh();
        $this->assertSame('Wakil Kepala Kurikulum', $staffUser->role);
        $this->assertSame('Wakil Kepala Kurikulum', $staffUser->jabatan);

        $this->actingAs($this->superadmin)
            ->get('/portal')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal')
                ->where('users', function ($users) use ($staffUser): bool {
                    $row = collect($users)->firstWhere('id', $staffUser->id);

                    return $row !== null && $row['role'] === 'Wakil Kepala Kurikulum';
                })
            );
    }

    public function test_portal_users_tracks_default_password_change_status_and_valid_iso_created_at(): void
    {
        $empLegacyDefault = Employee::create([
            'nama' => 'Siti Aisyah',
            'nipy' => '3001',
            'tanggal_lahir' => '1988-03-14',
        ]);
        $empChanged = Employee::create([
            'nama' => 'Budi Santoso',
            'nipy' => '3002',
            'tanggal_lahir' => '1991-09-20',
        ]);
        $empForced = Employee::create([
            'nama' => 'Citra Dewi',
            'nipy' => '3003',
            'tanggal_lahir' => '1993-11-02',
        ]);

        // Pre-existing generated account (must_change_password = false) still using default password YYYYMMDD
        $userLegacyDefault = User::factory()->create([
            'name' => $empLegacyDefault->nama,
            'username' => 'sitiaisyah11',
            'nipy' => $empLegacyDefault->nipy,
            'role' => 'Guru',
            'password' => Hash::make('19880314'),
            'must_change_password' => false,
        ]);

        // Pre-existing account whose password has been changed away from default
        $userChanged = User::factory()->create([
            'name' => $empChanged->nama,
            'username' => 'budisantoso22',
            'nipy' => $empChanged->nipy,
            'role' => 'Guru',
            'password' => Hash::make('MyNewSecret#2026'),
            'must_change_password' => false,
        ]);

        // Newly generated account with must_change_password = true
        $userForced = User::factory()->create([
            'name' => $empForced->nama,
            'username' => 'citradewi33',
            'nipy' => $empForced->nipy,
            'role' => 'Guru',
            'password' => Hash::make('19931102'),
            'must_change_password' => true,
        ]);

        $this->actingAs($this->superadmin)
            ->get('/portal')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal')
                ->where('users', function ($users) use ($userLegacyDefault, $userChanged, $userForced): bool {
                    $col = collect($users);
                    $legacyRow = $col->firstWhere('id', $userLegacyDefault->id);
                    $changedRow = $col->firstWhere('id', $userChanged->id);
                    $forcedRow = $col->firstWhere('id', $userForced->id);

                    return $legacyRow['has_changed_password'] === false
                        && $changedRow['has_changed_password'] === true
                        && $forcedRow['has_changed_password'] === false
                        && str_contains((string) $legacyRow['created_at'], 'T');
                })
            );
    }

    public function test_portal_users_displays_synced_employee_name_without_title_even_when_user_name_is_stale(): void
    {
        $employee = Employee::create([
            'nama' => 'Giri Sayekto',
            'gelar_belakang' => 'S.Pd.',
            'nipy' => '19830604 201610 1 019',
            'tanggal_lahir' => '1983-06-04',
        ]);

        $staleUser = User::factory()->create([
            'name' => 'Giri Sayekto, S. Pd',
            'username' => 'girisayekto04',
            'nipy' => $employee->nipy,
            'role' => 'Guru',
            'password' => Hash::make('19830604'),
            'must_change_password' => false,
        ]);

        $this->actingAs($this->superadmin)
            ->get('/portal')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal')
                ->where('users', function ($users) use ($staleUser): bool {
                    $row = collect($users)->firstWhere('id', $staleUser->id);

                    return $row !== null && $row['name'] === 'Giri Sayekto';
                })
            );
    }

    public function test_updating_employee_name_and_removing_title_syncs_linked_user_name_and_portal_list(): void
    {
        $empWithNipy = Employee::create([
            'nama' => 'Giri Sayekto, S. Pd',
            'gelar_belakang' => 'S.Pd.',
            'nipy' => '19830604',
            'nik' => '3313010406830001',
            'email' => 'giri@example.com',
        ]);

        $userWithNipy = User::factory()->create([
            'name' => 'Giri Sayekto, S. Pd',
            'username' => 'girisayekto01',
            'nipy' => '19830604',
            'email' => 'giri@example.com',
            'role' => 'Guru',
        ]);

        // User linked only by prior name (no NIPY, no email) whose title in nama is removed
        $empNameOnly = Employee::create([
            'nama' => 'Siti Aminah, S.Pd.',
            'gelar_belakang' => 'S.Pd.',
        ]);

        $userNameOnly = User::factory()->create([
            'name' => 'Siti Aminah, S.Pd.',
            'username' => 'sitiaminah02',
            'nipy' => null,
            'email' => null,
            'role' => 'Guru',
        ]);

        // Edit employee via StaffController endpoint: remove title from nama and clear gelar_belakang
        $this->actingAs($this->superadmin)
            ->put("/staff/{$empWithNipy->id}", [
                'nama' => 'Giri Sayekto',
                'gelar_depan' => null,
                'gelar_belakang' => null,
                'nipy' => '19830604',
                'email' => 'giri@example.com',
            ])
            ->assertRedirect();

        $this->actingAs($this->superadmin)
            ->put("/staff/{$empNameOnly->id}", [
                'nama' => 'Siti Aminah',
                'gelar_depan' => null,
                'gelar_belakang' => null,
            ])
            ->assertRedirect();

        $this->assertSame('Giri Sayekto', $userWithNipy->fresh()->name);
        $this->assertSame('Siti Aminah', $userNameOnly->fresh()->name);

        $this->actingAs($this->superadmin)
            ->get('/portal')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal')
                ->where('users', function ($users) use ($userWithNipy, $userNameOnly): bool {
                    $col = collect($users);
                    $giriRow = $col->firstWhere('id', $userWithNipy->id);
                    $sitiRow = $col->firstWhere('id', $userNameOnly->id);

                    return $giriRow !== null
                        && $giriRow['name'] === 'Giri Sayekto'
                        && $sitiRow !== null
                        && $sitiRow['name'] === 'Siti Aminah';
                })
            );
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

        Schema::create('academic_years', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('semester')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('school_levels', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('school_units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_level_id')->nullable();
            $table->string('name');
            $table->string('code')->nullable();
            $table->timestamps();
        });

        Schema::create('classrooms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->nullable();
            $table->foreignId('school_unit_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('student_academic_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->nullable();
            $table->unsignedBigInteger('student_id')->nullable();
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
            $table->string('nik')->nullable();
            $table->string('nama');
            $table->string('gelar_depan')->nullable();
            $table->string('gelar_belakang')->nullable();
            $table->string('jk')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('email')->nullable();
            $table->string('no_wa')->nullable();
            $table->string('foto')->nullable();
            $table->string('status_keaktifan')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('jenjang_kepegawaian')->nullable();
            $table->date('tmt_kenaikan_jenjang')->nullable();
            $table->date('tst_kenaikan_jenjang')->nullable();
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
