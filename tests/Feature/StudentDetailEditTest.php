<?php

namespace Tests\Feature;

use App\Models\Student;
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
}
