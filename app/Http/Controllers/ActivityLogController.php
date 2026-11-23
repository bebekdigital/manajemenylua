<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserActivityLog;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    /**
     * Tampilkan dashboard perbandingan pemakaian menu, Pemetaan PSB, dan riwayat log 7 hari (Superadmin only).
     */
    public function index(Request $request): Response
    {
        abort_if($request->user()?->role !== 'superadmin', 403, 'Hanya Superadmin yang dapat mengakses log aktivitas.');

        $tableReady = Schema::hasTable('user_activity_logs');
        $since = now()->subDays(UserActivityLog::RETENTION_DAYS);
        $mainMenus = ActivityLogger::mainMenus();

        if (! $tableReady) {
            return Inertia::render('ActivityLogs/Index', [
                'tableReady' => false,
                'retentionDays' => UserActivityLog::RETENTION_DAYS,
                'summary' => [
                    'active_users' => 0,
                    'total_logins' => 0,
                    'psb_users' => 0,
                    'psb_adoption_pct' => 0,
                    'psb_visits' => 0,
                    'psb_interactions' => 0,
                    'total_menu_visits' => 0,
                    'psb_share_pct' => 0,
                    'psb_rank' => null,
                    'total_rows_7d' => 0,
                ],
                'menuStats' => [],
                'psbBreakdown' => [
                    'visits' => 0,
                    'mode_welfare' => 0,
                    'mode_school' => 0,
                    'export_excel' => 0,
                ],
                'userAdoption' => [],
                'logs' => [
                    'data' => [],
                    'links' => [],
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => 0,
                    'from' => null,
                    'to' => null,
                ],
                'filterOptions' => [
                    'users' => [],
                    'modules' => [],
                ],
                'filters' => [
                    'module' => 'all',
                    'user_id' => 'all',
                    'action' => 'all',
                    'search' => '',
                ],
            ]);
        }

        // Bersihkan otomatis log > 7 hari saat halaman dibuka Superadmin
        UserActivityLog::pruneExpiredLogs(force: true);

        // Ambil ringkasan 7 hari terakhir (kolom minimal agar hemat memori & cepat)
        $recentRows = UserActivityLog::query()
            ->select(['id', 'user_id', 'user_name', 'user_role', 'action', 'module', 'menu_label', 'created_at'])
            ->where('created_at', '>=', $since)
            ->orderByDesc('created_at')
            ->get();

        // Hitung perbandingan antar menu utama (action = page_visit)
        $visitRows = $recentRows->where('action', 'page_visit');
        $totalMenuVisits = $visitRows->count();

        $menuStats = collect($mainMenus)
            ->map(function (array $meta, string $moduleKey) use ($visitRows, $totalMenuVisits) {
                $rows = $visitRows->where('module', $moduleKey);
                $visits = $rows->count();
                $uniqueUsers = $rows->pluck('user_id')->filter()->unique()->count();
                $lastVisitedAt = $rows->first()?->created_at?->toIso8601String();

                return [
                    'module' => $moduleKey,
                    'label' => $meta['label'],
                    'category' => $meta['category'],
                    'visits' => $visits,
                    'unique_users' => $uniqueUsers,
                    'share_pct' => $totalMenuVisits > 0 ? round(($visits / $totalMenuVisits) * 100, 1) : 0,
                    'last_visited_at' => $lastVisitedAt,
                    'is_psb' => $moduleKey === 'psb',
                ];
            })
            ->sortBy([
                ['visits', 'desc'],
                ['unique_users', 'desc'],
                ['label', 'asc'],
            ])
            ->values();

        $psbRankIndex = $menuStats->search(fn (array $item) => $item['module'] === 'psb');
        $psbMenuStat = $menuStats->firstWhere('module', 'psb');

        $psbRows = $recentRows->where('module', 'psb');
        $psbBreakdown = [
            'visits' => $psbRows->where('action', 'page_visit')->count(),
            'mode_welfare' => $psbRows->where('action', 'psb_mode_welfare')->count(),
            'mode_school' => $psbRows->where('action', 'psb_mode_school')->count(),
            'export_excel' => $psbRows->where('action', 'psb_export_excel')->count(),
        ];

        $loginRows = $recentRows->where('action', 'login');
        $activeUserIds = $recentRows->pluck('user_id')->filter()->unique();
        $psbUserIds = $psbRows->pluck('user_id')->filter()->unique();

        // Rekap per user yang aktif dalam 7 hari (agar terlihat siapa yg login vs yg buka Pemetaan PSB)
        $userAdoption = $recentRows
            ->filter(fn ($row) => ! empty($row->user_id))
            ->groupBy('user_id')
            ->map(function ($rows, $userId) {
                $first = $rows->first();
                $logins = $rows->where('action', 'login');
                $visits = $rows->where('action', 'page_visit');
                $psb = $rows->where('module', 'psb');

                $topMenus = $visits
                    ->groupBy('menu_label')
                    ->map(fn ($g, $label) => ['label' => $label, 'count' => $g->count()])
                    ->sortByDesc('count')
                    ->take(3)
                    ->values()
                    ->all();

                return [
                    'user_id' => (int) $userId,
                    'user_name' => $first->user_name ?: '-',
                    'user_role' => $first->user_role ?: '-',
                    'login_count' => $logins->count(),
                    'last_login_at' => $logins->first()?->created_at?->toIso8601String(),
                    'menu_visits' => $visits->count(),
                    'psb_visits' => $psb->where('action', 'page_visit')->count(),
                    'psb_actions' => $psb->where('action', '!=', 'page_visit')->count(),
                    'psb_last_at' => $psb->first()?->created_at?->toIso8601String(),
                    'last_activity_at' => $first->created_at?->toIso8601String(),
                    'top_menus' => $topMenus,
                ];
            })
            ->sortByDesc(fn (array $u) => ($u['psb_visits'] * 10000) + ($u['menu_visits'] * 100) + $u['login_count'])
            ->values()
            ->all();

        // Query tabel log detail dengan pagination
        $selectedModule = (string) $request->input('module', 'all');
        $selectedUserId = (string) $request->input('user_id', 'all');
        $selectedAction = (string) $request->input('action', 'all');
        $search = trim((string) $request->input('search', ''));

        $logsQuery = UserActivityLog::query()
            ->where('created_at', '>=', $since);

        if ($selectedModule !== '' && $selectedModule !== 'all') {
            $logsQuery->where('module', $selectedModule);
        }
        if ($selectedUserId !== '' && $selectedUserId !== 'all' && ctype_digit($selectedUserId)) {
            $logsQuery->where('user_id', (int) $selectedUserId);
        }
        if ($selectedAction !== '' && $selectedAction !== 'all') {
            if ($selectedAction === 'psb_action') {
                $logsQuery->where('action', 'like', 'psb_%');
            } else {
                $logsQuery->where('action', $selectedAction);
            }
        }
        if ($search !== '') {
            $logsQuery->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                    ->orWhere('menu_label', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('path', 'like', "%{$search}%");
            });
        }

        $logs = $logsQuery
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (UserActivityLog $log) => [
                'id' => $log->id,
                'user_id' => $log->user_id,
                'user_name' => $log->user_name ?: '-',
                'user_role' => $log->user_role ?: '-',
                'action' => $log->action,
                'module' => $log->module,
                'menu_label' => $log->menu_label,
                'path' => $log->path,
                'description' => $log->description,
                'metadata' => $log->metadata,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->toIso8601String(),
                'created_at_human' => $log->created_at?->translatedFormat('d M Y, H:i'),
            ]);

        $userFilterOptions = User::query()
            ->select(['id', 'name', 'role'])
            ->whereIn('id', $activeUserIds->all())
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => (string) $u->id,
                'name' => $u->name,
                'role' => $u->role,
            ])
            ->values()
            ->all();

        $moduleFilterOptions = array_merge(
            [['value' => 'auth', 'label' => 'Login Sistem']],
            collect($mainMenus)
                ->map(fn (array $meta, string $key) => ['value' => $key, 'label' => $meta['label']])
                ->values()
                ->all()
        );

        $activeUsersCount = $activeUserIds->count();
        $psbUsersCount = $psbUserIds->count();

        return Inertia::render('ActivityLogs/Index', [
            'tableReady' => true,
            'retentionDays' => UserActivityLog::RETENTION_DAYS,
            'summary' => [
                'active_users' => $activeUsersCount,
                'total_logins' => $loginRows->count(),
                'psb_users' => $psbUsersCount,
                'psb_adoption_pct' => $activeUsersCount > 0 ? round(($psbUsersCount / $activeUsersCount) * 100, 1) : 0,
                'psb_visits' => $psbMenuStat['visits'] ?? 0,
                'psb_interactions' => $psbRows->where('action', '!=', 'page_visit')->count(),
                'total_menu_visits' => $totalMenuVisits,
                'psb_share_pct' => $psbMenuStat['share_pct'] ?? 0,
                'psb_rank' => $psbRankIndex !== false ? ($psbRankIndex + 1) : null,
                'total_rows_7d' => $recentRows->count(),
            ],
            'menuStats' => $menuStats->all(),
            'psbBreakdown' => $psbBreakdown,
            'userAdoption' => $userAdoption,
            'logs' => $logs,
            'filterOptions' => [
                'users' => $userFilterOptions,
                'modules' => $moduleFilterOptions,
            ],
            'filters' => [
                'module' => $selectedModule,
                'user_id' => $selectedUserId,
                'action' => $selectedAction,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Rekam event penggunaan fitur kunci pada Pemetaan PSB secara ringan.
     */
    public function trackPsbEvent(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['recorded' => false], 401);
        }

        $validated = $request->validate([
            'event' => ['required', 'string', 'in:mode_school,mode_welfare,export_excel'],
            'mode' => ['nullable', 'string', 'in:school,welfare'],
        ]);

        $descriptions = [
            'mode_school' => 'Menggunakan tab Asal Sekolah di Pemetaan PSB',
            'mode_welfare' => 'Menggunakan tab Domisili di Pemetaan PSB',
            'export_excel' => 'Mengunduh data Excel Pemetaan PSB',
        ];

        $metadata = ! empty($validated['mode']) ? ['mode' => $validated['mode']] : null;
        $recorded = ActivityLogger::logPsbEvent(
            $request,
            $user,
            $validated['event'],
            $descriptions[$validated['event']],
            $metadata
        );

        return response()->json(['recorded' => $recorded]);
    }

    /**
     * Bersihkan log yang berusia lebih dari 7 hari secara manual oleh Superadmin.
     */
    public function pruneNow(Request $request): RedirectResponse
    {
        abort_if($request->user()?->role !== 'superadmin', 403);

        $deleted = UserActivityLog::pruneExpiredLogs(force: true);

        return back()->with('success', "Pembersihan selesai: {$deleted} riwayat log lebih dari ".UserActivityLog::RETENTION_DAYS.' hari telah dihapus.');
    }
}
