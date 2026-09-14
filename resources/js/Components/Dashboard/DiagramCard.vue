<script setup>
const chartData = [
    { label: 'Kelas 7', value: 98, color: 'bg-primary' },
    { label: 'Kelas 8', value: 95, color: 'bg-primary' },
    { label: 'Kelas 9', value: 92, color: 'bg-primary' },
    { label: 'Kelas 10', value: 89, color: 'bg-secondary' },
    { label: 'Kelas 11', value: 94, color: 'bg-secondary' },
    { label: 'Kelas 12', value: 96, color: 'bg-secondary' },
];
</script>

<template>
    <div class="bg-surface-container-lowest rounded-2xl border border-primary/10 shadow-[0px_4px_20px_rgba(0,40,20,0.06)] overflow-hidden h-full flex flex-col">
        <div class="px-5 py-4 border-b border-outline-variant/30 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center">
                    <span class="material-symbols-outlined text-orange-600 text-xl">bar_chart</span>
                </div>
                <div>
                    <h3 class="text-base font-bold text-on-surface">Tingkat Kehadiran</h3>
                    <p class="text-xs text-on-surface-variant">Rata-rata per Jenjang (%)</p>
                </div>
            </div>
        </div>
        
        <div class="flex-grow p-5 flex items-end justify-between gap-2 h-48 relative pt-10">
            <!-- Background Grid Lines -->
            <div class="absolute inset-0 px-5 py-5 flex flex-col justify-between pointer-events-none border-b border-outline-variant/20 z-0 pb-10">
                <div class="w-full border-t border-dashed border-outline-variant/30"></div>
                <div class="w-full border-t border-dashed border-outline-variant/30"></div>
                <div class="w-full border-t border-dashed border-outline-variant/30"></div>
                <div class="w-full border-t border-dashed border-outline-variant/30"></div>
            </div>
            
            <!-- Bars -->
            <div v-for="bar in chartData" :key="bar.label" class="relative z-10 flex flex-col items-center flex-1 h-full justify-end group">
                <span class="text-[10px] font-bold text-on-surface mb-2 opacity-0 group-hover:opacity-100 transition-opacity -translate-y-2 group-hover:translate-y-0 absolute top-0">{{ bar.value }}%</span>
                <div :class="`w-full max-w-[32px] rounded-t-md ${bar.color} transition-all duration-500 ease-in-out hover:opacity-80 cursor-pointer`" :style="`height: ${bar.value}%`"></div>
                <span class="text-[10px] text-on-surface-variant font-medium mt-3 text-center whitespace-nowrap overflow-hidden text-ellipsis w-full px-1">{{ bar.label.replace('Kelas ', 'K') }}</span>
            </div>
        </div>
    </div>
</template>
