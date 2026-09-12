<?php

namespace App\Http\Middleware;

use App\Models\AcademicYear;
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
        $selectedYearId = $request->query('academic_year_id') ?? session('selected_academic_year_id');
        $selectedYear = $selectedYearId
            ? AcademicYear::find($selectedYearId)
            : (AcademicYear::current() ?? AcademicYear::first());

        if ($selectedYear && session('selected_academic_year_id') !== $selectedYear->id) {
            session(['selected_academic_year_id' => $selectedYear->id]);
        }

        return [
            ...parent::share($request),
            'academicYears' => fn () => AcademicYear::orderByDesc('name')
                ->orderByDesc('semester')
                ->get()
                ->map(fn ($year) => [
                    'id' => $year->id,
                    'name' => $year->name,
                    'semester' => $year->semester,
                    'is_active' => (bool) $year->is_active,
                ]),
            'selectedAcademicYear' => $selectedYear ? [
                'id' => $selectedYear->id,
                'name' => $selectedYear->name,
                'semester' => $selectedYear->semester,
                'is_active' => (bool) $selectedYear->is_active,
            ] : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'import_summary' => fn () => $request->session()->get('import_summary'),
            ],
        ];
    }
}
