<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Models\SchoolProgram;
use App\Models\SchoolUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        $name = trim($validated['name']);

        // Cari atau buat program master school_programs
        $program = SchoolProgram::firstOrCreate(['name' => $name]);

        // Cek apakah sudah terhubung di unit ini
        $exists = $schoolUnit->programs()->where('school_programs.id', $program->id)->exists();
        if ($exists) {
            return back()->with('error', 'Program sudah ada di unit ini.');
        }

        $schoolUnit->programs()->attach($program->id);

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

        $name = trim($validated['name']);
        $oldName = $schoolProgram->name;

        $duplicate = SchoolProgram::where('name', $name)->where('id', '!=', $schoolProgram->id)->exists();
        if ($duplicate) {
            return back()->with('error', 'Nama program tersebut sudah ada.');
        }

        $schoolProgram->update([
            'name' => $name,
        ]);

        if ($oldName !== $name) {
            DB::table('student_academic_records')
                ->where('program', $oldName)
                ->update(['program' => $name]);

            if (Schema::hasColumn('students', 'program')) {
                DB::table('students')
                    ->where('program', $oldName)
                    ->update(['program' => $name]);
            }
        }

        return back()->with('success', 'Program berhasil diperbarui.');
    }

    public function destroy(Request $request, SchoolProgram $schoolProgram)
    {
        if (! RolePermission::canAccess($request->user(), 'setting_academic', 'delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus program pendidikan.');
        }

        $unitId = $request->input('unit_id');
        if ($unitId) {
            $schoolUnit = SchoolUnit::find($unitId);
            $schoolUnit?->programs()->detach($schoolProgram->id);

            if ($schoolProgram->units()->count() === 0) {
                $schoolProgram->delete();
            }
        } else {
            $schoolProgram->units()->detach();
            $schoolProgram->delete();
        }

        return back()->with('success', 'Program berhasil dihapus.');
    }
}
