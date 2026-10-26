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
    public static function defaultFeatures(): array
    {
        return [
            [
                'group' => 'database_students',
                'name' => 'Data Siswa',
                'category' => 'Database',
                'description' => 'Akses direktori siswa, mutasi, detail data siswa, dan upload berkas.',
                'supported_actions' => ['view', 'create', 'update', 'delete', 'export', 'import'],
            ],
            [
                'group' => 'database_employees',
                'name' => 'Data Pegawai (SDM)',
                'category' => 'Database',
                'description' => 'Akses biodata pegawai, riwayat penugasan unit, dan dokumen kepegawaian.',
                'supported_actions' => ['view', 'create', 'update', 'delete', 'export', 'import'],
            ],
            [
                'group' => 'administration',
                'name' => 'Administrasi & Dokumen',
                'category' => 'Operasional',
                'description' => 'Cetak formulir biodata, surat aktif sekolah, dan pengaturan template dokumen.',
                'supported_actions' => ['view', 'create', 'update', 'delete', 'export'],
            ],
            [
                'group' => 'statistics',
                'name' => 'Statistik & Analitik',
                'category' => 'Laporan',
                'description' => 'Grafik rekapitulasi data siswa, status PIP, jenis kelamin, dan statistik guru.',
                'supported_actions' => ['view', 'export'],
            ],
            [
                'group' => 'pembelajaran',
                'name' => 'Pembelajaran & Kelas',
                'category' => 'Akademik',
                'description' => 'Pengelolaan rombel, penempatan siswa di kelas, dan catatan pembelajaran.',
                'supported_actions' => ['view', 'create', 'update', 'delete'],
            ],
            [
                'group' => 'portal_academic',
                'name' => 'Pengaturan: Tahun Ajaran',
                'category' => 'Pengaturan Master',
                'description' => 'Pengaturan tahun ajaran baru, semester aktif, dan tanggal kalender akademik.',
                'supported_actions' => ['view', 'create', 'update', 'delete'],
            ],
            [
                'group' => 'portal_structure',
                'name' => 'Pengaturan: Unit & Bidang',
                'category' => 'Pengaturan Master',
                'description' => 'Pengaturan jenjang sekolah, unit sekolah, program studi, kelas, dan jabatan.',
                'supported_actions' => ['view', 'create', 'update', 'delete'],
            ],
            [
                'group' => 'portal_users',
                'name' => 'Pengaturan: Pengguna',
                'category' => 'Pengaturan Master',
                'description' => 'Pengelolaan akun login pengguna, tambah akun baru, dan generate akun otomatis.',
                'supported_actions' => ['view', 'create', 'update', 'delete', 'import'],
            ],
            [
                'group' => 'portal_permissions',
                'name' => 'Pengaturan: Hak Akses',
                'category' => 'Pengaturan Master',
                'description' => 'Matriks perizinan fitur untuk setiap role pengguna sistem.',
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
        $existingCount = self::where('role', $role)->count();
        if ($existingCount > 0) {
            return;
        }

        $features = self::defaultFeatures();

        foreach ($features as $feat) {
            $group = $feat['group'];
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
                'database_students' => ['view' => true, 'export' => true],
                'database_employees' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true, 'export' => true, 'import' => true],
                'administration' => ['view' => true, 'export' => true],
                'statistics' => ['view' => true, 'export' => true],
                'pembelajaran' => ['view' => true],
                'portal_academic' => ['view' => true],
                'portal_structure' => ['view' => true, 'update' => true],
                'portal_users' => ['view' => true],
                'portal_permissions' => ['view' => true],
                default => ['view' => false],
            };
        }

        if (in_array($role, ['Guru', 'Guru Kelas', 'Guru Mata Pelajaran', 'Wali Kelas'])) {
            return match ($group) {
                'database_students' => ['view' => true],
                'database_employees' => ['view' => true],
                'administration' => ['view' => true, 'export' => true],
                'statistics' => ['view' => true],
                'pembelajaran' => ['view' => true, 'update' => true],
                default => ['view' => false],
            };
        }

        // Default untuk role lain
        return ['view' => false];
    }
}
