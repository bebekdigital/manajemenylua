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
            'level_id' => 'nullable|exists:school_levels,id',
        ]);

        $name = trim($validated['name']);
        $levelId = $validated['level_id'] ?? null;

        // Jika level_id tidak dikirim, ambil level pertama dari unit ini
        if (! $levelId) {
            $levelId = $schoolUnit->levels()->value('school_levels.id');
        }

        // Cari atau buat program master school_programs
        $program = SchoolProgram::firstOrCreate(['name' => $name]);

        // Cek apakah sudah terhubung di unit dan jenjang ini
        $query = DB::table('school_unit_program')
            ->where('school_unit_id', $schoolUnit->id)
            ->where('school_program_id', $program->id);

        if ($levelId) {
            $query->where('school_level_id', $levelId);
        }

        if ($query->exists()) {
            return back()->with('error', 'Program sudah ada di unit pada jenjang ini.');
        }

        DB::table('school_unit_program')->insert([
            'school_level_id' => $levelId,
            'school_unit_id' => $schoolUnit->id,
            'school_program_id' => $program->id,
            'created_at' => now(),
            'updated_at' => now(),
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
        $levelId = $request->input('level_id');

        if ($unitId) {
            $query = DB::table('school_unit_program')
                ->where('school_unit_id', $unitId)
                ->where('school_program_id', $schoolProgram->id);

            if ($levelId) {
                $query->where('school_level_id', $levelId);
            }

            $query->delete();

            // Jika program sudah tidak terikat di level/unit mana pun
            $remaining = DB::table('school_unit_program')
                ->where('school_program_id', $schoolProgram->id)
                ->count();

            if ($remaining === 0) {
                $schoolProgram->delete();
            }
        } else {
            DB::table('school_unit_program')
                ->where('school_program_id', $schoolProgram->id)
                ->delete();
            $schoolProgram->delete();
        }

        return back()->with('success', 'Program berhasil dihapus.');
    }
}
