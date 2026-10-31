<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeePosition;
use App\Models\RolePermission;
use App\Models\SchoolUnit;
use App\Services\EmployeeImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeeController extends Controller
{
    /**
     * Display the employee directory page.
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();
        if (! RolePermission::canAccess($user, 'employee_directory', 'view')) {
            abort(403, 'Akses ditolak: Role Anda tidak memiliki izin untuk melihat direktori pegawai.');
        }

        $canCreate = RolePermission::canAccess($user, 'employee_directory', 'create');
        $canUpdate = RolePermission::canAccess($user, 'employee_directory', 'update');
        $canExport = RolePermission::canAccess($user, 'employee_directory', 'export');
        $canImport = RolePermission::canAccess($user, 'employee_directory', 'import');

        $isTeacher = $user?->isTeacher() ?? false;
        $myEmployee = $isTeacher ? $user?->employeeProfile() : null;

        $selectedYearId = $request->query('academic_year_id') ?? session('selected_academic_year_id');
        $selectedYear = $selectedYearId
            ? AcademicYear::find($selectedYearId)
            : (AcademicYear::current() ?? AcademicYear::first());

        $query = Employee::with([
            'records',
            'positions',
            'educationHistories',
            'trainingHistories',
        ])->orderBy('nama');

        // Batasi akses: Role Guru hanya bisa melihat data pegawai atas nama dia sendiri
        if ($isTeacher) {
            if ($myEmployee) {
                $query->where('id', $myEmployee->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $employees = $query
            ->get()
            ->map(function (Employee $employee) {
                return $this->mapEmployeeForList($employee);
            });

        return Inertia::render('Staff', [
            'employees' => $employees,
            'isTeacher' => $isTeacher,
            'permissions' => [
                'can_create' => $canCreate,
                'can_update' => $canUpdate,
                'can_export' => $canExport,
                'can_import' => $canImport,
            ],
            'teacherEmployee' => $myEmployee ? [
                'id' => $myEmployee->id,
                'nama' => $myEmployee->nama,
            ] : null,
            'selectedAcademicYear' => $selectedYear ? [
                'id' => $selectedYear->id,
                'name' => $selectedYear->name,
                'semester' => $selectedYear->semester,
            ] : null,
        ]);
    }

    /**
     * Display the employee detail page.
     */
    public function show(string $id, Request $request): Response
    {
        $user = auth()->user();
        if (! RolePermission::canAccess($user, 'employee_detail_history', 'view')) {
            abort(403, 'Akses ditolak: Role Anda tidak memiliki izin untuk melihat detail profil pegawai.');
        }

        if ($user?->isTeacher()) {
            $myEmployee = $user->employeeProfile();
            if (! $myEmployee || (string) $myEmployee->id !== (string) $id) {
                abort(403, 'Akses ditolak: Sebagai Guru, Anda hanya dapat melihat data profil pegawai atas nama Anda sendiri.');
            }
        }

        $selectedYearId = $request->query('academic_year_id') ?? session('selected_academic_year_id');
        $selectedYear = $selectedYearId
            ? AcademicYear::find($selectedYearId)
            : (AcademicYear::current() ?? AcademicYear::first());

        $employee = Employee::with([
            'educationHistories',
            'trainingHistories',
            'records',
            'positions',
            'documents',
            'permits.updater',
        ])->findOrFail($id);

        $latestPosition = $employee->positions->sortByDesc('tmt_jabatan')->first();
        $latestRecord = $employee->records->sortByDesc('tmt_kenaikan_jenjang')->first();

        $allYears = AcademicYear::orderByDesc('name')->orderByDesc('semester')->get();

        $employeeData = [
            'id' => $employee->id,
            'nipy' => $employee->nipy,
            'nama' => $employee->nama,
            'gelar_depan' => $employee->gelar_depan,
            'gelar_belakang' => $employee->gelar_belakang,
            'nama_lengkap_gelar' => $employee->nama,
            'jk' => $employee->jk,
            'ttl' => $employee->ttl,
            'tempat_lahir' => $employee->tempat_lahir,
            'tanggal_lahir' => $employee->tanggal_lahir?->format('Y-m-d'),
            'nik' => $employee->nik,
            'no_wa' => $employee->no_wa,
            'email' => $employee->email,

            // Alamat Domisili
            'jalan_domisili' => $employee->jalan_domisili,
            'rt_rw_domisili' => $employee->rt_rw_domisili,
            'dusun_domisili' => $employee->dusun_domisili,
            'desa_domisili' => $employee->desa_domisili,
            'kecamatan_domisili' => $employee->kecamatan_domisili,
            'kab_domisili' => $employee->kab_domisili,
            'provinsi_domisili' => $employee->provinsi_domisili,

            // Alamat Tinggal
            'jalan_tinggal' => $employee->jalan_tinggal,
            'rt_rw_tinggal' => $employee->rt_rw_tinggal,
            'dusun_tinggal' => $employee->dusun_tinggal,
            'desa_tinggal' => $employee->desa_tinggal,
            'kecamatan_tinggal' => $employee->kecamatan_tinggal,
            'kabupaten_tinggal' => $employee->kabupaten_tinggal,
            'provinsi_tinggal' => $employee->provinsi_tinggal,
            'status_rumah' => $employee->status_rumah,
            'kepemilikan_bpjs' => $employee->kepemilikan_bpjs,
            'penanggung_bpjs' => $employee->penanggung_bpjs,

            // Profil & Keluarga
            'skill' => $employee->skill,
            'status_pernikahan' => $employee->status_pernikahan,
            'nama_suami_istri' => $employee->nama_suami_istri,
            'ttl_suami_istri' => $employee->ttl_suami_istri,
            'pekerjaan_suami_istri' => $employee->pekerjaan_suami_istri,
            'tanggal_menikah' => $employee->tanggal_menikah?->format('Y-m-d'),
            'formatted_tanggal_menikah' => $employee->tanggal_menikah?->translatedFormat('d F Y'),
            'jumlah_anak' => $employee->jumlah_anak,
            'nama_ibu' => $employee->nama_ibu,
            'nama_ayah' => $employee->nama_ayah,
            'alamat_orangtua' => $employee->alamat_orangtua,
            'kontak_darurat' => $employee->kontak_darurat,
            'hubungan_kontak_darurat' => $employee->hubungan_kontak_darurat,

            // Data Statis Kepegawaian (TMT Pengangkatan, TST Pengangkatan, Masa Kerja, Dapodik, Keaktifan)
            'tmt_pengangkatan' => $employee->tmt_pengangkatan?->format('Y-m-d'),
            'formatted_tmt_pengangkatan' => $employee->tmt_pengangkatan?->translatedFormat('d F Y'),
            'tst_pengangkatan' => $employee->tst_pengangkatan?->format('Y-m-d'),
            'formatted_tst_pengangkatan' => $employee->tst_pengangkatan?->translatedFormat('d F Y'),
            'masa_kerja' => $employee->masa_kerja,
            'keaktifan_dapodik' => $employee->keaktifan_dapodik,
            'unit_keaktifan_dapodik' => $employee->unit_keaktifan_dapodik,
            'status_keaktifan' => $employee->status_keaktifan ?? 'Aktif',
            'keterangan_tidak_aktif' => $employee->keterangan_tidak_aktif,

            // Kepegawaian & Penugasan aktif
            'unit_kerja' => $latestPosition?->unit_kerja,
            'jabatan' => $latestPosition?->jabatan,
            'jenis_kepegawaian' => $latestPosition?->jenis_kepegawaian,
            'tmt_jabatan' => $latestPosition?->tmt_jabatan?->format('Y-m-d'),
            'formatted_tmt_jabatan' => $latestPosition?->tmt_jabatan?->translatedFormat('d F Y'),
            'tst_jabatan' => $latestPosition?->tst_jabatan?->format('Y-m-d'),
            'formatted_tst_jabatan' => $latestPosition?->tst_jabatan?->translatedFormat('d F Y'),

            'jenjang_kepegawaian' => $latestRecord?->jenjang_kepegawaian,
            'tmt_kenaikan_jenjang' => $latestRecord?->tmt_kenaikan_jenjang?->format('Y-m-d'),
            'formatted_tmt_kenaikan_jenjang' => $latestRecord?->tmt_kenaikan_jenjang?->translatedFormat('d F Y'),
            'tst_kenaikan_jenjang' => $latestRecord?->tst_kenaikan_jenjang?->format('Y-m-d'),
            'formatted_tst_kenaikan_jenjang' => $latestRecord?->tst_kenaikan_jenjang?->translatedFormat('d F Y'),
            'tst_jenjang' => $latestRecord?->tst_kenaikan_jenjang?->format('Y-m-d'),
            'formatted_tst_jenjang' => $latestRecord?->tst_kenaikan_jenjang?->translatedFormat('d F Y'),

            // Dokumen Pokok & Khusus
            'dokumen_ktp' => $employee->documents->firstWhere('category', 'ktp'),
            'dokumen_kk' => $employee->documents->firstWhere('category', 'kk'),
            'dokumen_sk_pengangkatan' => $employee->documents->firstWhere('category', 'sk_pengangkatan'),

            // Sheet 3: Riwayat Kepegawaian (Jenjang) dengan dokumen SK Jenjang terkait
            'riwayat_kepegawaian' => $employee->records->map(fn ($r) => [
                'id' => $r->id,
                'jenjang_kepegawaian' => $r->jenjang_kepegawaian,
                'no_sk' => $r->no_sk,
                'tmt_kenaikan_jenjang' => $r->tmt_kenaikan_jenjang?->format('Y-m-d'),
                'formatted_tmt_kenaikan_jenjang' => $r->tmt_kenaikan_jenjang?->translatedFormat('d F Y'),
                'tst_kenaikan_jenjang' => $r->tst_kenaikan_jenjang?->format('Y-m-d'),
                'formatted_tst_kenaikan_jenjang' => $r->tst_kenaikan_jenjang?->translatedFormat('d F Y'),
                'tst_jenjang' => $r->tst_kenaikan_jenjang?->format('Y-m-d'),
                'formatted_tst_jenjang' => $r->tst_kenaikan_jenjang?->translatedFormat('d F Y'),
                'document' => $employee->documents->first(fn ($d) => $d->record_type === 'record' && $d->record_id == $r->id),
            ])->sortByDesc('tmt_kenaikan_jenjang')->values()->toArray(),

            // Sheet 4: Riwayat Jabatan (Penugasan) dengan dokumen SK Penugasan terkait
            'riwayat_jabatan' => $employee->positions->map(fn ($p) => [
                'id' => $p->id,
                'unit_kerja' => $p->unit_kerja,
                'jabatan' => $p->jabatan,
                'jenis_kepegawaian' => $p->jenis_kepegawaian,
                'no_sk' => $p->no_sk,
                'tmt_jabatan' => $p->tmt_jabatan?->format('Y-m-d'),
                'formatted_tmt_jabatan' => $p->tmt_jabatan?->translatedFormat('d F Y'),
                'tst_jabatan' => $p->tst_jabatan?->format('Y-m-d'),
                'formatted_tst_jabatan' => $p->tst_jabatan?->translatedFormat('d F Y'),
                'document' => $employee->documents->first(fn ($d) => $d->record_type === 'position' && $d->record_id == $p->id),
            ])->sortByDesc('tmt_jabatan')->values()->toArray(),

            // Riwayat Pendidikan dengan dokumen Ijazah terkait
            'riwayat_pendidikan' => $employee->educationHistories->map(fn ($e) => [
                'id' => $e->id,
                'jenjang_pendidikan' => $e->jenjang_pendidikan,
                'jurusan' => $e->jurusan,
                'instansi_pendidikan' => $e->instansi_pendidikan,
                'tahun_lulus' => $e->tahun_lulus,
                'pembiayaan' => $e->pembiayaan,
                'document' => $employee->documents->first(fn ($d) => $d->record_type === 'education' && $d->record_id == $e->id),
            ])->toArray(),

            // Riwayat Pelatihan dan Pendidikan Nonformal dengan dokumen Sertifikat terkait
            'riwayat_pelatihan' => $employee->trainingHistories->map(fn ($t) => [
                'id' => $t->id,
                'bidang' => $t->bidang,
                'keahlian' => $t->keahlian,
                'instansi_penyelenggara' => $t->instansi_penyelenggara,
                'tahun' => $t->tahun,
                'pembiayaan' => $t->pembiayaan,
                'document' => $employee->documents->first(fn ($d) => $d->record_type === 'training' && $d->record_id == $t->id),
            ])->toArray(),

            // Dokumen Ijazah tambahan / bebas (misal S2 atau sertifikat lain tanpa record_id khusus)
            'dokumen_ijazah_tambahan' => $employee->documents->filter(fn ($d) => $d->category === 'ijazah' && empty($d->record_id))->values()->toArray(),

            // Seluruh dokumen
            'all_documents' => $employee->documents->values()->toArray(),

            // Riwayat & Data Perizinan Pegawai
            'perizinan' => $employee->permits->sortByDesc('tanggal')->values()->map(fn ($p) => [
                'id' => $p->id,
                'tanggal' => $p->tanggal?->format('Y-m-d'),
                'tanggal_selesai' => $p->tanggal_selesai?->format('Y-m-d'),
                'formatted_tanggal' => $p->formatted_tanggal,
                'jenis' => $p->jenis,
                'detail' => $p->detail,
                'keterangan' => $p->keterangan,
                'lampiran_path' => $p->lampiran_path,
                'lampiran_nama' => $p->lampiran_nama,
                'preview_url' => $p->preview_url,
                'download_url' => $p->download_url,
                'status' => $p->status,
                'catatan' => $p->catatan,
                'updater_name' => $p->updater?->name,
                'updater_role' => $p->updater?->role,
                'created_at' => $p->created_at?->format('Y-m-d H:i:s'),
                'formatted_created_at' => $p->created_at?->translatedFormat('d M Y H:i'),
            ])->toArray(),
        ];

        $departments = Schema::hasTable('departments') && Schema::hasTable('department_positions')
            ? Department::with('positions')->orderBy('order')->orderBy('name')->get()->map(fn ($dept) => [
                'id' => $dept->id,
                'name' => $dept->name,
                'positions' => $dept->relationLoaded('positions')
                    ? $dept->positions->pluck('name')->values()->toArray()
                    : [],
            ])->toArray()
            : [];

        $deptKeyed = collect($departments)->keyBy('name');

        $schoolUnits = Schema::hasTable('school_units')
            ? SchoolUnit::orderBy('name')->get()->map(function ($unit) use ($deptKeyed) {
                $dept = $deptKeyed->get($unit->name);

                return [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'positions' => $dept ? $dept['positions'] : [],
                ];
            })->toArray()
            : [];

        $units = array_column($schoolUnits, 'name');
        $deptNames = array_column($departments, 'name');
        $posUnits = EmployeePosition::whereNotNull('unit_kerja')->distinct()->pluck('unit_kerja')->toArray();
        $availableUnits = array_values(array_unique(array_filter(array_merge($units, $deptNames, $posUnits))));
        sort($availableUnits);

        $isTeacher = $user?->isTeacher() ?? false;
        $myEmployee = $isTeacher ? $user?->employeeProfile() : null;

        $employeeListQuery = Employee::query()->orderBy('nama');
        if ($isTeacher) {
            if ($myEmployee) {
                $employeeListQuery->where('id', $myEmployee->id);
            } else {
                $employeeListQuery->whereRaw('1 = 0');
            }
        }

        $allAccessibleEmployees = $employeeListQuery
            ->with(['positions' => fn ($q) => $q->latest('tmt_jabatan')])
            ->select(['id', 'nama', 'nipy'])
            ->get()
            ->map(function ($emp) {
                $latestPos = $emp->positions->first();

                return [
                    'id' => $emp->id,
                    'nama' => $emp->nama,
                    'nipy' => $emp->nipy,
                    'jabatan' => $latestPos?->jabatan,
                    'unit_kerja' => $latestPos?->unit_kerja,
                ];
            })
            ->toArray();

        return Inertia::render('StaffDetail', [
            'employee' => $employeeData,
            'allEmployees' => $allAccessibleEmployees,
            'permissions' => [
                'can_update' => RolePermission::canAccess($user, 'employee_detail_history', 'update'),
                'can_delete' => RolePermission::canAccess($user, 'employee_detail_history', 'delete'),
                'can_view_kepegawaian' => RolePermission::canAccess($user, 'employee_kepegawaian', 'view'),
                'can_update_kepegawaian' => RolePermission::canAccess($user, 'employee_kepegawaian', 'update'),
            ],
            'selectedAcademicYear' => $selectedYear ? [
                'id' => $selectedYear->id,
                'name' => $selectedYear->name,
                'semester' => $selectedYear->semester,
            ] : null,
            'allAcademicYears' => $allYears->map(fn ($y) => [
                'id' => $y->id,
                'name' => $y->name,
                'semester' => $y->semester,
            ])->toArray(),
            'availableUnits' => $availableUnits,
            'schoolUnits' => $schoolUnits,
            'departments' => $departments,
        ]);
    }

    /**
     * Update the specified employee in storage.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $user = auth()->user();
        if (! RolePermission::canAccess($user, 'employee_detail_history', 'update')) {
            abort(403, 'Akses ditolak: Role Anda tidak memiliki izin untuk mengubah data pegawai.');
        }

        if ($user?->isTeacher()) {
            $myEmployee = $user->employeeProfile();
            if (! $myEmployee || (string) $myEmployee->id !== (string) $id) {
                abort(403, 'Akses ditolak: Sebagai Guru, Anda hanya dapat mengubah data profil pegawai atas nama Anda sendiri.');
            }
        }

        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            // Identitas
            'nama' => ['required', 'string', 'max:255'],
            'gelar_depan' => ['nullable', 'string', 'max:50'],
            'gelar_belakang' => ['nullable', 'string', 'max:50'],
            'nipy' => ['nullable', 'string', 'max:50'],
            'nik' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('employees', 'nik')->ignore($employee->id),
            ],
            'jk' => ['nullable', 'in:L,P'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date'],
            'no_wa' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'skill' => ['nullable', 'string', 'max:255'],

            // Alamat Domisili
            'jalan_domisili' => ['nullable', 'string', 'max:255'],
            'rt_rw_domisili' => ['nullable', 'string', 'max:50'],
            'dusun_domisili' => ['nullable', 'string', 'max:100'],
            'desa_domisili' => ['nullable', 'string', 'max:100'],
            'kecamatan_domisili' => ['nullable', 'string', 'max:100'],
            'kab_domisili' => ['nullable', 'string', 'max:100'],
            'provinsi_domisili' => ['nullable', 'string', 'max:100'],

            // Alamat Tinggal
            'jalan_tinggal' => ['nullable', 'string', 'max:255'],
            'rt_rw_tinggal' => ['nullable', 'string', 'max:50'],
            'dusun_tinggal' => ['nullable', 'string', 'max:100'],
            'desa_tinggal' => ['nullable', 'string', 'max:100'],
            'kecamatan_tinggal' => ['nullable', 'string', 'max:100'],
            'kabupaten_tinggal' => ['nullable', 'string', 'max:100'],
            'provinsi_tinggal' => ['nullable', 'string', 'max:100'],
            'status_rumah' => ['nullable', 'string', 'max:50'],
            'kepemilikan_bpjs' => ['nullable', 'string', 'max:50'],
            'penanggung_bpjs' => ['nullable', 'string', 'max:100'],

            // Keluarga
            'status_pernikahan' => ['nullable', 'string', 'max:50'],
            'nama_suami_istri' => ['nullable', 'string', 'max:255'],
            'ttl_suami_istri' => ['nullable', 'string', 'max:100'],
            'pekerjaan_suami_istri' => ['nullable', 'string', 'max:100'],
            'tanggal_menikah' => ['nullable', 'date'],
            'jumlah_anak' => ['nullable', 'integer', 'min:0'],
            'nama_ibu' => ['nullable', 'string', 'max:255'],
            'nama_ayah' => ['nullable', 'string', 'max:255'],
            'alamat_orangtua' => ['nullable', 'string', 'max:255'],
            'kontak_darurat' => ['nullable', 'string', 'max:50'],
            'hubungan_kontak_darurat' => ['nullable', 'string', 'max:100'],

            // Data Statis Kepegawaian (Sheet 1)
            'tmt_pengangkatan' => ['nullable', 'date'],
            'tst_pengangkatan' => ['nullable', 'date'],
            'keaktifan_dapodik' => ['nullable', 'string', 'max:50'],
            'unit_keaktifan_dapodik' => ['nullable', 'string', 'max:100'],
            'status_keaktifan' => ['nullable', 'string', 'max:50'],
            'keterangan_tidak_aktif' => ['nullable', 'string', 'max:255'],

            // Kepegawaian (Sheet 3)
            'jenjang_kepegawaian' => ['nullable', 'string', 'max:50'],
            'tmt_kenaikan_jenjang' => ['nullable', 'date'],
            'tst_kenaikan_jenjang' => ['nullable', 'date'],
            'tst_jenjang' => ['nullable', 'date'],

            // Jabatan (Sheet 4)
            'unit_kerja' => ['nullable', 'string', 'max:100'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'jenis_kepegawaian' => ['nullable', 'string', 'max:100'],
            'tmt_jabatan' => ['nullable', 'date'],
            'tst_jabatan' => ['nullable', 'date'],

            // Riwayat Pendidikan
            'riwayat_pendidikan' => ['nullable', 'array'],
            'riwayat_pendidikan.*.jenjang_pendidikan' => ['nullable', 'string', 'max:50'],
            'riwayat_pendidikan.*.jurusan' => ['nullable', 'string', 'max:100'],
            'riwayat_pendidikan.*.instansi_pendidikan' => ['nullable', 'string', 'max:150'],
            'riwayat_pendidikan.*.tahun_lulus' => ['nullable', 'string', 'max:20'],
            'riwayat_pendidikan.*.pembiayaan' => ['nullable', 'string', 'max:50'],

            // Riwayat Kepegawaian array (opsional jika diedit multi-row)
            'riwayat_kepegawaian' => ['nullable', 'array'],
            'riwayat_kepegawaian.*.id' => ['nullable'],
            'riwayat_kepegawaian.*.jenjang_kepegawaian' => ['nullable', 'string', 'max:50'],
            'riwayat_kepegawaian.*.no_sk' => ['nullable', 'string', 'max:100'],
            'riwayat_kepegawaian.*.tmt_kenaikan_jenjang' => ['nullable', 'date'],
            'riwayat_kepegawaian.*.tst_kenaikan_jenjang' => ['nullable', 'date'],
            // Riwayat Jabatan array (opsional jika diedit multi-row)
            'riwayat_jabatan' => ['nullable', 'array'],
            'riwayat_jabatan.*.id' => ['nullable'],
            'riwayat_jabatan.*.unit_kerja' => ['nullable', 'string', 'max:100'],
            'riwayat_jabatan.*.jabatan' => ['nullable', 'string', 'max:100'],
            'riwayat_jabatan.*.jenis_kepegawaian' => ['nullable', 'string', 'max:100'],
            'riwayat_jabatan.*.no_sk' => ['nullable', 'string', 'max:100'],
            'riwayat_jabatan.*.tmt_jabatan' => ['nullable', 'date'],
            'riwayat_jabatan.*.tst_jabatan' => ['nullable', 'date'],
            // Riwayat Pendidikan array
            'riwayat_pendidikan' => ['nullable', 'array'],
            'riwayat_pendidikan.*.id' => ['nullable'],
            'riwayat_pendidikan.*.jenjang_pendidikan' => ['nullable', 'string', 'max:50'],
            'riwayat_pendidikan.*.jurusan' => ['nullable', 'string', 'max:100'],
            'riwayat_pendidikan.*.instansi_pendidikan' => ['nullable', 'string', 'max:150'],
            'riwayat_pendidikan.*.tahun_lulus' => ['nullable', 'string', 'max:20'],
            'riwayat_pendidikan.*.pembiayaan' => ['nullable', 'string', 'max:50'],
            // Riwayat Pelatihan dan Nonformal array
            'riwayat_pelatihan' => ['nullable', 'array'],
            'riwayat_pelatihan.*.id' => ['nullable'],
            'riwayat_pelatihan.*.bidang' => ['nullable', 'string', 'max:150'],
            'riwayat_pelatihan.*.keahlian' => ['nullable', 'string', 'max:150'],
            'riwayat_pelatihan.*.instansi_penyelenggara' => ['nullable', 'string', 'max:150'],
            'riwayat_pelatihan.*.tahun' => ['nullable', 'string', 'max:20'],
            'riwayat_pelatihan.*.pembiayaan' => ['nullable', 'string', 'max:100'],

            // Riwayat Perizinan array
            'riwayat_perizinan' => ['nullable', 'array'],
            'riwayat_perizinan.*.id' => ['nullable'],
            'riwayat_perizinan.*.tanggal' => ['nullable', 'date'],
            'riwayat_perizinan.*.tanggal_selesai' => ['nullable', 'date'],
            'riwayat_perizinan.*.jenis' => ['nullable', 'string', 'max:100'],
            'riwayat_perizinan.*.detail' => ['nullable', 'string', 'max:255'],
            'riwayat_perizinan.*.keterangan' => ['nullable', 'string'],
            'riwayat_perizinan.*.status' => ['nullable', 'string', 'in:diajukan,disetujui,ditolak'],
            'riwayat_perizinan.*.catatan' => ['nullable', 'string'],
            'riwayat_perizinan.*.lampiran' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'nik.unique' => 'NIK sudah digunakan oleh pegawai lain.',
        ]);

        $canUpdateKepegawaian = RolePermission::canAccess($user, 'employee_kepegawaian', 'update');
        if (! $canUpdateKepegawaian) {
            unset(
                $validated['nipy'],
                $validated['tmt_pengangkatan'],
                $validated['tst_pengangkatan'],
                $validated['keaktifan_dapodik'],
                $validated['unit_keaktifan_dapodik'],
                $validated['status_keaktifan'],
                $validated['keterangan_tidak_aktif'],
                $validated['jenjang_kepegawaian'],
                $validated['tmt_kenaikan_jenjang'],
                $validated['tst_kenaikan_jenjang'],
                $validated['tst_jenjang'],
                $validated['riwayat_kepegawaian']
            );
        }

        DB::transaction(function () use ($employee, $validated, $user, $request) {
            $employee->update(collect($validated)->only([
                'nama', 'gelar_depan', 'gelar_belakang', 'nipy', 'nik', 'jk', 'tempat_lahir', 'tanggal_lahir', 'no_wa', 'email', 'skill',
                'jalan_domisili', 'rt_rw_domisili', 'dusun_domisili', 'desa_domisili', 'kecamatan_domisili', 'kab_domisili', 'provinsi_domisili',
                'jalan_tinggal', 'rt_rw_tinggal', 'dusun_tinggal', 'desa_tinggal', 'kecamatan_tinggal', 'kabupaten_tinggal', 'provinsi_tinggal',
                'status_rumah', 'kepemilikan_bpjs', 'penanggung_bpjs',
                'status_pernikahan', 'nama_suami_istri', 'ttl_suami_istri', 'pekerjaan_suami_istri', 'tanggal_menikah', 'jumlah_anak',
                'nama_ibu', 'nama_ayah', 'alamat_orangtua', 'kontak_darurat', 'hubungan_kontak_darurat',
                'tmt_pengangkatan', 'tst_pengangkatan', 'keaktifan_dapodik', 'unit_keaktifan_dapodik', 'status_keaktifan', 'keterangan_tidak_aktif',
            ])->toArray());

            // Handle Jabatan / Penugasan (Sheet 4)
            if (isset($validated['riwayat_jabatan']) && is_array($validated['riwayat_jabatan'])) {
                $keptPositionIds = [];
                foreach ($validated['riwayat_jabatan'] as $pos) {
                    if (! empty($pos['unit_kerja']) || ! empty($pos['jabatan']) || ! empty($pos['tmt_jabatan'])) {
                        $posData = [
                            'unit_kerja' => $pos['unit_kerja'] ?? null,
                            'jabatan' => $pos['jabatan'] ?? null,
                            'jenis_kepegawaian' => $pos['jenis_kepegawaian'] ?? null,
                            'no_sk' => $pos['no_sk'] ?? null,
                            'tmt_jabatan' => $pos['tmt_jabatan'] ?? null,
                            'tst_jabatan' => $pos['tst_jabatan'] ?? null,
                        ];

                        if (! empty($pos['id'])) {
                            $existingPos = $employee->positions()->find($pos['id']);
                            if ($existingPos) {
                                $existingPos->update($posData);
                                $keptPositionIds[] = $existingPos->id;

                                continue;
                            }
                        }

                        $newPos = $employee->positions()->create($posData);
                        $keptPositionIds[] = $newPos->id;
                    }
                }

                $positionsToDelete = $employee->positions()->whereNotIn('id', $keptPositionIds)->get();
                foreach ($positionsToDelete as $delPos) {
                    $employee->documents()->where('record_type', 'position')->where('record_id', $delPos->id)->delete();
                    $delPos->delete();
                }
            } elseif (! empty($validated['unit_kerja']) || ! empty($validated['jabatan']) || ! empty($validated['tmt_jabatan'])) {
                $latestPos = $employee->positions()->latest('tmt_jabatan')->first();
                if ($latestPos) {
                    $latestPos->update([
                        'unit_kerja' => $validated['unit_kerja'] ?? null,
                        'jabatan' => $validated['jabatan'] ?? null,
                        'jenis_kepegawaian' => $validated['jenis_kepegawaian'] ?? null,
                        'tmt_jabatan' => $validated['tmt_jabatan'] ?? $latestPos->tmt_jabatan,
                        'tst_jabatan' => $validated['tst_jabatan'] ?? $latestPos->tst_jabatan,
                    ]);
                } else {
                    $employee->positions()->create([
                        'unit_kerja' => $validated['unit_kerja'] ?? null,
                        'jabatan' => $validated['jabatan'] ?? null,
                        'jenis_kepegawaian' => $validated['jenis_kepegawaian'] ?? null,
                        'tmt_jabatan' => $validated['tmt_jabatan'] ?? null,
                        'tst_jabatan' => $validated['tst_jabatan'] ?? null,
                    ]);
                }
            }

            // Handle Riwayat Kepegawaian (Sheet 3)
            $tstJenjangVal = $validated['tst_kenaikan_jenjang'] ?? ($validated['tst_jenjang'] ?? null);
            if (isset($validated['riwayat_kepegawaian']) && is_array($validated['riwayat_kepegawaian'])) {
                $keptRecordIds = [];
                foreach ($validated['riwayat_kepegawaian'] as $rec) {
                    if (! empty($rec['jenjang_kepegawaian']) || ! empty($rec['tmt_kenaikan_jenjang'])) {
                        $recordData = [
                            'jenjang_kepegawaian' => $rec['jenjang_kepegawaian'] ?? null,
                            'no_sk' => $rec['no_sk'] ?? null,
                            'tmt_kenaikan_jenjang' => $rec['tmt_kenaikan_jenjang'] ?? null,
                            'tst_kenaikan_jenjang' => $rec['tst_kenaikan_jenjang'] ?? ($rec['tst_jenjang'] ?? null),
                        ];

                        if (! empty($rec['id'])) {
                            $existingRecord = $employee->records()->find($rec['id']);
                            if ($existingRecord) {
                                $existingRecord->update($recordData);
                                $keptRecordIds[] = $existingRecord->id;

                                continue;
                            }
                        }

                        $newRecord = $employee->records()->create($recordData);
                        $keptRecordIds[] = $newRecord->id;
                    }
                }

                // Delete records that were removed by the user
                $recordsToDelete = $employee->records()->whereNotIn('id', $keptRecordIds)->get();
                foreach ($recordsToDelete as $delRec) {
                    $employee->documents()->where('record_type', 'record')->where('record_id', $delRec->id)->delete();
                    $delRec->delete();
                }
            } elseif (! empty($validated['jenjang_kepegawaian']) || ! empty($validated['tmt_kenaikan_jenjang'])) {
                $latestRec = $employee->records()->latest('tmt_kenaikan_jenjang')->first();
                if ($latestRec) {
                    $latestRec->update([
                        'jenjang_kepegawaian' => $validated['jenjang_kepegawaian'] ?? null,
                        'tmt_kenaikan_jenjang' => $validated['tmt_kenaikan_jenjang'] ?? $latestRec->tmt_kenaikan_jenjang,
                        'tst_kenaikan_jenjang' => $tstJenjangVal ?? $latestRec->tst_kenaikan_jenjang,
                    ]);
                } else {
                    $employee->records()->create([
                        'jenjang_kepegawaian' => $validated['jenjang_kepegawaian'] ?? null,
                        'tmt_kenaikan_jenjang' => $validated['tmt_kenaikan_jenjang'] ?? null,
                        'tst_kenaikan_jenjang' => $tstJenjangVal,
                    ]);
                }
            }

            // Handle Pendidikan (Sheet 2)
            if (isset($validated['riwayat_pendidikan']) && is_array($validated['riwayat_pendidikan'])) {
                $keptEducationIds = [];
                foreach ($validated['riwayat_pendidikan'] as $edu) {
                    if (! empty($edu['jenjang_pendidikan']) || ! empty($edu['instansi_pendidikan']) || ! empty($edu['jurusan'])) {
                        $eduData = [
                            'jenjang_pendidikan' => $edu['jenjang_pendidikan'] ?? null,
                            'jurusan' => $edu['jurusan'] ?? null,
                            'instansi_pendidikan' => $edu['instansi_pendidikan'] ?? null,
                            'tahun_lulus' => $edu['tahun_lulus'] ?? null,
                            'pembiayaan' => $edu['pembiayaan'] ?? null,
                        ];

                        if (! empty($edu['id'])) {
                            $existingEdu = $employee->educationHistories()->find($edu['id']);
                            if ($existingEdu) {
                                $existingEdu->update($eduData);
                                $keptEducationIds[] = $existingEdu->id;

                                continue;
                            }
                        }

                        $newEdu = $employee->educationHistories()->create($eduData);
                        $keptEducationIds[] = $newEdu->id;
                    }
                }

                $educationToDelete = $employee->educationHistories()->whereNotIn('id', $keptEducationIds)->get();
                foreach ($educationToDelete as $delEdu) {
                    $employee->documents()->where('record_type', 'education')->where('record_id', $delEdu->id)->delete();
                    $delEdu->delete();
                }
            }

            // Handle Pelatihan dan Pendidikan Nonformal
            if (isset($validated['riwayat_pelatihan']) && is_array($validated['riwayat_pelatihan'])) {
                $keptTrainingIds = [];
                foreach ($validated['riwayat_pelatihan'] as $training) {
                    if (! empty($training['bidang']) || ! empty($training['keahlian']) || ! empty($training['instansi_penyelenggara']) || ! empty($training['tahun'])) {
                        $trainingData = [
                            'bidang' => $training['bidang'] ?? null,
                            'keahlian' => $training['keahlian'] ?? null,
                            'instansi_penyelenggara' => $training['instansi_penyelenggara'] ?? null,
                            'tahun' => $training['tahun'] ?? null,
                            'pembiayaan' => $training['pembiayaan'] ?? null,
                        ];

                        if (! empty($training['id'])) {
                            $existingTraining = $employee->trainingHistories()->find($training['id']);
                            if ($existingTraining) {
                                $existingTraining->update($trainingData);
                                $keptTrainingIds[] = $existingTraining->id;

                                continue;
                            }
                        }

                        $newTraining = $employee->trainingHistories()->create($trainingData);
                        $keptTrainingIds[] = $newTraining->id;
                    }
                }

                $trainingsToDelete = $employee->trainingHistories()->whereNotIn('id', $keptTrainingIds)->get();
                foreach ($trainingsToDelete as $delTraining) {
                    $employee->documents()->where('record_type', 'training')->where('record_id', $delTraining->id)->delete();
                    $delTraining->delete();
                }
            }

            // Handle Perizinan Pegawai
            if (isset($validated['riwayat_perizinan']) && is_array($validated['riwayat_perizinan'])) {
                $keptPermitIds = [];
                foreach ($validated['riwayat_perizinan'] as $index => $permitItem) {
                    if (! empty($permitItem['jenis'])) {
                        $status = (! $user?->isTeacher() && ! empty($permitItem['status'])) ? $permitItem['status'] : 'diajukan';

                        $permitData = [
                            'tanggal' => ! empty($permitItem['tanggal']) ? $permitItem['tanggal'] : now()->toDateString(),
                            'tanggal_selesai' => ! empty($permitItem['tanggal_selesai']) ? $permitItem['tanggal_selesai'] : null,
                            'jenis' => $permitItem['jenis'],
                            'detail' => $permitItem['detail'] ?? null,
                            'keterangan' => $permitItem['keterangan'] ?? null,
                            'status' => $status,
                            'catatan' => $permitItem['catatan'] ?? null,
                            'updated_by' => $user?->id,
                        ];

                        if ($request->hasFile("riwayat_perizinan.{$index}.lampiran")) {
                            $file = $request->file("riwayat_perizinan.{$index}.lampiran");
                            $permitData['lampiran_nama'] = $file->getClientOriginalName();
                            $permitData['lampiran_path'] = $file->store("employee_permits/{$employee->id}", 'local');
                        }

                        if (! empty($permitItem['id'])) {
                            $existingPermit = $employee->permits()->find($permitItem['id']);
                            if ($existingPermit) {
                                if ($user?->isTeacher() && $existingPermit->status !== 'diajukan') {
                                    // Perizinan yang sudah disetujui / ditolak tidak dapat diubah oleh Guru
                                    $keptPermitIds[] = $existingPermit->id;

                                    continue;
                                }

                                if ($user?->isTeacher()) {
                                    $permitData['status'] = $existingPermit->status;
                                    $permitData['catatan'] = $existingPermit->catatan;
                                }
                                if (! empty($permitData['lampiran_path']) && $existingPermit->lampiran_path && $existingPermit->lampiran_path !== $permitData['lampiran_path'] && Storage::disk('local')->exists($existingPermit->lampiran_path)) {
                                    Storage::disk('local')->delete($existingPermit->lampiran_path);
                                }
                                $existingPermit->update($permitData);
                                $keptPermitIds[] = $existingPermit->id;

                                continue;
                            }
                        }

                        $permitData['created_by'] = $user?->id;
                        $newPermit = $employee->permits()->create($permitData);
                        $keptPermitIds[] = $newPermit->id;
                    }
                }

                $permitsToDelete = $employee->permits()->whereNotIn('id', $keptPermitIds)->get();
                foreach ($permitsToDelete as $delPermit) {
                    if ($user?->isTeacher() && $delPermit->status !== 'diajukan') {
                        // Guru tidak dapat menghapus perizinan yang sudah disetujui / ditolak
                        continue;
                    }
                    if ($delPermit->lampiran_path && Storage::disk('local')->exists($delPermit->lampiran_path)) {
                        Storage::disk('local')->delete($delPermit->lampiran_path);
                    }
                    $delPermit->delete();
                }
            }
        });

        return back()->with('success', "Data pegawai '{$employee->nama}' berhasil diperbarui.");
    }

    /**
     * Handle XLSX employee import.
     */
    public function import(Request $request, EmployeeImportService $importService): RedirectResponse
    {
        $user = auth()->user();
        if (! RolePermission::canAccess($user, 'employee_directory', 'import') || $user?->isTeacher()) {
            abort(403, 'Akses ditolak: Role Anda tidak memiliki izin untuk mengimpor data pegawai.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'file.required' => 'File Excel template wajib diunggah.',
            'file.mimes' => 'Format file harus berupa .xlsx atau .xls.',
            'file.max' => 'Ukuran file tidak boleh melebihi 10MB.',
        ]);

        $result = $importService->import($request->file('file'));

        if (! $result['success']) {
            return back()->with('error', 'Gagal mengimpor data: '.($result['errors'][0] ?? 'Terjadi kesalahan sistem.'));
        }

        $summaryMsg = "Berhasil memproses {$result['total_employees']} pegawai ({$result['created_count']} baru, {$result['updated_count']} diperbarui), {$result['education_count']} riwayat pendidikan, {$result['records_count']} catatan jenjang kepegawaian, dan {$result['positions_count']} riwayat jabatan.";

        return back()
            ->with('success', $summaryMsg)
            ->with('import_summary', $result);
    }

    /**
     * Download the XLSX template file.
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        $user = auth()->user();
        if (! RolePermission::canAccess($user, 'employee_directory', 'export') || $user?->isTeacher()) {
            abort(403, 'Akses ditolak: Role Anda tidak memiliki izin untuk mengunduh template data pegawai.');
        }

        $spreadsheet = $this->buildTemplate();

        $tmpFile = tempnam(sys_get_temp_dir(), 'employee_template_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpFile);

        return response()->download($tmpFile, 'Template_Data_Pegawai.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * @return array<string, mixed>
     */
    private function mapEmployeeForList(Employee $employee): array
    {
        $latestPosition = $employee->positions->sortByDesc('tmt_jabatan')->first();
        $latestRecord = $employee->records->sortByDesc('tmt_kenaikan_jenjang')->first();

        return [
            'id' => $employee->id,
            'nipy' => $employee->nipy,
            'nama' => $employee->nama,
            'gelar_depan' => $employee->gelar_depan,
            'gelar_belakang' => $employee->gelar_belakang,
            'nama_lengkap_gelar' => $employee->nama,
            'jk' => $employee->jk,
            'ttl' => $employee->ttl,
            'nik' => $employee->nik,
            'no_wa' => $employee->no_wa,
            'email' => $employee->email,
            'unit_kerja' => $latestPosition?->unit_kerja,
            'jabatan' => $latestPosition?->jabatan,
            'status_keaktifan' => $employee->status_keaktifan ?? 'Aktif',
            'jenjang_kepegawaian' => $latestRecord?->jenjang_kepegawaian,
            'status_pernikahan' => $employee->status_pernikahan,
            'skill' => $employee->skill,
        ];
    }

    private function buildTemplate(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;

        $headerFill = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A6B3C']];
        $headerFont = ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10];
        $exampleFill = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']];
        $borderStyle = [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'CCCCCC'],
            ],
        ];

        // ---- Sheet 1: Data Pegawai ----
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('1. Data Pegawai');

        $headers1 = [
            'A' => 'NIPY',
            'B' => 'Nama Lengkap',
            'C' => 'Gelar Depan',
            'D' => 'Gelar Belakang',
            'E' => 'JK (L/P)',
            'F' => 'Tempat Lahir',
            'G' => 'Tanggal Lahir',
            'H' => 'NIK',
            'I' => 'TMT Pengangkatan',
            'J' => 'TST Pengangkatan',
            'K' => 'Keaktifan Dapodik',
            'L' => 'Unit Keaktifan Dapodik',
            'M' => 'Status Keaktifan',
            'N' => 'Keterangan Tidak Aktif',
            'O' => 'No WA',
            'P' => 'Email',
            'Q' => 'Jalan Domisili',
            'R' => 'RT/RW Domisili',
            'S' => 'Dusun Domisili',
            'T' => 'Desa Domisili',
            'U' => 'Kecamatan Domisili',
            'V' => 'Kab Domisili',
            'W' => 'Provinsi Domisili',
            'X' => 'Jalan Tinggal',
            'Y' => 'RT/RW Tinggal',
            'Z' => 'Dusun Tinggal',
            'AA' => 'Desa Tinggal',
            'AB' => 'Kecamatan Tinggal',
            'AC' => 'Kabupaten Tinggal',
            'AD' => 'Provinsi Tinggal',
            'AE' => 'Status Rumah',
            'AF' => 'Kepemilikan BPJS (Ya/Tidak)',
            'AG' => 'Penanggung BPJS',
            'AH' => 'Skill',
            'AI' => 'Status Pernikahan',
            'AJ' => 'Nama Suami/Istri',
            'AK' => 'TTL Suami/Istri',
            'AL' => 'Pekerjaan Suami/Istri',
            'AM' => 'Tanggal Menikah',
            'AN' => 'Jumlah Anak',
            'AO' => 'Nama Ibu',
            'AP' => 'Nama Ayah',
            'AQ' => 'Alamat Orangtua',
            'AR' => 'Kontak Darurat',
            'AS' => 'Hubungan Kontak Darurat',
        ];

        foreach ($headers1 as $col => $label) {
            $sheet1->setCellValue("{$col}1", $label);
            $sheet1->getStyle("{$col}1")->applyFromArray([
                'fill' => $headerFill,
                'font' => $headerFont,
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet1->getColumnDimension($col)->setWidth(20);
        }

        // Format Text columns explicitly so leading zeros/numbers survive
        $sheet1->getStyle('A2:A1000')->getNumberFormat()->setFormatCode('@'); // NIPY
        $sheet1->getStyle('H2:H1000')->getNumberFormat()->setFormatCode('@'); // NIK
        $sheet1->getStyle('O2:O1000')->getNumberFormat()->setFormatCode('@'); // No WA

        // Format Date columns
        $sheet1->getStyle('G2:G1000')->getNumberFormat()->setFormatCode('yyyy/mm/dd'); // Tanggal Lahir
        $sheet1->getStyle('I2:I1000')->getNumberFormat()->setFormatCode('yyyy/mm/dd'); // TMT Pengangkatan
        $sheet1->getStyle('J2:J1000')->getNumberFormat()->setFormatCode('yyyy/mm/dd'); // TST Pengangkatan
        $sheet1->getStyle('AM2:AM1000')->getNumberFormat()->setFormatCode('yyyy/mm/dd'); // Tanggal Menikah

        // Example row for Sheet 1
        $example1 = [
            'A' => '1001', 'B' => 'Ahmad Fauzi', 'C' => 'Dr.', 'D' => 'S.Pd', 'E' => 'L', 'F' => 'Semarang', 'G' => '1985/06/15',
            'H' => '3301150678900001', 'I' => '2015/07/01', 'J' => '', 'K' => 'Aktif', 'L' => 'SDIT Ulil Albab', 'M' => 'Aktif', 'N' => '-',
            'O' => '081234567890', 'P' => 'ahmad@sekolah.sch.id',
            'Q' => 'Jl. Merdeka No. 10', 'R' => '002/005', 'S' => 'Gondang', 'T' => 'Gondangrejo',
            'U' => 'Karanganyar', 'V' => 'Karanganyar', 'W' => 'Jawa Tengah',
            'X' => 'Jl. Merdeka No. 10', 'Y' => '002/005', 'Z' => 'Gondang', 'AA' => 'Gondangrejo',
            'AB' => 'Karanganyar', 'AC' => 'Karanganyar', 'AD' => 'Jawa Tengah', 'AE' => 'Milik Sendiri',
            'AF' => 'Ya', 'AG' => 'Pribadi',
            'AH' => 'Matematika, MS Office', 'AI' => 'Menikah', 'AJ' => 'Siti Rahayu', 'AK' => 'Solo, 10/03/1987',
            'AL' => 'Guru', 'AM' => '2010/12/20', 'AN' => '2',
            'AO' => 'Siti Aminah', 'AP' => 'Budi Santoso', 'AQ' => 'Jl. Damai No. 5, Semarang',
            'AR' => '082345678901', 'AS' => 'Saudara Kandung',
        ];

        foreach ($example1 as $col => $val) {
            $sheet1->setCellValue("{$col}2", $val);
            $sheet1->getStyle("{$col}2")->applyFromArray(['fill' => $exampleFill]);
        }

        $lastCol1 = 'AS';
        $sheet1->getStyle("A1:{$lastCol1}1")->applyFromArray(['borders' => $borderStyle]);
        $sheet1->getStyle("A2:{$lastCol1}2")->applyFromArray(['borders' => $borderStyle]);

        // ---- Sheet 2: Riwayat Pendidikan ----
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('2. Riwayat Pendidikan');

        $headers2 = [
            'A' => 'NIK',
            'B' => 'Nama Lengkap',
            'C' => 'Jenjang Pendidikan',
            'D' => 'Jurusan',
            'E' => 'Instansi Pendidikan',
            'F' => 'Tahun Lulus',
            'G' => 'Pembiayaan',
        ];

        foreach ($headers2 as $col => $label) {
            $sheet2->setCellValue("{$col}1", $label);
            $sheet2->getStyle("{$col}1")->applyFromArray([
                'fill' => $headerFill,
                'font' => $headerFont,
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet2->getColumnDimension($col)->setWidth(22);
        }

        $sheet2->getStyle('A2:A1000')->getNumberFormat()->setFormatCode('@');

        $example2 = [
            'A' => '3301150678900001', 'B' => 'Ahmad Fauzi, S.Pd', 'C' => 'S1',
            'D' => 'Pendidikan Matematika', 'E' => 'UNS Surakarta', 'F' => '2008', 'G' => 'Beasiswa',
        ];

        foreach ($example2 as $col => $val) {
            $sheet2->setCellValue("{$col}2", $val);
            $sheet2->getStyle("{$col}2")->applyFromArray(['fill' => $exampleFill]);
        }

        $sheet2->getStyle('A1:G1')->applyFromArray(['borders' => $borderStyle]);
        $sheet2->getStyle('A2:G2')->applyFromArray(['borders' => $borderStyle]);

        // ---- Sheet 3: Riwayat Kepegawaian (Jenjang Kepegawaian murni TMT & TST) ----
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('3. Riwayat Kepegawaian');

        $headers3 = [
            'A' => 'NIK',
            'B' => 'Nama Lengkap',
            'C' => 'TMT Kenaikan Jenjang',
            'D' => 'TST Kenaikan Jenjang',
            'E' => 'Jenjang Kepegawaian',
        ];

        foreach ($headers3 as $col => $label) {
            $sheet3->setCellValue("{$col}1", $label);
            $sheet3->getStyle("{$col}1")->applyFromArray([
                'fill' => $headerFill,
                'font' => $headerFont,
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet3->getColumnDimension($col)->setWidth(22);
        }

        $sheet3->getStyle('A2:A1000')->getNumberFormat()->setFormatCode('@');
        $sheet3->getStyle('C2:C1000')->getNumberFormat()->setFormatCode('yyyy/mm/dd');
        $sheet3->getStyle('D2:D1000')->getNumberFormat()->setFormatCode('yyyy/mm/dd');

        $example3 = [
            'A' => '3301150678900001', 'B' => 'Ahmad Fauzi, S.Pd',
            'C' => '2020/07/01', 'D' => '', 'E' => 'GTY',
        ];

        foreach ($example3 as $col => $val) {
            $sheet3->setCellValue("{$col}2", $val);
            $sheet3->getStyle("{$col}2")->applyFromArray(['fill' => $exampleFill]);
        }

        $sheet3->getStyle('A1:E1')->applyFromArray(['borders' => $borderStyle]);
        $sheet3->getStyle('A2:E2')->applyFromArray(['borders' => $borderStyle]);

        // ---- Sheet 4: Riwayat Jabatan (NIK, Nama Lengkap, TMT Jabatan, TST Jabatan, Unit Kerja, Jabatan, Jenis Kepegawaian) ----
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('4. Riwayat Jabatan');

        $headers4 = [
            'A' => 'NIK',
            'B' => 'Nama Lengkap',
            'C' => 'TMT Jabatan',
            'D' => 'TST Jabatan',
            'E' => 'Unit Kerja',
            'F' => 'Jabatan',
            'G' => 'Jenis Kepegawaian',
        ];

        foreach ($headers4 as $col => $label) {
            $sheet4->setCellValue("{$col}1", $label);
            $sheet4->getStyle("{$col}1")->applyFromArray([
                'fill' => $headerFill,
                'font' => $headerFont,
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet4->getColumnDimension($col)->setWidth(22);
        }

        $sheet4->getStyle('A2:A1000')->getNumberFormat()->setFormatCode('@');
        $sheet4->getStyle('C2:C1000')->getNumberFormat()->setFormatCode('yyyy/mm/dd');
        $sheet4->getStyle('D2:D1000')->getNumberFormat()->setFormatCode('yyyy/mm/dd');

        $example4 = [
            'A' => '3301150678900001', 'B' => 'Ahmad Fauzi, S.Pd',
            'C' => '2020/07/01', 'D' => '',
            'E' => 'SDIT Ulil Albab', 'F' => 'Guru Kelas', 'G' => 'Tenaga Pendidik',
        ];

        foreach ($example4 as $col => $val) {
            $sheet4->setCellValue("{$col}2", $val);
            $sheet4->getStyle("{$col}2")->applyFromArray(['fill' => $exampleFill]);
        }

        $sheet4->getStyle('A1:G1')->applyFromArray(['borders' => $borderStyle]);
        $sheet4->getStyle('A2:G2')->applyFromArray(['borders' => $borderStyle]);

        // Set active sheet to sheet 1
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }
}
