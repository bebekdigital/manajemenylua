<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use Illuminate\Support\Facades\Storage;

/**
 * Builds A4 student identity PDF documents (Cover, School Profile, Student Identity)
 * matching StudentDocumentSheet.vue layout using zero-dependency SimplePdfWriter.
 */
class StudentIdentityPdfService
{
    /**
     * Generate binary PDF string for one or more student data arrays.
     *
     * @param  array<int, array<string, mixed>>  $students
     * @param  array<int, string>  $pages
     */
    public function generatePdf(
        array $students,
        DocumentTemplate $template,
        array $pages = ['cover', 'school_profile', 'identity'],
        bool $showPhoto = true
    ): string {
        $pdf = new SimplePdfWriter;

        foreach ($students as $student) {
            if (in_array('cover', $pages, true)) {
                $this->renderCoverPage($pdf, $student, $template);
            }
            if (in_array('school_profile', $pages, true)) {
                $this->renderSchoolProfilePage($pdf, $template);
            }
            if (in_array('identity', $pages, true)) {
                $this->renderIdentityPage($pdf, $student, $template, $showPhoto);
            }
        }

        return $pdf->output();
    }

    /**
     * Render Page 1: Cover Depan Rapor.
     *
     * @param  array<string, mixed>  $student
     */
    private function renderCoverPage(SimplePdfWriter $pdf, array $student, DocumentTemplate $template): void
    {
        $pdf->addPage();

        $school = $template->school_profile ?? [];
        $options = $template->options ?? [];

        $marginX = 20.0;
        $contentW = 170.0;

        // 1. Logo Tut Wuri Handayani
        $showTutWuri = (bool) ($options['show_tut_wuri_logo'] ?? true);
        if ($showTutWuri) {
            $tutPath = ! empty($options['tut_wuri_logo_path'])
                ? Storage::disk('public')->path($options['tut_wuri_logo_path'])
                : null;

            $logoW = 50.0;
            $logoX = $marginX + ($contentW - $logoW) / 2.0;
            if ($tutPath && is_file($tutPath)) {
                $pdf->addImage($tutPath, $logoX, 24.0, $logoW, $logoW, false, true);
            } else {
                $pdf->setDrawColor(200, 200, 200);
                $pdf->setFillColor(245, 245, 245);
                $pdf->rect($logoX, 24.0, $logoW, $logoW, 'DF');
                $pdf->setTextColor(120, 120, 120);
                $pdf->textAligned($logoX, 50.0, $logoW, 'LOGO KEMENTERIAN', 'F2', 8.5, 'C');
            }
        }

        // 2. Jenjang Sekolah
        $pdf->setTextColor(0, 0, 0);
        $jenjangTingkat = mb_strtoupper((string) ($school['jenjang_tingkat'] ?? 'SEKOLAH MENENGAH PERTAMA'), 'UTF-8');
        $jenjangSingkat = (string) ($school['jenjang_singkat'] ?? '( SMP )');
        $pdf->textAligned($marginX, 90.0, $contentW, $jenjangTingkat, 'F2', 18.0, 'C');
        $pdf->textAligned($marginX, 99.0, $contentW, $jenjangSingkat, 'F2', 18.0, 'C');

        // 3. Logo Sekolah
        $showSchoolLogo = (bool) ($options['show_school_logo'] ?? true);
        if ($showSchoolLogo) {
            $schoolPath = ! empty($options['school_logo_path'])
                ? Storage::disk('public')->path($options['school_logo_path'])
                : null;

            $sLogoW = 45.0;
            $sLogoX = $marginX + ($contentW - $sLogoW) / 2.0;
            if ($schoolPath && is_file($schoolPath)) {
                $pdf->addImage($schoolPath, $sLogoX, 114.0, $sLogoW, $sLogoW, false, true);
            } else {
                $pdf->setDrawColor(6, 95, 70);
                $pdf->setFillColor(6, 95, 70);
                $pdf->rect($sLogoX + 4.0, 118.0, 37.0, 37.0, 'B');
                $pdf->setTextColor(255, 255, 255);
                $pdf->textAligned($sLogoX + 4.0, 136.0, 37.0, 'ULIL ALBAB', 'F2', 9.0, 'C');
            }
        }

        // 4. Nama Peserta Didik & NISN / NIS
        $pdf->setTextColor(55, 65, 81);
        $pdf->textAligned($marginX, 180.0, $contentW, 'Nama Peserta Didik', 'F1', 12.0, 'C');

        $pdf->setTextColor(0, 0, 0);
        $studentName = mb_strtoupper((string) ($student['nama_kapital'] ?? $student['nama'] ?? '-'), 'UTF-8');
        $pdf->multiLineText($marginX + 6.0, 189.0, $contentW - 12.0, 7.5, $studentName, 'F2', 17.5, 'C');

        $pdf->setTextColor(55, 65, 81);
        $pdf->textAligned($marginX, 212.0, $contentW, 'NISN / NIS', 'F1', 12.0, 'C');

        $pdf->setTextColor(0, 0, 0);
        $nisn = (string) ($student['nisn'] ?? '-');
        $nis = (string) ($student['nis'] ?? $student['nipd'] ?? '-');
        $pdf->textAligned($marginX, 221.0, $contentW, "{$nisn} / {$nis}", 'F2', 15.0, 'C');

        // 5. Kementerian Title (Bottom)
        $kementerian = mb_strtoupper(
            (string) ($school['kementerian_title'] ?? "KEMENTERIAN PENDIDIKAN DASAR DAN MENENGAH\nREPUBLIK INDONESIA"),
            'UTF-8'
        );
        $kementerianLines = preg_split('/\r\n|\r|\n/', $kementerian) ?: [$kementerian];
        $bottomY = 262.0 - ((count($kementerianLines) - 1) * 7.5);
        foreach ($kementerianLines as $line) {
            $pdf->textAligned($marginX, $bottomY, $contentW, trim($line), 'F2', 14.5, 'C');
            $bottomY += 7.5;
        }
    }

