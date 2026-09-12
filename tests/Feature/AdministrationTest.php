<?php

namespace Tests\Feature;

use App\Models\DocumentTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_administration_page_can_be_rendered(): void
    {
        $response = $this->get('/administration');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Administration')
            ->has('students')
            ->has('academicYears')
            ->has('classrooms')
            ->has('template')
            ->where('template.code', 'student_identity')
            ->where('template.school_profile.nama_sekolah', 'SMP IT TAHFIDZUL QURAN ULIL ALBAB KARANGANYAR')
        );
    }

    public function test_document_template_can_be_updated(): void
    {
        $updatedData = [
            'school_profile' => [
                'nama_sekolah' => 'SMP IT TAHFIDZUL QURAN ULIL ALBAB (TERAKREDITASI A)',
                'jenjang_tingkat' => 'SEKOLAH MENENGAH PERTAMA',
                'jenjang_singkat' => '( SMP )',
                'npsn' => '69990054',
                'nis_nss_nds' => '202030',
                'alamat_sekolah' => 'Jl. Solo Purwodadi KM 7',
                'kelurahan_desa' => 'Selokaton',
                'kecamatan' => 'Gondangrejo',
                'kota_kabupaten' => 'Karanganyar',
                'provinsi' => 'Jawa Tengah',
                'website' => 'http://www.smpit.ulilalbabkra.sch.id',
                'email' => 'smpit.ulil.albab.kra@gmail.com',
                'kementerian_title' => 'KEMENTERIAN PENDIDIKAN DASAR DAN MENENGAH REPUBLIK INDONESIA',
            ],
            'signatory' => [
                'tempat_titimangsa' => 'Karanganyar',
                'tanggal_titimangsa' => '15 Juli 2025',
                'jabatan' => 'Kepala Sekolah',
                'nama_kepala_sekolah' => 'Nurul Choirul Janah, S.Pd., M.Pd.',
                'nip' => '198205122005012003',
            ],
            'options' => [
                'show_tut_wuri_logo' => true,
                'show_school_logo' => true,
                'show_photo_box' => false,
                'paper_size' => 'A4',
            ],
        ];

        $response = $this->post('/administration/template', $updatedData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('document_templates', [
            'code' => 'student_identity',
        ]);

        $template = DocumentTemplate::getStudentIdentityTemplate();
        $this->assertSame('SMP IT TAHFIDZUL QURAN ULIL ALBAB (TERAKREDITASI A)', $template->school_profile['nama_sekolah']);
        $this->assertSame('Nurul Choirul Janah, S.Pd., M.Pd.', $template->signatory['nama_kepala_sekolah']);
        $this->assertFalse($template->options['show_photo_box']);
    }

    public function test_document_template_can_be_reset(): void
    {
        // First mutate
        $template = DocumentTemplate::getStudentIdentityTemplate();
        $template->update([
            'school_profile' => array_merge($template->school_profile, ['nama_sekolah' => 'SEKOLAH SEMENTARA']),
        ]);

        $response = $this->post('/administration/template/reset');

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $template->refresh();
        $this->assertSame('SMP IT TAHFIDZUL QURAN ULIL ALBAB KARANGANYAR', $template->school_profile['nama_sekolah']);
    }
}
