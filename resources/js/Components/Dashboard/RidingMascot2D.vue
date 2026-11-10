<script setup>
import { ref, computed, onUnmounted } from 'vue';
import MascotFaceFeatures from '@/Components/Dashboard/MascotFaceFeatures.vue';

const props = defineProps({
    gender: {
        type: String,
        default: 'L', // 'L' = Putra, 'P' = Putri
    },
    mood: {
        type: String,
        default: 'gaspol', // 'gaspol' | 'senang' | 'semangat' | 'sedih' | 'capek'
    },
    isNight: {
        type: Boolean,
        default: false,
    },
});

const uid = computed(() => `ride-${props.gender}-${props.mood}`);

/**
 * Saat mood 'gaspol', gunakan ekspresi wajah 'semangat' dari MascotFaceFeatures
 * agar wajah tetap identik dengan karakter mengintip namun penuh antusiasme.
 */
const faceMood = computed(() => (props.mood === 'gaspol' ? 'semangat' : props.mood));

/**
 * Palet per karakter: Putra (motor biru sporty, helm kuning), Putri (motor pink cerah, helm putih).
 */
const palette = computed(() => (props.gender === 'P'
    ? {
        bodyLight: '#F472B6',
        bodyDark: '#DB2777',
        accent: '#FEF08A',
        helmetLight: '#FFFFFF',
        helmetDark: '#E2E8F0',
        helmetStripe: '#EC4899',
        outfit: '#0F766E',
        outfitShade: '#115E59',
        legs: '#0F766E',
    }
    : {
        bodyLight: '#3B82F6',
        bodyDark: '#1D4ED8',
        accent: '#FACC15',
        helmetLight: '#FDE047',
        helmetDark: '#EAB308',
        helmetStripe: '#1D4ED8',
        outfit: '#F8FAFC',
        outfitShade: '#CBD5E1',
        legs: '#334155',
    }));

const isWinking = ref(false);
let winkTimeout = null;

function triggerHappySquint() {
    if (winkTimeout) {
        clearTimeout(winkTimeout);
    }
    isWinking.value = true;
    winkTimeout = setTimeout(() => {
        isWinking.value = false;
    }, 650);
}

onUnmounted(() => {
    if (winkTimeout) {
        clearTimeout(winkTimeout);
    }
});
</script>

