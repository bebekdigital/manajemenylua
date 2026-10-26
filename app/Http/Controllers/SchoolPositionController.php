<?php

namespace App\Http\Controllers;

use App\Models\SchoolPosition;
use App\Models\SchoolUnit;
use Illuminate\Http\Request;

class SchoolPositionController extends Controller
{
    public function store(Request $request, SchoolUnit $schoolUnit)
    {
        $validated = $request->validate([
            'school_position_id' => 'nullable|exists:school_positions,id',
            'name' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
        ]);

        if (empty($validated['school_position_id']) && empty($validated['name'])) {
            return back()->withErrors(['name' => 'Pilih jabatan dari daftar atau masukkan nama jabatan baru.']);
        }

        if (! empty($validated['school_position_id'])) {
            $position = SchoolPosition::findOrFail($validated['school_position_id']);
        } else {
            $cleanName = trim($validated['name']);
            $position = SchoolPosition::firstOrCreate(['name' => $cleanName]);
        }

        if ($schoolUnit->positions()->where('school_position_id', $position->id)->exists()) {
            return back()->withErrors(['name' => "Jabatan \"{$position->name}\" sudah ada di unit {$schoolUnit->name}."]);
        }

        $schoolUnit->positions()->attach($position->id, [
            'order' => $validated['order'] ?? 0,
        ]);

        return back()->with('success', "Jabatan \"{$position->name}\" berhasil ditambahkan ke unit {$schoolUnit->name}.");
    }

    public function update(Request $request, SchoolPosition $schoolPosition)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:school_positions,name,'.$schoolPosition->id,
        ]);

        $schoolPosition->update([
            'name' => trim($validated['name']),
        ]);

        return back()->with('success', 'Nama jabatan berhasil diperbarui.');
    }

    public function detach(SchoolUnit $schoolUnit, SchoolPosition $schoolPosition)
    {
        $schoolUnit->positions()->detach($schoolPosition->id);

        return back()->with('success', "Jabatan \"{$schoolPosition->name}\" berhasil dihapus dari unit {$schoolUnit->name}.");
    }

    public function destroy(SchoolPosition $schoolPosition)
    {
        $name = $schoolPosition->name;
        $schoolPosition->units()->detach();
        $schoolPosition->delete();

        return back()->with('success', "Jabatan \"{$name}\" berhasil dihapus dari sistem.");
    }
}
