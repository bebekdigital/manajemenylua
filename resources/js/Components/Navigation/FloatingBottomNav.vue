<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { usePendingApprovals } from '@/Composables/usePendingApprovals';

const page = usePage();

const { pendingApprovalsCount } = usePendingApprovals();

const currentPath = computed(() => page.url.split('?')[0]);

/**
 * Deteksi apakah halaman saat ini berada dalam Section tertentu atau subhalaman miliknya.
 */
const activeSection = computed(() => {
    const path = currentPath.value;

    if (
        path.startsWith('/section/database-siswa') ||
        path.startsWith('/students') ||
        path === '/statistics' ||
        path.startsWith('/students/statistics')
    ) {
        return 'database-siswa';
    }

    if (
        path.startsWith('/section/database-pegawai') ||
        path.startsWith('/staff')
    ) {
        return 'database-pegawai';
    }

    if (
        path.startsWith('/section/administrasi-aset') ||
        path.startsWith('/administration')
    ) {
        return 'administrasi-aset';
    }

    if (
        path.startsWith('/section/akademik') ||
        path.startsWith('/pembelajaran')
    ) {
        return 'akademik';
    }

    return null;
});

/**
 * Daftar navigasi floating bottom yang dinamis:
 * - Jika di dalam Section: berisi Submenu Section tersebut + Beranda
 * - Jika di luar Section (Beranda / Utama): berisi menu utama (Beranda, Agenda, Info, Persetujuan)
 */
const navItems = computed(() => {
    const path = currentPath.value;
    const section = activeSection.value;

    if (section === 'database-siswa') {
        return [
            {
                label: 'Beranda',
                href: '/beranda',
                icon: 'grid_view',
                active: path === '/' || path === '/beranda',
                external: false,
            },
            {
                label: 'Statistik Siswa',
                href: '/statistics',
                icon: 'bar_chart',
                active: path === '/statistics' || path.startsWith('/students/statistics'),
                external: false,
            },
            {
                label: 'Data Siswa',
                href: '/students',
                icon: 'groups',
                active: path.startsWith('/students') && !path.startsWith('/students/statistics'),
                external: false,
            },
        ];
    }

    if (section === 'database-pegawai') {
        return [
            {
                label: 'Beranda',
                href: '/beranda',
                icon: 'grid_view',
                active: path === '/' || path === '/beranda',
                external: false,
            },
            {
                label: 'Statistik Pegawai',
                href: '/staff/statistics',
                icon: 'query_stats',
                active: path.startsWith('/staff/statistics'),
                external: false,
            },
            {
                label: 'Data Pegawai',
                href: '/staff',
                icon: 'badge',
                active: path.startsWith('/staff') && !path.startsWith('/staff/statistics'),
                external: false,
            },
        ];
    }

    if (section === 'administrasi-aset') {
        return [
            {
                label: 'Beranda',
                href: '/beranda',
                icon: 'grid_view',
                active: path === '/' || path === '/beranda',
                external: false,
            },
            {
                label: 'Administrasi',
                href: '/administration',
                icon: 'description',
                active: path.startsWith('/administration'),
                external: false,
            },
            {
                label: 'Aset',
                href: 'https://assetulil.com',
                icon: 'inventory_2',
                active: false,
                external: true,
            },
        ];
    }

    if (section === 'akademik') {
        return [
            {
                label: 'Beranda',
                href: '/beranda',
                icon: 'grid_view',
                active: path === '/' || path === '/beranda',
                external: false,
            },
            {
                label: 'Pembelajaran',
                href: '/pembelajaran/kelas',
                icon: 'menu_book',
                active: path.startsWith('/pembelajaran'),
                external: false,
            },
            {
                label: 'Kesiswaan',
                href: '#',
                icon: 'diversity_3',
                active: false,
                external: false,
            },
        ];
    }

    // Default Navigation (Beranda, Agenda, Info, Persetujuan)
    return [
        {
            label: 'Beranda',
            href: '/beranda',
            icon: 'grid_view',
            active: path === '/' || path.startsWith('/beranda'),
            external: false,
        },
        {
            label: 'Agenda',
            href: '/agenda',
            icon: 'calendar_month',
            active: path.startsWith('/agenda'),
            external: false,
        },
        {
            label: 'Info',
            href: '/info',
            icon: 'campaign',
            active: path.startsWith('/info'),
            external: false,
        },
        {
            label: 'Persetujuan',
            href: '/persetujuan',
            icon: 'task_alt',
            active: path.startsWith('/persetujuan'),
            badge: pendingApprovalsCount.value,
            external: false,
        },
    ];
});
</script>

<template>
    <!-- Floating Bottom Navigation Bar (Memanjang Full Rounded dengan Konteks Submenu Otomatis) -->
    <nav
        class="md:hidden fixed bottom-3.5 inset-x-3.5 sm:left-1/2 sm:-translate-x-1/2 sm:w-[460px] z-40 select-none pointer-events-none"
        aria-label="Navigasi Bawah"
    >
        <div
            class="pointer-events-auto bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-full shadow-[0_10px_30px_rgba(15,23,42,0.15)] px-2 py-1.5 flex items-center justify-around gap-1 safe-area-bottom"
        >
            <template v-for="item in navItems" :key="item.label">
                <!-- External Link (misal: Aset) -->
                <a
                    v-if="item.external"
                    :href="item.href"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex flex-col items-center justify-center flex-1 py-1 transition-all duration-200 relative group cursor-pointer text-slate-500 hover:text-emerald-900"
                >
                    <div class="flex items-center justify-center px-3.5 py-1 rounded-full transition-all duration-200 bg-transparent">
                        <span class="material-symbols-outlined text-[21px] transition-transform duration-200 group-active:scale-90">
                            {{ item.icon }}
                        </span>
                    </div>
                    <span class="text-[10px] mt-0.5 leading-tight truncate max-w-[85px]">
                        {{ item.label }}
                    </span>
                </a>

                <!-- Internal Link -->
                <Link
                    v-else
                    :href="item.href"
                    class="flex flex-col items-center justify-center flex-1 py-1 transition-all duration-200 relative group cursor-pointer"
                    :class="item.active ? 'text-emerald-950 font-bold' : 'text-slate-500 hover:text-emerald-900'"
                >
                    <div
                        class="flex items-center justify-center px-3.5 py-1 rounded-full transition-all duration-200 relative"
                        :class="item.active ? 'bg-emerald-100' : 'bg-transparent'"
                    >
                        <span
                            class="material-symbols-outlined text-[21px] transition-transform duration-200 group-active:scale-90"
                            :class="{ 'filled-icon': item.active }"
                        >
                            {{ item.icon }}
                        </span>

                        <!-- Badge Realtime Pengajuan Pending jika ada -->
                        <span
                            v-if="item.badge && item.badge > 0"
                            class="absolute -top-1 -right-0.5 w-[17px] h-[17px] min-w-[17px] min-h-[17px] aspect-square rounded-full text-[10px] font-bold bg-rose-600 text-white flex items-center justify-center shadow-xs ring-2 ring-white"
                        >
                            {{ item.badge }}
                        </span>
                    </div>
                    <span class="text-[10px] mt-0.5 leading-tight truncate max-w-[85px]">
                        {{ item.label }}
                    </span>
                </Link>
            </template>
        </div>
    </nav>
</template>

<style scoped>
.safe-area-bottom {
    padding-bottom: max(0.375rem, env(safe-area-inset-bottom, 0px));
}
.filled-icon {
    font-variation-settings: 'FILL' 1;
}
</style>

