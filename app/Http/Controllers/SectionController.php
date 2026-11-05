<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    /**
     * Map of section categories with their metadata and submenus.
     */
    protected array $sections = [
        'database-siswa' => [
            'slug' => 'database-siswa',
            'title' => 'Database Siswa',
            'subtitle' => 'Pusat data induk siswa, mapping kelas, dan grafik statistik akademik.',
            'icon' => 'groups',
            'color' => 'emerald',
            'submenus' => [
                [
                    'title' => 'Statistik Siswa',
                    'subtitle' => 'Grafik dan rekapitulasi jumlah siswa aktif per jenjang dan rombel kelas.',
                    'icon' => 'bar_chart',
                    'href' => '/statistics',
                    'badge' => 'Statistik',
                    'external' => false,
                ],
                [
                    'title' => 'Data Siswa',
                    'subtitle' => 'Direktori lengkap data induk santri, profil siswa, dan mapping kelas.',
                    'icon' => 'groups',
                    'href' => '/students',
                    'badge' => 'Direktori',
                    'external' => false,
                ],
            ],
        ],
        'database-pegawai' => [
            'slug' => 'database-pegawai',
            'title' => 'Database Pegawai',
            'subtitle' => 'Manajemen data kepegawaian, arsip berkas, perizinan, dan statistik pegawai.',
            'icon' => 'badge',
            'color' => 'teal',
            'submenus' => [
                [
                    'title' => 'Statistik Pegawai',
                    'subtitle' => 'Rekapitulasi jumlah pendidik dan staf berdasarkan unit kerja dan status.',
                    'icon' => 'query_stats',
                    'href' => '/staff/statistics',
                    'badge' => 'Statistik',
                    'external' => false,
                ],
                [
                    'title' => 'Data Pegawai',
                    'subtitle' => 'Daftar data induk pegawai, riwayat jabatan, berkas SK, dan perizinan.',
                    'icon' => 'badge',
                    'href' => '/staff',
                    'badge' => 'Direktori',
                    'external' => false,
                ],
            ],
        ],
        'administrasi-aset' => [
            'slug' => 'administrasi-aset',
            'title' => 'Administrasi & Aset',
            'subtitle' => 'Layanan cetak berkas dokumen siswa serta portal inventaris aset yayasan.',
            'icon' => 'folder_shared',
            'color' => 'sky',
            'submenus' => [
                [
                    'title' => 'Administrasi',
                    'subtitle' => 'Cetak dokumen identitas siswa, pasfoto, dan kelengkapan arsip madrasah.',
                    'icon' => 'description',
                    'href' => '/administration',
                    'badge' => 'Cetak Berkas',
                    'external' => false,
                ],
                [
                    'title' => 'Aset Yayasan',
                    'subtitle' => 'Portal inventaris dan pengelolaan sarana prasarana aset yayasan.',
                    'icon' => 'inventory_2',
                    'href' => 'https://assetulil.com',
                    'badge' => 'Portal Eksternal',
                    'external' => true,
                ],
            ],
        ],
        'akademik' => [
            'slug' => 'akademik',
            'title' => 'Akademik',
            'subtitle' => 'Pengelolaan rombongan belajar kelas, pembelajaran, dan aktivitas kesiswaan.',
            'icon' => 'menu_book',
            'color' => 'amber',
            'submenus' => [
                [
                    'title' => 'Pembelajaran',
                    'subtitle' => 'Manajemen rombongan belajar kelas dan pembagian santri/siswa aktif.',
                    'icon' => 'menu_book',
                    'href' => '/pembelajaran/kelas',
                    'badge' => 'Rombel Aktif',
                    'external' => false,
                ],
                [
                    'title' => 'Kesiswaan',
                    'subtitle' => 'Program pembinaan santri, ekstrakurikuler, dan kedisiplinan.',
                    'icon' => 'diversity_3',
                    'href' => '#',
                    'badge' => 'Segera Hadir',
                    'external' => false,
                ],
            ],
        ],
    ];

    /**
     * Redirect the section request directly to its primary submenu.
     */
    public function show(Request $request, string $slug)
    {
        $primarySubmenuRoutes = [
            'database-siswa' => '/students',
            'database-pegawai' => '/staff',
            'administrasi-aset' => '/administration',
            'akademik' => '/pembelajaran/kelas',
        ];

        if (! isset($primarySubmenuRoutes[$slug])) {
            abort(404, 'Kategori menu tidak ditemukan.');
        }

        // Validasi akses khusus Database Pegawai
        if ($slug === 'database-pegawai') {
            $user = $request->user();
            $canView = $user?->role === 'superadmin'
                || RolePermission::canAccess($user, 'employee_directory', 'view')
                || (bool) ($user?->can_view_employee ?? false);

            if (! $canView) {
                abort(403, 'Akses ditolak: Anda tidak memiliki izin untuk melihat database pegawai.');
            }
        }

        return redirect($primarySubmenuRoutes[$slug]);
    }
}
