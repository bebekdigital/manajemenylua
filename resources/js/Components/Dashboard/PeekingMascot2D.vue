<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    gender: {
        type: String,
        default: 'L', // 'L' = Putra, 'P' = Putri
    },
    mood: {
        type: String,
        default: 'senang', // 'senang' | 'semangat' | 'sedih' | 'capek'
    },
});

const uid = computed(() => `mascot-${props.gender}-${props.mood}`);
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
</script>

<template>
    <div
        class="mascot-2d-root select-none cursor-pointer"
        :title="gender === 'P' ? 'Karakter Putri' : 'Karakter Putra'"
        @click.stop.prevent="triggerHappySquint"
    >
        <svg
            viewBox="0 0 120 88"
            class="w-[82px] sm:w-[92px] aspect-[120/88] h-auto overflow-visible block"
            aria-hidden="true"
        >
            <defs>
                <!-- Potong bagian bawah kepala tepat di garis atas banner (y = 79) -->
                <clipPath :id="`${uid}-edge-clip`">
                    <rect x="-10" y="-15" width="140" height="94" />
                </clipPath>

                <!-- Gradien Kulit Hangat Cerah -->
                <linearGradient :id="`${uid}-skin`" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#FFEAD6" />
                    <stop offset="100%" stop-color="#FFD5B5" />
                </linearGradient>

                <!-- Gradien Peci Putra -->
                <linearGradient :id="`${uid}-peci`" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#334155" />
                    <stop offset="100%" stop-color="#1E293B" />
                </linearGradient>

                <!-- Gradien Kerudung Putri (Hijau Sage Cerah) -->
                <linearGradient :id="`${uid}-hijab`" x1="15%" y1="0%" x2="85%" y2="100%">
                    <stop offset="0%" stop-color="#38B2AC" />
                    <stop offset="100%" stop-color="#228B68" />
                </linearGradient>
            </defs>

            <!-- LAYER KEPALA MENGINTIP (Terpotong rapi tepat di tepi atas banner y = 79) -->
            <g :clip-path="`url(#${uid}-edge-clip)`">
                <g :class="['mascot-head-peek', `mascot-mood-${mood}`, { 'mascot-head-bounce': isWinking }]">
                    <!-- ================= KARAKTER PUTRI (BERKERUDUNG) ================= -->
                    <template v-if="gender === 'P'">
                        <!-- Siluet Luar Kerudung Bulat -->
                        <path
                            d="M 60 9 C 88 9, 103 28, 103 57 C 103 69, 99 77, 95 84 L 25 84 C 21 77, 17 69, 17 57 C 17 28, 32 9, 60 9 Z"
                            :fill="`url(#${uid}-hijab)`"
                        />
                        <!-- Highlight Lembut Atas Kerudung -->
                        <path
                            d="M 35 18 C 46 12.5, 74 12.5, 85 18"
                            fill="none"
                            stroke="#A7F3D0"
                            stroke-width="2.2"
                            stroke-linecap="round"
                            opacity="0.45"
                        />
                        <!-- Ciput Dalam Cream Hangat -->
                        <path
                            d="M 29 52 C 29 30, 42 22, 60 22 C 78 22, 91 30, 91 52 Z"
                            fill="#FFF7E8"
                        />
                        <!-- Wajah Mochi Bulat -->
                        <path
                            d="M 60 28.5 C 78 28.5, 90 41, 90 61 C 90 74, 80 84, 60 84 C 40 84, 30 74, 30 61 C 30 41, 42 28.5, 60 28.5 Z"
                            :fill="`url(#${uid}-skin)`"
                        />
                        <!-- Lipatan Halus Samping Kerudung -->
                        <path
                            d="M 23.5 63 Q 27 72 31 79 M 96.5 63 Q 93 72 89 79"
                            fill="none"
                            stroke="#16654A"
                            stroke-width="2"
                            stroke-linecap="round"
                            opacity="0.32"
                        />
                        <!-- Pin Bunga Kecil Kuning di Kerudung -->
                        <g transform="translate(30, 30)">
                            <circle cx="0" cy="-3.1" r="2.2" fill="#FEF08A" />
                            <circle cx="3" cy="-0.9" r="2.2" fill="#FEF08A" />
                            <circle cx="1.8" cy="2.5" r="2.2" fill="#FEF08A" />
                            <circle cx="-1.8" cy="2.5" r="2.2" fill="#FEF08A" />
                            <circle cx="-3" cy="-0.9" r="2.2" fill="#FEF08A" />
                            <circle cx="0" cy="0" r="1.9" fill="#F59E0B" />
                        </g>
                    </template>

                    <!-- ================= KARAKTER PUTRA (PECI) ================= -->
                    <template v-else>
                        <!-- Telinga Kiri & Kanan -->
                        <ellipse cx="22.5" cy="63" rx="6" ry="7" fill="#FFC9A3" />
                        <ellipse cx="23.5" cy="63" rx="3" ry="3.8" fill="#F3A680" />
                        <ellipse cx="97.5" cy="63" rx="6" ry="7" fill="#FFC9A3" />
                        <ellipse cx="96.5" cy="63" rx="3" ry="3.8" fill="#F3A680" />

                        <!-- Wajah Mochi Bulat (Bagian bawah diperpanjang melewati garis potong y=79 agar tidak bercelah saat memantul) -->
                        <path
                            d="M 60 27 C 81 27, 95 41, 95 62 C 95 73, 92 80, 90 85 L 30 85 C 28 80, 25 73, 25 62 C 25 41, 39 27, 60 27 Z"
                            :fill="`url(#${uid}-skin)`"
                        />

                        <!-- Poni Rambut Lembut di Bawah Peci -->
                        <path
                            d="M 28 31 L 92 31 C 95 38, 95.5 47, 95 56 C 92.5 51, 90.5 45, 88 41 Q 81 44.5, 74 39.5 Q 66 45, 58 40 Q 50 45, 43 39.5 Q 36 44, 32 41 C 29.5 45, 27.5 51, 25 56 C 24.5 47, 25 38, 28 31 Z"
                            fill="#2C1D17"
                        />

                        <!-- Peci / Songkok Bulat Rapi -->
                        <path
                            d="M 31 16.5 C 32.5 11, 44 9.5, 60 9.5 C 76 9.5, 87.5 11, 89 16.5 L 93 36 C 76 32.5, 44 32.5, 27 36 Z"
                            :fill="`url(#${uid}-peci)`"
                        />
                        <!-- Highlight Halus Peci -->
                        <path
                            d="M 38 15.5 C 48 13.5, 72 13.5, 82 15.5"
                            fill="none"
                            stroke="#94A3B8"
                            stroke-width="1.6"
                            stroke-linecap="round"
                            opacity="0.4"
                        />
                        <circle cx="80" cy="24.5" r="2.1" fill="#FBBF24" />
                    </template>

                    <!-- ================= RONA PIPI ================= -->
                    <template v-if="mood === 'semangat'">
                        <ellipse cx="35" cy="69" rx="7.8" ry="4.6" fill="#FF5C8A" opacity="0.6" />
                        <ellipse cx="85" cy="69" rx="7.8" ry="4.6" fill="#FF5C8A" opacity="0.6" />
                    </template>
                    <template v-else-if="mood === 'sedih'">
                        <ellipse cx="35.5" cy="70" rx="6.5" ry="3.4" fill="#FFA4B6" opacity="0.35" />
                        <ellipse cx="84.5" cy="70" rx="6.5" ry="3.4" fill="#FFA4B6" opacity="0.35" />
                    </template>
                    <template v-else-if="mood === 'capek'">
                        <ellipse cx="35.5" cy="71" rx="5.8" ry="2.8" fill="#FDA4AF" opacity="0.25" />
                        <ellipse cx="84.5" cy="71" rx="5.8" ry="2.8" fill="#FDA4AF" opacity="0.25" />
                    </template>
                    <template v-else>
                        <ellipse cx="35.5" cy="69.5" rx="7.2" ry="4" fill="#FF8FA3" opacity="0.52" />
                        <ellipse cx="84.5" cy="69.5" rx="7.2" ry="4" fill="#FF8FA3" opacity="0.52" />
                    </template>

                    <!-- ================= ALIS ================= -->
                    <!-- Semangat: alis terangkat tinggi melengkung (antusias) -->
                    <g v-if="mood === 'semangat'" fill="none" stroke="#2C1D17" stroke-width="2.6" stroke-linecap="round">
                        <path d="M 38.5 48.5 Q 44.5 43.5 50.5 47.5" />
                        <path d="M 69.5 47.5 Q 75.5 43.5 81.5 48.5" />
                    </g>
                    <!-- Sedih: ujung dalam alis naik, ujung luar turun (memelas) -->
                    <g v-else-if="mood === 'sedih'" fill="none" stroke="#432E24" stroke-width="2.2" stroke-linecap="round">
                        <path d="M 39 51.5 Q 45 50.5 50 46.5" />
                        <path d="M 70 46.5 Q 75 50.5 81 51.5" />
                    </g>
                    <!-- Capek: alis datar rendah dan rileks -->
                    <g v-else-if="mood === 'capek'" fill="none" stroke="#5E4336" stroke-width="2.1" stroke-linecap="round">
                        <path d="M 39.5 51.5 Q 45 50.3 50 51" />
                        <path d="M 70 51 Q 75 50.3 80.5 51.5" />
                    </g>
                    <!-- Senang: alis ramah -->
                    <g v-else fill="none" stroke="#432E24" stroke-width="2.2" stroke-linecap="round">
                        <path d="M 39.5 49.5 Q 44.5 47 49.5 49" />
                        <path d="M 70.5 49 Q 75.5 47 80.5 49.5" />
                    </g>

                    <!-- ================= MATA ================= -->
                    <!-- Mata senyum melengkung saat diklik -->
                    <g v-if="isWinking" fill="none" stroke="#231B18" stroke-width="3" stroke-linecap="round">
                        <path d="M 39 61.5 Q 44.5 55.5 50 61.5" />
                        <path d="M 70 61.5 Q 75.5 55.5 81 61.5" />
                    </g>

                    <!-- Semangat: mata besar berbinar dengan kilau bintang -->
                    <g v-else-if="mood === 'semangat'" class="mascot-eyes-blink">
                        <template v-if="gender === 'P'">
                            <path d="M 39 57 L 36 54.8 M 81 57 L 84 54.8" stroke="#231B18" stroke-width="2" stroke-linecap="round" />
                        </template>
                        <ellipse cx="44.5" cy="60.5" rx="6" ry="7" fill="#1C1411" />
                        <path d="M 42.8 54.8 L 43.9 57.8 L 46.9 58.9 L 43.9 60 L 42.8 63 L 41.7 60 L 38.7 58.9 L 41.7 57.8 Z" fill="#FFFFFF" />
                        <circle cx="47.2" cy="63.4" r="1.3" fill="#FFFFFF" />
                        <ellipse cx="75.5" cy="60.5" rx="6" ry="7" fill="#1C1411" />
                        <path d="M 73.8 54.8 L 74.9 57.8 L 77.9 58.9 L 74.9 60 L 73.8 63 L 72.7 60 L 69.7 58.9 L 72.7 57.8 Z" fill="#FFFFFF" />
                        <circle cx="78.2" cy="63.4" r="1.3" fill="#FFFFFF" />
                    </g>

                    <!-- Sedih: mata berkaca-kaca, genangan air di kelopak bawah -->
                    <g v-else-if="mood === 'sedih'" class="mascot-eyes-blink">
                        <template v-if="gender === 'P'">
                            <path d="M 39.5 58.5 L 37 57.2 M 80.5 58.5 L 83 57.2" stroke="#231B18" stroke-width="1.8" stroke-linecap="round" />
                        </template>
                        <ellipse cx="44.5" cy="62" rx="5.4" ry="6.2" fill="#231B18" />
                        <ellipse cx="43" cy="59.6" rx="2.6" ry="2.2" fill="#FFFFFF" />
                        <circle cx="46.6" cy="64.4" r="1.2" fill="#FFFFFF" />
                        <path d="M 39.6 65.6 Q 44.5 69 49.4 65.6" fill="none" stroke="#7DD3FC" stroke-width="1.8" stroke-linecap="round" />
                        <ellipse cx="75.5" cy="62" rx="5.4" ry="6.2" fill="#231B18" />
                        <ellipse cx="74" cy="59.6" rx="2.6" ry="2.2" fill="#FFFFFF" />
                        <circle cx="77.6" cy="64.4" r="1.2" fill="#FFFFFF" />
                        <path d="M 70.6 65.6 Q 75.5 69 80.4 65.6" fill="none" stroke="#7DD3FC" stroke-width="1.8" stroke-linecap="round" />
                    </g>

                    <!-- Capek: kelopak mata turun setengah + kantung mata -->
                    <g v-else-if="mood === 'capek'" class="mascot-eyes-sleepy">
                        <path d="M 40 60.5 Q 40 66.5 44.5 66.5 Q 49 66.5 49 60.5 Z" fill="#231B18" />
                        <circle cx="46.2" cy="62.6" r="1" fill="#FFFFFF" opacity="0.85" />
                        <path d="M 38.8 60.5 L 50.2 60.5" stroke="#231B18" stroke-width="2.6" stroke-linecap="round" />
                        <path d="M 71 60.5 Q 71 66.5 75.5 66.5 Q 80 66.5 80 60.5 Z" fill="#231B18" />
                        <circle cx="77.2" cy="62.6" r="1" fill="#FFFFFF" opacity="0.85" />
                        <path d="M 69.8 60.5 L 81.2 60.5" stroke="#231B18" stroke-width="2.6" stroke-linecap="round" />
                    </g>

                    <!-- Senang: mata bulat berkilau dengan kedipan natural -->
                    <g v-else class="mascot-eyes-blink">
                        <template v-if="gender === 'P'">
                            <path d="M 39.5 57.5 L 36.8 55.5 M 80.5 57.5 L 83.2 55.5" stroke="#231B18" stroke-width="2" stroke-linecap="round" />
                        </template>
                        <ellipse cx="44.5" cy="61" rx="5.3" ry="6.2" fill="#231B18" />
                        <circle cx="42.5" cy="58.5" r="2.1" fill="#FFFFFF" />
                        <circle cx="46.5" cy="63.2" r="1" fill="#FFFFFF" opacity="0.85" />
                        <ellipse cx="75.5" cy="61" rx="5.3" ry="6.2" fill="#231B18" />
                        <circle cx="73.5" cy="58.5" r="2.1" fill="#FFFFFF" />
                        <circle cx="77.5" cy="63.2" r="1" fill="#FFFFFF" opacity="0.85" />
                    </g>

                    <!-- Kantung mata keunguan (khusus capek) -->
                    <g v-if="mood === 'capek' && !isWinking" fill="none" stroke="#A78BFA" stroke-width="1.3" stroke-linecap="round" opacity="0.7">
                        <path d="M 40.5 68.6 Q 44.5 70.4 48.5 68.6" />
                        <path d="M 71.5 68.6 Q 75.5 70.4 79.5 68.6" />
                    </g>

                    <!-- ================= MULUT ================= -->
                    <!-- Semangat: tertawa lebar terbuka, gigi atas & lidah terlihat -->
                    <g v-if="mood === 'semangat'">
                        <path d="M 52 68.2 Q 60 70 68 68.2 Q 67.2 78 60 78 Q 52.8 78 52 68.2 Z" fill="#8B1E2D" />
                        <path d="M 53 68.6 Q 60 70.2 67 68.6 L 66.6 70.6 Q 60 72 53.4 70.6 Z" fill="#FFFFFF" />
                        <path d="M 55.2 75.2 Q 60 72.2 64.8 75.2 Q 63 77.8 60 77.8 Q 57 77.8 55.2 75.2 Z" fill="#FB7185" />
                    </g>

                    <!-- Sedih: mulut cemberut melengkung ke bawah -->
                    <path
                        v-else-if="mood === 'sedih'"
                        d="M 55.5 74.5 Q 60 69.8 64.5 74.5"
                        fill="none"
                        stroke="#B86B52"
                        stroke-width="2.2"
                        stroke-linecap="round"
                    />

                    <!-- Capek: menguap (mulut oval terbuka) -->
                    <g v-else-if="mood === 'capek'" class="mascot-yawn">
                        <ellipse cx="60" cy="73" rx="3.2" ry="4.2" fill="#7A2E2E" />
                        <ellipse cx="60" cy="75.4" rx="2.2" ry="1.4" fill="#F28B9B" />
                    </g>

                    <!-- Senang: senyum kecil -->
                    <path
                        v-else
                        d="M 57.2 70.5 Q 60 73.5 62.8 70.5"
                        fill="none"
                        stroke="#B86B52"
                        stroke-width="2.1"
                        stroke-linecap="round"
                    />

                    <!-- ================= AKSEN PER MOOD ================= -->
                    <!-- Semangat: garis energi memancar + bintang kilau -->
                    <template v-if="mood === 'semangat'">
                        <g class="mascot-energy" fill="none" stroke="#F59E0B" stroke-width="2.2" stroke-linecap="round">
                            <path d="M 14 34 L 7 31 M 13 43 L 6 43 M 18 25 L 13 19" />
                            <path d="M 106 34 L 113 31 M 107 43 L 114 43 M 102 25 L 107 19" />
                        </g>
                        <path class="mascot-sparkle-1" d="M 22 9 L 23.3 12.7 L 27 14 L 23.3 15.3 L 22 19 L 20.7 15.3 L 17 14 L 20.7 12.7 Z" fill="#FBBF24" />
                        <path class="mascot-sparkle-2" d="M 98 6 L 99 8.9 L 102 10 L 99 11.1 L 98 14 L 97 11.1 L 94 10 L 97 8.9 Z" fill="#FBBF24" />
                    </template>

                    <!-- Sedih: air mata menetes dari kedua mata -->
                    <template v-else-if="mood === 'sedih'">
                        <path class="mascot-tear" d="M 48.5 67 Q 51 71.5 48.5 73.6 Q 46 71.5 48.5 67 Z" fill="#38BDF8" />
                        <path class="mascot-tear mascot-tear-delay" d="M 71.5 67 Q 74 71.5 71.5 73.6 Q 69 71.5 71.5 67 Z" fill="#38BDF8" />
                    </template>

                    <!-- Capek: huruf "z" mengambang -->
                    <g v-else-if="mood === 'capek'" fill="none" stroke="#64748B" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path class="mascot-z-1" d="M 100 26 L 105 26 L 100 31 L 105 31" />
                        <path class="mascot-z-2" d="M 107 15 L 111 15 L 107 19 L 111 19" />
                    </g>
                </g>
            </g>

            <!-- LAYER DEPAN GARIS BANNER (y = 74..85): Dua Tangan Mungil Berpegangan di Tepi Atas Banner -->
            <g class="mascot-hands">
                <!-- Bayangan Halus Tangan di Atas Banner Putih -->
                <ellipse cx="31" cy="81.5" rx="8.2" ry="4.2" fill="#0F172A" opacity="0.08" />
                <ellipse cx="89" cy="81.5" rx="8.2" ry="4.2" fill="#0F172A" opacity="0.08" />

                <!-- Tangan Mungil Kiri -->
                <ellipse cx="31" cy="79.5" rx="8" ry="5.2" fill="#FFD8B9" stroke="#F1B38A" stroke-width="1.2" />
                <line x1="28.5" y1="77.8" x2="28.5" y2="82.2" stroke="#E39D70" stroke-width="1.4" stroke-linecap="round" />
                <line x1="33.5" y1="77.8" x2="33.5" y2="82.2" stroke="#E39D70" stroke-width="1.4" stroke-linecap="round" />

                <!-- Tangan Mungil Kanan -->
                <ellipse cx="89" cy="79.5" rx="8" ry="5.2" fill="#FFD8B9" stroke="#F1B38A" stroke-width="1.2" />
                <line x1="86.5" y1="77.8" x2="86.5" y2="82.2" stroke="#E39D70" stroke-width="1.4" stroke-linecap="round" />
                <line x1="91.5" y1="77.8" x2="91.5" y2="82.2" stroke="#E39D70" stroke-width="1.4" stroke-linecap="round" />
            </g>
        </svg>
    </div>
