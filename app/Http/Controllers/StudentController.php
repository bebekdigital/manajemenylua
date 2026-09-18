<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use App\Models\StudentFamily;
use App\Services\StudentImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StudentController extends Controller
{
    /**
     * Handle XLSX student import.
     */
    public function import(Request $request, StudentImportService $importService): RedirectResponse
    {
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

        $summaryMsg = "Berhasil memproses {$result['total_students']} siswa ({$result['created_count']} baru, {$result['updated_count']} diperbarui), {$result['family_count']} data keluarga, {$result['siblings_count']} saudara kandung, dan {$result['academic_records_count']} catatan akademik.";

        return back()
            ->with('success', $summaryMsg)
            ->with('import_summary', $result);
    }

    /**
     * Display the student directory page.
     */
    public function index(Request $request): Response
    {
        $selectedYearId = $request->query('academic_year_id') ?? session('selected_academic_year_id');
        $selectedYear = $selectedYearId
            ? AcademicYear::find($selectedYearId)
            : (AcademicYear::current() ?? AcademicYear::first());

        if ($selectedYear && session('selected_academic_year_id') !== $selectedYear->id) {
            session(['selected_academic_year_id' => $selectedYear->id]);
        }

        $students = Student::with([
            'academicRecords' => function ($query) use ($selectedYear) {
                if ($selectedYear) {
                    $query->where('academic_year_id', $selectedYear->id);
                }
                $query->with('classroom');
            },
            'family',
            'siblings',
        ])
            ->when($selectedYear, function ($query) use ($selectedYear) {
                $query->whereHas('academicRecords', function ($q) use ($selectedYear) {
                    $q->where('academic_year_id', $selectedYear->id);
                });
            })
            ->orderBy('unit')
            ->orderBy('nama')
            ->get()
            ->map(function (Student $student) {
                $family = $student->family;
                $record = $student->academicRecords->first();
                $classroom = $record?->classroom;

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
                    'program' => $student->program,
                    'jenjang' => $record?->jenjang,
                    'tingkat' => $record?->tingkat,
                    'kelas' => $classroom?->name,
                    'student_status' => $record?->student_status ?? 'aktif',
                    'no_wa' => $student->no_wa,
                    'sekolah_asal' => $student->sekolah_asal,
                    'status_keluarga' => $student->status_keluarga,
                    'anak_ke' => $student->anak_ke,
                    'diterima_di_jenjang' => $student->diterima_di_jenjang,
                    'tanggal_diterima' => $student->tanggal_diterima ? $student->tanggal_diterima->format('Y-m-d') : null,
                    'formatted_tanggal_diterima' => $student->tanggal_diterima ? $student->tanggal_diterima->translatedFormat('d F Y') : null,
                    'jalan' => $student->jalan,
                    'rt_rw' => $student->rt_rw,
                    'dusun' => $student->dusun,
                    'desa' => $student->desa,
                    'kecamatan' => $student->kecamatan,
                    'kabupaten' => $student->kabupaten,
                    'provinsi' => $student->provinsi,
                    'ayah' => $family ? [
                        'nama' => $family->ayah_nama,
                        'tahun_lahir' => $family->ayah_tahun_lahir,
                        'pekerjaan' => $family->ayah_pekerjaan,
                        'penghasilan' => $family->ayah_penghasilan,
                        'status' => $family->ayah_status,
                    ] : null,
                    'ibu' => $family ? [
                        'nama' => $family->ibu_nama,
                        'tahun_lahir' => $family->ibu_tahun_lahir,
                        'pekerjaan' => $family->ibu_pekerjaan,
                        'penghasilan' => $family->ibu_penghasilan,
                        'status' => $family->ibu_status,
                    ] : null,
                    'bisnis' => $family ? [
                        'has_bisnis' => $family->has_bisnis ?? false,
                        'jenis_bisnis' => $family->jenis_bisnis,
                    ] : null,
                    'wali' => $family && $family->wali_nama ? [
                        'nama' => $family->wali_nama,
                        'hubungan' => $family->wali_hubungan,
                        'pekerjaan' => $family->wali_pekerjaan,
                        'penghasilan' => $family->wali_penghasilan,
                    ] : null,
                    'saudara' => $student->siblings->map(fn ($s) => [
                        'nama' => $s->nama,
                        'tanggal_lahir' => $s->tanggal_lahir?->format('Y-m-d'),
                    ])->toArray(),
                    'bantuan' => [
                        'desil' => $record?->desil,
                        'pip' => $record?->status_pip ?? false,
                        'pip_keterangan' => $record?->pip_keterangan,
                        'kip' => $record?->status_kip ?? false,
                        'no_kip' => $record?->no_kip,
                    ],
                ];
            });

        return Inertia::render('Students', [
            'students' => $students,
            'selectedAcademicYear' => $selectedYear ? [
                'id' => $selectedYear->id,
                'name' => $selectedYear->name,
                'semester' => $selectedYear->semester,
                'is_active' => (bool) $selectedYear->is_active,
            ] : null,
        ]);
    }

    /**
     * Display the student detail page.
     */
    public function show(string $nisn, Request $request): Response
    {
        $selectedYearId = $request->query('academic_year_id') ?? session('selected_academic_year_id');
        $selectedYear = $selectedYearId
            ? AcademicYear::find($selectedYearId)
            : (AcademicYear::current() ?? AcademicYear::first());

        $studentModel = Student::with([
            'academicRecords' => function ($query) use ($selectedYear) {
                if ($selectedYear) {
                    $query->where('academic_year_id', $selectedYear->id);
                }
                $query->with('classroom');
            },
            'family',
            'siblings',
        ])->where('nisn', $nisn)->firstOrFail();

        $family = $studentModel->family;
        $record = $studentModel->academicRecords->first();
        $classroom = $record?->classroom;

        $studentData = [
            'nisn' => $studentModel->nisn,
            'nama' => $studentModel->nama,
            'nipd' => $studentModel->nipd,
            'jk' => $studentModel->jk,
            'ttl' => $studentModel->ttl,
            'nik' => $studentModel->nik,
            'no_kk' => $studentModel->no_kk,
            'agama' => $studentModel->agama,
            'unit' => $studentModel->unit,
            'program' => $studentModel->program,
            'jenjang' => $record?->jenjang,
            'tingkat' => $record?->tingkat,
            'kelas' => $classroom?->name,
            'student_status' => $record?->student_status ?? 'aktif',
            'no_wa' => $studentModel->no_wa,
            'sekolah_asal' => $studentModel->sekolah_asal,
            'status_keluarga' => $studentModel->status_keluarga,
            'anak_ke' => $studentModel->anak_ke,
            'diterima_di_jenjang' => $studentModel->diterima_di_jenjang,
            'tanggal_diterima' => $studentModel->tanggal_diterima ? $studentModel->tanggal_diterima->format('Y-m-d') : null,
            'formatted_tanggal_diterima' => $studentModel->tanggal_diterima ? $studentModel->tanggal_diterima->translatedFormat('d F Y') : null,
            'jalan' => $studentModel->jalan,
            'rt_rw' => $studentModel->rt_rw,
            'dusun' => $studentModel->dusun,
            'desa' => $studentModel->desa,
            'kecamatan' => $studentModel->kecamatan,
            'kabupaten' => $studentModel->kabupaten,
            'provinsi' => $studentModel->provinsi,
            'ayah' => $family ? [
                'nama' => $family->ayah_nama,
                'tahun_lahir' => $family->ayah_tahun_lahir,
                'pekerjaan' => $family->ayah_pekerjaan,
                'penghasilan' => $family->ayah_penghasilan,
                'status' => $family->ayah_status,
            ] : null,
            'ibu' => $family ? [
                'nama' => $family->ibu_nama,
                'tahun_lahir' => $family->ibu_tahun_lahir,
                'pekerjaan' => $family->ibu_pekerjaan,
                'penghasilan' => $family->ibu_penghasilan,
                'status' => $family->ibu_status,
            ] : null,
            'bisnis' => $family ? [
                'has_bisnis' => $family->has_bisnis ?? false,
                'jenis_bisnis' => $family->jenis_bisnis,
            ] : null,
            'wali' => $family && $family->wali_nama ? [
                'nama' => $family->wali_nama,
                'hubungan' => $family->wali_hubungan,
                'pekerjaan' => $family->wali_pekerjaan,
                'penghasilan' => $family->wali_penghasilan,
            ] : null,
            'saudara' => $studentModel->siblings->map(fn ($s) => [
                'nama' => $s->nama,
                'tanggal_lahir' => $s->tanggal_lahir?->format('Y-m-d'),
            ])->toArray(),
            'bantuan' => [
                'desil' => $record?->desil,
                'pip' => $record?->status_pip ?? false,
                'pip_keterangan' => $record?->pip_keterangan,
                'kip' => $record?->status_kip ?? false,
                'no_kip' => $record?->no_kip,
            ],
        ];

        return Inertia::render('StudentDetail', [
            'student' => $studentData,
            'selectedAcademicYear' => $selectedYear ? [
                'id' => $selectedYear->id,
                'name' => $selectedYear->name,
                'semester' => $selectedYear->semester,
                'is_active' => (bool) $selectedYear->is_active,
            ] : null,
        ]);
    }

    /**
     * Display the inline student editing page.
     */
    public function inlineEdit(Request $request): Response
    {
        $selectedYearId = $request->query('academic_year_id') ?? session('selected_academic_year_id');
        $selectedYear = $selectedYearId
            ? AcademicYear::find($selectedYearId)
            : (AcademicYear::current() ?? AcademicYear::first());

        if ($selectedYear && session('selected_academic_year_id') !== $selectedYear->id) {
            session(['selected_academic_year_id' => $selectedYear->id]);
        }

        $students = Student::with([
            'academicRecords' => function ($query) use ($selectedYear) {
                if ($selectedYear) {
                    $query->where('academic_year_id', $selectedYear->id);
                }
                $query->with('classroom');
            },
            'family',
            'siblings',
        ])
            ->when($selectedYear, function ($query) use ($selectedYear) {
                $query->whereHas('academicRecords', function ($q) use ($selectedYear) {
                    $q->where('academic_year_id', $selectedYear->id);
                });
            })
            ->orderBy('unit')
            ->orderBy('nama')
            ->get()
            ->map(function (Student $student) {
                $family = $student->family;
                $record = $student->academicRecords->first();
                $classroom = $record?->classroom;

                return [
                    // Identitas
                    'nisn' => $student->nisn,
                    'nama' => $student->nama,
                    'nipd' => $student->nipd,
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
                    'program' => $student->program,
                    'jenjang' => $record?->jenjang,
                    'tingkat' => $record?->tingkat,
                    'kelas' => $classroom?->name,
                    'student_status' => $record?->student_status ?? 'aktif',
                    // Registrasi
                    'status_keluarga' => $student->status_keluarga,
                    'anak_ke' => $student->anak_ke,
                    'diterima_di_jenjang' => $student->diterima_di_jenjang,
                    'tanggal_diterima' => $student->tanggal_diterima ? $student->tanggal_diterima->format('Y-m-d') : null,
                    // Alamat
                    'jalan' => $student->jalan,
                    'rt_rw' => $student->rt_rw,
                    'dusun' => $student->dusun,
                    'desa' => $student->desa,
                    'kecamatan' => $student->kecamatan,
                    'kabupaten' => $student->kabupaten,
                    'provinsi' => $student->provinsi,
                    // Keluarga - Ayah
                    'ayah_nama' => $family?->ayah_nama,
                    'ayah_tahun_lahir' => $family?->ayah_tahun_lahir,
                    'ayah_pendidikan' => $family?->ayah_pendidikan,
                    'ayah_pekerjaan' => $family?->ayah_pekerjaan,
                    'ayah_penghasilan' => $family?->ayah_penghasilan,
                    'ayah_status' => $family?->ayah_status,
                    // Keluarga - Ibu
                    'ibu_nama' => $family?->ibu_nama,
                    'ibu_tahun_lahir' => $family?->ibu_tahun_lahir,
                    'ibu_pendidikan' => $family?->ibu_pendidikan,
                    'ibu_pekerjaan' => $family?->ibu_pekerjaan,
                    'ibu_penghasilan' => $family?->ibu_penghasilan,
                    'ibu_status' => $family?->ibu_status,
                    // Keluarga - Wali
                    'wali_nama' => $family?->wali_nama,
                    'wali_hubungan' => $family?->wali_hubungan,
                    'wali_pekerjaan' => $family?->wali_pekerjaan,
                    'wali_penghasilan' => $family?->wali_penghasilan,
                    // Bantuan
                    'desil' => $record?->desil,
                    'status_pip' => $record?->status_pip ?? false,
                    'pip_keterangan' => $record?->pip_keterangan,
                    'status_kip' => $record?->status_kip ?? false,
                    'no_kip' => $record?->no_kip,
                ];
            });

        return Inertia::render('Students/InlineEdit', [
            'students' => $students,
            'selectedAcademicYear' => $selectedYear ? [
                'id' => $selectedYear->id,
                'name' => $selectedYear->name,
                'semester' => $selectedYear->semester,
                'is_active' => (bool) $selectedYear->is_active,
            ] : null,
        ]);
    }

    /**
     * Process batch updates from inline editing.
     */
    public function inlineUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'students' => ['required', 'array'],
            'students.*.nisn' => ['required', 'string'],
            'students.*.nama' => ['required', 'string'],
            'students.*.nipd' => ['nullable', 'string'],
            'students.*.nik' => ['nullable', 'string'],
            'students.*.no_kk' => ['nullable', 'string'],
            'students.*.jk' => ['nullable', 'string'],
            'students.*.tempat_lahir' => ['nullable', 'string'],
            'students.*.tanggal_lahir' => ['nullable', 'date'],
            'students.*.agama' => ['nullable', 'string'],
            'students.*.no_wa' => ['nullable', 'string'],
            'students.*.sekolah_asal' => ['nullable', 'string'],
            'students.*.unit' => ['nullable', 'string'],
            'students.*.program' => ['nullable', 'string'],
            'students.*.jenjang' => ['nullable', 'string'],
            'students.*.tingkat' => ['nullable', 'numeric'],
            'students.*.kelas' => ['nullable', 'string'],
            'students.*.student_status' => ['nullable', 'string'],
            'students.*.status_keluarga' => ['nullable', 'string'],
            'students.*.anak_ke' => ['nullable', 'integer'],
            'students.*.diterima_di_jenjang' => ['nullable', 'string'],
            'students.*.tanggal_diterima' => ['nullable', 'date'],
            'students.*.jalan' => ['nullable', 'string'],
            'students.*.rt_rw' => ['nullable', 'string'],
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

        $selectedYearId = session('selected_academic_year_id') ?? AcademicYear::current()?->id;

        DB::transaction(function () use ($validated, $selectedYearId) {
            foreach ($validated['students'] as $d) {
                $student = Student::where('nisn', $d['nisn'])->first();
                if (! $student) {
                    continue;
                }

                // Update student base data
                $student->update([
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
                    'program' => $d['program'] ?? $student->program,
                    'status_keluarga' => $d['status_keluarga'] ?? $student->status_keluarga,
                    'anak_ke' => $d['anak_ke'] ?? $student->anak_ke,
                    'diterima_di_jenjang' => $d['diterima_di_jenjang'] ?? $student->diterima_di_jenjang,
                    'tanggal_diterima' => $d['tanggal_diterima'] ?? $student->tanggal_diterima,
                    'jalan' => $d['jalan'] ?? $student->jalan,
                    'rt_rw' => $d['rt_rw'] ?? $student->rt_rw,
                    'dusun' => $d['dusun'] ?? $student->dusun,
                    'desa' => $d['desa'] ?? $student->desa,
                    'kecamatan' => $d['kecamatan'] ?? $student->kecamatan,
                    'kabupaten' => $d['kabupaten'] ?? $student->kabupaten,
                    'provinsi' => $d['provinsi'] ?? $student->provinsi,
                ]);

                // Update or create family data
                $familyData = [
                    'ayah_nama' => $d['ayah_nama'] ?? null,
                    'ayah_tahun_lahir' => $d['ayah_tahun_lahir'] ?? null,
                    'ayah_pendidikan' => $d['ayah_pendidikan'] ?? null,
                    'ayah_pekerjaan' => $d['ayah_pekerjaan'] ?? null,
                    'ayah_penghasilan' => $d['ayah_penghasilan'] ?? null,
                    'ayah_status' => $d['ayah_status'] ?? null,
                    'ibu_nama' => $d['ibu_nama'] ?? null,
                    'ibu_tahun_lahir' => $d['ibu_tahun_lahir'] ?? null,
                    'ibu_pendidikan' => $d['ibu_pendidikan'] ?? null,
                    'ibu_pekerjaan' => $d['ibu_pekerjaan'] ?? null,
                    'ibu_penghasilan' => $d['ibu_penghasilan'] ?? null,
                    'ibu_status' => $d['ibu_status'] ?? null,
                    'wali_nama' => $d['wali_nama'] ?? null,
                    'wali_hubungan' => $d['wali_hubungan'] ?? null,
                    'wali_pekerjaan' => $d['wali_pekerjaan'] ?? null,
                    'wali_penghasilan' => $d['wali_penghasilan'] ?? null,
                ];

                StudentFamily::updateOrCreate(
                    ['student_id' => $student->id],
                    $familyData
                );

                // Update academic record
                if ($selectedYearId) {
                    $record = StudentAcademicRecord::where('student_id', $student->id)
                        ->where('academic_year_id', $selectedYearId)
                        ->first();

                    if ($record) {
                        $classroomId = $record->classroom_id;
                        if (! empty($d['kelas']) && ! empty($d['unit'])) {
                            $classroom = Classroom::firstOrCreate(
                                [
                                    'name' => $d['kelas'],
                                    'unit' => $d['unit'],
                                    'academic_year_id' => $selectedYearId
                                ],
                                [
                                    'name' => $d['kelas'],
                                    'unit' => $d['unit'],
                                    'academic_year_id' => $selectedYearId,
                                    'jenjang' => $d['jenjang'] ?? null,
                                    'grade' => $d['tingkat'] ?? null,
                                ]
                            );
                            $classroomId = $classroom->id;
                        }

                        $record->update([
                            'jenjang' => $d['jenjang'] ?? $record->jenjang,
                            'tingkat' => $d['tingkat'] ?? $record->tingkat,
                            'student_status' => $d['student_status'] ?? $record->student_status,
                            'classroom_id' => $classroomId,
                            'desil' => $d['desil'] ?? $record->desil,
                            'status_pip' => $d['status_pip'] ?? $record->status_pip,
                            'pip_keterangan' => $d['pip_keterangan'] ?? $record->pip_keterangan,
                            'status_kip' => $d['status_kip'] ?? $record->status_kip,
                            'no_kip' => $d['no_kip'] ?? $record->no_kip,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil diperbarui secara massal.');
    }

    /**
     * Stream the XLSX import template as a download.
     */
    public function downloadTemplate(): HttpResponse
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('Sistem Manajemen Sekolah')
            ->setTitle('Template Import Data Siswa');

        $this->buildSheet1($spreadsheet);
        $this->buildSheet2($spreadsheet);
        $this->buildSheet3($spreadsheet);
        $this->buildSheet4($spreadsheet);
        $this->buildSheet5($spreadsheet);
        $this->buildSheetPanduan($spreadsheet);
        $spreadsheet->setActiveSheetIndex(0);

        ob_start();
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        $content = ob_get_clean();

        $filename = 'template_import_siswa_'.date('Ymd').'.xlsx';

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    // ----------------------------------------------------------------
    // XLSX Template Sheet Builders
    // ----------------------------------------------------------------

    private function buildSheet1(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('1. Data Siswa');

        $headers = [
            'A' => ['NISN', 12],
            'B' => ['Nama Lengkap', 30],
            'C' => ['NIPD', 12],
            'D' => ['NIK', 18],
            'E' => ['No KK', 18],
            'F' => ['Unit', 35],
            'G' => ['Program', 12],
            'H' => ['JK (L/P)', 10],
            'I' => ['Tempat Lahir', 20],
            'J' => ['Tanggal Lahir (YYYY-MM-DD)', 24],
            'K' => ['Agama', 12],
            'L' => ['No WA', 16],
            'M' => ['Sekolah Asal', 30],
            'N' => ['Status Keluarga', 20],
            'O' => ['Anak ke', 10],
            'P' => ['Diterima di Jenjang/Kelas', 25],
            'Q' => ['Tanggal Diterima (YYYY-MM-DD)', 30],
        ];

        $this->applySheetHeaders($sheet, $headers, '004B23');

        $sheet->fromArray([[
            '0051234001', 'Ahmad Fauzi Rahman', '10231001',
            '3313151503140001', '3313150101080001',
            'SDIT Ulil Albab Gondangrejo', 'Umum',
            'L', 'Karanganyar', '2014-03-15', 'Islam',
            '081234567001', 'TK Aisyiyah Colomadu',
            'Anak Kandung', '1', '7', '2024-07-15',
        ]], null, 'A2');

        $this->styleExampleRow($sheet, 2, 'A', 'Q');
        $sheet->freezePane('A2');
    }

    private function buildSheet2(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('2. Alamat');

        $headers = [
            'A' => ['NISN', 12],
            'B' => ['Jalan', 30],
            'C' => ['RT/RW', 14],
            'D' => ['Dusun', 18],
            'E' => ['Desa/Kelurahan', 20],
            'F' => ['Kecamatan', 20],
            'G' => ['Kabupaten/Kota', 20],
            'H' => ['Provinsi', 20],
        ];

        $this->applySheetHeaders($sheet, $headers, '1565C0');

        $sheet->fromArray([[
            '0051234001', 'Jl. Lawu No. 12', 'RT 03 / RW 05',
            'Ngemplak', 'Colomadu', 'Colomadu', 'Karanganyar', 'Jawa Tengah',
        ]], null, 'A2');

        $this->styleExampleRow($sheet, 2, 'A', 'H');
        $sheet->freezePane('A2');
    }

    private function buildSheet3(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('3. Data Keluarga');

        $headers = [
            'A' => ['NISN', 12],
            'B' => ['Nama Ayah', 25],
            'C' => ['NIK Ayah', 18],
            'D' => ['Thn Lahir Ayah', 16],
            'E' => ['Pendidikan Ayah', 18],
            'F' => ['Pekerjaan Ayah', 20],
            'G' => ['Penghasilan Ayah', 28],
            'H' => ['Status Ayah (Hidup/Meninggal)', 30],
            'I' => ['Nama Ibu', 25],
            'J' => ['NIK Ibu', 18],
            'K' => ['Thn Lahir Ibu', 16],
            'L' => ['Pendidikan Ibu', 18],
            'M' => ['Pekerjaan Ibu', 20],
            'N' => ['Penghasilan Ibu', 28],
            'O' => ['Status Ibu (Hidup/Meninggal)', 30],
            'P' => ['Memiliki Bisnis/Usaha (Ya/Tidak)', 32],
            'Q' => ['Jenis Bisnis/Usaha', 30],
            'R' => ['Nama Wali', 25],
            'S' => ['NIK Wali', 18],
            'T' => ['Thn Lahir Wali', 16],
            'U' => ['Hubungan Wali', 18],
            'V' => ['Pendidikan Wali', 18],
            'W' => ['Pekerjaan Wali', 20],
            'X' => ['Penghasilan Wali', 28],
        ];

        $this->applySheetHeaders($sheet, $headers, '6A1E99');

        $sheet->fromArray([[
            '0051234001',
            'Fauzi Hidayat', '', '1980', 'SMA/SMK', 'Wiraswasta', 'Rp 3.000.000 - Rp 5.000.000', 'Hidup',
            'Siti Rahmawati', '', '1984', 'SMA/SMK', 'Ibu Rumah Tangga', 'Kurang dari Rp 1.000.000', 'Hidup',
            'Ya', 'Warung Makan',
            '', '', '', '', '', '', '',
        ]], null, 'A2');

        $this->styleExampleRow($sheet, 2, 'A', 'X');
        $sheet->freezePane('B2');
    }

    private function buildSheet4(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('4. Saudara Kandung');

        $headers = [
            'A' => ['NISN Siswa', 12],
            'B' => ['Nama Saudara', 30],
            'C' => ['Tanggal Lahir (YYYY-MM-DD)', 26],
        ];

        $this->applySheetHeaders($sheet, $headers, 'BF360C');

        $sheet->fromArray([
            ['0051234001', 'Aisyah Fauzia', '2016-05-12'],
            ['0051234001', 'Muhammad Farhan', '2019-08-23'],
        ], null, 'A2');

        $this->styleExampleRow($sheet, 2, 'A', 'C');
        $this->styleExampleRow($sheet, 3, 'A', 'C');
        $sheet->freezePane('A2');
    }

    private function buildSheet5(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('5. Data Akademik (per TA)');

        $headers = [
            'A' => ['NISN', 12],
            'B' => ['Tahun Ajaran', 14],
            'C' => ['Semester', 12],
            'D' => ['Jenjang (SD/SMP)', 16],
            'E' => ['Tingkat (1-9)', 14],
            'F' => ['Kelas/Rombel', 16],
            'G' => ['Status Siswa', 16],
            'H' => ['Desil (1-10)', 14],
            'I' => ['Status PIP (Ya/Tidak)', 20],
            'J' => ['Keterangan PIP', 28],
            'K' => ['Status KIP (Ya/Tidak)', 20],
            'L' => ['No KIP', 20],
        ];

        $this->applySheetHeaders($sheet, $headers, '004D40');

        $sheet->fromArray([[
            '0051234001', '2025/2026', 'Ganjil', 'SD', '5', '5A',
            'aktif', '3', 'Ya', 'Penerima PIP tahap 1 TA 2025/2026', 'Ya', '6071012345670001',
        ]], null, 'A2');

        $this->styleExampleRow($sheet, 2, 'A', 'L');
        $sheet->freezePane('A2');
    }

    private function buildSheetPanduan(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('0. Panduan');
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(72);

        $sheet->setCellValue('A1', 'PANDUAN PENGISIAN TEMPLATE IMPORT DATA SISWA');
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '004B23']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(40);

        $panduan = [
            ['', ''],
            ['', 'CARA PENGGUNAAN'],
            ['1.', 'Isi data mulai dari baris ke-2 pada setiap sheet. JANGAN mengubah baris header (baris 1).'],
            ['2.', 'Baris berwarna hijau muda adalah CONTOH. Hapus baris contoh sebelum mengisi data Anda.'],
            ['3.', 'Kolom NISN adalah kunci utama antar sheet. Pastikan NISN konsisten di semua sheet.'],
            ['4.', 'Urutan: Sheet 1 - Sheet 2 - Sheet 3 - Sheet 4 - Sheet 5.'],
            ['', ''],
            ['', 'DESKRIPSI SHEET'],
            ['1.', 'Sheet "1. Data Siswa" - Data identitas tetap siswa (tidak berubah meski naik kelas).'],
            ['2.', 'Sheet "2. Alamat" - Data alamat domisili siswa (satu baris per siswa).'],
            ['3.', 'Sheet "3. Data Keluarga" - Data orang tua / wali.'],
            ['4.', 'Sheet "4. Saudara Kandung" - Satu baris per saudara.'],
            ['5.', 'Sheet "5. Data Akademik" - Data DINAMIS per Tahun Ajaran (kelas, status, bantuan).'],
            ['', ''],
            ['', 'CATATAN PENTING'],
            ['*', 'Format tanggal: YYYY-MM-DD (contoh: 2014-03-15).'],
            ['*', 'NISN harus unik. Tidak boleh ada NISN ganda di Sheet 1.'],
            ['*', 'Data Akademik boleh lebih dari 1 baris per siswa (untuk Tahun Ajaran berbeda).'],
            ['', ''],
            ['', 'UNIT & PROGRAM'],
            ['*', 'SDIT Ulil Albab Gondangrejo  ->  Program: Umum'],
            ['*', 'SMPIT Ulil Albab Gondangrejo ->  Program: Fullday'],
            ['*', 'PPTQ Ulil Albab Gondangrejo  ->  Program: Boarding'],
        ];

        $row = 2;
        foreach ($panduan as [$no, $text]) {
            $sheet->setCellValue("A{$row}", $no);
            $sheet->setCellValue("B{$row}", $text);

            if ($no === '' && $text !== '') {
                $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']],
                ]);
            }

            $sheet->getRowDimension($row)->setRowHeight(18);
            $row++;
        }

        $sheet->getStyle('A2:B'.$row)->applyFromArray([
            'font' => ['size' => 10],
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
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
}
