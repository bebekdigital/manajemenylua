<?php

namespace App\Http\Controllers;

use App\Models\SchoolProgram;
use App\Models\SchoolUnit;
use Illuminate\Http\Request;

class SchoolProgramController extends Controller
{
    public function store(Request $request, SchoolUnit $schoolUnit)
    {
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
        $schoolProgram->delete();

        return back()->with('success', 'Program berhasil dihapus.');
    }
}
