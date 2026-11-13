<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\RolePermission;
use App\Models\SchoolClass;
use App\Models\SchoolLevel;
use App\Models\SchoolProgram;
use App\Models\SchoolUnit;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use App\Services\StudentImportService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentController extends Controller
{
    /**
     * Handle XLSX student import.
     */
    public function import(Request $request, StudentImportService $importService): RedirectResponse
    {
        if (! RolePermission::canAccess($request->user(), 'student_data', 'import')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengimpor data siswa.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
            'unit' => ['nullable', 'string'],
        ], [
            'file.required' => 'File Excel template wajib diunggah.',
            'file.mimes' => 'Format file harus berupa .xlsx atau .xls.',
            'file.max' => 'Ukuran file tidak boleh melebihi 10MB.',
        ]);

        $unit = $request->input('unit');
        $result = $importService->import($request->file('file'), $unit);

        if (! $result['success']) {
            return back()->with('error', 'Gagal mengimpor data: '.($result['errors'][0] ?? 'Terjadi kesalahan sistem.'));
        }

        $summaryMsg = "Berhasil memproses {$result['total_students']} siswa ({$result['created_count']} baru, {$result['updated_count']} diperbarui), {$result['family_count']} data keluarga, {$result['siblings_count']} saudara kandung, dan {$result['academic_records_count']} catatan akademik.";

        if ($result['skipped_count'] > 0) {
            $summaryMsg .= " ({$result['skipped_count']} siswa dilewati karena bukan unit yang dipilih.)";
        }

        return back()
            ->with('success', $summaryMsg)
            ->with('import_summary', $result);
    }

    /**
     * Display the student directory page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if (! RolePermission::canAccess($user, 'student_data', 'view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat data siswa.');
        }

        $permissions = [
            'can_view' => RolePermission::canAccess($user, 'student_data', 'view'),
            'can_create' => RolePermission::canAccess($user, 'student_data', 'create'),
            'can_update' => RolePermission::canAccess($user, 'student_data', 'update'),
            'can_delete' => RolePermission::canAccess($user, 'student_data', 'delete'),
            'can_export' => RolePermission::canAccess($user, 'student_data', 'export'),
            'can_import' => RolePermission::canAccess($user, 'student_data', 'import'),
        ];

        $students = Student::with([
            'siblings',
            'currentRecord.classroom',
        ])
            ->orderBy('unit')
            ->orderBy('nama')
            ->get()
            ->map(function (Student $student) {
                $record = $student->currentRecord;
                $masterKeaktifan = $student->status_keaktifan ?: 'Aktif';
                $normKeaktifan = strtolower(trim((string) $masterKeaktifan));
                $isInactive = str_contains($normKeaktifan, 'tidak');
                $isLulus = $normKeaktifan === 'lulus';

                return [
                    'nisn' => $student->nisn,
                    'nama' => $student->nama,
                    'nipd' => $student->nipd,
                    'jk' => $student->jk,
                    'ttl' => $student->ttl,
                    'nik' => $student->nik,
                    'no_kk' => $student->no_kk,
                    'agama' => $student->agama,
                    'unit' => $student->unit,
                    'unit_pesantren' => $student->unit_pesantren ?? $record?->unit_pesantren,
                    'program' => $student->program ?? $record?->program,
                    'angkatan' => $student->angkatan,
                    'photo_url' => $student->photo_url,
                    'jenjang' => $student->jenjang ?? $record?->classroom?->jenjang,
                    'student_status' => $isInactive ? 'tidak_aktif' : ($isLulus ? 'lulus' : 'aktif'),
                    'status_keaktifan' => $masterKeaktifan,
                    'status_per_semester' => in_array($record?->student_status, ['Tinggal Kelas', 'mengulang']) ? 'Tinggal Kelas' : 'Naik Kelas',
                    'keterangan_tidak_aktif' => $student->keterangan_tidak_aktif ?? $record?->ket_tidak_aktif,
                    'tanggal_tidak_aktif' => $student->tanggal_tidak_aktif ? $student->tanggal_tidak_aktif->format('Y-m-d') : ($record?->tanggal_tidak_aktif?->format('Y-m-d')),
                    'no_wa' => $student->no_wa,
                    'sekolah_asal' => $student->sekolah_asal,
                    'status_keluarga' => $student->status_keluarga,
                    'anak_ke' => $student->anak_ke,
                    'diterima_di_jenjang' => $student->diterima_di_jenjang,
                    'tanggal_diterima' => $student->tanggal_diterima ? $student->tanggal_diterima->format('Y-m-d') : null,
                    'formatted_tanggal_diterima' => $student->tanggal_diterima ? $student->tanggal_diterima->translatedFormat('d F Y') : null,
                    'info_psb' => $student->info_psb,
                    'jalan' => $student->jalan,
                    'rt' => $student->rt,
                    'rw' => $student->rw,
                    'dusun' => $student->dusun,
                    'desa' => $student->desa,
                    'kecamatan' => $student->kecamatan,
                    'kabupaten' => $student->kabupaten,
                    'provinsi' => $student->provinsi,
                    'ayah' => [
                        'nama' => $student->ayah_nama,
                        'tahun_lahir' => $student->ayah_tahun_lahir,
                        'pekerjaan' => $student->ayah_pekerjaan,
                        'penghasilan' => $student->ayah_penghasilan,
                        'status' => $student->ayah_status ?? 'Hidup',
                    ],
                    'ibu' => [
                        'nama' => $student->ibu_nama,
                        'tahun_lahir' => $student->ibu_tahun_lahir,
                        'pekerjaan' => $student->ibu_pekerjaan,
                        'penghasilan' => $student->ibu_penghasilan,
                        'status' => $student->ibu_status ?? 'Hidup',
                    ],
                    'bisnis' => [
                        'has_bisnis' => $student->has_bisnis ?? false,
                        'jenis_bisnis' => $student->jenis_bisnis,
                    ],
                    'wali' => [
                        'nama' => $student->wali_nama,
                        'hubungan' => $student->wali_hubungan,
                        'pekerjaan' => $student->wali_pekerjaan,
                        'penghasilan' => $student->wali_penghasilan,
                    ],
                    'saudara' => $student->siblings->map(fn ($s) => [
                        'nama' => $s->nama,
                        'tanggal_lahir' => $s->tanggal_lahir?->format('Y-m-d'),
                    ])->toArray(),
                    'bantuan' => [
                        'desil' => $student->desil ?? $record?->desil,
                        'pip' => (bool) ($student->status_pip ?? $record?->status_pip ?? false),
                        'pip_keterangan' => $student->pip_keterangan ?? $record?->pip_keterangan,
                        'kip' => (bool) ($student->status_kip ?? $record?->status_kip ?? false),
                        'no_kip' => $student->no_kip ?? $record?->no_kip,
                    ],
                ];
            });

        return Inertia::render('Students', [
            'students' => $students,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Show the form for creating a new student.
     */
    public function create(Request $request): Response
    {
        $user = $request->user();
        if (! RolePermission::canAccess($user, 'student_data', 'create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambah data siswa.');
        }

        $activeYear = AcademicYear::current();

        return Inertia::render('Students/Create', [
            'allUnits' => SchoolUnit::orderBy('name')->pluck('name')->toArray(),
            'allLevels' => SchoolLevel::orderBy('name')->pluck('name')->toArray(),
            'allPrograms' => SchoolProgram::orderBy('name')->pluck('name')->unique()->values()->toArray(),
            'allClasses' => Classroom::with('academicYear')->orderBy('name')->get()->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'unit' => $c->unit,
                'jenjang' => $c->jenjang,
                'grade' => $c->grade,
                'academic_year_id' => $c->academic_year_id,
            ])->toArray(),
            'academicYears' => AcademicYear::orderByDesc('name')->get(['id', 'name', 'semester'])->toArray(),
            'activeAcademicYear' => $activeYear,
        ]);
    }

    /**
     * Store a newly created student in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! RolePermission::canAccess($user, 'student_data', 'create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambah data siswa.');
        }

        $validated = $request->validate([
            // Identitas Pokok
            'nama' => ['required', 'string', 'max:255'],
            'nisn' => ['required', 'string', 'max:20', 'unique:students,nisn'],
            'nipd' => ['nullable', 'string', 'max:50'],
            'nik' => ['nullable', 'string', 'max:30'],
            'no_kk' => ['nullable', 'string', 'max:30'],
            'jk' => ['required', 'in:L,P'],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date'],
            'agama' => ['nullable', 'string', 'max:50'],
            'no_wa' => ['nullable', 'string', 'max:50'],
            'no_wa_2' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'unit' => ['nullable', 'string', 'max:100'],
            'unit_pesantren' => ['nullable', 'string', 'max:100'],
            'program' => ['nullable', 'string', 'max:100'],
            'jenjang' => ['nullable', 'string', 'max:50'],
            'tingkat' => ['nullable', 'string', 'max:20'],
            'classroom_id' => ['nullable', 'exists:classrooms,id'],
            'kelas' => ['nullable', 'string', 'max:50'],

            // Alamat
            'jalan' => ['nullable', 'string', 'max:255'],
            'rt' => ['nullable', 'string', 'max:10'],
            'rw' => ['nullable', 'string', 'max:10'],
            'dusun' => ['nullable', 'string', 'max:100'],
            'desa' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kabupaten' => ['nullable', 'string', 'max:100'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'jenis_tinggal' => ['nullable', 'string', 'max:100'],
            'alat_transportasi' => ['nullable', 'string', 'max:100'],
            'jarak_rumah' => ['nullable', 'string', 'max:50'],

            // Keluarga
            'status_keluarga' => ['nullable', 'string', 'max:50'],
            'anak_ke' => ['nullable', 'integer', 'min:1', 'max:50'],
            'has_bisnis' => ['nullable', 'boolean'],
            'jenis_bisnis' => ['nullable', 'string', 'max:255'],
            // Ayah
            'ayah_nama' => ['nullable', 'string', 'max:255'],
            'ayah_tahun_lahir' => ['nullable', 'integer', 'min:1920', 'max:'.(date('Y') + 1)],
            'ayah_pendidikan' => ['nullable', 'string', 'max:100'],
            'ayah_pekerjaan' => ['nullable', 'string', 'max:100'],
            'ayah_penghasilan' => ['nullable', 'string', 'max:100'],
            'ayah_nik' => ['nullable', 'string', 'max:30'],
            'ayah_status' => ['nullable', 'string', 'max:50'],
            // Ibu
            'ibu_nama' => ['nullable', 'string', 'max:255'],
            'ibu_tahun_lahir' => ['nullable', 'integer', 'min:1920', 'max:'.(date('Y') + 1)],
            'ibu_pendidikan' => ['nullable', 'string', 'max:100'],
            'ibu_pekerjaan' => ['nullable', 'string', 'max:100'],
            'ibu_penghasilan' => ['nullable', 'string', 'max:100'],
            'ibu_nik' => ['nullable', 'string', 'max:30'],
            'ibu_status' => ['nullable', 'string', 'max:50'],
            // Wali
            'wali_nama' => ['nullable', 'string', 'max:255'],
            'wali_tahun_lahir' => ['nullable', 'integer', 'min:1920', 'max:'.(date('Y') + 1)],
            'wali_pendidikan' => ['nullable', 'string', 'max:100'],
            'wali_pekerjaan' => ['nullable', 'string', 'max:100'],
            'wali_penghasilan' => ['nullable', 'string', 'max:100'],
            'wali_nik' => ['nullable', 'string', 'max:30'],
            'wali_hubungan' => ['nullable', 'string', 'max:100'],

            // Registrasi
            'status_registrasi' => ['nullable', 'string', 'max:50'],
            'sekolah_asal' => ['nullable', 'string', 'max:255'],
            'diterima_di_jenjang' => ['nullable', 'string', 'max:50'],
            'tanggal_diterima' => ['nullable', 'date'],
            'info_psb' => ['nullable', 'string', 'max:100'],
            'status_keaktifan' => ['nullable', 'string', 'max:50'],
            'keterangan_tidak_aktif' => ['nullable', 'string', 'max:255'],
            'tanggal_tidak_aktif' => ['nullable', 'date'],

            // Kesejahteraan
            'status_kip' => ['nullable', 'boolean'],
            'no_kip' => ['nullable', 'string', 'max:100'],
            'nama_di_kip' => ['nullable', 'string', 'max:255'],
            'status_pip' => ['nullable', 'boolean'],
            'pip_keterangan' => ['nullable', 'string', 'max:255'],
            'desil' => ['nullable', 'integer', 'min:1', 'max:10'],
            'kebutuhan_khusus' => ['nullable', 'string', 'max:255'],
        ], [
            'nama.required' => 'Nama lengkap siswa wajib diisi.',
            'nisn.required' => 'NISN wajib diisi.',
            'nisn.unique' => 'NISN ini sudah terdaftar di sistem.',
            'jk.required' => 'Jenis kelamin wajib dipilih.',
            'tempat_lahir.required' => 'Tempat lahir wajib diisi.',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
            'tanggal_lahir.date' => 'Format tanggal lahir tidak valid.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $student = DB::transaction(function () use ($validated) {
            $isTidakAktif = in_array(strtolower($validated['status_keaktifan'] ?? 'aktif'), ['tidak aktif', 'tidak_aktif']);

            // Auto calculate angkatan from tanggal_diterima or current year
            $angkatan = null;
            if (! empty($validated['tanggal_diterima'])) {
                $angkatan = date('Y', strtotime($validated['tanggal_diterima']));
            } else {
                $angkatan = date('Y');
            }

            $studentData = array_merge($validated, [
                'agama' => $validated['agama'] ?? 'Islam',
                'angkatan' => $angkatan,
                'status_keaktifan' => $isTidakAktif ? 'Tidak Aktif' : 'Aktif',
                'keterangan_tidak_aktif' => $isTidakAktif ? ($validated['keterangan_tidak_aktif'] ?? null) : null,
                'tanggal_tidak_aktif' => $isTidakAktif ? ($validated['tanggal_tidak_aktif'] ?? null) : null,
                'has_bisnis' => (bool) ($validated['has_bisnis'] ?? false),
                'status_kip' => (bool) ($validated['status_kip'] ?? false),
                'status_pip' => (bool) ($validated['status_pip'] ?? false),
            ]);

            // Remove non-student table fields before create
            unset($studentData['classroom_id']);
            unset($studentData['kelas']);
            unset($studentData['program']);
            unset($studentData['tingkat']);

            $student = Student::create($studentData);

            // Buat record akademik untuk tahun ajaran aktif jika ada
            $activeYear = AcademicYear::current();
            if ($activeYear) {
                // Cari atau cocokan classroom_id jika user memilih kelas
                $classroomId = $validated['classroom_id'] ?? null;
                if (! $classroomId && ! empty($validated['kelas'])) {
                    $matchingClass = Classroom::where('academic_year_id', $activeYear->id)
                        ->where('name', $validated['kelas'])
                        ->first();
                    if ($matchingClass) {
                        $classroomId = $matchingClass->id;
                    }
                }

                StudentAcademicRecord::create([
                    'student_id' => $student->id,
                    'academic_year_id' => $activeYear->id,
                    'classroom_id' => $classroomId,
                    'unit' => $student->unit,
                    'jenjang' => $student->jenjang,
                    'tingkat' => $validated['tingkat'] ?? null,
                    'program' => $validated['program'] ?? 'Umum',
                    'unit_pesantren' => $validated['unit_pesantren'] ?? null,
                    'student_status' => 'Naik Kelas',
                    'ket_tidak_aktif' => $isTidakAktif ? ($validated['keterangan_tidak_aktif'] ?? null) : null,
                    'tanggal_tidak_aktif' => $isTidakAktif ? ($validated['tanggal_tidak_aktif'] ?? null) : null,
                    'desil' => $validated['desil'] ?? null,
                    'status_pip' => (bool) ($validated['status_pip'] ?? false),
                    'pip_keterangan' => $validated['pip_keterangan'] ?? null,
                    'status_kip' => (bool) ($validated['status_kip'] ?? false),
                    'no_kip' => $validated['no_kip'] ?? null,
                ]);
            }

            return $student;
        });

        return redirect()->route('students.show', ['nisn' => $student->nisn])
            ->with('success', "Siswa baru '{$student->nama}' berhasil ditambahkan ke database.");
    }

    /**
     * Display the student detail page.
     */
    public function show(string $nisn, Request $request): Response
    {
        $user = $request->user();
        if (! RolePermission::canAccess($user, 'student_data', 'view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat rincian data siswa.');
        }

        $permissions = [
            'can_view' => RolePermission::canAccess($user, 'student_data', 'view'),
            'can_create' => RolePermission::canAccess($user, 'student_data', 'create'),
            'can_update' => RolePermission::canAccess($user, 'student_data', 'update'),
            'can_delete' => RolePermission::canAccess($user, 'student_data', 'delete'),
            'can_export' => RolePermission::canAccess($user, 'student_data', 'export'),
            'can_import' => RolePermission::canAccess($user, 'student_data', 'import'),
        ];

        $studentModel = Student::with([
            'academicRecords.classroom',
            'academicRecords.academicYear',
            'siblings',
        ])->where('nisn', $nisn)->orWhere('id', $nisn)->firstOrFail();

        $activeYear = AcademicYear::current();
        $currentRecord = $studentModel->academicRecords->firstWhere('academic_year_id', $activeYear?->id)
            ?? $studentModel->academicRecords->sortByDesc('academic_year_id')->first();

        $studentData = [
            'id' => $studentModel->id,
            'nisn' => $studentModel->nisn,
            'nama' => $studentModel->nama,
            'nipd' => $studentModel->nipd,
            'jk' => $studentModel->jk,
            'ttl' => $studentModel->ttl,
            'tempat_lahir' => $studentModel->tempat_lahir,
            'tanggal_lahir' => $studentModel->tanggal_lahir ? $studentModel->tanggal_lahir->format('Y-m-d') : null,
            'formatted_tanggal_lahir' => $studentModel->tanggal_lahir ? $studentModel->tanggal_lahir->translatedFormat('d F Y') : null,
            'nik' => $studentModel->nik,
            'no_kk' => $studentModel->no_kk,
            'agama' => $studentModel->agama,
            'unit' => $studentModel->unit,
            'unit_pesantren' => $studentModel->unit_pesantren ?? $currentRecord?->unit_pesantren,
            'program' => $studentModel->program ?? $currentRecord?->program,
            'photo_url' => $studentModel->photo_url,
            'jenjang' => $currentRecord?->classroom?->jenjang ?? $studentModel->jenjang ?? $studentModel->diterima_di_jenjang,
            'tingkat' => $currentRecord?->tingkat,
            'kelas' => $currentRecord?->classroom?->name,
            'student_status' => (str_contains(strtolower(trim((string) ($studentModel->status_keaktifan ?: 'Aktif'))), 'tidak')) ? 'tidak_aktif' : ((strtolower(trim((string) $studentModel->status_keaktifan)) === 'lulus') ? 'lulus' : 'aktif'),
            'status_keaktifan' => $studentModel->status_keaktifan ?: 'Aktif',
            'status_per_semester' => in_array($currentRecord?->student_status, ['Tinggal Kelas', 'mengulang']) ? 'Tinggal Kelas' : 'Naik Kelas',
            'ket_tidak_aktif' => $studentModel->keterangan_tidak_aktif ?? $currentRecord?->ket_tidak_aktif,
            'keterangan_tidak_aktif' => $studentModel->keterangan_tidak_aktif ?? $currentRecord?->ket_tidak_aktif,
            'tanggal_tidak_aktif' => $studentModel->tanggal_tidak_aktif ? $studentModel->tanggal_tidak_aktif->format('Y-m-d') : ($currentRecord?->tanggal_tidak_aktif?->format('Y-m-d')),
            'formatted_tanggal_tidak_aktif' => $studentModel->tanggal_tidak_aktif ? $studentModel->tanggal_tidak_aktif->translatedFormat('d F Y') : ($currentRecord?->tanggal_tidak_aktif?->translatedFormat('d F Y')),
            'no_wa' => $studentModel->no_wa,
            'no_wa_2' => $studentModel->no_wa_2,
            'email' => $studentModel->email,
            'sekolah_asal' => $studentModel->sekolah_asal,
            'status_keluarga' => $studentModel->status_keluarga,
            'anak_ke' => $studentModel->anak_ke,
            'diterima_di_jenjang' => $studentModel->diterima_di_jenjang,
            'tanggal_diterima' => $studentModel->tanggal_diterima ? $studentModel->tanggal_diterima->format('Y-m-d') : null,
            'formatted_tanggal_diterima' => $studentModel->tanggal_diterima ? $studentModel->tanggal_diterima->translatedFormat('d F Y') : null,
            'info_psb' => $studentModel->info_psb,
            'jalan' => $studentModel->jalan,
            'rt' => $studentModel->rt,
            'rw' => $studentModel->rw,
            'dusun' => $studentModel->dusun,
            'desa' => $studentModel->desa,
            'kecamatan' => $studentModel->kecamatan,
            'kabupaten' => $studentModel->kabupaten,
            'provinsi' => $studentModel->provinsi,
            'jenis_tinggal' => $studentModel->jenis_tinggal,
            'alat_transportasi' => $studentModel->alat_transportasi,
            'jarak_rumah' => $studentModel->jarak_rumah,
            'kebutuhan_khusus' => $studentModel->kebutuhan_khusus,
            'ayah' => [
                'nama' => $studentModel->ayah_nama,
                'tahun_lahir' => $studentModel->ayah_tahun_lahir,
                'pekerjaan' => $studentModel->ayah_pekerjaan,
                'penghasilan' => $studentModel->ayah_penghasilan,
                'nik' => $studentModel->ayah_nik,
                'status' => $studentModel->ayah_status ?? 'Hidup',
            ],
            'ibu' => [
                'nama' => $studentModel->ibu_nama,
                'tahun_lahir' => $studentModel->ibu_tahun_lahir,
                'pekerjaan' => $studentModel->ibu_pekerjaan,
                'penghasilan' => $studentModel->ibu_penghasilan,
                'nik' => $studentModel->ibu_nik,
                'status' => $studentModel->ibu_status ?? 'Hidup',
            ],
            'bisnis' => [
                'has_bisnis' => $studentModel->has_bisnis ?? false,
                'jenis_bisnis' => $studentModel->jenis_bisnis,
            ],
            'wali' => [
                'nama' => $studentModel->wali_nama,
                'hubungan' => $studentModel->wali_hubungan,
                'pekerjaan' => $studentModel->wali_pekerjaan,
                'penghasilan' => $studentModel->wali_penghasilan,
                'nik' => $studentModel->wali_nik,
            ],
            'saudara' => $studentModel->siblings->map(fn ($s) => [
                'nama' => $s->nama,
                'tanggal_lahir' => $s->tanggal_lahir?->format('Y-m-d'),
            ])->toArray(),
            'riwayat_keaktifan' => (function () use ($studentModel) {
                // 1. Lengkapi data periode start_date & end_date untuk setiap record akademik
                $mappedRecords = $studentModel->academicRecords->map(function ($r) {
                    $academicYear = $r->academicYear;
                    $startDate = $academicYear?->start_date?->format('Y-m-d');
                    $endDate = $academicYear?->end_date?->format('Y-m-d');

                    if (! $startDate || ! $endDate) {
                        $yearName = $academicYear?->name ?? '';
                        if (preg_match('/(\d{4})\s*[\/-]\s*(\d{4})/', $yearName, $m)) {
                            $y1 = $m[1];
                            $y2 = $m[2];
                            $sem = strtolower((string) ($academicYear?->semester ?? ''));
                            if ($sem === 'ganjil') {
                                $startDate = $startDate ?: "{$y1}-07-01";
                                $endDate = $endDate ?: "{$y1}-12-31";
                            } elseif ($sem === 'genap') {
                                $startDate = $startDate ?: "{$y2}-01-01";
                                $endDate = $endDate ?: "{$y2}-06-30";
                            } else {
                                $startDate = $startDate ?: "{$y1}-07-01";
                                $endDate = $endDate ?: "{$y2}-06-30";
                            }
                        }
                    }

                    return [
                        'record' => $r,
                        'academic_year' => $academicYear?->name,
                        'semester' => $academicYear?->semester,
                        'start_date' => $startDate ?? '1970-01-01',
                        'end_date' => $endDate ?? '1970-12-31',
                    ];
                })->sortBy('start_date')->values();

                $masterKeaktifan = trim((string) ($studentModel->status_keaktifan ?: 'Aktif'));
                $masterKet = trim((string) ($studentModel->keterangan_tidak_aktif ?? ''));
                $tglTidakAktif = $studentModel->tanggal_tidak_aktif?->format('Y-m-d');
                $hasInactiveState = ! empty($masterKet) || strcasecmp($masterKeaktifan, 'Aktif') !== 0;

                // Threshold tanggal semester saat siswa pertama kali menjadi Tidak Aktif / Tidak Aktif-Sebagian
                $inactiveStartThreshold = null;
                $results = [];

                foreach ($mappedRecords as $item) {
                    $r = $item['record'];
                    $startDate = $item['start_date'];
                    $endDate = $item['end_date'];

                    $statusKeaktifanSemester = 'Aktif';
                    $ketSemester = null;

                    if ($hasInactiveState) {
                        // Jika di riwayat semester sebelumnya sudah tidak aktif (termasuk tidak aktif sebagian),
                        // maka semester-semester selanjutnya dihilangkan total (tidak perlu ditampilkan barisnya).
                        if ($inactiveStartThreshold !== null && $startDate > $inactiveStartThreshold) {
                            continue;
                        }

                        $ketSemester = $masterKet ?: (strcasecmp($masterKeaktifan, 'Lulus') === 0 ? 'Lulus' : 'Tidak Aktif');

                        if ($tglTidakAktif && $startDate && $endDate) {
                            if ($tglTidakAktif <= $startDate) {
                                $statusKeaktifanSemester = 'Tidak Aktif';
                                $inactiveStartThreshold = $startDate;
                            } elseif ($tglTidakAktif > $endDate) {
                                $statusKeaktifanSemester = 'Aktif';
                                $ketSemester = null; // Masih aktif penuh pada semester ini
                            } else {
                                // Tanggal di tengah-tengah rentang semester -> Tidak Aktif-Sebagian
                                $statusKeaktifanSemester = 'Tidak Aktif-Sebagian';
                                $inactiveStartThreshold = $startDate;
                            }
                        } else {
                            if (strcasecmp($masterKeaktifan, 'Lulus') === 0 || strcasecmp($masterKet, 'Lulus') === 0) {
                                $statusKeaktifanSemester = 'Lulus';
                            } else {
                                $statusKeaktifanSemester = 'Tidak Aktif';
                            }
                            $inactiveStartThreshold = $startDate;
                        }
                    }

                    $results[] = [
                        'id' => $r->id,
                        'academic_year' => $item['academic_year'],
                        'semester' => $item['semester'],
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'unit' => $r->unit ?? $r->classroom?->unit ?? $studentModel->unit,
                        'unit_pesantren' => $r->unit_pesantren ?? $studentModel->unit_pesantren,
                        'program' => $r->program,
                        'tingkat' => $r->tingkat,
                        'kelas' => $r->classroom?->name,
                        'status' => $statusKeaktifanSemester,
                        'keterangan' => $ketSemester,
                        'tanggal_tidak_aktif' => $tglTidakAktif,
                        'student_status' => $r->student_status,
                    ];
                }

                // Urutkan dari Tahun Ajaran terbaru dulu ke terlama
                return array_reverse($results);
            })(),
            'angkatan' => $studentModel->angkatan,
            'diterima_di_jenjang' => $studentModel->diterima_di_jenjang,
            'tanggal_diterima' => $studentModel->tanggal_diterima?->format('Y-m-d'),
            'formatted_tanggal_diterima' => $studentModel->tanggal_diterima ? Carbon::parse($studentModel->tanggal_diterima)->translatedFormat('d F Y') : null,
            'info_psb' => $studentModel->info_psb,
            'status_registrasi' => $studentModel->status_registrasi,
            'bantuan' => [
                'desil' => $studentModel->desil ?? $currentRecord?->desil,
                'pip' => (bool) ($studentModel->status_pip ?? $currentRecord?->status_pip ?? false),
                'pip_keterangan' => $studentModel->pip_keterangan ?? $currentRecord?->pip_keterangan,
                'kip' => (bool) ($studentModel->status_kip ?? $currentRecord?->status_kip ?? false),
                'no_kip' => $studentModel->no_kip ?? $currentRecord?->no_kip,
                'nama_di_kip' => $studentModel->nama_di_kip,
            ],
        ];

        return Inertia::render('StudentDetail', [
            'student' => $studentData,
            'permissions' => $permissions,
            'allUnits' => fn () => SchoolUnit::orderBy('name')->pluck('name')->toArray(),
            'allLevels' => fn () => SchoolLevel::orderBy('name')->pluck('name')->toArray(),
            'allPrograms' => fn () => SchoolProgram::orderBy('name')->pluck('name')->unique()->values()->toArray(),
        ]);
    }

    /**
     * Update the specified student resource in storage.
     */
    public function update(string $id, Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! RolePermission::canAccess($user, 'student_data', 'update')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah data siswa.');
        }

        $student = Student::where('id', $id)->orWhere('nisn', $id)->firstOrFail();

        // Handle Tab Registrasi
        if ($request->input('_tab') === 'registrasi' || $request->has('status_registrasi') || $request->has('status_keaktifan')) {
            $validated = $request->validate([
                'status_registrasi' => ['nullable', 'string', 'max:50'],
                'sekolah_asal' => ['nullable', 'string', 'max:255'],
                'diterima_di_jenjang' => ['nullable', 'string', 'max:50'],
                'tanggal_diterima' => ['nullable', 'date'],
                'info_psb' => ['nullable', 'string', 'max:100'],
                'status_keaktifan' => ['required', 'string', 'in:Aktif,Tidak Aktif,Lulus'],
                'keterangan_tidak_aktif' => ['nullable', 'string', 'max:255'],
                'tanggal_tidak_aktif' => ['nullable', 'date'],
            ], [
                'status_keaktifan.required' => 'Status keaktifan wajib dipilih.',
                'status_keaktifan.in' => 'Status keaktifan harus salah satu dari: Aktif, Tidak Aktif, atau Lulus.',
                'tanggal_diterima.date' => 'Format tanggal diterima tidak valid.',
                'tanggal_tidak_aktif.date' => 'Format tanggal tidak aktif tidak valid.',
            ]);

            $rawKeaktifan = trim($validated['status_keaktifan']);
            if (strcasecmp($rawKeaktifan, 'lulus') === 0) {
                $statusKeaktifan = 'Lulus';
                $ketKeaktifan = 'Lulus';
                $tglKeaktifan = $validated['tanggal_tidak_aktif'] ?? $student->tanggal_tidak_aktif ?? now()->toDateString();
            } elseif (in_array(strtolower($rawKeaktifan), ['tidak aktif', 'tidak_aktif', 'non-aktif', 'non_aktif'])) {
                $statusKeaktifan = 'Tidak Aktif';
                $ketKeaktifan = $validated['keterangan_tidak_aktif'] ?? 'Tidak Aktif';
                $tglKeaktifan = $validated['tanggal_tidak_aktif'] ?? $student->tanggal_tidak_aktif ?? now()->toDateString();
            } else {
                $statusKeaktifan = 'Aktif';
                $ketKeaktifan = null;
                $tglKeaktifan = null;
            }

            $student->update([
                'status_registrasi' => $validated['status_registrasi'] ?? null,
                'sekolah_asal' => $validated['sekolah_asal'] ?? null,
                'diterima_di_jenjang' => $validated['diterima_di_jenjang'] ?? null,
                'tanggal_diterima' => $validated['tanggal_diterima'] ?? null,
                'info_psb' => $validated['info_psb'] ?? null,
                'status_keaktifan' => $statusKeaktifan,
                'keterangan_tidak_aktif' => $ketKeaktifan,
                'tanggal_tidak_aktif' => $tglKeaktifan,
            ]);

            $activeYear = AcademicYear::current();
            if ($activeYear) {
                $record = $student->academicRecords()->where('academic_year_id', $activeYear->id)->first();
                if ($record) {
                    $record->update([
                        'student_status' => $record->student_status ?: 'Naik Kelas',
                        'ket_tidak_aktif' => $statusKeaktifan === 'Aktif' ? null : $ketKeaktifan,
                        'tanggal_tidak_aktif' => $statusKeaktifan === 'Aktif' ? null : $tglKeaktifan,
                    ]);
                } else {
                    $student->academicRecords()->create([
                        'academic_year_id' => $activeYear->id,
                        'unit' => $student->unit,
                        'jenjang' => $student->jenjang,
                        'student_status' => 'Naik Kelas',
                        'ket_tidak_aktif' => $statusKeaktifan === 'Aktif' ? null : $ketKeaktifan,
                        'tanggal_tidak_aktif' => $statusKeaktifan === 'Aktif' ? null : $tglKeaktifan,
                    ]);
                }
            }

            return redirect()->back()->with('success', "Data registrasi siswa '{$student->nama}' berhasil diperbarui.");
        }

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nisn' => ['required', 'string', 'max:20', Rule::unique('students', 'nisn')->ignore($student->id)],
            'nipd' => ['nullable', 'string', 'max:50'],
            'nik' => ['nullable', 'string', 'max:30'],
            'no_kk' => ['nullable', 'string', 'max:30'],
            'jk' => ['required', 'in:L,P'],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date'],
            'agama' => ['nullable', 'string', 'max:50'],
            'no_wa' => ['nullable', 'string', 'max:50'],
            'no_wa_2' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'unit' => ['nullable', 'string', 'max:100'],
            'unit_pesantren' => ['nullable', 'string', 'max:100'],
            'program' => ['nullable', 'string', 'max:100'],
            'jenjang' => ['nullable', 'string', 'max:50'],
            'angkatan' => ['nullable', 'string', 'max:50'],
            'tingkat' => ['nullable', 'string', 'max:20'],
            'kelas' => ['nullable', 'string', 'max:50'],
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'nisn.required' => 'NISN wajib diisi.',
            'nisn.unique' => 'NISN sudah digunakan oleh siswa lain.',
            'jk.required' => 'Jenis kelamin wajib dipilih.',
            'jk.in' => 'Pilihan jenis kelamin tidak valid.',
            'tempat_lahir.required' => 'Tempat lahir wajib diisi.',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
            'tanggal_lahir.date' => 'Format tanggal lahir tidak valid.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $oldNisn = $student->nisn;

        $student->update([
            'nama' => $validated['nama'],
            'nisn' => $validated['nisn'],
            'nipd' => $validated['nipd'] ?? null,
            'nik' => $validated['nik'] ?? null,
            'no_kk' => $validated['no_kk'] ?? null,
            'jk' => $validated['jk'],
            'tempat_lahir' => $validated['tempat_lahir'],
            'tanggal_lahir' => $validated['tanggal_lahir'],
            'agama' => $validated['agama'] ?? 'Islam',
            'no_wa' => $validated['no_wa'] ?? null,
            'no_wa_2' => $validated['no_wa_2'] ?? null,
            'email' => $validated['email'] ?? null,
            'unit' => $validated['unit'] ?? $student->unit,
            'unit_pesantren' => array_key_exists('unit_pesantren', $validated) ? $validated['unit_pesantren'] : $student->unit_pesantren,
            'jenjang' => $validated['jenjang'] ?? $student->jenjang,
            'angkatan' => $validated['angkatan'] ?? null,
        ]);

        // Sinkronisasi program & jenjang ke Academic Record aktif siswa bila ada
        $activeYear = AcademicYear::current();
        if ($activeYear) {
            $recordUpdates = [
                'unit' => $student->unit,
                'jenjang' => $student->jenjang,
            ];
            if (isset($validated['program'])) {
                $recordUpdates['program'] = $validated['program'];
            }
            if (array_key_exists('unit_pesantren', $validated)) {
                $recordUpdates['unit_pesantren'] = $validated['unit_pesantren'];
            }
            if (isset($validated['tingkat'])) {
                $recordUpdates['tingkat'] = $validated['tingkat'];
            }
            if ($student->status_keaktifan) {
                $isInactive = strtolower(trim((string) $student->status_keaktifan)) === 'tidak aktif';
                $recordUpdates['student_status'] = $isInactive ? 'dropout' : 'aktif';
                $recordUpdates['ket_tidak_aktif'] = $student->keterangan_tidak_aktif;
                $recordUpdates['tanggal_tidak_aktif'] = $student->tanggal_tidak_aktif;
            }
            $student->academicRecords()->updateOrCreate(
                ['academic_year_id' => $activeYear->id],
                $recordUpdates
            );
        }

        $successMsg = "Data identitas siswa '{$student->nama}' berhasil diperbarui.";

        if ($student->nisn !== $oldNisn) {
            return redirect()->route('students.show', ['nisn' => $student->nisn])->with('success', $successMsg);
        }

        return redirect()->back()->with('success', $successMsg);
    }

    /**
     * Display the inline student editing page.
     */
    public function inlineEdit(Request $request): Response
    {
        $user = $request->user();
        if (! RolePermission::canAccess($user, 'student_data', 'update')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah data siswa.');
        }

        if ($request->has('academic_year_id') && $request->filled('academic_year_id')) {
            $selectedYearId = (int) $request->query('academic_year_id');
            session(['selected_academic_year_id' => $selectedYearId]);
        } else {
            $selectedYearId = session('selected_academic_year_id') ?? AcademicYear::current()?->id;
        }

        $allAcademicYears = AcademicYear::orderByDesc('start_date')
            ->orderByRaw("FIELD(semester, 'Genap', 'Ganjil')")
            ->get();

        $currentYear = AcademicYear::current();
        $academicYearsList = $allAcademicYears->map(function ($ay) use ($currentYear) {
            return [
                'id' => $ay->id,
                'name' => $ay->name,
                'semester' => $ay->semester,
                'label' => "TA {$ay->name} - {$ay->semester}",
                'is_current' => ($currentYear && $ay->id === $currentYear->id),
                'start_date' => $ay->start_date?->format('Y-m-d'),
            ];
        })->values()->all();

        $selectedYear = $selectedYearId ? AcademicYear::find($selectedYearId) : $currentYear;

        $students = Student::with([
            'siblings',
            'academicRecords' => function ($query) use ($selectedYearId) {
                if ($selectedYearId) {
                    $query->where('academic_year_id', $selectedYearId);
                }
                $query->with('classroom');
            },
        ])
            ->orderBy('unit')
            ->orderBy('nama')
            ->get()
            ->map(function (Student $student) {
                $record = $student->academicRecords->first();

                return [
                    // Identitas
                    'nisn' => $student->nisn,
                    'nama' => $student->nama,
                    'nipd' => $student->nipd,
                    'angkatan' => $student->angkatan,
                    'nik' => $student->nik,
                    'no_kk' => $student->no_kk,
                    'jk' => $student->jk,
                    'tempat_lahir' => $student->tempat_lahir,
                    'tanggal_lahir' => $student->tanggal_lahir ? $student->tanggal_lahir->format('Y-m-d') : null,
                    'agama' => $student->agama,
                    'no_wa' => $student->no_wa,
                    'sekolah_asal' => $student->sekolah_asal,
                    // Akademik
                    'unit' => $student->unit,
                    'unit_pesantren' => $student->unit_pesantren ?? $record?->unit_pesantren,
                    'program' => $student->program ?? $record?->program,
                    'jenjang' => $record?->classroom?->jenjang ?? $student->diterima_di_jenjang,
                    'tingkat' => $record?->tingkat,
                    'kelas' => $record?->classroom?->name,
                    'student_status' => in_array($record?->student_status, ['Tinggal Kelas', 'mengulang']) ? 'Tinggal Kelas' : 'Naik Kelas',
                    'ket_tidak_aktif' => $record?->ket_tidak_aktif,
                    // Registrasi
                    'status_keaktifan' => $student->status_keaktifan ?: 'Aktif',
                    'keterangan_tidak_aktif' => $student->keterangan_tidak_aktif,
                    'tanggal_tidak_aktif' => $student->tanggal_tidak_aktif ? $student->tanggal_tidak_aktif->format('Y-m-d') : null,
                    'status_keluarga' => $student->status_keluarga,
                    'anak_ke' => $student->anak_ke,
                    'diterima_di_jenjang' => $student->diterima_di_jenjang,
                    'tanggal_diterima' => $student->tanggal_diterima ? $student->tanggal_diterima->format('Y-m-d') : null,
                    'info_psb' => $student->info_psb,
                    // Alamat
                    'jalan' => $student->jalan,
                    'rt' => $student->rt,
                    'rw' => $student->rw,
                    'dusun' => $student->dusun,
                    'desa' => $student->desa,
                    'kecamatan' => $student->kecamatan,
                    'kabupaten' => $student->kabupaten,
                    'provinsi' => $student->provinsi,
                    // Keluarga - Ayah
                    'ayah_nama' => $student->ayah_nama,
                    'ayah_tahun_lahir' => $student->ayah_tahun_lahir,
                    'ayah_pendidikan' => $student->ayah_pendidikan,
                    'ayah_pekerjaan' => $student->ayah_pekerjaan,
                    'ayah_penghasilan' => $student->ayah_penghasilan,
                    'ayah_status' => $student->ayah_status ?? 'Hidup',
                    // Keluarga - Ibu
                    'ibu_nama' => $student->ibu_nama,
                    'ibu_tahun_lahir' => $student->ibu_tahun_lahir,
                    'ibu_pendidikan' => $student->ibu_pendidikan,
                    'ibu_pekerjaan' => $student->ibu_pekerjaan,
                    'ibu_penghasilan' => $student->ibu_penghasilan,
                    'ibu_status' => $student->ibu_status ?? 'Hidup',
                    // Keluarga - Wali
                    'wali_nama' => $student->wali_nama,
                    'wali_hubungan' => $student->wali_hubungan,
                    'wali_pekerjaan' => $student->wali_pekerjaan,
                    'wali_penghasilan' => $student->wali_penghasilan,
                    // Bantuan
                    'desil' => $student->desil ?? $record?->desil,
                    'status_pip' => (bool) ($student->status_pip ?? $record?->status_pip ?? false),
                    'pip_keterangan' => $student->pip_keterangan ?? $record?->pip_keterangan,
                    'status_kip' => (bool) ($student->status_kip ?? $record?->status_kip ?? false),
                    'no_kip' => $student->no_kip ?? $record?->no_kip,
                ];
            });

        return Inertia::render('Students/InlineEdit', [
            'students' => $students,
            'selectedAcademicYear' => $selectedYear,
            'allAcademicYears' => $academicYearsList,
            'allUnits' => fn () => SchoolUnit::orderBy('name')->pluck('name')->toArray(),
            'allLevels' => fn () => SchoolLevel::orderBy('name')->pluck('name')->toArray(),
            'allPrograms' => fn () => SchoolProgram::orderBy('name')->pluck('name')->unique()->values()->toArray(),
            'allClassrooms' => fn () => Classroom::when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))->orderBy('name')->pluck('name')->unique()->values()->toArray(),
        ]);
    }

    /**
     * Process batch updates from inline editing.
     */
    public function inlineUpdate(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! RolePermission::canAccess($user, 'student_data', 'update')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah data siswa.');
        }

        $validated = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'target_academic_year_ids' => ['nullable', 'array'],
            'target_academic_year_ids.*' => ['integer', 'exists:academic_years,id'],
            'multi_semester_students' => ['nullable', 'array'],
            'multi_semester_students.*' => ['string'],
            'students' => ['required', 'array'],
            'students.*.nisn' => ['required', 'string'],
            'students.*.nama' => ['required', 'string'],
            'students.*.nipd' => ['nullable', 'string'],
            'students.*.angkatan' => ['nullable', 'string', 'max:50'],
            'students.*.nik' => ['nullable', 'string'],
            'students.*.no_kk' => ['nullable', 'string'],
            'students.*.jk' => ['nullable', 'string'],
            'students.*.tempat_lahir' => ['nullable', 'string'],
            'students.*.tanggal_lahir' => ['nullable', 'date'],
            'students.*.agama' => ['nullable', 'string'],
            'students.*.no_wa' => ['nullable', 'string'],
            'students.*.sekolah_asal' => ['nullable', 'string'],
            'students.*.unit' => ['nullable', 'string'],
            'students.*.unit_pesantren' => ['nullable', 'string'],
            'students.*.program' => ['nullable', 'string'],
            'students.*.jenjang' => ['nullable', 'string'],
            'students.*.tingkat' => ['nullable', 'numeric'],
            'students.*.kelas' => ['nullable', 'string'],
            'students.*.student_status' => ['nullable', 'string'],
            'students.*.ket_tidak_aktif' => ['nullable', 'string'],
            'students.*.status_keaktifan' => ['nullable', 'string'],
            'students.*.keterangan_tidak_aktif' => ['nullable', 'string'],
            'students.*.tanggal_tidak_aktif' => ['nullable', 'date'],
            'students.*.status_keluarga' => ['nullable', 'string'],
            'students.*.anak_ke' => ['nullable', 'integer'],
            'students.*.diterima_di_jenjang' => ['nullable', 'string'],
            'students.*.tanggal_diterima' => ['nullable', 'date'],
            'students.*.info_psb' => ['nullable', 'string'],
            'students.*.jalan' => ['nullable', 'string'],
            'students.*.rt' => ['nullable', 'string', 'max:10'],
            'students.*.rw' => ['nullable', 'string', 'max:10'],
            'students.*.dusun' => ['nullable', 'string'],
            'students.*.desa' => ['nullable', 'string'],
            'students.*.kecamatan' => ['nullable', 'string'],
            'students.*.kabupaten' => ['nullable', 'string'],
            'students.*.provinsi' => ['nullable', 'string'],
            'students.*.ayah_nama' => ['nullable', 'string'],
            'students.*.ayah_tahun_lahir' => ['nullable', 'integer'],
            'students.*.ayah_pendidikan' => ['nullable', 'string'],
            'students.*.ayah_pekerjaan' => ['nullable', 'string'],
            'students.*.ayah_penghasilan' => ['nullable', 'string'],
            'students.*.ayah_status' => ['nullable', 'string'],
            'students.*.ibu_nama' => ['nullable', 'string'],
            'students.*.ibu_tahun_lahir' => ['nullable', 'integer'],
            'students.*.ibu_pendidikan' => ['nullable', 'string'],
            'students.*.ibu_pekerjaan' => ['nullable', 'string'],
            'students.*.ibu_penghasilan' => ['nullable', 'string'],
            'students.*.ibu_status' => ['nullable', 'string'],
            'students.*.wali_nama' => ['nullable', 'string'],
            'students.*.wali_hubungan' => ['nullable', 'string'],
            'students.*.wali_pekerjaan' => ['nullable', 'string'],
            'students.*.wali_penghasilan' => ['nullable', 'string'],
            'students.*.desil' => ['nullable', 'integer'],
            'students.*.status_pip' => ['nullable', 'boolean'],
            'students.*.pip_keterangan' => ['nullable', 'string'],
            'students.*.status_kip' => ['nullable', 'boolean'],
            'students.*.no_kip' => ['nullable', 'string'],
        ]);

        $selectedYearId = $request->input('academic_year_id')
            ?? session('selected_academic_year_id')
            ?? AcademicYear::current()?->id;

        $targetAcademicYearIds = $request->input('target_academic_year_ids') ?? [];
        $multiSemesterStudents = $request->input('multi_semester_students') ?? [];
        $currentYear = AcademicYear::current();

        DB::transaction(function () use ($validated, $selectedYearId, $targetAcademicYearIds, $multiSemesterStudents) {
            foreach ($validated['students'] as $d) {
                $student = Student::where('nisn', $d['nisn'])->first();
                if (! $student) {
                    continue;
                }

                // 1. Update Student model (all columns on unified students table)
                $studentUpdateData = [
                    'nama' => $d['nama'],
                    'nipd' => $d['nipd'] ?? $student->nipd,
                    'nik' => $d['nik'] ?? $student->nik,
                    'no_kk' => $d['no_kk'] ?? $student->no_kk,
                    'jk' => $d['jk'] ?? $student->jk,
                    'tempat_lahir' => $d['tempat_lahir'] ?? $student->tempat_lahir,
                    'tanggal_lahir' => $d['tanggal_lahir'] ?? $student->tanggal_lahir,
                    'agama' => $d['agama'] ?? $student->agama,
                    'no_wa' => $d['no_wa'] ?? $student->no_wa,
                    'sekolah_asal' => $d['sekolah_asal'] ?? $student->sekolah_asal,
                    'unit' => $d['unit'] ?? $student->unit,
                    'status_keluarga' => $d['status_keluarga'] ?? $student->status_keluarga,
                    'anak_ke' => $d['anak_ke'] ?? $student->anak_ke,
                    'diterima_di_jenjang' => $d['diterima_di_jenjang'] ?? $student->diterima_di_jenjang,
                    'tanggal_diterima' => $d['tanggal_diterima'] ?? $student->tanggal_diterima,
                    'info_psb' => $d['info_psb'] ?? $student->info_psb,
                    'jalan' => $d['jalan'] ?? $student->jalan,
                    'rt' => $d['rt'] ?? $student->rt,
                    'rw' => $d['rw'] ?? $student->rw,
                    'dusun' => $d['dusun'] ?? $student->dusun,
                    'desa' => $d['desa'] ?? $student->desa,
                    'kecamatan' => $d['kecamatan'] ?? $student->kecamatan,
                    'kabupaten' => $d['kabupaten'] ?? $student->kabupaten,
                    'provinsi' => $d['provinsi'] ?? $student->provinsi,
                    'ayah_nama' => $d['ayah_nama'] ?? $student->ayah_nama,
                    'ayah_tahun_lahir' => $d['ayah_tahun_lahir'] ?? $student->ayah_tahun_lahir,
                    'ayah_pendidikan' => $d['ayah_pendidikan'] ?? $student->ayah_pendidikan,
                    'ayah_pekerjaan' => $d['ayah_pekerjaan'] ?? $student->ayah_pekerjaan,
                    'ayah_penghasilan' => $d['ayah_penghasilan'] ?? $student->ayah_penghasilan,
                    'ayah_status' => $d['ayah_status'] ?? $student->ayah_status,
                    'ibu_nama' => $d['ibu_nama'] ?? $student->ibu_nama,
                    'ibu_tahun_lahir' => $d['ibu_tahun_lahir'] ?? $student->ibu_tahun_lahir,
                    'ibu_pendidikan' => $d['ibu_pendidikan'] ?? $student->ibu_pendidikan,
                    'ibu_pekerjaan' => $d['ibu_pekerjaan'] ?? $student->ibu_pekerjaan,
                    'ibu_penghasilan' => $d['ibu_penghasilan'] ?? $student->ibu_penghasilan,
                    'ibu_status' => $d['ibu_status'] ?? $student->ibu_status,
                    'wali_nama' => $d['wali_nama'] ?? $student->wali_nama,
                    'wali_hubungan' => $d['wali_hubungan'] ?? $student->wali_hubungan,
                    'wali_pekerjaan' => $d['wali_pekerjaan'] ?? $student->wali_pekerjaan,
                    'wali_penghasilan' => $d['wali_penghasilan'] ?? $student->wali_penghasilan,
                    'desil' => $d['desil'] ?? $student->desil,
                    'status_pip' => $d['status_pip'] ?? $student->status_pip,
                    'pip_keterangan' => $d['pip_keterangan'] ?? $student->pip_keterangan,
                    'status_kip' => $d['status_kip'] ?? $student->status_kip,
                    'no_kip' => $d['no_kip'] ?? $student->no_kip,
                ];

                if (array_key_exists('unit_pesantren', $d)) {
                    $studentUpdateData['unit_pesantren'] = $d['unit_pesantren'] ?: null;
                }
                if (array_key_exists('angkatan', $d)) {
                    $studentUpdateData['angkatan'] = $d['angkatan'] ?: null;
                }

                // Handle status_keaktifan & keterangan_tidak_aktif (Master Data di students)
                $hasStatusKeaktifan = array_key_exists('status_keaktifan', $d) && ! empty($d['status_keaktifan']);
                $hasKetTidakAktif = array_key_exists('keterangan_tidak_aktif', $d);

                if ($hasStatusKeaktifan) {
                    $rawMaster = trim((string) $d['status_keaktifan']);
                    if (strcasecmp($rawMaster, 'lulus') === 0) {
                        $studentUpdateData['status_keaktifan'] = 'Lulus';
                        $studentUpdateData['keterangan_tidak_aktif'] = 'Lulus';
                        $studentUpdateData['tanggal_tidak_aktif'] = $d['tanggal_tidak_aktif'] ?? $student->tanggal_tidak_aktif ?? now()->toDateString();
                    } elseif (strcasecmp($rawMaster, 'tidak aktif') === 0 || str_contains(strtolower($rawMaster), 'tidak')) {
                        $studentUpdateData['status_keaktifan'] = 'Tidak Aktif';
                        $studentUpdateData['keterangan_tidak_aktif'] = $hasKetTidakAktif ? ($d['keterangan_tidak_aktif'] ?: 'Lainnya') : ($student->keterangan_tidak_aktif ?: 'Lainnya');
                        $studentUpdateData['tanggal_tidak_aktif'] = $d['tanggal_tidak_aktif'] ?? $student->tanggal_tidak_aktif ?? now()->toDateString();
                    } else {
                        $studentUpdateData['status_keaktifan'] = 'Aktif';
                        $studentUpdateData['keterangan_tidak_aktif'] = null;
                        $studentUpdateData['tanggal_tidak_aktif'] = null;
                    }
                } elseif ($hasKetTidakAktif) {
                    $ketRaw = trim((string) ($d['keterangan_tidak_aktif'] ?? ''));
                    if (empty($ketRaw)) {
                        $studentUpdateData['status_keaktifan'] = 'Aktif';
                        $studentUpdateData['keterangan_tidak_aktif'] = null;
                        $studentUpdateData['tanggal_tidak_aktif'] = null;
                    } elseif (strcasecmp($ketRaw, 'lulus') === 0) {
                        $studentUpdateData['status_keaktifan'] = 'Lulus';
                        $studentUpdateData['keterangan_tidak_aktif'] = 'Lulus';
                        $studentUpdateData['tanggal_tidak_aktif'] = $d['tanggal_tidak_aktif'] ?? $student->tanggal_tidak_aktif ?? now()->toDateString();
                    } else {
                        $studentUpdateData['status_keaktifan'] = 'Tidak Aktif';
                        $studentUpdateData['keterangan_tidak_aktif'] = $ketRaw;
                        $studentUpdateData['tanggal_tidak_aktif'] = $d['tanggal_tidak_aktif'] ?? $student->tanggal_tidak_aktif ?? now()->toDateString();
                    }
                }

                // Tentukan target Tahun Ajaran untuk siswa ini
                $isMulti = in_array($student->nisn, $multiSemesterStudents);
                if ($isMulti && ! empty($targetAcademicYearIds)) {
                    $studentYearIds = $targetAcademicYearIds;
                } else {
                    $selectedYear = $selectedYearId ? AcademicYear::find($selectedYearId) : null;
                    $studentYearIds = $selectedYear
                        ? AcademicYear::where('name', $selectedYear->name)->pluck('id')->all()
                        : ($selectedYearId ? [$selectedYearId] : []);
                }

                // Status per semester (strictly Naik Kelas / Tinggal Kelas)
                $rawStatus = isset($d['student_status']) ? strtolower(trim((string) $d['student_status'])) : null;
                $statusVal = (in_array($rawStatus, ['tinggal kelas', 'mengulang'])) ? 'Tinggal Kelas' : 'Naik Kelas';

                // Sinkronisasi status keaktifan & keterangan ke semester yang ditargetkan
                $finalStatusKeaktifan = $studentUpdateData['status_keaktifan'] ?? $student->status_keaktifan ?? 'Aktif';
                $finalKetTidakAktif = $finalStatusKeaktifan === 'Aktif' ? null : ($studentUpdateData['keterangan_tidak_aktif'] ?? $student->keterangan_tidak_aktif);
                $finalTglTidakAktif = $finalStatusKeaktifan === 'Aktif' ? null : ($studentUpdateData['tanggal_tidak_aktif'] ?? $student->tanggal_tidak_aktif);

                $student->update($studentUpdateData);

                // 2. Update / Create StudentAcademicRecord di setiap target academic year
                foreach ($studentYearIds as $yId) {
                    $record = StudentAcademicRecord::where('student_id', $student->id)
                        ->where('academic_year_id', $yId)
                        ->first();

                    if (! $record) {
                        $record = StudentAcademicRecord::create([
                            'student_id' => $student->id,
                            'academic_year_id' => $yId,
                            'student_status' => $statusVal,
                            'ket_tidak_aktif' => $finalKetTidakAktif,
                            'tanggal_tidak_aktif' => $finalTglTidakAktif,
                        ]);
                    } else {
                        $record->update([
                            'student_status' => $statusVal,
                            'ket_tidak_aktif' => $finalKetTidakAktif,
                            'tanggal_tidak_aktif' => $finalTglTidakAktif,
                        ]);
                    }

                    $classroomId = $record->classroom_id;
                    if (! empty($d['kelas']) && ! empty($d['unit'])) {
                        $classroom = Classroom::firstOrCreate(
                            [
                                'name' => $d['kelas'],
                                'unit' => $d['unit'],
                                'academic_year_id' => $yId,
                            ],
                            [
                                'name' => $d['kelas'],
                                'unit' => $d['unit'],
                                'academic_year_id' => $yId,
                                'jenjang' => $d['jenjang'] ?? null,
                                'grade' => $d['tingkat'] ?? null,
                            ]
                        );
                        $classroomId = $classroom->id;
                    }

                    $recordUpdateData = [
                        'unit' => $student->unit,
                        'jenjang' => $student->jenjang,
                        'tingkat' => $d['tingkat'] ?? $record->tingkat,
                        'program' => $d['program'] ?? $record->program,
                        'student_status' => $statusVal,
                        'ket_tidak_aktif' => $recordKet,
                        'classroom_id' => $classroomId,
                        'desil' => $d['desil'] ?? $record->desil,
                        'status_pip' => $d['status_pip'] ?? $record->status_pip,
                        'pip_keterangan' => $d['pip_keterangan'] ?? $record->pip_keterangan,
                        'status_kip' => $d['status_kip'] ?? $record->status_kip,
                        'no_kip' => $d['no_kip'] ?? $record->no_kip,
                    ];

                    if (array_key_exists('unit_pesantren', $d)) {
                        $recordUpdateData['unit_pesantren'] = $d['unit_pesantren'] ?: null;
                    }

                    $record->update($recordUpdateData);
                }
            }
        });

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil diperbarui secara massal.');
    }

    /**
     * Handle batch photo upload, matching filenames containing NISN to students.
     */
    public function uploadPhotos(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! RolePermission::canAccess($user, 'student_data', 'update') && ! RolePermission::canAccess($user, 'student_data', 'create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengunggah foto siswa.');
        }

        // Mode ZIP
        if ($request->hasFile('zip')) {
            return $this->handleZipUpload($request);
        }

        // Mode foto individu (maks 20 karena batas PHP max_file_uploads)
        $request->validate([
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], [
            'photos.required' => 'Pilih minimal 1 file foto.',
            'photos.*.image' => 'File harus berupa gambar.',
            'photos.*.mimes' => 'Format foto harus jpeg, png, jpg, atau webp.',
            'photos.*.max' => 'Ukuran foto maksimal 5MB per file.',
        ]);

        [$matched, $unmatched, $total] = $this->processPhotoFiles($request->file('photos'));

        return $this->buildUploadResponse($matched, $total, $unmatched);
    }

    /**
     * Proses upload ZIP berisi foto-foto siswa.
     */
    private function handleZipUpload(Request $request): RedirectResponse
    {
        $request->validate([
            'zip' => ['required', 'file', 'mimes:zip', 'max:102400'],
        ], [
            'zip.required' => 'File ZIP wajib diunggah.',
            'zip.mimes' => 'File harus berformat .zip.',
            'zip.max' => 'Ukuran ZIP maksimal 100MB.',
        ]);

        $zipPath = $request->file('zip')->getRealPath();
        $zip = new \ZipArchive;

        if ($zip->open($zipPath) !== true) {
            return back()->with('error', 'File ZIP tidak valid atau rusak.');
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $extractedFiles = [];

        $tmpDir = sys_get_temp_dir().'/student_photos_'.uniqid();
        @mkdir($tmpDir, 0755, true);

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            // Lewati folder dan file bukan gambar
            if (str_ends_with($name, '/') || ! in_array($ext, $allowedExtensions)) {
                continue;
            }

            $basename = basename($name);
            $tmpPath = $tmpDir.'/'.$basename;
            file_put_contents($tmpPath, $zip->getFromIndex($i));
            $extractedFiles[] = ['path' => $tmpPath, 'name' => $basename];
        }

        $zip->close();

        $allNisns = Student::pluck('nisn')->toArray();
        $matched = 0;
        $unmatched = [];

        foreach ($extractedFiles as $fileInfo) {
            $originalName = pathinfo($fileInfo['name'], PATHINFO_FILENAME);
            $ext = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));

            $foundNisn = null;
            foreach ($allNisns as $nisn) {
                if (str_contains($originalName, $nisn)) {
                    $foundNisn = $nisn;
                    break;
                }
            }

            if (! $foundNisn) {
                $unmatched[] = $fileInfo['name'];
                @unlink($fileInfo['path']);

                continue;
            }

            $storagePath = 'student-photos/'.$foundNisn.'.jpg';
            $existingStudent = Student::where('nisn', $foundNisn)->first();

            if ($existingStudent?->foto && $existingStudent->foto !== $storagePath) {
                Storage::disk('local')->delete($existingStudent->foto);
            }

            $destAbsolutePath = Storage::disk('local')->path($storagePath);
            $this->compressAndSaveAsJpeg($fileInfo['path'], $destAbsolutePath, 1000, 1000, 82);
            @unlink($fileInfo['path']);

            Student::where('nisn', $foundNisn)->update(['foto' => $storagePath]);
            $matched++;
        }

        // Bersihkan temp dir
        @rmdir($tmpDir);

        return $this->buildUploadResponse($matched, count($extractedFiles), $unmatched);
    }

    /**
     * Proses array file foto dan cocokkan dengan NISN.
     *
     * @param  array<UploadedFile>  $photos
     * @return array{int, array<string>, int}
     */
    private function processPhotoFiles(array $photos): array
    {
        $allNisns = Student::pluck('nisn')->toArray();
        $matched = 0;
        $unmatched = [];

        foreach ($photos as $photo) {
            $originalName = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);

            $foundNisn = null;
            foreach ($allNisns as $nisn) {
                if (str_contains($originalName, $nisn)) {
                    $foundNisn = $nisn;
                    break;
                }
            }

            if (! $foundNisn) {
                $unmatched[] = $photo->getClientOriginalName();

                continue;
            }

            $storagePath = 'student-photos/'.$foundNisn.'.jpg';
            $existingStudent = Student::where('nisn', $foundNisn)->first();

            if ($existingStudent?->foto && $existingStudent->foto !== $storagePath) {
                Storage::disk('local')->delete($existingStudent->foto);
            }

            $destAbsolutePath = Storage::disk('local')->path($storagePath);
            $this->compressAndSaveAsJpeg($photo->getRealPath(), $destAbsolutePath, 1000, 1000, 82);
            Student::where('nisn', $foundNisn)->update(['foto' => $storagePath]);
            $matched++;
        }

        return [$matched, $unmatched, count($photos)];
    }

    /**
     * Buat response redirect dengan pesan ringkasan hasil upload.
     *
     * @param  array<string>  $unmatched
     */
    private function buildUploadResponse(int $matched, int $total, array $unmatched): RedirectResponse
    {
        $msg = "{$matched} dari {$total} foto berhasil dicocokkan dan disimpan.";

        if (count($unmatched) > 0) {
            $failedList = implode(', ', array_slice($unmatched, 0, 5));
            $msg .= ' File tidak cocok: '.$failedList;
            if (count($unmatched) > 5) {
                $msg .= ' dan '.(count($unmatched) - 5).' lainnya';
            }
            $msg .= '.';
        }

        return back()->with($matched > 0 ? 'success' : 'error', $msg);
    }

    /**
     * Tampilkan foto siswa secara aman (hanya untuk user yang login).
     */
    public function showPhoto(string $filename): BinaryFileResponse|HttpResponse
    {
        $path = 'student-photos/'.$filename;

        if (! Storage::disk('local')->exists($path)) {
            abort(404, 'Foto tidak ditemukan.');
        }

        return response()->file(Storage::disk('local')->path($path), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Kompres dan simpan gambar sebagai format JPEG (.jpg) menggunakan PHP GD jika tersedia.
     */
    private function compressAndSaveAsJpeg(string $sourcePath, string $targetPath, int $maxWidth = 1000, int $maxHeight = 1000, int $quality = 82): bool
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

        // Hitung aspek rasio proporsional
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

        // Latar belakang putih bersih (jika sumber PNG transparan)
        $white = imagecolorallocate($targetImage, 255, 255, 255);
        imagefilledrectangle($targetImage, 0, 0, $width, $height, $white);

        imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);

        $saved = imagejpeg($targetImage, $targetPath, $quality);

        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        return $saved;
    }

    /**
     * Stream the XLSX import template as a download.
     */
    public function downloadTemplate(Request $request): HttpResponse
    {
        $user = $request->user();
        if (! RolePermission::canAccess($user, 'student_data', 'export')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengunduh template data siswa.');
        }

        // Ambil data jenjang, unit sekolah, program, dan kelas dari Pengaturan > Akademik
        $levels = SchoolLevel::orderBy('order')->pluck('name')->filter()->values();
        if ($levels->isEmpty()) {
            $levels = collect(['SD', 'SMP']);
        }

        $units = SchoolUnit::with('levels')
            ->get()
            ->sortBy([
                fn ($a, $b) => ($a->levels->min('order') ?? 0) <=> ($b->levels->min('order') ?? 0),
                fn ($a, $b) => strcmp($a->name, $b->name),
            ])
            ->pluck('name')
            ->filter()
            ->values();

        if ($units->isEmpty()) {
            $units = collect(['SDIT Ulil Albab', 'SMPIT Ulil Albab']);
        }

        $programs = SchoolProgram::orderBy('name')->pluck('name')->filter()->unique()->values();
        $classes = SchoolClass::orderBy('name')->pluck('name')->filter()->unique()->values();

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('Sistem Manajemen Sekolah')
            ->setTitle('Template Import Data Siswa')
            ->setSubject('Template Import Siswa Semua Jenjang');

        $this->buildDropdownsSheet($spreadsheet, $levels, $units, $programs, $classes);
        $this->buildSheet1($spreadsheet, $levels, $units);
        $this->buildSheet2($spreadsheet);

        // Remove default sheet 0 if it was created by new Spreadsheet but we shifted things.
        // Actually createSheet(0) shifts existing sheets, so index 1 is now Sheet 1.
        $spreadsheet->setActiveSheetIndex(1);

        ob_start();
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        $content = ob_get_clean();

        $filename = 'template_import_siswa_semua_jenjang_'.date('Ymd').'.xlsx';

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function buildDropdownsSheet(Spreadsheet $spreadsheet, $levels, $units, $programs, $classes): void
    {
        // Insert as the very first sheet
        $sheet = $spreadsheet->createSheet(0);
        $sheet->setTitle('Dropdowns');

        $sheet->setCellValue('A1', 'Jenjang');
        foreach ($levels as $index => $val) {
            $sheet->setCellValue('A'.($index + 2), $val);
        }

        $sheet->setCellValue('B1', 'Units');
        foreach ($units as $index => $val) {
            $sheet->setCellValue('B'.($index + 2), $val);
        }

        $sheet->setCellValue('C1', 'Programs');
        foreach ($programs as $index => $val) {
            $sheet->setCellValue('C'.($index + 2), $val);
        }

        $sheet->setCellValue('D1', 'Classes');
        foreach ($classes as $index => $val) {
            $sheet->setCellValue('D'.($index + 2), $val);
        }

        // Hide the sheet
        $sheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
    }

    // ----------------------------------------------------------------
    // XLSX Template Sheet Builders
    // ----------------------------------------------------------------

    private function buildSheet1(Spreadsheet $spreadsheet, $levels, $units): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('1. Data Siswa');

        $headers = [
            'A' => ['NISN', 12],
            'B' => ['Nama Lengkap', 30],
            'C' => ['NIPD', 12],
            'D' => ['Angkatan', 15],
            'E' => ['Jenjang', 12],
            'F' => ['Unit', 35],
            'G' => ['JK (L/P)', 10],
            'H' => ['Tempat Lahir', 20],
            'I' => ['Tanggal Lahir (YYYY-MM-DD)', 24],
            'J' => ['NIK', 18],
            'K' => ['Agama', 12],
            'L' => ['Jalan', 20],
            'M' => ['RT', 6],
            'N' => ['RW', 6],
            'O' => ['Dusun', 18],
            'P' => ['Desa/Kelurahan', 20],
            'Q' => ['Kecamatan', 20],
            'R' => ['Kabupaten', 20],
            'S' => ['Provinsi', 20],
            'T' => ['Jenis Tinggal', 20],
            'U' => ['Alat Transportasi', 20],
            'V' => ['No. WA 1', 16],
            'W' => ['No. WA 2', 16],
            'X' => ['Email', 25],
            'Y' => ['Nama Ayah', 25],
            'Z' => ['Tahun Lahir Ayah', 16],
            'AA' => ['Pendidikan Ayah', 18],
            'AB' => ['Pekerjaan Ayah', 20],
            'AC' => ['Penghasilan Ayah', 28],
            'AD' => ['NIK Ayah', 18],
            'AE' => ['Status Ayah (Hidup/Meninggal)', 28],
            'AF' => ['Nama Ibu', 25],
            'AG' => ['Tahun Lahir Ibu', 16],
            'AH' => ['Pendidikan Ibu', 18],
            'AI' => ['Pekerjaan Ibu', 20],
            'AJ' => ['Penghasilan Ibu', 28],
            'AK' => ['NIK Ibu', 18],
            'AL' => ['Status Ibu (Hidup/Meninggal)', 28],
            'AM' => ['Nama Wali', 25],
            'AN' => ['Tahun Lahir Wali', 16],
            'AO' => ['Pendidikan Wali', 18],
            'AP' => ['Pekerjaan Wali', 20],
            'AQ' => ['Penghasilan Wali', 28],
            'AR' => ['NIK Wali', 18],
            'AS' => ['Hubungan Wali', 18],
            'AT' => ['Penerima KIP (Ya/Tidak)', 25],
            'AU' => ['Nomor KIP', 20],
            'AV' => ['Nama di KIP', 25],
            'AW' => ['Kelayakan PIP (Ya/Tidak)', 25],
            'AX' => ['Alasan Layak PIP', 30],
            'AY' => ['Kebutuhan Khusus', 20],
            'AZ' => ['Sekolah Asal', 30],
            'BA' => ['Anak ke -', 10],
            'BB' => ['No KK', 18],
            'BC' => ['Jarak Rumah ke Sekolah', 25],
            'BD' => ['Apakah Memiliki Usaha (Ya/Tidak)', 30],
            'BE' => ['Jenis Usaha', 30],
            'BF' => ['Desil', 15],
            'BG' => ['Diterima di Jenjang / Kelas', 30],
            'BH' => ['Tanggal Diterima (YYYY-MM-DD)', 25],
            'BI' => ['Sumber Informasi PSB', 30],
            'BJ' => ['Status Registrasi', 20],
            'BK' => ['Status Keaktifan (Aktif/Tidak Aktif)', 30],
            'BL' => ['Keterangan Tidak Aktif', 32],
            'BM' => ['Tanggal Tidak Aktif (YYYY-MM-DD)', 28],
        ];

        $this->applySheetHeaders($sheet, $headers, '004B23');

        // Format kolom Tanggal Lahir, Tanggal Diterima, & Tanggal Tidak Aktif
        $sheet->getStyle('I2:I1000')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
        $sheet->getStyle('BH2:BH1000')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
        $sheet->getStyle('BM2:BM1000')->getNumberFormat()->setFormatCode('yyyy-mm-dd');

        // Format kolom ID/Nomor sebagai Teks (mencegah angka dengan awalan 0 atau simbol / berubah)
        $textCols = ['A', 'C', 'D', 'J', 'V', 'W', 'AD', 'AK', 'AR', 'AU', 'BB', 'BK', 'BL'];
        foreach ($textCols as $col) {
            $sheet->getStyle($col.'2:'.$col.'1000')->getNumberFormat()->setFormatCode('@');
        }

        $sdUnit = $units->first(fn ($u) => str_contains(strtoupper($u), 'SD')) ?? ($units->first() ?? 'SDIT Ulil Albab');
        $smpUnit = $units->first(fn ($u) => str_contains(strtoupper($u), 'SMP')) ?? ($units->skip(1)->first() ?? 'SMPIT Ulil Albab');

        $exampleRows = [
            [
                '0051234001', 'Ahmad Fauzi Rahman', '10231001', // A, B, C
                '1', 'SD', $sdUnit, // D (Contoh Angkatan: 1, 2, 5, 7, 9, dll.), E, F
                'L', 'Karanganyar', '2014-03-15', // G, H, I
                '3313150101080001', 'Islam', 'Jl. Lawu No. 12', // J, K, L
                '03', '05', 'Ngemplak', 'Desa Makmur', 'Colomadu', 'Karanganyar', 'Jawa Tengah', // M, N, O, P, Q, R, S
                'Bersama Orang Tua', 'Jalan Kaki', '081234567001', '081234567002', 'ahmad.fauzi@example.com', // T, U, V, W, X
                'Fauzi Hidayat', '1980', 'SMA/SMK', 'Wiraswasta', 'Rp 3.000.000 - Rp 5.000.000', '3313150101800001', 'Hidup', // Y, Z, AA, AB, AC, AD, AE
                'Siti Rahmawati', '1984', 'SMA/SMK', 'Ibu Rumah Tangga', 'Kurang dari Rp 1.000.000', '3313150101840002', 'Hidup', // AF, AG, AH, AI, AJ, AK, AL
                '', '', '', '', '', '', '', // AM, AN, AO, AP, AQ, AR, AS
                'Ya', '6071012345670001', 'Ahmad Fauzi Rahman', // AT, AU, AV
                'Ya', 'Penerima PIP tahap 1', 'Tidak ada', // AW, AX, AY
                'TK Aisyiyah Colomadu', '1', '3313151503140001', 'Kurang dari 1 km', // AZ, BA, BB, BC
                'Tidak', '-', '1', // BD, BE, BF
                'SDIT', '2025-07-01', 'Brosur', 'Siswa Baru', // BG, BH, BI, BJ
                'Aktif', '', '', // BK, BL, BM
            ],
            [
                '0051234002', 'Aisyah Azzahra', '10232001', // A, B, C
                '5', 'SMP', $smpUnit, // D (Contoh Angkatan: 1, 2, 5, 7, 9, dll.), E, F
                'P', 'Surakarta', '2011-08-20', // G, H, I
                '3313154208110002', 'Islam', 'Jl. Slamet Riyadi No. 45', // J, K, L
                '01', '02', 'Kerten', 'Kerten', 'Laweyan', 'Surakarta', 'Jawa Tengah', // M, N, O, P, Q, R, S
                'Bersama Orang Tua', 'Sepeda Motor', '081234567003', '081234567004', 'aisyah.azzahra@example.com', // T, U, V, W, X
                'Rahmat Hidayat', '1978', 'S1', 'PNS / ASN', 'Rp 5.000.000 - Rp 10.000.000', '3313150101780002', 'Hidup', // Y, Z, AA, AB, AC, AD, AE
                'Nurul Khotimah', '1982', 'D3', 'Wiraswasta', 'Rp 2.000.000 - Rp 3.000.000', '3313150101820003', 'Hidup', // AF, AG, AH, AI, AJ, AK, AL
                '', '', '', '', '', '', '', // AM, AN, AO, AP, AQ, AR, AS
                'Tidak', '', '', // AT, AU, AV
                'Tidak', '', 'Tidak ada', // AW, AX, AY
                'SDIT Ulil Albab', '2', '3313154208110001', '2 km', // AZ, BA, BB, BC
                'Ya', 'Toko Kelontong', '2', // BD, BE, BF
                'SMPIT', '2025-07-01', 'Media Sosial', 'Siswa Baru', // BG, BH, BI, BJ
                'Tidak Aktif', 'Mutasi Keluar', '2025-08-10', // BK, BL, BM
            ],
        ];

        $sheet->fromArray($exampleRows, null, 'A2');

        $jenjangLastRow = max(count($levels) + 1, 2);
        $unitLastRow = max(count($units) + 1, 2);

        for ($row = 2; $row <= 1000; $row++) {
            // Dropdown Jenjang (dari Dropdowns sheet kolom A)
            $valJenjang = $sheet->getCell('E'.$row)->getDataValidation();
            $valJenjang->setType(DataValidation::TYPE_LIST);
            $valJenjang->setAllowBlank(true);
            $valJenjang->setShowDropDown(true);
            $valJenjang->setShowErrorMessage(true);
            $valJenjang->setErrorStyle(DataValidation::STYLE_STOP);
            $valJenjang->setErrorTitle('Invalid Input');
            $valJenjang->setError('Pilih Jenjang yang valid dari daftar.');
            $valJenjang->setFormula1("Dropdowns!\$A\$2:\$A\${$jenjangLastRow}");

            // Dropdown Unit (dari Dropdowns sheet kolom B)
            $valUnit = $sheet->getCell('F'.$row)->getDataValidation();
            $valUnit->setType(DataValidation::TYPE_LIST);
            $valUnit->setAllowBlank(true);
            $valUnit->setShowDropDown(true);
            $valUnit->setShowErrorMessage(true);
            $valUnit->setErrorStyle(DataValidation::STYLE_STOP);
            $valUnit->setErrorTitle('Invalid Input');
            $valUnit->setError('Pilih Unit yang valid dari daftar.');
            $valUnit->setFormula1("Dropdowns!\$B\$2:\$B\${$unitLastRow}");

            $valJk = $sheet->getCell('G'.$row)->getDataValidation();
            $valJk->setType(DataValidation::TYPE_LIST);
            $valJk->setAllowBlank(true);
            $valJk->setShowDropDown(true);
            $valJk->setFormula1('"L,P"');

            $valStatusAyah = $sheet->getCell('AE'.$row)->getDataValidation();
            $valStatusAyah->setType(DataValidation::TYPE_LIST);
            $valStatusAyah->setAllowBlank(true);
            $valStatusAyah->setShowDropDown(true);
            $valStatusAyah->setFormula1('"Hidup,Meninggal"');

            $valStatusIbu = $sheet->getCell('AL'.$row)->getDataValidation();
            $valStatusIbu->setType(DataValidation::TYPE_LIST);
            $valStatusIbu->setAllowBlank(true);
            $valStatusIbu->setShowDropDown(true);
            $valStatusIbu->setFormula1('"Hidup,Meninggal"');

            $valBisnis = $sheet->getCell('BD'.$row)->getDataValidation();
            $valBisnis->setType(DataValidation::TYPE_LIST);
            $valBisnis->setAllowBlank(true);
            $valBisnis->setShowDropDown(true);
            $valBisnis->setFormula1('"Ya,Tidak"');

            $valStatusRegistrasi = $sheet->getCell('BJ'.$row)->getDataValidation();
            $valStatusRegistrasi->setType(DataValidation::TYPE_LIST);
            $valStatusRegistrasi->setAllowBlank(true);
            $valStatusRegistrasi->setShowDropDown(true);
            $valStatusRegistrasi->setFormula1('"Siswa Baru,Mutasi Masuk"');

            // Dropdown Status Keaktifan (BK)
            $valStatusKeaktifan = $sheet->getCell('BK'.$row)->getDataValidation();
            $valStatusKeaktifan->setType(DataValidation::TYPE_LIST);
            $valStatusKeaktifan->setAllowBlank(true);
            $valStatusKeaktifan->setShowDropDown(true);
            $valStatusKeaktifan->setFormula1('"Aktif,Tidak Aktif"');

            // Dropdown Keterangan Tidak Aktif (BL)
            $valKetTidakAktif = $sheet->getCell('BL'.$row)->getDataValidation();
            $valKetTidakAktif->setType(DataValidation::TYPE_LIST);
            $valKetTidakAktif->setAllowBlank(true);
            $valKetTidakAktif->setShowDropDown(true);
            $valKetTidakAktif->setPromptTitle('Keterangan Tidak Aktif');
            $valKetTidakAktif->setPrompt('Hanya diisi jika Status Keaktifan Tidak Aktif.');
            $valKetTidakAktif->setShowInputMessage(true);
            $valKetTidakAktif->setFormula1('"Mutasi Keluar,Dikeluarkan,Putus Sekolah,Meninggal,Lainnya"');
        }

        $this->styleExampleRow($sheet, 2, 'A', 'BM');
        $this->styleExampleRow($sheet, 3, 'A', 'BM');
        $sheet->freezePane('A2');
    }

    private function buildSheet2(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('2. Data Saudara');

        $headers = [
            'A' => ['NISN Siswa', 12],
            'B' => ['Nama Saudara', 30],
            'C' => ['Tanggal Lahir (YYYY-MM-DD)', 26],
        ];

        $this->applySheetHeaders($sheet, $headers, 'BF360C');

        // Format kolom
        $sheet->getStyle('A2:A1000')->getNumberFormat()->setFormatCode('@'); // NISN sebagai Text
        $sheet->getStyle('C2:C1000')->getNumberFormat()->setFormatCode('yyyy-mm-dd'); // Tanggal Lahir

        $sheet->fromArray([
            ['0051234001', 'Aisyah Fauzia', '2016-05-12'],
            ['0051234001', 'Muhammad Farhan', '2019-08-23'],
            ['0051234002', 'Fatimah Zahra', '2014-02-10'],
        ], null, 'A2');

        $this->styleExampleRow($sheet, 2, 'A', 'C');
        $this->styleExampleRow($sheet, 3, 'A', 'C');
        $this->styleExampleRow($sheet, 4, 'A', 'C');
        $sheet->freezePane('A2');
    }

    /**
     * Apply styled header row.
     *
     * @param  array<string, array{string, int}>  $headers
     */
    private function applySheetHeaders($sheet, array $headers, string $colorRgb): void
    {
        foreach ($headers as $col => [$label, $width]) {
            $sheet->setCellValue("{$col}1", $label);
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $lastCol = array_key_last($headers);
        $range = "A1:{$lastCol}1";

        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colorRgb]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(36);
    }

    /**
     * Style an example data row.
     */
    private function styleExampleRow($sheet, int $row, string $fromCol, string $toCol): void
    {
        $sheet->getStyle("{$fromCol}{$row}:{$toCol}{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']],
            'font' => ['italic' => true, 'color' => ['rgb' => '2E7D32'], 'size' => 9],
        ]);
    }

    /**
     * Sinkronisasi ulang riwayat akademik seluruh siswa per tahun ajaran.
     */
    public function syncAcademicRecords(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! RolePermission::canAccess($user, 'student_data', 'update') && $user?->role !== 'superadmin') {
            abort(403, 'Akses ditolak: Anda tidak memiliki izin untuk sinkronisasi riwayat.');
        }

        Artisan::call('students:sync-academic-records');

        return redirect()->back()->with('success', 'Riwayat akademik seluruh siswa berhasil disinkronkan.');
    }
}
