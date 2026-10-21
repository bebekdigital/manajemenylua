<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeEducationHistory;
use App\Models\EmployeePosition;
use App\Models\EmployeeRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_upload_and_replace_ktp_in_private_storage(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $employee = Employee::create([
            'nama' => 'Budi Santoso',
            'nik' => '3301010101010001',
        ]);

        $file = UploadedFile::fake()->create('ktp_budi.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user)->post("/staff/{$employee->id}/documents", [
            'category' => 'ktp',
            'title' => 'KTP Budi Santoso',
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $doc = EmployeeDocument::where('employee_id', $employee->id)->where('category', 'ktp')->first();
        $this->assertNotNull($doc);
        $this->assertEquals('KTP Budi Santoso', $doc->title);
        $this->assertEquals('application/pdf', $doc->mime_type);
        Storage::disk('local')->assertExists($doc->file_path);

        // Upload new file to replace KTP
        $newFile = UploadedFile::fake()->image('ktp_budi_scan.jpg', 600, 400);

        $response2 = $this->actingAs($user)->post("/staff/{$employee->id}/documents", [
            'category' => 'ktp',
            'title' => 'KTP Budi (Update)',
            'file' => $newFile,
        ]);

        $response2->assertRedirect();
        // Verify only 1 KTP exists
        $this->assertEquals(1, EmployeeDocument::where('employee_id', $employee->id)->where('category', 'ktp')->count());

        $doc->refresh();
        $this->assertEquals('KTP Budi (Update)', $doc->title);
        $this->assertStringContainsString('ktp_budi_scan.jpg', $doc->file_name);
        Storage::disk('local')->assertExists($doc->file_path);
    }

    public function test_can_upload_sk_jenjang_linked_to_employee_record(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $employee = Employee::create(['nama' => 'Ahmad', 'nik' => '3301010101010002']);
        $record = EmployeeRecord::create([
            'employee_id' => $employee->id,
            'jenjang_kepegawaian' => 'GTY',
            'tmt_kenaikan_jenjang' => '2021-07-01',
        ]);

        $file = UploadedFile::fake()->create('sk_gty_2021.pdf', 350, 'application/pdf');

        $response = $this->actingAs($user)->post("/staff/{$employee->id}/documents", [
            'category' => 'sk_jenjang',
            'record_type' => 'record',
            'record_id' => $record->id,
            'title' => 'SK Jenjang GTY 2021',
            'file' => $file,
        ]);

        $response->assertRedirect();

        $doc = EmployeeDocument::where('employee_id', $employee->id)
            ->where('category', 'sk_jenjang')
            ->where('record_type', 'record')
            ->where('record_id', $record->id)
            ->first();

        $this->assertNotNull($doc);
        $this->assertEquals('SK Jenjang GTY 2021', $doc->title);
        Storage::disk('local')->assertExists($doc->file_path);
    }

    public function test_can_upload_sk_penugasan_linked_to_employee_position(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $employee = Employee::create(['nama' => 'Siti', 'nik' => '3301010101010003']);
        $position = EmployeePosition::create([
            'employee_id' => $employee->id,
            'unit_kerja' => 'SDIT Ulil Albab',
            'jabatan' => 'Kepala Sekolah',
            'tmt_jabatan' => '2022-07-01',
        ]);

        $file = UploadedFile::fake()->create('sk_kepsek.pdf', 400, 'application/pdf');

        $response = $this->actingAs($user)->post("/staff/{$employee->id}/documents", [
            'category' => 'sk_penugasan',
            'record_type' => 'position',
            'record_id' => $position->id,
            'title' => 'SK Kepala Sekolah 2022',
            'file' => $file,
        ]);

        $response->assertRedirect();

        $doc = EmployeeDocument::where('employee_id', $employee->id)
            ->where('category', 'sk_penugasan')
            ->where('record_type', 'position')
            ->where('record_id', $position->id)
            ->first();

        $this->assertNotNull($doc);
        $this->assertEquals('SK Kepala Sekolah 2022', $doc->title);
        Storage::disk('local')->assertExists($doc->file_path);
    }

    public function test_can_upload_multiple_ijazah_documents(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $employee = Employee::create(['nama' => 'Ust. Umar', 'nik' => '3301010101010004']);
        $edu1 = EmployeeEducationHistory::create([
            'employee_id' => $employee->id,
            'jenjang_pendidikan' => 'S1',
            'jurusan' => 'Pendidikan Agama Islam',
            'instansi_pendidikan' => 'UIN Raden Mas Said',
            'tahun_lulus' => '2016',
        ]);

        // Upload Ijazah S1
        $fileS1 = UploadedFile::fake()->create('ijazah_s1.pdf', 500, 'application/pdf');
        $this->actingAs($user)->post("/staff/{$employee->id}/documents", [
            'category' => 'ijazah',
            'record_type' => 'education',
            'record_id' => $edu1->id,
            'title' => 'Ijazah S1 PAI',
            'file' => $fileS1,
        ])->assertRedirect();

        // Upload Ijazah S2 (independent / additional)
        $fileS2 = UploadedFile::fake()->create('ijazah_s2.pdf', 700, 'application/pdf');
        $this->actingAs($user)->post("/staff/{$employee->id}/documents", [
            'category' => 'ijazah',
            'title' => 'Ijazah S2 Manajemen Pendidikan',
            'file' => $fileS2,
        ])->assertRedirect();

        $docs = EmployeeDocument::where('employee_id', $employee->id)->where('category', 'ijazah')->get();
        $this->assertCount(2, $docs);
    }

    public function test_can_preview_and_download_document(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $employee = Employee::create(['nama' => 'Ust. Yusuf', 'nik' => '3301010101010005']);

        $file = UploadedFile::fake()->create('kk_yusuf.pdf', 300, 'application/pdf');
        $this->actingAs($user)->post("/staff/{$employee->id}/documents", [
            'category' => 'kk',
            'title' => 'Kartu Keluarga Yusuf',
            'file' => $file,
        ]);

        $doc = EmployeeDocument::where('employee_id', $employee->id)->first();

        // Test Preview
        $previewRes = $this->actingAs($user)->get("/staff/{$employee->id}/documents/{$doc->id}/preview");
        $previewRes->assertStatus(200);
        $previewRes->assertHeader('Content-Disposition', 'inline; filename="kk_yusuf.pdf"');

        // Test Download
        $downloadRes = $this->actingAs($user)->get("/staff/{$employee->id}/documents/{$doc->id}/download");
        $downloadRes->assertStatus(200);
        $this->assertStringContainsString('attachment', $downloadRes->headers->get('Content-Disposition') ?? '');
    }

    public function test_can_delete_document(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $employee = Employee::create(['nama' => 'Fatima', 'nik' => '3301010101010006']);

        $file = UploadedFile::fake()->create('sk_pengangkatan.pdf', 450, 'application/pdf');
        $this->actingAs($user)->post("/staff/{$employee->id}/documents", [
            'category' => 'sk_pengangkatan',
            'title' => 'SK Pengangkatan Fatima',
            'file' => $file,
        ]);

        $doc = EmployeeDocument::where('employee_id', $employee->id)->first();
        $filePath = $doc->file_path;
        Storage::disk('local')->assertExists($filePath);

        $deleteRes = $this->actingAs($user)->delete("/staff/{$employee->id}/documents/{$doc->id}");
        $deleteRes->assertRedirect();

        $this->assertNull(EmployeeDocument::find($doc->id));
        Storage::disk('local')->assertMissing($filePath);
    }
}
