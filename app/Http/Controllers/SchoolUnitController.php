<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Models\SchoolLevel;
use App\Models\SchoolUnit;
use Illuminate\Http\Request;

class SchoolUnitController extends Controller
{
    public function store(Request $request, SchoolLevel $schoolLevel)
    {
        if (! RolePermission::canAccess($request->user(), 'setting_academic', 'create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambah unit sekolah.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:school_units,name',
        ]);

        $schoolLevel->units()->create([
            'name' => $validated['name'],
        ]);

        return back()->with('success', 'Unit berhasil ditambahkan.');
    }

    public function update(Request $request, SchoolUnit $schoolUnit)
    {
        if (! RolePermission::canAccess($request->user(), 'setting_academic', 'update')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah unit sekolah.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:school_units,name,'.$schoolUnit->id,
        ]);

        $schoolUnit->update([
            'name' => $validated['name'],
        ]);

        return back()->with('success', 'Unit berhasil diperbarui.');
    }

    public function destroy(SchoolUnit $schoolUnit)
    {
        if (! RolePermission::canAccess(request()->user(), 'setting_academic', 'delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus unit sekolah.');
        }

        // Add check here if units have relation to students/classrooms later
        $schoolUnit->delete();

        return back()->with('success', 'Unit berhasil dihapus.');
    }
}