<template>
    <div
        class="riding-mascot-root select-none cursor-pointer"
        :title="gender === 'P' ? 'Karakter Putri — Gaspol!' : 'Karakter Putra — Gaspol!'"
        @click.stop.prevent="triggerHappySquint"
    >
        <svg
            viewBox="-12 -14 285 138"
            class="w-[175px] sm:w-[215px] aspect-[285/138] h-auto overflow-visible block"
            aria-hidden="true"
        >
            <defs>
                <linearGradient :id="`${uid}-skin`" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#FFEAD6" />
                    <stop offset="100%" stop-color="#FFD5B5" />
                </linearGradient>
                <linearGradient :id="`${uid}-hijab`" x1="15%" y1="0%" x2="85%" y2="100%">
                    <stop offset="0%" stop-color="#38B2AC" />
                    <stop offset="100%" stop-color="#228B68" />
                </linearGradient>
                <linearGradient :id="`${uid}-helmet`" x1="20%" y1="0%" x2="80%" y2="100%">
                    <stop offset="0%" :stop-color="palette.helmetLight" />
                    <stop offset="100%" :stop-color="palette.helmetDark" />
                </linearGradient>
                <linearGradient :id="`${uid}-body`" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" :stop-color="palette.bodyLight" />
                    <stop offset="100%" :stop-color="palette.bodyDark" />
                </linearGradient>
                <linearGradient :id="`${uid}-beam`" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="#FEF08A" stop-opacity="0.85" />
                    <stop offset="100%" stop-color="#FEF08A" stop-opacity="0" />
                </linearGradient>
                <linearGradient :id="`${uid}-flame`" x1="100%" y1="50%" x2="0%" y2="50%">
                    <stop offset="0%" stop-color="#FEF08A" />
                    <stop offset="45%" stop-color="#F97316" />
                    <stop offset="100%" stop-color="#EF4444" stop-opacity="0" />
                </linearGradient>
            </defs>

            <!-- Grup gerak horizontal: Blayer -> GASPOL -> Ngerem Mendadak -> Mundur (looping) -->
            <g class="ride-move">
                <!-- Bayangan tetap menapak di atas garis banner -->
                <ellipse class="ride-shadow" cx="84" cy="121" rx="60" ry="2.8" fill="#0F172A" opacity="0.16" />

                <!-- Kepulan asap tebal knalpot saat blayer & gaspol -->
                <g fill="#E2E8F0">
                    <circle class="ride-puff ride-puff-1" cx="10" cy="106" r="5.5" />
                    <circle class="ride-puff ride-puff-2" cx="6" cy="104" r="7" />
                    <circle class="ride-puff ride-puff-3" cx="2" cy="101" r="6" />
                    <circle class="ride-puff ride-puff-4" cx="-4" cy="98" r="8" />
                </g>

                <!-- Asap decitan ban depan & belakang saat ngerem mendadak -->
                <g fill="#F1F5F9">
                    <circle class="ride-skid ride-skid-1" cx="136" cy="118" r="5.5" />
                    <circle class="ride-skid ride-skid-2" cx="144" cy="115" r="7" />
                    <circle class="ride-skid ride-skid-3" cx="50" cy="118" r="4.8" />
                </g>

                <!-- Garis angin / speed lines kencang saat gaspol -->
                <g class="ride-speed" fill="none" stroke-linecap="round">
                    <path d="M -14 26 L 24 26" stroke="#FBBF24" stroke-width="2.4" />
                    <path d="M -22 48 L 16 48" stroke="#FFFFFF" stroke-width="2.2" opacity="0.9" />
                    <path d="M -18 68 L 12 68" stroke="#38BDF8" stroke-width="2.4" />
                    <path d="M -26 88 L 8 88" stroke="#FBBF24" stroke-width="2" />
                </g>

                <!-- Pivot 1: Wheelie pada poros roda belakang (40, 108) saat ngegas kencang -->
                <g class="ride-wheelie">
                    <!-- Pivot 2: Stoppie / tukik depan pada poros roda depan (126, 108) saat ngerem mendadak -->
                    <g class="ride-stoppie">
                        <!-- Getaran mesin bertenaga -->
                        <g class="ride-idle">
                            <!-- Semburan api turbo dari knalpot saat ngegas -->
                            <g class="ride-flame">
                                <path
                                    d="M 14 103.5 L -16 98 L -8 106.5 L -20 110 L -6 111.5 L -12 115.5 L 14 109.5 Z"
                                    :fill="`url(#${uid}-flame)`"
                                />
                                <path
                                    d="M 14 105 L -4 103.5 L 0 106.8 L -6 108.5 L 14 108.2 Z"
                                    fill="#FEF9C3"
                                />
                            </g>

                            <!-- Sorot lampu depan (suasana malam atau efek turbo) -->
                            <path
                                v-if="isNight"
                                class="ride-beam"
                                d="M 124 60 L 200 44 L 200 84 L 124 68 Z"
                                :fill="`url(#${uid}-beam)`"
                            />

                            <!-- Roda Belakang -->
                            <g transform="translate(40 108)">
                                <g class="ride-wheel">
                                    <circle r="13" fill="#0F172A" />
                                    <circle r="10.2" fill="none" stroke="#334155" stroke-width="1" stroke-dasharray="4 3" />
                                    <circle r="7.5" fill="#E2E8F0" />
                                    <path d="M 0 -7 L 0 7 M -6.06 -3.5 L 6.06 3.5 M -6.06 3.5 L 6.06 -3.5" stroke="#475569" stroke-width="1.8" stroke-linecap="round" />
                                    <circle r="2.6" :fill="palette.bodyDark" />
                                </g>
                            </g>

                            <!-- Roda Depan -->
                            <g transform="translate(126 108)">
                                <g class="ride-wheel">
                                    <circle r="13" fill="#0F172A" />
                                    <circle r="10.2" fill="none" stroke="#334155" stroke-width="1" stroke-dasharray="4 3" />
                                    <circle r="7.5" fill="#E2E8F0" />
                                    <path d="M 0 -7 L 0 7 M -6.06 -3.5 L 6.06 3.5 M -6.06 3.5 L 6.06 -3.5" stroke="#475569" stroke-width="1.8" stroke-linecap="round" />
                                    <circle r="2.6" :fill="palette.bodyDark" />
                                </g>
                            </g>

                            <!-- Garpu depan & shockbreaker -->
                            <path d="M 117 62 L 126 108" stroke="#94A3B8" stroke-width="3.4" stroke-linecap="round" />
                            <circle cx="123.5" cy="105" r="3.2" fill="#EF4444" />

                            <!-- Mesin CVT & lengan ayun -->
                            <path d="M 36 104 L 66 99 Q 73 99 72 106 L 70 110 L 40 112 Z" fill="#334155" />
                            <circle cx="46" cy="106.5" r="3.2" fill="#64748B" />

                            <!-- Knalpot racing sporty -->
                            <path d="M 13 101.5 L 39 104 L 39 109.5 L 13 108.5 Z" fill="#64748B" />
                            <rect x="10.5" y="101.5" width="4.5" height="7" rx="1.5" fill="#1E293B" />

                            <!-- Bodi belakang motor matic -->
                            <path
                                d="M 16 94 C 14 81, 24 73, 40 73 L 82 73 C 86 73, 88 76, 88 80 L 88 92 C 74 94, 66 96, 60 100 L 56 102 C 52 94, 46 90, 40 90 C 32 90, 26 94, 24 100 Z"
                                :fill="`url(#${uid}-body)`"
                            />
                            <!-- Striping sporty di bodi belakang -->
                            <path d="M 24 83 L 78 78.5" fill="none" :stroke="palette.accent" stroke-width="2.6" stroke-linecap="round" />
                            <path d="M 30 86.5 L 72 83" fill="none" stroke="#FFFFFF" stroke-width="1.6" stroke-linecap="round" opacity="0.85" />

                            <!-- Lampu rem belakang + efek kilau merah saat mengerem -->
                            <ellipse cx="17.5" cy="84.5" rx="2.8" ry="3.8" fill="#DC2626" />
                            <circle class="ride-brakelight" cx="15.5" cy="84.5" r="9" fill="#EF4444" />
                            <circle class="ride-brakelight" cx="15.5" cy="84.5" r="4.5" fill="#FCA5A5" />

                            <!-- Jok motor -->
                            <path d="M 34 73 C 34 67, 40 65, 48 65 L 80 66 C 86 66, 88 69, 88 73 Z" fill="#1E293B" />

                            <!-- Dek bawah / pijakan kaki -->
                            <path d="M 56 102 L 61 97 L 108 95 L 112 101 Z" :fill="palette.bodyDark" />
                            <rect x="74" y="93" width="34" height="4" rx="2" fill="#334155" />

                            <!-- ================= PENGENDARA ================= -->
                            <!-- Kaki -->
                            <path d="M 62 69 L 91 70" fill="none" :stroke="palette.legs" stroke-width="10" stroke-linecap="round" />
                            <path d="M 91 70 L 95 91" fill="none" :stroke="palette.legs" stroke-width="8.5" stroke-linecap="round" />
                            <ellipse cx="98" cy="92.5" rx="6.2" ry="3.3" fill="#111827" />

                            <!-- Tameng depan aerodinamis -->
                            <path
                                d="M 104 98 L 100 64 C 100 56, 104 51, 111 49 L 117 48 L 125 72 C 131 82, 134 88, 136 95 L 112 99 Z"
                                :fill="`url(#${uid}-body)`"
                            />
                            <path d="M 106 60 L 118 76" fill="none" :stroke="palette.accent" stroke-width="2.2" stroke-linecap="round" />

                            <!-- Spakbor depan -->
                            <path
                                d="M 112 100 C 114 90, 120 86, 126 86 C 133 86, 139 90, 141 99 L 137 99 C 135 93, 131 90, 126 90 C 121 90, 117 93, 115 100 Z"
                                :fill="palette.bodyDark"
                            />

                            <!-- Lampu depan LED -->
                            <ellipse cx="123" cy="64" rx="3.2" ry="4.8" fill="#FEF08A" stroke="#EAB308" stroke-width="1.2" />

                            <!-- Batang setang & cover setang -->
                            <path d="M 107 52 L 111 38 L 118 38 L 116 52 Z" :fill="palette.bodyDark" />
                            <rect x="104" y="33" width="21" height="6.5" rx="3.2" :fill="`url(#${uid}-body)`" />
                            <!-- Spion -->
                            <path d="M 117 33 L 121 23" stroke="#475569" stroke-width="1.6" stroke-linecap="round" />
                            <ellipse cx="122" cy="21" rx="3.4" ry="2.6" fill="#CBD5E1" stroke="#475569" stroke-width="1.2" />
                            <!-- Handgrip gas -->
                            <rect x="99" y="34.5" width="8" height="4" rx="2" fill="#111827" />

                            <!-- Badan & Lengan (condong dinamis mengikuti tarikan gas & rem) -->
                            <g class="ride-rider">
                                <!-- Badan -->
                                <path
                                    d="M 54 72 C 52 54, 56 42, 66 40 C 75 40, 79 48, 79 58 L 76 72 Z"
                                    :fill="palette.outfit"
                                    :stroke="palette.outfitShade"
                                    stroke-width="1.2"
                                />
                                <!-- Kerudung menjuntai di bahu + berkibar saat ngegas (Putri) -->
                                <path
                                    v-if="gender === 'P'"
                                    d="M 50 45 Q 66 36 81 46 L 80 60 Q 65 67 46 59 Z"
                                    :fill="`url(#${uid}-hijab)`"
                                />
                                <!-- Kancing baju koko (Putra) -->
                                <g v-else fill="#94A3B8">
                                    <circle cx="70" cy="50" r="1" />
                                    <circle cx="70.5" cy="56" r="1" />
                                    <circle cx="71" cy="62" r="1" />
                                </g>

                                <!-- Lengan memegang handgrip gas -->
                                <path d="M 72 48 Q 88 50 101 38.5" fill="none" :stroke="palette.outfitShade" stroke-width="7.4" stroke-linecap="round" />
                                <path d="M 72 48 Q 88 50 101 38.5" fill="none" :stroke="palette.outfit" stroke-width="5.6" stroke-linecap="round" />
                                <circle cx="102.5" cy="37" r="3.6" fill="#FFD8B9" stroke="#F1B38A" stroke-width="1" />

                                <!-- Kepala (wajah sama persis dengan versi mengintip) + helm -->
                                <g class="ride-head">
                                    <g transform="translate(36 -2) scale(0.5)">
                                        <!-- Putri: kerudung, ciput, wajah -->
                                        <template v-if="gender === 'P'">
                                            <path
                                                d="M 60 9 C 88 9, 103 28, 103 57 C 103 69, 99 77, 95 84 C 82 90, 38 90, 25 84 C 21 77, 17 69, 17 57 C 17 28, 32 9, 60 9 Z"
                                                :fill="`url(#${uid}-hijab)`"
                                            />
                                            <path d="M 29 52 C 29 30, 42 22, 60 22 C 78 22, 91 30, 91 52 Z" fill="#FFF7E8" />
                                            <path
                                                d="M 60 28.5 C 78 28.5, 90 41, 90 61 C 90 74, 80 84, 60 84 C 40 84, 30 74, 30 61 C 30 41, 42 28.5, 60 28.5 Z"
                                                :fill="`url(#${uid}-skin)`"
                                            />
                                            <path
                                                d="M 23.5 63 Q 27 72 31 79 M 96.5 63 Q 93 72 89 79"
                                                fill="none"
                                                stroke="#16654A"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                opacity="0.32"
                                            />
                                            <g transform="translate(22, 70)">
                                                <circle cx="0" cy="-3.1" r="2.2" fill="#FEF08A" />
                                                <circle cx="3" cy="-0.9" r="2.2" fill="#FEF08A" />
                                                <circle cx="1.8" cy="2.5" r="2.2" fill="#FEF08A" />
                                                <circle cx="-1.8" cy="2.5" r="2.2" fill="#FEF08A" />
                                                <circle cx="-3" cy="-0.9" r="2.2" fill="#FEF08A" />
                                                <circle cx="0" cy="0" r="1.9" fill="#F59E0B" />
                                            </g>
                                        </template>

                                        <!-- Putra: telinga, wajah, poni -->
                                        <template v-else>
                                            <ellipse cx="22.5" cy="63" rx="6" ry="7" fill="#FFC9A3" />
                                            <ellipse cx="23.5" cy="63" rx="3" ry="3.8" fill="#F3A680" />
                                            <ellipse cx="97.5" cy="63" rx="6" ry="7" fill="#FFC9A3" />
                                            <ellipse cx="96.5" cy="63" rx="3" ry="3.8" fill="#F3A680" />
                                            <path
                                                d="M 60 27 C 81 27, 94 41, 94 61 C 94 75, 81 84.5, 60 84.5 C 39 84.5, 26 75, 26 61 C 26 41, 39 27, 60 27 Z"
                                                :fill="`url(#${uid}-skin)`"
                                            />
                                            <path
                                                d="M 28 31 L 92 31 C 95 38, 95.5 47, 95 56 C 92.5 51, 90.5 45, 88 41 Q 81 44.5, 74 39.5 Q 66 45, 58 40 Q 50 45, 43 39.5 Q 36 44, 32 41 C 29.5 45, 27.5 51, 25 56 C 24.5 47, 25 38, 28 31 Z"
                                                fill="#2C1D17"
                                            />
                                        </template>

                                        <!-- Helm half-face -->
                                        <path
                                            d="M 16 54 C 13 24, 34 3, 60 3 C 86 3, 107 24, 104 54 C 104 58, 100 60, 97 58 C 95 40, 82 30, 60 30 C 38 30, 25 40, 23 58 C 20 60, 16 58, 16 54 Z"
                                            :fill="`url(#${uid}-helmet)`"
                                            stroke="#0F172A"
                                            stroke-opacity="0.14"
                                            stroke-width="1.5"
                                        />
                                        <path d="M 60 4.5 L 60 29" :stroke="palette.helmetStripe" stroke-width="7.5" stroke-linecap="round" />
                                        <!-- Kaca helm (visor) -->
                                        <path d="M 22 31 C 34 18, 86 18, 98 31 L 96 37 C 84 26, 36 26, 24 37 Z" fill="#0F172A" opacity="0.35" />
                                        <path d="M 34 25.5 Q 46 20.5 58 20.5" fill="none" stroke="#FFFFFF" stroke-width="2.2" stroke-linecap="round" opacity="0.65" />
                                        <circle cx="86" cy="15" r="2.8" fill="#FFFFFF" opacity="0.88" />
                                        <!-- Tali dagu -->
                                        <path
                                            d="M 24 58 C 28 75, 42 84.5, 60 85 C 78 84.5, 92 75, 96 58"
                                            fill="none"
                                            stroke="#1F2937"
                                            stroke-width="2.2"
                                            stroke-linecap="round"
                                            opacity="0.55"
                                        />

                                        <!-- Wajah identik dari MascotFaceFeatures -->
                                        <MascotFaceFeatures :gender="gender" :mood="faceMood" :is-winking="isWinking" />
                                    </g>
                                </g>
                            </g>
                        </g>
                    </g>
                </g>
            </g>
        </svg>
    </div>
