<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PsbSummaryTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat skema minimum pada SQLite :memory: untuk menghindari migrasi lama bertipe MySQL MODIFY
        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('username')->unique()->nullable();
            $table->string('role')->default('superadmin');
            $table->boolean('is_active')->default(true);
            $table->string('email')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
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
            $table->string('semester')->default('Ganjil');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('classrooms', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('unit')->nullable();
            $table->string('jenjang')->nullable();
            $table->integer('grade')->nullable();
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->string('nisn')->nullable();
            $table->string('nama');
            $table->string('jk')->nullable();
            $table->string('unit')->nullable();
            $table->string('unit_pesantren')->nullable();
            $table->string('jenjang')->nullable();
            $table->string('program')->nullable();
            $table->string('angkatan')->nullable();
            $table->string('status_keaktifan')->nullable();
            $table->string('status_registrasi')->nullable();
            $table->date('tanggal_diterima')->nullable();
            $table->string('sekolah_asal')->nullable();
            $table->string('npsn')->nullable();
            $table->string('desa')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kabupaten')->nullable();
            $table->string('info_psb')->nullable();
            $table->string('foto')->nullable();
            $table->timestamps();
        });

        Schema::create('student_academic_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id');
            $table->foreignId('academic_year_id');
            $table->foreignId('classroom_id')->nullable();
            $table->string('unit')->nullable();
            $table->string('jenjang')->nullable();
            $table->string('program')->nullable();
            $table->string('tingkat')->nullable();
            $table->string('student_status')->default('Naik Kelas');
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('nipy')->nullable();
            $table->string('nama')->nullable();
            $table->string('email')->nullable();
            $table->string('jk')->nullable();
            $table->string('status_keaktifan')->nullable();
            $table->string('unit_keaktifan_dapodik')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('role');
            $table->string('feature_group');
            $table->string('feature_name')->nullable();
            $table->boolean('can_view')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_update')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->boolean('can_export')->default(false);
            $table->boolean('can_import')->default(false);
            $table->timestamps();
        });

        Schema::create('employee_permits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        $this->user = User::create([
            'name' => 'Admin PSB',
            'username' => 'adminpsb',
            'email' => 'adminpsb@example.com',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        AcademicYear::create([
            'name' => '2025/2026',
            'semester' => 'Ganjil',
            'start_date' => '2025-07-01',
            'end_date' => '2025-12-31',
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_from_psb_summary(): void
    {
        $this->get('/psb/ringkasan')->assertRedirect('/');
    }

    public function test_psb_root_redirects_to_ringkasan(): void
    {
        $this->actingAs($this->user)
            ->get('/psb')
            ->assertRedirect(route('psb.summary'));
    }

    public function test_psb_summary_page_loads_students_with_npsn_and_ta_masuk(): void
    {
        Student::create([
            'nisn' => '0123456789',
            'nama' => 'Ahmad Uji PSB',
            'jk' => 'L',
            'unit' => 'SMPIT ULIL ALBAB',
            'jenjang' => 'SMP',
            'angkatan' => '7',
            'sekolah_asal' => 'SD N 01 LALUNG',
            'npsn' => '20312609',
            'tanggal_diterima' => '2025-07-14',
            'status_registrasi' => 'Siswa Baru',
            'status_keaktifan' => 'Aktif',
        ]);

        $response = $this->actingAs($this->user)->get('/psb/ringkasan');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Psb/Summary')
            ->has('students', 1)
            ->has('academicYears', 1)
            ->where('currentAcademicYearName', '2025/2026')
            ->where('students.0.npsn', '20312609')
            ->where('students.0.sekolah_asal', 'SD N 01 LALUNG')
            ->where('students.0.ta_masuk', '2025/2026')
        );
    }

    public function test_static_solo_raya_school_dataset_is_valid_and_complete(): void
    {
        $path = public_path('data/solo-raya-sd-sederajat.json');
        $this->assertFileExists($path);

        $data = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data['schools'] ?? []);

        $schools = collect($data['schools']);
        $bentuks = $schools->pluck('bentuk')->unique()->all();
        $this->assertContains('SD', $bentuks);
        $this->assertContains('MI', $bentuks);
        $this->assertContains('PKBM', $bentuks);

        $kabupatens = $schools->pluck('kabupaten')->unique()->all();
        foreach (['Surakarta', 'Karanganyar', 'Sukoharjo', 'Sragen', 'Wonogiri', 'Klaten', 'Boyolali'] as $kab) {
            $this->assertContains($kab, $kabupatens);
        }

        $missingCoords = $schools->filter(fn ($s) => empty($s['lat']) || empty($s['lng']))->count();
        $this->assertSame(0, $missingCoords);
    }
}
