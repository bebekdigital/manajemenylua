<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\DocumentTemplate;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $academicYears = AcademicYear::orderByDesc('is_active')
            ->orderByDesc('name')
            ->get(['id', 'name', 'semester', 'is_active', 'start_date']);

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
            ->map(function (Student $student) use ($selectedYear) {
                $family = $student->family;
                $record = $student->academicRecords->first();
                $classroom = $record?->classroom;

                // Alamat lengkap
                $alamatParts = array_filter([
                    $student->jalan,
                    $student->rt_rw ? "RT/RW {$student->rt_rw}" : null,
                    $student->dusun ? "Dsn. {$student->dusun}" : null,
                    $student->desa ? "Ds. {$student->desa}" : null,
                    $student->kecamatan ? "Kec. {$student->kecamatan}" : null,
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
                    'program' => $student->program ?? 'Fullday',
                    'jenjang' => $record?->jenjang ?? 'SMP',
                    'tingkat' => $record?->tingkat,
                    'classroom_id' => $classroom?->id,
                    'kelas' => $classroom?->name ?? '-',
                    'kelas_diterima' => $kelasDiterima,
                    'tanggal_diterima' => $tanggalDiterima,
                    'status_keluarga' => 'Anak Kandung',
                    'anak_ke' => (string) ($student->siblings->count() + 1),
                    'no_wa' => $student->no_wa ?? '-',
                    'sekolah_asal' => $student->sekolah_asal ?? '-',
                    'alamat' => $alamatLengkap,
                    'ayah_nama' => $family?->ayah_nama ?? '-',
                    'ayah_pekerjaan' => $family?->ayah_pekerjaan ?? '-',
                    'ayah_penghasilan' => $family?->ayah_penghasilan ?? '-',
                    'ibu_nama' => $family?->ibu_nama ?? '-',
                    'ibu_pekerjaan' => $family?->ibu_pekerjaan ?? '-',
                    'ibu_penghasilan' => $family?->ibu_penghasilan ?? '-',
                    'wali_nama' => $family?->wali_nama ?? '-',
                    'wali_alamat' => $family?->wali_nama ? $alamatLengkap : '-',
                    'wali_hp' => $family?->wali_nama ? ($student->no_wa ?? '-') : '-',
                    'wali_pekerjaan' => $family?->wali_pekerjaan ?? '-',
                ];
            });

        return Inertia::render('Administration/IdentityDocument', [
            'students' => $students,
            'academicYears' => $academicYears,
            'classrooms' => $classrooms,
            'selectedAcademicYear' => $selectedYear ? [
                'id' => $selectedYear->id,
                'name' => $selectedYear->name,
                'semester' => $selectedYear->semester,
                'is_active' => (bool) $selectedYear->is_active,
                'start_date' => $selectedYear->start_date?->format('Y-m-d'),
            ] : null,
            'template' => $template,
        ]);
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
            'signatory.tempat_titimangsa' => ['nullable', 'string'],
            'signatory.tanggal_titimangsa' => ['nullable', 'string'],
            'signatory.jabatan' => ['nullable', 'string'],
            'signatory.nama_kepala_sekolah' => ['nullable', 'string'],
            'signatory.nip' => ['nullable', 'string'],

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

        $validated['options'] = $options;
        unset($validated['tut_wuri_logo_file'], $validated['school_logo_file']);

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
