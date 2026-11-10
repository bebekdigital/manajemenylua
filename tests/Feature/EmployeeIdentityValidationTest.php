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
                '_tab' => 'keluarga',
                'nama' => 'Legacy Pegawai',
                'status_pernikahan' => 'Menikah',
                'nama_suami_istri' => 'Fulanah',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect("/staff/{$legacyEmployee->id}");

        $legacyEmployee->refresh();
        $this->assertEquals('Fulanah', $legacyEmployee->nama_suami_istri);
    }

    public function test_updating_alamat_tab_requires_all_address_fields(): void
    {
        $response = $this->actingAs($this->user)->put("/staff/{$this->employee->id}", [
            '_tab' => 'alamat',
            'nama' => 'Budi Santoso',
            'provinsi_domisili' => '',
            'kab_domisili' => '',
            'kecamatan_domisili' => '',
            'desa_domisili' => '',
            'dusun_domisili' => '',
            'rt_rw_domisili' => '',
            'jalan_domisili' => '',
            'provinsi_tinggal' => '',
            'kabupaten_tinggal' => '',
            'kecamatan_tinggal' => '',
            'desa_tinggal' => '',
            'dusun_tinggal' => '',
            'rt_rw_tinggal' => '',
            'jalan_tinggal' => '',
            'status_rumah' => '',
        ]);

        $response->assertSessionHasErrors([
            'provinsi_domisili',
            'kab_domisili',
            'kecamatan_domisili',
            'desa_domisili',
            'dusun_domisili',
            'rt_rw_domisili',
            'jalan_domisili',
            'provinsi_tinggal',
            'kabupaten_tinggal',
            'kecamatan_tinggal',
            'desa_tinggal',
            'dusun_tinggal',
            'rt_rw_tinggal',
            'jalan_tinggal',
            'status_rumah',
        ]);
    }

    public function test_updating_alamat_tab_succeeds_when_all_address_fields_are_filled(): void
    {
        $response = $this->actingAs($this->user)
            ->from("/staff/{$this->employee->id}")
            ->put("/staff/{$this->employee->id}", [
                '_tab' => 'alamat',
                'nama' => 'Budi Santoso',
                'provinsi_domisili' => 'JAWA TENGAH',
                'kab_domisili' => 'KABUPATEN KARANGANYAR',
                'kecamatan_domisili' => 'JATEN',
                'desa_domisili' => 'DAGEN',
                'dusun_domisili' => 'Dusun 1',
                'rt_rw_domisili' => '001/002',
                'jalan_domisili' => 'Jl. Lawu No. 10',
                'provinsi_tinggal' => 'JAWA TENGAH',
                'kabupaten_tinggal' => 'KABUPATEN KARANGANYAR',
                'kecamatan_tinggal' => 'JATEN',
                'desa_tinggal' => 'DAGEN',
                'dusun_tinggal' => 'Dusun 1',
                'rt_rw_tinggal' => '001/002',
                'jalan_tinggal' => 'Jl. Lawu No. 10',
                'status_rumah' => 'Milik Sendiri',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect("/staff/{$this->employee->id}");

        $this->employee->refresh();
        $this->assertEquals('Jl. Lawu No. 10', $this->employee->jalan_domisili);
        $this->assertEquals('Milik Sendiri', $this->employee->status_rumah);
    }
}
