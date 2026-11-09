<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Models\SchoolProgram;
use App\Models\SchoolUnit;
use Illuminate\Http\Request;

class SchoolProgramController extends Controller
{
    public function store(Request $request, SchoolUnit $schoolUnit)
    {
        if (! RolePermission::canAccess($request->user(), 'setting_academic', 'create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambah program pendidikan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $exists = $schoolUnit->programs()->where('name', $validated['name'])->exists();
        if ($exists) {
            return back()->with('error', 'Program sudah ada di unit ini.');
        }

        $schoolUnit->programs()->create([
            'name' => $validated['name'],
        ]);

        return back()->with('success', 'Program berhasil ditambahkan.');
    }

    public function update(Request $request, SchoolProgram $schoolProgram)
    {
        if (! RolePermission::canAccess($request->user(), 'setting_academic', 'update')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah program pendidikan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $exists = $schoolProgram->unit->programs()
            ->where('name', $validated['name'])
            ->where('id', '!=', $schoolProgram->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Program sudah ada di unit ini.');
        }

        $schoolProgram->update([
            'name' => $validated['name'],
        ]);

        return back()->with('success', 'Program berhasil diperbarui.');
    }

    public function destroy(SchoolProgram $schoolProgram)
    {
        if (! RolePermission::canAccess(request()->user(), 'setting_academic', 'delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus program pendidikan.');
        }

        $schoolProgram->delete();

        return back()->with('success', 'Program berhasil dihapus.');
    }
}
