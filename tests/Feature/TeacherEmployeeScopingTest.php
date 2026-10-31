<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\RolePermission;
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

    public function test_teacher_cannot_update_own_data_when_can_update_permission_is_disabled(): void
    {
        $teacherEmp = Employee::create([
            'nama' => 'Siti Guru',
            'nipy' => '112233',
            'nik' => '3301010101010003',
        ]);

        $teacherUser = User::factory()->create([
            'name' => 'Siti Guru',
            'role' => 'Guru',
            'nipy' => '112233',
        ]);

        // Nonaktifkan checklist Ubah (can_update) untuk Role Guru
        RolePermission::updateOrCreate(
            ['role' => 'Guru', 'feature_group' => 'employee_detail_history'],
            ['feature_name' => 'Detail Profil & Riwayat Penugasan', 'can_view' => true, 'can_update' => false]
        );

        $response = $this->actingAs($teacherUser)->put("/staff/{$teacherEmp->id}", [
            'nama' => 'Siti Guru Edit',
        ]);

        $response->assertStatus(403);

        // Dan di halaman show(), permissions.can_update harus false
        $showRes = $this->actingAs($teacherUser)->get("/staff/{$teacherEmp->id}");
        $showRes->assertOk();
        $showRes->assertInertia(fn ($page) => $page->component('StaffDetail')->where('permissions.can_update', false));
    }

    public function test_teacher_can_update_own_data_when_can_update_permission_is_enabled(): void
    {
        $teacherEmp = Employee::create([
            'nama' => 'Siti Guru',
            'nipy' => '112233',
            'nik' => '3301010101010003',
        ]);

        $teacherUser = User::factory()->create([
            'name' => 'Siti Guru',
            'role' => 'Guru',
            'nipy' => '112233',
        ]);

        // Aktifkan checklist Ubah (can_update) untuk Role Guru
        RolePermission::updateOrCreate(
            ['role' => 'Guru', 'feature_group' => 'employee_detail_history'],
            ['feature_name' => 'Detail Profil & Riwayat Penugasan', 'can_view' => true, 'can_update' => true]
        );

        $response = $this->actingAs($teacherUser)->put("/staff/{$teacherEmp->id}", [
            'nama' => 'Siti Guru Berhasil',
        ]);

        $response->assertRedirect();
        $this->assertEquals('Siti Guru Berhasil', $teacherEmp->fresh()->nama);

        // Dan di halaman show(), permissions.can_update harus true
        $showRes = $this->actingAs($teacherUser)->get("/staff/{$teacherEmp->id}");
        $showRes->assertOk();
        $showRes->assertInertia(fn ($page) => $page->component('StaffDetail')->where('permissions.can_update', true));
    }

    public function test_role_with_view_unchecked_cannot_access_employee_and_has_can_view_employee_false(): void
    {
        $user = User::factory()->create([
            'name' => 'Staf Tata Usaha',
            'role' => 'Tata Usaha',
        ]);

        // Uncheck view permission for 'Tata Usaha'
        RolePermission::updateOrCreate(
            ['role' => 'Tata Usaha', 'feature_group' => 'employee_detail_history'],
            [
                'feature_name' => 'Detail Data Pegawai',
                'can_view' => false,
            ]
        );

        $response = $this->actingAs($user)->get('/beranda');
        $response->assertOk();
        $response->assertInertia(function ($page) {
            $page->where('auth.permissions.can_view_employee', false)
                ->where('auth.user.can_view_employee', false);
        });

        // Visiting /staff should be rejected with 403 Forbidden
        $staffResponse = $this->actingAs($user)->get('/staff');
        $staffResponse->assertStatus(403);
    }

    public function test_role_with_view_checked_has_can_view_employee_true(): void
    {
        $user = User::factory()->create([
            'name' => 'Kepala TU',
            'role' => 'Kepala Tata Usaha',
        ]);

        RolePermission::updateOrCreate(
            ['role' => 'Kepala Tata Usaha', 'feature_group' => 'employee_detail_history'],
            [
                'feature_name' => 'Data Pegawai',
                'can_view' => true,
            ]
        );

        $response = $this->actingAs($user)->get('/beranda');
        $response->assertOk();
        $response->assertInertia(function ($page) {
            $page->where('auth.permissions.can_view_employee', true)
                ->where('auth.user.can_view_employee', true);
        });
    }

    public function test_child_kepegawaian_permission_controls_can_view_kepegawaian_and_update(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin Data',
            'role' => 'Staf Administrasi',
        ]);

        $employee = Employee::create([
            'nama' => 'Pegawai Lama',
            'nipy' => 'ORIGINAL_NIPY',
            'nik' => '3301010101019999',
            'status_keaktifan' => 'Aktif',
        ]);

        // Parent Data Pegawai: view true, update true
        RolePermission::updateOrCreate(
            ['role' => 'Staf Administrasi', 'feature_group' => 'employee_detail_history'],
            [
                'feature_name' => 'Data Pegawai',
                'can_view' => true,
                'can_update' => true,
            ]
        );

        // Child Data Kepegawaian: view false, update false
        RolePermission::updateOrCreate(
            ['role' => 'Staf Administrasi', 'feature_group' => 'employee_kepegawaian'],
            [
                'feature_name' => 'Data Kepegawaian',
                'can_view' => false,
                'can_update' => false,
            ]
        );

        // View detail page
        $response = $this->actingAs($user)->get('/staff/'.$employee->id);
        $response->assertOk();
        $response->assertInertia(function ($page) {
            $page->where('permissions.can_update', true)
                ->where('permissions.can_view_kepegawaian', false)
                ->where('permissions.can_update_kepegawaian', false);
        });

        // Try updating NIPY
        $updateResponse = $this->actingAs($user)->put('/staff/'.$employee->id, [
            'nama' => 'Nama Baru',
            'nipy' => 'CHANGED_NIPY',
            'status_keaktifan' => 'Nonaktif',
        ]);
        $updateResponse->assertRedirect('/staff/'.$employee->id);

        $employee->refresh();
        $this->assertEquals('Nama Baru', $employee->nama);
        // Kepegawaian fields must be untouched
        $this->assertEquals('ORIGINAL_NIPY', $employee->nipy);
        $this->assertEquals('Aktif', $employee->status_keaktifan);
    }

    public function test_teacher_by_default_has_can_update_kepegawaian_false_and_superadmin_has_true(): void
    {
        $teacher = User::factory()->create([
            'name' => 'Guru IPA',
            'role' => 'Guru',
            'nipy' => 'GURU_NIPY_1',
        ]);
        $teacherEmp = Employee::create([
            'nama' => 'Guru IPA',
            'nipy' => 'GURU_NIPY_1',
            'nik' => '3301010101018888',
            'status_keaktifan' => 'Aktif',
        ]);

        $superadmin = User::factory()->create([
            'name' => 'Super Admin',
            'role' => 'superadmin',
        ]);

        // Guru views their own profile
        $responseGuru = $this->actingAs($teacher)->get('/staff/'.$teacherEmp->id);
        $responseGuru->assertOk();
        $responseGuru->assertInertia(function ($page) {
            $page->where('permissions.can_update', true)
                ->where('permissions.can_view_kepegawaian', true)
                ->where('permissions.can_update_kepegawaian', false);
        });

        // Superadmin views employee
        $responseSuper = $this->actingAs($superadmin)->get('/staff/'.$teacherEmp->id);
        $responseSuper->assertOk();
        $responseSuper->assertInertia(function ($page) {
            $page->where('permissions.can_update', true)
                ->where('permissions.can_view_kepegawaian', true)
                ->where('permissions.can_update_kepegawaian', true);
        });

        // Superadmin updates NIPY
        $updateSuper = $this->actingAs($superadmin)->put('/staff/'.$teacherEmp->id, [
            'nama' => 'Guru IPA Updated',
            'nipy' => 'GURU_NIPY_UPDATED',
            'status_keaktifan' => 'Aktif',
        ]);
        $updateSuper->assertRedirect('/staff/'.$teacherEmp->id);

        $teacherEmp->refresh();
        $this->assertEquals('GURU_NIPY_UPDATED', $teacherEmp->nipy);
    }
}
