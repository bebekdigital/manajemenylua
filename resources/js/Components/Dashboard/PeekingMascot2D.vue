<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    gender: {
        type: String,
        default: 'L', // 'L' = Putra, 'P' = Putri
    },
});

const uid = computed(() => `mascot-${props.gender}`);
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
                <g :class="['mascot-head-peek', { 'mascot-head-bounce': isWinking }]">
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

                    <!-- ================= FITUR WAJAH MENGGEMASKAN (PUTRA & PUTRI) ================= -->
                    <!-- Rona Pipi Merah Muda Cerah -->
                    <ellipse cx="35.5" cy="69.5" rx="7.2" ry="4" fill="#FF8FA3" opacity="0.52" />
                    <ellipse cx="84.5" cy="69.5" rx="7.2" ry="4" fill="#FF8FA3" opacity="0.52" />

                    <!-- Alis Ramah -->
                    <path
                        d="M 39.5 49.5 Q 44.5 47 49.5 49"
                        fill="none"
                        stroke="#432E24"
                        stroke-width="2.2"
                        stroke-linecap="round"
                    />
                    <path
                        d="M 70.5 49 Q 75.5 47 80.5 49.5"
                        fill="none"
                        stroke="#432E24"
                        stroke-width="2.2"
                        stroke-linecap="round"
                    />

                    <!-- Mata Senyum Melengkung (Saat Diklik) -->
                    <g v-if="isWinking">
                        <path
                            d="M 39 61.5 Q 44.5 55.5 50 61.5"
                            fill="none"
                            stroke="#231B18"
                            stroke-width="3"
                            stroke-linecap="round"
                        />
                        <path
                            d="M 70 61.5 Q 75.5 55.5 81 61.5"
                            fill="none"
                            stroke="#231B18"
                            stroke-width="3"
                            stroke-linecap="round"
                        />
                    </g>

                    <!-- Mata Bulat Berkilau dengan Animasi Kedipan Natural -->
                    <g v-else class="mascot-eyes-blink">
                        <!-- Bulu Mata Halus Khusus Putri -->
                        <template v-if="gender === 'P'">
                            <path
                                d="M 39.5 57.5 L 36.8 55.5 M 80.5 57.5 L 83.2 55.5"
                                stroke="#231B18"
                                stroke-width="2"
                                stroke-linecap="round"
                            />
                        </template>

                        <!-- Mata Kiri -->
                        <ellipse cx="44.5" cy="61" rx="5.3" ry="6.2" fill="#231B18" />
                        <circle cx="42.5" cy="58.5" r="2.1" fill="#FFFFFF" />
                        <circle cx="46.5" cy="63.2" r="1" fill="#FFFFFF" opacity="0.85" />

                        <!-- Mata Kanan -->
                        <ellipse cx="75.5" cy="61" rx="5.3" ry="6.2" fill="#231B18" />
                        <circle cx="73.5" cy="58.5" r="2.1" fill="#FFFFFF" />
                        <circle cx="77.5" cy="63.2" r="1" fill="#FFFFFF" opacity="0.85" />
                    </g>

                    <!-- Senyum Kecil Bahagia Tepat di Atas Tepi Banner -->
                    <path
                        d="M 57.2 70.5 Q 60 73.5 62.8 70.5"
                        fill="none"
                        stroke="#B86B52"
                        stroke-width="2.1"
                        stroke-linecap="round"
                    />
                </g>
            </g>

            <!-- LAYER DEPAN GARIS BANNER (y = 74..85): Dua Tangan Mungil Berpegangan di Tepi Atas Banner -->
            <g class="mascot-hands">
                <!-- Bayangan Halus Tangan di Atas Banner Putih -->
                <ellipse cx="31" cy="81.5" rx="8.2" ry="4.2" fill="#0F172A" opacity="0.08" />
                <ellipse cx="89" cy="81.5" rx="8.2" ry="4.2" fill="#0F172A" opacity="0.08" />

                <!-- Tangan Mungil Kiri -->
                <ellipse
                    cx="31"
                    cy="79.5"
                    rx="8"
                    ry="5.2"
                    fill="#FFD8B9"
                    stroke="#F1B38A"
                    stroke-width="1.2"
                />
                <line x1="28.5" y1="77.8" x2="28.5" y2="82.2" stroke="#E39D70" stroke-width="1.4" stroke-linecap="round" />
                <line x1="33.5" y1="77.8" x2="33.5" y2="82.2" stroke="#E39D70" stroke-width="1.4" stroke-linecap="round" />

                <!-- Tangan Mungil Kanan -->
                <ellipse
                    cx="89"
                    cy="79.5"
                    rx="8"
                    ry="5.2"
                    fill="#FFD8B9"
                    stroke="#F1B38A"
                    stroke-width="1.2"
                />
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

/* Gerakan mengintip halus tanpa lepas dari tepi banner */
@keyframes gentlePeek2D {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(2px);
    }
}

.mascot-eyes-blink {
    transform-box: fill-box;
    transform-origin: center 62%;
    animation: naturalBlink2D 5.6s ease-in-out infinite;
}

.mascot-head-peek {
    transform-origin: 60px 79px;
    animation: gentlePeek2D 4.5s ease-in-out infinite;
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.mascot-head-bounce {
    transform: translateY(-3px) !important;
}

@media (prefers-reduced-motion: reduce) {
    .mascot-eyes-blink,
    .mascot-head-peek {
        animation: none;
    }
}
</style>
