<?php

namespace App\Http\Middleware;

use App\Models\RolePermission;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $canViewEmployee = $user ? RolePermission::canAccess($user, 'employee_detail_history', 'view') : false;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->employeeProfile()?->nama ?: $user->name,
                    'username' => $user->username,
                    'role' => $user->role,
                    'must_change_password' => (bool) ($user->must_change_password ?? false),
                    'can_view_employee' => $canViewEmployee,
                ] : null,
                'permissions' => [
                    'can_view_employee' => $canViewEmployee,
                ],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'import_summary' => fn () => $request->session()->get('import_summary'),
            ],
        ];
    }
}
