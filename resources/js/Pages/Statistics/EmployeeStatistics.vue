<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    metrics: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            inactive: 0,
            male: 0,
            female: 0,
        }),
    },
    unitStats: {
        type: Array,
        default: () => [],
    },
    employmentTypeStats: {
        type: Array,
        default: () => [],
    },
});

const maxUnitTotal = computed(() => {
    if (!props.unitStats.length) return 1;
    return Math.max(...props.unitStats.map(item => item.total), 1);
});

const maxTypeTotal = computed(() => {
    if (!props.employmentTypeStats.length) return 1;
    return Math.max(...props.employmentTypeStats.map(item => item.total), 1);
});
</script>

<template>
    <div class="flex-grow max-w-6xl mx-auto w-full px-4 py-5 md:py-8">
        <Head title="Statistik Pegawai - Yayasan Li Ulil Albab" />

        <!-- Navigation Bar Atas: Tombol Kembali -->
        <div class="mb-5 flex items-center justify-between">
            <Link
                href="/section/database-pegawai"
                class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-teal-900 px-3 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200/80 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-800"
            >
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                <span>Kembali ke Database Pegawai</span>
            </Link>

            <span class="text-xs font-medium text-slate-400">
                Data Kepegawaian Realtime
            </span>
        </div>

        <!-- Header Card -->
        <div class="bg-white rounded-3xl border border-slate-200/90 p-5 sm:p-7 shadow-xs mb-6">
            <div class="flex items-start gap-4">
                <div class="w-13 h-13 rounded-2xl bg-teal-50 border border-teal-200 flex items-center justify-center text-teal-800 shrink-0 shadow-2xs">
                    <span class="material-symbols-outlined text-[28px]">query_stats</span>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="inline-flex items-center text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-teal-50 text-teal-800 border border-teal-200">
                        Database Pegawai
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
                        Statistik Pegawai
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-600 mt-1">
                        Ringkasan jumlah pendidik dan tenaga kependidikan per unit kerja dan status kepegawaian.
                    </p>
                </div>
            </div>

            <!-- 4 Metric Cards Ringkas -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-5 border-t border-slate-100">
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Pegawai</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ metrics.total }}</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">Semua data terdaftar</p>
                </div>

                <div class="p-3.5 rounded-2xl bg-emerald-50/70 border border-emerald-200/80">
                    <p class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider">Pegawai Aktif</p>
                    <p class="text-2xl font-extrabold text-emerald-950 mt-1">{{ metrics.active }}</p>
                    <p class="text-[11px] text-emerald-700 mt-0.5">Status aktif bertugas</p>
                </div>

                <div class="p-3.5 rounded-2xl bg-sky-50/70 border border-sky-200/80">
                    <p class="text-[11px] font-bold text-sky-800 uppercase tracking-wider">Laki-laki</p>
                    <p class="text-2xl font-extrabold text-sky-950 mt-1">{{ metrics.male }}</p>
                    <p class="text-[11px] text-sky-700 mt-0.5">Pegawai putra</p>
                </div>

                <div class="p-3.5 rounded-2xl bg-rose-50/70 border border-rose-200/80">
                    <p class="text-[11px] font-bold text-rose-800 uppercase tracking-wider">Perempuan</p>
                    <p class="text-2xl font-extrabold text-rose-950 mt-1">{{ metrics.female }}</p>
                    <p class="text-[11px] text-rose-700 mt-0.5">Pegawai putri</p>
                </div>
            </div>
        </div>

        <!-- 2 Kolom Rincian: Unit Kerja & Jenis Kepegawaian -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Rincian Unit Kerja -->
            <div class="bg-white rounded-3xl border border-slate-200/90 p-5 sm:p-6 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-teal-700">domain</span>
                        <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider">
                            Pegawai per Unit Kerja
                        </h2>
                    </div>
                    <span class="text-xs text-slate-400 font-medium">{{ unitStats.length }} Unit</span>
                </div>

                <div v-if="unitStats.length === 0" class="py-8 text-center text-xs text-slate-400">
                    Belum ada data unit kerja tersimpan.
                </div>

                <div v-else class="space-y-3.5">
                    <div
                        v-for="item in unitStats"
                        :key="item.unit"
                        class="space-y-1.5"
                    >
                        <div class="flex items-center justify-between text-xs font-semibold">
                            <span class="text-slate-800 truncate">{{ item.unit }}</span>
                            <span class="text-teal-900 font-bold shrink-0 ml-2">{{ item.total }} orang</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                            <div
                                class="bg-teal-600 h-2.5 rounded-full transition-all duration-500"
                                :style="{ width: `${Math.max(6, Math.round((item.total / maxUnitTotal) * 100))}%` }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rincian Status / Jenis Kepegawaian -->
            <div class="bg-white rounded-3xl border border-slate-200/90 p-5 sm:p-6 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-emerald-700">work</span>
                        <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider">
                            Jenis Kepegawaian
                        </h2>
                    </div>
                    <span class="text-xs text-slate-400 font-medium">{{ employmentTypeStats.length }} Kategori</span>
                </div>

                <div v-if="employmentTypeStats.length === 0" class="py-8 text-center text-xs text-slate-400">
                    Belum ada data jenis kepegawaian tersimpan.
                </div>

                <div v-else class="space-y-3.5">
                    <div
                        v-for="item in employmentTypeStats"
                        :key="item.type"
                        class="space-y-1.5"
                    >
                        <div class="flex items-center justify-between text-xs font-semibold">
                            <span class="text-slate-800 truncate">{{ item.type }}</span>
                            <span class="text-emerald-900 font-bold shrink-0 ml-2">{{ item.total }} orang</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                            <div
                                class="bg-emerald-600 h-2.5 rounded-full transition-all duration-500"
                                :style="{ width: `${Math.max(6, Math.round((item.total / maxTypeTotal) * 100))}%` }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

