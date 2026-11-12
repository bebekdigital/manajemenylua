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
            'name' => 'required|string|max:255',
        ]);

        $name = trim($validated['name']);

        // Cari atau buat master unit sekolah
        $unit = SchoolUnit::firstOrCreate(['name' => $name]);

        // Cek apakah sudah terhubung dengan jenjang ini
        $exists = $schoolLevel->units()->where('school_units.id', $unit->id)->exists();
        if ($exists) {
            return back()->with('error', 'Unit sudah ada di jenjang ini.');
        }

        $schoolLevel->units()->attach($unit->id);

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
            'name' => trim($validated['name']),
        ]);

        return back()->with('success', 'Unit berhasil diperbarui.');
    }

    public function destroy(Request $request, SchoolUnit $schoolUnit)
    {
        if (! RolePermission::canAccess(request()->user(), 'setting_academic', 'delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus unit sekolah.');
        }

        $levelId = $request->input('level_id');
        if ($levelId) {
            $schoolLevel = SchoolLevel::find($levelId);
            $schoolLevel?->units()->detach($schoolUnit->id);

            // Jika unit sudah tidak terikat di jenjang mana pun
            if ($schoolUnit->levels()->count() === 0) {
                $schoolUnit->programs()->detach();
                $schoolUnit->delete();
            }
        } else {
            $schoolUnit->levels()->detach();
            $schoolUnit->programs()->detach();
            $schoolUnit->delete();
        }

        return back()->with('success', 'Unit berhasil dihapus.');
    }
}
