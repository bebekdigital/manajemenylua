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

    private SchoolUnit $unitSD;

    private SchoolUnit $unitSMP;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $levelSD = SchoolLevel::create(['name' => 'SD', 'order' => 1]);
        $levelSMP = SchoolLevel::create(['name' => 'SMP', 'order' => 2]);
        $this->unitSD = SchoolUnit::create(['school_level_id' => $levelSD->id, 'name' => 'SDIT Ulil Albab']);
        $this->unitSMP = SchoolUnit::create(['school_level_id' => $levelSMP->id, 'name' => 'SMPIT Ulil Albab']);
    }

    public function test_can_create_new_master_position_and_attach_to_unit(): void
    {
        $response = $this->actingAs($this->admin)->post(route('portal.school-positions.store', $this->unitSD), [
            'name' => 'Guru',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('school_positions', [
            'name' => 'Guru',
        ]);

        $position = SchoolPosition::where('name', 'Guru')->first();
        $this->assertDatabaseHas('school_unit_positions', [
            'school_unit_id' => $this->unitSD->id,
            'school_position_id' => $position->id,
        ]);
    }

    public function test_can_assign_existing_master_position_to_another_unit_with_same_id(): void
    {
        // 1. Buat master posisi "Guru"
        $position = SchoolPosition::create(['name' => 'Guru']);
        $this->unitSD->positions()->attach($position->id);

        // 2. Unit SMP memilih jabatan "Guru" yang sudah ada via dropdown ID
        $response = $this->actingAs($this->admin)->post(route('portal.school-positions.store', $this->unitSMP), [
            'school_position_id' => $position->id,
        ]);

        $response->assertSessionHas('success');

        // Pastikan hanya ada 1 record master "Guru" di database
        $this->assertEquals(1, SchoolPosition::where('name', 'Guru')->count());

        // Pastikan terhubung ke kedua unit
        $this->assertDatabaseHas('school_unit_positions', [
            'school_unit_id' => $this->unitSD->id,
            'school_position_id' => $position->id,
        ]);
        $this->assertDatabaseHas('school_unit_positions', [
            'school_unit_id' => $this->unitSMP->id,
            'school_position_id' => $position->id,
        ]);
    }

    public function test_cannot_assign_duplicate_position_to_same_unit(): void
    {
        $position = SchoolPosition::create(['name' => 'Kepala Sekolah']);
        $this->unitSD->positions()->attach($position->id);

        $response = $this->actingAs($this->admin)->post(route('portal.school-positions.store', $this->unitSD), [
            'school_position_id' => $position->id,
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_can_update_master_position_name(): void
    {
        $pos = SchoolPosition::create(['name' => 'Guru Agama']);
        $this->unitSD->positions()->attach($pos->id);

        $response = $this->actingAs($this->admin)->put(route('portal.school-positions.update', $pos), [
            'name' => 'Guru PAI',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('school_positions', [
            'id' => $pos->id,
            'name' => 'Guru PAI',
        ]);
    }

    public function test_can_detach_position_from_specific_unit_without_deleting_from_other_unit(): void
    {
        $pos = SchoolPosition::create(['name' => 'Guru']);
        $this->unitSD->positions()->attach($pos->id);
        $this->unitSMP->positions()->attach($pos->id);

        // Hapus dari SD
        $response = $this->actingAs($this->admin)->delete(route('portal.school-positions.detach', [
            'schoolUnit' => $this->unitSD,
            'schoolPosition' => $pos,
        ]));

        $response->assertSessionHas('success');

        // Tidak lagi ada di SD
        $this->assertDatabaseMissing('school_unit_positions', [
            'school_unit_id' => $this->unitSD->id,
            'school_position_id' => $pos->id,
        ]);

        // Tetap ada di SMP
        $this->assertDatabaseHas('school_unit_positions', [
            'school_unit_id' => $this->unitSMP->id,
            'school_position_id' => $pos->id,
        ]);

        // Master posisi "Guru" tetap ada
        $this->assertDatabaseHas('school_positions', [
            'id' => $pos->id,
            'name' => 'Guru',
        ]);
    }
}
