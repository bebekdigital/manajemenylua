<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\EmployeePosition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_portal_page(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('portal.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Portal')
            ->has('academicYears')
            ->has('users')
            ->has('schoolLevels')
            ->has('departments')
            ->has('availableRoles')
            ->has('schoolRoles')
            ->has('deptRoles')
            ->has('employees')
        );
    }

    public function test_superadmin_can_create_user_with_jabatan_role(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
        ]);

        $response = $this->actingAs($superadmin)->post(route('portal.users.store'), [
            'name' => 'Ustadz Ahmad',
            'username' => 'ahmad123',
            'email' => 'ahmad@ulilalbabkra.sch.id',
            'password' => 'password123',
            'role' => 'Guru Kelas',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'username' => 'ahmad123',
            'role' => 'Guru Kelas',
        ]);
    }

    public function test_superadmin_can_batch_generate_user_accounts_for_employees(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
        ]);

        $employee = Employee::create([
            'nama' => 'Muhammad Fatih Alfarizi',
            'tanggal_lahir' => '1995-08-17',
            'nipy' => '19950817001',
            'jk' => 'L',
        ]);

        EmployeePosition::create([
            'employee_id' => $employee->id,
            'unit_kerja' => 'SMP Islam Terpadu',
            'jabatan' => 'Guru PJOK',
            'tmt_jabatan' => '2020-01-01',
        ]);

        $response = $this->actingAs($superadmin)->post(route('portal.users.generate'), [
            'employee_ids' => [$employee->id],
        ]);

        $response->assertSessionHas('success');

        $createdUser = User::where('name', 'Muhammad Fatih Alfarizi')->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals('Guru PJOK', $createdUser->role);
        $this->assertMatchesRegularExpression('/^muhammadalfarizi\d{2}$/', $createdUser->username);
        $this->assertTrue(Hash::check('19950817', $createdUser->password));
    }

    public function test_non_superadmin_cannot_generate_user_accounts(): void
    {
        $regularUser = User::factory()->create([
            'role' => 'Guru',
        ]);

        $response = $this->actingAs($regularUser)->post(route('portal.users.generate'), [
            'employee_ids' => [1],
        ]);

        $response->assertStatus(403);
    }

    public function test_superadmin_can_update_user_role(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
        ]);

        $user = User::factory()->create([
            'name' => 'Budi Santoso',
            'username' => 'budi123',
            'role' => 'Guru',
        ]);

        $response = $this->actingAs($superadmin)->put(route('portal.users.update', $user), [
            'name' => 'Budi Santoso',
            'username' => 'budi123',
            'role' => 'Kepala Sekolah',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'Kepala Sekolah',
        ]);
    }

    public function test_superadmin_can_get_permissions_matrix(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
        ]);

        $response = $this->actingAs($superadmin)->get(route('portal.permissions.matrix', ['role' => 'Kepala Bidang SDM']));

        $response->assertStatus(200);
        $response->assertJsonPath('role', 'Kepala Bidang SDM');
        $response->assertJsonStructure(['role', 'matrix']);
    }

    public function test_superadmin_can_update_role_permissions(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
        ]);

        $response = $this->actingAs($superadmin)->post(route('portal.permissions.update'), [
            'role' => 'Guru',
            'permissions' => [
                [
                    'group' => 'database_students',
                    'name' => 'Data Siswa',
                    'can_view' => true,
                    'can_create' => true,
                    'can_update' => false,
                    'can_delete' => false,
                    'can_export' => true,
                    'can_import' => false,
                ],
            ],
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('role_permissions', [
            'role' => 'Guru',
            'feature_group' => 'database_students',
            'can_create' => 1,
            'can_update' => 0,
        ]);
    }

    public function test_non_superadmin_cannot_update_role_permissions(): void
    {
        $guru = User::factory()->create([
            'role' => 'Guru',
        ]);

        $response = $this->actingAs($guru)->post(route('portal.permissions.update'), [
            'role' => 'Guru',
            'permissions' => [],
        ]);

        $response->assertStatus(403);
    }

    public function test_portal_index_returns_pass_default_and_is_active_for_users(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
        ]);

        $employee = Employee::create([
            'nama' => 'Siti Nurhaliza',
            'tanggal_lahir' => '1992-04-21',
            'nipy' => '19920421001',
        ]);

        $user = User::factory()->create([
            'name' => 'Siti Nurhaliza',
            'username' => 'sitinur92',
            'nipy' => '19920421001',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superadmin)->get(route('portal.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Portal')
            ->has('users', 2) // superadmin + user
            ->where('users.1.pass_default', '19920421')
            ->where('users.1.is_active', true)
        );
    }

    public function test_superadmin_can_toggle_user_active_status(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
        ]);

        $targetUser = User::factory()->create([
            'name' => 'Target User',
            'username' => 'target123',
            'is_active' => true,
        ]);

        // 1. Nonaktifkan user
        $response = $this->actingAs($superadmin)->patch(route('portal.users.toggle-status', $targetUser));
        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertFalse($targetUser->fresh()->is_active);

        // 2. Aktifkan kembali user
        $response2 = $this->actingAs($superadmin)->patch(route('portal.users.toggle-status', $targetUser));
        $response2->assertRedirect();
        $response2->assertSessionHas('success');
        $this->assertTrue($targetUser->fresh()->is_active);

        // 3. Superadmin tidak bisa menonaktifkan akun sendiri
        $responseSelf = $this->actingAs($superadmin)->patch(route('portal.users.toggle-status', $superadmin));
        $responseSelf->assertRedirect();
        $responseSelf->assertSessionHas('error');
        $this->assertTrue($superadmin->fresh()->is_active);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $inactiveUser = User::factory()->create([
            'username' => 'nonaktif123',
            'password' => Hash::make('rahasia123'),
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'user_id' => 'nonaktif123',
            'password' => 'rahasia123',
        ]);

        $response->assertSessionHasErrors(['user_id']);
        $this->assertGuest();

        // Aktifkan user, kemudian coba login lagi
        $inactiveUser->update(['is_active' => true]);

        $responseActive = $this->post('/login', [
            'user_id' => 'nonaktif123',
            'password' => 'rahasia123',
        ]);

        $responseActive->assertRedirect('/beranda');
        $this->assertAuthenticatedAs($inactiveUser);
    }
}
