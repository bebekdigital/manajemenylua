<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    private const PENDING_ATTRIBUTE = '_pending_activity_log';

    /**
     * Evaluasi kunjungan menu saat sesi masih aktif, lalu tunda penulisan ke fase terminate().
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldInspectRequest($request, $response)) {
            return $response;
        }

        $user = $request->user();
        $path = '/'.ltrim($request->path(), '/');
        $target = ActivityLogger::resolveMenuTarget($path);

        if (! $user || ! $target) {
            return $response;
        }

        if (! ActivityLogger::shouldRecordMenuVisit($request, $target['module'])) {
            return $response;
        }

        $request->attributes->set(self::PENDING_ATTRIBUTE, [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_role' => $user->role,
            'action' => 'page_visit',
            'module' => $target['module'],
            'menu_label' => $target['label'],
            'path' => $path,
            'description' => 'Membuka menu '.$target['label'],
            'metadata' => null,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return $response;
    }

    /**
     * Tulis log ke database setelah response dikirim ke browser (Terminable Middleware).
     */
    public function terminate(Request $request, Response $response): void
    {
        $payload = $request->attributes->get(self::PENDING_ATTRIBUTE);
        if (! is_array($payload)) {
            return;
        }

        $request->attributes->remove(self::PENDING_ATTRIBUTE);
        ActivityLogger::writeLog($payload);
    }

    protected function shouldInspectRequest(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || ! $request->user()) {
            return false;
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            return false;
        }

        // Lewati prefetch browser / Inertia partial reload / request JSON non-halaman
        if (
            $request->header('X-Inertia-Partial-Data') ||
            $request->header('X-Inertia-Prefetch') ||
            strtolower((string) $request->header('Purpose', '')) === 'prefetch'
        ) {
            return false;
        }

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return false;
        }

        return true;
    }
}
