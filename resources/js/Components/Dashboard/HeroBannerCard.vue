<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import PeekingMascot2D from '@/Components/Dashboard/PeekingMascot2D.vue';
import RidingMascot2D from '@/Components/Dashboard/RidingMascot2D.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user || {});

// Deteksi otomatis Unit (SDIT vs SMPIT/PPTQ) & Jenis Kelamin (L vs P)
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

// Urutan & konfigurasi tema suasana waktu (Pagi, Siang, Sore, Malam)
const TIME_THEMES = ['pagi', 'siang', 'sore', 'malam'];

const TIME_META = {
    pagi: {
        label: 'Pagi',
        greeting: 'Selamat pagi',
        sectionBg: 'bg-gradient-to-b from-[#cfe9fc] via-[#eef7ff] to-[#fff4e4] md:border-sky-200/80',
        scrim: 'from-white/40 via-amber-50/10 to-transparent',
        headingText: 'text-slate-900',
        dateText: 'text-slate-800',
        pillButton: 'bg-white/85 hover:bg-white text-slate-800 border-white shadow-sm focus-visible:ring-slate-800',
    },
    siang: {
        label: 'Siang',
        greeting: 'Selamat siang',
        sectionBg: 'bg-gradient-to-b from-[#bae0fd] via-[#ddf1fe] to-[#eef8ff] md:border-sky-200/80',
        scrim: 'from-white/45 via-sky-50/15 to-transparent',
        headingText: 'text-slate-900',
        dateText: 'text-slate-800',
        pillButton: 'bg-white/85 hover:bg-white text-slate-800 border-white shadow-sm focus-visible:ring-slate-800',
    },
    sore: {
        label: 'Sore',
        greeting: 'Selamat sore',
        sectionBg: 'bg-gradient-to-b from-[#fde0b6] via-[#ffedd0] to-[#fff5e4] md:border-amber-200/80',
        scrim: 'from-amber-50/45 via-orange-50/10 to-transparent',
        headingText: 'text-slate-900',
        dateText: 'text-slate-800',
        pillButton: 'bg-white/85 hover:bg-white text-slate-800 border-white shadow-sm focus-visible:ring-slate-800',
    },
    malam: {
        label: 'Malam',
        greeting: 'Selamat malam',
        sectionBg: 'bg-gradient-to-b from-[#0b1633] via-[#13254c] to-[#1d3464] md:border-indigo-900/60',
        scrim: 'from-slate-950/45 via-slate-950/15 to-transparent',
        headingText: 'text-white drop-shadow-[0_1px_6px_rgba(8,15,35,0.45)]',
        dateText: 'text-slate-200',
        pillButton: 'bg-slate-900/65 hover:bg-slate-900/85 text-white border-white/25 backdrop-blur-sm shadow-sm focus-visible:ring-white',
    },
};

const HERO_BACKGROUNDS = {
    sdit: {
        pagi: '/images/hero/bg-sdit-pagi.jpg',
        siang: '/images/hero/bg-sdit-2d.jpg',
        sore: '/images/hero/bg-sdit-sore.jpg',
        malam: '/images/hero/bg-sdit-malam.jpg',
    },
    smpit: {
        pagi: '/images/hero/bg-smpit-pagi.jpg',
        siang: '/images/hero/bg-smpit-2d.jpg',
        sore: '/images/hero/bg-smpit-sore.jpg',
        malam: '/images/hero/bg-smpit-malam.jpg',
    },
};

// Sapaan waktu & tanggal realtime Bahasa Indonesia
const now = ref(new Date());
let clockInterval = null;

const detectedTimeTheme = computed(() => {
    const hour = now.value.getHours();
    if (hour >= 4 && hour < 11) {
        return 'pagi';
    }
    if (hour >= 11 && hour < 15) {
        return 'siang';
    }
    if (hour >= 15 && hour < 18) {
        return 'sore';
    }
    return 'malam';
});