</template>

<style scoped>
/*
 * Siklus 5.2 detik — "GASPOL!":
 * 0-14%  : Blayer-blayer pemanasan di tempat
 * 14-42% : GASPOL! Akselerasi kencang + wheelie tinggi + api knalpot + speed lines
 * 42-56% : NGEREM MENDADAK! Stoppie (ban belakang terangkat sedikit) + lampu rem + asap ban
 * 56-63% : Berhenti sejenak & tengok belakang
 * 63-93% : MUNDUR sigap kembali ke titik awal
 * 93-100%: Siap gaspol lagi (looping)
 */
@keyframes rideMove {
    0%, 5% { transform: translateX(0); }
    7% { transform: translateX(3px); }
    9% { transform: translateX(0); }
    12% { transform: translateX(4px); }
    14% { transform: translateX(0); animation-timing-function: cubic-bezier(0.22, 0.85, 0.32, 1); }
    42% { transform: translateX(104px); animation-timing-function: cubic-bezier(0.15, 0.75, 0.3, 1); }
    50% { transform: translateX(118px); }
    63% { transform: translateX(118px); animation-timing-function: cubic-bezier(0.45, 0, 0.55, 1); }
    93%, 100% { transform: translateX(0); }
}

@keyframes rideWheel {
    0%, 14% { transform: rotate(0deg); animation-timing-function: cubic-bezier(0.22, 0.85, 0.32, 1); }
    42% { transform: rotate(458deg); animation-timing-function: cubic-bezier(0.15, 0.75, 0.3, 1); }
    50%, 63% { transform: rotate(520deg); animation-timing-function: cubic-bezier(0.45, 0, 0.55, 1); }
    93%, 100% { transform: rotate(0deg); }
}

