<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import PeekingMascot2D from '@/Components/Dashboard/PeekingMascot2D.vue';

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

const backgroundUrl = computed(() => {
    return activeUnitTheme.value === 'smpit'
        ? '/images/hero/bg-smpit-2d.jpg'
        : '/images/hero/bg-sdit-2d.jpg';
});

const unitAltText = computed(() => {
    return activeUnitTheme.value === 'smpit'
        ? 'Ilustrasi 2D Gedung SMPIT dan PPTQ Ulil Albab'
        : 'Ilustrasi 2D Gedung SDIT Ulil Albab';
});

// Sapaan waktu & tanggal realtime Bahasa Indonesia
const now = ref(new Date());
let clockInterval = null;

const greetingText = computed(() => {
    const hour = now.value.getHours();
    if (hour >= 4 && hour < 11) {
        return 'Selamat pagi';
    }
    if (hour >= 11 && hour < 15) {
        return 'Selamat siang';
    }
    if (hour >= 15 && hour < 18) {
        return 'Selamat sore';
    }
    return 'Selamat malam';
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

const activeAgendaIndex = ref(0);
const isSliderPaused = ref(false);
let agendaSlideInterval = null;

const currentAgenda = computed(() => upcomingAgendas[activeAgendaIndex.value]);

function nextAgenda() {
    activeAgendaIndex.value = (activeAgendaIndex.value + 1) % upcomingAgendas.length;
}

onMounted(() => {
    clockInterval = setInterval(() => {
        now.value = new Date();
    }, 60000);

    agendaSlideInterval = setInterval(() => {
        if (!isSliderPaused.value) {
            nextAgenda();
        }
    }, 4200);
});

onUnmounted(() => {
    if (clockInterval) {
        clearInterval(clockInterval);
    }
    if (agendaSlideInterval) {
        clearInterval(agendaSlideInterval);
    }
});
</script>

<template>
    <section
        class="relative w-full overflow-hidden bg-gradient-to-b from-[#bae0fd] via-[#ddf1fe] to-[#eef8ff] min-h-[55dvh] sm:min-h-[55dvh] md:min-h-0 md:rounded-[28px] md:border md:border-sky-200/80 md:shadow-sm"
    >
        <!-- Latar Ilustrasi 2D Cerah & Bahagia (SDIT / SMPIT & PPTQ) -->
        <div class="absolute inset-0 z-0">
            <img
                :src="backgroundUrl"
                :alt="unitAltText"
                class="w-full h-full object-cover object-[58%_85%] md:object-[center_78%] transition-opacity duration-500"
            />
            <!-- Kabut Cahaya Lembut di Kiri Atas agar Teks Sapaan Gelap Kontras Tinggi -->
            <div
                class="absolute inset-0 bg-gradient-to-br from-white/45 via-sky-50/15 to-transparent pointer-events-none"
            ></div>
        </div>

        <!-- Konten Utama: 55% Porsi Layar pada Mobile, Proporsional pada Desktop -->
        <div
            class="relative z-10 px-4 pt-16 pb-12 sm:px-6 md:px-8 md:pt-7 md:pb-7 flex flex-col justify-between min-h-[55dvh] sm:min-h-[55dvh] md:min-h-[300px]"
        >
            <!-- Bagian Atas: Sapaan, Tanggal, dan Kontrol Pratinjau Ringkas -->
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-3">
                <div class="max-w-xl">
                    <h1
                        class="font-headline-lg text-[26px] sm:text-3xl md:text-[33px] font-extrabold text-slate-900 tracking-tight leading-tight"
                    >
                        {{ greetingText }}, {{ shortDisplayName }}.
                    </h1>

                    <p class="mt-1 text-[13px] sm:text-sm font-medium text-slate-700">
                        {{ formattedToday }}
                    </p>
                </div>

                <!-- Tombol Pratinjau Sederhana (Khusus Superadmin untuk Cek Latar & Karakter) -->
                <div
                    v-if="isSuperAdmin"
                    class="inline-flex items-center gap-2 self-start"
                >
                    <button
                        type="button"
                        @click="cycleUnitTheme"
                        class="min-h-[36px] px-3.5 py-1.5 rounded-[9999px] text-xs font-semibold bg-white/80 hover:bg-white text-slate-700 border border-white shadow-sm transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-800"
                        title="Ganti pratinjau latar gedung"
                    >
                        {{ activeUnitTheme === 'sdit' ? 'Latar: SDIT' : 'Latar: SMPIT' }}
                    </button>
                    <button
                        type="button"
                        @click="cycleGender"
                        class="min-h-[36px] px-3.5 py-1.5 rounded-[9999px] text-xs font-semibold bg-white/80 hover:bg-white text-slate-700 border border-white shadow-sm transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-800"
                        title="Ganti pratinjau karakter"
                    >
                        {{ activeGender === 'L' ? 'Karakter: Putra' : 'Karakter: Putri' }}
                    </button>
                </div>
            </div>

            <!-- Bagian Bawah: Banner Agenda Auto-Slide + Wajah Karakter 2D Mengintip -->
            <div
                class="relative mt-auto pt-8 sm:pt-10 md:mt-16 md:pt-0 w-full md:max-w-xl"
                @mouseenter="isSliderPaused = true"
                @mouseleave="isSliderPaused = false"
                @focusin="isSliderPaused = true"
                @focusout="isSliderPaused = false"
            >
                <!-- Wajah Karakter 2D Menggemaskan Mengintip di Tepi Atas Banner Agenda -->
                <div
                    class="absolute right-12 sm:right-14 bottom-full translate-y-[10.23%] z-30"
                >
                    <Transition
                        mode="out-in"
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 translate-y-3"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 translate-y-2"
                    >
                        <PeekingMascot2D
                            :key="activeGender"
                            :gender="activeGender"
                        />
                    </Transition>
                </div>

                <!-- Banner Agenda Putih Minimalis seperti Referensi (Auto-Slide Agenda 1 & 2) -->
                <Link
                    href="/agenda"
                    class="group relative z-20 flex items-center justify-between gap-3 rounded-[30px] bg-white/95 backdrop-blur-sm pl-5 pr-2 py-2 shadow-[0_8px_28px_rgba(15,23,42,0.09)] border border-white transition-shadow hover:shadow-[0_10px_32px_rgba(15,23,42,0.13)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-900"
                >
                    <!-- Konten Teks Agenda yang Bergeser Otomatis -->
                    <div class="min-w-0 flex-1 overflow-hidden py-0.5">
                        <Transition name="agenda-slide" mode="out-in">
                            <div :key="currentAgenda.id" class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <!-- Indikator 2 Slide Minimalis tanpa Warna Tambahan -->
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

                    <!-- Tombol Lingkaran Hitam Konsentris seperti Referensi -->
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
    </section>
</template>

<style scoped>
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

@media (prefers-reduced-motion: reduce) {
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
