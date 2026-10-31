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
            // Kategori: Database Pegawai (Parent: Data Pegawai)
            [
                'group' => 'employee_detail_history',
                'name' => 'Data Pegawai',
                'category' => 'Database Pegawai',
                'description' => 'Akses modul data pegawai (direktori, profil, dan sub-modul terkait).',
                'parent_group' => null,
                'supported_actions' => ['view', 'create', 'update', 'delete', 'export', 'import'],
            ],
            // Kategori: Database Pegawai (Child: Data Kepegawaian)
            [
                'group' => 'employee_kepegawaian',
                'name' => 'Data Kepegawaian',
                'category' => 'Database Pegawai',
                'description' => 'Akses rincian data kepegawaian (NIPY, status keaktifan, TMT/TST, dan riwayat di Tab Kepegawaian).',
                'parent_group' => 'employee_detail_history',
                'supported_actions' => ['view', 'create', 'update', 'delete', 'export', 'import'],
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
                'parent_group' => $feat['parent_group'] ?? null,
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
        $parentPerm = self::where('role', $role)->where('feature_group', 'employee_detail_history')->first();

        foreach ($features as $feat) {
            $group = $feat['group'];

            // Jika fitur ini belum pernah disimpan untuk role ini, tambahkan
            if (! in_array($group, $existingGroups)) {
                $defaults = self::getDefaultValuesForRoleAndGroup($role, $group);

                $canView = ($group === 'employee_kepegawaian' && $parentPerm)
                    ? (bool) $parentPerm->can_view
                    : ($defaults['view'] ?? false);
                $canCreate = ($group === 'employee_kepegawaian' && $parentPerm)
                    ? (bool) $parentPerm->can_create
                    : ($defaults['create'] ?? false);
                $canUpdate = ($group === 'employee_kepegawaian' && $parentPerm)
                    ? (bool) $parentPerm->can_update
                    : ($defaults['update'] ?? false);
                $canDelete = ($group === 'employee_kepegawaian' && $parentPerm)
                    ? (bool) $parentPerm->can_delete
                    : ($defaults['delete'] ?? false);
                $canExport = ($group === 'employee_kepegawaian' && $parentPerm)
                    ? (bool) $parentPerm->can_export
                    : ($defaults['export'] ?? false);
                $canImport = ($group === 'employee_kepegawaian' && $parentPerm)
                    ? (bool) $parentPerm->can_import
                    : ($defaults['import'] ?? false);

                self::create([
                    'role' => $role,
                    'feature_group' => $group,
                    'feature_name' => $feat['name'],
                    'can_view' => $canView,
                    'can_create' => $canCreate,
                    'can_update' => $canUpdate,
                    'can_delete' => $canDelete,
                    'can_export' => $canExport,
                    'can_import' => $canImport,
                ]);
            }
        }
    }

    /**
     * Periksa apakah user atau role memiliki hak akses pada aksi fitur tertentu.
     */
    public static function canAccess(User|string|null $userOrRole, string $group, string $action = 'view'): bool
    {
        if (! $userOrRole) {
            return false;
        }

        $role = $userOrRole instanceof User ? (string) $userOrRole->role : (string) $userOrRole;
        $role = trim($role);

        if (in_array(strtolower($role), ['superadmin', 'admin'])) {
            return true;
        }

        // Petakan seluruh modul pegawai
        if (in_array($group, ['employee_directory', 'employee_detail_history', 'employee_documents'])) {
            $group = 'employee_detail_history';
        } elseif ($group === 'employee_kepegawaian') {
            $group = 'employee_kepegawaian';
        } else {
            // Modul selain pegawai belum dimasukkan ke matriks perizinan bertahap, izinkan secara default
            return true;
        }

        // Cari konfigurasi hak akses spesifik di database
        $perm = self::where('role', $role)
            ->where('feature_group', $group)
            ->first();

        // Jika tidak ditemukan dan role adalah variasi guru, cek role 'Guru'
        if (! $perm && (str_contains(strtolower($role), 'guru') || str_contains(strtolower($role), 'wali kelas'))) {
            $perm = self::where('role', 'Guru')
                ->where('feature_group', $group)
                ->first();
        }

        // Fallback untuk child employee_kepegawaian ke parent employee_detail_history jika belum diset
        if (! $perm && $group === 'employee_kepegawaian') {
            $parentPerm = self::where('role', $role)
                ->where('feature_group', 'employee_detail_history')
                ->first();
            if (! $parentPerm && (str_contains(strtolower($role), 'guru') || str_contains(strtolower($role), 'wali kelas'))) {
                $parentPerm = self::where('role', 'Guru')
                    ->where('feature_group', 'employee_detail_history')
                    ->first();
            }
            if ($parentPerm) {
                $perm = $parentPerm;
            }
        }

        $column = 'can_'.strtolower($action);

        if ($perm) {
            return (bool) ($perm->{$column} ?? false);
        }

        // Fallback ke nilai bawaan jika belum pernah disimpan
        $defaults = self::getDefaultValuesForRoleAndGroup($role, $group);

        return (bool) ($defaults[$action] ?? false);
    }

    /**
     * Nilai rekomendasi awal hak akses berdasarkan role dan fitur.
     *
     * @return array<string, bool>
     */
    protected static function getDefaultValuesForRoleAndGroup(string $role, string $group): array
    {
        if (in_array(strtolower($role), ['superadmin', 'admin'])) {
            return [
                'view' => true,
                'create' => true,
                'update' => true,
                'delete' => true,
                'export' => true,
                'import' => true,
            ];
        }

        if (in_array($role, ['Kepala Sekolah', 'Ketua Badan Pelaksana', 'Kepala Bidang SDM']) || str_contains(strtolower($role), 'kepala')) {
            return [
                'view' => true,
                'create' => true,
                'update' => true,
                'delete' => false,
                'export' => true,
                'import' => false,
            ];
        }

        if (in_array($role, ['Guru', 'Guru Kelas', 'Guru Mata Pelajaran', 'Wali Kelas']) || str_contains(strtolower($role), 'guru')) {
            return [
                'view' => true,
                'create' => false,
                'update' => true,
                'delete' => false,
                'export' => false,
                'import' => false,
            ];
        }

        return [
            'view' => true,
            'create' => false,
            'update' => false,
            'delete' => false,
            'export' => false,
            'import' => false,
        ];
    }
}
