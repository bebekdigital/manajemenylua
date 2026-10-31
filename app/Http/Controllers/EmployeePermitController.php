<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeePermit;
use App\Models\RolePermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeePermitController extends Controller
{
    /**
     * Store a newly created employee permit in storage.
     */
    public function store(Request $request, string $id): RedirectResponse
    {
        $this->authorizeEmployeeAccess($id, 'create');

        $employee = Employee::findOrFail($id);
        $user = auth()->user();

        $validated = $request->validate([
            'tanggal' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal'],
            'jenis' => ['required', 'string', 'max:100'],
            'detail' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:diajukan,disetujui,ditolak'],
            'catatan' => ['nullable', 'string'],
            'lampiran' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'max:10240', // Max 10MB
            ],
        ], [
            'jenis.required' => 'Jenis perizinan wajib diisi.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            'lampiran.mimes' => 'Format lampiran harus berupa PDF, JPG, JPEG, PNG, atau WEBP.',
            'lampiran.max' => 'Ukuran lampiran maksimal 10 MB.',
        ]);

        $filePath = null;
        $fileName = null;

        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');
            $fileName = $file->getClientOriginalName();
            $filePath = $file->store("employee_permits/{$employee->id}", 'local');
        }

        // Status default adalah 'diajukan'
        $status = $validated['status'] ?? 'diajukan';
        if ($user?->isTeacher()) {
            $status = 'diajukan';
        }

        $employee->permits()->create([
            'tanggal' => $validated['tanggal'] ?? now()->toDateString(),
            'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
            'jenis' => $validated['jenis'],
            'detail' => $validated['detail'] ?? null,
            'keterangan' => $validated['keterangan'] ?? null,
            'lampiran_path' => $filePath,
            'lampiran_nama' => $fileName,
            'status' => $status,
            'catatan' => $validated['catatan'] ?? null,
            'created_by' => $user?->id,
            'updated_by' => $user?->id,
        ]);

        return back()->with('success', 'Data perizinan berhasil diajukan / ditambahkan.');
    }

    /**
     * Update the specified employee permit in storage.
     */
    public function update(Request $request, string $id, string $permitId): RedirectResponse
    {
        $this->authorizeEmployeeAccess($id, 'update');

        $employee = Employee::findOrFail($id);
        $permit = $employee->permits()->findOrFail($permitId);
        $user = auth()->user();

        if ($user?->isTeacher() && $permit->status !== 'diajukan') {
            abort(403, 'Akses ditolak: Perizinan yang sudah diproses tidak dapat diubah.');
        }

        $validated = $request->validate([
            'tanggal' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal'],
            'jenis' => ['required', 'string', 'max:100'],
            'detail' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:diajukan,disetujui,ditolak'],
            'catatan' => ['nullable', 'string'],
            'lampiran' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'max:10240',
            ],
        ], [
            'jenis.required' => 'Jenis perizinan wajib diisi.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            'lampiran.mimes' => 'Format lampiran harus berupa PDF, JPG, JPEG, PNG, atau WEBP.',
            'lampiran.max' => 'Ukuran lampiran maksimal 10 MB.',
        ]);

        $updateData = [
            'jenis' => $validated['jenis'],
            'detail' => $validated['detail'] ?? null,
            'keterangan' => $validated['keterangan'] ?? null,
            'updated_by' => $user?->id,
        ];

        if (array_key_exists('tanggal', $validated)) {
            $updateData['tanggal'] = $validated['tanggal'];
        }
        if (array_key_exists('tanggal_selesai', $validated)) {
            $updateData['tanggal_selesai'] = $validated['tanggal_selesai'];
        }
        if (isset($validated['catatan'])) {
            $updateData['catatan'] = $validated['catatan'];
        }

        // Hanya non-guru atau admin yang berhak mengubah status, atau jika guru maka status tetap kecuali belum diset
        if (! $user?->isTeacher() && isset($validated['status'])) {
            $updateData['status'] = $validated['status'];
        }

        if ($request->hasFile('lampiran')) {
            if ($permit->lampiran_path && Storage::disk('local')->exists($permit->lampiran_path)) {
                Storage::disk('local')->delete($permit->lampiran_path);
            }

            $file = $request->file('lampiran');
            $updateData['lampiran_nama'] = $file->getClientOriginalName();
            $updateData['lampiran_path'] = $file->store("employee_permits/{$employee->id}", 'local');
        }

        $permit->update($updateData);

        return back()->with('success', 'Data perizinan berhasil diperbarui.');
    }

    /**
     * Quick update for note and status (Catatan).
     */
    public function updateNote(Request $request, string $id, string $permitId): RedirectResponse
    {
        $this->authorizeEmployeeAccess($id, 'update');

        $employee = Employee::findOrFail($id);
        $permit = $employee->permits()->findOrFail($permitId);
        $user = auth()->user();

        if ($user?->isTeacher()) {
            abort(403, 'Akses ditolak: Guru tidak diizinkan mengubah catatan perizinan.');
        }

        $validated = $request->validate([
            'catatan' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:diajukan,disetujui,ditolak'],
        ]);

        $updateData = [
            'catatan' => $validated['catatan'] ?? null,
            'updated_by' => $user?->id,
        ];

        if (! $user?->isTeacher() && isset($validated['status'])) {
            $updateData['status'] = $validated['status'];
        }

        $permit->update($updateData);

        return back()->with('success', 'Catatan perizinan berhasil diperbarui.');
    }

    /**
     * Remove the specified employee permit from storage.
     */
    public function destroy(string $id, string $permitId): RedirectResponse
    {
        $this->authorizeEmployeeAccess($id, 'delete');

        $employee = Employee::findOrFail($id);
        $permit = $employee->permits()->findOrFail($permitId);
        $user = auth()->user();

        if ($user?->isTeacher() && $permit->status !== 'diajukan') {
            abort(403, 'Akses ditolak: Perizinan yang sudah diproses tidak dapat dihapus.');
        }

        if ($permit->lampiran_path && Storage::disk('local')->exists($permit->lampiran_path)) {
            Storage::disk('local')->delete($permit->lampiran_path);
        }

        $permit->delete();

        return back()->with('success', 'Data perizinan berhasil dihapus.');
    }

    /**
     * Preview permit attachment file inline (PDF / Image).
     */
    public function preview(string $id, string $permitId): BinaryFileResponse
    {
        $this->authorizeEmployeeAccess($id, 'view');

        $permit = EmployeePermit::where('employee_id', $id)->findOrFail($permitId);

        if (! $permit->lampiran_path || ! Storage::disk('local')->exists($permit->lampiran_path)) {
            abort(404, 'Berkas fisik lampiran tidak ditemukan di server.');
        }

        $absolutePath = Storage::disk('local')->path($permit->lampiran_path);
        $mime = mime_content_type($absolutePath) ?: 'application/octet-stream';

        return response()->file($absolutePath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($permit->lampiran_nama ?? 'lampiran').'"',
        ]);
    }

    /**
     * Download permit attachment securely.
     */
    public function download(string $id, string $permitId): StreamedResponse
    {
        $this->authorizeEmployeeAccess($id, 'view');

        $permit = EmployeePermit::where('employee_id', $id)->findOrFail($permitId);

        if (! $permit->lampiran_path || ! Storage::disk('local')->exists($permit->lampiran_path)) {
            abort(404, 'Berkas fisik lampiran tidak ditemukan di server.');
        }

        return Storage::disk('local')->download($permit->lampiran_path, $permit->lampiran_nama ?? 'lampiran');
    }

    /**
     * Authorize access to employee permit actions.
     */
    private function authorizeEmployeeAccess(string $employeeId, string $action = 'view'): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(401);
        }

        if ($user->isTeacher()) {
            $myEmployee = $user->employeeProfile();
            if (! $myEmployee || (string) $myEmployee->id !== (string) $employeeId) {
                abort(403, 'Akses ditolak: Anda hanya dapat mengakses data perizinan atas nama Anda sendiri.');
            }

            return;
        }

        if (! RolePermission::canAccess($user, 'employee_detail_history', $action)) {
            abort(403, 'Akses ditolak: Role Anda tidak memiliki izin untuk mengelola perizinan pegawai.');
        }
    }
}