</template>

<style scoped>
/* Kedipan mata natural: 1x kedip lalu jeda, diikuti 2x kedip cepat (double-blink) */
@keyframes naturalBlink2D {
    0%, 42%, 46%, 84%, 87.5%, 89.5%, 93%, 100% {
        transform: scaleY(1);
    }
    44%, 86%, 91% {
        transform: scaleY(0.08);
    }
}

/* Kedipan berat dan lambat untuk mood capek */
@keyframes sleepyBlink2D {
    0%, 55%, 100% {
        transform: scaleY(1);
    }
    62%, 72% {
        transform: scaleY(0.15);
    }
}

@keyframes gentlePeek2D {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(2px); }
}

/* Semangat: melompat-lompat kecil penuh energi */
@keyframes spiritedPeek2D {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    20% { transform: translateY(-3.5px) rotate(-2deg); }
    40% { transform: translateY(0px) rotate(0deg); }
    60% { transform: translateY(-3.5px) rotate(2deg); }
    80% { transform: translateY(0px) rotate(0deg); }
}

/* Sedih: sedikit menunduk */
@keyframes sadPeek2D {
    0%, 100% { transform: translateY(2px); }
    50% { transform: translateY(3px); }
}

/* Capek: kepala terkulai miring pelan */
@keyframes tiredPeek2D {
    0%, 100% { transform: translateY(2.5px) rotate(0deg); }
    50% { transform: translateY(3.5px) rotate(-3deg); }
}

