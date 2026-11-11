<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    students: {
        type: Array,
        default: () => [],
    },
    academicYear: {
        type: String,
        default: '',
    },
    unit: {
        type: String,
        default: 'Semua Unit',
    },
});

// State Filter & Pencarian Bar Utama
const searchQuery = ref('');
const filterLimit = ref('10'); // '5', '10', '20', 'all'

// State Modal Pop-up Rincian Siswa
const selectedSchoolModal = ref(null);
const modalSearch = ref('');

function openSchoolModal(schoolItem) {
    selectedSchoolModal.value = schoolItem;
    modalSearch.value = '';
}

function closeSchoolModal() {
    selectedSchoolModal.value = null;
    modalSearch.value = '';
}

function handleKeyDown(e) {
    if (e.key === 'Escape' && selectedSchoolModal.value) {
        closeSchoolModal();
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
});

/**
 * Normalisasi nama sekolah asal:
 * - Menyatukan variasi ejaan umum (MIM vs MI Muhammadiyah, SDN vs SD Negeri, SDIT vs SD IT, dll)
 * - Menghilangkan tanda baca titik/strip berlebih
 * - Menangani nomor sekolah dengan angka nol depan (SDN 01 -> SDN 1)
 * - Mengembalikan 'Belum Terdata' jika nilai kosong / null / strip
 */
