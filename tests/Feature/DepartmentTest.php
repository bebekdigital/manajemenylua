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

    public function test_portal_page_renders_organization_positions_data(): void
    {
        $dept = Department::create(['name' => 'Badan Pembina', 'order' => 1]);
        $parent = DepartmentPosition::create([
            'name' => 'Ketua Dewan Pembina',
            'department_id' => $dept->id,
            'level' => 1,
        ]);
        DepartmentPosition::create([
            'name' => 'Sekretaris Yayasan',
            'department_id' => $dept->id,
            'level' => 2,
            'parent_id' => $parent->id,
        ]);

        $response = $this->actingAs($this->user)->get('/portal');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Portal')
            ->has('organizationPositions', 2)
            ->where('organizationPositions.0.name', 'Ketua Dewan Pembina')
            ->where('organizationPositions.0.level', 1)
            ->where('organizationPositions.1.name', 'Sekretaris Yayasan')
            ->where('organizationPositions.1.level', 2)
            ->where('organizationPositions.1.parent_name', 'Ketua Dewan Pembina')
        );
    }

    public function test_can_create_position_directly_with_level_and_parent(): void
    {
        $dept = Department::create(['name' => 'Bidang Pendidikan', 'order' => 1]);
        $kabid = DepartmentPosition::create([
            'name' => 'Kepala Bidang Pendidikan',
            'department_id' => $dept->id,
            'level' => 3,
        ]);

        $response = $this->actingAs($this->user)->post('/portal/department-positions', [
            'name' => 'Koordinator Kurikulum',
            'department_id' => $dept->id,
            'level' => 4,
            'parent_id' => $kabid->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('department_positions', [
            'name' => 'Koordinator Kurikulum',
            'department_id' => $dept->id,
            'level' => 4,
            'parent_id' => $kabid->id,
        ]);
    }

    public function test_cannot_create_cyclic_hierarchy_on_update(): void
    {
        $pos1 = DepartmentPosition::create(['name' => 'Posisi A', 'level' => 1]);
        $pos2 = DepartmentPosition::create(['name' => 'Posisi B', 'level' => 2, 'parent_id' => $pos1->id]);
        $pos3 = DepartmentPosition::create(['name' => 'Posisi C', 'level' => 3, 'parent_id' => $pos2->id]);

        // Attempt to set pos3 as parent of pos1 (circular)
        $response = $this->actingAs($this->user)->put("/portal/department-positions/{$pos1->id}", [
            'name' => 'Posisi A',
            'parent_id' => $pos3->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('department_positions', [
            'id' => $pos1->id,
            'parent_id' => null,
        ]);
    }

    public function test_deleting_parent_position_reparents_children(): void
    {
        $grandparent = DepartmentPosition::create(['name' => 'Ketua Umum', 'level' => 2]);
        $parent = DepartmentPosition::create(['name' => 'Kepala Bidang', 'level' => 3, 'parent_id' => $grandparent->id]);
        $child = DepartmentPosition::create(['name' => 'Staf Pelaksana', 'level' => 5, 'parent_id' => $parent->id]);

        $response = $this->actingAs($this->user)->delete("/portal/department-positions/{$parent->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('department_positions', ['id' => $parent->id]);

        // Child should now be reparented to grandparent
        $this->assertDatabaseHas('department_positions', [
            'id' => $child->id,
            'parent_id' => $grandparent->id,
        ]);
    }

    public function test_can_update_organization_level_name_and_subtitle(): void
    {
        $response = $this->actingAs($this->user)->put('/portal/organization-levels/1', [
            'name' => 'Majelis Pembina Yayasan',
            'subtitle' => 'Pemegang mandat tertinggi dan pengawas yayasan',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('organization_levels', [
            'level' => 1,
            'name' => 'Majelis Pembina Yayasan',
            'subtitle' => 'Pemegang mandat tertinggi dan pengawas yayasan',
        ]);
    }
}
