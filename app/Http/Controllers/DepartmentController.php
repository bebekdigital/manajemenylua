<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /**
     * Store a newly created department.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        Department::create([
            'name' => $validated['name'],
            'order' => $validated['order'] ?? 0,
        ]);

        return back()->with('success', 'Bidang berhasil ditambahkan.');
    }

    /**
     * Update the specified department.
     */
    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name,'.$department->id],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $department->update([
            'name' => $validated['name'],
            'order' => $validated['order'] ?? $department->order,
        ]);

        return back()->with('success', 'Bidang berhasil diperbarui.');
    }

    /**
     * Remove the specified department.
     */
    public function destroy(Department $department): RedirectResponse
    {
        if ($department->positions()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus Bidang karena masih memiliki daftar Jabatan.');
        }

        $department->delete();

        return back()->with('success', 'Bidang berhasil dihapus.');
    }
}
