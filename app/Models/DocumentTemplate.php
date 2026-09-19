<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'description',
        'school_profile',
        'signatory',
        'template_content',
        'options',
    ];

    protected function casts(): array
    {
        return [
            'school_profile' => 'array',
            'signatory' => 'array',
            'template_content' => 'array',
            'options' => 'array',
        ];
    }

    /**
     * Get the default configuration for student identity template.
     *
     * @return array<string, mixed>
     */
    public static function getDefaultStudentIdentityConfig(): array
    {
        return [
            'code' => 'student_identity',
            'title' => 'Berkas Identitas Peserta Didik & Cover Rapor',
            'description' => 'Template resmi cetak Berkas Identitas Siswa, Cover, dan Profil Sekolah',
            'school_profile' => [
                'nama_sekolah' => 'SMP IT TAHFIDZUL QURAN ULIL ALBAB KARANGANYAR',
                'jenjang_tingkat' => 'SEKOLAH MENENGAH PERTAMA',
                'jenjang_singkat' => '( SMP )',
                'npsn' => '69990054',
                'nis_nss_nds' => '-',
                'alamat_sekolah' => 'Jl. Solo Purwodadi KM 7, Ngaglik RT: 02/VIII',
                'kelurahan_desa' => 'Selokaton',
                'kecamatan' => 'Kec. Gondangrejo',
                'kota_kabupaten' => 'Kab. Karanganyar',
                'provinsi' => 'Prov. Jawa Tengah',
                'website' => 'http://www.smpit.ulilalbabkra.sch.id',
                'email' => 'smpit.ulil.albab.kra@gmail.com',
                'kementerian_title' => "KEMENTERIAN PENDIDIKAN DASAR DAN MENENGAH\nREPUBLIK INDONESIA",
            ],
            'signatory' => [
                'tempat_penandatangan' => 'Karanganyar',
                'jabatan' => 'Kepala Sekolah',
                'nama_kepala_sekolah' => 'Nurul Choirul Janah, S.Pd.',
                'nip' => '',
            ],
            'template_content' => [
                'cover_title' => 'SEKOLAH MENENGAH PERTAMA',
                'cover_subtitle' => '( SMP )',
                'identity_title' => 'IDENTITAS PESERTA DIDIK',
                'fields' => [
                    ['no' => 1, 'label' => 'Nama Lengkap Peserta Didik', 'value' => '{{Nama}}', 'type' => 'text'],
                    ['no' => 2, 'label' => 'Nomor Induk / NISN', 'value' => '{{NIS}} / {{NISN}}', 'type' => 'text'],
                    ['no' => 3, 'label' => 'Tempat, Tanggal Lahir', 'value' => '{{Tempat Lahir}}, {{TTL}}', 'type' => 'text'],
                    ['no' => 4, 'label' => 'Jenis Kelamin', 'value' => '{{JK}}', 'type' => 'text'],
                    ['no' => 5, 'label' => 'Agama', 'value' => '{{Agama}}', 'type' => 'text'],
                    ['no' => 6, 'label' => 'Status dalam Keluarga', 'value' => 'Anak Kandung', 'type' => 'text'],
                    ['no' => 7, 'label' => 'Anak ke', 'value' => '{{Anak ke}}', 'type' => 'text'],
                    ['no' => 8, 'label' => 'Alamat Peserta Didik', 'value' => '{{Alamat}}', 'type' => 'text'],
                    ['no' => 9, 'label' => 'Nomor Telepon', 'value' => '{{HP}}', 'type' => 'text'],
                    ['no' => 10, 'label' => 'Sekolah Asal', 'value' => '{{Sekolah Asal}}', 'type' => 'text'],
                    [
                        'no' => 11,
                        'label' => 'Diterima di sekolah ini',
                        'type' => 'group',
                        'sub_items' => [
                            ['key' => 'a', 'label' => 'Di kelas', 'value' => '{{Kelas Diterima}}'],
                            ['key' => 'b', 'label' => 'Pada tanggal', 'value' => '{{Tanggal Diterima}}'],
                        ],
                    ],
                    [
                        'no' => 12,
                        'label' => 'Nama Orang Tua',
                        'type' => 'group',
                        'sub_items' => [
                            ['key' => 'a', 'label' => 'Ayah', 'value' => '{{Nama Ayah}}'],
                            ['key' => 'b', 'label' => 'Ibu', 'value' => '{{Nama Ibu}}'],
                        ],
                    ],
                    ['no' => 13, 'label' => 'Alamat Orang Tua', 'value' => '{{Alamat}}', 'type' => 'text'],
                    ['no' => 14, 'label' => 'Nomor Telepon Orang Tua', 'value' => '{{HP}}', 'type' => 'text'],
                    [
                        'no' => 15,
                        'label' => 'Pekerjaan Orang Tua',
                        'type' => 'group',
                        'sub_items' => [
                            ['key' => 'a', 'label' => 'Ayah', 'value' => '{{Pekerjaan Ayah}}'],
                            ['key' => 'b', 'label' => 'Ibu', 'value' => '{{Pekerjaan Ibu}}'],
                        ],
                    ],
                    ['no' => 16, 'label' => 'Nama Wali', 'value' => '{{Nama Wali}}', 'type' => 'text'],
                    ['no' => 17, 'label' => 'Alamat Wali', 'value' => '{{Alamat Wali}}', 'type' => 'text'],
                    ['no' => 18, 'label' => 'Nomor Telepon Wali', 'value' => '{{HP Wali}}', 'type' => 'text'],
                    ['no' => 19, 'label' => 'Pekerjaan Wali', 'value' => '{{Pekerjaan Wali}}', 'type' => 'text'],
                ],
            ],
            'options' => [
                'show_tut_wuri_logo' => true,
                'show_school_logo' => true,
                'show_photo_box' => true,
                'paper_size' => 'A4',
                'identity_visibility' => [
                    'nama_lengkap' => true,
                    'nomor_induk' => true,
                    'tempat_tanggal_lahir' => true,
                    'jenis_kelamin' => true,
                    'agama' => true,
                    'status_keluarga' => true,
                    'anak_ke' => true,
                    'alamat_peserta' => true,
                    'nomor_telepon' => true,
                    'sekolah_asal' => true,
                    'diterima_di_sekolah' => true,
                    'nama_orang_tua' => true,
                    'alamat_orang_tua' => true,
                    'telepon_orang_tua' => true,
                    'pekerjaan_orang_tua' => true,
                    'nama_wali' => true,
                    'alamat_wali' => true,
                    'telepon_wali' => true,
                    'pekerjaan_wali' => true,
                ],
            ],
        ];
    }

    /**
     * Get or create active student identity template.
     */
    public static function getStudentIdentityTemplate(): self
    {
        $default = self::getDefaultStudentIdentityConfig();

        return self::firstOrCreate(
            ['code' => 'student_identity'],
            $default
        );
    }
}
