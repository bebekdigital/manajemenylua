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
            'name' => 'required|string|max:255',
            'order' => 'nullable|integer',
        ]);

        if ($schoolUnit->positions()->where('name', $validated['name'])->exists()) {
            return back()->withErrors(['name' => 'Nama Jabatan sudah ada di unit ini.']);
        }

        $schoolUnit->positions()->create([
            'name' => $validated['name'],
            'order' => $validated['order'] ?? 0,
        ]);

        return back()->with('success', 'Jabatan unit berhasil ditambahkan.');
    }

    public function update(Request $request, SchoolPosition $schoolPosition)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'order' => 'nullable|integer',
        ]);

        if ($schoolPosition->unit->positions()->where('name', $validated['name'])->where('id', '!=', $schoolPosition->id)->exists()) {
            return back()->withErrors(['name' => 'Nama Jabatan sudah ada di unit ini.']);
        }

        $schoolPosition->update([
            'name' => $validated['name'],
            'order' => $validated['order'] ?? $schoolPosition->order,
        ]);

        return back()->with('success', 'Jabatan unit berhasil diperbarui.');
    }

    public function destroy(SchoolPosition $schoolPosition)
    {
        $name = $schoolPosition->name;
        $schoolPosition->delete();

        return back()->with('success', "Jabatan \"{$name}\" berhasil dihapus.");
    }
}
