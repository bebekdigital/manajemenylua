<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
});

const modules = [
    {
        id: 'identity-document',
        title: 'Berkas Identitas Siswa',
        description: 'Cetak Cover Rapor, Profil Sekolah, dan Lembar Identitas Peserta Didik secara massal sesuai format resmi sekolah.',
        icon: 'contact_page',
        href: '/administration/identity-document',
        status: 'available',
        badge: 'Tersedia',
        features: ['Cetak massal seluruh kelas', 'Template yang dapat diubah', 'Preview sebelum cetak'],
        gradient: 'from-primary/10 to-primary/5',
        iconBg: 'bg-primary-container',
        iconColor: 'text-on-primary-container',
        accentColor: 'bg-primary',
    },
    {
        id: 'letters',
        title: 'Surat Keterangan Siswa',
        description: 'Pembuatan surat keterangan aktif, pindah sekolah, dan kelulusan untuk peserta didik.',
        icon: 'mail',
        href: null,
        status: 'coming_soon',
        badge: 'Segera Hadir',
        features: ['Surat aktif sekolah', 'Surat pindah', 'Surat kelulusan'],
        gradient: 'from-secondary/10 to-secondary/5',
        iconBg: 'bg-secondary-container',
        iconColor: 'text-on-secondary-container',
        accentColor: 'bg-secondary',
    },
    {
        id: 'report-card',
        title: 'Rapor & Nilai Siswa',
        description: 'Rekap nilai dan pembuatan rapor semester peserta didik secara digital.',
        icon: 'school',
        href: null,
        status: 'coming_soon',
        badge: 'Segera Hadir',
        features: ['Rekap nilai per mata pelajaran', 'Cetak rapor semester', 'Ekspor ke Excel'],
        gradient: 'from-tertiary/10 to-tertiary/5',
        iconBg: 'bg-tertiary-container',
        iconColor: 'text-on-tertiary-container',
        accentColor: 'bg-tertiary',
    },
];
</script>

<template>
    <Head title="Layanan Administrasi" />

    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop font-sans">
        <div class="max-w-7xl mx-auto w-full">
            <!-- Page Header -->
            <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl text-primary font-bold">
                    Layanan Administrasi
                </h2>
                <p class="font-body-md text-body-md text-on-surface-variant mt-1">
                    Pilih layanan administrasi yang ingin Anda gunakan
                </p>
            </div>
        </div>

            <!-- Module Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 md:gap-10">
                <component
                    :is="mod.href ? Link : 'div'"
                    v-for="mod in modules"
                    :key="mod.id"
                    :href="mod.href || undefined"
                    :class="[
                        'group relative bg-white rounded-2xl border overflow-hidden transition-all duration-200 flex flex-col',
                        mod.status === 'available'
                            ? 'border-outline-variant md:hover:shadow-lg md:hover:-translate-y-1 cursor-pointer'
                            : 'border-outline-variant/50 opacity-70 cursor-default'
                    ]"
                >
                    <!-- Top accent bar -->
                    <div :class="['h-1 shrink-0', mod.accentColor]"></div>

                    <div class="p-6 flex flex-col flex-1">
                        <!-- Header: Icon + Badge -->
                        <div class="flex items-start justify-between mb-5">
                            <div :class="['w-12 h-12 rounded-xl flex items-center justify-center', mod.iconBg]">
                                <span :class="['material-symbols-outlined text-2xl', mod.iconColor]">{{ mod.icon }}</span>
                            </div>
                            <span
                                :class="[
                                    'text-[11px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wide',
                                    mod.status === 'available'
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-surface-container text-on-surface-variant'
                                ]"
                            >
                                {{ mod.badge }}
                            </span>
                        </div>

                        <!-- Title & Description -->
                        <h2 :class="[
                            'text-base font-bold mb-2 leading-snug transition-colors',
                            mod.status === 'available' ? 'text-on-surface md:group-hover:text-primary' : 'text-on-surface-variant'
                        ]">
                            {{ mod.title }}
                        </h2>
                        <p class="text-sm text-on-surface-variant leading-relaxed mb-5">
                            {{ mod.description }}
                        </p>

                        <!-- Feature list -->
                        <ul class="space-y-1.5 mb-6">
                            <li
                                v-for="feature in mod.features"
                                :key="feature"
                                class="flex items-center space-x-2 text-xs text-on-surface-variant"
                            >
                                <span :class="['material-symbols-outlined text-[15px]', mod.status === 'available' ? 'text-primary' : 'text-on-surface-variant/50']">
                                    check_circle
                                </span>
                                <span>{{ feature }}</span>
                            </li>
                        </ul>

                        <!-- Footer CTA -->
                        <div class="pt-4 border-t border-outline-variant/50 mt-auto">
                            <div v-if="mod.status === 'available'" class="flex items-center space-x-1.5 text-sm font-semibold text-primary md:group-hover:underline">
                                <span>Buka Fitur</span>
                                <span class="material-symbols-outlined text-base md:group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
                            </div>
                            <div v-else class="text-xs text-on-surface-variant flex items-center space-x-1.5">
                                <span class="material-symbols-outlined text-base">schedule</span>
                                <span>Dalam pengembangan</span>
                            </div>
                        </div>
                    </div>
                </component>
            </div>

        </div>
    </div>
</template>