function normalizeSchoolName(raw) {
    if (!raw || typeof raw !== 'string') return 'Belum Terdata';
    let s = raw.trim();
    if (!s) return 'Belum Terdata';

    const lower = s.toLowerCase();
    const emptyIndicators = [
        '-', '--', '---', '.', '_', 'none', 'null', 'nil',
        'tidak ada', 'belum ada', 'belum', 'tidak tahu', 'tidak diisi', 'kosong', '?',
        'tidak berkesempatan', 'belum sekolah', 'belum bersekolah'
    ];
    if (emptyIndicators.includes(lower)) {
        return 'Belum Terdata';
    }

    // Bersihkan whitespace ganda / tab
    s = s.replace(/[\t\r\n]+/g, ' ').replace(/\s{2,}/g, ' ');

    // 1. MIM / MI Muhammadiyah
    if (/^(m\.?i\.?\s*muhamm?adiyah|mim)\b/i.test(s)) {
        s = s.replace(/^(m\.?i\.?\s*muhamm?adiyah|mim)\s*/i, 'MIM ');
    }
    // 2. SDN / SD Negeri / SD N / SD Neg.
    else if (/^(s\.?d\.?\s*nege?ri|sdn|s\.?d\.?\s*n|sd\s*neg\.?)\b/i.test(s)) {
        s = s.replace(/^(s\.?d\.?\s*nege?ri|sdn|s\.?d\.?\s*n|sd\s*neg\.?)\s*/i, 'SDN ');
    }
    // 3. SDIT / SD IT / SD-IT / SD Islam Terpadu
    else if (/^(s\.?d\.?\s*islam\s*terpadu|sdit|sd[\s\-_]+it)\b/i.test(s)) {
        s = s.replace(/^(s\.?d\.?\s*islam\s*terpadu|sdit|sd[\s\-_]+it)\s*/i, 'SDIT ');
    }
    // 4. SDS / SD Swasta
    else if (/^(s\.?d\.?\s*swasta|sds)\b/i.test(s)) {
        s = s.replace(/^(s\.?d\.?\s*swasta|sds)\s*/i, 'SDS ');
    }
    // 5. SMPN / SMP Negeri / SMP N
    else if (/^(s\.?m\.?p\.?\s*nege?ri|smpn|s\.?m\.?p\.?\s*n)\b/i.test(s)) {
        s = s.replace(/^(s\.?m\.?p\.?\s*nege?ri|smpn|s\.?m\.?p\.?\s*n)\s*/i, 'SMPN ');
    }
    // 6. SMPIT / SMP IT / SMP Islam Terpadu
    else if (/^(s\.?m\.?p\.?\s*islam\s*terpadu|smpit|smp[\s\-_]+it)\b/i.test(s)) {
        s = s.replace(/^(s\.?m\.?p\.?\s*islam\s*terpadu|smpit|smp[\s\-_]+it)\s*/i, 'SMPIT ');
    }
    // 7. MTsN / MTs Negeri / MTs N
    else if (/^(m\.?t\.?s\.?\s*nege?ri|mtsn|m\.?t\.?s\.?\s*n)\b/i.test(s)) {
        s = s.replace(/^(m\.?t\.?s\.?\s*nege?ri|mtsn|m\.?t\.?s\.?\s*n)\s*/i, 'MTsN ');
    }
    // 8. MTsM / MTs Muhammadiyah
    else if (/^(m\.?t\.?s\.?\s*muhamm?adiyah|mtsm)\b/i.test(s)) {
        s = s.replace(/^(m\.?t\.?s\.?\s*muhamm?adiyah|mtsm)\s*/i, 'MTsM ');
    }
    // 9. TK ABA / TK Aisyiyah Bustanul Athfal / TK Aisyiyah
    else if (/^(t\.?k\.?\s*'?aisyiyah(\s*bustanul\s*athfal)?|tk[\s\-_]+aba|aba)\b/i.test(s)) {
        s = s.replace(/^(t\.?k\.?\s*'?aisyiyah(\s*bustanul\s*athfal)?|tk[\s\-_]+aba|aba)\s*/i, 'TK ABA ');
    }
    // 10. TK Pertiwi
    else if (/^(t\.?k\.?\s*pertiwi)\b/i.test(s)) {
        s = s.replace(/^(t\.?k\.?\s*pertiwi)\s*/i, 'TK Pertiwi ');
    }
    // 11. TKIT / TK IT / TK Islam Terpadu
    else if (/^(t\.?k\.?\s*islam\s*terpadu|tkit|tk[\s\-_]+it)\b/i.test(s)) {
        s = s.replace(/^(t\.?k\.?\s*islam\s*terpadu|tkit|tk[\s\-_]+it)\s*/i, 'TKIT ');
    }
    // 12. RA / Raudhatul Athfal
    else if (/^(r\.?a\.?\s*raudhatul\s*athfal|raudhatul\s*athfal|r\.?a\.)\b/i.test(s)) {
        s = s.replace(/^(r\.?a\.?\s*raudhatul\s*athfal|raudhatul\s*athfal|r\.?a\.)\s*/i, 'RA ');
    }
    // 13. Pondok Pesantren / Ponpes / PP.
    else if (/^(pondok\s*pesantren|ponpes|p\.?p\.)\b/i.test(s)) {
        s = s.replace(/^(pondok\s*pesantren|ponpes|p\.?p\.)\s*/i, 'Ponpes ');
    }

    // Normalisasi angka nol depan (SDN 01 Gondangrejo -> SDN 1 Gondangrejo)
    s = s.replace(/^(SDN|MIM|SMPN|MTsN|SDS|SMPIT|SDIT|TKIT|TK ABA|TK Pertiwi|RA)\s+0+(\d+)\b/i, (m, p1, p2) => `${p1} ${p2}`);

    // Standardisasi Kapitalisasi (Title Case), pertahankan singkatan instansi
    const acronyms = new Set([
        'SDN', 'MIM', 'SDIT', 'SMPN', 'SMPIT', 'MTsN', 'MTsM', 'SDS', 'TKIT', 'TK', 'ABA',
        'RA', 'PAUD', 'KB', 'PKBM', 'MI', 'SD', 'SMP', 'MTS', 'SMA', 'SMK', 'MA', 'IT', 'TQ', 'PPTQ'
    ]);

    const words = s.split(' ').filter(Boolean).map(word => {
        const up = word.toUpperCase();
        if (acronyms.has(up)) {
            if (up === 'MTSN') return 'MTsN';
            if (up === 'MTSM') return 'MTsM';
            return up;
        }
        if (/^(I|II|III|IV|V|VI|VII|VIII|IX|X|XI|XII)$/i.test(word)) {
            return word.toUpperCase();
        }
        return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
    });

    return words.join(' ').trim();
}

// Agregasi & Normalisasi Data
const originStats = computed(() => {
    const list = props.students || [];
    const schoolMap = {};
    const unrecordedStudents = [];
    const totalStudents = list.length;

    list.forEach(student => {
        const norm = normalizeSchoolName(student.sekolah_asal);
        if (norm === 'Belum Terdata') {
            unrecordedStudents.push(student);
        } else {
            if (!schoolMap[norm]) {
                schoolMap[norm] = [];
            }
            schoolMap[norm].push(student);
        }
    });

    const recordedList = Object.entries(schoolMap)
        .map(([name, studentList]) => ({
            name,
            count: studentList.length,
            percentage: totalStudents > 0 ? Number(((studentList.length / totalStudents) * 100).toFixed(1)) : 0,
            students: studentList.sort((a, b) => (a.nama || '').localeCompare(b.nama || '')),
            isUnrecorded: false,
        }))
        .sort((a, b) => b.count - a.count || a.name.localeCompare(b.name));

    const unrecordedItem = {
        name: 'Belum Terdata',
        count: unrecordedStudents.length,
        percentage: totalStudents > 0 ? Number(((unrecordedStudents.length / totalStudents) * 100).toFixed(1)) : 0,
        students: unrecordedStudents.sort((a, b) => (a.nama || '').localeCompare(b.nama || '')),
        isUnrecorded: true,
    };

    return {
        totalStudents,
        recordedCount: totalStudents - unrecordedStudents.length,
        recordedPercentage: totalStudents > 0 ? Number((((totalStudents - unrecordedStudents.length) / totalStudents) * 100).toFixed(1)) : 0,
        unrecordedCount: unrecordedStudents.length,
        unrecordedPercentage: totalStudents > 0 ? Number(((unrecordedStudents.length / totalStudents) * 100).toFixed(1)) : 0,
        uniqueSchoolCount: recordedList.length,
        schools: recordedList,
        unrecordedItem,
    };
});

// Daftar Sekolah yang Ditampilkan setelah Filter & Pencarian
const displayedSchools = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    let list = [...originStats.value.schools];

    if (q) {
        list = list.filter(item => item.name.toLowerCase().includes(q));
    } else if (filterLimit.value !== 'all') {
        const limit = parseInt(filterLimit.value, 10);
        list = list.slice(0, limit);
    }

    return list;
});

// Nilai count tertinggi untuk proporsi bar
const maxCount = computed(() => {
    let max = 1;
    if (originStats.value.unrecordedCount > max) {
        max = originStats.value.unrecordedCount;
    }
    originStats.value.schools.forEach(s => {
        if (s.count > max) max = s.count;
    });
    return max;
});

// Siswa yang difilter di dalam modal pencarian pop-up
const modalFilteredStudents = computed(() => {
    if (!selectedSchoolModal.value) return [];
    const q = modalSearch.value.trim().toLowerCase();
    const students = selectedSchoolModal.value.students || [];
    if (!q) return students;

    return students.filter(s => {
        const nama = (s.nama || '').toLowerCase();
        const nisn = (s.nisn || '').toLowerCase();
        const kelas = (s.kelas || '').toLowerCase();
        const kec = (s.kecamatan || '').toLowerCase();
        const kab = (s.kabupaten || '').toLowerCase();
        return nama.includes(q) || nisn.includes(q) || kelas.includes(q) || kec.includes(q) || kab.includes(q);
    });
});
</script>

<template>
    <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-6 shadow-xs mb-5">
        <!-- Header: Judul & Ringkasan -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                <div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                        Statistik Asal Sekolah Siswa
                    </h3>
                    <p class="text-[11px] text-slate-400 font-medium mt-0.5">
                        Berdasarkan data registrasi sekolah asal — {{ unit }} (TA {{ academicYear }})
                    </p>
                </div>
            </div>

            <!-- Ringkasan Cepat / Status Pills -->
            <div class="flex items-center gap-2 flex-wrap">
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-emerald-50 border border-emerald-200/70 text-emerald-800 text-[11px] font-bold">
                    <span class="material-symbols-outlined text-[14px]">school</span>
                    <span>{{ originStats.uniqueSchoolCount }} Sekolah Terdata</span>
                </div>
                <button
                    v-if="originStats.unrecordedCount > 0"
                    type="button"
                    @click="openSchoolModal(originStats.unrecordedItem)"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-amber-50 hover:bg-amber-100 border border-amber-200/80 text-amber-800 text-[11px] font-bold transition-all cursor-pointer shadow-2xs"
                    title="Klik untuk melihat siswa yang belum mengisi data asal sekolah"
                >
                    <span class="material-symbols-outlined text-[14px] text-amber-600">help_outline</span>
                    <span>{{ originStats.unrecordedCount }} Belum Terdata ({{ originStats.unrecordedPercentage }}%)</span>
                    <span class="material-symbols-outlined text-[13px] text-amber-500">chevron_right</span>
                </button>
            </div>
        </div>

        <!-- Toolbar: Pencarian Cepat & Filter Limit -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-3 mb-3 border-b border-slate-100 shrink-0">
            <div class="relative w-full sm:w-72">
                <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari nama sekolah asal..."
                    class="w-full pl-8 pr-7 py-1.5 rounded-xl border border-slate-200 text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                />
                <button
                    v-if="searchQuery"
                    type="button"
                    @click="searchQuery = ''"
                    class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-[16px] cursor-pointer"
                >
                    close
                </button>
            </div>

            <!-- Filter Kapsul Limit (Top 5, Top 10, Top 20, Semua) -->
            <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider shrink-0">Tampilkan:</span>
                <div class="inline-flex items-center p-0.5 bg-slate-100 rounded-lg border border-slate-200 text-xs">
                    <button
                        v-for="opt in [
                            { label: 'Top 5', value: '5' },
                            { label: 'Top 10', value: '10' },
                            { label: 'Top 20', value: '20' },
                            { label: 'Semua', value: 'all' },
                        ]"
                        :key="opt.value"
                        type="button"
                        @click="filterLimit = opt.value; searchQuery = ''"
                        class="px-2.5 py-1 rounded-md text-[11px] font-semibold transition-all cursor-pointer"
                        :class="filterLimit === opt.value && !searchQuery
                            ? 'bg-white text-emerald-900 font-bold shadow-2xs'
                            : 'text-slate-500 hover:text-slate-800'"
                    >
                        {{ opt.label }}
                    </button>
                </div>
                <span class="text-[11px] text-slate-400 font-medium hidden md:inline">
                    ({{ displayedSchools.length }} dari {{ originStats.uniqueSchoolCount }} sekolah)
                </span>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- STAT BAR: DATA BELUM TERDATA (KLIK UNTUK POP-UP SISWA)       -->
        <!-- ============================================================ -->
        <div
            v-if="originStats.unrecordedCount > 0 && (!searchQuery || 'belum terdata'.includes(searchQuery.toLowerCase()))"
            @click="openSchoolModal(originStats.unrecordedItem)"
            class="mb-3.5 p-3 rounded-2xl bg-amber-50/70 hover:bg-amber-100/70 border border-amber-200/80 space-y-2 transition-all cursor-pointer group shadow-2xs"
            title="Klik untuk melihat nama-nama siswa yang belum terdata sekolah asalnya"
        >
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex items-start gap-2.5 flex-1">
                    <span class="w-6 h-6 rounded-lg bg-amber-100 border border-amber-300 text-[11px] font-bold text-amber-800 flex items-center justify-center shrink-0 mt-0.5 group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-[14px]">help_outline</span>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-xs sm:text-[13px] font-bold text-amber-950 group-hover:text-amber-900 break-words leading-snug">
                                Belum Terdata / Kosong
                            </span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-amber-200/70 text-amber-900">
                                Perlu Dilengkapi
                            </span>
                        </div>
                        <p class="text-[10.5px] text-amber-700/80 font-medium mt-0.5 break-words">
                            Siswa yang belum memiliki riwayat data sekolah asal di Tab Registrasi
                        </p>
                        <p class="text-[10px] text-amber-800 font-semibold group-hover:underline flex items-center gap-1 mt-1">
                            <span>Klik untuk melihat daftar {{ originStats.unrecordedCount }} siswa</span>
                            <span class="material-symbols-outlined text-[12px]">open_in_new</span>
                        </p>
                    </div>
                </div>

                <!-- Jumlah & Persen Font Beda -->
                <div class="flex items-center gap-1.5 shrink-0 text-xs mt-0.5">
                    <span class="font-extrabold text-amber-950">
                        {{ originStats.unrecordedCount }} Siswa
                    </span>
                    <span class="text-[10.5px] font-bold text-amber-900 bg-amber-100 px-2 py-0.5 rounded-md border border-amber-300">
                        {{ originStats.unrecordedPercentage }}%
                    </span>
                </div>
            </div>

            <!-- Bar Horizontal Belum Terdata -->
            <div class="w-full bg-amber-200/60 rounded-full h-2.5 overflow-hidden">
                <div
                    class="bg-amber-500 h-full rounded-full transition-all duration-500"
                    :style="{ width: `${Math.max((originStats.unrecordedCount / maxCount) * 100, 3)}%` }"
                ></div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- DAFTAR STAT BAR ASAL SEKOLAH (KLIK UNTUK POP-UP SISWA)       -->
        <!-- ============================================================ -->
        <div v-if="displayedSchools.length > 0" class="space-y-2.5 max-h-[520px] overflow-y-auto pr-1.5 custom-school-scrollbar">
            <div
                v-for="(item, idx) in displayedSchools"
                :key="item.name"
                @click="openSchoolModal(item)"
                class="p-2.5 sm:p-3 rounded-2xl bg-slate-50/70 hover:bg-emerald-50/50 hover:border-emerald-200 border border-slate-100 transition-all space-y-2 cursor-pointer group shadow-2xs"
                :title="`Klik untuk melihat nama-nama siswa dari ${item.name}`"
            >
                <!-- Baris Atas: Ranking, Nama Sekolah (Lanjut ke bawah jika panjang / break-words), Jumlah & Persen -->
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex items-start gap-2.5 flex-1">
                        <!-- Ranking Badge -->
                        <span
                            class="w-6 h-6 rounded-lg text-[10.5px] font-bold flex items-center justify-center shrink-0 border shadow-2xs mt-0.5 group-hover:scale-105 transition-transform"
                            :class="{
                                'bg-amber-100 text-amber-900 border-amber-300 font-extrabold': idx === 0 && !searchQuery,
                                'bg-slate-200 text-slate-800 border-slate-300 font-extrabold': idx === 1 && !searchQuery,
                                'bg-amber-50 text-amber-800 border-amber-200 font-bold': idx === 2 && !searchQuery,
                                'bg-white text-emerald-800 border-emerald-200/80': idx > 2 || searchQuery,
                            }"
                            :title="`Peringkat #${idx + 1}`"
                        >
                            {{ idx + 1 }}
                        </span>

                        <!-- Nama Sekolah: Wrap ke baris bawahnya jika tidak muat (bukan titik-titik) -->
                        <div class="min-w-0 flex-1">
                            <h4 class="text-xs sm:text-[13px] font-bold text-slate-900 group-hover:text-emerald-950 break-words leading-snug">
                                {{ item.name }}
                            </h4>
                            <p class="text-[10px] text-slate-400 group-hover:text-emerald-700 font-medium flex items-center gap-1 mt-0.5 transition-colors">
                                <span>Klik untuk melihat {{ item.count }} siswa</span>
                                <span class="material-symbols-outlined text-[12px]">open_in_new</span>
                            </p>
                        </div>
                    </div>

                    <!-- Jumlah Siswa & Persen dengan Warna Font Berbeda -->
                    <div class="flex items-center gap-1.5 shrink-0 text-xs mt-0.5">
                        <span class="font-extrabold text-emerald-800">
                            {{ item.count }} Siswa
                        </span>
                        <span class="text-[10.5px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded-md border border-amber-200/80">
                            {{ item.percentage }}%
                        </span>
                    </div>
                </div>

                <!-- Bar Horizontal Proporsional -->
                <div class="w-full bg-slate-200/70 rounded-full h-2.5 overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :class="{
                            'bg-emerald-700': idx === 0 && !searchQuery,
                            'bg-emerald-600': idx > 0 && idx < 3 && !searchQuery,
                            'bg-emerald-500': idx >= 3 || searchQuery,
                        }"
                        :style="{ width: `${Math.max((item.count / maxCount) * 100, 3)}%` }"
                    ></div>
                </div>
            </div>
        </div>

        <!-- Empty State ketika Pencarian Tidak Ditemukan -->
        <div v-else-if="searchQuery" class="py-10 text-center">
            <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400">
                <span class="material-symbols-outlined text-xl">search_off</span>
            </div>
            <p class="text-xs font-bold text-slate-700">Tidak ada sekolah yang cocok</p>
            <p class="text-[11px] text-slate-400 mt-0.5">
                Coba kata kunci pencarian lain atau klik tombol reset.
            </p>
            <button
                type="button"
                @click="searchQuery = ''"
                class="mt-2.5 px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition-all cursor-pointer"
            >
                Reset Pencarian
            </button>
        </div>

        <!-- Empty State ketika Siswa Belum Ada -->
        <div v-else class="py-10 text-center">
            <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400">
                <span class="material-symbols-outlined text-xl">school</span>
            </div>
            <p class="text-xs font-bold text-slate-700">Belum ada data siswa</p>
            <p class="text-[11px] text-slate-400 mt-0.5">
                Data asal sekolah akan muncul setelah siswa ditambahkan ke sistem.
            </p>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL POP-UP: DAFTAR NAMA SISWA BERDASARKAN ASAL SEKOLAH     -->
    <!-- ============================================================ -->
    <Teleport to="body">
        <div
            v-if="selectedSchoolModal"
            class="fixed inset-0 z-[999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 overflow-y-auto"
            @click.self="closeSchoolModal"
        >
            <div
                class="bg-white rounded-3xl border border-slate-200/90 shadow-2xl max-w-2xl w-full max-h-[85vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150"
            >
                <!-- Modal Header -->
                <div class="px-4 sm:px-6 py-4 border-b border-slate-100 flex items-start justify-between gap-3 bg-slate-50/70 shrink-0">
                    <div class="flex items-start gap-3 min-w-0">
                        <div
                            class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 border shadow-2xs mt-0.5"
                            :class="selectedSchoolModal.isUnrecorded
                                ? 'bg-amber-100 border-amber-300 text-amber-800'
                                : 'bg-emerald-100 border-emerald-300 text-emerald-800'"
                        >
                            <span class="material-symbols-outlined text-[22px]">
                                {{ selectedSchoolModal.isUnrecorded ? 'help_outline' : 'school' }}
                            </span>
                        </div>
                        <div class="min-w-0">
                            <span
                                class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider inline-block mb-1"
                                :class="selectedSchoolModal.isUnrecorded
                                    ? 'bg-amber-200/70 text-amber-900'
                                    : 'bg-emerald-100 text-emerald-900'"
                            >
                                {{ selectedSchoolModal.isUnrecorded ? 'Data Belum Terdata' : 'Sekolah Asal Siswa' }}
                            </span>
                            <h3 class="text-sm sm:text-base font-extrabold text-slate-900 break-words leading-snug">
                                {{ selectedSchoolModal.name }}
                            </h3>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5">
                                Total <span class="font-bold text-slate-800">{{ selectedSchoolModal.count }} Siswa</span>
                                ({{ selectedSchoolModal.percentage }}% dari seluruh siswa) • {{ unit }} (TA {{ academicYear }})
                            </p>
                        </div>
                    </div>

                    <!-- Tombol Tutup X -->
                    <button
                        type="button"
                        @click="closeSchoolModal"
                        class="text-slate-400 hover:text-slate-700 p-1.5 rounded-xl hover:bg-slate-200/60 transition-colors cursor-pointer shrink-0"
                        title="Tutup (ESC)"
                    >
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <!-- Modal Toolbar: Cari Nama Siswa & Counter -->
                <div class="px-4 sm:px-6 py-2.5 bg-white border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 shrink-0">
                    <div class="relative w-full sm:w-80">
                        <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[16px]">search</span>
                        <input
                            v-model="modalSearch"
                            type="text"
                            placeholder="Cari nama, NISN, atau kelas..."
                            class="w-full pl-8 pr-7 py-1.5 rounded-xl border border-slate-200 text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                        />
                        <button
                            v-if="modalSearch"
                            type="button"
                            @click="modalSearch = ''"
                            class="material-symbols-outlined absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-[15px] cursor-pointer"
                        >
                            close
                        </button>
                    </div>

                    <span class="text-[11px] font-semibold text-slate-500">
                        Menampilkan {{ modalFilteredStudents.length }} dari {{ selectedSchoolModal.count }} siswa
                    </span>
                </div>

                <!-- Modal Body: Daftar Siswa (Scrollable) -->
                <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-3 space-y-2 custom-school-scrollbar">
                    <div
                        v-if="modalFilteredStudents.length > 0"
                        class="space-y-2"
                    >
                        <div
                            v-for="(student, sIdx) in modalFilteredStudents"
                            :key="student.id || sIdx"
                            class="p-2.5 sm:p-3 rounded-2xl bg-slate-50/80 hover:bg-emerald-50/40 border border-slate-200/80 flex items-center justify-between gap-3 transition-colors"
                        >
                            <!-- Identitas Siswa -->
                            <div class="min-w-0 flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-lg bg-white border border-slate-200 text-[10.5px] font-bold text-slate-600 flex items-center justify-center shrink-0">
                                    {{ sIdx + 1 }}
                                </span>

                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-xs sm:text-[13px] font-bold text-slate-900 break-words">
                                            {{ student.nama }}
                                        </span>
                                        <!-- Gender Badge -->
                                        <span
                                            class="text-[9.5px] font-bold px-1.5 py-0.2 rounded border"
                                            :class="student.jk === 'L'
                                                ? 'bg-sky-50 text-sky-800 border-sky-200'
                                                : 'bg-rose-50 text-rose-800 border-rose-200'"
                                        >
                                            {{ student.jk === 'L' ? 'Laki-laki' : (student.jk === 'P' ? 'Perempuan' : student.jk) }}
                                        </span>
                                    </div>

                                    <!-- Informasi Kelas, NISN, & Domisili -->
                                    <div class="flex items-center gap-2 text-[10.5px] text-slate-500 font-medium flex-wrap mt-0.5">
                                        <span v-if="student.kelas" class="font-bold text-emerald-800 bg-emerald-50 px-1.5 py-0.2 rounded border border-emerald-200/70">
                                            Kelas {{ student.kelas }}
                                        </span>
                                        <span v-if="student.nisn">NISN: {{ student.nisn }}</span>
                                        <span v-if="student.unit" class="text-slate-400">• {{ student.unit }}</span>
                                        <span v-if="student.kecamatan" class="text-slate-400">• Kec. {{ student.kecamatan }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tombol Link Buka Detail Profil Siswa -->
                            <a
                                :href="`/students/${student.id}`"
                                target="_blank"
                                class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 hover:text-emerald-900 bg-white hover:bg-emerald-50 px-2.5 py-1.5 rounded-xl border border-slate-200 hover:border-emerald-300 shadow-2xs transition-all shrink-0 cursor-pointer"
                                title="Buka profil siswa di tab baru"
                            >
                                <span class="hidden sm:inline">Detail</span>
                                <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                            </a>
                        </div>
                    </div>

                    <!-- Empty Search Inside Modal -->
                    <div v-else class="py-12 text-center">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400">
                            <span class="material-symbols-outlined text-xl">person_search</span>
                        </div>
                        <p class="text-xs font-bold text-slate-700">Tidak ada siswa yang cocok</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            Coba kata kunci pencarian nama atau NISN lain.
                        </p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-4 sm:px-6 py-3 border-t border-slate-100 bg-slate-50/70 flex items-center justify-between gap-3 shrink-0">
                    <span class="text-[11px] text-slate-400 font-medium">
                        Tekan <kbd class="px-1.5 py-0.5 rounded bg-white border border-slate-300 text-[10px] font-mono text-slate-600 shadow-2xs">ESC</kbd> untuk menutup
                    </span>
                    <button
                        type="button"
                        @click="closeSchoolModal"
                        class="px-4 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition-all cursor-pointer shadow-xs"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.custom-school-scrollbar::-webkit-scrollbar {
    width: 5px;
}
.custom-school-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 9999px;
}
.custom-school-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 9999px;
}
.custom-school-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
