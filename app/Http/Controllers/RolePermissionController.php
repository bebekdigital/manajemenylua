<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\RolePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RolePermissionController extends Controller
{
    /**
     * Dapatkan matriks hak akses untuk role tertentu.
     */
    public function getMatrix(Request $request): JsonResponse
    {
        $role = $request->query('role', 'superadmin');
        $matrix = RolePermission::getPermissionsMatrix($role);

        return response()->json([
            'role' => $role,
            'matrix' => $matrix,
        ]);
    }

    /**
     * Simpan pembaruan matriks hak akses untuk satu role atau banyak role sekaligus.
     */
    public function update(Request $request): RedirectResponse
    {
        if (auth()->user()?->role !== 'superadmin') {
            abort(403, 'Hanya Superadmin yang berhak mengatur hak akses.');
        }

        $validated = $request->validate([
            'role' => ['nullable', 'string', 'max:100'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'max:100'],
            'permissions' => ['required', 'array'],
            'permissions.*.group' => ['required', 'string'],
            'permissions.*.name' => ['nullable', 'string'],
            'permissions.*.data_scope' => ['nullable', 'string', 'in:self,unit,all'],
            'permissions.*.can_view' => ['nullable', 'boolean'],
            'permissions.*.can_create' => ['nullable', 'boolean'],
            'permissions.*.can_update' => ['nullable', 'boolean'],
            'permissions.*.can_delete' => ['nullable', 'boolean'],
            'permissions.*.can_export' => ['nullable', 'boolean'],
            'permissions.*.can_import' => ['nullable', 'boolean'],
        ]);

        $targetRoles = ! empty($validated['roles'])
            ? array_values(array_unique(array_filter($validated['roles'])))
            : (! empty($validated['role']) ? [$validated['role']] : []);

        if (empty($targetRoles)) {
            return back()->withErrors(['role' => 'Jabatan tujuan harus dipilih.']);
        }

        $hasDataScopeColumn = Schema::hasColumn('role_permissions', 'data_scope');

        DB::transaction(function () use ($targetRoles, $validated, $hasDataScopeColumn) {
            foreach ($targetRoles as $role) {
                foreach ($validated['permissions'] as $item) {
                    $attributes = [
                        'feature_name' => $item['name'] ?? $item['group'],
                        'can_view' => ! empty($item['can_view']),
                        'can_create' => ! empty($item['can_create']),
                        'can_update' => ! empty($item['can_update']),
                        'can_delete' => ! empty($item['can_delete']),
                        'can_export' => ! empty($item['can_export']),
                        'can_import' => ! empty($item['can_import']),
                    ];

                    if ($hasDataScopeColumn) {
                        $attributes['data_scope'] = $item['group'] === 'employee_detail_history'
                            ? RolePermission::normalizeDataScope($item['data_scope'] ?? null, $role)
                            : RolePermission::SCOPE_ALL;
                    }

                    RolePermission::updateOrCreate(
                        [
                            'role' => $role,
                            'feature_group' => $item['group'],
                        ],
                        $attributes
                    );
                }
            }
        });

        Department::forgetOrganizationCaches();

        $count = count($targetRoles);
        $message = $count > 1
            ? "Hak akses untuk {$count} jabatan berhasil disimpan sekaligus."
            : "Hak akses untuk role \"{$targetRoles[0]}\" berhasil disimpan.";

        return back()->with('success', $message);
    }
}
