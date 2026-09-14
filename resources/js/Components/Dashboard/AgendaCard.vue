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
        typeColor: 'bg-orange-100 text-orange-700 border-orange-200'
    }
];
</script>

<template>
    <div class="bg-gradient-to-br from-emerald-100/80 via-emerald-50 to-amber-50/80 rounded-2xl border border-emerald-200/60 overflow-hidden h-full flex flex-col relative">
        <!-- Decorative blobs -->
        <div class="absolute -right-10 -top-10 w-36 h-36 rounded-full bg-amber-200/20 pointer-events-none blur-2xl"></div>
        <div class="absolute left-0 bottom-0 w-28 h-28 rounded-full bg-emerald-200/20 pointer-events-none blur-2xl"></div>

        <!-- Header -->
        <div class="px-5 py-4 border-b border-emerald-200/40 flex items-center justify-between relative z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-400 flex items-center justify-center shadow-sm">
                    <span class="material-symbols-outlined text-white text-xl">event_upcoming</span>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Agenda Terdekat</h3>
                    <p class="text-[11px] text-slate-500">Jadwal kegiatan akademik & yayasan</p>
                </div>
            </div>
            <button class="text-[11px] font-bold text-slate-700 hover:text-slate-900 transition-colors bg-slate-100/80 hover:bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200/60">Lihat Semua</button>
        </div>
        
        <!-- List -->
        <div class="flex-grow p-0 relative z-10">
            <div class="divide-y divide-emerald-100/80">
                <div v-for="agenda in agendas" :key="agenda.id" class="p-4 hover:bg-emerald-50/50 transition-colors flex items-start gap-4">
                    <div class="flex flex-col items-center justify-center w-12 h-12 rounded-xl bg-white border border-emerald-200/60 shadow-sm shrink-0">
                        <span class="text-[9px] font-bold text-amber-500 uppercase leading-tight">{{ agenda.date.split(' ')[1] }}</span>
                        <span class="text-base font-extrabold text-slate-900 leading-tight">{{ agenda.date.split(' ')[0] }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h4 class="font-bold text-slate-900 text-sm truncate">{{ agenda.title }}</h4>
                            <span :class="`text-[9px] font-bold px-2 py-0.5 rounded-md border shrink-0 ${agenda.typeColor}`">{{ agenda.type }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-[11px] text-slate-500">
                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[13px]">schedule</span> {{ agenda.time }}</span>
                            <span class="flex items-center gap-1 truncate"><span class="material-symbols-outlined text-[13px]">location_on</span> {{ agenda.location }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