/* Wheelie berporos di ban belakang (40, 108) saat blayer & gaspol */
@keyframes rideWheelie {
    0%, 5% { transform: rotate(0deg); }
    7% { transform: rotate(-2.5deg); }
    9% { transform: rotate(0deg); }
    12% { transform: rotate(-3.5deg); }
    14% { transform: rotate(0deg); }
    19% { transform: rotate(-10.5deg); }
    31% { transform: rotate(-7.5deg); }
    39%, 100% { transform: rotate(0deg); }
}

/* Stoppie berporos di ban depan (126, 108) saat ngerem mendadak */
@keyframes rideStoppie {
    0%, 41% { transform: rotate(0deg); }
    45% { transform: rotate(6.5deg); }
    50% { transform: rotate(4deg); }
    55% { transform: rotate(-1.5deg); }
    59%, 100% { transform: rotate(0deg); }
}

/* Gerak badan pengendara mengikuti tarikan gas & hentakan rem */
@keyframes rideRider {
    0%, 14% { transform: rotate(0deg); }
    19%, 34% { transform: rotate(-4deg); }
    41% { transform: rotate(0deg); }
    45% { transform: rotate(5deg); }
    54% { transform: rotate(-1.5deg); }
    58%, 100% { transform: rotate(0deg); }
}

