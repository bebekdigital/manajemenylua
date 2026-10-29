<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\DepartmentPosition;
use App\Models\Employee;
use App\Models\OrganizationLevel;
use App\Models\RolePermission;
use App\Models\SchoolLevel;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class PortalController extends Controller
{
    /**
     * Display the Portal Management (Pengaturan) page.
     */
    public function index(): Response
    {
        $academicYears = AcademicYear::orderByDesc('name')
            ->orderByRaw("CASE WHEN semester = 'Genap' THEN 1 ELSE 2 END")
            ->get()
            ->map(fn (AcademicYear $year) => [
                'id' => $year->id,
                'name' => $year->name,
                'semester' => $year->semester,
                'start_date' => $year->start_date?->format('Y-m-d'),
                'end_date' => $year->end_date?->format('Y-m-d'),
                'is_active' => (bool) $year->is_active,
                'students_count' => $year->studentRecords()->count(),
                'classrooms_count' => $year->classrooms()->count(),
            ]);

        $users = User::orderBy('name')->get()->map(fn ($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'created_at' => $user->created_at?->format('Y-m-d H:i'),
        ]);

        $schoolLevels = SchoolLevel::with(['units.programs', 'units.classes'])->orderBy('order')->get();

        $departments = Schema::hasTable('departments')
            ? Department::with('positions')->orderBy('order')->orderBy('name')->get()
            : collect([]);

        $organizationPositions = Schema::hasTable('department_positions')
            ? DepartmentPosition::with(['department', 'parent'])
                ->withCount('children')
                ->orderBy('level')
                ->orderBy('order')
                ->orderBy('name')
                ->get()
                ->map(fn (DepartmentPosition $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'level' => $p->level ?? 3,
                    'order' => $p->order ?? 0,
                    'department_id' => $p->department_id,
                    'department_name' => $p->department?->name,
                    'parent_id' => $p->parent_id,
                    'parent_name' => $p->parent?->name,
                    'children_count' => $p->children_count,
                ])
            : collect([]);

        $organizationLevels = Schema::hasTable('organization_levels')
            ? OrganizationLevel::orderBy('level')->get()
            : collect([]);

        $distinctPositions = Schema::hasTable('department_positions')
            ? DepartmentPosition::distinct()->pluck('name')->filter()->values()->toArray()
            : [];
        sort($distinctPositions);
        $availableRoles = array_values(array_unique(array_merge(['superadmin'], $distinctPositions)));
        $primaryRoles = ['superadmin', 'Kepala Bidang SDM', 'Guru'];

        $employees = Schema::hasTable('employees')
            ? Employee::with(['currentPosition', 'positions'])
                ->orderBy('nama')
                ->get()
                ->map(function (Employee $emp) use ($users) {
                    $latestPos = $emp->currentPosition ?? $emp->positions->sortByDesc('tmt_jabatan')->first();
                    $hasAccount = $users->contains(function ($u) use ($emp) {
                        return ($emp->nipy && ! empty($u['username']) && $u['username'] === $emp->nipy)
                            || strcasecmp($u['name'], $emp->nama) === 0
                            || ($emp->nama_lengkap_gelar && strcasecmp($u['name'], $emp->nama_lengkap_gelar) === 0);
                    });

                    return [
                        'id' => $emp->id,
                        'nama' => $emp->nama,
                        'nama_lengkap_gelar' => $emp->nama,
                        'nipy' => $emp->nipy,
                        'tanggal_lahir' => $emp->tanggal_lahir?->format('Y-m-d'),
                        'tanggal_lahir_formatted' => $emp->tanggal_lahir?->translatedFormat('d F Y') ?? '-',
                        'default_password' => $emp->tanggal_lahir ? $emp->tanggal_lahir->format('Ymd') : '12345678',
                        'jabatan' => $latestPos?->jabatan ?: 'Guru',
                        'unit_kerja' => $latestPos?->unit_kerja ?: '-',
                        'has_account' => $hasAccount,
                    ];
                })
            : collect([]);

        $defaultFeatures = RolePermission::defaultFeatures();
        $permissionsData = [];

        foreach ($availableRoles as $r) {
            $permissionsData[$r] = Schema::hasTable('role_permissions')
                ? RolePermission::getPermissionsMatrix($r)
                : array_map(function ($feat) use ($r) {
                    $isSuper = ($r === 'superadmin');

                    return [
                        'group' => $feat['group'],
                        'name' => $feat['name'],
                        'category' => $feat['category'],
                        'description' => $feat['description'],
                        'supported_actions' => $feat['supported_actions'],
                        'can_view' => $isSuper,
                        'can_create' => $isSuper,
                        'can_update' => $isSuper,
                        'can_delete' => $isSuper,
                        'can_export' => $isSuper,
                        'can_import' => $isSuper,
                    ];
                }, $defaultFeatures);
        }

        return Inertia::render('Portal', [
            'academicYears' => $academicYears,
            'users' => $users,
            'schoolLevels' => $schoolLevels,
            'departments' => $departments,
            'allSchoolPositions' => [],
            'organizationPositions' => $organizationPositions,
            'organizationLevels' => $organizationLevels,
            'availableRoles' => $availableRoles,
            'schoolRoles' => [],
            'deptRoles' => [],
            'employees' => $employees,
            'permissionsData' => $permissionsData,
            'defaultFeatures' => $defaultFeatures,
            'primaryRoles' => $primaryRoles,
        ]);
    }
}
