<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import HeroBannerCard from '@/Components/Dashboard/HeroBannerCard.vue';
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
    <div class="flex-grow max-w-container-max mx-auto w-full bg-white md:p-margin-desktop">
        <Head title="Beranda Utama - Yayasan Li Ulil Albab" />

        <!-- Hero Ilustrasi Gedung (SDIT / SMPIT & PPTQ) + Karakter 3D Clay + Sapaan + 2 Agenda Terdekat -->
        <!-- Mobile: Full-bleed menyatu sampai ke topbar | Desktop: Dibungkus kotak rounded di bawah topbar -->
        <div class="md:mb-6">
            <HeroBannerCard />
        </div>

        <!-- Konten Utama Bawah Hero (Pada Mobile: melengkung ke atas seperti sheet aplikasi modern) -->
        <div class="relative z-20 bg-white rounded-t-[26px] md:rounded-none -mt-5 md:mt-0 px-4 pt-5 pb-6 md:p-0">
            <!-- Mobile Navigation Menu Icons: Menu Section Layanan -->
            <div class="md:hidden mb-6 bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
                <div class="flex items-center justify-between mb-3.5 px-1">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-emerald-600"></div>
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Kategori Menu</h3>
                    </div>
                    <span class="text-[11px] text-slate-400 font-medium">Pilih Section</span>
                </div>

                <div class="grid grid-cols-4 sm:grid-cols-5 gap-y-4 gap-x-2">
                    <!-- 1. Section: Database Siswa (Langsung ke Data Siswa) -->
                    <Link
                        href="/students"
                        class="flex flex-col items-center group cursor-pointer text-center"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200/80 flex items-center justify-center text-emerald-800 group-hover:bg-emerald-100 group-active:scale-95 transition-all shadow-2xs">
                            <span class="material-symbols-outlined text-[24px]">groups</span>
                        </div>
                        <span class="text-[11px] font-semibold text-slate-700 mt-1.5 leading-tight group-hover:text-emerald-950">Database Siswa</span>
                    </Link>

                    <!-- 2. Section: Database Pegawai (Langsung ke Data Pegawai) -->
                    <Link
                        v-if="canViewEmployee"
                        href="/staff"
                        class="flex flex-col items-center group cursor-pointer text-center"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-teal-50 border border-teal-200/80 flex items-center justify-center text-teal-800 group-hover:bg-teal-100 group-active:scale-95 transition-all shadow-2xs">
                            <span class="material-symbols-outlined text-[24px]">badge</span>
                        </div>
                        <span class="text-[11px] font-semibold text-slate-700 mt-1.5 leading-tight group-hover:text-teal-950">Database Pegawai</span>
                    </Link>

                    <!-- 3. Section: Administrasi & Aset (Langsung ke Administrasi) -->
                    <Link
                        href="/administration"
                        class="flex flex-col items-center group cursor-pointer text-center"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-sky-50 border border-sky-200/80 flex items-center justify-center text-sky-800 group-hover:bg-sky-100 group-active:scale-95 transition-all shadow-2xs">
                            <span class="material-symbols-outlined text-[24px]">folder_shared</span>
                        </div>
                        <span class="text-[11px] font-semibold text-slate-700 mt-1.5 leading-tight group-hover:text-sky-950">Administrasi &amp; Aset</span>
                    </Link>

                    <!-- 4. Section: Akademik (Langsung ke Pembelajaran) -->
                    <Link
                        href="/pembelajaran/kelas"
                        class="flex flex-col items-center group cursor-pointer text-center"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-800 group-hover:bg-amber-100 group-active:scale-95 transition-all shadow-2xs">
                            <span class="material-symbols-outlined text-[24px]">menu_book</span>
                        </div>
                        <span class="text-[11px] font-semibold text-slate-700 mt-1.5 leading-tight group-hover:text-amber-950">Akademik</span>
                    </Link>

                    <!-- 5. Pengaturan Sistem -->
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

            <!-- Row: Pengumuman + Statistik + Diagram -->
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
    </div>
</template>