@keyframes sparkleAnim {
    0%, 100% { transform: scale(0.7) rotate(0deg); opacity: 0.55; }
    50% { transform: scale(1.15) rotate(20deg); opacity: 1; }
}

@keyframes energyPulse {
    0%, 100% { opacity: 0.45; transform: scale(0.94); }
    50% { opacity: 1; transform: scale(1.05); }
}

@keyframes tearDrip {
    0% { transform: translateY(0px); opacity: 0; }
    15% { opacity: 1; }
    80% { opacity: 1; }
    100% { transform: translateY(5px); opacity: 0; }
}

@keyframes zFloat {
    0% { transform: translate(0, 2px); opacity: 0; }
    30% { opacity: 1; }
    100% { transform: translate(3px, -5px); opacity: 0; }
}

@keyframes yawnAnim {
    0%, 100% { transform: scaleY(0.75); }
    50% { transform: scaleY(1.1); }
}

.mascot-eyes-blink {
    transform-box: fill-box;
    transform-origin: center 62%;
    animation: naturalBlink2D 5.6s ease-in-out infinite;
}

.mascot-eyes-sleepy {
    transform-box: fill-box;
    transform-origin: center 30%;
    animation: sleepyBlink2D 4.2s ease-in-out infinite;
}

.mascot-head-peek {
    transform-origin: 60px 79px;
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.mascot-mood-senang { animation: gentlePeek2D 4.5s ease-in-out infinite; }
.mascot-mood-semangat { animation: spiritedPeek2D 1.6s ease-in-out infinite; }
.mascot-mood-sedih { animation: sadPeek2D 5s ease-in-out infinite; }
.mascot-mood-capek { animation: tiredPeek2D 5.5s ease-in-out infinite; }

.mascot-sparkle-1,
.mascot-sparkle-2,
.mascot-energy,
.mascot-tear,
.mascot-z-1,
.mascot-z-2,
.mascot-yawn {
    transform-box: fill-box;
    transform-origin: center;
}

.mascot-sparkle-1 { animation: sparkleAnim 1.4s ease-in-out infinite; }
.mascot-sparkle-2 { animation: sparkleAnim 1.4s ease-in-out infinite 0.7s; }
.mascot-energy { animation: energyPulse 0.8s ease-in-out infinite; }
.mascot-tear { animation: tearDrip 2.2s ease-in infinite; }
.mascot-tear-delay { animation-delay: 1.1s; }
.mascot-z-1 { animation: zFloat 2.6s ease-out infinite; }
.mascot-z-2 { animation: zFloat 2.6s ease-out infinite 1.3s; }
.mascot-yawn { animation: yawnAnim 4.2s ease-in-out infinite; }

.mascot-head-bounce {
    transform: translateY(-3px) !important;
}

@media (prefers-reduced-motion: reduce) {
    .mascot-eyes-blink,
    .mascot-eyes-sleepy,
    .mascot-head-peek,
    .mascot-sparkle-1,
    .mascot-sparkle-2,
    .mascot-energy,
    .mascot-tear,
    .mascot-z-1,
    .mascot-z-2,
    .mascot-yawn {
        animation: none;
    }
}
</style>
