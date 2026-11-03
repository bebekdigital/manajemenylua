<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user || {});

// ============================================================
// Deteksi Otomatis Unit (SDIT vs SMPIT/PPTQ) & Jenis Kelamin (L vs P)
// ============================================================
const detectedUnitTheme = computed(() => {
    const unitStr = String(user.value?.unit || '').toUpperCase();
    if (unitStr.includes('SMPIT') || unitStr.includes('SMP IT') || unitStr.includes('PPTQ')) {
        return 'smpit';
    }
    return 'sdit';
});

const detectedGender = computed(() => {
    const jkStr = String(user.value?.jk || 'L').toUpperCase().trim();
    if (jkStr.startsWith('P') || jkStr.startsWith('W')) {
        return 'P';
    }
    return 'L';
});

// Override state untuk pratinjau cepat (berguna bagi Superadmin / Yayasaan)
const previewUnitOverride = ref(null);
const previewGenderOverride = ref(null);

const activeUnitTheme = computed(() => previewUnitOverride.value || detectedUnitTheme.value);
const activeGender = computed(() => previewGenderOverride.value || detectedGender.value);

const isSuperAdmin = computed(() => {
    const role = String(user.value?.role || '').toLowerCase();
    return role === 'superadmin' || !user.value?.unit;
});

function cycleUnitTheme() {
    previewUnitOverride.value = activeUnitTheme.value === 'sdit' ? 'smpit' : 'sdit';
}

function cycleGender() {
    previewGenderOverride.value = activeGender.value === 'L' ? 'P' : 'L';
}

// Aset Gambar Latar & Karakter 3D Clay
const backgroundUrl = computed(() => {
    return activeUnitTheme.value === 'smpit'
        ? '/images/hero/bg-smpit.jpg'
        : '/images/hero/bg-sdit.jpg';
});

const characterUrl = computed(() => {
    return activeGender.value === 'P'
        ? '/images/hero/char-female.png'
        : '/images/hero/char-male.png';
});

const unitBadgeLabel = computed(() => {
    if (activeUnitTheme.value === 'smpit') {
        const raw = String(user.value?.unit || '').toUpperCase();
        return raw.includes('PPTQ') ? 'PPTQ Ulil Albab' : 'SMPIT & PPTQ';
    }
    return 'SDIT Ulil Albab';
});

// ============================================================
// Sapaan Waktu & Tanggal Realtime Bahasa Indonesia
// ============================================================
const now = ref(new Date());
let timerInterval = null;

