<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend, ChartDataLabels);

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    students: {
        type: Array,
        default: () => [],
    },
    studentsPerClass: {
        type: Array,
        default: () => [],
    },
    selectedAcademicYear: {
        type: Object,
        default: null,
    },
});

// ============================================================
// Scroll & Sticky Title Observer (Identik dengan Data Siswa & Pegawai)
// ============================================================
const pageContainerRef = ref(null);
const mainTitleRef = ref(null);
const isScrolledPastTitle = ref(false);
let titleObserver = null;

function checkTitleVisibility() {
    if (mainTitleRef.value) {
        const rect = mainTitleRef.value.getBoundingClientRect();
        isScrolledPastTitle.value = rect.bottom < 15;
    }
}

onMounted(() => {
    window.addEventListener('scroll', checkTitleVisibility, { passive: true });

    if (pageContainerRef.value) {
        pageContainerRef.value.addEventListener('scroll', checkTitleVisibility, { passive: true });
    }

    if ('IntersectionObserver' in window && mainTitleRef.value) {
        titleObserver = new IntersectionObserver(
            ([entry]) => {
                if (!entry.isIntersecting && entry.boundingClientRect.top < 50) {
                    isScrolledPastTitle.value = true;
                } else if (entry.isIntersecting) {
                    isScrolledPastTitle.value = false;
                }
            },
            {
                root: null,
                threshold: 0,
            }
        );
        titleObserver.observe(mainTitleRef.value);
    }
});

onUnmounted(() => {
    window.removeEventListener('scroll', checkTitleVisibility);
    if (pageContainerRef.value) {
        pageContainerRef.value.removeEventListener('scroll', checkTitleVisibility);
    }
    if (titleObserver) {
        titleObserver.disconnect();
    }
});

// ============================================================
// Helper Filter Unit Sekolah (Semua Jenjang, SDIT, SMPIT)
// ============================================================
const activeUnit = ref('all');
const chartMode = ref('total'); // 'total' | 'gender'

function matchesUnit(unitValue, targetUnit) {
    if (targetUnit === 'all') return true;
    if (!unitValue) return false;
    const u = String(unitValue).toUpperCase().trim();
    if (targetUnit === 'SDIT') {
        return u.includes('SDIT') || u.includes('SD IT') || u === 'SD';
    }
    if (targetUnit === 'SMPIT') {
        return u.includes('SMPIT') || u.includes('SMP IT') || u === 'SMP';
    }
    return u === targetUnit.toUpperCase();
}

function getActiveCountForUnit(unitId) {
    if (props.students && props.students.length > 0) {
        return props.students.filter(s => {
            const isActive = (s.student_status || 'aktif').toLowerCase() === 'aktif';
            return isActive && matchesUnit(s.unit, unitId);
        }).length;
    }
    return props.studentsPerClass
        .filter(item => matchesUnit(item.unit, unitId))
        .reduce((sum, item) => sum + (Number(item.total) || 0), 0);
}

const unitTabs = computed(() => [
    { id: 'all', label: 'Semua Jenjang', icon: 'school', count: getActiveCountForUnit('all') },
    { id: 'SDIT', label: 'SDIT', icon: 'school', count: getActiveCountForUnit('SDIT') },
    { id: 'SMPIT', label: 'SMPIT', icon: 'domain', count: getActiveCountForUnit('SMPIT') },
]);

const activeUnitLabel = computed(() => {
    if (activeUnit.value === 'all') return 'Semua Jenjang Pendidikan';
    return unitTabs.value.find(t => t.id === activeUnit.value)?.label ?? activeUnit.value;
});

// ============================================================
// Data Siswa & Kelas Terfilter Berdasarkan Tab Unit
// ============================================================
const filteredStudents = computed(() => {
    if (!props.students || props.students.length === 0) return [];
    return props.students.filter(s => matchesUnit(s.unit, activeUnit.value));
});

const filteredClasses = computed(() => {
    return props.studentsPerClass.filter(item => matchesUnit(item.unit, activeUnit.value));
});

// ============================================================
// Perhitungan Metrik Utama (Total, Aktif, Tidak Aktif, L, P)
// ============================================================
const metrics = computed(() => {
    if (props.students && props.students.length > 0) {
        const list = filteredStudents.value;
        const total = list.length;
        let active = 0;
        let inactive = 0;
        let male = 0;
        let female = 0;

        list.forEach(s => {
            const isActive = (s.student_status || 'aktif').toLowerCase() === 'aktif';
            const jk = String(s.jk || '').toUpperCase().trim();
            if (isActive) {
                active++;
                if (jk === 'L') male++;
                else if (jk === 'P') female++;
            } else {
                inactive++;
            }
        });

        return {
            total,
            active,
            inactive,
            male,
            female,
        };
    }

    // Fallback jika hanya studentsPerClass yang tersedia
    const classList = filteredClasses.value;
    const active = classList.reduce((sum, item) => sum + (Number(item.total) || 0), 0);
    const male = classList.reduce((sum, item) => sum + (Number(item.male) || 0), 0);
    const female = classList.reduce((sum, item) => sum + (Number(item.female) || 0), 0);

    return {
        total: active,
        active,
        inactive: 0,
        male,
        female,
    };
});

