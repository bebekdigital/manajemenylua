<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
     * Simpan pembaruan matriks hak akses untuk role tertentu.
     */
    public function update(Request $request): RedirectResponse
    {
        if (auth()->user()?->role !== 'superadmin') {
            abort(403, 'Hanya Superadmin yang berhak mengatur hak akses.');
        }

        $validated = $request->validate([
            'role' => ['required', 'string', 'max:100'],
            'permissions' => ['required', 'array'],
            'permissions.*.group' => ['required', 'string'],
            'permissions.*.name' => ['nullable', 'string'],
            'permissions.*.can_view' => ['nullable', 'boolean'],
            'permissions.*.can_create' => ['nullable', 'boolean'],
            'permissions.*.can_update' => ['nullable', 'boolean'],
            'permissions.*.can_delete' => ['nullable', 'boolean'],
            'permissions.*.can_export' => ['nullable', 'boolean'],
            'permissions.*.can_import' => ['nullable', 'boolean'],
        ]);

        $role = $validated['role'];

        DB::transaction(function () use ($role, $validated) {
            foreach ($validated['permissions'] as $item) {
                RolePermission::updateOrCreate(
                    [
                        'role' => $role,
                        'feature_group' => $item['group'],
                    ],
                    [
                        'feature_name' => $item['name'] ?? $item['group'],
                        'can_view' => ! empty($item['can_view']),
                        'can_create' => ! empty($item['can_create']),
                        'can_update' => ! empty($item['can_update']),
                        'can_delete' => ! empty($item['can_delete']),
                        'can_export' => ! empty($item['can_export']),
                        'can_import' => ! empty($item['can_import']),
                    ]
                );
            }
        });

        return back()->with('success', "Hak akses untuk role \"{$role}\" berhasil disimpan.");
    }
}
