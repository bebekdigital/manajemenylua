<?php

namespace App\Http\Controllers;

use App\Models\SchoolLevel;
use App\Models\SchoolUnit;
use Illuminate\Http\Request;

class SchoolUnitController extends Controller
{
    public function store(Request $request, SchoolLevel $schoolLevel)
    {
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
        // Add check here if units have relation to students/classrooms later
        $schoolUnit->delete();

        return back()->with('success', 'Unit berhasil dihapus.');
    }
}
