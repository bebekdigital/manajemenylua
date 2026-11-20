<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentPosition;
use App\Models\EmployeePosition;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DepartmentPositionController extends Controller
{
    /**
     * Store a newly created position directly (from Diagram Susunan Organisasi).
     */
    public function storeDirect(Request $request): RedirectResponse
    {
        if (! RolePermission::canAccess($request->user(), 'setting_organization', 'create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambah jabatan organisasi.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'level' => ['required', 'integer', 'min:1', 'max:5'],
            'parent_id' => ['nullable', 'exists:department_positions,id'],
            'order' => ['nullable', 'integer'],
        ]);

        $name = trim((string) $validated['name']);

        $query = DepartmentPosition::where('name', $name);
        if (! empty($validated['department_id'])) {
            $query->where('department_id', $validated['department_id']);
        }
        if ($query->exists()) {
            return back()->with('error', 'Jabatan dengan nama ini sudah terdaftar.');
        }

        DepartmentPosition::create([
            'name' => $name,
            'department_id' => $validated['department_id'] ?? null,
            'level' => (int) $validated['level'],
            'parent_id' => $validated['parent_id'] ?? null,
            'order' => $validated['order'] ?? 0,
        ]);

        return back()->with('success', 'Jabatan berhasil ditambahkan ke susunan organisasi.');
    }

    /**
     * Store a newly created position in a department.
     */
    public function store(Request $request, Department $department): RedirectResponse
    {
        if (! RolePermission::canAccess($request->user(), 'setting_organization', 'create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambah jabatan ke bidang.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'level' => ['nullable', 'integer', 'min:1', 'max:5'],
            'parent_id' => ['nullable', 'exists:department_positions,id'],
            'order' => ['nullable', 'integer'],
        ]);

        $name = trim((string) $validated['name']);

        $exists = $department->positions()->where('name', $name)->exists();
        if ($exists) {
            return back()->with('error', 'Jabatan sudah terdaftar di bidang ini.');
        }

        $department->positions()->create([
            'name' => $name,
            'level' => (int) ($validated['level'] ?? 3),
            'parent_id' => $validated['parent_id'] ?? null,
            'order' => $validated['order'] ?? 0,
        ]);

        return back()->with('success', 'Jabatan berhasil ditambahkan ke bidang.');
    }

    /**
     * Update the specified position.
     */
    public function update(Request $request, DepartmentPosition $departmentPosition): RedirectResponse
    {
        if (! RolePermission::canAccess($request->user(), 'setting_organization', 'update')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah jabatan organisasi.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'level' => ['nullable', 'integer', 'min:1', 'max:5'],
            'parent_id' => ['nullable', 'exists:department_positions,id'],
            'order' => ['nullable', 'integer'],
        ]);

        if (! empty($validated['parent_id'])) {
            if ((int) $validated['parent_id'] === $departmentPosition->id) {
                return back()->with('error', 'Jabatan tidak dapat menjadi atasan dari dirinya sendiri.');
            }

            if ($this->isDescendant($departmentPosition, (int) $validated['parent_id'])) {
                return back()->with('error', 'Atasan tidak boleh dipilih dari bawahan jabatan ini (terjadi siklus hierarki).');
            }
        }

        $targetDeptId = array_key_exists('department_id', $validated)
            ? $validated['department_id']
            : $departmentPosition->department_id;

        $existsQuery = DepartmentPosition::where('name', $validated['name'])
            ->where('id', '!=', $departmentPosition->id);

        if ($targetDeptId) {
            $existsQuery->where('department_id', $targetDeptId);
        }

        if ($existsQuery->exists()) {
            return back()->with('error', 'Jabatan dengan nama ini sudah terdaftar.');
        }

        $departmentPosition->loadMissing('department');
        $oldName = trim((string) $departmentPosition->name);
        $oldDeptName = $departmentPosition->department?->name
            ? trim((string) $departmentPosition->department->name)
            : null;
        $newName = trim((string) $validated['name']);
        $newDeptName = ! empty($targetDeptId)
            ? trim((string) (Department::find($targetDeptId)?->name ?? ''))
            : null;

        $data = [
            'name' => $newName,
        ];

        if (array_key_exists('department_id', $validated)) {
            $data['department_id'] = $validated['department_id'];
        }
        if (isset($validated['level'])) {
            $data['level'] = (int) $validated['level'];
        }
        if (array_key_exists('parent_id', $validated)) {
            $data['parent_id'] = $validated['parent_id'];
        }
        if (isset($validated['order'])) {
            $data['order'] = (int) $validated['order'];
        }

        DB::transaction(function () use ($departmentPosition, $data, $oldName, $newName, $oldDeptName, $newDeptName): void {
            $departmentPosition->update($data);

            $nameChanged = $oldName !== '' && $oldName !== $newName;
            $deptChanged = ($oldDeptName ?? '') !== ($newDeptName ?? '') && $newDeptName !== null && $newDeptName !== '';

            if (! $nameChanged && ! $deptChanged) {
                return;
            }

            if ($oldName !== '' && Schema::hasTable('employee_positions')) {
                $empPosUpdates = [];
                if ($nameChanged) {
                    $empPosUpdates['jabatan'] = $newName;
                }
                if ($deptChanged) {
                    $empPosUpdates['unit_kerja'] = $newDeptName;
                }

                $empPosQuery = EmployeePosition::whereRaw('LOWER(TRIM(jabatan)) = ?', [mb_strtolower($oldName)]);
                if ($oldDeptName !== null && $oldDeptName !== '') {
                    $empPosQuery->whereRaw('LOWER(TRIM(unit_kerja)) = ?', [mb_strtolower($oldDeptName)]);
                }
                $empPosQuery->update($empPosUpdates);
            }

            if ($oldName !== '' && Schema::hasTable('users')) {
                $userUpdates = [];
                if ($nameChanged) {
                    $userUpdates['jabatan'] = $newName;
                }
                if ($deptChanged) {
                    $userUpdates['unit'] = $newDeptName;
                }

                $userQuery = User::whereRaw('LOWER(TRIM(jabatan)) = ?', [mb_strtolower($oldName)]);
                if ($oldDeptName !== null && $oldDeptName !== '') {
                    $userQuery->whereRaw('LOWER(TRIM(unit)) = ?', [mb_strtolower($oldDeptName)]);
                }
                $userQuery->update($userUpdates);

                if ($nameChanged) {
                    $roleQuery = User::where('role', '!=', 'superadmin')
                        ->where(function ($query) use ($oldName, $newName): void {
                            $query->whereRaw('LOWER(TRIM(role)) = ?', [mb_strtolower($oldName)])
                                ->orWhereRaw('LOWER(TRIM(jabatan)) = ?', [mb_strtolower($newName)]);
                        });
                    if ($oldDeptName !== null && $oldDeptName !== '') {
                        $roleQuery->where(function ($query) use ($oldDeptName, $newDeptName): void {
                            $query->whereRaw('LOWER(TRIM(unit)) = ?', [mb_strtolower($oldDeptName)]);
                            if ($newDeptName !== null && $newDeptName !== '') {
                                $query->orWhereRaw('LOWER(TRIM(unit)) = ?', [mb_strtolower($newDeptName)]);
                            }
                        });
                    }
                    $roleQuery->update(['role' => $newName]);
                }
            }

            if (
                $nameChanged
                && $oldName !== ''
                && Schema::hasTable('role_permissions')
                && ! DepartmentPosition::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($oldName)])->exists()
            ) {
                RolePermission::whereRaw('LOWER(TRIM(role)) = ?', [mb_strtolower($oldName)])
                    ->update(['role' => $newName]);
            }
        });

        Department::forgetOrganizationCaches();

        return back()->with('success', 'Jabatan berhasil diperbarui.');
    }

    /**
     * Remove the specified position.
     */
    public function destroy(DepartmentPosition $departmentPosition): RedirectResponse
    {
        if (! RolePermission::canAccess(request()->user(), 'setting_organization', 'delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus jabatan organisasi.');
        }

        // Re-parent direct children so hierarchy is maintained
        DepartmentPosition::where('parent_id', $departmentPosition->id)
            ->update(['parent_id' => $departmentPosition->parent_id]);

        $departmentPosition->delete();

        return back()->with('success', 'Jabatan berhasil dihapus dari susunan organisasi.');
    }

    /**
     * Check if candidate ID is descendant of position.
     */
    protected function isDescendant(DepartmentPosition $position, int $candidateId): bool
    {
        $childrenIds = DepartmentPosition::where('parent_id', $position->id)->pluck('id')->all();
        if (in_array($candidateId, $childrenIds, true)) {
            return true;
        }

        foreach ($childrenIds as $childId) {
            $child = DepartmentPosition::find($childId);
            if ($child && $this->isDescendant($child, $candidateId)) {
                return true;
            }
        }

        return false;
    }
}
