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
}
