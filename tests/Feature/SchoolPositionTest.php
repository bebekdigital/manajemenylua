<?php

namespace Tests\Feature;

use App\Models\SchoolLevel;
use App\Models\SchoolPosition;
use App\Models\SchoolUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolPositionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private SchoolUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $level = SchoolLevel::create(['name' => 'SD', 'order' => 1]);
        $this->unit = SchoolUnit::create(['school_level_id' => $level->id, 'name' => 'SDIT Ulil Albab']);
    }

    public function test_can_create_school_unit_position(): void
    {
        $response = $this->actingAs($this->admin)->post(route('portal.school-positions.store', $this->unit), [
            'name' => 'Wali Kelas 1',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('school_positions', [
            'school_unit_id' => $this->unit->id,
            'name' => 'Wali Kelas 1',
        ]);
    }

    public function test_cannot_create_duplicate_position_in_same_unit(): void
    {
        SchoolPosition::create([
            'school_unit_id' => $this->unit->id,
            'name' => 'Kepala Sekolah',
        ]);

        $response = $this->actingAs($this->admin)->post(route('portal.school-positions.store', $this->unit), [
            'name' => 'Kepala Sekolah',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_can_update_school_unit_position(): void
    {
        $pos = SchoolPosition::create([
            'school_unit_id' => $this->unit->id,
            'name' => 'Guru Agama',
        ]);

        $response = $this->actingAs($this->admin)->put(route('portal.school-positions.update', $pos), [
            'name' => 'Guru PAI',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('school_positions', [
            'id' => $pos->id,
            'name' => 'Guru PAI',
        ]);
    }

    public function test_can_delete_school_unit_position(): void
    {
        $pos = SchoolPosition::create([
            'school_unit_id' => $this->unit->id,
            'name' => 'Staff TU',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('portal.school-positions.destroy', $pos));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('school_positions', [
            'id' => $pos->id,
        ]);
    }
}