const totalClasses = computed(() => filteredClasses.value.length);

const avgStudentsPerClass = computed(() => {
    if (totalClasses.value === 0) return 0;
    const classTotal = filteredClasses.value.reduce((sum, item) => sum + (Number(item.total) || 0), 0);
    const base = classTotal > 0 ? classTotal : metrics.value.active;
    return Math.round(base / totalClasses.value);
});

const genderTotal = computed(() => {
    const sum = metrics.value.male + metrics.value.female;
    return sum > 0 ? sum : 0;
});

const malePercentage = computed(() => {
    if (genderTotal.value === 0) return 0;
    return Math.round((metrics.value.male / genderTotal.value) * 100);
});

const femalePercentage = computed(() => {
    if (genderTotal.value === 0) return 0;
    return 100 - malePercentage.value;
});

const activePercentage = computed(() => {
    if (metrics.value.total === 0) return 0;
    return Math.round((metrics.value.active / metrics.value.total) * 100);
});

// ============================================================
// Statistik Per Unit Sekolah (SDIT vs SMPIT)
// ============================================================
const unitBreakdown = computed(() => {
    const units = ['SDIT', 'SMPIT'];
    return units.map(unitKey => {
        let active = 0;
        let male = 0;
        let female = 0;
        let inactive = 0;

        if (props.students && props.students.length > 0) {
            props.students.forEach(s => {
                if (!matchesUnit(s.unit, unitKey)) return;
                const isAct = (s.student_status || 'aktif').toLowerCase() === 'aktif';
                const jk = String(s.jk || '').toUpperCase().trim();
                if (isAct) {
                    active++;
                    if (jk === 'L') male++;
                    else if (jk === 'P') female++;
                } else {
                    inactive++;
                }
            });
        } else {
            props.studentsPerClass.forEach(c => {
                if (!matchesUnit(c.unit, unitKey)) return;
                active += Number(c.total) || 0;
                male += Number(c.male) || 0;
                female += Number(c.female) || 0;
            });
        }

        const classCount = props.studentsPerClass.filter(c => matchesUnit(c.unit, unitKey)).length;

        return {
            unit: unitKey,
            active,
            inactive,
            total: active + inactive,
            male,
            female,
            classCount,
        };
    });
});

const maxUnitActive = computed(() => {
    if (!unitBreakdown.value.length) return 1;
    return Math.max(...unitBreakdown.value.map(u => u.active), 1);
});

// ============================================================
// Statistik Sebaran Angkatan Siswa Aktif
// ============================================================
const angkatanStats = computed(() => {
    const map = new Map();
    filteredStudents.value.forEach(s => {
        if ((s.student_status || 'aktif').toLowerCase() !== 'aktif') return;
        const ang = s.angkatan ? `Angkatan ${s.angkatan}` : 'Belum Diatur';
        if (!map.has(ang)) {
            map.set(ang, { label: ang, raw: s.angkatan || 0, total: 0, male: 0, female: 0 });
        }
        const entry = map.get(ang);
        entry.total++;
        const jk = String(s.jk || '').toUpperCase().trim();
        if (jk === 'L') entry.male++;
        else if (jk === 'P') entry.female++;
    });

    return Array.from(map.values())
        .sort((a, b) => Number(b.raw) - Number(a.raw))
        .slice(0, 6);
});

const maxAngkatanTotal = computed(() => {
    if (!angkatanStats.value.length) return 1;
    return Math.max(...angkatanStats.value.map(a => a.total), 1);
});

