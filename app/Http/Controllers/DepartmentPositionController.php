<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentPosition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DepartmentPositionController extends Controller
{
    /**
     * Store a newly created position in a department.
     */
    public function store(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $exists = $department->positions()->where('name', $validated['name'])->exists();
        if ($exists) {
            return back()->with('error', 'Jabatan sudah terdaftar di bidang ini.');
        }

        $department->positions()->create([
            'name' => $validated['name'],
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
        ]);

        $exists = $departmentPosition->department->positions()
            ->where('name', $validated['name'])
            ->where('id', '!=', $departmentPosition->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Jabatan sudah terdaftar di bidang ini.');
        }

        $departmentPosition->update([
            'name' => $validated['name'],
        ]);

        return back()->with('success', 'Jabatan berhasil diperbarui.');
    }

    /**
     * Remove the specified position.
     */
    public function destroy(DepartmentPosition $departmentPosition): RedirectResponse
    {
        $departmentPosition->delete();

        return back()->with('success', 'Jabatan berhasil dihapus.');
    }
}
