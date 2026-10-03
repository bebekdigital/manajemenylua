<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        $students = Student::with([
            'family',
            'siblings',
        ])
            ->orderBy('unit')
            ->orderBy('nama')
            ->get()
            ->map(function (Student $student) {
                $family = $student->family;

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
                    'angkatan' => $student->angkatan,
                    'photo_url' => $student->photo_url,
                    'jenjang' => $student->jenjang,
                    'student_status' => 'aktif', // Will be added to students table later if needed
                    'no_wa' => $student->no_wa,
                    'sekolah_asal' => $student->sekolah_asal,
                    'status_keluarga' => $student->status_keluarga,
                    'anak_ke' => $student->anak_ke,
                    'diterima_di_jenjang' => $student->diterima_di_jenjang,
                    'tanggal_diterima' => $student->tanggal_diterima ? $student->tanggal_diterima->format('Y-m-d') : null,
                    'formatted_tanggal_diterima' => $student->tanggal_diterima ? $student->tanggal_diterima->translatedFormat('d F Y') : null,
                    'info_psb' => $student->info_psb,
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
                        'desil' => $student->desil,
                        'pip' => $student->status_pip ?? false,
                        'pip_keterangan' => $student->pip_keterangan,
                        'kip' => $student->status_kip ?? false,
                        'no_kip' => $student->no_kip,
                    ],
                ];
            });

        return Inertia::render('Students', [
            'students' => $students,
        ]);
    }

    /**
     * Display the student detail page.
     */
    public function show(string $nisn, Request $request): Response
    {
        $studentModel = Student::with([
            'academicRecords.classroom',
            'academicRecords.academicYear',
            'siblings',
        ])->where('nisn', $nisn)->firstOrFail();

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
            'photo_url' => $studentModel->photo_url,
            'jenjang' => $studentModel->jenjang,
            'tingkat' => $studentModel->tingkat,
            'kelas' => $studentModel->kelas,
            'student_status' => 'aktif',
            'ket_tidak_aktif' => null,
            'tanggal_tidak_aktif' => null,
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
                'status' => $studentModel->ayah_status,
            ],
            'ibu' => [
                'nama' => $studentModel->ibu_nama,
                'tahun_lahir' => $studentModel->ibu_tahun_lahir,
                'pekerjaan' => $studentModel->ibu_pekerjaan,
                'penghasilan' => $studentModel->ibu_penghasilan,
                'nik' => $studentModel->ibu_nik,
                'status' => $studentModel->ibu_status,
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
            'riwayat_keaktifan' => $studentModel->academicRecords->map(fn ($r) => [
                'id' => $r->id,
                'academic_year' => $r->academicYear?->name,
                'semester' => $r->academicYear?->semester,
                'unit' => $r->classroom?->unit ?? $studentModel->unit,
                'kelas' => $r->classroom?->name,
                'status' => $r->student_status,
                'keterangan' => $r->ket_tidak_aktif,
            ])->sortBy(fn ($r) => $r['academic_year'].'-'.$r['semester'])->values()->toArray(),
            'angkatan' => $studentModel->angkatan,
            'diterima_di_jenjang' => $studentModel->diterima_di_jenjang,
            'tanggal_diterima' => $studentModel->tanggal_diterima?->format('Y-m-d'),
            'formatted_tanggal_diterima' => $studentModel->tanggal_diterima ? Carbon::parse($studentModel->tanggal_diterima)->translatedFormat('d F Y') : null,
            'info_psb' => $studentModel->info_psb,
            'status_registrasi' => $studentModel->status_registrasi,
            'bantuan' => [
                'desil' => $studentModel->desil,
                'pip' => $studentModel->status_pip ?? false,
                'pip_keterangan' => $studentModel->pip_keterangan,
                'kip' => $studentModel->status_kip ?? false,
                'no_kip' => $studentModel->no_kip,
                'nama_di_kip' => $studentModel->nama_di_kip,
            ],
        ];

        return Inertia::render('StudentDetail', [
            'student' => $studentData,
        ]);
    }

    /**
     * Display the inline student editing page.
     */
    public function inlineEdit(Request $request): Response
    {
        $students = Student::with([
            'family',
            'siblings',
        ])
            ->orderBy('unit')
            ->orderBy('nama')
            ->get()
            ->map(function (Student $student) {
                $family = $student->family;

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
                    'jenjang' => $student->jenjang,
                    'tingkat' => $student->tingkat,
                    'kelas' => $student->kelas,
                    'student_status' => 'aktif',
                    // Registrasi
                    'status_keluarga' => $student->status_keluarga,
                    'anak_ke' => $student->anak_ke,
                    'diterima_di_jenjang' => $student->diterima_di_jenjang,
                    'tanggal_diterima' => $student->tanggal_diterima ? $student->tanggal_diterima->format('Y-m-d') : null,
                    'info_psb' => $student->info_psb,
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
                    'desil' => $student->desil,
                    'status_pip' => $student->status_pip ?? false,
                    'pip_keterangan' => $student->pip_keterangan,
                    'status_kip' => $student->status_kip ?? false,
                    'no_kip' => $student->no_kip,
                ];
            });

        return Inertia::render('Students/InlineEdit', [
            'students' => $students,
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
            'students.*.info_psb' => ['nullable', 'string'],
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

                // Update student base data and family data
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
                    'info_psb' => $d['info_psb'] ?? $student->info_psb,
                    'jalan' => $d['jalan'] ?? $student->jalan,
                    'rt_rw' => $d['rt_rw'] ?? $student->rt_rw,
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
                ]);

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
                                    'academic_year_id' => $selectedYearId,
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
     * Handle batch photo upload, matching filenames containing NISN to students.
     */
    public function uploadPhotos(Request $request): RedirectResponse
    {
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

            $storagePath = 'student-photos/'.$foundNisn.'.'.$ext;
            $existingStudent = Student::where('nisn', $foundNisn)->first();

            if ($existingStudent?->foto && $existingStudent->foto !== $storagePath) {
                Storage::disk('local')->delete($existingStudent->foto);
            }

            Storage::disk('local')->put($storagePath, file_get_contents($fileInfo['path']));
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
            $ext = $photo->getClientOriginalExtension();

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

            $storagePath = 'student-photos/'.$foundNisn.'.'.$ext;
            $existingStudent = Student::where('nisn', $foundNisn)->first();

            if ($existingStudent?->foto) {
                Storage::disk('local')->delete($existingStudent->foto);
            }

            Storage::disk('local')->put($storagePath, file_get_contents($photo));
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

        return response()->file(Storage::disk('local')->path($path));
    }

    /**
     * Stream the XLSX import template as a download.
     */
    public function downloadTemplate(Request $request): HttpResponse
    {
        $tab = $request->query('tab', 'all');

        $units = collect();
        $programs = collect();
        $classes = collect();
        $jenjang = null;

        if ($tab !== 'all') {
            $mappedJenjang = null;
            if ($tab === 'SDIT') {
                $mappedJenjang = 'SD';
            } elseif ($tab === 'SMPIT') {
                $mappedJenjang = 'SMP';
            } else {
                $mappedJenjang = str_replace('IT', '', strtoupper($tab));
            }

            $level = SchoolLevel::where('name', $mappedJenjang)->orWhere('name', 'like', "%{$mappedJenjang}%")->first();

            if ($level) {
                $jenjang = $level->name;
                $units = SchoolUnit::where('school_level_id', $level->id)->pluck('name');
                $programs = SchoolProgram::whereHas('unit', fn ($q) => $q->where('school_level_id', $level->id))->pluck('name');
                $classes = SchoolClass::whereHas('unit', fn ($q) => $q->where('school_level_id', $level->id))->pluck('name');
            } else {
                $units = SchoolUnit::pluck('name');
                $programs = SchoolProgram::pluck('name');
                $classes = SchoolClass::pluck('name');
            }
        } else {
            $units = SchoolUnit::pluck('name');
            $programs = SchoolProgram::pluck('name');
            $classes = SchoolClass::pluck('name');
        }

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('Sistem Manajemen Sekolah')
            ->setTitle('Template Import Data Siswa');

        $this->buildDropdownsSheet($spreadsheet, $units, $programs, $classes);
        $this->buildSheet1($spreadsheet, $jenjang);
        $this->buildSheet2($spreadsheet);

        // Remove default sheet 0 if it was created by new Spreadsheet but we shifted things.
        // Actually createSheet(0) shifts existing sheets, so index 1 is now Sheet 1.
        $spreadsheet->setActiveSheetIndex(1);

        ob_start();
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        $content = ob_get_clean();

        $tabSuffix = ($tab !== 'all') ? strtoupper($tab) : 'Semua';
        $filename = 'template_import_siswa_'.$tabSuffix.'_'.date('Ymd').'.xlsx';

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function buildDropdownsSheet(Spreadsheet $spreadsheet, $units, $programs, $classes): void
    {
        // Insert as the very first sheet
        $sheet = $spreadsheet->createSheet(0);
        $sheet->setTitle('Dropdowns');

        $sheet->setCellValue('A1', 'Units');
        foreach ($units as $index => $val) {
            $sheet->setCellValue('A'.($index + 2), $val);
        }

        $sheet->setCellValue('B1', 'Programs');
        foreach ($programs as $index => $val) {
            $sheet->setCellValue('B'.($index + 2), $val);
        }

        $sheet->setCellValue('C1', 'Classes');
        foreach ($classes as $index => $val) {
            $sheet->setCellValue('C'.($index + 2), $val);
        }

        // Hide the sheet
        $sheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
    }

    // ----------------------------------------------------------------
    // XLSX Template Sheet Builders
    // ----------------------------------------------------------------

    private function buildSheet1(Spreadsheet $spreadsheet, ?string $jenjang = null): void
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
        ];

        $this->applySheetHeaders($sheet, $headers, '004B23');

        // Format kolom Tanggal Lahir & Tanggal Diterima
        $sheet->getStyle('I2:I1000')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
        $sheet->getStyle('BH2:BH1000')->getNumberFormat()->setFormatCode('yyyy-mm-dd');

        // Format kolom ID/Nomor sebagai Teks (mencegah angka dengan awalan 0 atau simbol / berubah)
        $textCols = ['A', 'C', 'D', 'J', 'V', 'W', 'AD', 'AK', 'AR', 'AU', 'BB'];
        foreach ($textCols as $col) {
            $sheet->getStyle($col.'2:'.$col.'1000')->getNumberFormat()->setFormatCode('@');
        }

        $unitExample = ($jenjang === 'SMP') ? 'SMPIT Ulil Albab' : 'SDIT Ulil Albab';

        $sheet->fromArray([[
            '0051234001', 'Ahmad Fauzi Rahman', '10231001', // A, B, C
            '2025/2026', $jenjang ?? 'SD', $unitExample, // D, E, F
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
        ]], null, 'A2');

        for ($row = 2; $row <= 1000; $row++) {
            // Dropdown/fixed for Jenjang
            if ($jenjang) {
                if ($row > 2) {
                    $sheet->setCellValue('E'.$row, $jenjang);
                }
                $valJenjang = $sheet->getCell('E'.$row)->getDataValidation();
                $valJenjang->setType(DataValidation::TYPE_LIST);
                $valJenjang->setShowDropDown(true);
                $valJenjang->setShowErrorMessage(true);
                $valJenjang->setErrorStyle(DataValidation::STYLE_STOP);
                $valJenjang->setErrorTitle('Invalid Input');
                $valJenjang->setError('Pilihan Jenjang dikunci (fixed) berdasarkan template.');
                $valJenjang->setFormula1('"'.$jenjang.'"');
            }

            $valUnit = $sheet->getCell('F'.$row)->getDataValidation();
            $valUnit->setType(DataValidation::TYPE_LIST);
            $valUnit->setAllowBlank(true);
            $valUnit->setShowDropDown(true);
            $valUnit->setFormula1('Dropdowns!$A$2:$A$200');

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
        }

        $this->styleExampleRow($sheet, 2, 'A', 'BE');
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
        ], null, 'A2');

        $this->styleExampleRow($sheet, 2, 'A', 'C');
        $this->styleExampleRow($sheet, 3, 'A', 'C');
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
}
