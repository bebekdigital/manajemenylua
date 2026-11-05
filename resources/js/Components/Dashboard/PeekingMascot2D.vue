<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    gender: {
        type: String,
        default: 'L', // 'L' = Putra, 'P' = Putri
    },
    mood: {
        type: String,
        default: 'senang', // 'senang', 'semangat', 'sedih', 'capek'
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
        :title="`${gender === 'P' ? 'Karakter Putri' : 'Karakter Putra'} (${mood})`"
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
                <g
                    :class="[
                        'mascot-head-peek',
                        `mascot-mood-${mood}`,
                        { 'mascot-head-bounce': isWinking }
                    ]"
                >
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

                        <!-- Wajah Mochi Bulat -->
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

                    <!-- ================= 1. RONA PIPI MERAH SESUAI MOOD ================= -->
                    <!-- SEMANGAT: Pipi Bersemu Merah Membara dengan Garis Kecepatan -->
                    <template v-if="mood === 'semangat'">
                        <ellipse cx="35.5" cy="69.5" rx="7.5" ry="4.5" fill="#FF477E" opacity="0.65" />
                        <ellipse cx="84.5" cy="69.5" rx="7.5" ry="4.5" fill="#FF477E" opacity="0.65" />
                        <line x1="32" y1="68" x2="34.5" y2="71.5" stroke="#E11D48" stroke-width="1.3" stroke-linecap="round" />
                        <line x1="36" y1="68" x2="38.5" y2="71.5" stroke="#E11D48" stroke-width="1.3" stroke-linecap="round" />
                        <line x1="81.5" y1="68" x2="84" y2="71.5" stroke="#E11D48" stroke-width="1.3" stroke-linecap="round" />
                        <line x1="85.5" y1="68" x2="88" y2="71.5" stroke="#E11D48" stroke-width="1.3" stroke-linecap="round" />
                    </template>

                    <!-- SEDIH: Pipi Halus dengan Rona Malu / Sendu -->
                    <template v-else-if="mood === 'sedih'">
                        <ellipse cx="35.5" cy="70" rx="6.5" ry="3.5" fill="#FFA4B6" opacity="0.38" />
                        <ellipse cx="84.5" cy="70" rx="6.5" ry="3.5" fill="#FFA4B6" opacity="0.38" />
                    </template>

                    <!-- CAPEK: Pipi Redup dengan Tanda Letih Ringan -->
                    <template v-else-if="mood === 'capek'">
                        <ellipse cx="35.5" cy="70" rx="6" ry="3" fill="#FDA4AF" opacity="0.28" />
                        <ellipse cx="84.5" cy="70" rx="6" ry="3" fill="#FDA4AF" opacity="0.28" />
                        <line x1="32.5" y1="67" x2="38.5" y2="67" stroke="#94A3B8" stroke-width="1.2" stroke-linecap="round" opacity="0.5" />
                        <line x1="81.5" y1="67" x2="87.5" y2="67" stroke="#94A3B8" stroke-width="1.2" stroke-linecap="round" opacity="0.5" />
                    </template>

                    <!-- SENANG (DEFAULT): Pipi Merah Muda Hangat Ceria -->
                    <template v-else>
                        <ellipse cx="35.5" cy="69.5" rx="7.2" ry="4" fill="#FF8FA3" opacity="0.52" />
                        <ellipse cx="84.5" cy="69.5" rx="7.2" ry="4" fill="#FF8FA3" opacity="0.52" />
                    </template>

                    <!-- ================= 2. ALIS SESUAI MOOD ================= -->
                    <!-- SEMANGAT: Alis Miring Tegas Penuh Tekad -->
                    <g v-if="mood === 'semangat'">
                        <path d="M 39 51 Q 45 46 51 46.5" fill="none" stroke="#2C1D17" stroke-width="2.6" stroke-linecap="round" />
                        <path d="M 69 46.5 Q 75 46 81 51" fill="none" stroke="#2C1D17" stroke-width="2.6" stroke-linecap="round" />
                    </g>

                    <!-- SEDIH: Alis Melengkung Turun ke Samping (Sendu Pouty) -->
                    <g v-else-if="mood === 'sedih'">
                        <path d="M 39 46 Q 44.5 49 50 51.5" fill="none" stroke="#432E24" stroke-width="2.2" stroke-linecap="round" />
                        <path d="M 70 51.5 Q 75.5 49 81 46" fill="none" stroke="#432E24" stroke-width="2.2" stroke-linecap="round" />
                    </g>

                    <!-- CAPEK: Alis Datar Lemas / Letih -->
                    <g v-else-if="mood === 'capek'">
                        <path d="M 39.5 50.5 Q 45 52 50.5 51.5" fill="none" stroke="#5E4336" stroke-width="2.1" stroke-linecap="round" />
                        <path d="M 69.5 51.5 Q 75 52 80.5 50.5" fill="none" stroke="#5E4336" stroke-width="2.1" stroke-linecap="round" />
                    </g>

                    <!-- SENANG: Alis Melengkung Ramah Ceria -->
                    <g v-else>
                        <path d="M 39.5 49.5 Q 44.5 47 49.5 49" fill="none" stroke="#432E24" stroke-width="2.2" stroke-linecap="round" />
                        <path d="M 70.5 49 Q 75.5 47 80.5 49.5" fill="none" stroke="#432E24" stroke-width="2.2" stroke-linecap="round" />
                    </g>

                    <!-- ================= 3. MATA SESUAI MOOD ================= -->
                    <!-- Mata Mengedip Saat Diklik -->
                    <g v-if="isWinking">
                        <path d="M 39 61.5 Q 44.5 55.5 50 61.5" fill="none" stroke="#231B18" stroke-width="3" stroke-linecap="round" />
                        <path d="M 70 61.5 Q 75.5 55.5 81 61.5" fill="none" stroke="#231B18" stroke-width="3" stroke-linecap="round" />
                    </g>

                    <!-- SEMANGAT: Mata Berbintang Api / Cahaya Menggebu -->
                    <g v-else-if="mood === 'semangat'" class="mascot-eyes-blink">
                        <!-- Bulu Mata Halus Khusus Putri -->
                        <template v-if="gender === 'P'">
                            <path d="M 39.5 57 L 36.5 55 M 80.5 57 L 83.5 55" stroke="#231B18" stroke-width="2" stroke-linecap="round" />
                        </template>

                        <!-- Mata Kiri -->
                        <ellipse cx="44.5" cy="60.5" rx="5.6" ry="6.4" fill="#1C1411" />
                        <!-- Bintang Kilau Utama ✦ -->
                        <path d="M 43 56.5 Q 43 58.5 41 58.5 Q 43 58.5 43 60.5 Q 43 58.5 45 58.5 Q 43 58.5 43 56.5 Z" fill="#FFFFFF" />
                        <circle cx="47" cy="62.8" r="1.3" fill="#FFFFFF" />
                        <circle cx="42.5" cy="63.5" r="0.9" fill="#FEF08A" />

                        <!-- Mata Kanan -->
                        <ellipse cx="75.5" cy="60.5" rx="5.6" ry="6.4" fill="#1C1411" />
                        <!-- Bintang Kilau Utama ✦ -->
                        <path d="M 74 56.5 Q 74 58.5 72 58.5 Q 74 58.5 74 60.5 Q 74 58.5 76 58.5 Q 74 58.5 74 56.5 Z" fill="#FFFFFF" />
                        <circle cx="78" cy="62.8" r="1.3" fill="#FFFFFF" />
                        <circle cx="73.5" cy="63.5" r="0.9" fill="#FEF08A" />
                    </g>

                    <!-- SEDIH: Mata Kaca Berkaca-kaca Penuh Air Mata Haru -->
                    <g v-else-if="mood === 'sedih'" class="mascot-eyes-blink">
                        <!-- Bulu Mata Halus Khusus Putri -->
                        <template v-if="gender === 'P'">
                            <path d="M 39.5 58.5 L 36.8 57 M 80.5 58.5 L 83.2 57" stroke="#231B18" stroke-width="1.8" stroke-linecap="round" />
                        </template>

                        <!-- Mata Kiri -->
                        <ellipse cx="44.5" cy="61.5" rx="5.3" ry="6.2" fill="#231B18" />
                        <ellipse cx="43.2" cy="58.8" rx="2.7" ry="2" fill="#FFFFFF" opacity="0.95" />
                        <ellipse cx="45.5" cy="63.8" rx="2.2" ry="1.5" fill="#BAE6FD" opacity="0.8" />
                        <circle cx="46.8" cy="64" r="0.8" fill="#FFFFFF" />

                        <!-- Mata Kanan -->
                        <ellipse cx="75.5" cy="61.5" rx="5.3" ry="6.2" fill="#231B18" />
                        <ellipse cx="74.2" cy="58.8" rx="2.7" ry="2" fill="#FFFFFF" opacity="0.95" />
                        <ellipse cx="76.5" cy="63.8" rx="2.2" ry="1.5" fill="#BAE6FD" opacity="0.8" />
                        <circle cx="77.8" cy="64" r="0.8" fill="#FFFFFF" />
                    </g>

                    <!-- CAPEK: Mata Mengantuk / Sayu Bergaris Kelopak Rendah -->
                    <g v-else-if="mood === 'capek'" class="mascot-eyes-sleepy">
                        <!-- Mata Kiri -->
                        <path d="M 39 60.5 Q 44.5 57 50 60.5" fill="none" stroke="#231B18" stroke-width="2.6" stroke-linecap="round" />
                        <path d="M 40.5 61 Q 44.5 64.8 48.5 61 Z" fill="#2E1C15" />
                        <circle cx="44" cy="62" r="1.1" fill="#FFFFFF" opacity="0.9" />

                        <!-- Mata Kanan -->
                        <path d="M 70 60.5 Q 75.5 57 81 60.5" fill="none" stroke="#231B18" stroke-width="2.6" stroke-linecap="round" />
                        <path d="M 71.5 61 Q 75.5 64.8 79.5 61 Z" fill="#2E1C15" />
                        <circle cx="75" cy="62" r="1.1" fill="#FFFFFF" opacity="0.9" />
                    </g>

                    <!-- SENANG (DEFAULT): Mata Bulat Berkilau Natural -->
                    <g v-else class="mascot-eyes-blink">
                        <template v-if="gender === 'P'">
                            <path d="M 39.5 57.5 L 36.8 55.5 M 80.5 57.5 L 83.2 55.5" stroke="#231B18" stroke-width="2" stroke-linecap="round" />
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

                    <!-- ================= 4. MULUT SESUAI MOOD ================= -->
                    <!-- SEMANGAT: Mulut Terbuka Ceria Bergigi / Lidah Antusias -->
                    <g v-if="mood === 'semangat'">
                        <path d="M 56.5 68.8 Q 60 67.8 63.5 68.8 Q 63.5 74.5 60 74.5 Q 56.5 74.5 56.5 68.8 Z" fill="#B91C1C" />
                        <path d="M 57.5 72.8 Q 60 71 62.5 72.8 Q 61.5 74.2 60 74.2 Q 58.5 74.2 57.5 72.8 Z" fill="#FB7185" />
                        <path d="M 57.2 69.2 Q 60 68.4 62.8 69.2" fill="none" stroke="#FFFFFF" stroke-width="1.1" stroke-linecap="round" />
                    </g>

                    <!-- SEDIH: Mulut Cemberut Melengkung ke Bawah (Pouty) -->
                    <path
                        v-else-if="mood === 'sedih'"
                        d="M 57 73 Q 60 70.2 63 73"
                        fill="none"
                        stroke="#B86B52"
                        stroke-width="2.2"
                        stroke-linecap="round"
                    />

                    <!-- CAPEK: Mulut Menghela Napas / Menguap Halus -->
                    <g v-else-if="mood === 'capek'">
                        <ellipse cx="60" cy="71" rx="2.5" ry="1.9" fill="#884433" />
                        <ellipse cx="60" cy="71" rx="1.7" ry="1.2" fill="#522419" />
                    </g>

                    <!-- SENANG (DEFAULT): Senyum Bahagia Melengkung Hangat -->
                    <path
                        v-else
                        d="M 57.2 70.5 Q 60 73.5 62.8 70.5"
                        fill="none"
                        stroke="#B86B52"
                        stroke-width="2.1"
                        stroke-linecap="round"
                    />

                    <!-- ================= 5. AKSEN UNIK & ANIMATIF PER MOOD ================= -->
                    <!-- SEMANGAT: Bintang Kilau Mengambang di Sekitar Kepala -->
                    <template v-if="mood === 'semangat'">
                        <g class="mascot-sparkle-1">
                            <path d="M 25 21 Q 25 25 21 25 Q 25 25 25 29 Q 25 25 29 25 Q 25 25 25 21 Z" fill="#FBBF24" />
                        </g>
                        <g class="mascot-sparkle-2">
                            <path d="M 95 24 Q 95 27 92 27 Q 95 27 95 30 Q 95 27 98 27 Q 95 27 95 24 Z" fill="#FBBF24" />
                        </g>
                    </template>

                    <!-- SEDIH: Tetes Air Mata Kaca Mengalir di Pipi Kiri -->
                    <template v-else-if="mood === 'sedih'">
                        <g class="mascot-tear">
                            <path d="M 48 67 Q 50 71.5 48 73 Q 46 71.5 48 67 Z" fill="#38BDF8" opacity="0.9" />
                            <circle cx="47.5" cy="70" r="0.6" fill="#FFFFFF" />
                        </g>
                    </template>

                    <!-- CAPEK: Tetes Keringat Anime di Pelipis Kanan + Awan Hembusan Napas -->
                    <template v-else-if="mood === 'capek'">
                        <!-- Tetes Keringat Comic Anime di Pelipis Kanan -->
                        <g class="mascot-sweat">
                            <path d="M 91 36 Q 93.5 40.5 92 42.5 Q 90 42.5 90 40.5 Q 90 38.5 91 36 Z" fill="#38BDF8" opacity="0.95" />
                            <path d="M 91.8 38.5 Q 92.3 40.5 91.8 41.5" stroke="#FFFFFF" stroke-width="0.8" stroke-linecap="round" fill="none" />
                        </g>
                        <!-- Kepulan Hembusan Napas Kecil di Samping Mulut -->
                        <g class="mascot-sigh">
                            <circle cx="67" cy="69" r="1.3" fill="#E2E8F0" opacity="0.75" />
                            <circle cx="70.5" cy="67.5" r="1.9" fill="#E2E8F0" opacity="0.65" />
                            <circle cx="74.5" cy="65.5" r="2.5" fill="#E2E8F0" opacity="0.5" />
                        </g>
                    </template>
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
/* Kedipan mata natural */
@keyframes naturalBlink2D {
    0%, 42%, 46%, 84%, 87.5%, 89.5%, 93%, 100% {
        transform: scaleY(1);
    }
    44%, 86%, 91% {
        transform: scaleY(0.08);
    }
}

