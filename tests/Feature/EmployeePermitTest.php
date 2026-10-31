<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeePermit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeePermitTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_employee_permit_with_attachment(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['role' => 'admin']);
        $employee = Employee::create([
            'nama' => 'Ahmad Fauzi',
            'nik' => '3301010101010002',
        ]);

        $file = UploadedFile::fake()->create('surat_dokter.pdf', 300, 'application/pdf');

        $response = $this->actingAs($user)->post("/staff/{$employee->id}/permits", [
            'tanggal' => '2026-10-31',
            'jenis' => 'Sakit',
            'detail' => 'Rawat Jalan',
            'keterangan' => 'Istirahat dokter selama 2 hari',
            'status' => 'diajukan',
            'lampiran' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $permit = EmployeePermit::where('employee_id', $employee->id)->first();
        $this->assertNotNull($permit);
        $this->assertEquals('Sakit', $permit->jenis);
        $this->assertEquals('Rawat Jalan', $permit->detail);
        $this->assertEquals('diajukan', $permit->status);
        $this->assertNotNull($permit->lampiran_path);
        $this->assertEquals('surat_dokter.pdf', $permit->lampiran_nama);

        Storage::disk('local')->assertExists($permit->lampiran_path);
    }

    public function test_user_can_update_employee_permit(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['role' => 'admin']);
        $employee = Employee::create([
            'nama' => 'Ahmad Fauzi',
            'nik' => '3301010101010003',
        ]);

        $permit = EmployeePermit::create([
            'employee_id' => $employee->id,
            'tanggal' => '2026-10-31',
            'jenis' => 'Izin',
            'detail' => 'Keperluan Pribadi',
            'keterangan' => 'Acara keluarga',
            'status' => 'diajukan',
        ]);

        $response = $this->actingAs($user)->put("/staff/{$employee->id}/permits/{$permit->id}", [
            'tanggal' => '2026-10-31',
            'jenis' => 'Cuti',
            'detail' => 'Cuti Tahunan',
            'keterangan' => 'Keperluan keluarga penting',
            'status' => 'disetujui',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $permit->refresh();
        $this->assertEquals('Cuti', $permit->jenis);
        $this->assertEquals('Cuti Tahunan', $permit->detail);
        $this->assertEquals('disetujui', $permit->status);
    }

    public function test_user_can_update_permit_note_and_status(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $employee = Employee::create([
            'nama' => 'Ahmad Fauzi',
            'nik' => '3301010101010004',
        ]);

        $permit = EmployeePermit::create([
            'employee_id' => $employee->id,
            'tanggal' => '2026-10-31',
            'jenis' => 'Izin',
            'detail' => 'Ada Urusan',
            'status' => 'diajukan',
        ]);

        $response = $this->actingAs($user)->patch("/staff/{$employee->id}/permits/{$permit->id}/note", [
            'status' => 'disetujui',
            'catatan' => 'Disetujui pimpinan lembaga',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $permit->refresh();
        $this->assertEquals('disetujui', $permit->status);
        $this->assertEquals('Disetujui pimpinan lembaga', $permit->catatan);
    }

    public function test_user_can_delete_employee_permit(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['role' => 'admin']);
        $employee = Employee::create([
            'nama' => 'Ahmad Fauzi',
            'nik' => '3301010101010005',
        ]);

        $file = UploadedFile::fake()->create('lampiran.pdf', 100, 'application/pdf');
        $storedPath = $file->store("employee_permits/{$employee->id}", 'local');

        $permit = EmployeePermit::create([
            'employee_id' => $employee->id,
            'tanggal' => '2026-10-31',
            'jenis' => 'Sakit',
            'status' => 'diajukan',
            'lampiran_path' => $storedPath,
            'lampiran_nama' => 'lampiran.pdf',
        ]);

        Storage::disk('local')->assertExists($storedPath);

        $response = $this->actingAs($user)->delete("/staff/{$employee->id}/permits/{$permit->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertNull(EmployeePermit::find($permit->id));
        Storage::disk('local')->assertMissing($storedPath);
    }

    public function test_permits_can_be_saved_and_synced_via_main_staff_update(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['role' => 'admin']);
        $employee = Employee::create([
            'nama' => 'Budi Santoso',
            'nik' => '3301010101010006',
        ]);

        $existingPermit = EmployeePermit::create([
            'employee_id' => $employee->id,
            'tanggal' => '2026-10-25',
            'jenis' => 'Izin',
            'detail' => 'Acara Keluarga',
            'status' => 'diajukan',
        ]);

        $file = UploadedFile::fake()->create('surat_tugas.pdf', 200, 'application/pdf');

        $response = $this->actingAs($user)->put("/staff/{$employee->id}", [
            'nama' => 'Budi Santoso',
            'nik' => '3301010101010006',
            'riwayat_perizinan' => [
                [
                    'id' => $existingPermit->id,
                    'tanggal' => '2026-10-25',
                    'tanggal_selesai' => '2026-10-26',
                    'jenis' => 'Izin Resmi',
                    'detail' => 'Acara Keluarga Besar',
                    'status' => 'disetujui',
                ],
                [
                    'id' => null,
                    'tanggal' => '2026-10-31',
                    'tanggal_selesai' => null,
                    'jenis' => 'Dinas',
                    'detail' => 'Pelatihan MGMP',
                    'status' => 'diajukan',
                    'lampiran' => $file,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(2, $employee->permits()->count());

        $existingPermit->refresh();
        $this->assertEquals('Izin Resmi', $existingPermit->jenis);
        $this->assertEquals('disetujui', $existingPermit->status);

        $newPermit = $employee->permits()->where('jenis', 'Dinas')->first();
        $this->assertNotNull($newPermit);
        $this->assertEquals('Pelatihan MGMP', $newPermit->detail);
        $this->assertNotNull($newPermit->lampiran_path);
        Storage::disk('local')->assertExists($newPermit->lampiran_path);

        // Uji penghapusan baris jika tidak lagi dikirim
        $this->actingAs($user)->put("/staff/{$employee->id}", [
            'nama' => 'Budi Santoso',
            'nik' => '3301010101010006',
            'riwayat_perizinan' => [
                [
                    'id' => $newPermit->id,
                    'tanggal' => '2026-10-31',
                    'jenis' => 'Dinas',
                ],
            ],
        ]);
        $this->assertEquals(1, $employee->permits()->count());
        $this->assertNull(EmployeePermit::find($existingPermit->id));
    }
}
