<?php

namespace App\Http\Controllers;

use App\Models\SchoolLevel;
use Illuminate\Http\Request;

class SchoolLevelController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:school_levels,name',
            'order' => 'integer|min:0',
        ]);

        SchoolLevel::create([
            'name' => $validated['name'],
            'order' => $validated['order'] ?? 0,
        ]);

        return back()->with('success', 'Jenjang berhasil ditambahkan.');
    }

    public function update(Request $request, SchoolLevel $schoolLevel)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:school_levels,name,' . $schoolLevel->id,
            'order' => 'integer|min:0',
        ]);

        $schoolLevel->update([
            'name' => $validated['name'],
            'order' => $validated['order'] ?? $schoolLevel->order,
        ]);

        return back()->with('success', 'Jenjang berhasil diperbarui.');
    }

    public function destroy(SchoolLevel $schoolLevel)
    {
        if ($schoolLevel->units()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus Jenjang karena masih memiliki Unit.');
        }

        $schoolLevel->delete();

        return back()->with('success', 'Jenjang berhasil dihapus.');
    }
}
