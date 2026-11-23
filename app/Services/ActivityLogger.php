<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ActivityLogger
{
    public const MENU_COOLDOWN_SECONDS = 300;

    public const PSB_MODE_COOLDOWN_SECONDS = 180;

    /**
     * Daftar definisi menu utama aplikasi agar perbandingan pemakaian menu akurat.
     *
     * @return array<string, array{label: string, category: string}>
     */
    public static function mainMenus(): array
    {
        return [
            'psb' => ['label' => 'Pemetaan PSB', 'category' => 'PSB'],
            'beranda' => ['label' => 'Beranda', 'category' => 'Workspace'],
            'student_statistics' => ['label' => 'Statistik Siswa', 'category' => 'Database Siswa'],
            'students' => ['label' => 'Data Siswa', 'category' => 'Database Siswa'],
            'staff_statistics' => ['label' => 'Statistik Pegawai', 'category' => 'Database Pegawai'],
            'staff' => ['label' => 'Data Pegawai', 'category' => 'Database Pegawai'],
            'administration' => ['label' => 'Administrasi', 'category' => 'Administrasi & Aset'],
            'pembelajaran' => ['label' => 'Pembelajaran', 'category' => 'Akademik'],
            'agenda' => ['label' => 'Agenda', 'category' => 'Utama'],
            'info' => ['label' => 'Info', 'category' => 'Utama'],
            'persetujuan' => ['label' => 'Persetujuan', 'category' => 'Utama'],
            'portal' => ['label' => 'Pengaturan', 'category' => 'Sistem'],
        ];
    }

    /**
     * Petakan path URL menjadi modul menu utama yang dipantau.
     *
     * @return array{module: string, label: string}|null
     */
    public static function resolveMenuTarget(string $rawPath): ?array
    {
        $path = '/'.ltrim($rawPath, '/');
        $path = strtolower(rtrim($path, '/')) ?: '/';

        if (
            $path === '/' ||
            str_starts_with($path, '/activity-logs') ||
            str_starts_with($path, '/api/') ||
            str_starts_with($path, '/section/') ||
            str_starts_with($path, '/students/photo/') ||
            str_starts_with($path, '/students/template') ||
            str_starts_with($path, '/staff/template') ||
            str_starts_with($path, '/run-migration') ||
            str_starts_with($path, '/setup-superadmin')
        ) {
            return null;
        }

        if ($path === '/beranda') {
            return ['module' => 'beranda', 'label' => 'Beranda'];
        }
        if ($path === '/psb' || str_starts_with($path, '/psb/ringkasan')) {
            return ['module' => 'psb', 'label' => 'Pemetaan PSB'];
        }
        if ($path === '/statistics' || str_starts_with($path, '/students/statistics')) {
            return ['module' => 'student_statistics', 'label' => 'Statistik Siswa'];
        }
        if (str_starts_with($path, '/students')) {
            return ['module' => 'students', 'label' => 'Data Siswa'];
        }
        if (str_starts_with($path, '/staff/statistics')) {
            return ['module' => 'staff_statistics', 'label' => 'Statistik Pegawai'];
        }
        if (str_starts_with($path, '/staff')) {
            if (
                str_contains($path, '/permits/') ||
                str_contains($path, '/documents/')
            ) {
                return null;
            }

            return ['module' => 'staff', 'label' => 'Data Pegawai'];
        }
        if (str_starts_with($path, '/administration')) {
            return ['module' => 'administration', 'label' => 'Administrasi'];
        }
        if (str_starts_with($path, '/pembelajaran')) {
            return ['module' => 'pembelajaran', 'label' => 'Pembelajaran'];
        }
        if (str_starts_with($path, '/agenda')) {
            return ['module' => 'agenda', 'label' => 'Agenda'];
        }
        if (str_starts_with($path, '/info')) {
            return ['module' => 'info', 'label' => 'Info'];
        }
        if (str_starts_with($path, '/persetujuan')) {
            return ['module' => 'persetujuan', 'label' => 'Persetujuan'];
        }
        if (str_starts_with($path, '/portal')) {
            return ['module' => 'portal', 'label' => 'Pengaturan'];
        }

        return null;
    }

    /**
     * Catat aktivitas login user yang berhasil.
     */
    public static function logLogin(Request $request, User $user): void
    {
        self::writeLog([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_role' => $user->role,
            'action' => 'login',
            'module' => 'auth',
            'menu_label' => 'Login Sistem',
            'path' => '/login',
            'description' => 'User berhasil login ke sistem',
            'metadata' => null,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * Catat kunjungan menu utama dengan cooldown session per menu.
     */
    public static function shouldRecordMenuVisit(Request $request, string $module): bool
    {
        $sessionKey = 'activity_menu_cooldown_'.$module;
        $lastRecorded = (int) $request->session()->get($sessionKey, 0);
        $nowTs = time();

        if ($lastRecorded > 0 && ($nowTs - $lastRecorded) < self::MENU_COOLDOWN_SECONDS) {
            return false;
        }

        $request->session()->put($sessionKey, $nowTs);

        return true;
    }

    /**
     * Catat event spesifik di Pemetaan PSB (ganti mode / export / buka detail wilayah).
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public static function logPsbEvent(Request $request, User $user, string $eventType, string $description, ?array $metadata = null): bool
    {
        $cooldownSeconds = $eventType === 'export_excel' ? 10 : self::PSB_MODE_COOLDOWN_SECONDS;
        $sessionKey = 'activity_psb_event_cooldown_'.$eventType;
        $lastRecorded = (int) $request->session()->get($sessionKey, 0);
        $nowTs = time();

        if ($lastRecorded > 0 && ($nowTs - $lastRecorded) < $cooldownSeconds) {
            return false;
        }

        $request->session()->put($sessionKey, $nowTs);

        self::writeLog([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_role' => $user->role,
            'action' => 'psb_'.$eventType,
            'module' => 'psb',
            'menu_label' => 'Pemetaan PSB',
            'path' => '/psb/ringkasan',
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return true;
    }

    /**
     * Simpan baris log secara aman dan jalankan auto-prune berkala (retensi 7 hari).
     *
     * @param  array<string, mixed>  $payload
     */
    public static function writeLog(array $payload): void
    {
        try {
            if (! Schema::hasTable('user_activity_logs')) {
                return;
            }

            UserActivityLog::query()->create($payload);
            UserActivityLog::pruneExpiredLogs();
        } catch (Throwable) {
            // Jangan sampai pencatatan log mengganggu alur utama user
        }
    }
}
