<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Models\SchoolClass;
use App\Models\SchoolUnit;
use Illuminate\Http\Request;

class SchoolClassController extends Controller
{
    public function store(Request $request, SchoolUnit $schoolUnit)
    {
        if (! RolePermission::canAccess($request->user(), 'setting_academic', 'create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambah kelas.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Prevent duplicate class name in the same unit
        if ($schoolUnit->classes()->where('name', $validated['name'])->exists()) {
            return back()->withErrors(['name' => 'Nama Kelas sudah ada di unit ini.']);
        }

        $schoolUnit->classes()->create($validated);

        return back()->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(Request $request, SchoolClass $schoolClass)
    {
        if (! RolePermission::canAccess($request->user(), 'setting_academic', 'update')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah kelas.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        if ($schoolClass->unit->classes()->where('name', $validated['name'])->where('id', '!=', $schoolClass->id)->exists()) {
            return back()->withErrors(['name' => 'Nama Kelas sudah ada di unit ini.']);
        }

        $schoolClass->update($validated);

        return back()->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(SchoolClass $schoolClass)
    {
        if (! RolePermission::canAccess(request()->user(), 'setting_academic', 'delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus kelas.');
        }

        $schoolClass->delete();

        return back()->with('success', 'Kelas berhasil dihapus.');
    }
}
