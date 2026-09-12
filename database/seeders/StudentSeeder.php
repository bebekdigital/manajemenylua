<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Seed 20 students with family, sibling, and academic record data.
     */
    public function run(): void
    {
        $academicYear = AcademicYear::current();

        $students = [
            [
                'nisn' => '0051234001', 'nama' => 'Ahmad Fauzi Rahman', 'nipd' => '10231001',
                'jk' => 'L', 'tempat_lahir' => 'Karanganyar', 'tanggal_lahir' => '2014-03-15',
                'nik' => '3313151503140001', 'no_kk' => '3313150101080001', 'agama' => 'Islam',
                'unit' => 'SDIT Ulil Albab Gondangrejo', 'program' => 'Umum',
                'no_wa' => '081234567001', 'sekolah_asal' => 'TK Aisyiyah Colomadu',
                'jalan' => 'Jl. Lawu No. 12', 'rt_rw' => 'RT 03 / RW 05', 'dusun' => 'Ngemplak',
                'desa' => 'Colomadu', 'kecamatan' => 'Colomadu', 'kabupaten' => 'Karanganyar', 'provinsi' => 'Jawa Tengah',
                '_academic' => [
                    'jenjang' => 'SD', 'tingkat' => 5, 'kelas' => '5A', 'student_status' => 'aktif',
                    'desil' => 3, 'status_pip' => true, 'pip_keterangan' => 'Penerima PIP tahap 1 TA 2025/2026',
                    'status_kip' => true, 'no_kip' => '6071012345670001',
                ],
                '_family' => [
                    'ayah_nama' => 'Fauzi Hidayat', 'ayah_tahun_lahir' => 1980, 'ayah_pekerjaan' => 'Wiraswasta', 'ayah_penghasilan' => 'Rp 3.000.000 - Rp 5.000.000',
                    'ibu_nama' => 'Siti Rahmawati', 'ibu_tahun_lahir' => 1984, 'ibu_pekerjaan' => 'Ibu Rumah Tangga', 'ibu_penghasilan' => 'Kurang dari Rp 1.000.000',
                ],
                '_siblings' => [
                    ['nama' => 'Aisyah Fauzia', 'tanggal_lahir' => '2016-05-12'],
                    ['nama' => 'Muhammad Farhan', 'tanggal_lahir' => '2019-08-23'],
                ],
            ],
            [
                'nisn' => '0051234002', 'nama' => 'Siti Aisyah Putri', 'nipd' => '10231002',
                'jk' => 'P', 'tempat_lahir' => 'Surakarta', 'tanggal_lahir' => '2013-07-22',
                'nik' => '3372222207130002', 'no_kk' => '3372220201090002', 'agama' => 'Islam',
                'unit' => 'SDIT Ulil Albab Gondangrejo', 'program' => 'Umum',
                'no_wa' => '081234567002', 'sekolah_asal' => 'TK Al-Irsyad Solo',
                'jalan' => 'Jl. Slamet Riyadi No. 45', 'rt_rw' => 'RT 01 / RW 02', 'dusun' => '-',
                'desa' => 'Laweyan', 'kecamatan' => 'Laweyan', 'kabupaten' => 'Surakarta', 'provinsi' => 'Jawa Tengah',
                '_academic' => [
                    'jenjang' => 'SD', 'tingkat' => 6, 'kelas' => '6A', 'student_status' => 'aktif',
                    'desil' => 7, 'status_pip' => false, 'pip_keterangan' => '', 'status_kip' => false, 'no_kip' => '',
                ],
                '_family' => [
                    'ayah_nama' => 'Ahmad Zaini', 'ayah_tahun_lahir' => 1978, 'ayah_pekerjaan' => 'Pegawai Negeri', 'ayah_penghasilan' => 'Rp 5.000.000 - Rp 10.000.000',
                    'ibu_nama' => 'Nur Aisyah', 'ibu_tahun_lahir' => 1982, 'ibu_pekerjaan' => 'Guru', 'ibu_penghasilan' => 'Rp 3.000.000 - Rp 5.000.000',
                ],
                '_siblings' => [
                    ['nama' => 'Muhammad Zaini Jr.', 'tanggal_lahir' => '2017-03-14'],
                ],
            ],
            [
                'nisn' => '0051234003', 'nama' => 'Budi Santoso', 'nipd' => '10231003',
                'jk' => 'L', 'tempat_lahir' => 'Karanganyar', 'tanggal_lahir' => '2014-01-03',
                'nik' => '3313150301140003', 'no_kk' => '3313150301080003', 'agama' => 'Islam',
                'unit' => 'SDIT Ulil Albab Gondangrejo', 'program' => 'Umum',
                'no_wa' => '082134567003', 'sekolah_asal' => 'TK Pertiwi Jaten',
                'jalan' => 'Dk. Ngemplak', 'rt_rw' => 'RT 02 / RW 04', 'dusun' => 'Ngemplak',
                'desa' => 'Jaten', 'kecamatan' => 'Jaten', 'kabupaten' => 'Karanganyar', 'provinsi' => 'Jawa Tengah',
                '_academic' => [
                    'jenjang' => 'SD', 'tingkat' => 5, 'kelas' => '5B', 'student_status' => 'aktif',
                    'desil' => 1, 'status_pip' => true, 'pip_keterangan' => 'Penerima PIP tahap 2 TA 2025/2026',
                    'status_kip' => true, 'no_kip' => '6071012345670003',
                ],
                '_family' => [
                    'ayah_nama' => 'Sugiyanto', 'ayah_tahun_lahir' => 1976, 'ayah_pekerjaan' => 'Buruh Harian', 'ayah_penghasilan' => 'Rp 1.000.000 - Rp 3.000.000',
                    'ibu_nama' => 'Sunarti', 'ibu_tahun_lahir' => 1980, 'ibu_pekerjaan' => 'Pedagang', 'ibu_penghasilan' => 'Rp 1.000.000 - Rp 3.000.000',
                ],
                '_siblings' => [
                    ['nama' => 'Dewi Santoso', 'tanggal_lahir' => '2011-04-20'],
                    ['nama' => 'Rina Santoso', 'tanggal_lahir' => '2017-09-05'],
                    ['nama' => 'Adi Santoso', 'tanggal_lahir' => '2020-12-01'],
                ],
            ],
            [
                'nisn' => '0051234004', 'nama' => 'Dewi Lestari', 'nipd' => '10231004',
                'jk' => 'P', 'tempat_lahir' => 'Sragen', 'tanggal_lahir' => '2013-11-10',
                'nik' => '3314101011130004', 'no_kk' => '3314100101090004', 'agama' => 'Islam',
                'unit' => 'SDIT Ulil Albab Gondangrejo', 'program' => 'Umum',
                'no_wa' => '081345678004', 'sekolah_asal' => 'TK Dharma Wanita Sragen',
                'jalan' => 'Jl. Raya Sukowati No. 78', 'rt_rw' => 'RT 05 / RW 08', 'dusun' => '-',
                'desa' => 'Sragen Kulon', 'kecamatan' => 'Sragen', 'kabupaten' => 'Sragen', 'provinsi' => 'Jawa Tengah',
                '_academic' => [
                    'jenjang' => 'SD', 'tingkat' => 6, 'kelas' => '6B', 'student_status' => 'aktif',
                    'desil' => 2, 'status_pip' => true, 'pip_keterangan' => 'Penerima PIP tahap 1 TA 2025/2026',
                    'status_kip' => true, 'no_kip' => '6071012345670004',
                ],
                '_family' => [
                    'ayah_nama' => 'Surono', 'ayah_tahun_lahir' => 1975, 'ayah_pekerjaan' => 'Petani', 'ayah_penghasilan' => 'Rp 1.000.000 - Rp 3.000.000',
                    'ibu_nama' => 'Lestari Wulandari', 'ibu_tahun_lahir' => 1979, 'ibu_pekerjaan' => 'Ibu Rumah Tangga', 'ibu_penghasilan' => 'Kurang dari Rp 1.000.000',
                    'wali_nama' => 'Hj. Suminah', 'wali_hubungan' => 'Nenek', 'wali_pekerjaan' => 'Pensiunan', 'wali_penghasilan' => 'Rp 1.000.000 - Rp 3.000.000',
                ],
                '_siblings' => [
                    ['nama' => 'Riko Lestari', 'tanggal_lahir' => '2010-07-15'],
                ],
            ],
            [
                'nisn' => '0051234005', 'nama' => 'Muhammad Rizki Pratama', 'nipd' => '10231005',
                'jk' => 'L', 'tempat_lahir' => 'Karanganyar', 'tanggal_lahir' => '2014-05-28',
                'nik' => '3313152805140005', 'no_kk' => '3313150101080005', 'agama' => 'Islam',
                'unit' => 'SDIT Ulil Albab Gondangrejo', 'program' => 'Umum',
                'no_wa' => '085678901005', 'sekolah_asal' => 'TK Aisyiyah Palur',
                'jalan' => 'Perum Griya Asri Blok C-7', 'rt_rw' => 'RT 01 / RW 12', 'dusun' => 'Ngringo',
                'desa' => 'Palur', 'kecamatan' => 'Jaten', 'kabupaten' => 'Karanganyar', 'provinsi' => 'Jawa Tengah',
                '_academic' => [
                    'jenjang' => 'SD', 'tingkat' => 5, 'kelas' => '5A', 'student_status' => 'aktif',
                    'desil' => 8, 'status_pip' => false, 'pip_keterangan' => '', 'status_kip' => false, 'no_kip' => '',
                ],
                '_family' => [
                    'ayah_nama' => 'Pratama Wijaya', 'ayah_tahun_lahir' => 1982, 'ayah_pekerjaan' => 'Karyawan Swasta', 'ayah_penghasilan' => 'Rp 3.000.000 - Rp 5.000.000',
                    'ibu_nama' => 'Rina Pratiwi', 'ibu_tahun_lahir' => 1985, 'ibu_pekerjaan' => 'Karyawan Swasta', 'ibu_penghasilan' => 'Rp 3.000.000 - Rp 5.000.000',
                ],
                '_siblings' => [],
            ],
            [
                'nisn' => '0051234006', 'nama' => 'Nur Halimah', 'nipd' => '10231006',
                'jk' => 'P', 'tempat_lahir' => 'Boyolali', 'tanggal_lahir' => '2014-02-14',
                'nik' => '3309141402140006', 'no_kk' => '3309140101080006', 'agama' => 'Islam',
                'unit' => 'SDIT Ulil Albab Gondangrejo', 'program' => 'Umum',
                'no_wa' => '087890123006', 'sekolah_asal' => 'RA Al-Hidayah Boyolali',
                'jalan' => 'Dk. Karangjati', 'rt_rw' => 'RT 01 / RW 03', 'dusun' => 'Karangjati',
                'desa' => 'Ngemplak', 'kecamatan' => 'Ngemplak', 'kabupaten' => 'Boyolali', 'provinsi' => 'Jawa Tengah',
                '_academic' => [
                    'jenjang' => 'SD', 'tingkat' => 5, 'kelas' => '5B', 'student_status' => 'aktif',
                    'desil' => 4, 'status_pip' => true, 'pip_keterangan' => 'Penerima PIP tahap 1 TA 2025/2026',
                    'status_kip' => true, 'no_kip' => '6071012345670006',
                ],
                '_family' => [
                    'ayah_nama' => 'Halim Mujahid', 'ayah_tahun_lahir' => 1979, 'ayah_pekerjaan' => 'Pedagang', 'ayah_penghasilan' => 'Rp 1.000.000 - Rp 3.000.000',
                    'ibu_nama' => 'Nurhayati', 'ibu_tahun_lahir' => 1983, 'ibu_pekerjaan' => 'Ibu Rumah Tangga', 'ibu_penghasilan' => 'Kurang dari Rp 1.000.000',
                ],
                '_siblings' => [
                    ['nama' => 'Ahmad Halim', 'tanggal_lahir' => '2012-06-18'],
                    ['nama' => 'Fatma Halimah', 'tanggal_lahir' => '2018-11-30'],
                ],
            ],
            [
                'nisn' => '0051234007', 'nama' => 'Fajar Dwi Nugroho', 'nipd' => '10231007',
                'jk' => 'L', 'tempat_lahir' => 'Karanganyar', 'tanggal_lahir' => '2013-09-09',
                'nik' => '3313150909130007', 'no_kk' => '3313150101080007', 'agama' => 'Islam',
                'unit' => 'SDIT Ulil Albab Gondangrejo', 'program' => 'Umum',
                'no_wa' => '081234567007', 'sekolah_asal' => 'TK Pertiwi Jaten',
                'jalan' => 'Jl. Kapten Mulyadi No. 33', 'rt_rw' => 'RT 04 / RW 06', 'dusun' => 'Jaten',
                'desa' => 'Jaten', 'kecamatan' => 'Jaten', 'kabupaten' => 'Karanganyar', 'provinsi' => 'Jawa Tengah',
                '_academic' => [
                    'jenjang' => 'SD', 'tingkat' => 6, 'kelas' => '6A', 'student_status' => 'aktif',
                    'desil' => 9, 'status_pip' => false, 'pip_keterangan' => '', 'status_kip' => false, 'no_kip' => '',
                ],
                '_family' => [
                    'ayah_nama' => 'Nugroho Adi', 'ayah_tahun_lahir' => 1977, 'ayah_pekerjaan' => 'TNI/Polri', 'ayah_penghasilan' => 'Rp 5.000.000 - Rp 10.000.000',
                    'ibu_nama' => 'Dwi Astuti', 'ibu_tahun_lahir' => 1981, 'ibu_pekerjaan' => 'Ibu Rumah Tangga', 'ibu_penghasilan' => 'Kurang dari Rp 1.000.000',
                ],
                '_siblings' => [
                    ['nama' => 'Galang Nugroho', 'tanggal_lahir' => '2016-02-28'],
                ],
            ],
            [
                'nisn' => '0051234008', 'nama' => 'Zahra Amelia Putri', 'nipd' => '10231008',
                'jk' => 'P', 'tempat_lahir' => 'Sukoharjo', 'tanggal_lahir' => '2014-04-07',
                'nik' => '3311070704140008', 'no_kk' => '3311070101080008', 'agama' => 'Islam',
                'unit' => 'SDIT Ulil Albab Gondangrejo', 'program' => 'Umum',
                'no_wa' => '089012345008', 'sekolah_asal' => 'TK Pembina Kartasura',
                'jalan' => 'Jl. Ir. Soekarno No. 19', 'rt_rw' => 'RT 02 / RW 07', 'dusun' => '-',
                'desa' => 'Kartasura', 'kecamatan' => 'Kartasura', 'kabupaten' => 'Sukoharjo', 'provinsi' => 'Jawa Tengah',
                '_academic' => [
                    'jenjang' => 'SD', 'tingkat' => 5, 'kelas' => '5A', 'student_status' => 'aktif',
                    'desil' => 10, 'status_pip' => false, 'pip_keterangan' => '', 'status_kip' => false, 'no_kip' => '',
                ],
                '_family' => [
                    'ayah_nama' => 'Arif Setiawan', 'ayah_tahun_lahir' => 1981, 'ayah_pekerjaan' => 'Dosen', 'ayah_penghasilan' => 'Rp 5.000.000 - Rp 10.000.000',
                    'ibu_nama' => 'Amelia Sari', 'ibu_tahun_lahir' => 1984, 'ibu_pekerjaan' => 'Dokter', 'ibu_penghasilan' => 'Rp 10.000.000 ke atas',
                ],
                '_siblings' => [
                    ['nama' => 'Zain Arif', 'tanggal_lahir' => '2017-01-09'],
                ],
            ],
            [
                'nisn' => '0051234009', 'nama' => 'Rafi Ahmad Hidayat', 'nipd' => '10231009',
                'jk' => 'L', 'tempat_lahir' => 'Karanganyar', 'tanggal_lahir' => '2014-08-21',
                'nik' => '3313152108140009', 'no_kk' => '3313150101080009', 'agama' => 'Islam',
                'unit' => 'SDIT Ulil Albab Gondangrejo', 'program' => 'Umum',
                'no_wa' => '082345678009', 'sekolah_asal' => 'TK IT Nur Hidayah',
                'jalan' => 'Dk. Papahan', 'rt_rw' => 'RT 03 / RW 06', 'dusun' => 'Papahan',
                'desa' => 'Tasikmadu', 'kecamatan' => 'Tasikmadu', 'kabupaten' => 'Karanganyar', 'provinsi' => 'Jawa Tengah',
                '_academic' => [
                    'jenjang' => 'SD', 'tingkat' => 5, 'kelas' => '5B', 'student_status' => 'aktif',
                    'desil' => 5, 'status_pip' => false, 'pip_keterangan' => '', 'status_kip' => false, 'no_kip' => '',
                ],
                '_family' => [
                    'ayah_nama' => 'Hidayat Nur', 'ayah_tahun_lahir' => 1980, 'ayah_pekerjaan' => 'Wiraswasta', 'ayah_penghasilan' => 'Rp 3.000.000 - Rp 5.000.000',
                    'ibu_nama' => 'Fatimah Hidayat', 'ibu_tahun_lahir' => 1983, 'ibu_pekerjaan' => 'Ibu Rumah Tangga', 'ibu_penghasilan' => 'Kurang dari Rp 1.000.000',
                ],
                '_siblings' => [
                    ['nama' => 'Rafli Hidayat', 'tanggal_lahir' => '2012-10-11'],
                    ['nama' => 'Rania Hidayat', 'tanggal_lahir' => '2018-03-25'],
                ],
            ],
            [
                'nisn' => '0051234010', 'nama' => 'Anisa Rahmawati', 'nipd' => '10231010',
                'jk' => 'P', 'tempat_lahir' => 'Karanganyar', 'tanggal_lahir' => '2013-06-30',
                'nik' => '3313153006130010', 'no_kk' => '3313150101080010', 'agama' => 'Islam',
                'unit' => 'SDIT Ulil Albab Gondangrejo', 'program' => 'Umum',
                'no_wa' => '081567890010', 'sekolah_asal' => 'TK Aisyiyah Bejen',
                'jalan' => 'Jl. Tentara Pelajar No. 5', 'rt_rw' => 'RT 02 / RW 01', 'dusun' => 'Bejen',
                'desa' => 'Bejen', 'kecamatan' => 'Karanganyar', 'kabupaten' => 'Karanganyar', 'provinsi' => 'Jawa Tengah',
                '_academic' => [
                    'jenjang' => 'SD', 'tingkat' => 6, 'kelas' => '6B', 'student_status' => 'aktif',
                    'desil' => 6, 'status_pip' => false, 'pip_keterangan' => '', 'status_kip' => false, 'no_kip' => '',
                ],
                '_family' => [
                    'ayah_nama' => 'Rahman Wijaya', 'ayah_tahun_lahir' => 1974, 'ayah_pekerjaan' => 'Pedagang', 'ayah_penghasilan' => 'Rp 3.000.000 - Rp 5.000.000',
                    'ibu_nama' => 'Rahmawati', 'ibu_tahun_lahir' => 1978, 'ibu_pekerjaan' => 'Guru', 'ibu_penghasilan' => 'Rp 3.000.000 - Rp 5.000.000',
                ],
                '_siblings' => [
                    ['nama' => 'Arif Rahmawan', 'tanggal_lahir' => '2010-08-22'],
                ],
            ],
            // --- SMPIT Ulil Albab Gondangrejo (Fullday) ---
            [
                'nisn' => '0051234011', 'nama' => 'Dimas Arya Kusuma', 'nipd' => '10231011',
                'jk' => 'L', 'tempat_lahir' => 'Wonogiri', 'tanggal_lahir' => '2013-12-18',
                'nik' => '3312181212130011', 'no_kk' => '3312180101080011', 'agama' => 'Islam',
                'unit' => 'SMPIT Ulil Albab Gondangrejo', 'program' => 'Fullday',
                'no_wa' => '085678901011', 'sekolah_asal' => 'TK Darussalam Wonogiri',
                'jalan' => 'Jl. Diponegoro No. 52', 'rt_rw' => 'RT 06 / RW 03', 'dusun' => '-',
                'desa' => 'Wonogiri', 'kecamatan' => 'Wonogiri', 'kabupaten' => 'Wonogiri', 'provinsi' => 'Jawa Tengah',
                '_academic' => [
                    'jenjang' => 'SMP', 'tingkat' => 7, 'kelas' => '7A', 'student_status' => 'aktif',
                    'desil' => 8, 'status_pip' => false, 'pip_keterangan' => '', 'status_kip' => false, 'no_kip' => '',
                ],
                '_family' => [
                    'ayah_nama' => 'Kusuma Adi', 'ayah_tahun_lahir' => 1976, 'ayah_pekerjaan' => 'PNS', 'ayah_penghasilan' => 'Rp 5.000.000 - Rp 10.000.000',
                    'ibu_nama' => 'Arya Dewi', 'ibu_tahun_lahir' => 1980, 'ibu_pekerjaan' => 'PNS', 'ibu_penghasilan' => 'Rp 3.000.000 - Rp 5.000.000',
                ],
                '_siblings' => [],
            ],
            [
                'nisn' => '0051234012', 'nama' => 'Fatimah Azzahra', 'nipd' => '10231012',
                'jk' => 'P', 'tempat_lahir' => 'Karanganyar', 'tanggal_lahir' => '2014-10-05',
                'nik' => '3313150510140012', 'no_kk' => '3313150101080012', 'agama' => 'Islam',
                'unit' => 'SMPIT Ulil Albab Gondangrejo', 'program' => 'Fullday',
                'no_wa' => '087890123012', 'sekolah_asal' => 'RA Al-Muttaqin',
                'jalan' => 'Dk. Gaum', 'rt_rw' => 'RT 04 / RW 02', 'dusun' => 'Gaum',
                'desa' => 'Tasikmadu', 'kecamatan' => 'Tasikmadu', 'kabupaten' => 'Karanganyar', 'provinsi' => 'Jawa Tengah',
                '_academic' => [
                    'jenjang' => 'SMP', 'tingkat' => 7, 'kelas' => '7A', 'student_status' => 'aktif',
                    'desil' => 2, 'status_pip' => true, 'pip_keterangan' => 'Penerima PIP tahap 2 TA 2025/2026',
                    'status_kip' => true, 'no_kip' => '6071012345670012',
                ],
                '_family' => [
                    'ayah_nama' => 'Zainal Arifin', 'ayah_tahun_lahir' => 1979, 'ayah_pekerjaan' => 'Buruh Pabrik', 'ayah_penghasilan' => 'Rp 1.000.000 - Rp 3.000.000',
                    'ibu_nama' => 'Azzah Nur', 'ibu_tahun_lahir' => 1982, 'ibu_pekerjaan' => 'Ibu Rumah Tangga', 'ibu_penghasilan' => 'Kurang dari Rp 1.000.000',
                ],
                '_siblings' => [
                    ['nama' => 'Zaki Arifin', 'tanggal_lahir' => '2012-01-17'],
                    ['nama' => 'Zakiyah Arifin', 'tanggal_lahir' => '2017-07-04'],
                    ['nama' => 'Zulfa Arifin', 'tanggal_lahir' => '2020-11-19'],
                ],
            ],
            [
                'nisn' => '0051234013', 'nama' => 'Ilham Maulana', 'nipd' => '10231013',
                'jk' => 'L', 'tempat_lahir' => 'Klaten', 'tanggal_lahir' => '2014-03-12',
                'nik' => '3310121203140013', 'no_kk' => '3310120101080013', 'agama' => 'Islam',
                'unit' => 'SMPIT Ulil Albab Gondangrejo', 'program' => 'Fullday',
                'no_wa' => '081234567013', 'sekolah_asal' => 'TK Pembina Klaten',
                'jalan' => 'Jl. Pemuda No. 67', 'rt_rw' => 'RT 03 / RW 05', 'dusun' => '-',
                'desa' => 'Klaten Utara', 'kecamatan' => 'Klaten Utara', 'kabupaten' => 'Klaten', 'provinsi' => 'Jawa Tengah',
                '_academic' => [
                    'jenjang' => 'SMP', 'tingkat' => 8, 'kelas' => '8A', 'student_status' => 'aktif',
                    'desil' => 5, 'status_pip' => false, 'pip_keterangan' => '', 'status_kip' => false, 'no_kip' => '',
                ],
                '_family' => [
                    'ayah_nama' => 'Maulana Hasan', 'ayah_tahun_lahir' => 1980, 'ayah_pekerjaan' => 'Karyawan Swasta', 'ayah_penghasilan' => 'Rp 3.000.000 - Rp 5.000.000',
                    'ibu_nama' => 'Hasanah', 'ibu_tahun_lahir' => 1984, 'ibu_pekerjaan' => 'Ibu Rumah Tangga', 'ibu_penghasilan' => 'Kurang dari Rp 1.000.000',
                ],
                '_siblings' => [
                    ['nama' => 'Hasna Maulana', 'tanggal_lahir' => '2016-06-21'],
                ],
            ],
            [
                'nisn' => '0051234014', 'nama' => 'Khadijah Nur Aini', 'nipd' => '10231014',
                'jk' => 'P', 'tempat_lahir' => 'Karanganyar', 'tanggal_lahir' => '2014-01-27',
                'nik' => '3313152701140014', 'no_kk' => '3313150101080014', 'agama' => 'Islam',
                'unit' => 'SMPIT Ulil Albab Gondangrejo', 'program' => 'Fullday',
                'no_wa' => '082345678014', 'sekolah_asal' => 'TK IT Tawangmangu',
                'jalan' => 'Jl. Lawu Barat No. 8', 'rt_rw' => 'RT 01 / RW 04', 'dusun' => 'Kalisoro',
                'desa' => 'Tawangmangu', 'kecamatan' => 'Tawangmangu', 'kabupaten' => 'Karanganyar', 'provinsi' => 'Jawa Tengah',
                '_academic' => [
                    'jenjang' => 'SMP', 'tingkat' => 8, 'kelas' => '8A', 'student_status' => 'aktif',
                    'desil' => 3, 'status_pip' => true, 'pip_keterangan' => 'Penerima PIP tahap 1 TA 2025/2026',
                    'status_kip' => true, 'no_kip' => '6071012345670014',
                ],
                '_family' => [
                    'ayah_nama' => 'Nur Rohman', 'ayah_tahun_lahir' => 1978, 'ayah_pekerjaan' => 'Petani', 'ayah_penghasilan' => 'Rp 1.000.000 - Rp 3.000.000',
                    'ibu_nama' => 'Aini Sulistyowati', 'ibu_tahun_lahir' => 1981, 'ibu_pekerjaan' => 'Pedagang', 'ibu_penghasilan' => 'Rp 1.000.000 - Rp 3.000.000',
                ],
                '_siblings' => [
                    ['nama' => 'Nur Aini Putri', 'tanggal_lahir' => '2011-09-03'],
                    ['nama' => 'Nur Rohim', 'tanggal_lahir' => '2018-04-16'],
                ],
            ],
            [
                'nisn' => '0051234015', 'nama' => 'Yoga Aditya Putra', 'nipd' => '10231015',
                'jk' => 'L', 'tempat_lahir' => 'Surakarta', 'tanggal_lahir' => '2013-06-19',
                'nik' => '3372191906130015', 'no_kk' => '3372190101080015', 'agama' => 'Islam',
                'unit' => 'SMPIT Ulil Albab Gondangrejo', 'program' => 'Fullday',
                'no_wa' => '085678901015', 'sekolah_asal' => 'TK Budi Mulia Solo',
                'jalan' => 'Jl. Adi Sucipto No. 101', 'rt_rw' => 'RT 04 / RW 09', 'dusun' => 'Jajar',
                'desa' => 'Laweyan', 'kecamatan' => 'Laweyan', 'kabupaten' => 'Surakarta', 'provinsi' => 'Jawa Tengah',
                '_academic' => [
                    'jenjang' => 'SMP', 'tingkat' => 9, 'kelas' => '9A', 'student_status' => 'aktif',
                    'desil' => 10, 'status_pip' => false, 'pip_keterangan' => '', 'status_kip' => false, 'no_kip' => '',
                ],
                '_family' => [
                    'ayah_nama' => 'Aditya Pratama', 'ayah_tahun_lahir' => 1977, 'ayah_pekerjaan' => 'Pengusaha', 'ayah_penghasilan' => 'Rp 10.000.000 ke atas',
                    'ibu_nama' => 'Putri Handayani', 'ibu_tahun_lahir' => 1980, 'ibu_pekerjaan' => 'Ibu Rumah Tangga', 'ibu_penghasilan' => 'Kurang dari Rp 1.000.000',
                ],
                '_siblings' => [
                    ['nama' => 'Yoga Pratama Jr.', 'tanggal_lahir' => '2017-12-02'],
                ],
            ],
            [
                'nisn' => '0051234016', 'nama' => 'Nabila Syifa Aulia', 'nipd' => '10231016',
                'jk' => 'P', 'tempat_lahir' => 'Karanganyar', 'tanggal_lahir' => '2014-08-08',
                'nik' => '3313150808140016', 'no_kk' => '3313150101080016', 'agama' => 'Islam',
                'unit' => 'SMPIT Ulil Albab Gondangrejo', 'program' => 'Fullday',
                'no_wa' => '087890123016', 'sekolah_asal' => 'TK Aisyiyah Jaten',
                'jalan' => 'Perum Josroyo Indah Blok A-12', 'rt_rw' => 'RT 06 / RW 15', 'dusun' => 'Josroyo',
                'desa' => 'Jaten', 'kecamatan' => 'Jaten', 'kabupaten' => 'Karanganyar', 'provinsi' => 'Jawa Tengah',
                '_academic' => [
                    'jenjang' => 'SMP', 'tingkat' => 9, 'kelas' => '9A', 'student_status' => 'aktif',
                    'desil' => 7, 'status_pip' => false, 'pip_keterangan' => '', 'status_kip' => false, 'no_kip' => '',
                ],
                '_family' => [
                    'ayah_nama' => 'Aulia Rahman', 'ayah_tahun_lahir' => 1982, 'ayah_pekerjaan' => 'Karyawan Swasta', 'ayah_penghasilan' => 'Rp 3.000.000 - Rp 5.000.000',
                    'ibu_nama' => 'Syifa Mariana', 'ibu_tahun_lahir' => 1985, 'ibu_pekerjaan' => 'Apoteker', 'ibu_penghasilan' => 'Rp 5.000.000 - Rp 10.000.000',
                ],
                '_siblings' => [],
            ],
            // --- PPTQ Ulil Albab Gondangrejo (Boarding) ---
            [
                'nisn' => '0051234017', 'nama' => 'Hasan Abdullah', 'nipd' => '10231017',
                'jk' => 'L', 'tempat_lahir' => 'Karanganyar', 'tanggal_lahir' => '2014-04-25',
                'nik' => '3313152504140017', 'no_kk' => '3313150101080017', 'agama' => 'Islam',
                'unit' => 'PPTQ Ulil Albab Gondangrejo', 'program' => 'Boarding',
                'no_wa' => '081234567017', 'sekolah_asal' => 'RA Al-Ikhlas Popongan',
                'jalan' => 'Dk. Popongan', 'rt_rw' => 'RT 01 / RW 05', 'dusun' => 'Popongan',
                'desa' => 'Karanganyar', 'kecamatan' => 'Karanganyar', 'kabupaten' => 'Karanganyar', 'provinsi' => 'Jawa Tengah',
                '_academic' => [
                    'jenjang' => 'SMP', 'tingkat' => 7, 'kelas' => '7-Tahfidz', 'student_status' => 'aktif',
                    'desil' => 1, 'status_pip' => true, 'pip_keterangan' => 'Penerima PIP tahap 1 TA 2025/2026',
                    'status_kip' => true, 'no_kip' => '6071012345670017',
                ],
                '_family' => [
                    'ayah_nama' => 'Abdullah Faqih', 'ayah_tahun_lahir' => 1975, 'ayah_pekerjaan' => 'Ustadz', 'ayah_penghasilan' => 'Rp 1.000.000 - Rp 3.000.000',
                    'ibu_nama' => 'Hana Abdullah', 'ibu_tahun_lahir' => 1979, 'ibu_pekerjaan' => 'Ibu Rumah Tangga', 'ibu_penghasilan' => 'Kurang dari Rp 1.000.000',
                ],
                '_siblings' => [
                    ['nama' => 'Husain Abdullah', 'tanggal_lahir' => '2011-02-14'],
                    ['nama' => 'Hamzah Abdullah', 'tanggal_lahir' => '2016-08-07'],
                    ['nama' => 'Hafsah Abdullah', 'tanggal_lahir' => '2019-05-30'],
                    ['nama' => 'Hafiz Abdullah', 'tanggal_lahir' => '2021-10-12'],
                ],
            ],
            [
                'nisn' => '0051234018', 'nama' => 'Rina Melati Sari', 'nipd' => '10231018',
                'jk' => 'P', 'tempat_lahir' => 'Sragen', 'tanggal_lahir' => '2013-11-16',
                'nik' => '3314161611130018', 'no_kk' => '3314160101080018', 'agama' => 'Islam',
                'unit' => 'PPTQ Ulil Albab Gondangrejo', 'program' => 'Boarding',
                'no_wa' => '082345678018', 'sekolah_asal' => 'TK Dharma Wanita Sragen',
                'jalan' => 'Jl. Ahmad Yani No. 30', 'rt_rw' => 'RT 02 / RW 04', 'dusun' => '-',
                'desa' => 'Sragen Wetan', 'kecamatan' => 'Sragen', 'kabupaten' => 'Sragen', 'provinsi' => 'Jawa Tengah',
                '_academic' => [
                    'jenjang' => 'SMP', 'tingkat' => 8, 'kelas' => '8-Tahfidz', 'student_status' => 'aktif',
                    'desil' => 4, 'status_pip' => true, 'pip_keterangan' => 'Penerima PIP tahap 2 TA 2025/2026',
                    'status_kip' => true, 'no_kip' => '6071012345670018',
                ],
                '_family' => [
                    'ayah_nama' => 'Sarimin', 'ayah_tahun_lahir' => 1973, 'ayah_pekerjaan' => 'Pedagang', 'ayah_penghasilan' => 'Rp 1.000.000 - Rp 3.000.000',
                    'ibu_nama' => 'Melati Indah', 'ibu_tahun_lahir' => 1977, 'ibu_pekerjaan' => 'Penjahit', 'ibu_penghasilan' => 'Rp 1.000.000 - Rp 3.000.000',
                ],
                '_siblings' => [
                    ['nama' => 'Riko Sarimin', 'tanggal_lahir' => '2010-03-08'],
                    ['nama' => 'Rani Melati', 'tanggal_lahir' => '2017-10-25'],
                ],
            ],
            [
                'nisn' => '0051234019', 'nama' => 'Arkan Zaki Firmansyah', 'nipd' => '10231019',
                'jk' => 'L', 'tempat_lahir' => 'Karanganyar', 'tanggal_lahir' => '2014-02-02',
                'nik' => '3313150202140019', 'no_kk' => '3313150101080019', 'agama' => 'Islam',
                'unit' => 'PPTQ Ulil Albab Gondangrejo', 'program' => 'Boarding',
                'no_wa' => '085678901019', 'sekolah_asal' => 'TK IT Lukmanul Hakim',
                'jalan' => 'Dk. Gerdu', 'rt_rw' => 'RT 02 / RW 01', 'dusun' => 'Gerdu',
                'desa' => 'Karangpandan', 'kecamatan' => 'Karangpandan', 'kabupaten' => 'Karanganyar', 'provinsi' => 'Jawa Tengah',
                '_academic' => [
                    'jenjang' => 'SMP', 'tingkat' => 8, 'kelas' => '8-Tahfidz', 'student_status' => 'aktif',
                    'desil' => 2, 'status_pip' => true, 'pip_keterangan' => 'Penerima PIP tahap 1 TA 2025/2026',
                    'status_kip' => true, 'no_kip' => '6071012345670019',
                ],
                '_family' => [
                    'ayah_nama' => 'Firmansyah', 'ayah_tahun_lahir' => 1981, 'ayah_pekerjaan' => 'Sopir', 'ayah_penghasilan' => 'Rp 1.000.000 - Rp 3.000.000',
                    'ibu_nama' => 'Zakiyah', 'ibu_tahun_lahir' => 1984, 'ibu_pekerjaan' => 'Ibu Rumah Tangga', 'ibu_penghasilan' => 'Kurang dari Rp 1.000.000',
                    'wali_nama' => 'H. Suparman', 'wali_hubungan' => 'Kakek', 'wali_pekerjaan' => 'Pensiunan', 'wali_penghasilan' => 'Rp 1.000.000 - Rp 3.000.000',
                ],
                '_siblings' => [
                    ['nama' => 'Arifa Firmansyah', 'tanggal_lahir' => '2016-04-13'],
                ],
            ],
            [
                'nisn' => '0051234020', 'nama' => 'Salma Khairunnisa', 'nipd' => '10231020',
                'jk' => 'P', 'tempat_lahir' => 'Karanganyar', 'tanggal_lahir' => '2014-07-11',
                'nik' => '3313151107140020', 'no_kk' => '3313150101080020', 'agama' => 'Islam',
                'unit' => 'PPTQ Ulil Albab Gondangrejo', 'program' => 'Boarding',
                'no_wa' => '087890123020', 'sekolah_asal' => 'TK Aisyiyah Colomadu',
                'jalan' => 'Jl. Mangesti Luhur No. 4', 'rt_rw' => 'RT 03 / RW 07', 'dusun' => 'Gawanan',
                'desa' => 'Colomadu', 'kecamatan' => 'Colomadu', 'kabupaten' => 'Karanganyar', 'provinsi' => 'Jawa Tengah',
                '_academic' => [
                    'jenjang' => 'SMP', 'tingkat' => 9, 'kelas' => '9-Tahfidz', 'student_status' => 'aktif',
                    'desil' => 6, 'status_pip' => false, 'pip_keterangan' => '', 'status_kip' => false, 'no_kip' => '',
                ],
                '_family' => [
                    'ayah_nama' => 'Khairul Anwar', 'ayah_tahun_lahir' => 1983, 'ayah_pekerjaan' => 'Guru', 'ayah_penghasilan' => 'Rp 3.000.000 - Rp 5.000.000',
                    'ibu_nama' => 'Nisa Khairiyah', 'ibu_tahun_lahir' => 1986, 'ibu_pekerjaan' => 'Bidan', 'ibu_penghasilan' => 'Rp 3.000.000 - Rp 5.000.000',
                ],
                '_siblings' => [
                    ['nama' => 'Salim Khairul', 'tanggal_lahir' => '2018-06-15'],
                ],
            ],
        ];

        $previousYear = AcademicYear::where('name', '2024/2025')->where('semester', 'Genap')->first();

        foreach ($students as $index => $data) {
            $academicData = $data['_academic'] ?? [];
            $familyData = $data['_family'] ?? [];
            $siblingsData = $data['_siblings'] ?? [];

            unset($data['_academic'], $data['_family'], $data['_siblings']);

            $student = Student::create($data);

            // Create academic record linked to active TA (2025/2026 Ganjil)
            if (! empty($academicData)) {
                $kelasName = $academicData['kelas'];
                unset($academicData['kelas']);

                // Look up the classroom by unit + name
                $classroom = Classroom::where('academic_year_id', $academicYear->id)
                    ->where('unit', $student->unit)
                    ->where('name', $kelasName)
                    ->first();

                $student->academicRecords()->create(array_merge($academicData, [
                    'academic_year_id' => $academicYear->id,
                    'classroom_id' => $classroom?->id,
                ]));

                // For the first 10 students, also seed their record in the previous TA (2024/2025 Genap)
                if ($previousYear && $index < 10) {
                    $prevGrade = max(1, $academicData['tingkat'] - 1);
                    $prevKelasName = $prevGrade.'A';
                    $prevClassroom = Classroom::where('academic_year_id', $previousYear->id)
                        ->where('unit', $student->unit)
                        ->where('name', $prevKelasName)
                        ->first();

                    $student->academicRecords()->create([
                        'academic_year_id' => $previousYear->id,
                        'classroom_id' => $prevClassroom?->id,
                        'jenjang' => $academicData['jenjang'],
                        'tingkat' => $prevGrade,
                        'student_status' => 'aktif',
                        'desil' => $academicData['desil'] ?? null,
                        'status_pip' => $academicData['status_pip'] ?? false,
                        'pip_keterangan' => $academicData['pip_keterangan'] ?? null,
                        'status_kip' => $academicData['status_kip'] ?? false,
                        'no_kip' => $academicData['no_kip'] ?? null,
                    ]);
                }
            }

            if (! empty($familyData)) {
                $student->family()->create($familyData);
            }

            foreach ($siblingsData as $sibling) {
                $student->siblings()->create($sibling);
            }
        }
    }
}
