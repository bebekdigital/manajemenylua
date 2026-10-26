<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{
    /**
     * Upload and save an employee document in private storage (Option A).
     */
    public function store(Request $request, string $id): RedirectResponse
    {
        $this->authorizeEmployeeAccess($id);

        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'max:10240', // Max 10MB
            ],
            'category' => [
                'required',
                'string',
                'in:ktp,kk,sk_pengangkatan,ijazah,sk_jenjang,sk_penugasan,lainnya',
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'record_type' => ['nullable', 'string', 'max:50'],
            'record_id' => ['nullable', 'integer'],
        ], [
            'file.required' => 'Berkas dokumen wajib dipilih.',
            'file.mimes' => 'Format berkas harus berupa PDF, JPG, JPEG, PNG, atau WEBP.',
            'file.max' => 'Ukuran berkas tidak boleh lebih dari 10 MB.',
            'category.required' => 'Kategori dokumen wajib ditentukan.',
        ]);

        $file = $request->file('file');
        $category = $validated['category'];
        $recordType = $validated['record_type'] ?? null;
        $recordId = ! empty($validated['record_id']) ? (int) $validated['record_id'] : null;

        // Auto-generate a title if none provided
        $title = ! empty($validated['title']) ? trim($validated['title']) : match ($category) {
            'ktp' => 'KTP Pegawai',
            'kk' => 'Kartu Keluarga (KK)',
            'sk_pengangkatan' => 'SK Pengangkatan',
            'ijazah' => 'Ijazah Kuliah',
            'sk_jenjang' => 'SK Kenaikan Jenjang',
            'sk_penugasan' => 'SK Penugasan / Jabatan',
            default => $file->getClientOriginalName(),
        };

        // Determine if replacing an existing document
        $query = EmployeeDocument::where('employee_id', $employee->id)
            ->where('category', $category);

        if ($recordType && $recordId) {
            $query->where('record_type', $recordType)->where('record_id', $recordId);
        } elseif (in_array($category, ['ktp', 'kk', 'sk_pengangkatan'])) {
            // Only one active KTP, KK, and SK Pengangkatan per employee
            $query->whereNull('record_id');
        } else {
            // For general ijazah or lainnya without specific record_id, only replace if explicitly targeting an ID
            if ($request->filled('document_id')) {
                $query->where('id', $request->input('document_id'));
            } else {
                $query = null;
            }
        }

        $existing = $query ? $query->first() : null;

        if ($existing) {
            if (Storage::disk('local')->exists($existing->file_path)) {
                Storage::disk('local')->delete($existing->file_path);
            }
        }

        // Store file in private local disk: storage/app/private/documents/employees/{id}
        $extension = $file->getClientOriginalExtension() ?: 'bin';
        $randomFileName = Str::random(40).'.'.$extension;
        $directory = "documents/employees/{$employee->id}";
        $path = $file->storeAs($directory, $randomFileName, 'local');

        if ($existing) {
            $existing->update([
                'title' => $title,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);
        } else {
            EmployeeDocument::create([
                'employee_id' => $employee->id,
                'category' => $category,
                'record_type' => $recordType,
                'record_id' => $recordId,
                'title' => $title,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);
        }

        return back()->with('success', "Dokumen {$title} berhasil diunggah.");
    }

    /**
     * Preview an employee document inline (PDF / Images).
     */
    public function preview(string $id, string $documentId): BinaryFileResponse
    {
        $this->authorizeEmployeeAccess($id);

        $document = EmployeeDocument::where('employee_id', $id)->findOrFail($documentId);

        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'Berkas fisik dokumen tidak ditemukan di server.');
        }

        $absolutePath = Storage::disk('local')->path($document->file_path);

        return response()->file($absolutePath, [
            'Content-Type' => $document->mime_type ?? 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.addslashes($document->file_name).'"',
        ]);
    }

    /**
     * Download an employee document securely.
     */
    public function download(string $id, string $documentId): StreamedResponse
    {
        $this->authorizeEmployeeAccess($id);

        $document = EmployeeDocument::where('employee_id', $id)->findOrFail($documentId);

        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'Berkas fisik dokumen tidak ditemukan di server.');
        }

        return Storage::disk('local')->download($document->file_path, $document->file_name);
    }

    /**
     * Delete an employee document and remove its file from private storage.
     */
    public function destroy(string $id, string $documentId): RedirectResponse
    {
        $this->authorizeEmployeeAccess($id);

        $document = EmployeeDocument::where('employee_id', $id)->findOrFail($documentId);

        if (Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $title = $document->title;
        $document->delete();

        return back()->with('success', "Dokumen {$title} berhasil dihapus.");
    }

    /**
     * Authorize that a teacher user can only access their own employee documents.
     */
    private function authorizeEmployeeAccess(string $employeeId): void
    {
        $user = auth()->user();
        if ($user?->isTeacher()) {
            $myEmployee = $user->employeeProfile();
            if (! $myEmployee || (string) $myEmployee->id !== (string) $employeeId) {
                abort(403, 'Akses ditolak: Anda hanya memiliki izin mengakses dokumen pegawai atas nama Anda sendiri.');
            }
        }
    }
}
