<script setup>
/**
 * Fitur wajah maskot (pipi, alis, mata, mulut, aksen mood) dalam koordinat kepala 120x88.
 * Dipakai bersama oleh PeekingMascot2D dan RidingMascot2D agar wajah selalu identik.
 */
defineProps({
    gender: {
        type: String,
        default: 'L',
    },
    mood: {
        type: String,
        default: 'senang',
    },
    isWinking: {
        type: Boolean,
        default: false,
    },
    showAccents: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <g>
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
        <template v-if="showAccents">
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
        </template>
    </g>
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

@media (prefers-reduced-motion: reduce) {
    .mascot-eyes-blink,
    .mascot-eyes-sleepy,
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

