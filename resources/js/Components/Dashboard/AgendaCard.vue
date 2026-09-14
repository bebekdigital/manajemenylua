<script setup>
const agendas = [
    {
        id: 1,
        title: 'Rapat Koordinasi Awal Bulan',
        date: '10 Nov 2023',
        time: '08:00 - 10:00 WIB',
        location: 'Ruang Rapat Utama',
        type: 'Rapat',
        typeColor: 'bg-emerald-600/20 text-emerald-100 border-emerald-400/30'
    },
    {
        id: 2,
        title: 'Pelaksanaan Ujian Tengah Semester (UTS)',
        date: '15 - 20 Nov 2023',
        time: '07:30 - Selesai',
        location: 'Seluruh Kelas',
        type: 'Akademik',
        typeColor: 'bg-yellow-400/20 text-yellow-200 border-yellow-400/30'
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
    <div class="bg-gradient-to-br from-emerald-700 via-emerald-800 to-emerald-900 rounded-2xl overflow-hidden h-full flex flex-col relative text-white">
        <!-- Decorative blobs -->
        <div class="absolute -right-10 -top-10 w-40 h-40 rounded-full bg-yellow-300/10 pointer-events-none blur-2xl"></div>
        <div class="absolute left-1/2 bottom-0 w-28 h-28 rounded-full bg-emerald-400/10 pointer-events-none blur-2xl"></div>

        <!-- Header -->
        <div class="px-5 py-4 border-b border-white/10 flex items-center justify-between relative z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-yellow-300 flex items-center justify-center">
                    <span class="material-symbols-outlined text-emerald-900 text-xl">event_upcoming</span>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">Agenda Terdekat</h3>
                    <p class="text-[11px] text-emerald-200/70">Jadwal kegiatan akademik & yayasan</p>
                </div>
            </div>
            <button class="text-[11px] font-bold text-yellow-300 hover:text-yellow-200 transition-colors bg-white/10 hover:bg-white/15 px-3 py-1.5 rounded-lg border border-white/10">Lihat Semua</button>
        </div>
        
        <!-- List -->
        <div class="flex-grow p-0 relative z-10">
            <div class="divide-y divide-white/10">
                <div v-for="agenda in agendas" :key="agenda.id" class="p-4 hover:bg-white/5 transition-colors flex items-start gap-4">
                    <div class="flex flex-col items-center justify-center w-12 h-12 rounded-xl bg-white/10 border border-white/10 shrink-0">
                        <span class="text-[9px] font-bold text-yellow-300 uppercase leading-tight">{{ agenda.date.split(' ')[1] }}</span>
                        <span class="text-base font-extrabold text-white leading-tight">{{ agenda.date.split(' ')[0] }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h4 class="font-bold text-white text-sm truncate">{{ agenda.title }}</h4>
                            <span :class="`text-[9px] font-bold px-2 py-0.5 rounded-md border shrink-0 ${agenda.typeColor}`">{{ agenda.type }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-[11px] text-emerald-200/70">
                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[13px]">schedule</span> {{ agenda.time }}</span>
                            <span class="flex items-center gap-1 truncate"><span class="material-symbols-outlined text-[13px]">location_on</span> {{ agenda.location }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