// ============================================================
// Konfigurasi Grafik Bar (Total Siswa vs Laki-laki & Perempuan)
// ============================================================
const chartData = computed(() => {
    const data = filteredClasses.value;

    if (chartMode.value === 'gender') {
        return {
            labels: data.map(item => item.classroom_name),
            datasets: [
                {
                    label: 'Laki-laki (L)',
                    data: data.map(item => Number(item.male) || 0),
                    backgroundColor: '#047857', // emerald-700
                    hoverBackgroundColor: '#065f46', // emerald-800
                    borderWidth: 0,
                    borderRadius: { topLeft: 6, topRight: 6, bottomLeft: 0, bottomRight: 0 },
                    borderSkipped: 'bottom',
                },
                {
                    label: 'Perempuan (P)',
                    data: data.map(item => Number(item.female) || 0),
                    backgroundColor: '#6ee7b7', // emerald-300 (soft mint senada)
                    hoverBackgroundColor: '#34d399', // emerald-400
                    borderWidth: 0,
                    borderRadius: { topLeft: 6, topRight: 6, bottomLeft: 0, bottomRight: 0 },
                    borderSkipped: 'bottom',
                },
            ],
        };
    }

    return {
        labels: data.map(item => item.classroom_name),
        datasets: [
            {
                label: 'Jumlah Siswa',
                data: data.map(item => Number(item.total) || 0),
                backgroundColor: '#047857', // emerald-700
                hoverBackgroundColor: '#064e3b', // emerald-900
                borderWidth: 0,
                borderRadius: { topLeft: 6, topRight: 6, bottomLeft: 0, bottomRight: 0 },
                borderSkipped: 'bottom',
            },
        ],
    };
});

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    layout: {
        padding: {
            top: 24,
        },
    },
    plugins: {
        legend: {
            display: chartMode.value === 'gender',
            position: 'top',
            align: 'end',
            labels: {
                usePointStyle: true,
                pointStyle: 'circle',
                boxWidth: 8,
                font: { size: 12, weight: '600', family: "'Plus Jakarta Sans', 'Inter', sans-serif" },
                color: '#334155',
            },
        },
        title: { display: false },
        tooltip: {
            backgroundColor: 'rgba(15, 23, 42, 0.92)',
            titleFont: { size: 13, weight: 'bold', family: "'Plus Jakarta Sans', 'Inter', sans-serif" },
            bodyFont: { size: 12, family: "'Plus Jakarta Sans', 'Inter', sans-serif" },
            padding: 12,
            cornerRadius: 12,
            displayColors: true,
            boxPadding: 4,
            callbacks: {
                title: (items) => `Kelas ${items[0].label}`,
                label: (item) => ` ${item.dataset.label}: ${item.raw} Siswa`,
            },
        },
        datalabels: {
            anchor: 'end',
            align: 'top',
            color: (ctx) => {
                if (chartMode.value === 'gender') {
                    return ctx.datasetIndex === 0 ? '#065f46' : '#0f766e';
                }
                return '#047857';
            },
            font: { weight: 'bold', size: 11, family: "'Plus Jakarta Sans', 'Inter', sans-serif" },
            offset: 2,
            formatter: (value) => (value > 0 ? value : ''),
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: {
                font: { size: 11.5, weight: '600', family: "'Plus Jakarta Sans', 'Inter', sans-serif" },
                color: '#475569',
            },
            border: { display: false },
        },
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(226, 232, 240, 0.85)',
                drawBorder: false,
            },
            ticks: {
                font: { size: 11, family: "'Plus Jakarta Sans', 'Inter', sans-serif" },
                color: '#94a3b8',
                stepSize: 5,
                padding: 8,
            },
            border: { display: false },
        },
    },
    animation: {
        duration: 600,
        easing: 'easeOutQuart',
    },
}));

const chartKey = ref(0);
watch([activeUnit, chartMode], () => {
    chartKey.value++;
});

const classTableTotals = computed(() => {
    const total = filteredClasses.value.reduce((sum, item) => sum + (Number(item.total) || 0), 0);
    const male = filteredClasses.value.reduce((sum, item) => sum + (Number(item.male) || 0), 0);
    const female = filteredClasses.value.reduce((sum, item) => sum + (Number(item.female) || 0), 0);
    return { total, male, female };
});

function getClassMalePercent(item) {
    const sum = (Number(item.male) || 0) + (Number(item.female) || 0);
    if (sum <= 0) return 50;
    return Math.round(((Number(item.male) || 0) / sum) * 100);
}
</script>

