<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AgendaCard from '@/Components/Dashboard/AgendaCard.vue';
import KpiCard from '@/Components/Dashboard/KpiCard.vue';
import AnnouncementCard from '@/Components/Dashboard/AnnouncementCard.vue';
import StatistikCard from '@/Components/Dashboard/StatistikCard.vue';
import DiagramCard from '@/Components/Dashboard/DiagramCard.vue';

defineOptions({
    layout: AppLayout,
});

const page = usePage();
const canViewEmployee = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return false;
    if (user.role === 'superadmin') return true;
    return Boolean(page.props.auth?.permissions?.can_view_employee ?? user.can_view_employee ?? false);
});
</script>

<template>
    <div class="flex-grow p-gutter md:p-margin-desktop max-w-container-max mx-auto w-full bg-white">
        <Head title="Beranda Utama - Yayasan Li Ulil Albab" />

        <!-- Row 1: Agenda + KPI (swipeable on mobile) -->
        <div class="flex lg:grid lg:grid-cols-3 gap-4 lg:gap-6 mb-6 overflow-x-auto snap-x snap-mandatory lg:overflow-visible no-scrollbar">
            <div class="lg:col-span-2 w-[85vw] sm:w-auto lg:w-auto snap-start shrink-0 ml-0">
                <AgendaCard />
            </div>
            <div class="lg:col-span-1 w-[85vw] sm:w-auto lg:w-auto snap-start shrink-0 mr-4 lg:mr-0">
                <KpiCard />
            </div>
        </div>

        <!-- Mobile Navigation Menu Icons: Sidebar items moved below Agenda on Mobile -->
        <div class="md:hidden mb-6 bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
            <div class="flex items-center justify-between mb-3.5 px-1">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-emerald-600"></div>
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Menu Layanan</h3>
                </div>
                <span class="text-[11px] text-slate-400 font-medium">Akses Cepat</span>
            </div>

            <div class="grid grid-cols-4 gap-y-4 gap-x-2">
                <!-- 1. Data Siswa -->
                <Link
                    href="/students"
                    class="flex flex-col items-center group cursor-pointer text-center"
                >
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200/80 flex items-center justify-center text-emerald-800 group-hover:bg-emerald-100 group-active:scale-95 transition-all shadow-2xs">
                        <span class="material-symbols-outlined text-[24px]">groups</span>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-700 mt-1.5 leading-tight group-hover:text-emerald-950">Data Siswa</span>
                </Link>

                <!-- 2. Data Pegawai -->
                <Link
                    v-if="canViewEmployee"
                    href="/staff"
                    class="flex flex-col items-center group cursor-pointer text-center"
                >
                    <div class="w-12 h-12 rounded-2xl bg-teal-50 border border-teal-200/80 flex items-center justify-center text-teal-800 group-hover:bg-teal-100 group-active:scale-95 transition-all shadow-2xs">
                        <span class="material-symbols-outlined text-[24px]">badge</span>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-700 mt-1.5 leading-tight group-hover:text-teal-950">Data Pegawai</span>
                </Link>

                <!-- 3. Administrasi -->
                <Link
                    href="/administration"
                    class="flex flex-col items-center group cursor-pointer text-center"
                >
                    <div class="w-12 h-12 rounded-2xl bg-sky-50 border border-sky-200/80 flex items-center justify-center text-sky-800 group-hover:bg-sky-100 group-active:scale-95 transition-all shadow-2xs">
                        <span class="material-symbols-outlined text-[24px]">description</span>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-700 mt-1.5 leading-tight group-hover:text-sky-950">Administrasi</span>
                </Link>

                <!-- 4. Statistik -->
                <Link
                    href="/statistics"
                    class="flex flex-col items-center group cursor-pointer text-center"
                >
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-200/80 flex items-center justify-center text-indigo-800 group-hover:bg-indigo-100 group-active:scale-95 transition-all shadow-2xs">
                        <span class="material-symbols-outlined text-[24px]">bar_chart</span>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-700 mt-1.5 leading-tight group-hover:text-indigo-950">Statistik</span>
                </Link>

                <!-- 5. Pembelajaran -->
                <Link
                    href="/pembelajaran/kelas"
                    class="flex flex-col items-center group cursor-pointer text-center"
                >
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-800 group-hover:bg-amber-100 group-active:scale-95 transition-all shadow-2xs">
                        <span class="material-symbols-outlined text-[24px]">menu_book</span>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-700 mt-1.5 leading-tight group-hover:text-amber-950">Pembelajaran</span>
                </Link>

                <!-- 6. Kesiswaan -->
                <Link
                    href="#"
                    class="flex flex-col items-center group cursor-pointer text-center"
                >
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 border border-purple-200/80 flex items-center justify-center text-purple-800 group-hover:bg-purple-100 group-active:scale-95 transition-all shadow-2xs">
                        <span class="material-symbols-outlined text-[24px]">diversity_3</span>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-700 mt-1.5 leading-tight group-hover:text-purple-950">Kesiswaan</span>
                </Link>

                <!-- 7. Aset -->
                <a
                    href="https://assetulil.com"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex flex-col items-center group cursor-pointer text-center"
                >
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-200/80 flex items-center justify-center text-rose-800 group-hover:bg-rose-100 group-active:scale-95 transition-all shadow-2xs">
                        <span class="material-symbols-outlined text-[24px]">inventory_2</span>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-700 mt-1.5 leading-tight group-hover:text-rose-950">Aset</span>
                </a>

                <!-- 8. Pengaturan -->
                <Link
                    href="/portal"
                    class="flex flex-col items-center group cursor-pointer text-center"
                >
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-700 group-hover:bg-slate-200 group-active:scale-95 transition-all shadow-2xs">
                        <span class="material-symbols-outlined text-[24px]">settings</span>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-700 mt-1.5 leading-tight group-hover:text-slate-950">Pengaturan</span>
                </Link>
            </div>
        </div>

        <!-- Row 2: Pengumuman + Statistik + Diagram -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-1">
                <AnnouncementCard />
            </div>
            <div class="md:col-span-1">
                <StatistikCard />
            </div>
            <div class="md:col-span-1">
                <DiagramCard />
            </div>
        </div>
    </div>
</template>

<style scoped>
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>
