<script setup>
const agendas = [
    {
        id: 1,
        title: 'Rapat Koordinasi Awal Bulan',
        date: '10 Nov 2023',
        time: '08:00 - 10:00 WIB',
        location: 'Ruang Rapat Utama',
        type: 'Rapat',
        typeColor: 'bg-blue-100 text-blue-700 border-blue-200'
    },
    {
        id: 2,
        title: 'Pelaksanaan Ujian Tengah Semester (UTS)',
        date: '15 - 20 Nov 2023',
        time: '07:30 - Selesai',
        location: 'Seluruh Kelas',
        type: 'Akademik',
        typeColor: 'bg-emerald-100 text-emerald-700 border-emerald-200'
    },
    {
        id: 3,
        title: 'Batas Akhir Input Nilai UTS',
        date: '25 Nov 2023',
        time: '23:59 WIB',
        location: 'Sistem Akademik',
        type: 'Deadline',
        typeColor: 'bg-red-100 text-red-700 border-red-200'
    }
];
</script>

<template>
    <div class="bg-gradient-to-br from-emerald-50 to-emerald-100/50 rounded-2xl border border-emerald-200/60 shadow-[0px_4px_20px_rgba(16,185,129,0.08)] overflow-hidden h-full flex flex-col relative">
        <!-- Decorative subtle pattern/blob -->
        <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-200/30 rounded-full blur-2xl -translate-y-10 translate-x-10 pointer-events-none"></div>
        
        <div class="px-5 py-4 border-b border-emerald-200/50 flex items-center justify-between relative z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500 flex items-center justify-center shadow-sm">
                    <span class="material-symbols-outlined text-white text-xl">event_upcoming</span>
                </div>
                <div>
                    <h3 class="text-base font-bold text-emerald-950">Agenda Terdekat</h3>
                    <p class="text-xs text-emerald-700/80">Jadwal kegiatan akademik & yayasan</p>
                </div>
            </div>
            <button class="text-xs font-bold text-emerald-600 hover:text-emerald-800 transition-colors bg-white/60 hover:bg-white px-3 py-1.5 rounded-lg border border-emerald-200/50">Lihat Semua</button>
        </div>
        
        <div class="flex-grow p-0 relative z-10">
            <div class="divide-y divide-emerald-200/50">
                <div v-for="agenda in agendas" :key="agenda.id" class="p-4 hover:bg-white/40 transition-colors flex flex-col sm:flex-row gap-4 sm:items-center justify-between">
                    <div class="flex items-start gap-4">
                        <div class="hidden sm:flex flex-col items-center justify-center w-14 h-14 rounded-xl bg-white border border-emerald-200/60 shadow-sm shrink-0">
                            <span class="text-xs font-bold text-emerald-600 uppercase">{{ agenda.date.split(' ')[1] }}</span>
                            <span class="text-lg font-extrabold text-emerald-900 leading-tight">{{ agenda.date.split(' ')[0] }}</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="font-bold text-emerald-950 text-sm">{{ agenda.title }}</h4>
                                <span :class="`text-[9px] font-bold px-2 py-0.5 rounded-md border ${agenda.typeColor}`">{{ agenda.type }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-emerald-700/90">
                                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">schedule</span> {{ agenda.time }}</span>
                                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">location_on</span> {{ agenda.location }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
