<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\RolePermission;
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
        $this->authorizeEmployeeAccess($id, 'create');

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
                'in:ktp,kk,sk_pengangkatan,ijazah,sk_jenjang,sk_penugasan,sertifikat_pelatihan,sertifikat,lainnya',
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'record_type' => ['nullable', 'string', 'max:50'],
            'record_id' => ['nullable', 'integer'],
            'document_id' => ['nullable', 'integer'],
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
        $documentId = ! empty($validated['document_id']) ? (int) $validated['document_id'] : null;

        // Auto-generate a title if none provided
        $title = ! empty($validated['title']) ? trim($validated['title']) : match ($category) {
            'ktp' => 'KTP Pegawai',
            'kk' => 'Kartu Keluarga (KK)',
            'sk_pengangkatan' => 'SK Pengangkatan',
            'ijazah' => 'Ijazah Kuliah',
            'sk_jenjang' => 'SK Kenaikan Jenjang',
            'sk_penugasan' => 'SK Penugasan / Jabatan',
            'sertifikat_pelatihan', 'sertifikat' => 'Sertifikat Pelatihan',
            default => $file->getClientOriginalName(),
        };

        // Determine if replacing an existing document
        $existing = null;
        if ($documentId) {
            $existing = EmployeeDocument::where('employee_id', $employee->id)
                ->where('id', $documentId)
                ->first();
        }

        if (! $existing) {
            $query = EmployeeDocument::where('employee_id', $employee->id)
                ->where('category', $category);

            if ($recordType && $recordId) {
                $query->where('record_type', $recordType)->where('record_id', $recordId);
            } elseif (in_array($category, ['ktp', 'kk', 'sk_pengangkatan'])) {
                // Only one active KTP, KK, and SK Pengangkatan per employee
            } else {
                $query = null;
            }

            $existing = $query ? $query->latest('id')->first() : null;
        }

        if ($existing) {
            if ($existing->file_path && Storage::disk('local')->exists($existing->file_path)) {
                Storage::disk('local')->delete($existing->file_path);
            }
        }

        // Clean up any duplicate records in this exact category/record slot
        $dupQuery = EmployeeDocument::where('employee_id', $employee->id)
            ->where('category', $category);

        if ($existing) {
            $dupQuery->where('id', '!=', $existing->id);
        }

        if ($recordType && $recordId) {
            $dupQuery->where('record_type', $recordType)->where('record_id', $recordId);
        } elseif (in_array($category, ['ktp', 'kk', 'sk_pengangkatan'])) {
            // Keep clean for single slots
        } else {
            $dupQuery = null;
        }

        if ($dupQuery) {
            $duplicates = $dupQuery->get();
            foreach ($duplicates as $dup) {
                if ($dup->file_path && Storage::disk('local')->exists($dup->file_path)) {
                    Storage::disk('local')->delete($dup->file_path);
                }
                $dup->delete();
            }
        }

        $isImage = str_starts_with($file->getMimeType() ?: '', 'image/') ||
            in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'webp']);

        $directory = "documents/employees/{$employee->id}";

        if ($isImage) {
            // Standarisasi dan kompres berkas gambar ke JPEG (.jpg) kualitas tinggi, ukuran hemat
            $randomFileName = Str::random(40).'.jpg';
            $path = "{$directory}/{$randomFileName}";
            $absolutePath = Storage::disk('local')->path($path);

            $this->compressAndSaveAsJpeg($file->getRealPath(), $absolutePath, 1600, 1600, 82);

            $storedFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'.jpg';
            $storedFileSize = file_exists($absolutePath) ? filesize($absolutePath) : $file->getSize();
            $storedMime = 'image/jpeg';
        } else {
            // Berkas PDF atau dokumen non-gambar disimpan asli
            $extension = $file->getClientOriginalExtension() ?: 'bin';
            $randomFileName = Str::random(40).'.'.$extension;
            $path = $file->storeAs($directory, $randomFileName, 'local');

            $storedFileName = $file->getClientOriginalName();
            $storedFileSize = $file->getSize();
            $storedMime = $file->getMimeType();
        }

        $isReplacement = (bool) $existing;

        if ($existing) {
            $existing->update([
                'title' => $title,
                'file_path' => $path,
                'file_name' => $storedFileName,
                'file_size' => $storedFileSize,
                'mime_type' => $storedMime,
                'category' => $category,
                'record_type' => $recordType ?: $existing->record_type,
                'record_id' => $recordId ?: $existing->record_id,
            ]);
            $existing->touch();
        } else {
            EmployeeDocument::create([
                'employee_id' => $employee->id,
                'category' => $category,
                'record_type' => $recordType,
                'record_id' => $recordId,
                'title' => $title,
                'file_path' => $path,
                'file_name' => $storedFileName,
                'file_size' => $storedFileSize,
                'mime_type' => $storedMime,
            ]);
        }

        $actionMessage = $isReplacement ? "Dokumen {$title} berhasil diperbarui." : "Dokumen {$title} berhasil diunggah.";

        return back()->with('success', $actionMessage);
    }

    /**
     * Preview an employee document inline (PDF / Images).
     */
    public function preview(string $id, string $documentId): BinaryFileResponse
    {
        $this->authorizeEmployeeAccess($id, 'view');

        $document = EmployeeDocument::where('employee_id', $id)->findOrFail($documentId);

        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'Berkas fisik dokumen tidak ditemukan di server.');
        }

        $absolutePath = Storage::disk('local')->path($document->file_path);

        return response()->file($absolutePath, [
            'Content-Type' => $document->mime_type ?? 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.addslashes($document->file_name).'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => 'Sun, 02 Jan 1990 00:00:00 GMT',
        ]);
    }

    /**
     * Download an employee document securely.
     */
    public function download(string $id, string $documentId): StreamedResponse
    {
        $this->authorizeEmployeeAccess($id, 'export');

        $document = EmployeeDocument::where('employee_id', $id)->findOrFail($documentId);

        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'Berkas fisik dokumen tidak ditemukan di server.');
        }

        return Storage::disk('local')->download(
            $document->file_path,
            $document->file_name,
            [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => 'Sun, 02 Jan 1990 00:00:00 GMT',
            ]
        );
    }

    /**
     * Delete an employee document and remove its file from private storage.
     */
    public function destroy(string $id, string $documentId): RedirectResponse
    {
        $this->authorizeEmployeeAccess($id, 'delete');

        $document = EmployeeDocument::where('employee_id', $id)->findOrFail($documentId);

        if (Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $title = $document->title;
        $document->delete();

        return back()->with('success', "Dokumen {$title} berhasil dihapus.");
    }

    /**
     * Authorize that a user has permission and a teacher user can only access their own employee documents.
     */
    private function authorizeEmployeeAccess(string $employeeId, string $action = 'view'): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        // 1. Superadmin & Admin memiliki izin penuh tanpa pembatasan
        $roleName = strtolower(trim((string) ($user->role ?? '')));
        if (in_array($roleName, ['superadmin', 'admin'])) {
            return;
        }

        // 2. Proteksi Guru: hanya boleh mengakses atau mengelola dokumen atas nama profil dirinya sendiri
        if ($user->isTeacher()) {
            $myEmployee = $user->employeeProfile();
            if (! $myEmployee || (string) $myEmployee->id !== (string) $employeeId) {
                abort(403, 'Akses ditolak: Anda hanya memiliki izin mengakses dokumen pegawai atas nama Anda sendiri.');
            }

            // Pengecekan izin spesifik Dokumen Kepegawaian untuk guru pada datanya sendiri
            if ($action === 'view') {
                if (! RolePermission::canAccess($user, 'employee_documents', 'view')) {
                    abort(403, 'Akses ditolak: Anda tidak memiliki izin untuk melihat dokumen pegawai.');
                }
            } elseif ($action === 'create' || $action === 'update') {
                $canEdit = RolePermission::canAccess($user, 'employee_documents', 'create')
                    || RolePermission::canAccess($user, 'employee_documents', 'update');
                if (! $canEdit) {
                    abort(403, 'Akses ditolak: Role Anda tidak memiliki izin untuk mengunggah atau mengganti dokumen pegawai.');
                }
            } elseif ($action === 'delete') {
                $canDelete = RolePermission::canAccess($user, 'employee_documents', 'delete')
                    || RolePermission::canAccess($user, 'employee_documents', 'update');
                if (! $canDelete) {
                    abort(403, 'Akses ditolak: Role Anda tidak memiliki izin untuk menghapus dokumen pegawai.');
                }
            } elseif ($action === 'export') {
                $canExport = RolePermission::canAccess($user, 'employee_documents', 'export')
                    || RolePermission::canAccess($user, 'employee_documents', 'view');
                if (! $canExport) {
                    abort(403, 'Akses ditolak: Role Anda tidak memiliki izin untuk mengunduh dokumen pegawai.');
                }
            }

            return;
        }

        // 3. Untuk role lainnya (SDM, Kepala Sekolah, Staf TU, dll.)
        if ($action === 'view') {
            if (! RolePermission::canAccess($user, 'employee_documents', 'view')) {
                abort(403, 'Akses ditolak: Role Anda tidak memiliki izin untuk melihat dokumen pegawai.');
            }
        } elseif ($action === 'create' || $action === 'update') {
            $canModify = RolePermission::canAccess($user, 'employee_documents', 'create')
                || RolePermission::canAccess($user, 'employee_documents', 'update');
            if (! $canModify) {
                abort(403, 'Akses ditolak: Role Anda tidak memiliki izin untuk mengunggah atau memperbarui dokumen pegawai.');
            }
        } elseif ($action === 'delete') {
            $canDelete = RolePermission::canAccess($user, 'employee_documents', 'delete')
                || RolePermission::canAccess($user, 'employee_documents', 'update');
            if (! $canDelete) {
                abort(403, 'Akses ditolak: Role Anda tidak memiliki izin untuk menghapus dokumen pegawai.');
            }
        } elseif ($action === 'export') {
            $canExport = RolePermission::canAccess($user, 'employee_documents', 'export')
                || RolePermission::canAccess($user, 'employee_documents', 'view');
            if (! $canExport) {
                abort(403, 'Akses ditolak: Role Anda tidak memiliki izin untuk mengunduh dokumen pegawai.');
            }
        }
    }

    /**
     * Kompres dan simpan gambar sebagai format JPEG (.jpg) menggunakan PHP GD jika tersedia.
     */
    private function compressAndSaveAsJpeg(string $sourcePath, string $targetPath, int $maxWidth = 1600, int $maxHeight = 1600, int $quality = 82): bool
    {
        $dir = dirname($targetPath);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        if (! extension_loaded('gd') || ! file_exists($sourcePath)) {
            return @copy($sourcePath, $targetPath);
        }

        $imageInfo = @getimagesize($sourcePath);
        if (! $imageInfo) {
            return @copy($sourcePath, $targetPath);
        }

        [$origWidth, $origHeight, $imageType] = $imageInfo;

        $sourceImage = match ($imageType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : null,
            default => null,
        };

        if (! $sourceImage) {
            return @copy($sourcePath, $targetPath);
        }

        $width = $origWidth;
        $height = $origHeight;
        if ($width > $maxWidth || $height > $maxHeight) {
            if ($width / $height > $maxWidth / $maxHeight) {
                $height = (int) round(($height * $maxWidth) / $width);
                $width = $maxWidth;
            } else {
                $width = (int) round(($width * $maxHeight) / $height);
                $height = $maxHeight;
            }
        }

        $targetImage = imagecreatetruecolor($width, $height);

        $white = imagecolorallocate($targetImage, 255, 255, 255);
        imagefilledrectangle($targetImage, 0, 0, $width, $height, $white);

        imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);

        $saved = imagejpeg($targetImage, $targetPath, $quality);

        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        return $saved;
    }
}
