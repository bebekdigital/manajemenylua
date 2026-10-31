<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Generate user accounts automatically for selected employees.
     */
    public function generate(Request $request): RedirectResponse
    {
        if (auth()->user()?->role !== 'superadmin') {
            abort(403, 'Hanya Superadmin yang berhak membuat akun baru.');
        }

        $validated = $request->validate([
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['required', 'integer', 'exists:employees,id'],
        ], [
            'employee_ids.required' => 'Pilih minimal satu pegawai untuk dibuatkan akun.',
            'employee_ids.min' => 'Pilih minimal satu pegawai untuk dibuatkan akun.',
        ]);

        $employees = Employee::with(['currentPosition', 'positions'])->whereIn('id', $validated['employee_ids'])->get();

        $createdCount = 0;
        $createdList = [];

        DB::transaction(function () use ($employees, &$createdCount, &$createdList) {
            foreach ($employees as $employee) {
                // Generate username dari kata pertama dan terakhir nama lengkapnya + 2 angka acak
                $cleanName = preg_replace('/[^a-zA-Z\s]/', '', $employee->nama);
                $words = array_values(array_filter(explode(' ', strtolower($cleanName))));

                if (empty($words)) {
                    $base = 'pegawai';
                } elseif (count($words) === 1) {
                    $base = $words[0];
                } else {
                    $base = $words[0].end($words);
                }

                if (strlen($base) < 3) {
                    $base = 'user'.$base;
                }

                do {
                    $digits = sprintf('%02d', mt_rand(10, 99));
                    $username = $base.$digits;
                } while (User::where('username', $username)->exists());

                // Password default: tanggal lahir format YYYYMMDD
                $rawPassword = $employee->tanggal_lahir
                    ? $employee->tanggal_lahir->format('Ymd')
                    : '12345678';

                // Role: diambil dari Jabatan Penugasan
                $pos = $employee->currentPosition ?? $employee->positions->sortByDesc('tmt_jabatan')->first();
                $role = $pos?->jabatan ?: 'Guru';

                $user = User::create([
                    'name' => $employee->nama,
                    'username' => $username,
                    'email' => null,
                    'password' => Hash::make($rawPassword),
                    'role' => $role,
                    'is_active' => true,
                    'must_change_password' => true,
                    'nipy' => $employee->nipy,
                    'status_kepegawaian' => $employee->status_keaktifan ?? 'Aktif',
                    'jk' => $employee->jk,
                    'ttl' => $employee->ttl,
                    'unit' => $pos?->unit_kerja,
                    'jabatan' => $pos?->jabatan,
                    'no_wa' => $employee->no_wa,
                    'foto' => $employee->foto,
                ]);

                $createdCount++;
                $createdList[] = [
                    'nama' => $user->name,
                    'username' => $user->username,
                    'default_password' => $rawPassword,
                    'role' => $user->role,
                ];
            }
        });

        return back()
            ->with('success', "Berhasil membuat {$createdCount} akun pengguna baru.")
            ->with('generatedUsers', $createdList);
    }

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
            'role' => ['required', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (empty($validated['email'])) {
            $validated['email'] = null;
        }
        $validated['password'] = Hash::make($validated['password']);
        if (! isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }
        if (! isset($validated['must_change_password'])) {
            $validated['must_change_password'] = true;
        }

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
            'role' => ['required', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return back()->with('success', 'Data akun berhasil diperbarui.');
    }

    /**
     * Toggle active status of a user account.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        if (auth()->user()?->role !== 'superadmin') {
            abort(403, 'Hanya Superadmin yang berhak mengubah status akun.');
        }

        if (auth()->id() === $user->id) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $statusText = $user->is_active ? 'diaktifkan (akses dibuka)' : 'dinonaktifkan (akses ditutup)';

        return back()->with('success', "Akun '{$user->name}' berhasil {$statusText}.");
    }

    /**
     * Reset user password back to default password (tanggal lahir YYYYMMDD).
     */
    public function resetPassword(User $user): RedirectResponse
    {
        if (auth()->user()?->role !== 'superadmin') {
            abort(403, 'Hanya Superadmin yang berhak mereset password akun.');
        }

        $rawPassword = $user->getDefaultPassword() ?: '12345678';

        $user->update([
            'password' => Hash::make($rawPassword),
            'must_change_password' => true,
        ]);

        return back()->with('success', "Password akun '{$user->name}' berhasil direset ke password default ({$rawPassword}). Pengguna wajib membuat password baru saat login berikutnya.");
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
