<?php

namespace App\Http\Controllers;

use App\Models\SchoolProgram;
use Illuminate\Http\Request;

class SchoolProgramController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:school_programs,name',
        ]);

        SchoolProgram::create([
            'name' => $validated['name'],
        ]);

        return back()->with('success', 'Program berhasil ditambahkan.');
    }

    public function update(Request $request, SchoolProgram $schoolProgram)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:school_programs,name,' . $schoolProgram->id,
        ]);

        $schoolProgram->update([
            'name' => $validated['name'],
        ]);

        return back()->with('success', 'Program berhasil diperbarui.');
    }

    public function destroy(SchoolProgram $schoolProgram)
    {
        $schoolProgram->delete();

        return back()->with('success', 'Program berhasil dihapus.');
    }
}
