<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentInlineEditTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::first() ?? User::factory()->create();
    }

    public function test_students_index_loads_successfully_without_student_families_table(): void
    {
        $response = $this->actingAs($this->user)->get('/students');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Students')
            ->has('students')
        );
    }

    public function test_inline_edit_loads_successfully_without_family_relation(): void
    {
        $response = $this->actingAs($this->user)->get('/students/inline-edit');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Students/InlineEdit')
            ->has('students')
        );
    }

    public function test_inline_update_saves_family_and_student_fields_directly_on_student(): void
    {
        $student = Student::firstOrFail();

        $response = $this->actingAs($this->user)->post('/students/inline-update', [
            'students' => [
                [
                    'nisn' => $student->nisn,
                    'nama' => 'Nama Diubah',
                    'ayah_nama' => 'Ayah Diubah',
                    'ibu_nama' => 'Ibu Diubah',
                    'wali_nama' => 'Wali Diubah',
                    'status_pip' => true,
                    'status_kip' => true,
                ],
            ],
        ]);

        $response->assertRedirect();
        $student->refresh();
        $this->assertSame('Nama Diubah', $student->nama);
        $this->assertSame('Ayah Diubah', $student->ayah_nama);
        $this->assertSame('Ibu Diubah', $student->ibu_nama);
        $this->assertSame('Wali Diubah', $student->wali_nama);
    }

    public function test_administration_identity_document_loads_without_family_relation(): void
    {
        $response = $this->actingAs($this->user)->get('/administration/identity-document');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Administration/IdentityDocument')
            ->has('students')
        );
    }
}
