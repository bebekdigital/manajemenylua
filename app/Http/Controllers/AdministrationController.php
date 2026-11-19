<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\DocumentTemplate;
use App\Models\Student;
use App\Services\StudentIdentityPdfService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AdministrationController extends Controller
{
    /**
     * Display the administration dashboard menu.
     */
    public function index(): Response
    {
        return Inertia::render('Administration/Index');
    }

    /**
     * Display the student photo gallery for printing.
     */
    public function studentPhotos(Request $request): Response
    {
        $selectedYearId = $request->query('academic_year_id') ?? session('selected_academic_year_id');
        $selectedYear = $selectedYearId
            ? AcademicYear::find($selectedYearId)
            : (AcademicYear::current() ?? AcademicYear::first());

        $students = Student::with([
            'academicRecords' => function ($query) use ($selectedYear) {
                if ($selectedYear) {
                    $query->where('academic_year_id', $selectedYear->id);
                }
                $query->with('classroom');
            },
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
                $record = $student->academicRecords->first();
                $classroom = $record?->classroom;

                return [
                    'nisn' => $student->nisn,
                    'nama' => $student->nama,
                    'unit' => $student->unit,
                    'kelas' => $classroom?->name,
                    'jenjang' => $record?->jenjang,
                    'tingkat' => $record?->tingkat,
                    'photo_url' => $student->photo_url,
                ];
            });

        $classrooms = $students->pluck('kelas')->filter()->unique()->sort()->values();
        $units = $students->pluck('unit')->filter()->unique()->sort()->values();

        return Inertia::render('Administration/StudentPhotos', [
            'students' => $students,
            'classrooms' => $classrooms,
            'units' => $units,
        ]);
    }

    /**
     * Display the administration page with student lists & template settings.
     */
    public function identityDocument(Request $request): Response
    {
        $selectedYearId = $request->query('academic_year_id') ?? session('selected_academic_year_id');
        $selectedYear = $selectedYearId
            ? AcademicYear::find($selectedYearId)
            : (AcademicYear::current() ?? AcademicYear::first());

        if ($selectedYear && session('selected_academic_year_id') !== $selectedYear->id) {
            session(['selected_academic_year_id' => $selectedYear->id]);
        }

        $academicYears = AcademicYear::orderByDesc('name')
            ->get(['id', 'name', 'semester', 'start_date']);

        $classrooms = Classroom::orderBy('grade')
            ->orderBy('name')
            ->get(['id', 'name', 'grade', 'unit', 'jenjang']);

        $template = DocumentTemplate::getStudentIdentityTemplate();

        $students = Student::with([
            'academicRecords' => function ($query) use ($selectedYear) {
                if ($selectedYear) {
                    $query->where('academic_year_id', $selectedYear->id);
                }
                $query->with('classroom');
            },
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
            ->map(fn (Student $student) => $this->mapStudentForIdentityDocument($student, $selectedYear));

        return Inertia::render('Administration/IdentityDocument', [
            'students' => $students,
            'academicYears' => $academicYears,
            'classrooms' => $classrooms,
            'selectedAcademicYear' => $selectedYear ? [
                'id' => $selectedYear->id,
                'name' => $selectedYear->name,
                'semester' => $selectedYear->semester,
                'start_date' => $selectedYear->start_date?->format('Y-m-d'),
            ] : null,
            'template' => $template,
        ]);
    }

    /**
     * Generate and directly download student identity PDF (single, merged, or per-student).
     */
    public function downloadIdentityPdf(Request $request, StudentIdentityPdfService $pdfService): HttpResponse
    {
        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['required', 'integer', 'exists:students,id'],
            'pages' => ['nullable', 'array', 'min:1'],
            'pages.*' => ['string', 'in:cover,school_profile,identity'],
            'show_photo' => ['nullable', 'boolean'],
            'filename' => ['nullable', 'string', 'max:180'],
        ]);

        $selectedYearId = session('selected_academic_year_id');
        $selectedYear = $selectedYearId
            ? AcademicYear::find($selectedYearId)
            : (AcademicYear::current() ?? AcademicYear::first());

        $students = Student::with([
            'academicRecords' => function ($query) use ($selectedYear) {
                if ($selectedYear) {
                    $query->where('academic_year_id', $selectedYear->id);
                }
                $query->with('classroom');
            },
            'siblings',
        ])
            ->whereIn('id', $validated['student_ids'])
            ->orderBy('unit')
            ->orderBy('nama')
            ->get()
            ->map(fn (Student $student) => $this->mapStudentForIdentityDocument($student, $selectedYear))
            ->values()
            ->all();

        $template = DocumentTemplate::getStudentIdentityTemplate();
        $pages = $validated['pages'] ?? ['cover', 'school_profile', 'identity'];
        $showPhoto = array_key_exists('show_photo', $validated)
            ? filter_var($validated['show_photo'], FILTER_VALIDATE_BOOLEAN)
            : true;

        $pdfBinary = $pdfService->generatePdf($students, $template, $pages, $showPhoto);

        $defaultName = count($students) === 1
            ? 'Identitas - '.$students[0]['nama'].' ('.$students[0]['nisn'].').pdf'
            : 'Identitas Siswa - Gabungan ('.count($students).' Siswa).pdf';

        $rawName = $validated['filename'] ?? $defaultName;
        $safeFilename = preg_replace('/[^A-Za-z0-9\-_(). ]+/', '_', $rawName) ?: 'identitas_siswa.pdf';
        if (! str_ends_with(strtolower($safeFilename), '.pdf')) {
            $safeFilename .= '.pdf';
        }

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$safeFilename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Format student model data for student identity document preview, print, and PDF.
     *
     * @return array<string, mixed>
     */
    private function mapStudentForIdentityDocument(Student $student, ?AcademicYear $selectedYear): array
    {
        $record = $student->academicRecords->first();
        $classroom = $record?->classroom;

        // Alamat lengkap
        $alamatParts = array_filter([
            $student->jalan,
            ($student->rt || $student->rw) ? ('RT '.($student->rt ?: '-').' / RW '.($student->rw ?: '-')) : null,
            $student->dusun,
            $student->desa,
            $student->kecamatan,
            $student->kabupaten,
            $student->provinsi,
        ]);
        $alamatLengkap = ! empty($alamatParts) ? implode(', ', $alamatParts) : '-';

        // Jenis kelamin formal
        $jkLabel = $student->jk === 'L' ? 'Laki-laki' : ($student->jk === 'P' ? 'Perempuan' : '-');

        // Tanggal diterima default
        $tanggalDiterima = $selectedYear?->start_date
            ? Carbon::parse($selectedYear->start_date)->translatedFormat('d F Y')
            : ($student->created_at ? $student->created_at->translatedFormat('d F Y') : '-');

        // Kelas diterima
        $kelasDiterima = $classroom?->name ?? ($record?->tingkat ? 'Kelas '.$record->tingkat : 'VII (Tujuh)');

        return [
            'id' => $student->id,
            'nisn' => $student->nisn ?? '-',
            'nis' => $student->nipd ?? $student->nisn ?? '-',
            'nipd' => $student->nipd ?? '-',
            'nama' => $student->nama,
            'nama_kapital' => mb_strtoupper($student->nama, 'UTF-8'),
            'jk' => $jkLabel,
            'jk_raw' => $student->jk,
            'tempat_lahir' => $student->tempat_lahir ?? '-',
            'tanggal_lahir' => $student->tanggal_lahir ? $student->tanggal_lahir->translatedFormat('d F Y') : '-',
            'ttl' => $student->ttl,
            'nik' => $student->nik ?? '-',
            'no_kk' => $student->no_kk ?? '-',
            'agama' => $student->agama ?? 'Islam',
            'unit' => $student->unit ?? 'SMPIT Ulil Albab',
            'unit_pesantren' => $student->unit_pesantren ?? $record?->unit_pesantren ?? '-',
            'program' => $student->program ?? 'Fullday',
            'jenjang' => $record?->jenjang ?? 'SMP',
            'tingkat' => $record?->tingkat,
            'angkatan' => $student->angkatan,
            'status_keaktifan' => $student->status_keaktifan ?? 'Aktif',
            'photo_url' => $student->photo_url,
            'foto_path' => $student->foto,
            'classroom_id' => $classroom?->id,
            'kelas' => $classroom?->name ?? '-',
            'kelas_diterima' => $student->diterima_di_jenjang ?? $kelasDiterima,
            'tanggal_diterima' => $student->tanggal_diterima ? $student->tanggal_diterima->translatedFormat('d F Y') : $tanggalDiterima,
            'status_keluarga' => $student->status_keluarga ?? 'Anak Kandung',
            'anak_ke' => $student->anak_ke ? (string) $student->anak_ke : (string) ($student->siblings->count() + 1),
            'no_wa' => $student->no_wa ?? '-',
            'sekolah_asal' => $student->sekolah_asal ?? '-',
            'alamat' => $alamatLengkap,
            'ayah_nama' => $student->ayah_nama ?? '-',
            'ayah_pekerjaan' => $student->ayah_pekerjaan ?? '-',
            'ayah_penghasilan' => $student->ayah_penghasilan ?? '-',
            'ibu_nama' => $student->ibu_nama ?? '-',
            'ibu_pekerjaan' => $student->ibu_pekerjaan ?? '-',
            'ibu_penghasilan' => $student->ibu_penghasilan ?? '-',
            'wali_nama' => $student->wali_nama ?? '-',
            'wali_alamat' => $student->wali_nama ? $alamatLengkap : '-',
            'wali_hp' => $student->wali_nama ? ($student->no_wa ?? '-') : '-',
            'wali_pekerjaan' => $student->wali_pekerjaan ?? '-',
        ];
    }

    /**
     * Display the template editor page.
     */
    public function editTemplate(): Response
    {
        $template = DocumentTemplate::getStudentIdentityTemplate();

        return Inertia::render('Administration/TemplateEditor', [
            'template' => $template,
            'students' => [], // Fallback will be used in the component
        ]);
    }

    /**
     * Update the document template configuration.
     */
    public function updateTemplate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_profile' => ['required', 'array'],
            'school_profile.nama_sekolah' => ['required', 'string'],
            'school_profile.jenjang_tingkat' => ['nullable', 'string'],
            'school_profile.jenjang_singkat' => ['nullable', 'string'],
            'school_profile.npsn' => ['nullable', 'string'],
            'school_profile.nis_nss_nds' => ['nullable', 'string'],
            'school_profile.alamat_sekolah' => ['nullable', 'string'],
            'school_profile.kelurahan_desa' => ['nullable', 'string'],
            'school_profile.kecamatan' => ['nullable', 'string'],
            'school_profile.kota_kabupaten' => ['nullable', 'string'],
            'school_profile.provinsi' => ['nullable', 'string'],
            'school_profile.website' => ['nullable', 'string'],
            'school_profile.email' => ['nullable', 'string'],
            'school_profile.kementerian_title' => ['nullable', 'string'],

            'signatory' => ['required', 'array'],
            'signatory.tempat_penandatangan' => ['nullable', 'string'],
            'signatory.jabatan' => ['nullable', 'string'],
            'signatory.nama_kepala_sekolah' => ['nullable', 'string'],
            'signatory.nip' => ['nullable', 'string'],

            'identity_visibility' => ['nullable', 'array'],
            'identity_visibility.nama_lengkap' => ['nullable', 'boolean'],
            'identity_visibility.nomor_induk' => ['nullable', 'boolean'],
            'identity_visibility.tempat_tanggal_lahir' => ['nullable', 'boolean'],
            'identity_visibility.jenis_kelamin' => ['nullable', 'boolean'],
            'identity_visibility.agama' => ['nullable', 'boolean'],
            'identity_visibility.status_keluarga' => ['nullable', 'boolean'],
            'identity_visibility.anak_ke' => ['nullable', 'boolean'],
            'identity_visibility.alamat_peserta' => ['nullable', 'boolean'],
            'identity_visibility.nomor_telepon' => ['nullable', 'boolean'],
            'identity_visibility.sekolah_asal' => ['nullable', 'boolean'],
            'identity_visibility.diterima_di_sekolah' => ['nullable', 'boolean'],
            'identity_visibility.nama_orang_tua' => ['nullable', 'boolean'],
            'identity_visibility.alamat_orang_tua' => ['nullable', 'boolean'],
            'identity_visibility.telepon_orang_tua' => ['nullable', 'boolean'],
            'identity_visibility.pekerjaan_orang_tua' => ['nullable', 'boolean'],
            'identity_visibility.nama_wali' => ['nullable', 'boolean'],
            'identity_visibility.alamat_wali' => ['nullable', 'boolean'],
            'identity_visibility.telepon_wali' => ['nullable', 'boolean'],
            'identity_visibility.pekerjaan_wali' => ['nullable', 'boolean'],

            'options' => ['nullable', 'array'],
            'tut_wuri_logo_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,svg', 'max:2048'],
            'school_logo_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,svg', 'max:2048'],
        ]);

        $template = DocumentTemplate::getStudentIdentityTemplate();
        $options = $template->options ?? [];

        // Update options from request
        if (isset($validated['options'])) {
            $options = array_merge($options, $validated['options']);
            // When options are sent as form-data, booleans might be strings like "true" or "false"
            foreach (['show_tut_wuri_logo', 'show_school_logo', 'show_photo_box'] as $opt) {
                if (isset($options[$opt])) {
                    $options[$opt] = filter_var($options[$opt], FILTER_VALIDATE_BOOLEAN);
                }
            }
        }

        // Handle File Uploads
        if ($request->hasFile('tut_wuri_logo_file')) {
            if (! empty($options['tut_wuri_logo_path']) && Storage::disk('public')->exists($options['tut_wuri_logo_path'])) {
                Storage::disk('public')->delete($options['tut_wuri_logo_path']);
            }
            $path = $request->file('tut_wuri_logo_file')->store('logos', 'public');
            $options['tut_wuri_logo_path'] = $path;
        }

        if ($request->hasFile('school_logo_file')) {
            if (! empty($options['school_logo_path']) && Storage::disk('public')->exists($options['school_logo_path'])) {
                Storage::disk('public')->delete($options['school_logo_path']);
            }
            $path = $request->file('school_logo_file')->store('logos', 'public');
            $options['school_logo_path'] = $path;
        }

        if (isset($validated['identity_visibility'])) {
            $options['identity_visibility'] = array_merge($options['identity_visibility'] ?? [], $validated['identity_visibility']);
            // Convert strings like "true" to booleans if sent via form-data
            foreach ($options['identity_visibility'] as $key => $val) {
                $options['identity_visibility'][$key] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
            }
        }

        $validated['options'] = $options;
        unset($validated['tut_wuri_logo_file'], $validated['school_logo_file'], $validated['identity_visibility']);

        $template->update($validated);

        return back()->with('success', 'Template Berkas Identitas Siswa berhasil diperbarui.');
    }

    /**
     * Reset the document template to default values.
     */
    public function resetTemplate(): RedirectResponse
    {
        $template = DocumentTemplate::getStudentIdentityTemplate();
        $defaultConfig = DocumentTemplate::getDefaultStudentIdentityConfig();

        $template->update([
            'school_profile' => $defaultConfig['school_profile'],
            'signatory' => $defaultConfig['signatory'],
            'template_content' => $defaultConfig['template_content'],
            'options' => $defaultConfig['options'],
        ]);

        return back()->with('success', 'Template berhasil direset ke format bawaan.');
    }
}
