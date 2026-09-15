<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile page.
     */
    public function show(): Response
    {
        $user = Auth::user();

        return Inertia::render('Profile', [
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'nipy' => $user->nipy,
                'status_kepegawaian' => $user->status_kepegawaian,
                'jk' => $user->jk,
                'ttl' => $user->ttl,
                'unit' => $user->unit,
                'jabatan' => $user->jabatan,
                'no_wa' => $user->no_wa,
                'foto' => $user->foto,
            ],
        ]);
    }

    /**
     * Update the user's profile.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'jk' => 'nullable|string|in:L,P',
            'ttl' => 'nullable|string|max:255',
            'no_wa' => 'nullable|string|max:20',
        ]);

        $user = Auth::user();
        $user->update($validated);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * Update the user's profile photo (dummy - stores base64 or path).
     */
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $user = Auth::user();

        // Store dummy path for now (no real storage setup needed)
        if ($request->hasFile('foto')) {
            $filename = 'profile_'.$user->id.'_'.time().'.'.$request->file('foto')->getClientOriginalExtension();
            $request->file('foto')->move(public_path('uploads/profiles'), $filename);
            $user->update(['foto' => '/uploads/profiles/'.$filename]);
        }

        return back()->with('success', 'Foto profil berhasil diperbarui.');
    }
}
