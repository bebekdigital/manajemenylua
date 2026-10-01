import re

with open('app/Http/Controllers/StudentController.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace headers for buildSheet1
headers1_old = r"""        $headers = [
            'A' => ['NISN', 12],
            'B' => ['Nama Lengkap', 30],
            'C' => ['NIPD', 12],
            'D' => ['Jenjang', 12],
            'E' => ['Unit', 35],
            'F' => ['Program', 12],
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
            'V' => ['No. WA', 16],
            'W' => ['Email', 25],
            'X' => ['Nama Ayah', 25],
            'Y' => ['Tahun Lahir Ayah', 16],
            'Z' => ['Pendidikan Ayah', 18],
            'AA' => ['Pekerjaan Ayah', 20],
            'AB' => ['Penghasilan Ayah', 28],
            'AC' => ['NIK Ayah', 18],
            'AD' => ['Nama Ibu', 25],
            'AE' => ['Tahun Lahir Ibu', 16],
            'AF' => ['Pendidikan Ibu', 18],
            'AG' => ['Pekerjaan Ibu', 20],
            'AH' => ['Penghasilan Ibu', 28],
            'AI' => ['NIK Ibu', 18],
            'AJ' => ['Nama Wali', 25],
            'AK' => ['Tahun Lahir Wali', 16],
            'AL' => ['Pendidikan Wali', 18],
            'AM' => ['Pekerjaan Wali', 20],
            'AN' => ['Penghasilan Wali', 28],
            'AO' => ['NIK Wali', 18],
            'AP' => ['Hubungan Wali', 18],
            'AQ' => ['Penerima KIP (Ya/Tidak)', 25],
            'AR' => ['Nomor KIP', 20],
            'AS' => ['Nama di KIP', 25],
            'AT' => ['Kelayakan PIP (Ya/Tidak)', 25],
            'AU' => ['Alasan Layak PIP', 30],
            'AV' => ['Kebutuhan Khusus', 20],
            'AW' => ['Sekolah Asal', 30],
            'AX' => ['Anak ke -', 10],
            'AY' => ['No KK', 18],
            'AZ' => ['Jarak Rumah ke Sekolah', 25],
        ];"""

headers1_new = r"""        $headers = [
            'A' => ['NISN', 12],
            'B' => ['Nama Lengkap', 30],
            'C' => ['NIPD', 12],
            'D' => ['Jenjang', 12],
            'E' => ['Unit', 35],
            'F' => ['JK (L/P)', 10],
            'G' => ['Tempat Lahir', 20],
            'H' => ['Tanggal Lahir (YYYY-MM-DD)', 24],
            'I' => ['NIK', 18],
            'J' => ['Agama', 12],
            'K' => ['Jalan', 20],
            'L' => ['RT', 6],
            'M' => ['RW', 6],
            'N' => ['Dusun', 18],
            'O' => ['Desa/Kelurahan', 20],
            'P' => ['Kecamatan', 20],
            'Q' => ['Kabupaten', 20],
            'R' => ['Provinsi', 20],
            'S' => ['Jenis Tinggal', 20],
            'T' => ['Alat Transportasi', 20],
            'U' => ['No. WA', 16],
            'V' => ['Email', 25],
            'W' => ['Nama Ayah', 25],
            'X' => ['Tahun Lahir Ayah', 16],
            'Y' => ['Pendidikan Ayah', 18],
            'Z' => ['Pekerjaan Ayah', 20],
            'AA' => ['Penghasilan Ayah', 28],
            'AB' => ['NIK Ayah', 18],
            'AC' => ['Nama Ibu', 25],
            'AD' => ['Tahun Lahir Ibu', 16],
            'AE' => ['Pendidikan Ibu', 18],
            'AF' => ['Pekerjaan Ibu', 20],
            'AG' => ['Penghasilan Ibu', 28],
            'AH' => ['NIK Ibu', 18],
            'AI' => ['Nama Wali', 25],
            'AJ' => ['Tahun Lahir Wali', 16],
            'AK' => ['Pendidikan Wali', 18],
            'AL' => ['Pekerjaan Wali', 20],
            'AM' => ['Penghasilan Wali', 28],
            'AN' => ['NIK Wali', 18],
            'AO' => ['Hubungan Wali', 18],
            'AP' => ['Penerima KIP (Ya/Tidak)', 25],
            'AQ' => ['Nomor KIP', 20],
            'AR' => ['Nama di KIP', 25],
            'AS' => ['Kelayakan PIP (Ya/Tidak)', 25],
            'AT' => ['Alasan Layak PIP', 30],
            'AU' => ['Kebutuhan Khusus', 20],
            'AV' => ['Sekolah Asal', 30],
            'AW' => ['Anak ke -', 10],
            'AX' => ['No KK', 18],
            'AY' => ['Jarak Rumah ke Sekolah', 25],
        ];"""

content = content.replace(headers1_old, headers1_new)

# Replace data in buildSheet1
data1_old = r"""        $sheet->fromArray([[
            '0051234001', 'Ahmad Fauzi Rahman', '10231001',
            $jenjang ?? 'SD', $unitExample, 'Umum',
            'L', 'Karanganyar', '2014-03-15',"""
data1_new = r"""        $sheet->fromArray([[
            '0051234001', 'Ahmad Fauzi Rahman', '10231001',
            $jenjang ?? 'SD', $unitExample,
            'L', 'Karanganyar', '2014-03-15',"""
content = content.replace(data1_old, data1_new)

# Remove program validation and shift JK in buildSheet1
val_old = r"""            $valProgram = $sheet->getCell('F'.$row)->getDataValidation();
            $valProgram->setType(DataValidation::TYPE_LIST);
            $valProgram->setAllowBlank(true);
            $valProgram->setShowDropDown(true);
            $valProgram->setFormula1('Dropdowns!$B$2:$B$200');

            $valJk = $sheet->getCell('G'.$row)->getDataValidation();"""
val_new = r"""            $valJk = $sheet->getCell('F'.$row)->getDataValidation();"""
content = content.replace(val_old, val_new)

style1_old = r"""        $this->styleExampleRow($sheet, 2, 'A', 'AZ');"""
style1_new = r"""        $this->styleExampleRow($sheet, 2, 'A', 'AY');"""
content = content.replace(style1_old, style1_new)

# buildSheet3
headers3_old = r"""        $headers = [
            'A' => ['NISN', 12],
            'B' => ['Nama Lengkap', 30],
            'C' => ['Tahun Ajaran', 14],
            'D' => ['Semester', 12],
            'E' => ['Kelas/Rombel', 16],
            'F' => ['Tingkat', 14],
            'G' => ['Status Aktif', 16],
        ];"""
headers3_new = r"""        $headers = [
            'A' => ['NISN', 12],
            'B' => ['Nama Lengkap', 30],
            'C' => ['Tahun Ajaran', 14],
            'D' => ['Semester', 12],
            'E' => ['Program', 14],
            'F' => ['Kelas/Rombel', 16],
            'G' => ['Tingkat', 14],
            'H' => ['Status Aktif', 16],
        ];"""
content = content.replace(headers3_old, headers3_new)

data3_old = r"""        $sheet->fromArray([[
            '0051234001', 'Ahmad Fauzi Rahman', '2025/2026', 'Ganjil', $kelasExample, '', 'aktif'
        ]], null, 'A2');

        for ($row = 2; $row <= 1000; $row++) {
            // Formula for Tingkat based on Kelas
            $sheet->setCellValue('F'.$row, '=IF(E'.$row.'="","",IFERROR(VALUE(LEFT(E'.$row.', IF(ISNUMBER(VALUE(MID(E'.$row.',2,1))), 2, 1))), ""))');

            // Fixed/locked for Tingkat
            $valTingkat = $sheet->getCell('F'.$row)->getDataValidation();
            $valTingkat->setType(DataValidation::TYPE_CUSTOM);
            $valTingkat->setShowErrorMessage(true);
            $valTingkat->setErrorStyle(DataValidation::STYLE_STOP);
            $valTingkat->setErrorTitle('Kolom Otomatis');
            $valTingkat->setError('Kolom Tingkat ini berisi rumus otomatis dan tidak boleh diedit manual.');
            $valTingkat->setFormula1('""');

            // Dropdown for Kelas/Rombel
            $valClass = $sheet->getCell('E'.$row)->getDataValidation();
            $valClass->setType(DataValidation::TYPE_LIST);
            $valClass->setAllowBlank(true);
            $valClass->setShowDropDown(true);
            $valClass->setFormula1('Dropdowns!$C$2:$C$200');
        }

        $this->styleExampleRow($sheet, 2, 'A', 'G');"""
data3_new = r"""        $sheet->fromArray([[
            '0051234001', 'Ahmad Fauzi Rahman', '2025/2026', 'Ganjil', 'Umum', $kelasExample, '', 'aktif'
        ]], null, 'A2');

        for ($row = 2; $row <= 1000; $row++) {
            // Dropdown for Program
            $valProgram = $sheet->getCell('E'.$row)->getDataValidation();
            $valProgram->setType(DataValidation::TYPE_LIST);
            $valProgram->setAllowBlank(true);
            $valProgram->setShowDropDown(true);
            $valProgram->setFormula1('Dropdowns!$B$2:$B$200');

            // Formula for Tingkat based on Kelas
            $sheet->setCellValue('G'.$row, '=IF(F'.$row.'="","",IFERROR(VALUE(LEFT(F'.$row.', IF(ISNUMBER(VALUE(MID(F'.$row.',2,1))), 2, 1))), ""))');

            // Fixed/locked for Tingkat
            $valTingkat = $sheet->getCell('G'.$row)->getDataValidation();
            $valTingkat->setType(DataValidation::TYPE_CUSTOM);
            $valTingkat->setShowErrorMessage(true);
            $valTingkat->setErrorStyle(DataValidation::STYLE_STOP);
            $valTingkat->setErrorTitle('Kolom Otomatis');
            $valTingkat->setError('Kolom Tingkat ini berisi rumus otomatis dan tidak boleh diedit manual.');
            $valTingkat->setFormula1('""');

            // Dropdown for Kelas/Rombel
            $valClass = $sheet->getCell('F'.$row)->getDataValidation();
            $valClass->setType(DataValidation::TYPE_LIST);
            $valClass->setAllowBlank(true);
            $valClass->setShowDropDown(true);
            $valClass->setFormula1('Dropdowns!$C$2:$C$200');
        }

        $this->styleExampleRow($sheet, 2, 'A', 'H');"""
content = content.replace(data3_old, data3_new)

with open('app/Http/Controllers/StudentController.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Done updating StudentController.")