<template>
    <div ref="pageContainerRef" class="flex-1 overflow-y-auto p-4 md:p-margin-desktop bg-white min-h-screen">
        <Head title="Statistik Siswa - Foundation Data Center" />

        <div class="max-w-[1440px] mx-auto pb-16 md:pb-8">
            <!-- Sticky Title Header Mobile (Muncul otomatis saat judul utama ter-scroll keluar layar, sama seperti Data Siswa & Pegawai) -->
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="-translate-y-full opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="-translate-y-full opacity-0"
            >
                <div
                    v-if="isScrolledPastTitle"
                    class="md:hidden fixed top-0 inset-x-0 z-40 bg-white/95 backdrop-blur-md px-4 py-3 flex items-center justify-center pt-[max(0.85rem,env(safe-area-inset-top))] transition-all shadow-2xs"
                >
                    <h2 class="text-base font-bold text-slate-900 tracking-tight text-center truncate">
                        Statistik Siswa
                    </h2>
                </div>
            </Transition>

            <!-- ============================================================ -->
            <!-- HEADER MOBILE (md:hidden) SESUAI DATA SISWA & DATA PEGAWAI   -->
            <!-- ============================================================ -->
            <div class="md:hidden flex items-center justify-between gap-3 pt-3 sm:pt-4 mb-5">
                <div class="min-w-0">
                    <h1 ref="mainTitleRef" class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Statistik Siswa
                    </h1>
                    <p class="text-xs text-slate-500 mt-1 font-medium truncate">
                        {{ metrics.active }} siswa aktif • {{ activeUnitLabel }}
                    </p>
                </div>

                <div class="shrink-0">
                    <Link
                        href="/students"
                        class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs shadow-xs transition-all cursor-pointer active:scale-95 border border-emerald-950/30"
                    >
                        <span class="material-symbols-outlined text-[17px]">groups</span>
                        <span>Data Siswa</span>
                    </Link>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- HEADER DESKTOP (hidden md:flex) SESUAI DATA SISWA & PEGAWAI  -->
            <!-- ============================================================ -->
            <div class="hidden md:flex mb-5 sm:mb-6 flex-row items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-xl sm:text-2xl md:text-[26px] font-bold text-slate-900 tracking-tight leading-tight">
                        Statistik Siswa
                    </h1>
                    <p class="text-xs sm:text-[13.5px] text-slate-500 mt-1 sm:mt-1.5 font-medium truncate">
                        <strong class="text-slate-700 font-semibold">{{ metrics.active }}</strong> siswa aktif dari
                        <strong class="text-slate-700 font-semibold">{{ metrics.total }}</strong> terdaftar di
                        <strong class="text-slate-700 font-semibold">{{ activeUnitLabel }}</strong>
                        <span v-if="selectedAcademicYear" class="text-slate-400">
                            • TA {{ selectedAcademicYear.name }} ({{ selectedAcademicYear.semester }})
                        </span>
                    </p>
                </div>

                <div class="flex items-center gap-2.5 shrink-0">
                    <Link
                        href="/students"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-semibold text-[13px] transition-all shadow-xs cursor-pointer active:scale-95"
                    >
                        <span class="material-symbols-outlined text-[18px]">groups</span>
                        <span>Direktori Data Siswa</span>
                    </Link>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- SEGMENTED CAPSULE TABS (SAMA DENGAN DATA SISWA & PEGAWAI)    -->
            <!-- ============================================================ -->
            <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="bg-slate-100 p-1 rounded-full inline-flex items-center gap-0.5 overflow-x-auto no-scrollbar max-w-full border border-slate-200/90 shrink-0">
                    <button
                        v-for="tab in unitTabs"
                        :key="tab.id"
                        type="button"
                        @click="activeUnit = tab.id"
                        :class="[
                            'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs sm:text-[13px] transition-all duration-150 cursor-pointer shrink-0',
                            activeUnit === tab.id
                                ? 'bg-emerald-100 text-emerald-950 font-bold border border-emerald-300 shadow-2xs'
                                : 'text-slate-600 hover:text-slate-900 font-medium border border-transparent'
                        ]"
                    >
                        <span class="material-symbols-outlined text-[16px]" :class="{ 'filled-icon': activeUnit === tab.id }">{{ tab.icon }}</span>
                        <span>{{ tab.label }}</span>
                        <span
                            :class="[
                                'px-2 py-0.5 rounded-full text-[10.5px] sm:text-[11px] leading-none transition-colors font-bold',
                                activeUnit === tab.id
                                    ? 'bg-emerald-900 text-yellow-200'
                                    : 'bg-slate-200/90 text-slate-600 font-semibold'
                            ]"
                        >
                            {{ tab.count }}
                        </span>
                    </button>
                </div>

                <!-- Badge Tahun Ajaran Aktif -->
                <div
                    v-if="selectedAcademicYear"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-bold self-start sm:self-auto shrink-0 shadow-2xs"
                >
                    <span class="material-symbols-outlined text-[16px] text-emerald-700">calendar_month</span>
                    <span>Tahun Ajaran {{ selectedAcademicYear.name }} • Semester {{ selectedAcademicYear.semester }}</span>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- 4 CARD STATISTIK UTAMA (WARNA SENADA EMERALD, TEAL & SLATE)  -->
            <!-- ============================================================ -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-5">
                <!-- Card 1: Jumlah Siswa Aktif -->
                <div class="p-3.5 sm:p-4 rounded-2xl bg-emerald-50/60 border border-emerald-200/80 shadow-2xs">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <div class="w-9 h-9 rounded-xl bg-emerald-100/90 border border-emerald-200 flex items-center justify-center text-emerald-800 shrink-0">
                            <span class="material-symbols-outlined text-[20px] filled-icon">groups</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-emerald-900 text-yellow-200">
                            {{ activePercentage }}% Aktif
                        </span>
                    </div>
                    <p class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider">Jumlah Siswa Aktif</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-emerald-950 mt-0.5 leading-tight">{{ metrics.active }}</p>
                    <p class="text-[11px] text-emerald-700/90 font-medium mt-1 truncate">
                        Dari {{ metrics.total }} terdaftar ({{ metrics.inactive }} non-aktif)
                    </p>
                </div>

                <!-- Card 2: Siswa Laki-laki -->
                <div class="p-3.5 sm:p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-200/80 flex items-center justify-center text-emerald-800 shrink-0">
                            <span class="material-symbols-outlined text-[20px]">male</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-md text-[10.5px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            L • {{ malePercentage }}%
                        </span>
                    </div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Siswa Laki-laki</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-0.5 leading-tight">{{ metrics.male }}</p>
                    <p class="text-[11px] text-slate-500 font-medium mt-1 truncate">
                        Peserta didik putra aktif
                    </p>
                </div>

                <!-- Card 3: Siswa Perempuan -->
                <div class="p-3.5 sm:p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <div class="w-9 h-9 rounded-xl bg-teal-50 border border-teal-200/80 flex items-center justify-center text-teal-800 shrink-0">
                            <span class="material-symbols-outlined text-[20px]">female</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-md text-[10.5px] font-bold bg-teal-50 text-teal-800 border border-teal-200">
                            P • {{ femalePercentage }}%
                        </span>
                    </div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Siswa Perempuan</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-0.5 leading-tight">{{ metrics.female }}</p>
                    <p class="text-[11px] text-slate-500 font-medium mt-1 truncate">
                        Peserta didik putri aktif
                    </p>
                </div>

                <!-- Card 4: Jumlah Kelas & Rata-rata -->
                <div class="p-3.5 sm:p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-700 shrink-0">
                            <span class="material-symbols-outlined text-[20px] filled-icon">meeting_room</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            Rombel
                        </span>
                    </div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Jumlah Kelas</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-0.5 leading-tight">{{ totalClasses }}</p>
                    <p class="text-[11px] text-slate-500 font-medium mt-1 truncate">
                        Rata-rata {{ avgStudentsPerClass }} siswa / kelas
                    </p>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- GRID 2 KOLOM: BAR LAKI-LAKI & PEREMPUAN + SEBARAN UNIT       -->
            <!-- ============================================================ -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
                <!-- Kolom Kiri: Bar Siswa Laki-laki dan Perempuan -->
                <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                    Perbandingan Siswa Laki-laki &amp; Perempuan
                                </h3>
                            </div>
                            <span class="text-xs text-slate-500 font-semibold">
                                {{ genderTotal }} Siswa
                            </span>
                        </div>

                        <!-- Stacked Ratio Bar Laki-laki vs Perempuan (Warna Senada Emerald Tua & Mint Lembut) -->
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 mb-5">
                            <div class="flex items-center justify-between text-xs font-bold mb-2">
                                <div class="flex items-center gap-1.5 text-emerald-900">
                                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">L</span>
                                    <span>Laki-laki ({{ malePercentage }}%)</span>
                                </div>
                                <div class="flex items-center gap-1.5 text-teal-800">
                                    <span>Perempuan ({{ femalePercentage }}%)</span>
                                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-teal-50 text-teal-800 border border-teal-200">P</span>
                                </div>
                            </div>

                            <!-- Dual Bar Senada -->
                            <div class="w-full h-3.5 rounded-full bg-slate-200 overflow-hidden flex">
                                <div
                                    v-if="genderTotal > 0"
                                    class="bg-emerald-700 h-full transition-all duration-500"
                                    :style="{ width: `${malePercentage}%` }"
                                    :title="`Laki-laki: ${metrics.male} siswa (${malePercentage}%)`"
                                ></div>
                                <div
                                    v-if="genderTotal > 0"
                                    class="bg-emerald-300 h-full transition-all duration-500"
                                    :style="{ width: `${femalePercentage}%` }"
                                    :title="`Perempuan: ${metrics.female} siswa (${femalePercentage}%)`"
                                ></div>
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-slate-500 font-semibold mt-2">
                                <span>{{ metrics.male }} siswa putra</span>
                                <span>{{ metrics.female }} siswa putri</span>
                            </div>
                        </div>

                        <!-- Bar Individual: Laki-laki & Perempuan -->
                        <div class="space-y-4">
                            <!-- Bar Laki-laki -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2 font-bold text-slate-800">
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200">L</span>
                                        <span>Siswa Laki-laki (Putra)</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <span class="font-extrabold text-emerald-950">{{ metrics.male }} siswa</span>
                                        <span class="text-[11px] font-semibold text-slate-400">({{ malePercentage }}%)</span>
                                    </div>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden border border-slate-200/60">
                                    <div
                                        class="bg-emerald-700 h-full rounded-full transition-all duration-500"
                                        :style="{ width: `${genderTotal > 0 ? Math.max(4, malePercentage) : 0}%` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Bar Perempuan -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2 font-bold text-slate-800">
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-teal-50 text-teal-800 border border-teal-200">P</span>
                                        <span>Siswa Perempuan (Putri)</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <span class="font-extrabold text-teal-900">{{ metrics.female }} siswa</span>
                                        <span class="text-[11px] font-semibold text-slate-400">({{ femalePercentage }}%)</span>
                                    </div>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden border border-slate-200/60">
                                    <div
                                        class="bg-emerald-400 h-full rounded-full transition-all duration-500"
                                        :style="{ width: `${genderTotal > 0 ? Math.max(4, femalePercentage) : 0}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ringkasan Status Keaktifan di Bagian Bawah Card -->
                    <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between gap-2 flex-wrap">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-950 border border-emerald-300">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                <span>Aktif: {{ metrics.active }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                <span>Tidak Aktif: {{ metrics.inactive }}</span>
                            </span>
                        </div>
                        <span class="text-[11px] text-slate-400 font-medium">
                            Total Terdaftar: {{ metrics.total }} siswa
                        </span>
                    </div>
                </div>

                <!-- Kolom Kanan: Sebaran Siswa per Unit Sekolah & Angkatan -->
                <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-6 shadow-xs flex flex-col justify-between space-y-5">
                    <!-- Sub-seksi 1: Sebaran per Unit Sekolah -->
                    <div>
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                    Sebaran Siswa per Unit Sekolah
                                </h3>
                            </div>
                            <span class="text-xs text-slate-500 font-semibold">
                                {{ unitBreakdown.length }} Unit
                            </span>
                        </div>

                        <div class="space-y-3.5">
                            <div
                                v-for="u in unitBreakdown"
                                :key="u.unit"
                                class="p-3.5 rounded-2xl border transition-all cursor-pointer"
                                :class="activeUnit === u.unit ? 'bg-emerald-50/60 border-emerald-300 shadow-2xs' : 'bg-white border-slate-200/90 hover:border-emerald-300'"
                                @click="activeUnit = activeUnit === u.unit ? 'all' : u.unit"
                            >
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs sm:text-[13px] font-bold text-slate-900">{{ u.unit }}</span>
                                        <span class="text-[11px] font-medium text-slate-500">• {{ u.classCount }} kelas</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10.5px] font-bold px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200">
                                            L: {{ u.male }}
                                        </span>
                                        <span class="text-[10.5px] font-bold px-1.5 py-0.5 rounded-md bg-teal-50 text-teal-800 border border-teal-200">
                                            P: {{ u.female }}
                                        </span>
                                        <span class="text-xs font-extrabold text-emerald-950 bg-emerald-100 border border-emerald-300 px-2.5 py-0.5 rounded-full">
                                            {{ u.active }} siswa
                                        </span>
                                    </div>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                    <div
                                        class="bg-emerald-700 h-2.5 rounded-full transition-all duration-500"
                                        :style="{ width: `${u.active > 0 ? Math.max(6, Math.round((u.active / maxUnitActive) * 100)) : 0}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sub-seksi 2: Sebaran per Angkatan -->
                    <div v-if="angkatanStats.length > 0" class="pt-4 border-t border-slate-100">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-teal-600 inline-block"></span>
                                <h4 class="text-[11.5px] font-bold text-slate-800 uppercase tracking-wider">
                                    Distribusi per Angkatan ({{ activeUnitLabel }})
                                </h4>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div
                                v-for="ang in angkatanStats"
                                :key="ang.label"
                                class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5"
                            >
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-slate-800 truncate">{{ ang.label }}</span>
                                    <span class="font-extrabold text-emerald-900 shrink-0 ml-1">{{ ang.total }} siswa</span>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                                    <div
                                        class="bg-teal-600 h-2 rounded-full transition-all duration-500"
                                        :style="{ width: `${Math.max(8, Math.round((ang.total / maxAngkatanTotal) * 100))}%` }"
                                    ></div>
                                </div>
                                <div class="flex items-center justify-between text-[10.5px] text-slate-500 font-medium">
                                    <span>L: {{ ang.male }}</span>
                                    <span>P: {{ ang.female }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- GRAFIK BAR JUMLAH SISWA PER KELAS                            -->
            <!-- ============================================================ -->
            <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-6 shadow-xs mb-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                            Grafik Bar Siswa per Kelas ({{ activeUnitLabel }})
                        </h3>
                    </div>

                    <!-- Capsule Switcher Mode Grafik: Total vs Laki-laki & Perempuan -->
                    <div class="bg-slate-100 p-1 rounded-full inline-flex items-center gap-0.5 border border-slate-200/90 self-start sm:self-auto shrink-0">
                        <button
                            type="button"
                            @click="chartMode = 'total'"
                            :class="[
                                'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs transition-all duration-150 cursor-pointer',
                                chartMode === 'total'
                                    ? 'bg-emerald-100 text-emerald-950 font-bold border border-emerald-300 shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900 font-medium border border-transparent'
                            ]"
                        >
                            <span class="material-symbols-outlined text-[15px]">bar_chart</span>
                            <span>Total Siswa</span>
                        </button>
                        <button
                            type="button"
                            @click="chartMode = 'gender'"
                            :class="[
                                'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs transition-all duration-150 cursor-pointer',
                                chartMode === 'gender'
                                    ? 'bg-emerald-100 text-emerald-950 font-bold border border-emerald-300 shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900 font-medium border border-transparent'
                            ]"
                        >
                            <span class="material-symbols-outlined text-[15px]">wc</span>
                            <span>Bar Laki-laki &amp; Perempuan</span>
                        </button>
                    </div>
                </div>

                <div v-if="filteredClasses.length > 0" class="h-[280px] sm:h-[320px]">
                    <Bar :key="chartKey" :data="chartData" :options="chartOptions" />
                </div>

                <!-- Empty State Grafik -->
                <div v-else class="py-12 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                        <span class="material-symbols-outlined text-2xl">bar_chart</span>
                    </div>
                    <p class="text-sm font-bold text-slate-700">Belum ada data rombel kelas</p>
                    <p class="text-xs text-slate-400 mt-1">
                        Data grafik per kelas akan muncul setelah siswa dipetakan ke kelas pada unit {{ activeUnitLabel }}.
                    </p>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- RINCIAN PER KELAS: CARD LIST (MOBILE) & TABEL (DESKTOP)      -->
            <!-- ============================================================ -->
            <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-6 shadow-xs">
                <div class="flex items-center justify-between mb-4 px-1">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                            Rincian Siswa per Kelas
                        </h3>
                    </div>
                    <span class="text-xs text-slate-500 font-medium">
                        {{ filteredClasses.length }} kelas • {{ classTableTotals.total }} siswa
                    </span>
                </div>

                <template v-if="filteredClasses.length > 0">
                    <!-- Tampilan Mobile: Card List (Identik dengan Card Data Siswa & Data Pegawai) -->
                    <div class="md:hidden space-y-3">
                        <div
                            v-for="(item, idx) in filteredClasses"
                            :key="idx"
                            class="bg-white rounded-2xl border border-slate-200/90 p-3.5 shadow-2xs space-y-2.5"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-11 h-11 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center font-extrabold text-sm text-emerald-900 shrink-0 shadow-2xs">
                                        {{ item.classroom_name }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="text-[13.5px] font-bold text-slate-900 leading-tight">
                                                Kelas {{ item.classroom_name }}
                                            </span>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                                                {{ item.unit }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 font-medium mt-0.5">
                                            Jenjang {{ item.jenjang }}
                                        </p>
                                    </div>
                                </div>

                                <div class="text-right shrink-0">
                                    <span class="text-lg font-extrabold text-emerald-900">{{ item.total }}</span>
                                    <p class="text-[10.5px] text-slate-400 font-semibold">Siswa</p>
                                </div>
                            </div>

                            <!-- Bar & Badge Laki-laki / Perempuan per Kelas (Senada Emerald & Mint) -->
                            <div class="pt-2 border-t border-slate-100 space-y-1.5">
                                <div class="flex items-center justify-between text-[11px] font-bold">
                                    <span class="inline-flex items-center gap-1 text-emerald-800">
                                        <span class="px-1.5 py-0.5 rounded bg-emerald-50 border border-emerald-200 text-[9.5px]">L</span>
                                        <span>{{ item.male || 0 }} Putra</span>
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-teal-800">
                                        <span>{{ item.female || 0 }} Putri</span>
                                        <span class="px-1.5 py-0.5 rounded bg-teal-50 border border-teal-200 text-[9.5px]">P</span>
                                    </span>
                                </div>
                                <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden flex">
                                    <div
                                        class="bg-emerald-700 h-full"
                                        :style="{ width: `${getClassMalePercent(item)}%` }"
                                    ></div>
                                    <div
                                        class="bg-emerald-300 h-full"
                                        :style="{ width: `${100 - getClassMalePercent(item)}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tampilan Desktop: Tabel Hijau Emerald (Identik dengan StudentTable & StaffTable) -->
                    <div class="hidden md:block bg-white rounded-xl border border-emerald-800 shadow-[0_4px_25px_rgba(0,40,20,0.08)] overflow-hidden">
                        <div class="overflow-x-auto custom-scrollbar-x">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-emerald-800 text-white border-b border-emerald-900">
                                        <th class="py-3.5 px-4 w-14 text-center text-[11.5px] font-bold uppercase tracking-wider select-none">
                                            No
                                        </th>
                                        <th class="py-3.5 px-4 text-[11.5px] font-bold uppercase tracking-wider select-none">
                                            Nama Kelas
                                        </th>
                                        <th class="py-3.5 px-4 text-[11.5px] font-bold uppercase tracking-wider select-none">
                                            Unit Sekolah
                                        </th>
                                        <th class="py-3.5 px-4 text-[11.5px] font-bold uppercase tracking-wider text-center select-none">
                                            Jenjang
                                        </th>
                                        <th class="py-3.5 px-4 text-[11.5px] font-bold uppercase tracking-wider text-center select-none">
                                            Laki-laki (L)
                                        </th>
                                        <th class="py-3.5 px-4 text-[11.5px] font-bold uppercase tracking-wider text-center select-none">
                                            Perempuan (P)
                                        </th>
                                        <th class="py-3.5 px-4 w-56 text-[11.5px] font-bold uppercase tracking-wider select-none">
                                            Bar Rasio L / P
                                        </th>
                                        <th class="py-3.5 px-4 text-[11.5px] font-bold uppercase tracking-wider text-right select-none">
                                            Total Siswa
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y-2 divide-slate-200">
                                    <tr
                                        v-for="(item, index) in filteredClasses"
                                        :key="index"
                                        class="hover:bg-slate-50/80 transition-colors"
                                    >
                                        <td class="py-3.5 px-4 text-[12px] text-slate-500 font-medium text-center align-middle">
                                            {{ index + 1 }}
                                        </td>
                                        <td class="py-3.5 px-4 text-[13px] font-bold text-slate-900 align-middle">
                                            Kelas {{ item.classroom_name }}
                                        </td>
                                        <td class="py-3.5 px-4 text-[12.5px] font-semibold text-slate-700 align-middle">
                                            {{ item.unit }}
                                        </td>
                                        <td class="py-3.5 px-4 text-[12px] font-semibold text-slate-600 text-center align-middle">
                                            <span class="px-2.5 py-0.5 rounded-full bg-slate-100 border border-slate-200 text-slate-700">
                                                {{ item.jenjang }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 text-center align-middle">
                                            <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                {{ item.male || 0 }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 text-center align-middle">
                                            <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-teal-50 text-teal-800 border border-teal-200">
                                                {{ item.female || 0 }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 align-middle">
                                            <div class="space-y-1">
                                                <div class="w-full h-2.5 rounded-full bg-slate-100 overflow-hidden flex border border-slate-200/70">
                                                    <div
                                                        class="bg-emerald-700 h-full"
                                                        :style="{ width: `${getClassMalePercent(item)}%` }"
                                                    ></div>
                                                    <div
                                                        class="bg-emerald-300 h-full"
                                                        :style="{ width: `${100 - getClassMalePercent(item)}%` }"
                                                    ></div>
                                                </div>
                                                <div class="flex justify-between text-[10px] font-semibold text-slate-400">
                                                    <span>L: {{ getClassMalePercent(item) }}%</span>
                                                    <span>P: {{ 100 - getClassMalePercent(item) }}%</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4 text-[13.5px] font-extrabold text-emerald-900 text-right align-middle">
                                            {{ item.total }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-emerald-50/80 border-t-2 border-emerald-200">
                                        <td colspan="4" class="py-3.5 px-4 text-xs font-extrabold text-emerald-950 uppercase tracking-wider">
                                            Total Keseluruhan ({{ activeUnitLabel }})
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-md text-xs font-extrabold bg-emerald-100 text-emerald-950 border border-emerald-300">
                                                {{ classTableTotals.male }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-md text-xs font-extrabold bg-teal-100 text-teal-950 border border-teal-300">
                                                {{ classTableTotals.female }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4"></td>
                                        <td class="py-3.5 px-4 text-sm font-extrabold text-emerald-950 text-right">
                                            {{ classTableTotals.total }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </template>

                <!-- Empty State Tabel -->
                <div v-else class="py-12 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                        <span class="material-symbols-outlined text-2xl">search_off</span>
                    </div>
                    <p class="text-sm font-bold text-slate-700">Belum ada rincian kelas ditemukan</p>
                    <p class="text-xs text-slate-400 mt-1">Pilih tab unit lain atau pastikan data siswa telah dipetakan ke kelas.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.filled-icon {
    font-variation-settings: 'FILL' 1;
}
</style>