/* Kedipan mata sayu / capek (lebih lambat & berat) */
@keyframes sleepyBlink2D {
    0%, 60%, 100% {
        transform: scaleY(1);
    }
    65%, 68% {
        transform: scaleY(0.12);
    }
}

/* Gerakan mengintip default (Senang) */
@keyframes gentlePeek2D {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(2px);
    }
}

/* Gerakan mengintip Semangat (lebih enerjik & memantul) */
@keyframes spiritedPeek2D {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
    }
    30% {
        transform: translateY(-2px) rotate(-1deg);
    }
    60% {
        transform: translateY(1.5px) rotate(1deg);
    }
}

/* Gerakan mengintip Sedih (sedikit menunduk sendu) */
@keyframes sadPeek2D {
    0%, 100% {
        transform: translateY(1px);
    }
    50% {
        transform: translateY(2.5px);
    }
}

/* Gerakan mengintip Capek (terkulai lemas pelan) */
@keyframes tiredPeek2D {
    0%, 100% {
        transform: translateY(1.5px);
    }
    50% {
        transform: translateY(3px);
    }
}

/* Kilauan Bintang Semangat */
@keyframes sparkleAnim {
    0%, 100% {
        transform: scale(0.8) rotate(0deg);
        opacity: 0.6;
    }
    50% {
        transform: scale(1.2) rotate(15deg);
        opacity: 1;
    }
}