/* Kepala terdorong inersia: ke belakang saat gaspol, ke depan saat ngerem, menoleh saat mundur */
@keyframes rideHead {
    0%, 12% { transform: translateX(0) rotate(0deg); }
    18%, 33% { transform: translateX(-3.5px) rotate(-7deg); }
    41% { transform: translateX(0) rotate(0deg); }
    45% { transform: translateX(4.5px) rotate(8.5deg); }
    53% { transform: translateX(-1.5px) rotate(-2deg); }
    58% { transform: translateX(0) rotate(0deg); }
    63%, 89% { transform: translateX(-2px) rotate(-4.5deg); }
    94%, 100% { transform: translateX(0) rotate(0deg); }
}

@keyframes rideIdle {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-0.9px); }
}

/* Api turbo knalpot menyembur saat blayer & gaspol */
@keyframes rideFlame {
    0%, 5% { opacity: 0; transform: scaleX(0.2); }
    7% { opacity: 0.9; transform: scaleX(0.65); }
    9% { opacity: 0; transform: scaleX(0.2); }
    12% { opacity: 1; transform: scaleX(0.8); }
    14% { opacity: 0.4; transform: scaleX(0.4); }
    17% { opacity: 1; transform: scaleX(1.15); }
    23% { opacity: 0.95; transform: scaleX(0.9); }
    29% { opacity: 1; transform: scaleX(1.2); }
    35% { opacity: 0.85; transform: scaleX(0.85); }
    40%, 100% { opacity: 0; transform: scaleX(0.1); }
}

