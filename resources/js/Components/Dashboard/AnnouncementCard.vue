<script setup>
import { Link } from '@inertiajs/vue3';

const announcements = [
    {
        id: 1,
        time: 'Hari ini, 09:00 WIB',
        title: 'Pemberitahuan Libur Nasional',
        description: 'Libur Hari Raya, seluruh kegiatan akademik diliburkan pada 12-14 November.',
        highlight: true,
    },
    {
        id: 2,
        time: '28 Okt 2023',
        title: 'Perbaikan Sistem Data',
        description: 'Sistem akademik akan offline sementara dari jam 02:00 s.d 04:00 WIB untuk pemeliharaan rutin.',
        highlight: false,
    },
];
</script>

<template>
    <div class="bg-surface-container-lowest rounded-2xl border border-primary/10 shadow-[0px_4px_20px_rgba(0,40,20,0.06)] overflow-hidden h-full flex flex-col">
        <div class="px-5 py-4 border-b border-outline-variant/30 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-secondary-container/30 flex items-center justify-center">
                    <span class="material-symbols-outlined text-secondary text-xl">campaign</span>
                </div>
                <div>
                    <h3 class="text-base font-bold text-on-surface">Pengumuman</h3>
                    <p class="text-xs text-on-surface-variant">Informasi penting terbaru</p>
                </div>
            </div>
            <Link
                href="/info"
                class="text-[10px] sm:text-[11px] font-bold text-slate-700 hover:text-slate-900 transition-colors bg-slate-100/80 hover:bg-slate-100 px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg border border-slate-200/60"
            >
                Lihat Semua
            </Link>
        </div>
        <div class="flex-grow p-5 space-y-4">
            <div
                v-for="(item, index) in announcements"
                :key="item.id"
                :class="[
                    'pl-4',
                    item.highlight ? 'border-l-[3px] border-primary' : 'border-l-[3px] border-outline-variant/50',
                    index < announcements.length - 1 ? 'pb-4 border-b border-outline-variant/20' : ''
                ]"
            >
                <p class="text-[10px] font-bold text-primary mb-1 uppercase tracking-wider">{{ item.time }}</p>
                <p class="font-bold text-on-surface text-sm mb-1">{{ item.title }}</p>
                <p class="text-xs text-on-surface-variant leading-relaxed">{{ item.description }}</p>
            </div>
        </div>
    </div>
</template>