onMounted(() => {
    timerInterval = setInterval(() => {
        now.value = new Date();
    }, 60000);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

const greetingText = computed(() => {
    const hour = now.value.getHours();
    if (hour >= 4 && hour < 11) return 'Selamat pagi';
    if (hour >= 11 && hour < 15) return 'Selamat siang';
    if (hour >= 15 && hour < 18) return 'Selamat sore';
    return 'Selamat malam';
});

const shortDisplayName = computed(() => {
    const fullName = String(user.value?.name || 'Pegawai').trim();
    // Hilangkan gelar di depan/belakang untuk sapaan hangat seperti "Selamat siang, Fath."
    const withoutBackTitle = fullName.split(',')[0].trim();
    const parts = withoutBackTitle
        .replace(/^(Drs?\.|Ir\.|H\.|Hj\.|Ust\.|Ustadz|Ustadzah)\s+/i, '')
        .trim()
        .split(/\s+/);
    return parts[0] || fullName;
});

const formattedToday = computed(() => {
    const formatter = new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
    return `Hari ini · ${formatter.format(now.value)}`;
});

// ============================================================
// 2 Agenda Terdekat (Dummy Data)
// ============================================================
const upcomingAgendas = [
    {
        id: 1,
        title: 'Rapat Koordinasi Bulanan Yayasan',
        date: '10 Nov',
        time: '08:00 - 10:00 WIB',
        location: 'Ruang Rapat Utama',
        category: 'Rapat',
        badgeClass: 'bg-amber-500/20 text-amber-950 border-amber-500/30',
        dotClass: 'bg-amber-500',
    },
    {
        id: 2,
        title: 'Pelaksanaan Penilaian Tengah Semester',
        date: '15 Nov',
        time: '07:30 WIB - Selesai',
        location: 'Seluruh Unit Pendidikan',
        category: 'Akademik',
        badgeClass: 'bg-emerald-600/15 text-emerald-950 border-emerald-600/30',
        dotClass: 'bg-emerald-600',
    },
];
</script>

<template>
    <section
        class="relative w-full overflow-hidden md:rounded-3xl md:border md:border-slate-200/80 md:shadow-md select-none"
    >
        <!-- Layer 1: Background Ilustrasi Gedung Sekolah (SDIT / SMPIT & PPTQ) -->
        <div class="absolute inset-0 z-0">
            <img
                :src="backgroundUrl"
                :alt="unitBadgeLabel"
                class="w-full h-full object-cover object-center transition-all duration-700 scale-[1.01]"
            />
            <!-- Gradient Overlay Halus agar Teks Sapaan di Langit & Agenda di Bawah Sangat Jelas Terbaca -->
            <div
                class="absolute inset-0 bg-gradient-to-b from-sky-950/35 via-sky-900/15 to-slate-950/55 md:from-sky-950/30 md:via-sky-950/10 md:to-slate-950/50"
            ></div>
            <!-- Radial Sun Glow Halus di Pojok Kiri Atas -->
            <div
                class="absolute -top-20 -left-20 w-80 h-80 rounded-full bg-amber-100/25 blur-3xl pointer-events-none"
            ></div>
        </div>

        <!-- Layer 2: Animasi Awan / Partikel CSS Ringan -->
        <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden">
            <div
                class="hero-cloud-slow absolute top-10 right-[28%] w-36 h-12 rounded-full bg-white/20 blur-xl"
            ></div>
            <div
                class="hero-cloud-fast absolute top-20 left-[40%] w-24 h-8 rounded-full bg-white/15 blur-lg"
            ></div>
        </div>

        <!-- Layer 3: Konten Utama di Dalam Ilustrasi -->
        <!-- Pada Mobile: pt-16 memberi ruang bagi tombol overlay TopNav (icon sekolah & profil/notifikasi) -->
        <div
            class="relative z-10 px-4 pt-16 pb-10 sm:px-6 md:px-8 md:pt-7 md:pb-7 flex flex-col justify-between min-h-[370px] sm:min-h-[350px] md:min-h-[330px]"
        >
            <!-- Baris Atas: Sapaan Nama Sesuai Jam, Hari Tanggal & Tombol Switcher Pratinjau -->
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div class="max-w-xl">
                    <!-- Pill Unit & Switcher Pratinjau -->
                    <div class="inline-flex flex-wrap items-center gap-1.5 mb-2.5">
                        <span
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-white/85 backdrop-blur-md text-emerald-950 shadow-2xs border border-white/70"
                        >
                            <span class="material-symbols-outlined text-[14px] text-emerald-700">domain</span>
                            <span>{{ unitBadgeLabel }}</span>
                        </span>

                        <!-- Tombol Switcher Cepat (Memudahkan pengecekan tampilan Gedung SDIT/SMPIT & Karakter L/P) -->
                        <button
                            v-if="isSuperAdmin || true"
                            type="button"
                            @click="cycleUnitTheme"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10.5px] font-semibold bg-slate-900/45 hover:bg-slate-900/65 text-white backdrop-blur-md border border-white/25 transition-all cursor-pointer active:scale-95"
                            title="Ganti pratinjau latar gedung (SDIT / SMPIT & PPTQ)"
                        >
                            <span class="material-symbols-outlined text-[13px]">flip_camera_android</span>
                            <span>Gedung: {{ activeUnitTheme === 'sdit' ? 'SDIT' : 'SMPIT/PPTQ' }}</span>
                        </button>

                        <button
                            v-if="isSuperAdmin || true"
                            type="button"
                            @click="cycleGender"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10.5px] font-semibold bg-slate-900/45 hover:bg-slate-900/65 text-white backdrop-blur-md border border-white/25 transition-all cursor-pointer active:scale-95"
                            title="Ganti pratinjau karakter 3D (Laki-laki / Perempuan)"
                        >
                            <span class="material-symbols-outlined text-[13px]">face</span>
                            <span>3D: {{ activeGender === 'L' ? 'Putra' : 'Putri' }}</span>
                        </button>
                    </div>

                    <!-- Sapaan Utama Sesuai Jam -->
                    <h1
                        class="text-[26px] sm:text-3xl md:text-[34px] font-extrabold text-white tracking-tight leading-tight drop-shadow-[0_2px_10px_rgba(15,23,42,0.45)]"
                    >
                        {{ greetingText }}, {{ shortDisplayName }}.
                    </h1>

                    <!-- Hari & Tanggal -->
                    <p
                        class="mt-1 text-xs sm:text-[13.5px] font-semibold text-white/95 flex items-center gap-1.5 drop-shadow-[0_1px_6px_rgba(15,23,42,0.45)]"
                    >
                        <span class="material-symbols-outlined text-[16px] text-amber-300">wb_sunny</span>
                        <span>{{ formattedToday }}</span>
                    </p>
                </div>
            </div>

            <!-- Baris Bawah: 2 Agenda Terdekat (Kiri/Tengah) + Karakter Maskot 3D Clay (Kanan) -->
            <div class="mt-6 md:mt-8 grid grid-cols-12 items-end gap-3 sm:gap-4">
                <!-- Kolom Agenda Terdekat (2 Agenda Dummy) -->
                <div class="col-span-8 sm:col-span-8 md:col-span-8 lg:col-span-8 z-20">
                    <div
                        class="bg-white/88 backdrop-blur-md rounded-2xl p-3 sm:p-4 border border-white/80 shadow-[0_10px_30px_rgba(15,23,42,0.18)]"
                    >
                        <!-- Header Agenda di dalam Kartu Ilustrasi -->
                        <div class="flex items-center justify-between mb-2.5 pb-2 border-b border-slate-200/70">
                            <div class="flex items-center gap-2">
                                <span
                                    class="w-6 h-6 rounded-lg bg-emerald-900 text-yellow-200 flex items-center justify-center shadow-2xs shrink-0"
                                >
                                    <span class="material-symbols-outlined text-[15px]">event_upcoming</span>
                                </span>
                                <span class="text-xs sm:text-[13px] font-extrabold text-slate-900 tracking-tight">
                                    2 Agenda Terdekat
                                </span>
                            </div>
                            <Link
                                href="/agenda"
                                class="inline-flex items-center gap-0.5 text-[11px] font-bold text-emerald-800 hover:text-emerald-950 transition-colors"
                            >
                                <span>Lihat Semua</span>
                                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                            </Link>
                        </div>

                        <!-- Daftar 2 Agenda Terdekat -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 sm:gap-2.5">
                            <Link
                                v-for="item in upcomingAgendas"
                                :key="item.id"
                                href="/agenda"
                                class="group flex items-start gap-2.5 p-2 sm:p-2.5 rounded-xl bg-white/90 hover:bg-emerald-50/70 border border-slate-200/80 transition-all shadow-2xs"
                            >
                                <!-- Kotak Tanggal Ringkas -->
                                <div
                                    class="flex flex-col items-center justify-center w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-emerald-950 text-white shrink-0 shadow-2xs group-hover:bg-emerald-900 transition-colors"
                                >
                                    <span class="text-[9px] font-bold uppercase text-yellow-300 leading-none">
                                        {{ item.date.split(' ')[1] }}
                                    </span>
                                    <span class="text-sm sm:text-[15px] font-extrabold leading-tight mt-0.5">
                                        {{ item.date.split(' ')[0] }}
                                    </span>
                                </div>

                                <!-- Detail Agenda -->
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="item.dotClass"></span>
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                            {{ item.category }}
                                        </span>
                                    </div>
                                    <h4
                                        class="text-xs sm:text-[13px] font-bold text-slate-900 leading-snug line-clamp-1 group-hover:text-emerald-950 mt-0.5"
                                    >
                                        {{ item.title }}
                                    </h4>
                                    <div
                                        class="flex flex-wrap items-center gap-x-2.5 gap-y-0.5 mt-1 text-[10.5px] text-slate-600 font-medium"
                                    >
                                        <span class="inline-flex items-center gap-1 truncate">
                                            <span class="material-symbols-outlined text-[12px] text-emerald-700">schedule</span>
                                            {{ item.time }}
                                        </span>
                                        <span class="hidden sm:inline-flex items-center gap-1 truncate">
                                            <span class="material-symbols-outlined text-[12px] text-emerald-700">location_on</span>
                                            {{ item.location }}
                                        </span>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Karakter Maskot 3D Clay (Laki-laki / Perempuan Berkerudung) -->
                <div class="col-span-4 sm:col-span-4 md:col-span-4 lg:col-span-4 flex justify-end items-end relative z-20">
                    <div class="relative hero-character-wrapper">
                        <!-- Cahaya Lembut di Belakang Karakter 3D -->
                        <div
                            class="absolute inset-x-2 bottom-2 h-24 sm:h-32 rounded-full bg-white/35 blur-2xl pointer-events-none"
                        ></div>

                        <!-- Gambar Karakter 3D Clay Transparan -->
                        <Transition
                            mode="out-in"
                            enter-active-class="transition duration-300 ease-out"
                            enter-from-class="opacity-0 translate-y-3 scale-95"
                            enter-to-class="opacity-100 translate-y-0 scale-100"
                            leave-active-class="transition duration-150 ease-in"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <img
                                :key="characterUrl"
                                :src="characterUrl"
                                :alt="activeGender === 'P' ? 'Karakter 3D Pegawai Putri' : 'Karakter 3D Pegawai Putra'"
                                class="hero-character-3d relative z-10 h-36 sm:h-48 md:h-56 lg:h-60 w-auto object-contain select-none pointer-events-none drop-shadow-[0_14px_20px_rgba(15,23,42,0.38)] -mb-3 sm:-mb-4"
                            />
                        </Transition>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
/* Animasi mengambang/bernapas halus pada karakter 3D */
@keyframes heroFloat {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
    }
    50% {
        transform: translateY(-6px) rotate(0.8deg);
    }
}

@keyframes cloudDriftSlow {
    0%, 100% {
        transform: translateX(0px);
    }
    50% {
        transform: translateX(18px);
    }
}

@keyframes cloudDriftFast {
    0%, 100% {
        transform: translateX(0px);
    }
    50% {
        transform: translateX(-14px);
    }
}

.hero-character-3d {
    animation: heroFloat 4.2s ease-in-out infinite;
    transform-origin: bottom center;
}

.hero-cloud-slow {
    animation: cloudDriftSlow 9s ease-in-out infinite;
}

.hero-cloud-fast {
    animation: cloudDriftFast 7s ease-in-out infinite;
}
</style>