const previewUnitOverride = ref(null);
const previewTimeOverride = ref(null);
const previewGenderOverride = ref(null);

const activeUnitTheme = computed(() => previewUnitOverride.value || detectedUnitTheme.value);
const activeTimeTheme = computed(() => previewTimeOverride.value || detectedTimeTheme.value);
const activeGender = computed(() => previewGenderOverride.value || detectedGender.value);

const currentTimeMeta = computed(() => TIME_META[activeTimeTheme.value] || TIME_META.siang);
const greetingText = computed(() => currentTimeMeta.value.greeting);

const isSuperAdmin = computed(() => {
    const role = String(user.value?.role || '').toLowerCase();
    return role === 'superadmin' || !user.value?.unit;
});

function cycleUnitTheme() {
    previewUnitOverride.value = activeUnitTheme.value === 'sdit' ? 'smpit' : 'sdit';
}

function cycleTimeTheme() {
    const currentIndex = TIME_THEMES.indexOf(activeTimeTheme.value);
    const nextIndex = (currentIndex + 1) % TIME_THEMES.length;
    previewTimeOverride.value = TIME_THEMES[nextIndex];
}

function cycleGender() {
    previewGenderOverride.value = activeGender.value === 'L' ? 'P' : 'L';
}

const backgroundUrl = computed(() => {
    const unitMap = HERO_BACKGROUNDS[activeUnitTheme.value] || HERO_BACKGROUNDS.sdit;
    return unitMap[activeTimeTheme.value] || unitMap.siang;
});

const unitAltText = computed(() => {
    const unitLabel = activeUnitTheme.value === 'smpit'
        ? 'SMPIT dan PPTQ Ulil Albab'
        : 'SDIT Ulil Albab';
    return `Ilustrasi 2D Gedung ${unitLabel} suasana ${currentTimeMeta.value.label.toLowerCase()}`;
});