/* Tetes Air Mata Sedih */
@keyframes tearAnim {
    0%, 100% {
        transform: translateY(0px);
        opacity: 0.85;
    }
    50% {
        transform: translateY(1.8px);
        opacity: 1;
    }
}

/* Tetes Keringat Capek */
@keyframes sweatAnim {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
    }
    50% {
        transform: translateY(1.5px) rotate(3deg);
    }
}

/* Hembusan Napas Capek */
@keyframes sighAnim {
    0%, 100% {
        transform: scale(0.8) translate(0, 0);
        opacity: 0.35;
    }
    50% {
        transform: scale(1.1) translate(1.5px, -1.5px);
        opacity: 0.75;
    }
}

.mascot-eyes-blink {
    transform-box: fill-box;
    transform-origin: center 62%;
    animation: naturalBlink2D 5.6s ease-in-out infinite;
}

.mascot-eyes-sleepy {
    transform-box: fill-box;
    transform-origin: center 62%;
    animation: sleepyBlink2D 6.8s ease-in-out infinite;
}

.mascot-head-peek {
    transform-origin: 60px 79px;
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.mascot-mood-senang {
    animation: gentlePeek2D 4.5s ease-in-out infinite;
}

.mascot-mood-semangat {
    animation: spiritedPeek2D 3.2s ease-in-out infinite;
}

.mascot-mood-sedih {
    animation: sadPeek2D 5.5s ease-in-out infinite;
}

.mascot-mood-capek {
    animation: tiredPeek2D 6s ease-in-out infinite;
}

.mascot-sparkle-1 {
    transform-origin: 25px 25px;
    animation: sparkleAnim 1.8s ease-in-out infinite;
}

.mascot-sparkle-2 {
    transform-origin: 95px 27px;
    animation: sparkleAnim 1.8s ease-in-out infinite 0.9s;
}

.mascot-tear {
    transform-origin: 48px 70px;
    animation: tearAnim 2.8s ease-in-out infinite;
}

.mascot-sweat {
    transform-origin: 91px 39px;
    animation: sweatAnim 2.4s ease-in-out infinite;
}

.mascot-sigh {
    transform-origin: 70px 67px;
    animation: sighAnim 3.2s ease-in-out infinite;
}

.mascot-head-bounce {
    transform: translateY(-3px) !important;
}

@media (prefers-reduced-motion: reduce) {
    .mascot-eyes-blink,
    .mascot-eyes-sleepy,
    .mascot-head-peek,
    .mascot-sparkle-1,
    .mascot-sparkle-2,
    .mascot-tear,
    .mascot-sweat,
    .mascot-sigh {
        animation: none;
    }
}
</style>
