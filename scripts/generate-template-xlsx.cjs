/**
 * generate-template-xlsx.cjs
 * --------------------------------------------------
 * Generates a multi-sheet XLSX template for
 * importing student data into MySQL.
 *
 * Sheets:
 *   1. Data Siswa   – flat student record (identity, address, family, welfare)
 *   2. Data Saudara – sibling records linked by NISN
 *
 * Usage:  node scripts/generate-template-xlsx.cjs
 * Output: storage/templates/template_database_siswa.xlsx
 */

const XLSX = require('xlsx');
const path = require('path');
const fs   = require('fs');

// ─── Column definitions ────────────────────────────────────────
const SISWA_HEADERS = [
    // Identitas
    'nisn',
    'nama',
    'nipd',
    'nik',
    'no_kk',
    'unit',
    'program',
    'jenjang',
    'tingkat',
    'kelas',
    'jk',
    'tempat_lahir',
    'tanggal_lahir',
    'agama',
    'no_wa',
    'sekolah_asal',

    // Alamat
    'jalan',
    'rt_rw',
    'dusun',
    'desa',
    'kecamatan',
    'kabupaten',
    'provinsi',

    // Data Ayah
    'ayah_nama',
    'ayah_tahun_lahir',
    'ayah_pekerjaan',
    'ayah_penghasilan',

    // Data Ibu
    'ibu_nama',
    'ibu_tahun_lahir',
    'ibu_pekerjaan',
    'ibu_penghasilan',

    // Data Wali (opsional)
    'wali_nama',
    'wali_hubungan',
    'wali_pekerjaan',
    'wali_penghasilan',

    // Kesejahteraan
    'desil',
    'status_pip',
    'pip_keterangan',
    'status_kip',
    'no_kip',
];

const SAUDARA_HEADERS = [
    'nisn',            // FK ke Data Siswa
    'nama_saudara',
    'tanggal_lahir',   // format YYYY-MM-DD
];

// ─── Example data (2 rows) ─────────────────────────────────────
const SISWA_EXAMPLES = [
    {
        nisn: '0051234001',
        nama: 'Ahmad Fauzi Rahman',
        nipd: '10231001',
        nik: '3313151503140001',
        no_kk: '3313150101080001',
        unit: 'SDIT Ulil Albab Gondangrejo',
        program: 'Umum',
        jenjang: 'SD',
        tingkat: 5,
        kelas: '5A',
        jk: 'L',
        tempat_lahir: 'Karanganyar',
        tanggal_lahir: '2014-03-15',
        agama: 'Islam',
        no_wa: '081234567001',
        sekolah_asal: 'TK Aisyiyah Colomadu',
        jalan: 'Jl. Lawu No. 12',
        rt_rw: 'RT 03 / RW 05',
        dusun: 'Ngemplak',
        desa: 'Colomadu',
        kecamatan: 'Colomadu',
        kabupaten: 'Karanganyar',
        provinsi: 'Jawa Tengah',
        ayah_nama: 'Fauzi Hidayat',
        ayah_tahun_lahir: 1980,
        ayah_pekerjaan: 'Wiraswasta',
        ayah_penghasilan: 'Rp 3.000.000 - Rp 5.000.000',
        ibu_nama: 'Siti Rahmawati',
        ibu_tahun_lahir: 1984,
        ibu_pekerjaan: 'Ibu Rumah Tangga',
        ibu_penghasilan: 'Kurang dari Rp 1.000.000',
        wali_nama: '',
        wali_hubungan: '',
        wali_pekerjaan: '',
        wali_penghasilan: '',
        desil: 3,
        status_pip: 'Ya',
        pip_keterangan: 'Penerima PIP tahap 1 TA 2025/2026',
        status_kip: 'Ya',
        no_kip: '6071012345670001',
    },
    {
        nisn: '0051234002',
        nama: 'Siti Aisyah Putri',
        nipd: '10231002',
        nik: '3372222207130002',
        no_kk: '3372220201090002',
        unit: 'SMPIT Ulil Albab Gondangrejo',
        program: 'Fullday',
        jenjang: 'SMP',
        tingkat: 7,
        kelas: '7A',
        jk: 'P',
        tempat_lahir: 'Surakarta',
        tanggal_lahir: '2013-07-22',
        agama: 'Islam',
        no_wa: '081234567002',
        sekolah_asal: 'TK Al-Irsyad Solo',
        jalan: 'Jl. Slamet Riyadi No. 45',
        rt_rw: 'RT 01 / RW 02',
        dusun: '-',
        desa: 'Laweyan',
        kecamatan: 'Laweyan',
        kabupaten: 'Surakarta',
        provinsi: 'Jawa Tengah',
        ayah_nama: 'Ahmad Zaini',
        ayah_tahun_lahir: 1978,
        ayah_pekerjaan: 'Pegawai Negeri',
        ayah_penghasilan: 'Rp 5.000.000 - Rp 10.000.000',
        ibu_nama: 'Nur Aisyah',
        ibu_tahun_lahir: 1982,
        ibu_pekerjaan: 'Guru',
        ibu_penghasilan: 'Rp 3.000.000 - Rp 5.000.000',
        wali_nama: '',
        wali_hubungan: '',
        wali_pekerjaan: '',
        wali_penghasilan: '',
        desil: 7,
        status_pip: 'Tidak',
        pip_keterangan: '',
        status_kip: 'Tidak',
        no_kip: '',
    },
];

