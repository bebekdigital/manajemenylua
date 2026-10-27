<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentPosition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DepartmentPositionController extends Controller
{
    /**
     * Store a newly created position directly (from Diagram Susunan Organisasi).
     */
    public function storeDirect(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'level' => ['required', 'integer', 'min:1', 'max:5'],
            'parent_id' => ['nullable', 'exists:department_positions,id'],
            'order' => ['nullable', 'integer'],
        ]);

        $query = DepartmentPosition::where('name', $validated['name']);
        if (! empty($validated['department_id'])) {
            $query->where('department_id', $validated['department_id']);
        }
        if ($query->exists()) {
            return back()->with('error', 'Jabatan dengan nama ini sudah terdaftar.');
        }

        DepartmentPosition::create([
            'name' => $validated['name'],
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'level' => ['nullable', 'integer', 'min:1', 'max:5'],
            'parent_id' => ['nullable', 'exists:department_positions,id'],
            'order' => ['nullable', 'integer'],
        ]);

        $exists = $department->positions()->where('name', $validated['name'])->exists();
        if ($exists) {
            return back()->with('error', 'Jabatan sudah terdaftar di bidang ini.');
        }

        $department->positions()->create([
            'name' => $validated['name'],
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

        $data = [
            'name' => $validated['name'],
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

        $departmentPosition->update($data);

        return back()->with('success', 'Jabatan berhasil diperbarui.');
    }

    /**
     * Remove the specified position.
     */
    public function destroy(DepartmentPosition $departmentPosition): RedirectResponse
    {
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
