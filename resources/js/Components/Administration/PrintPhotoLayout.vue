<script setup>
import { computed } from 'vue';

const props = defineProps({
    students: { type: Array, default: () => [] },
    settings: {
        type: Object,
        default: () => ({
            photoSize: '3x4',
            paperSize: 'A4',
            showName: true,
        }),
    },
});

// Dimensi foto (lebar x tinggi) dalam cm
const dimensions = computed(() => {
    if (props.settings.photoSize === '4x6') {
        return { w: 3.8, h: 5.8 };
    }
    // Default 3x4
    return { w: 2.8, h: 3.8 };
});

const paperClass = computed(() => {
    return props.settings.paperSize === 'F4' ? 'print-paper-f4' : 'print-paper-a4';
});
</script>

<template>
    <div class="print-layout-container" :class="paperClass">
        <!-- Flex wrap container for the photos -->
        <div class="photo-grid">
            <div
                v-for="(student, index) in students"
                :key="student.nisn + '-' + index"
                class="photo-card"
                :style="{
                    width: (dimensions.w + 0.4) + 'cm', // Tambah 0.4cm untuk padding kotak
                    height: (dimensions.h + (settings.showName ? 1.6 : 0.4)) + 'cm' // Tambah space untuk teks dan padding
                }"
            >
                <div class="photo-frame" :style="{ width: dimensions.w + 'cm', height: dimensions.h + 'cm' }">
                    <img v-if="student.photo_url" :src="student.photo_url" :alt="student.nama" class="photo-img" />
                    <div v-else class="no-photo">Tanpa Foto</div>
                </div>

                <div v-if="settings.showName" class="photo-info">
                    <div class="photo-name">{{ student.nama }}</div>
                    <div class="photo-nisn">{{ student.nisn }}</div>
                    <div class="photo-kelas">{{ student.kelas || '-' }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/*
 * Style khusus untuk komponen ini (akan aktif saat print jika wrapper utamanya ditampilkan).
 * Semua ukuran menggunakan absolute units (cm, mm, pt) agar presisi di kertas cetak.
 */

.print-layout-container {
    background-color: white;
    color: black;
    margin: 0;
    padding: 0;
    width: 100%;
}

.photo-grid {
    display: flex;
    flex-wrap: wrap;
    align-content: flex-start;
    /* Jarak antar kotak foto */
    gap: 0.5cm;
    /* Margin area aman dari tepi kertas */
    padding: 1cm;
}

.photo-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    /* Kotak hitam outline tidak rounded */
    border: 1px solid black;
    border-radius: 0;
    padding: 0.2cm;
    page-break-inside: avoid;
    break-inside: avoid;
    box-sizing: border-box;
}

.photo-frame {
    /* Foto tidak rounded */
    border-radius: 0;
    background-color: #f8f8f8;
    box-sizing: border-box;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.photo-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: top center;
    border-radius: 0;
}

.no-photo {
    font-size: 8pt;
    color: #999;
    font-family: sans-serif;
}

.photo-info {
    margin-top: 0.15cm;
    text-align: center;
    font-family: Arial, sans-serif;
    line-height: 1.2;
    width: 100%;
}

.photo-name {
    font-size: 7pt;
    font-weight: bold;
    /* Truncate text */
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

.photo-nisn, .photo-kelas {
    font-size: 6pt;
    color: #111;
}
</style>

<!-- Unscoped style khusus untuk rules @page -->
<style>
@media print {
    /* Seting halaman dasar via class di body (akan diset dari parent) */
    body.is-printing-a4 {
        margin: 0;
        padding: 0;
    }
    body.is-printing-f4 {
        margin: 0;
        padding: 0;
    }

    @page {
        margin: 0; /* Hapus margin bawaan browser */
    }
}
</style>