@keyframes rideBrakeLight {
    0%, 40% { opacity: 0; }
    42%, 57% { opacity: 0.95; }
    60%, 100% { opacity: 0; }
}

@keyframes ridePuff {
    0%, 6% { opacity: 0; transform: translate(0, 0) scale(0.4); }
    11% { opacity: 0.9; transform: translate(-6px, -2px) scale(0.95); }
    22% { opacity: 0.85; transform: translate(-22px, -8px) scale(1.6); }
    36% { opacity: 0; transform: translate(-38px, -14px) scale(2.1); }
    100% { opacity: 0; transform: translate(-38px, -14px) scale(2.1); }
}

@keyframes rideSkid {
    0%, 41% { opacity: 0; transform: translate(0, 0) scale(0.4); }
    44% { opacity: 0.95; transform: translate(5px, -3px) scale(1.1); }
    55% { opacity: 0; transform: translate(18px, -9px) scale(1.9); }
    100% { opacity: 0; transform: translate(18px, -9px) scale(1.9); }
}

@keyframes rideSpeed {
    0%, 14% { opacity: 0; transform: translateX(12px); }
    18% { opacity: 0.95; transform: translateX(0); }
    37% { opacity: 0.95; transform: translateX(-14px); }
    42%, 100% { opacity: 0; transform: translateX(-24px); }
}

