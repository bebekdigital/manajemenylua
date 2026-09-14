<template>
    <div class="bg-gradient-to-br from-[#005a30] via-[#006837] to-[#044426] rounded-2xl shadow-xl shadow-primary/20 overflow-hidden h-full flex flex-col relative text-white">
        <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-white/10 pointer-events-none blur-xl"></div>
        
        <div class="px-5 py-4 flex items-center justify-between relative z-10">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-accent-gold">workspace_premium</span>
                <h3 class="font-bold text-white tracking-wide text-sm">Skor KPI Yayasan</h3>
            </div>
            <span class="text-[10px] font-medium bg-white/10 px-2 py-0.5 rounded-full border border-white/20">Bulan Ini</span>
        </div>
        
        <div class="flex-grow flex flex-col items-center justify-center p-6 relative z-10">
            <div class="relative w-32 h-32 flex items-center justify-center">
                <!-- Outer Circle -->
                <svg class="absolute inset-0 w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="45" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="8" />
                    <!-- Progress (92%) -->
                    <circle cx="50" cy="50" r="45" fill="none" stroke="#facc15" stroke-width="8" stroke-dasharray="283" stroke-dashoffset="22.64" class="transition-all duration-1000 ease-out" />
                </svg>
                <div class="flex flex-col items-center">
                    <span class="text-4xl font-extrabold text-white tracking-tighter">92</span>
                    <span class="text-[10px] text-emerald-100/80 uppercase tracking-widest font-semibold mt-1">Sangat Baik</span>
                </div>
            </div>
            
            <div class="mt-6 w-full space-y-3">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-emerald-100/80">Kedisiplinan</span>
                    <span class="font-bold text-white">95%</span>
                </div>
                <div class="w-full bg-white/10 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-accent-gold h-full rounded-full" style="width: 95%"></div>
                </div>
                
                <div class="flex justify-between items-center text-xs">
                    <span class="text-emerald-100/80">Serapan Anggaran</span>
                    <span class="font-bold text-white">88%</span>
                </div>
                <div class="w-full bg-white/10 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-emerald-400 h-full rounded-full" style="width: 88%"></div>
                </div>
            </div>
        </div>
    </div>
</template>
