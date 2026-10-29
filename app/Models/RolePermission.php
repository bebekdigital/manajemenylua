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
            // Kategori: Database Pegawai
            [
                'group' => 'employee_detail_history',
                'name' => 'Detail Data Pegawai',
                'category' => 'Database Pegawai',
                'description' => 'Melihat, menambah, mengubah, dan menghapus rincian data pegawai (biodata, riwayat penugasan, dan dokumen pegawai).',
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

        // Petakan seluruh modul pegawai ke 'employee_detail_history'
        if (in_array($group, ['employee_directory', 'employee_detail_history', 'employee_documents'])) {
            $group = 'employee_detail_history';
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

        if ($role === 'Kepala Bidang SDM') {
            return [
                'view' => true,
                'create' => true,
                'update' => true,
                'delete' => true,
                'export' => true,
                'import' => true,
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
            'view' => false,
            'create' => false,
            'update' => false,
            'delete' => false,
            'export' => false,
            'import' => false,
        ];
    }
}
