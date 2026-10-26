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

                // Email
                $email = $username.'@ulilalbabkra.sch.id';
                if ($employee->email && ! User::where('email', $employee->email)->exists()) {
                    $email = $employee->email;
                }

                $user = User::create([
                    'name' => $employee->nama_lengkap_gelar ?: $employee->nama,
                    'username' => $username,
                    'email' => $email,
                    'password' => Hash::make($rawPassword),
                    'role' => $role,
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
        ]);

        if (empty($validated['email'])) {
            $validated['email'] = $validated['username'].'@ulilalbabkra.sch.id';
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
            'role' => ['required', 'string', 'max:100'],
        ]);

        if (empty($validated['email'])) {
            $validated['email'] = $validated['username'].'@ulilalbabkra.sch.id';
        }

        if (! empty($validated['password'])) {
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
