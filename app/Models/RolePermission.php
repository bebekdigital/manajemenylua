<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class RolePermission extends Model
{
    use HasFactory;

    public const SCOPE_SELF = 'self';

    public const SCOPE_UNIT = 'unit';

    public const SCOPE_ALL = 'all';

    protected $fillable = [
        'role',
        'feature_group',
        'feature_name',
        'data_scope',
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
            // Kategori: Database Siswa
            [
                'group' => 'student_data',
                'name' => 'Data Siswa',
                'category' => 'Database Siswa',
                'description' => 'Akses modul data siswa (direktori siswa, rincian profil, edit massal, template dan impor).',
                'parent_group' => null,
                'supported_actions' => ['view', 'create', 'update', 'delete', 'export', 'import'],
            ],
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
                'description' => 'Akses rincian data kepegawaian: Lihat (visibilitas tab & NIPY) • Tambah (tambah riwayat SK jenjang) • Ubah (edit data kepegawaian & SK) • Hapus (hapus riwayat SK). Kolom Ekspor dan Impor terpusat di modul Data Pegawai.',
                'parent_group' => 'employee_detail_history',
                'supported_actions' => ['view', 'create', 'update', 'delete'],
            ],
            // Kategori: Database Pegawai (Child: Data Penugasan)
            [
                'group' => 'employee_positions',
                'name' => 'Data Penugasan',
                'category' => 'Database Pegawai',
                'description' => 'Akses rincian penugasan & jabatan: Lihat (visibilitas Tab Penugasan) • Tambah (tambah riwayat jabatan baru) • Ubah (edit riwayat penugasan) • Hapus (hapus riwayat penugasan). Kolom Ekspor dan Impor terpusat di modul Data Pegawai.',
                'parent_group' => 'employee_detail_history',
                'supported_actions' => ['view', 'create', 'update', 'delete'],
            ],
            // Kategori: Database Pegawai (Child: Dokumen Kepegawaian)
            [
                'group' => 'employee_documents',
                'name' => 'Dokumen Kepegawaian',
                'category' => 'Database Pegawai',
                'description' => 'Akses berkas: Lihat (pratinjau) • Tambah (unggah berkas baru) • Ubah (ganti/unggah ulang) • Hapus (hapus berkas) • Ekspor (unduh berkas fisik). Kolom Impor dinonaktifkan.',
                'parent_group' => 'employee_detail_history',
                'supported_actions' => ['view', 'create', 'update', 'delete', 'export'],
            ],
            // Kategori: Pengaturan Master (Tab Akademik)
            [
                'group' => 'setting_academic',
                'name' => 'Tab Akademik',
                'category' => 'Pengaturan Master',
                'description' => 'Akses Tab Akademik di menu Pengaturan: Lihat (visibilitas tab, tahun ajaran, tingkat, unit, rombel) • Tambah (buat tahun ajaran, unit, kelas) • Ubah (edit data akademik & status aktif) • Hapus (hapus TA/tingkat/rombel). Kolom Ekspor dan Impor dinonaktifkan.',
                'parent_group' => null,
                'supported_actions' => ['view', 'create', 'update', 'delete'],
            ],
            // Kategori: Pengaturan Master (Tab Organisasi)
            [
                'group' => 'setting_organization',
                'name' => 'Tab Organisasi',
                'category' => 'Pengaturan Master',
                'description' => 'Akses Tab Organisasi di menu Pengaturan: Lihat (visibilitas tab & bagan struktur hierarki) • Tambah (tambah unit/bidang & posisi jabatan baru) • Ubah (edit nama, atasan, dan level) • Hapus (hapus jabatan atau bidang). Kolom Ekspor dan Impor dinonaktifkan.',
                'parent_group' => null,
                'supported_actions' => ['view', 'create', 'update', 'delete'],
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
                'data_scope' => $group === 'employee_detail_history'
                    ? ($isSuper ? self::SCOPE_ALL : self::normalizeDataScope($perm?->data_scope, $role))
                    : self::SCOPE_ALL,
                'can_view' => $isSuper || (bool) ($perm?->can_view),
                'can_create' => $isSuper || (bool) ($perm?->can_create),
                'can_update' => $isSuper || (bool) ($perm?->can_update),
                'can_delete' => $isSuper || (bool) ($perm?->can_delete),
                'can_export' => in_array('export', $feat['supported_actions']) && ($isSuper || (bool) ($perm?->can_export)),
                'can_import' => in_array('import', $feat['supported_actions']) && ($isSuper || (bool) ($perm?->can_import)),
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

                $isSDMOrAdmin = in_array(strtolower($role), ['superadmin', 'admin', 'kepala bidang sdm'])
                    || str_contains(strtolower($role), 'sdm')
                    || str_contains(strtolower($role), 'kepegawaian');

                if ($group === 'employee_kepegawaian' || $group === 'employee_positions') {
                    $canView = $parentPerm ? (bool) $parentPerm->can_view : ($defaults['view'] ?? false);
                    $canCreate = $isSDMOrAdmin ? ($defaults['create'] ?? false) : false;
                    $canUpdate = $isSDMOrAdmin ? ($defaults['update'] ?? false) : false;
                    $canDelete = $isSDMOrAdmin ? ($defaults['delete'] ?? false) : false;
                    $canExport = false;
                    $canImport = false;
                } elseif ($group === 'employee_documents') {
                    $canView = $parentPerm ? (bool) $parentPerm->can_view : ($defaults['view'] ?? false);
                    $canCreate = $defaults['create'] ?? false;
                    $canUpdate = $defaults['update'] ?? false;
                    $canDelete = $defaults['delete'] ?? false;
                    $canExport = $defaults['export'] ?? false;
                    $canImport = $defaults['import'] ?? false;
                } else {
                    $canView = $defaults['view'] ?? false;
                    $canCreate = $defaults['create'] ?? false;
                    $canUpdate = $defaults['update'] ?? false;
                    $canDelete = $defaults['delete'] ?? false;
                    $canExport = in_array('export', $feat['supported_actions']) ? ($defaults['export'] ?? false) : false;
                    $canImport = in_array('import', $feat['supported_actions']) ? ($defaults['import'] ?? false) : false;
                }

                self::create([
                    'role' => $role,
                    'feature_group' => $group,
                    'feature_name' => $feat['name'],
                    ...(Schema::hasColumn('role_permissions', 'data_scope') ? [
                        'data_scope' => $group === 'employee_detail_history'
                            ? self::getDefaultDataScopeForRole($role)
                            : self::SCOPE_ALL,
                    ] : []),
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

        // Petakan seluruh modul yang dikelola
        if (in_array($group, ['student', 'students', 'student_data'])) {
            $group = 'student_data';
        } elseif (in_array($group, ['employee_directory', 'employee_detail_history'])) {
            $group = 'employee_detail_history';
        } elseif ($group === 'employee_kepegawaian') {
            $group = 'employee_kepegawaian';
        } elseif ($group === 'employee_positions') {
            $group = 'employee_positions';
        } elseif ($group === 'employee_documents') {
            $group = 'employee_documents';
        } elseif (in_array($group, ['academic', 'setting_academic', 'portal_academic'])) {
            $group = 'setting_academic';
        } elseif (in_array($group, ['organization', 'setting_organization', 'portal_organization', 'unit_bidang'])) {
            $group = 'setting_organization';
        } else {
            // Modul selain yang ada di matriks perizinan bertahap, izinkan secara default
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

        // Fallback untuk child (employee_kepegawaian, employee_positions, & employee_documents) jika belum pernah diset di database
        if (! $perm && ($group === 'employee_kepegawaian' || $group === 'employee_positions' || $group === 'employee_documents')) {
            if ($action === 'view') {
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
            } else {
                $defaults = self::getDefaultValuesForRoleAndGroup($role, $group);

                return (bool) ($defaults[$action] ?? false);
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
     * Normalize any scope value into one of self | unit | all.
     */
    public static function normalizeDataScope(?string $scope, string $role = ''): string
    {
        if (in_array($scope, [self::SCOPE_SELF, self::SCOPE_UNIT, self::SCOPE_ALL], true)) {
            return $scope;
        }

        return self::getDefaultDataScopeForRole($role);
    }

    /**
     * Get the default employee data visibility scope for a given role.
     */
    public static function getDefaultDataScopeForRole(string $role): string
    {
        $lowerRole = strtolower(trim($role));

        if (in_array($lowerRole, ['superadmin', 'admin'], true)) {
            return self::SCOPE_ALL;
        }

        if (str_contains($lowerRole, 'guru') || str_contains($lowerRole, 'wali kelas')) {
            return self::SCOPE_SELF;
        }

        return self::SCOPE_ALL;
    }

    /**
     * Resolve the effective Data Pegawai visibility scope (self | unit | all) for a user or role.
     */
    public static function getEmployeeDataScope(User|string|null $userOrRole): string
    {
        if (! $userOrRole) {
            return self::SCOPE_SELF;
        }

        $role = trim($userOrRole instanceof User ? (string) $userOrRole->role : (string) $userOrRole);

        if (in_array(strtolower($role), ['superadmin', 'admin'], true)) {
            return self::SCOPE_ALL;
        }

        if (! Schema::hasTable('role_permissions') || ! Schema::hasColumn('role_permissions', 'data_scope')) {
            return self::getDefaultDataScopeForRole($role);
        }

        $perm = self::where('role', $role)
            ->where('feature_group', 'employee_detail_history')
            ->first();

        if (! $perm && (str_contains(strtolower($role), 'guru') || str_contains(strtolower($role), 'wali kelas'))) {
            $perm = self::where('role', 'Guru')
                ->where('feature_group', 'employee_detail_history')
                ->first();
        }

        if ($perm && ! empty($perm->data_scope)) {
            return self::normalizeDataScope($perm->data_scope, $role);
        }

        return self::getDefaultDataScopeForRole($role);
    }

    /**
     * Determine whether a user can view/access a specific employee under the configured scope hierarchy.
     * Hierarchy is cumulative: all > unit > self.
     */
    public static function canAccessEmployee(User $user, Employee $employee, ?string $scope = null, ?Employee $myEmployee = null, ?array $userUnits = null): bool
    {
        $effectiveScope = $scope ?? self::getEmployeeDataScope($user);

        if ($effectiveScope === self::SCOPE_ALL) {
            return true;
        }

        $ownEmployee = $myEmployee ?? $user->employeeProfile();
        if ($ownEmployee && (int) $ownEmployee->id === (int) $employee->id) {
            return true;
        }

        if ($effectiveScope !== self::SCOPE_UNIT) {
            return false;
        }

        $units = $userUnits ?? $user->employeeUnits();
        if ($units === []) {
            return false;
        }

        $positions = $employee->relationLoaded('positions')
            ? $employee->positions
            : $employee->positions()->get();

        $activePositions = $positions->filter(fn (EmployeePosition $pos): bool => empty($pos->tst_jabatan));
        $sourcePositions = $activePositions->isNotEmpty()
            ? $activePositions
            : $positions->sortByDesc('tmt_jabatan')->take(1);

        foreach ($sourcePositions as $pos) {
            $targetUnit = mb_strtolower(trim((string) ($pos->unit_kerja ?? '')));
            if ($targetUnit !== '' && in_array($targetUnit, $units, true)) {
                return true;
            }
        }

        return false;
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

        $lowerRole = strtolower($role);

        $isSDM = in_array($lowerRole, ['kepala bidang sdm'])
            || str_contains($lowerRole, 'sdm')
            || str_contains($lowerRole, 'kepegawaian');

        $isLeadership = str_contains($lowerRole, 'kepala')
            || str_contains($lowerRole, 'ketua')
            || str_contains($lowerRole, 'mudir')
            || str_contains($lowerRole, 'yayasan')
            || str_contains($lowerRole, 'pembina')
            || str_contains($lowerRole, 'pengawas');

        $isAcademicStaff = str_contains($lowerRole, 'kurikulum')
            || str_contains($lowerRole, 'pendidikan')
            || str_contains($lowerRole, 'akademik')
            || str_contains($lowerRole, 'tata usaha')
            || str_contains($lowerRole, 'tu');

        $isTeacher = in_array($role, ['Guru', 'Guru Kelas', 'Guru Mata Pelajaran', 'Wali Kelas'])
            || str_contains($lowerRole, 'guru')
            || str_contains($lowerRole, 'wali kelas');

        // Modul Data Siswa (Kategori: Database Siswa)
        if ($group === 'student_data') {
            if ($isLeadership || $isAcademicStaff) {
                return [
                    'view' => true,
                    'create' => true,
                    'update' => true,
                    'delete' => false,
                    'export' => true,
                    'import' => true,
                ];
            }

            if ($isTeacher) {
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

        // Modul Tab Akademik (Kategori: Pengaturan Master)
        if ($group === 'setting_academic') {
            if (str_contains($lowerRole, 'ketua') || str_contains($lowerRole, 'yayasan') || str_contains($lowerRole, 'pembina')) {
                return [
                    'view' => true,
                    'create' => true,
                    'update' => true,
                    'delete' => true,
                    'export' => false,
                    'import' => false,
                ];
            }

            if (str_contains($lowerRole, 'kepala') || str_contains($lowerRole, 'mudir') || str_contains($lowerRole, 'kurikulum') || str_contains($lowerRole, 'pendidikan')) {
                return [
                    'view' => true,
                    'create' => true,
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

        // Modul Tab Organisasi (Kategori: Pengaturan Master)
        if ($group === 'setting_organization') {
            if ($isSDM || str_contains($lowerRole, 'ketua') || str_contains($lowerRole, 'yayasan') || str_contains($lowerRole, 'pembina')) {
                return [
                    'view' => true,
                    'create' => true,
                    'update' => true,
                    'delete' => true,
                    'export' => false,
                    'import' => false,
                ];
            }

            if (str_contains($lowerRole, 'kepala') || str_contains($lowerRole, 'mudir')) {
                return [
                    'view' => true,
                    'create' => false,
                    'update' => false,
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

        // Modul Data Kepegawaian (NIPY, status keaktifan, SK jenjang)
        if ($group === 'employee_kepegawaian') {
            return [
                'view' => true,
                'create' => $isSDM,
                'update' => $isSDM,
                'delete' => $isSDM,
                'export' => false,
                'import' => false,
            ];
        }

        // Modul Data Penugasan (Riwayat Jabatan & Penugasan)
        if ($group === 'employee_positions') {
            return [
                'view' => true,
                'create' => $isSDM,
                'update' => $isSDM,
                'delete' => $isSDM,
                'export' => false,
                'import' => false,
            ];
        }

        // Modul Dokumen Kepegawaian (KTP, KK, SK, Ijazah, Sertifikat)
        if ($group === 'employee_documents') {
            if ($isSDM || str_contains(strtolower($role), 'kepala')) {
                return [
                    'view' => true,
                    'create' => true,
                    'update' => true,
                    'delete' => true,
                    'export' => true,
                    'import' => false,
                ];
            }

            if (in_array($role, ['Guru', 'Guru Kelas', 'Guru Mata Pelajaran', 'Wali Kelas']) || str_contains(strtolower($role), 'guru')) {
                return [
                    'view' => true,
                    'create' => true,
                    'update' => true,
                    'delete' => true,
                    'export' => true,
                    'import' => false,
                ];
            }

            return [
                'view' => true,
                'create' => false,
                'update' => false,
                'delete' => false,
                'export' => true,
                'import' => false,
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
