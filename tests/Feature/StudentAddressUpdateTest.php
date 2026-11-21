<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentAddressUpdateTest extends TestCase
{
    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('superadmin');
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(false);
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

        Schema::create('school_units', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('school_levels', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('school_programs', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->string('nisn')->unique();
            $table->string('nama');
            $table->string('jk')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('status_keaktifan')->default('Aktif');
            $table->string('jalan')->nullable();
            $table->string('rt')->nullable();
            $table->string('rw')->nullable();
            $table->string('dusun')->nullable();
            $table->string('desa')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kabupaten')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('jenis_tinggal')->nullable();
            $table->string('alat_transportasi')->nullable();
            $table->string('jarak_rumah')->nullable();
            $table->timestamps();
        });

        Schema::create('student_academic_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable();
            $table->foreignId('classroom_id')->nullable();
            $table->string('unit')->nullable();
            $table->string('jenjang')->nullable();
            $table->string('tingkat')->nullable();
            $table->string('student_status')->nullable();
            $table->timestamps();
        });

        Schema::create('student_siblings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('nama')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('nipy')->nullable();
            $table->string('nama');
            $table->string('gelar_depan')->nullable();
            $table->string('gelar_belakang')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_positions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('unit_kerja')->nullable();
            $table->string('jabatan')->nullable();
            $table->date('tmt_jabatan')->nullable();
            $table->date('tst_jabatan')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_permits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        $this->superadmin = User::factory()->create([
            'role' => 'superadmin',
        ]);
    }

    public function test_can_view_and_update_student_address_via_tab_handler(): void
    {
        $student = Student::create([
            'nisn' => '0123456789',
            'nama' => 'Syahida Amalina',
            'jk' => 'P',
            'desa' => 'Kismoyoso',
            'kecamatan' => 'Ngemplak',
            'kabupaten' => 'Kabupaten Boyolali',
            'provinsi' => 'Jawa Tengah',
        ]);

        $this->actingAs($this->superadmin)
            ->put("/students/{$student->id}", [
                '_tab' => 'alamat',
                'jalan' => 'Jl. Slamet Riyadi No. 45',
                'rt' => '003',
                'rw' => '007',
                'dusun' => 'Nglarangan',
                'desa' => 'Pandeyan',
                'kecamatan' => 'Ngemplak',
                'kabupaten' => 'Kabupaten Boyolali',
                'provinsi' => 'Jawa Tengah',
                'jenis_tinggal' => 'Bersama Orang Tua',
                'alat_transportasi' => 'Jemputan Sekolah',
                'jarak_rumah' => '4.2 km',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $student->refresh();
        $this->assertSame('Jl. Slamet Riyadi No. 45', $student->jalan);
        $this->assertSame('003', $student->rt);
        $this->assertSame('007', $student->rw);
        $this->assertSame('Nglarangan', $student->dusun);
        $this->assertSame('Pandeyan', $student->desa);
        $this->assertSame('Ngemplak', $student->kecamatan);
        $this->assertSame('Kabupaten Boyolali', $student->kabupaten);
        $this->assertSame('Jawa Tengah', $student->provinsi);
        $this->assertSame('Bersama Orang Tua', $student->jenis_tinggal);
        $this->assertSame('Jemputan Sekolah', $student->alat_transportasi);
        $this->assertSame('4.2 km', $student->jarak_rumah);

        $this->actingAs($this->superadmin)
            ->get("/students/{$student->nisn}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('StudentDetail')
                ->where('student.desa', 'Pandeyan')
                ->where('student.kecamatan', 'Ngemplak')
                ->where('student.kabupaten', 'Kabupaten Boyolali')
                ->where('student.provinsi', 'Jawa Tengah')
            );
    }
}