    /**
     * Render Page 2: Profil Sekolah.
     */
    private function renderSchoolProfilePage(SimplePdfWriter $pdf, DocumentTemplate $template): void
    {
        $pdf->addPage();

        $school = $template->school_profile ?? [];
        $content = $template->template_content ?? [];

        $marginX = 20.0;
        $contentW = 170.0;

        $pdf->setTextColor(0, 0, 0);
        $title = mb_strtoupper((string) ($content['profile_title'] ?? 'PROFIL SEKOLAH'), 'UTF-8');
        $pdf->textAligned($marginX, 32.0, $contentW, $title, 'F2', 15.0, 'C');

        $rows = [
            ['Nama Sekolah', mb_strtoupper((string) ($school['nama_sekolah'] ?? '-'), 'UTF-8'), 'F2', false],
            ['NPSN', (string) ($school['npsn'] ?? '-'), 'F1', false],
            ['NIS/NSS/NDS', (string) ($school['nis_nss_nds'] ?? '-'), 'F1', false],
            ['Alamat Sekolah', (string) ($school['alamat_sekolah'] ?? '-'), 'F1', false],
            ['Kelurahan / Desa', (string) ($school['kelurahan_desa'] ?? '-'), 'F1', false],
            ['Kecamatan', (string) ($school['kecamatan'] ?? '-'), 'F1', false],
            ['Kota/Kabupaten', (string) ($school['kota_kabupaten'] ?? '-'), 'F1', false],
            ['Provinsi', (string) ($school['provinsi'] ?? '-'), 'F1', false],
            ['Website', (string) ($school['website'] ?? '-'), 'F1', true],
            ['E-mail', (string) ($school['email'] ?? '-'), 'F1', false],
        ];

        $xStart = 28.0;
        $labelW = 54.0;
        $colonW = 6.0;
        $valX = $xStart + $labelW + $colonW;
        $valW = 185.0 - $valX;
        $y = 50.0;

        foreach ($rows as [$label, $val, $fontKey, $isLink]) {
            $pdf->setTextColor(0, 0, 0);
            $pdf->textAligned($xStart, $y, $labelW, $label, 'F2', 11.5, 'L');
            $pdf->textAligned($xStart + $labelW, $y, $colonW, ':', 'F1', 11.5, 'C');

            if ($isLink) {
                $pdf->setTextColor(37, 99, 235);
                $pdf->textAligned($valX, $y, $valW, $val, $fontKey, 11.5, 'L', true);
                $y += 10.5;
            } else {
                $pdf->setTextColor(0, 0, 0);
                $h = $pdf->multiLineText($valX, $y, $valW, 5.5, $val, $fontKey, 11.5, 'L');
                $y += max(10.5, $h + 4.5);
            }
        }
    }

