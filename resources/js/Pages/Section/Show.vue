<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
});

defineProps({
    section: {
        type: Object,
        required: true,
    },
});

const colorStyles = {
    emerald: {
        badgeBg: 'bg-emerald-50 text-emerald-800 border-emerald-200',
        iconBg: 'bg-emerald-100 text-emerald-900 border-emerald-200',
        ring: 'focus-visible:ring-emerald-700',
        hoverBorder: 'hover:border-emerald-300',
        arrowBg: 'group-hover:bg-emerald-700 group-hover:text-white',
    },
    teal: {
        badgeBg: 'bg-teal-50 text-teal-800 border-teal-200',
        iconBg: 'bg-teal-100 text-teal-900 border-teal-200',
        ring: 'focus-visible:ring-teal-700',
        hoverBorder: 'hover:border-teal-300',
        arrowBg: 'group-hover:bg-teal-700 group-hover:text-white',
    },
    sky: {
        badgeBg: 'bg-sky-50 text-sky-800 border-sky-200',
        iconBg: 'bg-sky-100 text-sky-900 border-sky-200',
        ring: 'focus-visible:ring-sky-700',
        hoverBorder: 'hover:border-sky-300',
        arrowBg: 'group-hover:bg-sky-700 group-hover:text-white',
    },
    amber: {
        badgeBg: 'bg-amber-50 text-amber-800 border-amber-200',
        iconBg: 'bg-amber-100 text-amber-900 border-amber-200',
        ring: 'focus-visible:ring-amber-700',
        hoverBorder: 'hover:border-amber-300',
        arrowBg: 'group-hover:bg-amber-700 group-hover:text-white',
    },
};
</script>

<template>
    <div class="flex-grow max-w-4xl mx-auto w-full p-4 md:p-margin-desktop">
        <Head :title="`${section.title} - Yayasan Li Ulil Albab`" />

        <!-- Navigation Bar Atas: Tombol Kembali ke Beranda -->
        <div class="mb-5 flex items-center justify-between">
            <Link
                href="/beranda"
                class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-emerald-900 px-3 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200/80 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-800"
            >
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                <span>Kembali ke Beranda</span>
            </Link>

            <span class="text-xs font-medium text-slate-400">
                Pilih Submenu
            </span>
        </div>

        <!-- Header Card Section -->
        <div class="bg-white rounded-3xl border border-slate-200/90 p-5 sm:p-7 shadow-xs mb-6">
            <div class="flex items-start gap-4">
                <div
                    :class="[
                        'w-14 h-14 rounded-2xl flex items-center justify-center border shrink-0 shadow-2xs',
                        colorStyles[section.color]?.iconBg || 'bg-slate-100 text-slate-800 border-slate-200'
                    ]"
                >
                    <span class="material-symbols-outlined text-[30px]">{{ section.icon }}</span>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span
                            :class="[
                                'inline-flex items-center text-[11px] font-bold px-2.5 py-0.5 rounded-full border',
                                colorStyles[section.color]?.badgeBg || 'bg-slate-100 text-slate-700 border-slate-200'
                            ]"
                        >
                            Kategori Section
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
                        {{ section.title }}
                    </h1>

                    <p class="text-xs sm:text-sm text-slate-600 mt-1.5 leading-relaxed">
                        {{ section.subtitle }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Daftar Pilihan Submenu (Cards Besar & Tactile) -->
        <div class="space-y-4">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider px-1">
                Submenu Tersedia ({{ section.submenus.length }})
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <template v-for="menu in section.submenus" :key="menu.title">
                    <!-- External Link -->
                    <a
                        v-if="menu.external"
                        :href="menu.href"
                        target="_blank"
                        rel="noopener noreferrer"
                        :class="[
                            'group relative bg-white rounded-2xl border border-slate-200 p-5 shadow-xs transition-all duration-200 hover:shadow-md cursor-pointer flex flex-col justify-between focus-visible:outline-none focus-visible:ring-2',
                            colorStyles[section.color]?.ring || 'focus-visible:ring-slate-800',
                            colorStyles[section.color]?.hoverBorder || 'hover:border-slate-300'
                        ]"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <div
                                    :class="[
                                        'w-11 h-11 rounded-xl flex items-center justify-center border shrink-0 transition-transform group-hover:scale-105',
                                        colorStyles[section.color]?.iconBg || 'bg-slate-100 text-slate-800 border-slate-200'
                                    ]"
                                >
                                    <span class="material-symbols-outlined text-[24px]">{{ menu.icon }}</span>
                                </div>
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                                    {{ menu.badge }}
                                </span>
                            </div>

                            <h3 class="text-base font-bold text-slate-900 group-hover:text-emerald-950 transition-colors">
                                {{ menu.title }}
                            </h3>

                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                {{ menu.subtitle }}
                            </p>
                        </div>

                        <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-slate-700">
                            <span>Buka Portal</span>
                            <span
                                :class="[
                                    'w-7 h-7 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center transition-colors',
                                    colorStyles[section.color]?.arrowBg || 'group-hover:bg-slate-800 group-hover:text-white'
                                ]"
                            >
                                <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                            </span>
                        </div>
                    </a>

                    <!-- Internal Inertia Link -->
                    <Link
                        v-else
                        :href="menu.href"
                        :class="[
                            'group relative bg-white rounded-2xl border border-slate-200 p-5 shadow-xs transition-all duration-200 hover:shadow-md cursor-pointer flex flex-col justify-between focus-visible:outline-none focus-visible:ring-2',
                            colorStyles[section.color]?.ring || 'focus-visible:ring-slate-800',
                            colorStyles[section.color]?.hoverBorder || 'hover:border-slate-300'
                        ]"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <div
                                    :class="[
                                        'w-11 h-11 rounded-xl flex items-center justify-center border shrink-0 transition-transform group-hover:scale-105',
                                        colorStyles[section.color]?.iconBg || 'bg-slate-100 text-slate-800 border-slate-200'
                                    ]"
                                >
                                    <span class="material-symbols-outlined text-[24px]">{{ menu.icon }}</span>
                                </div>
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                                    {{ menu.badge }}
                                </span>
                            </div>

                            <h3 class="text-base font-bold text-slate-900 group-hover:text-emerald-950 transition-colors">
                                {{ menu.title }}
                            </h3>

                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                {{ menu.subtitle }}
                            </p>
                        </div>

                        <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-slate-700">
                            <span>Akses Halaman</span>
                            <span
                                :class="[
                                    'w-7 h-7 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center transition-colors',
                                    colorStyles[section.color]?.arrowBg || 'group-hover:bg-slate-800 group-hover:text-white'
                                ]"
                            >
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </span>
                        </div>
                    </Link>
                </template>
            </div>
        </div>
    </div>
</template>

