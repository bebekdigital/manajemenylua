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
     * Update the user's profile photo (compressed and saved as JPG).
     */
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $user = Auth::user();

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $uploadDir = public_path('uploads/profiles');
            if (! is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }

            // Hapus foto profil lama jika ada
            if ($user->foto) {
                $oldPath = public_path(ltrim($user->foto, '/'));
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $filename = 'profile_'.$user->id.'_'.time().'.jpg';
            $targetPath = $uploadDir.'/'.$filename;

            $this->compressAndSaveAsJpeg($file->getRealPath(), $targetPath, 800, 800, 82);

            $user->update(['foto' => '/uploads/profiles/'.$filename]);
        }

        return back()->with('success', 'Foto profil berhasil diperbarui.');
    }

    /**
     * Kompres dan simpan gambar sebagai format JPEG (.jpg) menggunakan PHP GD jika tersedia.
     */
    private function compressAndSaveAsJpeg(string $sourcePath, string $targetPath, int $maxWidth = 800, int $maxHeight = 800, int $quality = 82): bool
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
