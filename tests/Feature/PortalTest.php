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
}
