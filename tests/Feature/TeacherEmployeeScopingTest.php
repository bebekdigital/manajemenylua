<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeacherEmployeeScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_only_see_their_own_employee_record_in_index(): void
    {
        $teacherEmp = Employee::create([
            'nama' => 'Ahmad Guru',
            'nipy' => '123456',
            'nik' => '3301010101010001',
        ]);

        $otherEmp = Employee::create([
            'nama' => 'Budi Staff',
            'nipy' => '654321',
            'nik' => '3301010101010002',
        ]);

        $teacherUser = User::factory()->create([
            'name' => 'Ahmad Guru',
            'role' => 'Guru',
            'nipy' => '123456',
        ]);

        $response = $this->actingAs($teacherUser)->get('/staff');

        $response->assertOk();
        $response->assertInertia(function ($page) use ($teacherEmp) {
            $page->component('Staff')
                ->where('isTeacher', true)
                ->where('teacherEmployee.id', $teacherEmp->id)
                ->has('employees', 1)
                ->where('employees.0.id', $teacherEmp->id);
        });
    }

    public function test_superadmin_can_see_all_employees_in_index(): void
    {
        Employee::create([
            'nama' => 'Ahmad Guru',
            'nik' => '3301010101010001',
        ]);

        Employee::create([
            'nama' => 'Budi Staff',
            'nik' => '3301010101010002',
        ]);

        $adminUser = User::factory()->create([
            'role' => 'superadmin',
        ]);

        $response = $this->actingAs($adminUser)->get('/staff');

        $response->assertOk();
        $response->assertInertia(function ($page) {
            $page->component('Staff')
                ->where('isTeacher', false)
                ->has('employees', 2);
        });
    }

    public function test_teacher_can_view_own_detail_page(): void
    {
        $teacherEmp = Employee::create([
            'nama' => 'Siti Guru',
            'nipy' => '112233',
            'nik' => '3301010101010003',
        ]);

        $teacherUser = User::factory()->create([
            'name' => 'Siti Guru',
            'role' => 'Guru Kelas',
            'nipy' => '112233',
        ]);

        $response = $this->actingAs($teacherUser)->get("/staff/{$teacherEmp->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('StaffDetail')->where('employee.id', $teacherEmp->id));
    }

    public function test_teacher_forbidden_to_view_other_employee_detail(): void
    {
        $teacherEmp = Employee::create([
            'nama' => 'Siti Guru',
            'nipy' => '112233',
            'nik' => '3301010101010003',
        ]);

        $otherEmp = Employee::create([
            'nama' => 'Budi Staff',
            'nipy' => '654321',
            'nik' => '3301010101010002',
        ]);

        $teacherUser = User::factory()->create([
            'name' => 'Siti Guru',
            'role' => 'Guru Kelas',
            'nipy' => '112233',
        ]);

        $response = $this->actingAs($teacherUser)->get("/staff/{$otherEmp->id}");

        $response->assertStatus(403);
    }

    public function test_teacher_forbidden_to_update_other_employee(): void
    {
        $teacherEmp = Employee::create([
            'nama' => 'Siti Guru',
            'nipy' => '112233',
            'nik' => '3301010101010003',
        ]);

        $otherEmp = Employee::create([
            'nama' => 'Budi Staff',
            'nipy' => '654321',
            'nik' => '3301010101010002',
        ]);

        $teacherUser = User::factory()->create([
            'name' => 'Siti Guru',
            'role' => 'Guru Mata Pelajaran',
            'nipy' => '112233',
        ]);

        $response = $this->actingAs($teacherUser)->put("/staff/{$otherEmp->id}", [
            'nama' => 'Hacked Name',
        ]);

        $response->assertStatus(403);
    }

    public function test_teacher_forbidden_to_import_or_download_employee_template(): void
    {
        $teacherUser = User::factory()->create([
            'role' => 'Guru',
        ]);

        $this->actingAs($teacherUser)->get('/staff/template')->assertStatus(403);

        $fakeFile = UploadedFile::fake()->create('employees.xlsx', 100);
        $this->actingAs($teacherUser)->post('/staff/import', ['file' => $fakeFile])->assertStatus(403);
    }

    public function test_teacher_forbidden_to_upload_or_modify_other_employee_documents(): void
    {
        Storage::fake('local');

        $teacherEmp = Employee::create([
            'nama' => 'Siti Guru',
            'nipy' => '112233',
            'nik' => '3301010101010003',
        ]);

        $otherEmp = Employee::create([
            'nama' => 'Budi Staff',
            'nipy' => '654321',
            'nik' => '3301010101010002',
        ]);

        $teacherUser = User::factory()->create([
            'name' => 'Siti Guru',
            'role' => 'Guru',
            'nipy' => '112233',
        ]);

        $file = UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf');

        $response = $this->actingAs($teacherUser)->post("/staff/{$otherEmp->id}/documents", [
            'category' => 'ktp',
            'title' => 'KTP Budi',
            'file' => $file,
        ]);

        $response->assertStatus(403);
    }
}
