<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'role',
        'feature_group',
        'feature_name',
        'can_view',
        'can_create',
        'can_update',
        'can_delete',
        'can_export',
        'can_import',
    ];

    protected function casts(): array
    {
        return [
            'can_view' => 'boolean',
            'can_create' => 'boolean',
            'can_update' => 'boolean',
            'can_delete' => 'boolean',
            'can_export' => 'boolean',
            'can_import' => 'boolean',
        ];
    }

    /**
     * Definisi master fitur yang dapat diatur hak aksesnya di portal.
     *
     * @return array<int, array<string, mixed>>
     */
    /**
     * Definisi master fitur yang dapat diatur hak aksesnya di portal.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaultFeatures(): array
    {
        return [
            // Kategori: Dashboard & Beranda
            [
                'group' => 'dashboard_overview',
                'name' => 'Beranda & Ringkasan',
                'category' => 'Dashboard',
                'description' => 'Akses halaman beranda, ringkasan statistik yayasan, kartu metrik cepat, dan grafik umum.',
                'supported_actions' => ['view'],
            ],

            // Kategori: Database Kesiswaan
            [
                'group' => 'student_directory',
                'name' => 'Direktori Data Siswa',
                'category' => 'Database Siswa',
                'description' => 'Akses tabel daftar siswa, pencarian, filter jenjang/unit/kelas, ekspor Excel, dan impor data siswa baru.',
                'supported_actions' => ['view', 'create', 'update', 'delete', 'export', 'import'],
            ],
            [
                'group' => 'student_detail',
                'name' => 'Detail Profil & Mutasi Siswa',
                'category' => 'Database Siswa',
                'description' => 'Melihat dan mengubah biodata lengkap siswa, data orang tua/wali, saudara kandung (siblings), dan riwayat mutasi akademik.',
                'supported_actions' => ['view', 'create', 'update', 'delete'],
            ],
            [
                'group' => 'student_inline_edit',
                'name' => 'Edit Cepat (Inline Edit Siswa)',
                'category' => 'Database Siswa',
                'description' => 'Pengeditan langsung massal data siswa berbasis baris spreadsheet tanpa membuka halaman detail satu per satu.',
                'supported_actions' => ['view', 'update'],
            ],
            [
                'group' => 'student_photos',
                'name' => 'Upload & Galeri Foto Siswa',
                'category' => 'Database Siswa',
                'description' => 'Unggah foto siswa secara massal dalam berkas arsip zip/gambar dan pemantauan kelengkapan foto siswa.',
                'supported_actions' => ['view', 'create', 'delete'],
            ],
            [
                'group' => 'student_mapping',
                'name' => 'Pemetaan (Mapping) Kelas Siswa',
                'category' => 'Database Siswa',
                'description' => 'Penempatan, migrasi, dan pemetaan kelas/rombel siswa antar tahun ajaran secara batch.',
                'supported_actions' => ['view', 'create', 'update'],
            ],

            // Kategori: Database Kepegawaian (SDM)
            [
                'group' => 'employee_directory',
                'name' => 'Direktori Data Pegawai',
                'category' => 'Database Pegawai',
                'description' => 'Daftar guru dan tenaga kependidikan yayasan, status keaktifan, pencarian, ekspor data, dan impor data pegawai.',
                'supported_actions' => ['view', 'create', 'update', 'delete', 'export', 'import'],
            ],
            [
                'group' => 'employee_detail_history',
                'name' => 'Detail Profil & Riwayat Penugasan',
                'category' => 'Database Pegawai',
                'description' => 'Melihat dan mengubah riwayat penugasan unit kerja, riwayat jabatan, riwayat pendidikan, dan biodata keluarga pegawai.',
                'supported_actions' => ['view', 'create', 'update', 'delete'],
            ],
            [
                'group' => 'employee_documents',
                'name' => 'Dokumen & Berkas SK Pegawai',
                'category' => 'Database Pegawai',
                'description' => 'Unggah, pratinjau, unduh, dan hapus berkas SK pengangkatan, sertifikat pendidik, dan ijazah pegawai.',
                'supported_actions' => ['view', 'create', 'delete', 'export'],
            ],

            // Kategori: Administrasi & Persuratan
            [
                'group' => 'administration_identity_document',
                'name' => 'Cetak Dokumen & Biodata Siswa',
                'category' => 'Administrasi',
                'description' => 'Cetak formulir identitas siswa standar, buku induk kesiswaan, dan surat keterangan aktif sekolah.',
                'supported_actions' => ['view', 'create', 'export'],
            ],
            [
                'group' => 'administration_template_editor',
                'name' => 'Editor Template Surat & Dokumen',
                'category' => 'Administrasi',
                'description' => 'Pengaturan template dokumen, layout margin, kop surat, variabel otomatis, dan teks blanko administrasi.',
                'supported_actions' => ['view', 'update'],
            ],

            // Kategori: Laporan & Statistik
            [
                'group' => 'statistics_student_analytics',
                'name' => 'Statistik & Analisis Kesiswaan',
                'category' => 'Statistik',
                'description' => 'Grafik dan tabel rekapitulasi siswa per jenjang, unit sekolah, jenis kelamin, desil ekonomi, dan status PIP/KIP.',
                'supported_actions' => ['view', 'export'],
            ],

            // Kategori: Pembelajaran & Akademik
            [
                'group' => 'pembelajaran_classrooms',
                'name' => 'Rombongan Belajar (Rombel) & Kelas',
                'category' => 'Pembelajaran',
                'description' => 'Kelola daftar rombel per unit & tahun ajaran, plotting penambahan/pengeluaran siswa di kelas, dan pengaturan wali kelas.',
                'supported_actions' => ['view', 'create', 'update', 'delete'],
            ],

            // Kategori: Pengaturan Master (Portal)
            [
                'group' => 'portal_academic_years',
                'name' => 'Pengaturan: Tahun Ajaran & Semester',
                'category' => 'Pengaturan Master',
                'description' => 'Pembuatan tahun ajaran baru, penetapan semester aktif (Ganjil/Genap), dan pengaturan kalender akademik.',
                'supported_actions' => ['view', 'create', 'update', 'delete'],
            ],
            [
                'group' => 'portal_school_units',
                'name' => 'Pengaturan: Jenjang, Unit & Program',
                'category' => 'Pengaturan Master',
                'description' => 'Master jenjang sekolah (PAUD/TK, SD, SMP, SMA), unit sekolah, program pendidikan (Reguler/Tahfidz), dan master rombel.',
                'supported_actions' => ['view', 'create', 'update', 'delete'],
            ],
            [
                'group' => 'portal_positions',
                'name' => 'Pengaturan: Struktur Bidang & Jabatan',
                'category' => 'Pengaturan Master',
                'description' => 'Master struktur bidang yayasan, penugasan jabatan unit sekolah, dan jabatan fungsional yayasan.',
                'supported_actions' => ['view', 'create', 'update', 'delete'],
            ],
            [
                'group' => 'portal_user_accounts',
                'name' => 'Pengaturan: Manajemen Akun Pengguna',
                'category' => 'Pengaturan Master',
                'description' => 'Pembuatan akun login baru, ubah akun, ubah role kewenangan, dan hapus akun staf/guru.',
                'supported_actions' => ['view', 'create', 'update', 'delete'],
            ],
            [
                'group' => 'portal_user_generate',
                'name' => 'Pengaturan: Generate Akun Otomatis',
                'category' => 'Pengaturan Master',
                'description' => 'Otomasi pembuatan massal akun pengguna dari data penugasan pegawai (username cerdas dan password tanggal lahir).',
                'supported_actions' => ['view', 'create'],
            ],
            [
                'group' => 'portal_role_permissions',
                'name' => 'Pengaturan: Matriks Hak Akses',
                'category' => 'Pengaturan Master',
                'description' => 'Konfigurasi matriks hak akses dan izin fitur bagi setiap jabatan / role di lingkungan sistem.',
                'supported_actions' => ['view', 'update'],
            ],
        ];
    }

    /**
     * Dapatkan permissions untuk role tertentu, digabungkan dengan list default.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getPermissionsMatrix(string $role): array
    {
        self::ensureDefaultsExistForRole($role);

        $saved = self::where('role', $role)->get()->keyBy('feature_group');
        $features = self::defaultFeatures();

        return array_map(function ($feat) use ($saved, $role) {
            $group = $feat['group'];
            $perm = $saved->get($group);

            $isSuper = ($role === 'superadmin');

            return [
                'group' => $group,
                'name' => $feat['name'],
                'category' => $feat['category'],
                'description' => $feat['description'],
                'supported_actions' => $feat['supported_actions'],
                'can_view' => $isSuper || (bool) ($perm?->can_view),
                'can_create' => $isSuper || (bool) ($perm?->can_create),
                'can_update' => $isSuper || (bool) ($perm?->can_update),
                'can_delete' => $isSuper || (bool) ($perm?->can_delete),
                'can_export' => $isSuper || (bool) ($perm?->can_export),
                'can_import' => $isSuper || (bool) ($perm?->can_import),
            ];
        }, $features);
    }

    /**
     * Inisialisasi default permissions jika role belum pernah diset.
     */
    public static function ensureDefaultsExistForRole(string $role): void
    {
        $features = self::defaultFeatures();
        $existingGroups = self::where('role', $role)->pluck('feature_group')->toArray();

        foreach ($features as $feat) {
            $group = $feat['group'];

            // Jika fitur ini belum pernah disimpan untuk role ini, tambahkan
            if (! in_array($group, $existingGroups)) {
                $defaults = self::getDefaultValuesForRoleAndGroup($role, $group);

                self::create([
                    'role' => $role,
                    'feature_group' => $group,
                    'feature_name' => $feat['name'],
                    'can_view' => $defaults['view'] ?? false,
                    'can_create' => $defaults['create'] ?? false,
                    'can_update' => $defaults['update'] ?? false,
                    'can_delete' => $defaults['delete'] ?? false,
                    'can_export' => $defaults['export'] ?? false,
                    'can_import' => $defaults['import'] ?? false,
                ]);
            }
        }
    }

    /**
     * Nilai rekomendasi awal hak akses berdasarkan role dan fitur.
     *
     * @return array<string, bool>
     */
    protected static function getDefaultValuesForRoleAndGroup(string $role, string $group): array
    {
        if ($role === 'superadmin') {
            return [
                'view' => true,
                'create' => true,
                'update' => true,
                'delete' => true,
                'export' => true,
                'import' => true,
            ];
        }

        if ($role === 'Kepala Bidang SDM') {
            return match ($group) {
                'dashboard_overview' => ['view' => true],
                'student_directory' => ['view' => true, 'export' => true],
                'student_detail' => ['view' => true],
                'student_inline_edit' => ['view' => false],
                'student_photos' => ['view' => true],
                'student_mapping' => ['view' => true],
                'employee_directory' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true, 'export' => true, 'import' => true],
                'employee_detail_history' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true],
                'employee_documents' => ['view' => true, 'create' => true, 'delete' => true, 'export' => true],
                'administration_identity_document' => ['view' => true, 'export' => true],
                'administration_template_editor' => ['view' => false],
                'statistics_student_analytics' => ['view' => true, 'export' => true],
                'pembelajaran_classrooms' => ['view' => true],
                'portal_academic_years' => ['view' => true],
                'portal_school_units' => ['view' => true],
                'portal_positions' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true],
                'portal_user_accounts' => ['view' => true],
                'portal_user_generate' => ['view' => false],
                'portal_role_permissions' => ['view' => true],
                default => ['view' => false],
            };
        }

        if (in_array($role, ['Guru', 'Guru Kelas', 'Guru Mata Pelajaran', 'Wali Kelas'])) {
            return match ($group) {
                'dashboard_overview' => ['view' => true],
                'student_directory' => ['view' => true],
                'student_detail' => ['view' => true],
                'student_inline_edit' => ['view' => false],
                'student_photos' => ['view' => true],
                'student_mapping' => ['view' => false],
                'employee_directory' => ['view' => true],
                'employee_detail_history' => ['view' => true],
                'employee_documents' => ['view' => true, 'export' => true],
                'administration_identity_document' => ['view' => true, 'export' => true],
                'administration_template_editor' => ['view' => false],
                'statistics_student_analytics' => ['view' => true],
                'pembelajaran_classrooms' => ['view' => true, 'update' => true],
                default => ['view' => false],
            };
        }

        // Default untuk role lain
        return ['view' => false];
    }
}
