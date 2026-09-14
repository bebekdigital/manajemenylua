<script setup>
import { ref } from 'vue';

const activeTab = ref('agenda'); // 'agenda' or 'kpi'

const agendas = [
    {
        id: 1,
        title: 'Rapat Koordinasi Awal Bulan',
        date: '10 Nov 2023',
        time: '08:00 - 10:00 WIB',
        location: 'Ruang Rapat Utama',
        type: 'Rapat',
        typeColor: 'bg-blue-500/20 text-blue-200 border-blue-400/30'
    },
    {
        id: 2,
        title: 'Pelaksanaan UTS',
        date: '15 - 20 Nov 2023',
        time: '07:30 - Selesai',
        location: 'Seluruh Kelas',
        type: 'Akademik',
        typeColor: 'bg-emerald-400/20 text-emerald-200 border-emerald-400/30'
    },
    {
        id: 3,
        title: 'Batas Akhir Input Nilai UTS',
        date: '25 Nov 2023',
        time: '23:59 WIB',
        location: 'Sistem Akademik',
        type: 'Deadline',
        typeColor: 'bg-red-400/20 text-red-200 border-red-400/30'
    }
];
</script>

<template>
    <div class="bg-gradient-to-br from-[#005a30] via-[#006837] to-[#044426] rounded-2xl shadow-xl shadow-emerald-900/20 overflow-hidden h-full flex flex-col relative text-white">
        <!-- Decorative blur blob -->
        <div class="absolute -right-8 -top-8 w-40 h-40 rounded-full bg-white/10 pointer-events-none blur-2xl"></div>
        <div class="absolute -left-10 bottom-10 w-32 h-32 rounded-full bg-accent-gold/10 pointer-events-none blur-2xl"></div>
        
        <!-- Header with Tabs -->
        <div class="px-5 py-4 border-b border-white/10 flex items-center justify-between relative z-10">
            <div class="flex bg-white/10 p-1 rounded-xl w-full sm:w-auto">
                <button 
                    @click="activeTab = 'agenda'"
                    class="flex-1 sm:flex-none px-4 py-1.5 rounded-lg text-xs font-bold transition-all duration-300 flex items-center justify-center gap-2"
                    :class="activeTab === 'agenda' ? 'bg-white text-emerald-900 shadow-sm' : 'text-emerald-100/70 hover:text-white'"
                >
                    <span class="material-symbols-outlined text-[16px] filled-icon">event_upcoming</span>
                    Agenda Terdekat
                </button>
                <button 
                    @click="activeTab = 'kpi'"
                    class="flex-1 sm:flex-none px-4 py-1.5 rounded-lg text-xs font-bold transition-all duration-300 flex items-center justify-center gap-2"
                    :class="activeTab === 'kpi' ? 'bg-white text-emerald-900 shadow-sm' : 'text-emerald-100/70 hover:text-white'"
                >
                    <span class="material-symbols-outlined text-[16px] filled-icon">workspace_premium</span>
                    Skor KPI
                </button>
            </div>
            
            <button v-if="activeTab === 'agenda'" class="hidden sm:block text-xs font-bold text-emerald-200 hover:text-white transition-colors bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-lg border border-white/10">Lihat Semua</button>
            <span v-if="activeTab === 'kpi'" class="hidden sm:block text-[10px] font-medium bg-white/10 px-2 py-0.5 rounded-full border border-white/20 text-emerald-100">Bulan Ini</span>
        </div>
        
        <!-- Content Area -->
        <div class="flex-grow p-0 relative z-10">
            
            <!-- Agenda Tab Content -->
            <Transition
                enter-active-class="transition-opacity duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="hidden"
            >
                <div v-if="activeTab === 'agenda'" class="divide-y divide-white/10">
                    <div v-for="agenda in agendas" :key="agenda.id" class="p-4 hover:bg-white/5 transition-colors flex flex-col sm:flex-row gap-4 sm:items-center justify-between">
                        <div class="flex items-start gap-4 w-full">
                            <div class="flex flex-col items-center justify-center w-14 h-14 rounded-xl bg-white/10 border border-white/10 shadow-sm shrink-0">
                                <span class="text-[10px] font-bold text-emerald-200 uppercase">{{ agenda.date.split(' ')[1] }}</span>
                                <span class="text-lg font-extrabold text-white leading-tight">{{ agenda.date.split(' ')[0] }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-2 mb-1.5">
                                    <h4 class="font-bold text-white text-sm truncate">{{ agenda.title }}</h4>
                                    <span :class="`text-[9px] font-bold px-2 py-0.5 rounded-md border w-fit ${agenda.typeColor}`">{{ agenda.type }}</span>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 text-[11px] text-emerald-100/80">
                                    <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[13px]">schedule</span> {{ agenda.time }}</span>
                                    <span class="flex items-center gap-1 truncate"><span class="material-symbols-outlined text-[13px]">location_on</span> {{ agenda.location }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- KPI Tab Content -->
            <Transition
                enter-active-class="transition-opacity duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="hidden"
            >
                <div v-if="activeTab === 'kpi'" class="p-6 flex flex-col sm:flex-row items-center justify-center gap-8 h-full">
                    <!-- Progress Circle -->
                    <div class="relative w-32 h-32 flex items-center justify-center shrink-0">
                        <!-- Outer Circle -->
                        <svg class="absolute inset-0 w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="45" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="8" />
                            <!-- Progress (92%) -->
                            <circle cx="50" cy="50" r="45" fill="none" stroke="#facc15" stroke-width="8" stroke-dasharray="283" stroke-dashoffset="22.64" class="transition-all duration-1000 ease-out" />
                        </svg>
                        <div class="flex flex-col items-center">
                            <span class="text-4xl font-extrabold text-white tracking-tighter">92</span>
                            <span class="text-[10px] text-emerald-200/90 uppercase tracking-widest font-semibold mt-1">Sangat Baik</span>
                        </div>
                    </div>
                    
                    <!-- Progress Bars -->
                    <div class="w-full sm:w-64 space-y-4">
                        <div>
                            <div class="flex justify-between items-center text-xs mb-1.5">
                                <span class="text-emerald-100/80 font-medium">Kedisiplinan</span>
                                <span class="font-bold text-white">95%</span>
                            </div>
                            <div class="w-full bg-white/10 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-accent-gold h-full rounded-full" style="width: 95%"></div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between items-center text-xs mb-1.5">
                                <span class="text-emerald-100/80 font-medium">Serapan Anggaran</span>
                                <span class="font-bold text-white">88%</span>
                            </div>
                            <div class="w-full bg-white/10 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-emerald-400 h-full rounded-full" style="width: 88%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </div>
</template>

<style scoped>
.filled-icon {
    font-variation-settings: 'FILL' 1;
}
</style>
