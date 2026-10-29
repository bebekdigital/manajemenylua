<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DepartmentPosition;
use App\Models\SchoolLevel;
use App\Models\SchoolUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolPositionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private SchoolUnit $unitSD;

    private SchoolUnit $unitSMP;

    private Department $deptSD;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'superadmin']);
        $levelSD = SchoolLevel::create(['name' => 'SD', 'order' => 1]);
        $levelSMP = SchoolLevel::create(['name' => 'SMP', 'order' => 2]);
        $this->unitSD = SchoolUnit::create(['school_level_id' => $levelSD->id, 'name' => 'SDIT Ulil Albab']);
        $this->unitSMP = SchoolUnit::create(['school_level_id' => $levelSMP->id, 'name' => 'SMPIT Ulil Albab']);

        $this->deptSD = Department::create(['name' => 'SDIT Ulil Albab', 'order' => 1]);
    }

    public function test_school_unit_positions_are_retrieved_from_department_positions(): void
    {
        DepartmentPosition::create([
            'department_id' => $this->deptSD->id,
            'name' => 'Kepala Sekolah',
            'level' => 3,
            'order' => 1,
        ]);
        DepartmentPosition::create([
            'department_id' => $this->deptSD->id,
            'name' => 'Guru Kelas',
            'level' => 4,
            'order' => 2,
        ]);

        $this->assertCount(2, $this->unitSD->positions);
        $this->assertEquals(['Kepala Sekolah', 'Guru Kelas'], $this->unitSD->positions->pluck('name')->toArray());
    }

    public function test_department_positions_can_be_managed_via_department_position_routes(): void
    {
        $response = $this->actingAs($this->admin)->post(route('portal.department-positions.store', $this->deptSD), [
            'department_id' => $this->deptSD->id,
            'name' => 'Wali Kelas',
            'level' => 4,
            'order' => 3,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('department_positions', [
            'department_id' => $this->deptSD->id,
            'name' => 'Wali Kelas',
        ]);

        $this->assertCount(1, $this->unitSD->fresh()->positions);
        $this->assertEquals('Wali Kelas', $this->unitSD->fresh()->positions->first()->name);
    }
}
