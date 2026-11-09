<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeIdentityValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->employee = Employee::create([
            'nama' => 'Budi Santoso',
            'nik' => '3301010101010001',
            'jk' => 'L',
            'tempat_lahir' => 'Karanganyar',
            'tanggal_lahir' => '1990-05-15',
            'no_wa' => '081234567890',
            'email' => 'budi@example.com',
            'kepemilikan_bpjs' => 'Ya',
            'penanggung_bpjs' => 'Yayasan',
        ]);
    }

    public function test_updating_identity_tab_requires_all_nine_fields(): void
    {
        $response = $this->actingAs($this->user)->put("/staff/{$this->employee->id}", [
            '_tab' => 'identitas',
            'nama' => '',
            'nik' => '',
            'jk' => '',
            'tempat_lahir' => '',
            'tanggal_lahir' => '',
            'no_wa' => '',
            'email' => '',
            'kepemilikan_bpjs' => '',
            'penanggung_bpjs' => '',
        ]);

        $response->assertSessionHasErrors([
            'nama',
            'nik',
            'jk',
            'tempat_lahir',
            'tanggal_lahir',
            'no_wa',
            'email',
            'kepemilikan_bpjs',
            'penanggung_bpjs',
        ]);
    }

    public function test_updating_identity_tab_succeeds_when_all_required_fields_are_filled(): void
    {
        $response = $this->actingAs($this->user)
            ->from("/staff/{$this->employee->id}")
            ->put("/staff/{$this->employee->id}", [
                '_tab' => 'identitas',
                'nama' => 'Budi Santoso, S.Pd',
                'nik' => '3301010101010099',
                'jk' => 'L',
                'tempat_lahir' => 'Solo',
                'tanggal_lahir' => '1992-08-20',
                'no_wa' => '081987654321',
                'email' => 'budibaru@example.com',
                'kepemilikan_bpjs' => 'Tidak',
                'penanggung_bpjs' => 'Tidak Ada',
            ]);

        $response->assertRedirect("/staff/{$this->employee->id}");
        $this->employee->refresh();

        $this->assertEquals('Budi Santoso, S.Pd', $this->employee->nama);
        $this->assertEquals('3301010101010099', $this->employee->nik);
        $this->assertEquals('Solo', $this->employee->tempat_lahir);
        $this->assertEquals('1992-08-20', $this->employee->tanggal_lahir?->format('Y-m-d'));
        $this->assertEquals('Tidak', $this->employee->kepemilikan_bpjs);
        $this->assertEquals('Tidak Ada', $this->employee->penanggung_bpjs);
    }

    public function test_updating_other_tabs_does_not_require_missing_identity_fields(): void
    {
        // Legacy employee with empty identity fields
        $legacyEmployee = Employee::create([
            'nama' => 'Legacy Pegawai',
        ]);

        $response = $this->actingAs($this->user)
            ->from("/staff/{$legacyEmployee->id}")
            ->put("/staff/{$legacyEmployee->id}", [
                '_tab' => 'alamat',
                'nama' => 'Legacy Pegawai',
                'jalan_domisili' => 'Jl. Lawu No. 12',
                'desa_domisili' => 'Dagen',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect("/staff/{$legacyEmployee->id}");

        $legacyEmployee->refresh();
        $this->assertEquals('Jl. Lawu No. 12', $legacyEmployee->jalan_domisili);
    }
}