const SAUDARA_EXAMPLES = [
    { nisn: '0051234001', nama_saudara: 'Aisyah Fauzia',    tanggal_lahir: '2016-05-12' },
    { nisn: '0051234001', nama_saudara: 'Muhammad Farhan',   tanggal_lahir: '2019-08-23' },
    { nisn: '0051234002', nama_saudara: 'Muhammad Zaini Jr.',tanggal_lahir: '2017-03-14' },
];

// ─── Build workbook ────────────────────────────────────────────

function buildSheet(headers, examples) {
    // First row = headers (bold via cell styling)
    const data = [headers];
    // Add example rows
    for (const row of examples) {
        data.push(headers.map(h => row[h] ?? ''));
    }

    const ws = XLSX.utils.aoa_to_sheet(data);

    // Set column widths based on header length (minimum 14)
    ws['!cols'] = headers.map(h => ({ wch: Math.max(h.length + 4, 14) }));

    return ws;
}

const wb = XLSX.utils.book_new();

// Sheet 1 – Data Siswa
const wsSiswa = buildSheet(SISWA_HEADERS, SISWA_EXAMPLES);
XLSX.utils.book_append_sheet(wb, wsSiswa, 'Data Siswa');

// Sheet 2 – Data Saudara
const wsSaudara = buildSheet(SAUDARA_HEADERS, SAUDARA_EXAMPLES);
XLSX.utils.book_append_sheet(wb, wsSaudara, 'Data Saudara');

// ─── Write to file ─────────────────────────────────────────────
const outDir = path.join(__dirname, '..', 'storage', 'templates');
if (!fs.existsSync(outDir)) {
    fs.mkdirSync(outDir, { recursive: true });
}

const outPath = path.join(outDir, 'template_database_siswa_v2.xlsx');
XLSX.writeFile(wb, outPath);

console.log(`✅ Template XLSX berhasil dibuat!`);
console.log(`   📁 ${outPath}`);
console.log('');
console.log('📋 Sheet "Data Siswa" — kolom:');
SISWA_HEADERS.forEach((h, i) => console.log(`   ${String(i+1).padStart(2)}.  ${h}`));
console.log('');
console.log('📋 Sheet "Data Saudara" — kolom:');
SAUDARA_HEADERS.forEach((h, i) => console.log(`   ${String(i+1).padStart(2)}.  ${h}`));
