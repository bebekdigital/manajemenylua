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
        default: 'senang', // 'senang' | 'semangat' | 'sedih' | 'capek'
    },
    isNight: {
        type: Boolean,
        default: false,
    },
});

const uid = computed(() => `ride-${props.gender}-${props.mood}`);

/**
 * Palet per karakter: Putra (motor biru, helm kuning), Putri (motor merah muda, helm putih).
 */
const palette = computed(() => (props.gender === 'P'
    ? {
        bodyLight: '#F9A8D4',
        bodyDark: '#DB2777',
        helmetLight: '#FFFFFF',
        helmetDark: '#E2E8F0',
        helmetStripe: '#EC4899',
        outfit: '#0F766E',
        outfitShade: '#115E59',
        legs: '#0F766E',
    }
    : {
        bodyLight: '#60A5FA',
        bodyDark: '#1D4ED8',
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
        :title="gender === 'P' ? 'Karakter Putri naik motor' : 'Karakter Putra naik motor'"
        @click.stop.prevent="triggerHappySquint"
    >
        <svg
            viewBox="0 -6 250 130"
            class="w-[150px] sm:w-[180px] aspect-[250/130] h-auto overflow-visible block"
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
                    <stop offset="0%" stop-color="#FEF08A" stop-opacity="0.75" />
                    <stop offset="100%" stop-color="#FEF08A" stop-opacity="0" />
                </linearGradient>
            </defs>

            <!-- Seluruh motor + pengendara bergerak maju, mengerem, lalu mundur (looping) -->
            <g class="ride-move">
                <!-- Bayangan di atas banner -->
                <ellipse cx="84" cy="121" rx="60" ry="2.6" fill="#0F172A" opacity="0.14" />

                <!-- Garis kecepatan saat ngegas -->
                <g class="ride-speed" fill="none" stroke="#94A3B8" stroke-width="2" stroke-linecap="round">
                    <path d="M 2 58 L 16 58" />
                    <path d="M 0 72 L 20 72" />
                    <path d="M 4 86 L 14 86" />
                </g>

                <!-- Kepulan asap knalpot saat ngegas -->
                <g fill="#CBD5E1">
                    <circle class="ride-puff" cx="11" cy="107" r="4" />
                    <circle class="ride-puff ride-puff-2" cx="11" cy="107" r="3.4" />
                    <circle class="ride-puff ride-puff-3" cx="11" cy="107" r="3" />
                </g>

                <!-- Debu roda depan saat mengerem -->
                <g fill="#E2E8F0">
                    <circle class="ride-dust" cx="138" cy="118" r="3" />
                    <circle class="ride-dust ride-dust-2" cx="134" cy="119" r="2.4" />
                </g>

                <!-- Sorot lampu depan (suasana malam) -->
                <path
                    v-if="isNight"
                    class="ride-beam"
                    d="M 124 60 L 196 46 L 196 84 L 124 68 Z"
                    :fill="`url(#${uid}-beam)`"
                />

                <!-- Roda Belakang -->
                <g transform="translate(40 108)">
                    <g class="ride-wheel">
                        <circle r="13" fill="#1E293B" />
                        <circle r="7.6" fill="#CBD5E1" />
                        <path d="M 0 -7 L 0 7 M -6.06 -3.5 L 6.06 3.5 M -6.06 3.5 L 6.06 -3.5" stroke="#64748B" stroke-width="1.7" stroke-linecap="round" />
                        <circle r="2.4" fill="#475569" />
                    </g>
                </g>

                <!-- Roda Depan -->
                <g transform="translate(126 108)">
                    <g class="ride-wheel">
                        <circle r="13" fill="#1E293B" />
                        <circle r="7.6" fill="#CBD5E1" />
                        <path d="M 0 -7 L 0 7 M -6.06 -3.5 L 6.06 3.5 M -6.06 3.5 L 6.06 -3.5" stroke="#64748B" stroke-width="1.7" stroke-linecap="round" />
                        <circle r="2.4" fill="#475569" />
                    </g>
                </g>

                <!-- Getaran mesin halus -->
                <g class="ride-idle">
                    <!-- Bodi motor ikut menunduk saat mengerem & mendongak saat ngegas -->
                    <g class="ride-pitch">
                        <!-- Garpu depan -->
                        <path d="M 117 62 L 126 108" stroke="#94A3B8" stroke-width="3.2" stroke-linecap="round" />

                        <!-- Mesin & lengan ayun -->
                        <path d="M 36 104 L 66 99 Q 73 99 72 106 L 70 110 L 40 112 Z" fill="#334155" />

                        <!-- Knalpot -->
                        <rect x="15" y="103.5" width="24" height="6.5" rx="3.2" fill="#94A3B8" />
                        <rect x="13" y="104.8" width="4" height="4" rx="1.2" fill="#475569" />

                        <!-- Bodi belakang -->
                        <path
                            d="M 16 94 C 14 82, 24 74, 40 73 L 82 73 C 86 73, 88 76, 88 80 L 88 92 C 74 94, 66 96, 60 100 L 56 102 C 52 94, 46 90, 40 90 C 32 90, 26 94, 24 100 Z"
                            :fill="`url(#${uid}-body)`"
                        />
                        <path d="M 28 82 L 80 79.5" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" opacity="0.7" />

                        <!-- Lampu rem -->
                        <ellipse cx="17.5" cy="85" rx="2.6" ry="3.6" fill="#B91C1C" />
                        <circle class="ride-brakelight" cx="16" cy="85" r="6.5" fill="#F87171" />

                        <!-- Jok -->
                        <path d="M 34 73 C 34 67, 40 65, 48 65 L 80 66 C 86 66, 88 69, 88 73 Z" fill="#1F2937" />

                        <!-- Dek bawah / pijakan kaki -->
                        <path d="M 56 102 L 61 97 L 108 95 L 112 101 Z" :fill="palette.bodyDark" />
                        <rect x="74" y="93" width="34" height="4" rx="2" fill="#334155" />

                        <!-- ================= PENGENDARA ================= -->
                        <!-- Kaki -->
                        <path d="M 62 69 L 91 70" fill="none" :stroke="palette.legs" stroke-width="10" stroke-linecap="round" />
                        <path d="M 91 70 L 95 91" fill="none" :stroke="palette.legs" stroke-width="8.5" stroke-linecap="round" />
                        <ellipse cx="98" cy="92.5" rx="6.2" ry="3.3" fill="#111827" />

                        <!-- Tameng depan -->
                        <path
                            d="M 104 98 L 100 64 C 100 56, 104 52, 110 50 L 116 49 L 124 72 C 130 82, 134 88, 136 95 L 112 99 Z"
                            :fill="`url(#${uid}-body)`"
                        />

                        <!-- Spakbor depan -->
                        <path
                            d="M 112 100 C 114 90, 120 86, 126 86 C 133 86, 139 90, 141 99 L 137 99 C 135 93, 131 90, 126 90 C 121 90, 117 93, 115 100 Z"
                            :fill="palette.bodyDark"
                        />

                        <!-- Lampu depan -->
                        <ellipse cx="122.5" cy="64" rx="3" ry="4.6" fill="#FEF9C3" stroke="#EAB308" stroke-width="1.2" />

                        <!-- Batang setang & cover setang -->
                        <path d="M 107 52 L 111 38 L 118 38 L 116 52 Z" :fill="palette.bodyDark" />
                        <rect x="104" y="33" width="21" height="6.5" rx="3.2" :fill="`url(#${uid}-body)`" />
                        <!-- Spion -->
                        <path d="M 117 33 L 121 23" stroke="#475569" stroke-width="1.6" stroke-linecap="round" />
                        <ellipse cx="122" cy="21" rx="3.4" ry="2.6" fill="#CBD5E1" stroke="#475569" stroke-width="1.2" />
                        <!-- Handgrip -->
                        <rect x="99" y="34.5" width="8" height="4" rx="2" fill="#111827" />

                        <!-- Badan -->
                        <path
                            d="M 54 72 C 52 54, 56 42, 66 40 C 75 40, 79 48, 79 58 L 76 72 Z"
                            :fill="palette.outfit"
                            :stroke="palette.outfitShade"
                            stroke-width="1.2"
                        />
                        <!-- Kerudung menjuntai di bahu (Putri) -->
                        <path
                            v-if="gender === 'P'"
                            d="M 52 46 Q 66 36 81 46 L 80 60 Q 66 67 53 60 Z"
                            :fill="`url(#${uid}-hijab)`"
                        />
                        <!-- Kancing baju koko (Putra) -->
                        <g v-else fill="#94A3B8">
                            <circle cx="70" cy="50" r="1" />
                            <circle cx="70.5" cy="56" r="1" />
                            <circle cx="71" cy="62" r="1" />
                        </g>

                        <!-- Lengan menuju handgrip -->
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
                                    stroke-opacity="0.12"
                                    stroke-width="1.4"
                                />
                                <path d="M 60 4.5 L 60 29" :stroke="palette.helmetStripe" stroke-width="7" stroke-linecap="round" />
                                <!-- Kaca helm terangkat -->
                                <path d="M 22 31 C 34 18, 86 18, 98 31 L 96 37 C 84 26, 36 26, 24 37 Z" fill="#0F172A" opacity="0.32" />
                                <path d="M 34 25.5 Q 46 20.5 58 20.5" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" opacity="0.55" />
                                <circle cx="86" cy="15" r="2.6" fill="#FFFFFF" opacity="0.85" />
                                <!-- Tali dagu -->
                                <path
                                    d="M 24 58 C 28 75, 42 84.5, 60 85 C 78 84.5, 92 75, 96 58"
                                    fill="none"
                                    stroke="#1F2937"
                                    stroke-width="2.2"
                                    stroke-linecap="round"
                                    opacity="0.55"
                                />

                                <MascotFaceFeatures :gender="gender" :mood="mood" :is-winking="isWinking" />
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
 * Satu siklus 7.5 detik:
 * 0-10%  diam (mesin menyala) → 10-38% ngegas → 38-48% ngerem → 48-58% berhenti
 * 58-90% mundur pelan ke posisi awal → 90-100% diam.
 * Putaran roda dihitung dari jarak tempuh (jari-jari 13): 70 → 308°, 86 → 379°.
 */
@keyframes rideMove {
    0%, 10% { transform: translateX(0); animation-timing-function: cubic-bezier(0.5, 0, 0.8, 0.6); }
    38% { transform: translateX(70px); animation-timing-function: cubic-bezier(0.2, 0.6, 0.4, 1); }
    48% { transform: translateX(86px); }
    58% { transform: translateX(86px); animation-timing-function: cubic-bezier(0.45, 0, 0.55, 1); }
    90%, 100% { transform: translateX(0); }
}

@keyframes rideWheel {
    0%, 10% { transform: rotate(0deg); animation-timing-function: cubic-bezier(0.5, 0, 0.8, 0.6); }
    38% { transform: rotate(308deg); animation-timing-function: cubic-bezier(0.2, 0.6, 0.4, 1); }
    48% { transform: rotate(379deg); }
    58% { transform: rotate(379deg); animation-timing-function: cubic-bezier(0.45, 0, 0.55, 1); }
    90%, 100% { transform: rotate(0deg); }
}

@keyframes ridePitch {
    0%, 10% { transform: rotate(0deg); }
    14% { transform: rotate(-3deg); }
    30% { transform: rotate(-1.4deg); }
    38% { transform: rotate(0deg); }
    42% { transform: rotate(3deg); }
    48% { transform: rotate(2deg); }
    53% { transform: rotate(-1deg); }
    58%, 100% { transform: rotate(0deg); }
}

/* Kepala terdorong inersia: ke belakang saat ngegas, ke depan saat ngerem, menoleh saat mundur */
@keyframes rideHead {
    0%, 10% { transform: translateX(0) rotate(0deg); }
    15% { transform: translateX(-2.5px) rotate(-4deg); }
    34% { transform: translateX(-1px) rotate(-1.5deg); }
    42% { transform: translateX(3px) rotate(5deg); }
    50% { transform: translateX(-1px) rotate(-1.5deg); }
    56% { transform: translateX(0) rotate(0deg); }
    62%, 86% { transform: translateX(-1.5px) rotate(-3deg); }
    92%, 100% { transform: translateX(0) rotate(0deg); }
}

@keyframes rideIdle {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-0.6px); }
}

@keyframes rideBrakeLight {
    0%, 37% { opacity: 0; }
    39%, 55% { opacity: 0.9; }
    58%, 100% { opacity: 0; }
}

@keyframes ridePuff {
    0%, 9% { opacity: 0; transform: translate(0, 0) scale(0.4); }
    12% { opacity: 0.85; transform: translate(-3px, -1px) scale(0.8); }
    24% { opacity: 0; transform: translate(-18px, -7px) scale(1.7); }
    100% { opacity: 0; transform: translate(-18px, -7px) scale(1.7); }
}

@keyframes rideSpeed {
    0%, 16% { opacity: 0; transform: translateX(6px); }
    24% { opacity: 0.75; transform: translateX(0); }
    36% { opacity: 0.75; transform: translateX(-4px); }
    42%, 100% { opacity: 0; transform: translateX(-8px); }
}

@keyframes rideDust {
    0%, 38% { opacity: 0; transform: translate(0, 0) scale(0.5); }
    41% { opacity: 0.85; transform: translate(3px, -2px) scale(1); }
    50% { opacity: 0; transform: translate(10px, -6px) scale(1.6); }
    100% { opacity: 0; transform: translate(10px, -6px) scale(1.6); }
}

@keyframes rideBeam {
    0%, 100% { opacity: 0.85; }
    50% { opacity: 1; }
}

.ride-move {
    animation: rideMove 7.5s infinite;
}

.ride-wheel {
    transform-box: fill-box;
    transform-origin: center;
    animation: rideWheel 7.5s infinite;
}

.ride-pitch {
    transform-origin: 40px 108px;
    animation: ridePitch 7.5s ease-in-out infinite;
}

.ride-head {
    transform-origin: 66px 40px;
    animation: rideHead 7.5s ease-in-out infinite;
}

.ride-idle {
    animation: rideIdle 0.16s linear infinite;
}

.ride-brakelight {
    opacity: 0;
    animation: rideBrakeLight 7.5s infinite;
}

.ride-puff,
.ride-dust {
    opacity: 0;
    transform-box: fill-box;
    transform-origin: center;
}

.ride-puff { animation: ridePuff 7.5s ease-out infinite; }
.ride-puff-2 { animation-delay: 0.3s; }
.ride-puff-3 { animation-delay: 0.6s; }

.ride-dust { animation: rideDust 7.5s ease-out infinite; }
.ride-dust-2 { animation-delay: 0.12s; }

.ride-speed {
    opacity: 0;
    animation: rideSpeed 7.5s linear infinite;
}

.ride-beam {
    animation: rideBeam 2.4s ease-in-out infinite;
}

@media (prefers-reduced-motion: reduce) {
    .ride-move,
    .ride-wheel,
    .ride-pitch,
    .ride-head,
    .ride-idle,
    .ride-brakelight,
    .ride-puff,
    .ride-dust,
    .ride-speed,
    .ride-beam {
        animation: none;
    }
}
</style>