const shortDisplayName = computed(() => {
    const fullName = String(user.value?.name || 'Pegawai').trim();
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

// 2 Agenda Terdekat (Otomatis bergeser halus antara agenda 1 & 2)
const upcomingAgendas = [
    {
        id: 1,
        title: 'Rapat Koordinasi Bulanan Yayasan',
        meta: '10 Nov · 08:00 - 10:00 WIB · Ruang Rapat Utama',
    },
    {
        id: 2,
        title: 'Pelaksanaan Penilaian Tengah Semester',
        meta: '15 Nov · 07:30 WIB - Selesai · Seluruh Unit',
    },
];

// Mood karakter, diganti bergiliran lewat satu tombol
const MOOD_OPTIONS = [
    { id: 'senang', label: 'Senang', icon: 'sentiment_satisfied' },
    { id: 'semangat', label: 'Semangat', icon: 'bolt' },
    { id: 'sedih', label: 'Sedih', icon: 'sentiment_dissatisfied' },
    { id: 'capek', label: 'Capek', icon: 'bedtime' },
    { id: 'gaspol', label: 'Gaspol', icon: 'two_wheeler' },
];

const currentMood = ref('senang');
const isRidingPose = computed(() => currentMood.value === 'gaspol');

const currentMoodIndex = computed(() => MOOD_OPTIONS.findIndex((m) => m.id === currentMood.value));
const currentMoodLabel = computed(() => MOOD_OPTIONS[currentMoodIndex.value]?.label || 'Senang');
const currentMoodIcon = computed(() => MOOD_OPTIONS[currentMoodIndex.value]?.icon || 'sentiment_satisfied');

const moodButtonTone = computed(() => (activeTimeTheme.value === 'malam'
    ? 'bg-purple-950/60 border-purple-400/40 text-purple-200 hover:bg-purple-950/75 focus-visible:ring-purple-300'
    : 'bg-purple-100/75 border-purple-300/80 text-purple-950 hover:bg-purple-100/90 focus-visible:ring-purple-700'));

function cycleMood() {
    const nextIndex = (currentMoodIndex.value + 1) % MOOD_OPTIONS.length;
    setMood(MOOD_OPTIONS[nextIndex].id);
}

function setMood(moodId) {
    currentMood.value = moodId;
    if (typeof window !== 'undefined' && window.localStorage) {
        try {
            localStorage.setItem('user_mascot_mood', moodId);
        } catch (e) {
            // Ignore storage errors
        }
    }
}

const activeAgendaIndex = ref(0);
const isSliderPaused = ref(false);
let agendaSlideInterval = null;
let preloadTimer = null;

const currentAgenda = computed(() => upcomingAgendas[activeAgendaIndex.value]);

function nextAgenda() {
    activeAgendaIndex.value = (activeAgendaIndex.value + 1) % upcomingAgendas.length;
}

function preloadPreviewBackgrounds() {
    Object.values(HERO_BACKGROUNDS).forEach((unitImages) => {
        Object.values(unitImages).forEach((src) => {
            const img = new Image();
            img.src = src;
        });
    });
}

onMounted(() => {
    if (typeof window !== 'undefined' && window.localStorage) {
        try {
            const savedMood = localStorage.getItem('user_mascot_mood');
            if (savedMood && MOOD_OPTIONS.some(m => m.id === savedMood)) {
                currentMood.value = savedMood;
            }
        } catch (e) {
            // Ignore
        }
    }

    clockInterval = setInterval(() => {
        now.value = new Date();
    }, 60000);

    agendaSlideInterval = setInterval(() => {
        if (!isSliderPaused.value) {
            nextAgenda();
        }
    }, 4200);

    if (isSuperAdmin.value && typeof window !== 'undefined') {
        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(() => preloadPreviewBackgrounds());
        } else {
            preloadTimer = setTimeout(preloadPreviewBackgrounds, 1200);
        }
    }
});

onUnmounted(() => {
    if (clockInterval) {
        clearInterval(clockInterval);
    }
    if (agendaSlideInterval) {
        clearInterval(agendaSlideInterval);
    }
    if (preloadTimer) {
        clearTimeout(preloadTimer);
    }
});
</script>

<template>
    <section
        :class="[
            'relative w-full overflow-hidden min-h-[55dvh] sm:min-h-[55dvh] md:min-h-0 md:rounded-[28px] md:border md:shadow-sm transition-colors duration-500',
            currentTimeMeta.sectionBg,
        ]"
    >
        <!-- Latar Ilustrasi 2D Lingkungan Sekolah Sesuai Waktu (Pagi, Siang, Sore, Malam) -->
        <div class="absolute inset-0 z-0">
            <Transition name="hero-bg-fade">
                <img
                    :key="backgroundUrl"
                    :src="backgroundUrl"
                    :alt="unitAltText"
                    class="absolute inset-0 w-full h-full object-cover object-[58%_85%] md:object-[center_78%]"
                />
            </Transition>
            <!-- Kabut Kontras Halus di Kiri Atas Sesuai Suasana Waktu -->
            <div
                :class="[
                    'absolute inset-0 bg-gradient-to-br pointer-events-none transition-colors duration-500',
                    currentTimeMeta.scrim,
                ]"
            ></div>
        </div>

        <!-- Konten Utama: 55% Porsi Layar pada Mobile, Proporsional pada Desktop -->
        <div
            class="relative z-10 px-4 pt-16 pb-12 sm:px-6 md:px-8 md:pt-7 md:pb-7 flex flex-col justify-between min-h-[55dvh] sm:min-h-[55dvh] md:min-h-[300px]"
        >
            <!-- Bagian Atas: Sapaan Sesuai Jam, Tanggal, dan Handle Pratinjau Superadmin -->
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-3">
                <div class="max-w-xl">
                    <h1
                        :class="[
                            'font-headline-lg text-[26px] sm:text-3xl md:text-[33px] font-extrabold tracking-tight leading-tight transition-colors duration-300',
                            currentTimeMeta.headingText,
                        ]"
                    >
                        {{ greetingText }}, {{ shortDisplayName }}.
                    </h1>

                    <p
                        :class="[
                            'mt-1 text-[13px] sm:text-sm font-medium transition-colors duration-300',
                            currentTimeMeta.dateText,
                        ]"
                    >
                        {{ formattedToday }}
                    </p>

                    <!-- Kapsul Mood di Bawah Tanggal (Khusus Non-Superadmin) -->
                    <div v-if="!isSuperAdmin" class="mt-2.5 sm:mt-3 flex items-center">
                        <button
                            type="button"
                            @click="cycleMood"
                            :class="[
                                'relative inline-flex items-center gap-2 pl-2.5 pr-3.5 py-1 sm:py-1.5 rounded-[30px] border transition-all duration-200 cursor-pointer active:scale-[0.97] shadow-2xs',
                                moodButtonTone,
                            ]"
                            :aria-label="`Mood hari ini: ${currentMoodLabel}. Klik untuk mengganti.`"
                            title="Klik untuk mengganti mood hari ini"
                        >
                            <div class="relative w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-purple-300/35 flex items-center justify-center shrink-0">
                                <Transition name="mood-label" mode="out-in">
                                    <span :key="currentMood" class="material-symbols-outlined text-[16px] sm:text-[18px]">
                                        {{ currentMoodIcon }}
                                    </span>
                                </Transition>
                            </div>
                            <div class="relative flex flex-col text-left leading-none">
                                <span class="text-[9px] sm:text-[9.5px] font-medium tracking-wide opacity-75 mb-0.5">
                                    Mood hari ini
                                </span>
                                <Transition name="mood-label" mode="out-in">
                                    <span :key="currentMood" class="text-xs sm:text-[12.5px] font-bold tracking-tight">
                                        {{ currentMoodLabel }}
                                    </span>
                                </Transition>
                            </div>
                            <span class="relative inline-flex items-center gap-[3px] ml-1 opacity-70" aria-hidden="true">
                                <span
                                    v-for="(m, idx) in MOOD_OPTIONS"
                                    :key="m.id"
                                    :class="[
                                        'block h-1 rounded-full bg-current transition-all duration-300',
                                        currentMoodIndex === idx ? 'w-2.5 opacity-90' : 'w-1 opacity-30',
                                    ]"
                                ></span>
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Handle Pratinjau Superadmin: Kapsul Mood (Sebelum Latar), Latar, Suasana Waktu, & Karakter -->
                <div
                    v-if="isSuperAdmin"
                    class="w-full md:w-auto -mx-4 px-4 sm:-mx-6 sm:px-6 md:mx-0 md:px-0 overflow-x-auto no-scrollbar flex items-center md:flex-wrap gap-1.5 sm:gap-2 self-start md:justify-end py-1 shrink-0"
                >
                    <!-- Kapsul Mood (Sebelum Kapsul Latar) -->
                    <button
                        type="button"
                        @click="cycleMood"
                        :class="[
                            'relative inline-flex items-center gap-2 pl-2.5 pr-3.5 py-1 sm:py-1.5 rounded-[30px] border transition-all duration-200 cursor-pointer active:scale-[0.97] shadow-2xs shrink-0',
                            moodButtonTone,
                        ]"
                        :aria-label="`Mood hari ini: ${currentMoodLabel}. Klik untuk mengganti.`"
                        title="Klik untuk mengganti mood hari ini"
                    >
                        <div class="relative w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-purple-300/35 flex items-center justify-center shrink-0">
                            <Transition name="mood-label" mode="out-in">
                                <span :key="currentMood" class="material-symbols-outlined text-[16px] sm:text-[18px]">
                                    {{ currentMoodIcon }}
                                </span>
                            </Transition>
                        </div>
                        <div class="relative flex flex-col text-left leading-none">
                            <span class="text-[9px] sm:text-[9.5px] font-medium tracking-wide opacity-75 mb-0.5">
                                Mood hari ini
                            </span>
                            <Transition name="mood-label" mode="out-in">
                                <span :key="currentMood" class="text-xs sm:text-[12.5px] font-bold tracking-tight">
                                    {{ currentMoodLabel }}
                                </span>
                            </Transition>
                        </div>
                        <span class="relative inline-flex items-center gap-[3px] ml-1 opacity-70" aria-hidden="true">
                            <span
                                v-for="(m, idx) in MOOD_OPTIONS"
                                :key="m.id"
                                :class="[
                                    'block h-1 rounded-full bg-current transition-all duration-300',
                                    currentMoodIndex === idx ? 'w-2.5 opacity-90' : 'w-1 opacity-30',
                                ]"
                            ></span>
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="cycleUnitTheme"
                        :class="[
                            'min-h-[38px] sm:min-h-[36px] px-3 sm:px-3.5 py-1.5 rounded-[9999px] text-xs font-semibold border transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 shrink-0',
                            currentTimeMeta.pillButton,
                        ]"
                        title="Ganti pratinjau latar gedung (SDIT / SMPIT)"
                    >
                        {{ activeUnitTheme === 'sdit' ? 'Latar: SDIT' : 'Latar: SMPIT' }}
                    </button>
                    <button
                        type="button"
                        @click="cycleTimeTheme"
                        :class="[
                            'min-h-[38px] sm:min-h-[36px] px-3 sm:px-3.5 py-1.5 rounded-[9999px] text-xs font-semibold border transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 shrink-0',
                            currentTimeMeta.pillButton,
                        ]"
                        title="Ganti pratinjau suasana waktu (Pagi / Siang / Sore / Malam)"
                    >
                        Waktu: {{ currentTimeMeta.label }}
                    </button>
                    <button
                        type="button"
                        @click="cycleGender"
                        :class="[
                            'min-h-[38px] sm:min-h-[36px] px-3 sm:px-3.5 py-1.5 rounded-[9999px] text-xs font-semibold border transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 shrink-0',
                            currentTimeMeta.pillButton,
                        ]"
                        title="Ganti pratinjau karakter (Putra / Putri)"
                    >
                        {{ activeGender === 'L' ? 'Karakter: Putra' : 'Karakter: Putri' }}
                    </button>
                </div>
            </div>

            <!-- Bagian Bawah: Spacer Luar Terpisah agar Padding Tidak Menggeser Posisi Mengintip Maskot -->
            <div
                :class="[
                    'mt-auto md:pt-2 w-full md:max-w-xl transition-[padding] duration-300',
                    isRidingPose ? 'pt-20 sm:pt-24 md:mt-20' : 'pt-14 sm:pt-16 md:mt-16',
                ]"
            >
                <div
                    class="relative w-full"
                    @mouseenter="isSliderPaused = true"
                    @mouseleave="isSliderPaused = false"
                    @focusin="isSliderPaused = true"
                    @focusout="isSliderPaused = false"
                >
                    <!-- Karakter 2D di Atas Garis Banner Agenda: Gaspol (Motor Matic) vs Senang/Semangat/Sedih/Capek (Mengintip) -->
                    <Transition
                        mode="out-in"
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 translate-y-3"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 translate-y-2"
                    >
                        <div
                            v-if="isRidingPose"
                            :key="`ride-${activeGender}`"
                            class="absolute right-2 sm:right-6 bottom-full translate-y-[4.5%] z-30"
                        >
                            <RidingMascot2D
                                :gender="activeGender"
                                :mood="currentMood"
                                :is-night="activeTimeTheme === 'malam'"
                            />
                        </div>

                        <div
                            v-else
                            :key="`peek-${activeGender}-${currentMood}`"
                            class="absolute right-12 sm:right-14 bottom-full translate-y-[10.8%] z-30"
                        >
                            <PeekingMascot2D
                                :gender="activeGender"
                                :mood="currentMood"
                            />
                        </div>
                    </Transition>

                    <!-- Banner Agenda Putih Minimalis (Auto-Slide Agenda 1 & 2) -->
                    <Link
                        href="/agenda"
                        class="group relative z-20 flex items-center justify-between gap-3 rounded-[30px] bg-white/95 backdrop-blur-sm pl-5 pr-2 py-2 shadow-[0_8px_28px_rgba(15,23,42,0.12)] border border-white transition-shadow hover:shadow-[0_10px_32px_rgba(15,23,42,0.16)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-900"
                    >
                        <!-- Konten Teks Agenda yang Bergeser Otomatis -->
                        <div class="min-w-0 flex-1 overflow-hidden py-0.5">
                            <Transition name="agenda-slide" mode="out-in">
                                <div :key="currentAgenda.id" class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <!-- Indikator 2 Slide Minimalis -->
                                        <span class="inline-flex items-center gap-1 shrink-0" aria-hidden="true">
                                            <span
                                                v-for="(item, idx) in upcomingAgendas"
                                                :key="item.id"
                                                :class="[
                                                    'block h-1 rounded-[9999px] transition-all duration-300',
                                                    activeAgendaIndex === idx
                                                        ? 'w-3.5 bg-slate-800'
                                                        : 'w-1 bg-slate-300'
                                                ]"
                                            ></span>
                                        </span>
                                        <p class="text-[11px] sm:text-xs font-medium text-slate-600 truncate">
                                            {{ currentAgenda.meta }}
                                        </p>
                                    </div>
                                    <p
                                        class="text-[13.5px] sm:text-[15px] font-semibold text-slate-900 truncate mt-0.5 group-hover:text-slate-700 transition-colors"
                                    >
                                        {{ currentAgenda.title }}
                                    </p>
                                </div>
                            </Transition>
                        </div>

                        <!-- Tombol Lingkaran Hitam Konsentris -->
                        <span
                            class="w-11 h-11 rounded-[9999px] bg-slate-900 group-hover:bg-slate-800 text-white flex items-center justify-center shrink-0 transition-transform group-active:scale-95"
                            aria-hidden="true"
                        >
                            <svg
                                viewBox="0 0 20 20"
                                fill="none"
                                class="w-4 h-4"
                            >
                                <path
                                    d="M5 10H15M15 10L10.5 5.5M15 10L10.5 14.5"
                                    stroke="currentColor"
                                    stroke-width="1.9"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </span>
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.hero-bg-fade-enter-active,
.hero-bg-fade-leave-active {
    transition: opacity 0.45s ease;
}

.hero-bg-fade-enter-from,
.hero-bg-fade-leave-to {
    opacity: 0;
}

.agenda-slide-enter-active,
.agenda-slide-leave-active {
    transition: opacity 0.28s ease, transform 0.28s ease;
}

.agenda-slide-enter-from {
    opacity: 0;
    transform: translateY(8px);
}

.agenda-slide-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

.mood-label-enter-active,
.mood-label-leave-active {
    transition: opacity 0.18s ease, transform 0.18s ease;
}

.mood-label-enter-from {
    opacity: 0;
    transform: translateY(5px);
}

.mood-label-leave-to {
    opacity: 0;
    transform: translateY(-5px);
}

@media (prefers-reduced-motion: reduce) {
    .hero-bg-fade-enter-active,
    .hero-bg-fade-leave-active,
    .agenda-slide-enter-active,
    .agenda-slide-leave-active {
        transition: opacity 0.15s ease;
    }

    .agenda-slide-enter-from,
    .agenda-slide-leave-to {
        transform: none;
    }
}
</style>
