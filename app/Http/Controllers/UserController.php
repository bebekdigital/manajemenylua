<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        // Pengecekan superadmin sederhana
        if (auth()->user()?->role !== 'superadmin') {
            abort(403, 'Hanya Superadmin yang berhak membuat akun baru.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'in:superadmin,guru'], // Dapat ditambah nanti
        ]);

        if (empty($validated['email'])) {
            $validated['email'] = $validated['username'] . '@ulilalbabkra.sch.id';
        }

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return back()->with('success', 'Akun pengguna berhasil dibuat.');
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        if (auth()->user()?->role !== 'superadmin') {
            abort(403, 'Hanya Superadmin yang berhak mengubah data akun.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'string', 'in:superadmin,guru'],
        ]);

        if (empty($validated['email'])) {
            $validated['email'] = $validated['username'] . '@ulilalbabkra.sch.id';
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return back()->with('success', 'Data akun berhasil diperbarui.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        if (auth()->user()?->role !== 'superadmin') {
            abort(403, 'Hanya Superadmin yang berhak menghapus akun.');
        }

        if (auth()->id() === $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return back()->with('success', 'Akun pengguna berhasil dihapus.');
    }
}
