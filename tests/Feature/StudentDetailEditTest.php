<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\SchoolLevel;
use App\Models\SchoolProgram;
use App\Models\SchoolUnit;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentDetailEditTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::first() ?? User::factory()->create();
    }

    public function test_student_detail_page_loads_with_identity_data(): void
    {
        $student = Student::firstOrFail();

        $response = $this->actingAs($this->user)->get("/students/{$student->nisn}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('StudentDetail')
            ->has('student')
            ->has('permissions')
            ->where('student.nisn', $student->nisn)
            ->where('student.nama', $student->nama)
        );
    }

    public function test_can_update_student_identity_data(): void
    {
        $student = Student::firstOrFail();

        $updateData = [
            'nama' => 'Ahmad Fulan Al-Baqir',
            'nisn' => $student->nisn,
            'nipd' => '12345678',
            'nik' => '3313010101010001',
            'no_kk' => '3313010101010002',
            'jk' => 'L',
            'tempat_lahir' => 'Karanganyar',
            'tanggal_lahir' => '2015-05-12',
            'agama' => 'Islam',
            'no_wa' => '081234567890',
            'no_wa_2' => '081234567891',
            'email' => 'ahmad.fulan@sekolah.sch.id',
            'unit' => $student->unit,
            'program' => 'Fullday',
            'jenjang' => 'SD',
            'angkatan' => '2024',
            'tingkat' => '3',
            'kelas' => '3A',
        ];

        $response = $this->actingAs($this->user)->put("/students/{$student->id}", $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $student->refresh();
        $this->assertSame('Ahmad Fulan Al-Baqir', $student->nama);
        $this->assertSame('12345678', $student->nipd);
        $this->assertSame('3313010101010001', $student->nik);
        $this->assertSame('3313010101010002', $student->no_kk);
        $this->assertSame('L', $student->jk);
        $this->assertSame('Karanganyar', $student->tempat_lahir);
        $this->assertSame('2015-05-12', $student->tanggal_lahir->format('Y-m-d'));
        $this->assertSame('081234567890', $student->no_wa);
        $this->assertSame('ahmad.fulan@sekolah.sch.id', $student->email);
        $this->assertSame('2024', $student->angkatan);
    }

    public function test_update_student_validates_required_fields(): void
    {
        $student = Student::firstOrFail();

        $response = $this->actingAs($this->user)->put("/students/{$student->id}", [
            'nama' => '',
            'nisn' => '',
            'jk' => '',
            'tempat_lahir' => '',
            'tanggal_lahir' => '',
        ]);

        $response->assertSessionHasErrors(['nama', 'nisn', 'jk', 'tempat_lahir', 'tanggal_lahir']);
    }

    public function test_can_update_student_registration_data(): void
    {
        $student = Student::firstOrFail();

        $response = $this->actingAs($this->user)->put("/students/{$student->id}", [
            '_tab' => 'registrasi',
            'status_registrasi' => 'Mutasi Masuk',
            'sekolah_asal' => 'TK Islam Al-Falah',
            'diterima_di_jenjang' => 'SD / Kelas 2',
            'tanggal_diterima' => '2024-07-15',
            'info_psb' => 'Brosur & Rekomendasi Teman',
            'status_keaktifan' => 'Tidak Aktif',
            'keterangan_tidak_aktif' => 'Mutasi / Pindah Sekolah',
            'tanggal_tidak_aktif' => '2025-01-10',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $student->refresh();
        $this->assertSame('Mutasi Masuk', $student->status_registrasi);
        $this->assertSame('TK Islam Al-Falah', $student->sekolah_asal);
        $this->assertSame('SD / Kelas 2', $student->diterima_di_jenjang);
        $this->assertSame('2024-07-15', $student->tanggal_diterima->format('Y-m-d'));
        $this->assertSame('Tidak Aktif', $student->status_keaktifan);
        $this->assertSame('Mutasi / Pindah Sekolah', $student->keterangan_tidak_aktif);
        $this->assertSame('2025-01-10', $student->tanggal_tidak_aktif->format('Y-m-d'));
    }

    public function test_can_access_student_create_page(): void
    {
        $response = $this->actingAs($this->user)->get('/students/create');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Students/Create')
            ->has('allUnits')
            ->has('allLevels')
            ->has('allPrograms')
            ->has('allClasses')
        );
    }

    public function test_can_store_new_student_manually(): void
    {
        $uniqueNisn = '9988776655';

        $data = [
            'nama' => 'Zaidan Al-Farisi',
            'nisn' => $uniqueNisn,
            'nipd' => '24250999',
            'nik' => '3313010101990001',
            'no_kk' => '3313010101990002',
            'jk' => 'L',
            'tempat_lahir' => 'Solo',
            'tanggal_lahir' => '2016-08-20',
            'agama' => 'Islam',
            'unit' => 'SDIT',
            'program' => 'Tahfidz',
            'jenjang' => 'SD',
            'tingkat' => '1',
            'jalan' => 'Jl. Lawu Timur No. 12',
            'rt' => '02',
            'rw' => '04',
            'dusun' => 'Bejen',
            'desa' => 'Bejen',
            'kecamatan' => 'Karanganyar',
            'kabupaten' => 'Karanganyar',
            'provinsi' => 'Jawa Tengah',
            'ayah_nama' => 'Budi Santoso',
            'ibu_nama' => 'Siti Aminah',
            'status_registrasi' => 'Siswa Baru',
            'sekolah_asal' => 'TK Aisyiyah',
            'tanggal_diterima' => '2024-07-01',
            'status_keaktifan' => 'Aktif',
        ];

        $response = $this->actingAs($this->user)->post('/students', $data);

        $response->assertRedirect(route('students.show', ['nisn' => $uniqueNisn]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('students', [
            'nisn' => $uniqueNisn,
            'nama' => 'Zaidan Al-Farisi',
            'unit' => 'SDIT',
            'ayah_nama' => 'Budi Santoso',
        ]);

        $newStudent = Student::where('nisn', $uniqueNisn)->first();
        $this->assertNotNull($newStudent);
        $this->assertDatabaseHas('student_academic_records', [
            'student_id' => $newStudent->id,
            'program' => 'Tahfidz',
            'tingkat' => '1',
        ]);
    }

    public function test_can_create_student_with_welfare_and_inactive_status(): void
    {
        $uniqueNisn = '3133661962';

        $data = [
            'nama' => 'Muhammad Ilham Al Haidar',
            'nisn' => $uniqueNisn,
            'nik' => '3313132803130001',
            'jk' => 'L',
            'tempat_lahir' => 'Karanganyar',
            'tanggal_lahir' => '2013-03-28',
            'agama' => 'Islam',
            'unit' => 'SMP IT Tahfidzul Quran Ulil Albab Karanganyar',
            'program' => 'Umum',
            'jenjang' => 'SMP',
            'tingkat' => '7',
            'no_wa' => '085229257445',
            'jalan' => 'Sidorejo',
            'rt' => '01',
            'rw' => '10',
            'dusun' => 'Sidorejo',
            'desa' => 'Selokaton',
            'kecamatan' => 'Gondangrejo',
            'kabupaten' => 'Karanganyar',
            'provinsi' => 'Jawa Tengah',
            'status_keluarga' => 'Anak Kandung',
            'anak_ke' => 1,
            'status_registrasi' => 'Siswa Baru',
            'sekolah_asal' => 'MIM Mundu',
            'tanggal_diterima' => '2025-07-14',
            'status_keaktifan' => 'Tidak Aktif',
            'keterangan_tidak_aktif' => 'Pindah ke pondok lain',
            'tanggal_tidak_aktif' => '2025-08-01',
            'status_kip' => false,
            'no_kip' => null,
            'nama_di_kip' => null,
            'status_pip' => false,
            'pip_keterangan' => null,
            'desil' => 3,
            'kebutuhan_khusus' => null,
        ];

        $response = $this->actingAs($this->user)->post('/students', $data);

        $response->assertRedirect(route('students.show', ['nisn' => $uniqueNisn]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('students', [
            'nisn' => $uniqueNisn,
            'nama' => 'Muhammad Ilham Al Haidar',
            'status_keaktifan' => 'Tidak Aktif',
            'desil' => 3,
        ]);

        $newStudent = Student::where('nisn', $uniqueNisn)->first();
        $this->assertNotNull($newStudent);
        $this->assertDatabaseHas('student_academic_records', [
            'student_id' => $newStudent->id,
            'program' => 'Umum',
            'tingkat' => '7',
            'student_status' => 'dropout',
            'ket_tidak_aktif' => 'Pindah ke pondok lain',
            'desil' => 3,
        ]);
    }

    public function test_can_update_student_formal_unit_and_pesantren_unit(): void
    {
        $student = Student::firstOrFail();

        $updateData = [
            'nama' => $student->nama,
            'nisn' => $student->nisn,
            'jk' => $student->jk ?: 'L',
            'tempat_lahir' => $student->tempat_lahir ?: 'Karanganyar',
            'tanggal_lahir' => $student->tanggal_lahir ? $student->tanggal_lahir->format('Y-m-d') : '2012-01-01',
            'unit' => 'SMP IT Tahfidzul Quran Ulil Albab Karanganyar',
            'unit_pesantren' => 'PPTQ Ulil Albab',
            'program' => 'Boarding',
            'jenjang' => 'SMP',
            'angkatan' => '2024',
            'tingkat' => '7',
        ];

        $response = $this->actingAs($this->user)->put("/students/{$student->id}", $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $student->refresh();
        $this->assertSame('SMP IT Tahfidzul Quran Ulil Albab Karanganyar', $student->unit);
        $this->assertSame('PPTQ Ulil Albab', $student->unit_pesantren);

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'unit' => 'SMP IT Tahfidzul Quran Ulil Albab Karanganyar',
            'unit_pesantren' => 'PPTQ Ulil Albab',
        ]);

        $this->assertDatabaseHas('student_academic_records', [
            'student_id' => $student->id,
            'program' => 'Boarding',
            'unit_pesantren' => 'PPTQ Ulil Albab',
        ]);
    }

    public function test_can_create_student_with_formal_unit_and_pesantren_unit(): void
    {
        $uniqueNisn = '9977553311';

        $data = [
            'nama' => 'Santri Boarding SMP',
            'nisn' => $uniqueNisn,
            'jk' => 'L',
            'tempat_lahir' => 'Karanganyar',
            'tanggal_lahir' => '2012-05-15',
            'agama' => 'Islam',
            'unit' => 'SMP IT Tahfidzul Quran Ulil Albab Karanganyar',
            'unit_pesantren' => 'PPTQ Ulil Albab',
            'program' => 'Boarding',
            'jenjang' => 'SMP',
            'tingkat' => '7',
            'status_registrasi' => 'Siswa Baru',
            'status_keaktifan' => 'Aktif',
        ];

        $response = $this->actingAs($this->user)->post('/students', $data);

        $response->assertRedirect(route('students.show', ['nisn' => $uniqueNisn]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('students', [
            'nisn' => $uniqueNisn,
            'nama' => 'Santri Boarding SMP',
            'unit' => 'SMP IT Tahfidzul Quran Ulil Albab Karanganyar',
            'unit_pesantren' => 'PPTQ Ulil Albab',
        ]);

        $newStudent = Student::where('nisn', $uniqueNisn)->first();
        $this->assertNotNull($newStudent);
        $this->assertDatabaseHas('student_academic_records', [
            'student_id' => $newStudent->id,
            'unit_pesantren' => 'PPTQ Ulil Albab',
            'program' => 'Boarding',
        ]);
    }

    public function test_can_update_student_address_data(): void
    {
        $student = Student::firstOrFail();

        $response = $this->actingAs($this->user)->put("/students/{$student->id}", [
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
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

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
    }

    public function test_alumni_student_detail_picks_latest_chronological_academic_record(): void
    {
        $laterCreatedOlderYear = AcademicYear::create([
            'name' => '2022/2023',
            'semester' => 'Genap',
            'start_date' => '2023-01-01',
            'end_date' => '2023-06-30',
        ]);

        $earlierCreatedNewerYear = AcademicYear::where('name', '2024/2025')
            ->where('semester', 'Genap')
            ->first() ?? AcademicYear::create([
                'name' => '2024/2025',
                'semester' => 'Genap',
                'start_date' => '2025-01-01',
                'end_date' => '2025-06-30',
            ]);

        $this->assertGreaterThan($earlierCreatedNewerYear->id, $laterCreatedOlderYear->id);

        $alumni = Student::create([
            'nisn' => '0099999906',
            'nama' => 'Alumni Angkatan Enam',
            'jk' => 'L',
            'unit' => 'SMP IT Tahfidzul Quran Ulil Albab Karanganyar',
            'unit_pesantren' => 'PPTQ Ulil Albab',
            'jenjang' => 'SMP',
            'angkatan' => '6',
            'program' => 'Boarding SMP',
            'status_keaktifan' => 'Lulus',
            'tanggal_diterima' => '2022-07-11',
            'tanggal_lulus' => '2025-07-01',
        ]);

        StudentAcademicRecord::create([
            'student_id' => $alumni->id,
            'academic_year_id' => $earlierCreatedNewerYear->id,
            'unit' => $alumni->unit,
            'jenjang' => 'SMP',
            'tingkat' => 9,
            'program' => 'Boarding SMP',
            'unit_pesantren' => 'PPTQ Ulil Albab',
            'student_status' => 'Aktif',
        ]);

        StudentAcademicRecord::create([
            'student_id' => $alumni->id,
            'academic_year_id' => $laterCreatedOlderYear->id,
            'unit' => $alumni->unit,
            'jenjang' => 'SMP',
            'tingkat' => 7,
            'program' => 'Boarding SMP',
            'unit_pesantren' => 'PPTQ Ulil Albab',
            'student_status' => 'Aktif',
        ]);

        $response = $this->actingAs($this->user)->get("/students/{$alumni->nisn}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('StudentDetail')
            ->where('student.tingkat', 9)
            ->where('student.riwayat_keaktifan.0.academic_year', '2024/2025')
            ->where('student.riwayat_keaktifan.0.tingkat', 9)
            ->where('student.riwayat_keaktifan.1.academic_year', '2022/2023')
            ->where('student.riwayat_keaktifan.1.tingkat', 7)
        );
    }

    public function test_create_page_passes_unit_configurations_and_store_persists_explicit_angkatan(): void
    {
        $level = SchoolLevel::firstOrCreate(['name' => 'SMP'], ['order' => 2]);
        $unit = SchoolUnit::firstOrCreate(['name' => 'SMP IT Tahfidzul Quran Ulil Albab Karanganyar']);
        $unit->levels()->syncWithoutDetaching([$level->id]);

        $program = SchoolProgram::firstOrCreate(['name' => 'Fullday SMP']);
        $unit->programs()->syncWithoutDetaching([$program->id => ['school_level_id' => $level->id]]);

        $createResponse = $this->actingAs($this->user)->get('/students/create');
        $createResponse->assertStatus(200);
        $createResponse->assertInertia(fn (Assert $page) => $page
            ->component('Students/Create')
            ->has('unitConfigurations')
            ->where('unitConfigurations', function ($configs) use ($unit) {
                $matched = collect($configs)->firstWhere('name', $unit->name);

                return $matched !== null
                    && in_array('SMP', $matched['levels'], true)
                    && collect($matched['programs'])->contains(fn ($p) => $p['name'] === 'Fullday SMP');
            })
        );

        $uniqueNisn = '0088776610';
        $storeResponse = $this->actingAs($this->user)->post('/students', [
            'nama' => 'Siswa Dengan Angkatan Eksplisit',
            'nisn' => $uniqueNisn,
            'jk' => 'L',
            'tempat_lahir' => 'Karanganyar',
            'tanggal_lahir' => '2013-02-10',
            'unit' => $unit->name,
            'program' => 'Fullday SMP',
            'jenjang' => 'SMP',
            'angkatan' => '10',
            'tingkat' => '7',
        ]);

        $storeResponse->assertRedirect(route('students.show', ['nisn' => $uniqueNisn]));
        $this->assertDatabaseHas('students', [
            'nisn' => $uniqueNisn,
            'angkatan' => '10',
            'jenjang' => 'SMP',
        ]);
    }
}
