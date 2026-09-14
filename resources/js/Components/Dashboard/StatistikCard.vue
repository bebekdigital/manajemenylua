<script setup>
const stats = [
    {
        id: 'students',
        label: 'Total Siswa Aktif',
        value: '1.248',
        icon: 'groups',
        trend: '+12 (Bulan Ini)',
        color: 'text-primary'
    },
    {
        id: 'teachers',
        label: 'Total Tenaga Pendidik',
        value: '84',
        icon: 'school',
        trend: 'Tetap',
        color: 'text-secondary'
    },
    {
        id: 'attendance',
        label: 'Rata-Rata Kehadiran',
        value: '98.5%',
        icon: 'fact_check',
        trend: '+0.5% (Naik)',
        color: 'text-emerald-500'
    }
];
</script>

<template>
    <div class="bg-surface-container-lowest rounded-2xl border border-primary/10 shadow-[0px_4px_20px_rgba(0,40,20,0.06)] overflow-hidden h-full flex flex-col">
        <div class="px-5 py-4 border-b border-outline-variant/30 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-blue-700 text-xl">analytics</span>
            </div>
            <div>
                <h3 class="text-base font-bold text-on-surface">Statistik Sekolah</h3>
                <p class="text-xs text-on-surface-variant">Ringkasan metrik SDM & Akademik</p>
            </div>
        </div>
        
        <div class="flex-grow p-5 flex flex-col justify-between space-y-4">
            <div v-for="(stat, index) in stats" :key="stat.id" :class="['flex items-center justify-between', index !== stats.length - 1 ? 'pb-4 border-b border-outline-variant/20' : '']">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center">
                        <span :class="`material-symbols-outlined ${stat.color} text-lg`">{{ stat.icon }}</span>
                    </div>
                    <div>
                        <p class="text-xs text-on-surface-variant font-medium">{{ stat.label }}</p>
                        <p class="text-[10px] text-outline mt-0.5">{{ stat.trend }}</p>
                    </div>
                </div>
                <div class="text-xl font-extrabold text-on-surface">
                    {{ stat.value }}
                </div>
            </div>
        </div>
    </div>
</template>
