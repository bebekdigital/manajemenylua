<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DepartmentPosition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'superadmin',
        ]);
    }

    public function test_portal_page_renders_departments_data(): void
    {
        $dept = Department::create(['name' => 'Bidang Pendidikan', 'order' => 1]);
        $dept->positions()->create(['name' => 'Kepala Bidang']);

        $response = $this->actingAs($this->user)->get('/portal');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Portal')
            ->has('departments')
            ->where('departments.0.name', 'Bidang Pendidikan')
            ->where('departments.0.positions.0.name', 'Kepala Bidang')
        );
    }

    public function test_can_create_department(): void
    {
        $response = $this->actingAs($this->user)->post('/portal/departments', [
            'name' => 'Bidang Keuangan',
            'order' => 2,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('departments', [
            'name' => 'Bidang Keuangan',
            'order' => 2,
        ]);
    }

    public function test_can_update_department(): void
    {
        $dept = Department::create(['name' => 'Bidang Umum', 'order' => 1]);

        $response = $this->actingAs($this->user)->put("/portal/departments/{$dept->id}", [
            'name' => 'Bidang Umum & Sarpras',
            'order' => 3,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('departments', [
            'id' => $dept->id,
            'name' => 'Bidang Umum & Sarpras',
            'order' => 3,
        ]);
    }

    public function test_cannot_delete_department_with_positions(): void
    {
        $dept = Department::create(['name' => 'Bidang SDM', 'order' => 1]);
        $dept->positions()->create(['name' => 'Staff SDM']);

        $response = $this->actingAs($this->user)->delete("/portal/departments/{$dept->id}");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('departments', ['id' => $dept->id]);
    }

    public function test_can_create_and_update_and_delete_position(): void
    {
        $dept = Department::create(['name' => 'Bidang Humas', 'order' => 1]);

        // Create
        $response = $this->actingAs($this->user)->post("/portal/departments/{$dept->id}/positions", [
            'name' => 'Koordinator Humas',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('department_positions', [
            'department_id' => $dept->id,
            'name' => 'Koordinator Humas',
        ]);

        $pos = DepartmentPosition::where('department_id', $dept->id)->first();

        // Update
        $updateResp = $this->actingAs($this->user)->put("/portal/department-positions/{$pos->id}", [
            'name' => 'Kepala Humas & Kemitraan',
        ]);
        $updateResp->assertRedirect();
        $this->assertDatabaseHas('department_positions', [
            'id' => $pos->id,
            'name' => 'Kepala Humas & Kemitraan',
        ]);

        // Delete position
        $deleteResp = $this->actingAs($this->user)->delete("/portal/department-positions/{$pos->id}");
        $deleteResp->assertRedirect();
        $this->assertDatabaseMissing('department_positions', ['id' => $pos->id]);

        // Delete empty department
        $deleteDeptResp = $this->actingAs($this->user)->delete("/portal/departments/{$dept->id}");
        $deleteDeptResp->assertRedirect();
        $this->assertDatabaseMissing('departments', ['id' => $dept->id]);
    }
}