    /**
     * Render Page 3: Identitas Peserta Didik + Pas Foto 3x4 + TTD Kepala Sekolah.
     *
     * @param  array<string, mixed>  $student
     */
    private function renderIdentityPage(
        SimplePdfWriter $pdf,
        array $student,
        DocumentTemplate $template,
        bool $showPhoto
    ): void {
        $pdf->addPage();

        $signatory = $template->signatory ?? [];
        $content = $template->template_content ?? [];
        $vis = $template->options['identity_visibility'] ?? [];

        $isVisible = static fn (string $key): bool => (bool) ($vis[$key] ?? true);

        $marginX = 20.0;
        $contentW = 170.0;

        $pdf->setTextColor(0, 0, 0);
        $title = mb_strtoupper((string) ($content['identity_title'] ?? 'IDENTITAS PESERTA DIDIK'), 'UTF-8');
        $pdf->textAligned($marginX, 27.0, $contentW, $title, 'F2', 14.5, 'C');

        $numX = $marginX;
        $numW = 8.5;
        $labelX = $numX + $numW;
        $labelW = 54.0;
        $colonX = $labelX + $labelW;
        $colonW = 4.5;
        $valX = $colonX + $colonW;
        $valW = ($marginX + $contentW) - $valX;

        $y = 39.0;
        $rowNumber = 1;
        $lineH = 4.4;
        $rowGap = 1.6;
        $fontSize = 10.5;

        $drawRow = function (?string $numLabel, string $label, string $value, string $valFont = 'F1', bool $indentLabel = false) use (
            $pdf, &$y, $numX, $numW, $labelX, $labelW, $colonX, $colonW, $valX, $valW, $lineH, $rowGap, $fontSize
        ): void {
            $pdf->setTextColor(0, 0, 0);
            if ($numLabel !== null) {
                $pdf->textAligned($numX, $y, $numW, $numLabel, 'F1', $fontSize, 'L');
            }
            $lblX = $indentLabel ? $labelX + 4.0 : $labelX;
            $lblW = $indentLabel ? $labelW - 4.0 : $labelW;
            $pdf->textAligned($lblX, $y, $lblW, $label, 'F1', $fontSize, 'L');

            if ($value !== '' || $numLabel === null) {
                $pdf->textAligned($colonX, $y, $colonW, ':', 'F1', $fontSize, 'C');
                $h = $pdf->multiLineText($valX, $y, $valW, $lineH, $value, $valFont, $fontSize, 'L');
            } else {
                $h = $lineH;
            }

            $y += max($lineH, $h) + $rowGap;
        };

        $drawHeaderRow = function (string $label) use ($pdf, &$y, &$rowNumber, $numX, $numW, $labelX, $labelW, $lineH, $rowGap, $fontSize): void {
            $pdf->setTextColor(0, 0, 0);
            $pdf->textAligned($numX, $y, $numW, $rowNumber++.'.', 'F1', $fontSize, 'L');
            $pdf->textAligned($labelX, $y, $labelW + 40.0, $label.':', 'F1', $fontSize, 'L');
            $y += $lineH + ($rowGap * 0.7);
        };

        if ($isVisible('nama_lengkap')) {
            $drawRow($rowNumber++.'.', 'Nama Lengkap Peserta Didik', mb_strtoupper((string) ($student['nama'] ?? '-'), 'UTF-8'), 'F2');
        }
        if ($isVisible('nomor_induk')) {
            $nis = (string) ($student['nis'] ?? $student['nipd'] ?? '-');
            $nisn = (string) ($student['nisn'] ?? '-');
            $drawRow($rowNumber++.'.', 'Nomor Induk / NISN', "{$nis} / {$nisn}");
        }
        if ($isVisible('tempat_tanggal_lahir')) {
            $tempat = (string) ($student['tempat_lahir'] ?? '-');
            $tgl = (string) ($student['tanggal_lahir'] ?? $student['ttl'] ?? '-');
            $drawRow($rowNumber++.'.', 'Tempat, Tanggal Lahir', "{$tempat}, {$tgl}");
        }
        if ($isVisible('jenis_kelamin')) {
            $drawRow($rowNumber++.'.', 'Jenis Kelamin', (string) ($student['jk'] ?? '-'));
        }
        if ($isVisible('agama')) {
            $drawRow($rowNumber++.'.', 'Agama', (string) ($student['agama'] ?? '-'));
        }
        if ($isVisible('status_keluarga')) {
            $drawRow($rowNumber++.'.', 'Status dalam Keluarga', (string) ($student['status_keluarga'] ?? '-'));
        }
        if ($isVisible('anak_ke')) {
            $drawRow($rowNumber++.'.', 'Anak ke', (string) ($student['anak_ke'] ?? '-'));
        }
        if ($isVisible('alamat_peserta')) {
            $drawRow($rowNumber++.'.', 'Alamat Peserta Didik', (string) ($student['alamat'] ?? '-'));
        }
        if ($isVisible('nomor_telepon')) {
            $drawRow($rowNumber++.'.', 'Nomor Telepon', (string) ($student['no_wa'] ?? $student['hp'] ?? '-'));
        }
        if ($isVisible('sekolah_asal')) {
            $drawRow($rowNumber++.'.', 'Sekolah Asal', (string) ($student['sekolah_asal'] ?? '-'));
        }
        if ($isVisible('diterima_di_sekolah')) {
            $drawHeaderRow('Diterima di sekolah ini');
            $drawRow(null, 'a. Di kelas', (string) ($student['kelas_diterima'] ?? $student['kelas'] ?? '-'), 'F1', true);
            $drawRow(null, 'b. Pada tanggal', (string) ($student['tanggal_diterima'] ?? '-'), 'F1', true);
        }
        if ($isVisible('nama_orang_tua')) {
            $drawHeaderRow('Nama Orang Tua');
            $drawRow(null, 'a. Ayah', (string) ($student['ayah_nama'] ?? '-'), 'F1', true);
            $drawRow(null, 'b. Ibu', (string) ($student['ibu_nama'] ?? '-'), 'F1', true);
        }
        if ($isVisible('alamat_orang_tua')) {
            $drawRow($rowNumber++.'.', 'Alamat Orang Tua', (string) ($student['alamat'] ?? '-'));
        }
        if ($isVisible('telepon_orang_tua')) {
            $drawRow($rowNumber++.'.', 'Nomor Telepon Orang Tua', (string) ($student['no_wa'] ?? $student['hp'] ?? '-'));
        }
        if ($isVisible('pekerjaan_orang_tua')) {
            $drawHeaderRow('Pekerjaan Orang Tua');
            $drawRow(null, 'a. Ayah', (string) ($student['ayah_pekerjaan'] ?? '-'), 'F1', true);
            $drawRow(null, 'b. Ibu', (string) ($student['ibu_pekerjaan'] ?? '-'), 'F1', true);
        }
        if ($isVisible('nama_wali')) {
            $drawRow($rowNumber++.'.', 'Nama Wali', (string) ($student['wali_nama'] ?? '-'));
        }
        if ($isVisible('alamat_wali')) {
            $drawRow($rowNumber++.'.', 'Alamat Wali', (string) ($student['wali_alamat'] ?? '-'));
        }
        if ($isVisible('telepon_wali')) {
            $drawRow($rowNumber++.'.', 'Nomor Telepon Wali', (string) ($student['wali_hp'] ?? '-'));
        }
        if ($isVisible('pekerjaan_wali')) {
            $drawRow($rowNumber++.'.', 'Pekerjaan Wali', (string) ($student['wali_pekerjaan'] ?? '-'));
        }

        // Bottom Block: Pas Foto 3x4 (30mm x 40mm) + Signature (0.7cm gap)
        $sigX = 128.0;
        $sigW = 54.0;
        $photoW = 30.0;
        $photoH = 40.0;
        $photoX = $sigX - 7.0 - $photoW; // 0.7cm (7mm) left of signature column (~1.2x stamp radius from center)

        // Ensure photo & signature stay neatly near bottom without overlapping table
        $sigTopY = max($y + 6.0, 224.0);
        if ($sigTopY + 38.0 > 277.0) {
            $sigTopY = 235.0;
        }

        // Draw signature lines
        $tempat = (string) ($signatory['tempat_penandatangan'] ?? $signatory['tempat_titimangsa'] ?? 'Karanganyar');
        $tglDiterima = (string) ($student['tanggal_diterima'] ?? '-');
        $namaKepsek = (string) ($signatory['nama_kepala_sekolah'] ?? 'Nurul Choirul Janah, S.Pd.');
        $nipKepsek = (string) ($signatory['nip'] ?? '-');
        if (trim($nipKepsek) === '') {
            $nipKepsek = '-';
        }

        $pdf->setTextColor(0, 0, 0);
        $pdf->textAligned($sigX, $sigTopY, $sigW, "{$tempat}, {$tglDiterima}", 'F1', 10.5, 'L');
        $pdf->textAligned($sigX, $sigTopY + 5.0, $sigW, 'Kepala Sekolah', 'F1', 10.5, 'L');
        $pdf->textAligned($sigX, $sigTopY + 28.0, $sigW, $namaKepsek, 'F2', 10.5, 'L', true);
        $pdf->textAligned($sigX, $sigTopY + 33.0, $sigW, "NIP. {$nipKepsek}", 'F1', 10.5, 'L');

        // Align bottom of 3x4 photo box with NIP line
        $photoBottomY = $sigTopY + 33.5;
        $photoTopY = $photoBottomY - $photoH;

        $photoRendered = false;
        if ($showPhoto && ! empty($student['foto_path'])) {
            $absPhoto = Storage::disk('local')->path($student['foto_path']);
            if (is_file($absPhoto)) {
                $photoRendered = $pdf->addImage($absPhoto, $photoX, $photoTopY, $photoW, $photoH, true, false);
            }
        }

        if (! $photoRendered) {
            $pdf->setTextColor(156, 163, 175);
            $pdf->textAligned($photoX, $photoTopY + 18.0, $photoW, 'Pas Foto', 'F1', 8.5, 'C');
            $pdf->textAligned($photoX, $photoTopY + 22.5, $photoW, '3 x 4', 'F1', 8.5, 'C');
        }

        // Photo border (draw after image so border is crisp)
        $pdf->setDrawColor(0, 0, 0);
        $pdf->setLineWidth(0.35);
        $pdf->rect($photoX, $photoTopY, $photoW, $photoH, 'D');
    }
}

