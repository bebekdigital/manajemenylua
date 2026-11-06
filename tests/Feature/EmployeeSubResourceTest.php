<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeEducationHistory;
use App\Models\EmployeePosition;
use App\Models\EmployeeRecord;
use App\Models\EmployeeTrainingHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSubResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->employee = Employee::create([
            'nama' => 'Test Employee',
            'nik' => '3301010101010001',
        ]);
    }

    public function test_can_create_and_update_position(): void
    {
        // 1. Create Position
        $response = $this->actingAs($this->user)->post("/staff/{$this->employee->id}/positions", [
            'unit_kerja' => 'SDIT Ulil Albab',
            'jabatan' => 'Guru Kelas',
            'jenis_kepegawaian' => 'Tenaga Pendidik',
            'tmt_jabatan' => '2022-07-01',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('employee_positions', [
            'employee_id' => $this->employee->id,
            'unit_kerja' => 'SDIT Ulil Albab',
            'jabatan' => 'Guru Kelas',
        ]);

        $pos = EmployeePosition::where('employee_id', $this->employee->id)->first();

        // 2. Update Position
        $updateResponse = $this->actingAs($this->user)->post("/staff/{$this->employee->id}/positions", [
            'id' => $pos->id,
            'unit_kerja' => 'SDIT Ulil Albab',
            'jabatan' => 'Kepala Sekolah',
            'tmt_jabatan' => '2024-01-01',
        ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('employee_positions', [
            'id' => $pos->id,
            'jabatan' => 'Kepala Sekolah',
        ]);
    }

    public function test_can_delete_position(): void
    {
        $pos = $this->employee->positions()->create([
            'unit_kerja' => 'SDIT Ulil Albab',
            'jabatan' => 'Wali Kelas',
        ]);

        $response = $this->actingAs($this->user)->delete("/staff/{$this->employee->id}/positions/{$pos->id}");
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('employee_positions', ['id' => $pos->id]);
    }

    public function test_can_create_and_update_record(): void
    {
        $response = $this->actingAs($this->user)->post("/staff/{$this->employee->id}/records", [
            'jenjang_kepegawaian' => 'GTY',
            'no_sk' => 'SK/001/2023',
            'tmt_kenaikan_jenjang' => '2023-01-01',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('employee_records', [
            'employee_id' => $this->employee->id,
            'jenjang_kepegawaian' => 'GTY',
            'no_sk' => 'SK/001/2023',
        ]);

        $rec = EmployeeRecord::where('employee_id', $this->employee->id)->first();

        $updateResponse = $this->actingAs($this->user)->post("/staff/{$this->employee->id}/records", [
            'id' => $rec->id,
            'jenjang_kepegawaian' => 'PJT',
            'tmt_kenaikan_jenjang' => '2025-01-01',
        ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('employee_records', [
            'id' => $rec->id,
            'jenjang_kepegawaian' => 'PJT',
        ]);
    }

    public function test_can_delete_record(): void
    {
        $rec = $this->employee->records()->create([
            'jenjang_kepegawaian' => 'GTY',
            'tmt_kenaikan_jenjang' => '2023-01-01',
        ]);

        $response = $this->actingAs($this->user)->delete("/staff/{$this->employee->id}/records/{$rec->id}");
        $response->assertRedirect();

        $this->assertDatabaseMissing('employee_records', ['id' => $rec->id]);
    }

    public function test_can_create_and_update_education(): void
    {
        $response = $this->actingAs($this->user)->post("/staff/{$this->employee->id}/education", [
            'jenjang_pendidikan' => 'S1',
            'jurusan' => 'Pendidikan Matematika',
            'instansi_pendidikan' => 'Universitas Sebelas Maret',
            'tahun_lulus' => '2020',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('employee_education_histories', [
            'employee_id' => $this->employee->id,
            'jenjang_pendidikan' => 'S1',
            'jurusan' => 'Pendidikan Matematika',
        ]);

        $edu = EmployeeEducationHistory::where('employee_id', $this->employee->id)->first();

        $updateResponse = $this->actingAs($this->user)->post("/staff/{$this->employee->id}/education", [
            'id' => $edu->id,
            'jenjang_pendidikan' => 'S2',
            'instansi_pendidikan' => 'Universitas Sebelas Maret',
            'tahun_lulus' => '2023',
        ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('employee_education_histories', [
            'id' => $edu->id,
            'jenjang_pendidikan' => 'S2',
        ]);
    }

    public function test_can_delete_education(): void
    {
        $edu = $this->employee->educationHistories()->create([
            'jenjang_pendidikan' => 'S1',
            'instansi_pendidikan' => 'UNS',
        ]);

        $response = $this->actingAs($this->user)->delete("/staff/{$this->employee->id}/education/{$edu->id}");
        $response->assertRedirect();

        $this->assertDatabaseMissing('employee_education_histories', ['id' => $edu->id]);
    }

    public function test_can_create_and_update_training(): void
    {
        $response = $this->actingAs($this->user)->post("/staff/{$this->employee->id}/training", [
            'bidang' => 'Kurikulum Merdeka',
            'keahlian' => 'Perangkat Pembelajaran',
            'instansi_penyelenggara' => 'Kemdikbud',
            'tahun' => '2023',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('employee_training_histories', [
            'employee_id' => $this->employee->id,
            'bidang' => 'Kurikulum Merdeka',
        ]);

        $t = EmployeeTrainingHistory::where('employee_id', $this->employee->id)->first();

        $updateResponse = $this->actingAs($this->user)->post("/staff/{$this->employee->id}/training", [
            'id' => $t->id,
            'bidang' => 'Manajemen Kelas Modern',
            'instansi_penyelenggara' => 'Kemdikbud',
            'tahun' => '2024',
        ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('employee_training_histories', [
            'id' => $t->id,
            'bidang' => 'Manajemen Kelas Modern',
        ]);
    }

    public function test_can_delete_training(): void
    {
        $t = $this->employee->trainingHistories()->create([
            'bidang' => 'Pelatihan IT',
            'instansi_penyelenggara' => 'Yayasan',
        ]);

        $response = $this->actingAs($this->user)->delete("/staff/{$this->employee->id}/training/{$t->id}");
        $response->assertRedirect();

        $this->assertDatabaseMissing('employee_training_histories', ['id' => $t->id]);
    }
}
