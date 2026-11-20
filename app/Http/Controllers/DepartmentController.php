<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\EmployeePosition;
use App\Models\OrganizationLevel;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DepartmentController extends Controller
{
    /**
     * Store a newly created department / unit.
     */
    public function store(Request $request): RedirectResponse
    {
        if (! RolePermission::canAccess($request->user(), 'setting_organization', 'create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambah unit atau bidang organisasi.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'unit_group' => ['nullable', 'string', 'in:Yayasan,Satuan Pendidikan'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        Department::create([
            'name' => trim((string) $validated['name']),
            'unit_group' => $validated['unit_group'] ?? 'Yayasan',
            'order' => $validated['order'] ?? 0,
        ]);

        return back()->with('success', 'Unit berhasil ditambahkan.');
    }

    /**
     * Update the specified department / unit.
     */
    public function update(Request $request, Department $department): RedirectResponse
    {
        if (! RolePermission::canAccess($request->user(), 'setting_organization', 'update')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah unit atau bidang organisasi.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name,'.$department->id],
            'unit_group' => ['nullable', 'string', 'in:Yayasan,Satuan Pendidikan'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $oldName = trim((string) $department->name);
        $newName = trim((string) $validated['name']);

        DB::transaction(function () use ($department, $validated, $oldName, $newName): void {
            $department->update([
                'name' => $newName,
                'unit_group' => $validated['unit_group'] ?? ($department->unit_group ?? 'Yayasan'),
                'order' => $validated['order'] ?? $department->order,
            ]);

            if ($oldName === '' || $oldName === $newName) {
                return;
            }

            if (Schema::hasTable('employee_positions')) {
                EmployeePosition::whereRaw('LOWER(TRIM(unit_kerja)) = ?', [mb_strtolower($oldName)])
                    ->update(['unit_kerja' => $newName]);
            }

            if (Schema::hasTable('users')) {
                User::whereRaw('LOWER(TRIM(unit)) = ?', [mb_strtolower($oldName)])
                    ->update(['unit' => $newName]);
            }
        });

        Department::forgetOrganizationCaches();

        return back()->with('success', 'Unit berhasil diperbarui.');
    }

    /**
     * Remove the specified department / unit.
     */
    public function destroy(Department $department): RedirectResponse
    {
        if (! RolePermission::canAccess(request()->user(), 'setting_organization', 'delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus unit atau bidang organisasi.');
        }

        if ($department->positions()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus Unit karena masih memiliki daftar Jabatan.');
        }

        $department->delete();

        return back()->with('success', 'Unit berhasil dihapus.');
    }

    /**
     * Update custom organization level name and subtitle.
     */
    public function updateOrganizationLevel(Request $request, int $level): RedirectResponse
    {
        if (! RolePermission::canAccess($request->user(), 'setting_organization', 'update')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah informasi level hierarki.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
        ]);

        if (Schema::hasTable('organization_levels')) {
            OrganizationLevel::updateOrCreate(
                ['level' => $level],
                [
                    'name' => $validated['name'],
                    'subtitle' => $validated['subtitle'] ?? null,
                ]
            );
        }

        return back()->with('success', "Nama Level {$level} berhasil diperbarui.");
    }
}