@keyframes rideShadow {
    0%, 14% { transform: scaleX(1); opacity: 0.16; }
    20%, 34% { transform: scaleX(0.82); opacity: 0.12; }
    42%, 100% { transform: scaleX(1); opacity: 0.16; }
}

@keyframes rideBeam {
    0%, 100% { opacity: 0.85; }
    50% { opacity: 1; }
}

.ride-move {
    animation: rideMove 5.2s infinite;
}

.ride-wheel {
    transform-box: fill-box;
    transform-origin: center;
    animation: rideWheel 5.2s infinite;
}

.ride-wheelie {
    transform-origin: 40px 108px;
    animation: rideWheelie 5.2s ease-in-out infinite;
}

.ride-stoppie {
    transform-origin: 126px 108px;
    animation: rideStoppie 5.2s ease-in-out infinite;
}

.ride-rider {
    transform-origin: 66px 70px;
    animation: rideRider 5.2s ease-in-out infinite;
}

.ride-head {
    transform-origin: 66px 40px;
    animation: rideHead 5.2s ease-in-out infinite;
}

.ride-idle {
    animation: rideIdle 0.11s linear infinite;
}

.ride-flame {
    opacity: 0;
    transform-origin: 14px 106.5px;
    animation: rideFlame 5.2s infinite;
}

.ride-brakelight {
    opacity: 0;
    animation: rideBrakeLight 5.2s infinite;
}

.ride-puff,
.ride-skid {
    opacity: 0;
    transform-box: fill-box;
    transform-origin: center;
}

.ride-puff-1 { animation: ridePuff 5.2s ease-out infinite; }
.ride-puff-2 { animation: ridePuff 5.2s ease-out infinite 0.18s; }
.ride-puff-3 { animation: ridePuff 5.2s ease-out infinite 0.36s; }
.ride-puff-4 { animation: ridePuff 5.2s ease-out infinite 0.54s; }

.ride-skid-1 { animation: rideSkid 5.2s ease-out infinite; }
.ride-skid-2 { animation: rideSkid 5.2s ease-out infinite 0.1s; }
.ride-skid-3 { animation: rideSkid 5.2s ease-out infinite 0.05s; }

.ride-speed {
    opacity: 0;
    animation: rideSpeed 5.2s linear infinite;
}

.ride-shadow {
    transform-origin: 84px 121px;
    animation: rideShadow 5.2s ease-in-out infinite;
}

.ride-beam {
    animation: rideBeam 2.4s ease-in-out infinite;
}

@media (prefers-reduced-motion: reduce) {
    .ride-move,
    .ride-wheel,
    .ride-wheelie,
    .ride-stoppie,
    .ride-rider,
    .ride-head,
    .ride-idle,
    .ride-flame,
    .ride-brakelight,
    .ride-puff,
    .ride-skid,
    .ride-speed,
    .ride-shadow,
    .ride-beam {
        animation: none;
    }
}
</style>
