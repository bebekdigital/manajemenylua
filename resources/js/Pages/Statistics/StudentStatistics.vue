<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
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
    availableAcademicYears: {
        type: Array,
        default: () => [],
    },
    allAcademicYears: {
        type: Array,
        default: () => [],
    },
});

// ============================================================
// State Dropdown Filter Tahun Ajaran & Semester
// ============================================================
const selectedYearName = ref(
    props.selectedAcademicYear?.name || (props.availableAcademicYears?.[0] ?? '2026/2027')
);
const selectedSemester = ref(props.selectedAcademicYear?.semester || 'Ganjil');

watch(() => props.selectedAcademicYear, (newVal) => {
    if (newVal) {
        if (newVal.name) selectedYearName.value = newVal.name;
        if (newVal.semester) selectedSemester.value = newVal.semester;
    }
}, { deep: true });

function handleFilterChange() {
    router.get('/statistics', {
        academic_year: selectedYearName.value,
        semester: selectedSemester.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

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
// Helper Filter Unit Sekolah (Semua, SDIT, SMPIT)
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

const unitTabs = computed(() => [
    { id: 'all', label: 'Semua', icon: 'school' },
    { id: 'SDIT', label: 'SDIT', icon: 'school' },
    { id: 'SMPIT', label: 'SMPIT', icon: 'domain' },
]);

const activeUnitLabel = computed(() => {
    if (activeUnit.value === 'all') return 'Semua';
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
// Perhitungan Metrik Utama & Siswa Baru per TA & Semester
// ============================================================
function isNewStudentForYear(student, yearName, semesterFilter, allYearsList = []) {
    if (!student?.tanggal_diterima && !student?.angkatan) return false;
    const tgl = student.tanggal_diterima ? String(student.tanggal_diterima).slice(0, 10) : null;

    if (tgl) {
        // Cek record spesifik pada allAcademicYears
        const matchingRecords = (allYearsList || []).filter(y => y.name === yearName);
        const filteredRecords = semesterFilter && semesterFilter !== 'Semua'
            ? matchingRecords.filter(y => (y.semester || '').toLowerCase() === semesterFilter.toLowerCase())
            : matchingRecords;

        for (const rec of filteredRecords) {
            if (rec.start_date && rec.end_date) {
                const s = String(rec.start_date).slice(0, 10);
                const e = String(rec.end_date).slice(0, 10);
                if (tgl >= s && tgl <= e) return true;
            }
        }

        // Fallback: deteksi dari nama tahun ajaran (misal '2026/2027')
        const match = yearName.match(/(\d{4})\s*[\/-]\s*(\d{4})/);
        if (match) {
            const startYear = match[1];
            const endYear = match[2];
            const sem = (semesterFilter || 'Semua').toLowerCase();

            if (sem === 'ganjil') {
                return tgl >= `${startYear}-01-01` && tgl <= `${startYear}-12-31`;
            } else if (sem === 'genap') {
                return tgl >= `${endYear}-01-01` && tgl <= `${endYear}-06-30`;
            } else {
                return tgl >= `${startYear}-01-01` && tgl <= `${endYear}-06-30`;
            }
        }
    }

    // Fallback: cek berdasarkan angkatan jika tanggal_diterima kosong
    if (student.angkatan) {
        const match = yearName.match(/(\d{4})/);
        if (match && String(student.angkatan).trim() === match[1]) {
            return true;
        }
    }

    return false;
}

const newStudentsCount = computed(() => {
    return filteredStudents.value.filter(s =>
        isNewStudentForYear(s, selectedYearName.value, selectedSemester.value, props.allAcademicYears)
    ).length;
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

// ============================================================
// Grafik Batang Jumlah Siswa Baru (x = TA, y = Jumlah Siswa)
// ============================================================
const newStudentsChartKey = ref(0);
const selectedChartYear = ref(
    props.selectedAcademicYear?.name || (props.availableAcademicYears?.[0] ?? '2026/2027')
);
const showStudentsTab = ref(false);
const studentGenderTab = ref('all'); // 'all' | 'L' | 'P'
const studentSearchQuery = ref('');

watch(selectedYearName, (newVal) => {
    if (newVal) {
        selectedChartYear.value = newVal;
    }
});

watch([activeUnit, selectedYearName, selectedSemester, selectedChartYear, () => props.students], () => {
    newStudentsChartKey.value++;
});

const chartAcademicYears = computed(() => {
    const set = new Set(props.availableAcademicYears || []);

    (props.students || []).forEach(s => {
        if (s.tanggal_diterima) {
            const yr = parseInt(String(s.tanggal_diterima).slice(0, 4), 10);
            const m = parseInt(String(s.tanggal_diterima).slice(5, 7), 10);
            if (yr && yr > 2015) {
                const ta = m >= 7 ? `${yr}/${yr + 1}` : `${yr - 1}/${yr}`;
                set.add(ta);
            }
        }
        if (s.angkatan) {
            const a = parseInt(s.angkatan, 10);
            if (a && a > 2015) {
                set.add(`${a}/${a + 1}`);
            }
        }
    });

    if (set.size === 0) {
        set.add('2024/2025');
        set.add('2025/2026');
        set.add('2026/2027');
    }

    return Array.from(set).sort((a, b) => {
        const yA = parseInt(a.slice(0, 4), 10) || 0;
        const yB = parseInt(b.slice(0, 4), 10) || 0;
        return yA - yB;
    });
});

const newStudentsPerYear = computed(() => {
    return chartAcademicYears.value.map(year => {
        const count = filteredStudents.value.filter(s =>
            isNewStudentForYear(s, year, selectedSemester.value, props.allAcademicYears)
        ).length;
        return {
            year,
            count,
        };
    });
});

const newStudentsChartData = computed(() => {
    const list = newStudentsPerYear.value;
    return {
        labels: list.map(item => item.year),
        datasets: [
            {
                label: 'Jumlah Siswa Baru',
                data: list.map(item => item.count),
                backgroundColor: list.map(item => item.year === selectedChartYear.value ? '#fbbf24' : '#047857'),
                hoverBackgroundColor: list.map(item => item.year === selectedChartYear.value ? '#f59e0b' : '#065f46'),
                borderWidth: 0,
                borderRadius: { topLeft: 6, topRight: 6, bottomLeft: 0, bottomRight: 0 },
                borderSkipped: 'bottom',
                maxBarThickness: 48,
            },
        ],
    };
});

const newStudentsChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    layout: {
        padding: { top: 24 },
    },
    onClick: (event, elements) => {
        if (elements && elements.length > 0) {
            const index = elements[0].index;
            const clickedYear = chartAcademicYears.value[index];
            if (clickedYear) {
                selectedChartYear.value = clickedYear;
                showStudentsTab.value = true;
            }
        }
    },
    plugins: {
        legend: { display: false },
        title: { display: false },
        tooltip: {
            backgroundColor: 'rgba(15, 23, 42, 0.92)',
            titleFont: { size: 13, weight: 'bold', family: "'Plus Jakarta Sans', 'Inter', sans-serif" },
            bodyFont: { size: 12, family: "'Plus Jakarta Sans', 'Inter', sans-serif" },
            padding: 12,
            cornerRadius: 12,
            callbacks: {
                title: (items) => `Tahun Ajaran ${items[0].label}`,
                label: (item) => ` ${item.raw} Siswa Baru`,
            },
        },
        datalabels: {
            anchor: 'end',
            align: 'top',
            color: (ctx) => {
                const year = chartAcademicYears.value[ctx.dataIndex];
                return year === selectedChartYear.value ? '#b45309' : '#047857';
            },
            font: { weight: 'bold', size: 11.5, family: "'Plus Jakarta Sans', 'Inter', sans-serif" },
            offset: 2,
            formatter: (value) => (value > 0 ? value : ''),
        },
    },
    scales: {
        x: {
            title: {
                display: true,
                text: 'Tahun Ajaran (TA)',
                color: '#64748b',
                font: { size: 11.5, weight: '600', family: "'Plus Jakarta Sans', 'Inter', sans-serif" },
                padding: { top: 8 },
            },
            grid: { display: false },
            ticks: {
                font: { size: 11.5, weight: '700', family: "'Plus Jakarta Sans', 'Inter', sans-serif" },
                color: '#334155',
            },
            border: { display: false },
        },
        y: {
            title: {
                display: true,
                text: 'Jumlah Siswa',
                color: '#64748b',
                font: { size: 11.5, weight: '600', family: "'Plus Jakarta Sans', 'Inter', sans-serif" },
                padding: { bottom: 8 },
            },
            beginAtZero: true,
            grid: {
                color: 'rgba(226, 232, 240, 0.85)',
                drawBorder: false,
            },
            ticks: {
                font: { size: 11, family: "'Plus Jakarta Sans', 'Inter', sans-serif" },
                color: '#94a3b8',
                stepSize: 5,
                precision: 0,
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

// Siswa baru yang masuk dalam Tahun Ajaran yang sedang dipilih pada grafik
const studentsInSelectedChartYear = computed(() => {
    if (!selectedChartYear.value) return [];
    return filteredStudents.value.filter(s =>
        isNewStudentForYear(s, selectedChartYear.value, selectedSemester.value, props.allAcademicYears)
    );
});

const chartYearCounts = computed(() => {
    const list = studentsInSelectedChartYear.value;
    let male = 0;
    let female = 0;
    list.forEach(s => {
        const jk = String(s.jk || '').toUpperCase();
        if (jk === 'L') male++;
        else if (jk === 'P') female++;
    });
    return { total: list.length, male, female };
});

const displayedNewStudents = computed(() => {
    let list = studentsInSelectedChartYear.value;
    if (studentGenderTab.value === 'L') {
        list = list.filter(s => String(s.jk || '').toUpperCase() === 'L');
    } else if (studentGenderTab.value === 'P') {
        list = list.filter(s => String(s.jk || '').toUpperCase() === 'P');
    }

    if (studentSearchQuery.value.trim()) {
        const q = studentSearchQuery.value.toLowerCase().trim();
        list = list.filter(s =>
            (s.nama && s.nama.toLowerCase().includes(q)) ||
            (s.nipd && String(s.nipd).toLowerCase().includes(q)) ||
            (s.nisn && String(s.nisn).toLowerCase().includes(q)) ||
            (s.kelas && s.kelas.toLowerCase().includes(q)) ||
            (s.unit && s.unit.toLowerCase().includes(q))
        );
    }
    return list;
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
                    backgroundColor: '#fbbf24', // yellow-400 (sesuai brand tema)
                    hoverBackgroundColor: '#f59e0b', // amber-500
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
                    return ctx.datasetIndex === 0 ? '#065f46' : '#b45309';
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
            <!-- HEADER MOBILE (md:hidden)                                    -->
            <!-- ============================================================ -->
            <div class="md:hidden flex items-center justify-between gap-3 pt-3 sm:pt-4 mb-5">
                <div class="min-w-0">
                    <h1 ref="mainTitleRef" class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Statistik Siswa
                    </h1>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- HEADER DESKTOP (hidden md:flex)                              -->
            <!-- ============================================================ -->
            <div class="hidden md:flex mb-5 sm:mb-6 flex-row items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-xl sm:text-2xl md:text-[26px] font-bold text-slate-900 tracking-tight leading-tight">
                        Statistik Siswa
                    </h1>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- SEGMENTED CAPSULE TABS                                       -->
            <!-- ============================================================ -->
            <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="bg-slate-100 p-1 rounded-full inline-flex items-center gap-0.5 overflow-x-auto no-scrollbar max-w-full border border-slate-200/90 shrink-0">
                    <button
                        v-for="tab in unitTabs"
                        :key="tab.id"
                        type="button"
                        @click="activeUnit = tab.id"
                        :class="[
                            'inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs sm:text-[13px] transition-all duration-150 cursor-pointer shrink-0',
                            activeUnit === tab.id
                                ? 'bg-emerald-100 text-emerald-950 font-bold border border-emerald-300 shadow-2xs'
                                : 'text-slate-600 hover:text-slate-900 font-medium border border-transparent'
                        ]"
                    >
                        <span class="material-symbols-outlined text-[16px]" :class="{ 'filled-icon': activeUnit === tab.id }">{{ tab.icon }}</span>
                        <span>{{ tab.label }}</span>
                    </button>
                </div>

                <!-- Dropdown Filter Tahun Ajaran & Semester (Terpisah) -->
                <div class="flex items-center gap-2 flex-wrap self-start sm:self-auto shrink-0">
                    <!-- Dropdown Tahun Ajaran -->
                    <div class="relative inline-flex items-center">
                        <span class="absolute left-3 text-slate-400 material-symbols-outlined text-[17px] pointer-events-none">calendar_month</span>
                        <select
                            v-model="selectedYearName"
                            @change="handleFilterChange"
                            class="appearance-none bg-white border border-slate-200/90 hover:border-emerald-300 text-slate-800 text-xs font-bold rounded-full pl-8 pr-7 py-1.5 shadow-2xs transition-all focus:outline-none focus:ring-2 focus:ring-emerald-500/20 cursor-pointer"
                        >
                            <option v-for="yr in (availableAcademicYears && availableAcademicYears.length ? availableAcademicYears : [selectedYearName])" :key="yr" :value="yr">
                                TA {{ yr }}
                            </option>
                        </select>
                        <span class="absolute right-2 text-slate-400 material-symbols-outlined text-[16px] pointer-events-none">expand_more</span>
                    </div>

                    <!-- Dropdown Semester -->
                    <div class="relative inline-flex items-center">
                        <span class="absolute left-3 text-slate-400 material-symbols-outlined text-[17px] pointer-events-none">schedule</span>
                        <select
                            v-model="selectedSemester"
                            @change="handleFilterChange"
                            class="appearance-none bg-white border border-slate-200/90 hover:border-emerald-300 text-slate-800 text-xs font-bold rounded-full pl-8 pr-7 py-1.5 shadow-2xs transition-all focus:outline-none focus:ring-2 focus:ring-emerald-500/20 cursor-pointer"
                        >
                            <option value="Semua">Semua Semester</option>
                            <option value="Ganjil">Semester Ganjil</option>
                            <option value="Genap">Semester Genap</option>
                        </select>
                        <span class="absolute right-2 text-slate-400 material-symbols-outlined text-[16px] pointer-events-none">expand_more</span>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- 2 CARD STATISTIK UTAMA: SISWA AKTIF & SISWA BARU             -->
            <!-- ============================================================ -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 mb-5">
                <!-- Card 1: Siswa Aktif -->
                <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-yellow-200 border border-yellow-300/80 flex items-center justify-center text-emerald-950 shadow-2xs shrink-0">
                        <span class="material-symbols-outlined text-[24px] filled-icon">groups</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Siswa Aktif</p>
                        <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-0.5 leading-tight">{{ metrics.active }}</p>
                    </div>
                </div>

                <!-- Card 2: Siswa Baru -->
                <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-yellow-200 border border-yellow-300/80 flex items-center justify-center text-emerald-950 shadow-2xs shrink-0">
                        <span class="material-symbols-outlined text-[24px]">person_add</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Siswa Baru</p>
                        <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-0.5 leading-tight">{{ newStudentsCount }}</p>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- GRAFIK BATANG JUMLAH SISWA BARU (x = TA, y = Jumlah Siswa)   -->
            <!-- ============================================================ -->
            <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-6 shadow-xs mb-5">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 flex-wrap gap-2">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                            Jumlah Siswa Baru per Tahun Ajaran ({{ activeUnitLabel }})
                        </h3>
                    </div>
                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-900 border border-emerald-200 text-[11px] font-bold">
                            <span class="w-2 h-2 rounded-full bg-yellow-400 inline-block"></span>
                            <span>TA: {{ selectedChartYear }}</span>
                        </span>
                        <button
                            v-if="chartAcademicYears.length > 0"
                            type="button"
                            @click="showStudentsTab = !showStudentsTab"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold transition-all cursor-pointer border"
                            :class="showStudentsTab ? 'bg-emerald-900 text-yellow-200 border-emerald-950 shadow-2xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200'"
                        >
                            <span class="material-symbols-outlined text-[15px]">{{ showStudentsTab ? 'expand_less' : 'group' }}</span>
                            <span>{{ showStudentsTab ? 'Tutup Daftar' : `Nama Siswa (${chartYearCounts.total})` }}</span>
                        </button>
                    </div>
                </div>

                <div v-if="chartAcademicYears.length > 0" class="h-[240px] sm:h-[280px]">
                    <Bar :key="newStudentsChartKey" :data="newStudentsChartData" :options="newStudentsChartOptions" />
                </div>
                <div v-else class="py-10 text-center text-slate-400 text-xs">
                    Belum ada data periode siswa baru.
                </div>

                <!-- Petunjuk interaktif klik batang -->
                <div v-if="chartAcademicYears.length > 0 && !showStudentsTab" class="mt-3 text-center">
                    <button
                        type="button"
                        @click="showStudentsTab = true"
                        class="text-[11.5px] text-slate-500 hover:text-emerald-800 font-medium inline-flex items-center gap-1.5 cursor-pointer transition-colors"
                    >
                        <span class="material-symbols-outlined text-[15px] text-emerald-700">touch_app</span>
                        <span>Klik salah satu batang grafik untuk menampilkan nama-nama siswa baru TA tersebut</span>
                    </button>
                </div>

                <!-- ============================================================ -->
                <!-- TAB RINCIAN NAMA SISWA BARU (MASIH 1 CONTAINER)              -->
                <!-- ============================================================ -->
                <div v-if="showStudentsTab" class="mt-6 pt-5 border-t border-slate-100">
                    <!-- Tab Header: Judul & Sub-tabs Gender -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-yellow-400 inline-block"></span>
                                <h4 class="text-xs sm:text-sm font-bold text-slate-900">
                                    Daftar Nama Siswa Baru — TA {{ selectedChartYear }}
                                </h4>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-950 border border-emerald-300">
                                    {{ chartYearCounts.total }} Siswa
                                </span>
                            </div>
                            <p class="text-[11.5px] text-slate-500 mt-0.5">
                                Unit: {{ activeUnitLabel }} • Periode: {{ selectedSemester === 'Semua' ? 'Semua Semester' : `Semester ${selectedSemester}` }}
                            </p>
                        </div>

                        <!-- Sub-tabs Gender: Semua, Laki-laki, Perempuan -->
                        <div class="bg-slate-100 p-1 rounded-full inline-flex items-center gap-0.5 self-start sm:self-auto shrink-0 border border-slate-200/90">
                            <button
                                type="button"
                                @click="studentGenderTab = 'all'"
                                :class="[
                                    'px-3 py-1 rounded-full text-xs font-bold transition-all cursor-pointer',
                                    studentGenderTab === 'all'
                                        ? 'bg-emerald-900 text-yellow-200 shadow-2xs'
                                        : 'text-slate-600 hover:text-slate-900'
                                ]"
                            >
                                Semua ({{ chartYearCounts.total }})
                            </button>
                            <button
                                type="button"
                                @click="studentGenderTab = 'L'"
                                :class="[
                                    'inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold transition-all cursor-pointer',
                                    studentGenderTab === 'L'
                                        ? 'bg-emerald-700 text-white shadow-2xs'
                                        : 'text-slate-600 hover:text-slate-900'
                                ]"
                            >
                                <span class="px-1 py-0.2 rounded bg-emerald-100 text-emerald-900 text-[9px] font-extrabold">L</span>
                                <span>Putra ({{ chartYearCounts.male }})</span>
                            </button>
                            <button
                                type="button"
                                @click="studentGenderTab = 'P'"
                                :class="[
                                    'inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold transition-all cursor-pointer',
                                    studentGenderTab === 'P'
                                        ? 'bg-yellow-400 text-emerald-950 shadow-2xs'
                                        : 'text-slate-600 hover:text-slate-900'
                                ]"
                            >
                                <span class="px-1 py-0.2 rounded bg-yellow-100 text-amber-900 text-[9px] font-extrabold">P</span>
                                <span>Putri ({{ chartYearCounts.female }})</span>
                            </button>
                        </div>
                    </div>

                    <!-- Search Input jika data lebih dari 5 -->
                    <div v-if="chartYearCounts.total > 5" class="mb-3 max-w-sm">
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 material-symbols-outlined text-[17px]">search</span>
                            <input
                                v-model="studentSearchQuery"
                                type="text"
                                placeholder="Cari nama siswa atau kelas..."
                                class="w-full bg-slate-50 border border-slate-200/90 rounded-xl pl-9 pr-3 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all"
                            />
                        </div>
                    </div>

                    <!-- Tampilan Daftar Siswa -->
                    <div v-if="displayedNewStudents.length > 0">
                        <!-- Mobile Cards -->
                        <div class="md:hidden space-y-2 max-h-[340px] overflow-y-auto pr-0.5">
                            <div
                                v-for="(st, idx) in displayedNewStudents"
                                :key="st.id || idx"
                                class="p-3 rounded-xl bg-slate-50/80 border border-slate-200/80 flex items-center justify-between gap-3"
                            >
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span
                                        class="w-7 h-7 rounded-lg flex items-center justify-center font-bold text-xs shrink-0"
                                        :class="st.jk === 'L' ? 'bg-emerald-100 text-emerald-900 border border-emerald-300' : 'bg-yellow-100 text-amber-900 border border-yellow-300'"
                                    >
                                        {{ st.jk || '-' }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-900 truncate">{{ st.nama || 'Siswa Tanpa Nama' }}</p>
                                        <p class="text-[11px] text-slate-500 truncate">
                                            {{ st.unit }} • Kelas: {{ st.kelas || '-' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-[10px] font-semibold text-slate-400 block">{{ st.tanggal_diterima || '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Table -->
                        <div class="hidden md:block overflow-hidden rounded-xl border border-slate-200/90 shadow-2xs max-h-[380px] overflow-y-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="sticky top-0 bg-slate-100 text-slate-700 border-b border-slate-200 uppercase text-[10.5px] font-bold tracking-wider">
                                    <tr>
                                        <th class="py-2.5 px-3.5 w-12 text-center">No</th>
                                        <th class="py-2.5 px-3.5">Nama Siswa</th>
                                        <th class="py-2.5 px-3.5 text-center w-24">Gender</th>
                                        <th class="py-2.5 px-3.5">Unit Sekolah</th>
                                        <th class="py-2.5 px-3.5">Kelas</th>
                                        <th class="py-2.5 px-3.5 text-right">Tanggal Diterima</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <tr
                                        v-for="(st, idx) in displayedNewStudents"
                                        :key="st.id || idx"
                                        class="hover:bg-slate-50/80 transition-colors"
                                    >
                                        <td class="py-2.5 px-3.5 text-center text-slate-400 font-medium">{{ idx + 1 }}</td>
                                        <td class="py-2.5 px-3.5 font-bold text-slate-900">{{ st.nama || '-' }}</td>
                                        <td class="py-2.5 px-3.5 text-center">
                                            <span
                                                class="inline-flex items-center justify-center px-2 py-0.5 rounded text-[10.5px] font-bold"
                                                :class="st.jk === 'L' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-yellow-50 text-amber-900 border border-yellow-300'"
                                            >
                                                {{ st.jk === 'L' ? 'L (Putra)' : (st.jk === 'P' ? 'P (Putri)' : '-') }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3.5 text-slate-600 font-medium">{{ st.unit }}</td>
                                        <td class="py-2.5 px-3.5 text-slate-600 font-medium">{{ st.kelas || '-' }}</td>
                                        <td class="py-2.5 px-3.5 text-right text-slate-500 font-medium">{{ st.tanggal_diterima || '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Empty State saat tidak ada siswa -->
                    <div v-else class="py-8 text-center text-slate-400 text-xs">
                        <span class="material-symbols-outlined text-2xl mb-1 text-slate-300 block">person_off</span>
                        <span>Tidak ada siswa baru yang sesuai kriteria pada TA {{ selectedChartYear }}.</span>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- GENDER SISWA (BAR LAKI-LAKI & PEREMPUAN)                     -->
            <!-- ============================================================ -->
            <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-6 shadow-xs mb-5">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                            Gender Siswa
                        </h3>
                    </div>
                    <span class="text-xs text-slate-500 font-semibold">
                        {{ genderTotal }} Siswa
                    </span>
                </div>

                <!-- Box Sesuai Gambar User (Persis Screenshot) -->
                <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs">
                    <!-- Baris Atas: [L] Laki-laki (XX%) ... Perempuan (YY%) [P] -->
                    <div class="flex items-center justify-between text-xs sm:text-[13.5px] font-bold mb-2.5 sm:mb-3">
                        <div class="flex items-center gap-2 text-emerald-950">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-300 shadow-2xs">L</span>
                            <span>Laki-laki ({{ malePercentage }}%)</span>
                        </div>
                        <div class="flex items-center gap-2 text-emerald-950">
                            <span>Perempuan ({{ femalePercentage }}%)</span>
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-300 shadow-2xs">P</span>
                        </div>
                    </div>

                    <!-- Dual Bar: Emerald Tua (#047857) untuk Putra & Mint Green (#6ee7b7) untuk Putri -->
                    <div class="w-full h-3.5 sm:h-4 rounded-full bg-slate-100 overflow-hidden flex">
                        <div
                            v-if="genderTotal > 0"
                            class="bg-[#047857] h-full transition-all duration-500"
                            :style="{ width: `${malePercentage}%` }"
                            :title="`Laki-laki: ${metrics.male} siswa (${malePercentage}%)`"
                        ></div>
                        <div
                            v-if="genderTotal > 0"
                            class="bg-[#6ee7b7] h-full transition-all duration-500"
                            :style="{ width: `${femalePercentage}%` }"
                            :title="`Perempuan: ${metrics.female} siswa (${femalePercentage}%)`"
                        ></div>
                    </div>

                    <!-- Baris Bawah: Total Putra & Putri (Tanpa Persen di Bawah Sesuai Gambar) -->
                    <div class="flex items-center justify-between text-xs sm:text-[12.5px] text-slate-500 font-medium mt-2.5 sm:mt-3">
                        <span>{{ metrics.male }} siswa putra</span>
                        <span>{{ metrics.female }} siswa putri</span>
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

                            <!-- Bar & Badge Laki-laki / Perempuan per Kelas (Senada Emerald & Kuning) -->
                            <div class="pt-2 border-t border-slate-100 space-y-1.5">
                                <div class="flex items-center justify-between text-[11px] font-bold">
                                    <span class="inline-flex items-center gap-1 text-emerald-800">
                                        <span class="px-1.5 py-0.5 rounded bg-emerald-50 border border-emerald-200 text-[9.5px]">L</span>
                                        <span>{{ item.male || 0 }} Putra</span>
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-amber-900">
                                        <span>{{ item.female || 0 }} Putri</span>
                                        <span class="px-1.5 py-0.5 rounded bg-yellow-50 border border-yellow-200 text-[9.5px]">P</span>
                                    </span>
                                </div>
                                <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden flex">
                                    <div
                                        class="bg-emerald-700 h-full"
                                        :style="{ width: `${getClassMalePercent(item)}%` }"
                                    ></div>
                                    <div
                                        class="bg-yellow-400 h-full"
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
                                            <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-yellow-50 text-amber-900 border border-yellow-200">
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
                                                        class="bg-yellow-400 h-full"
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
                                            <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-md text-xs font-extrabold bg-yellow-100 text-amber-950 border border-yellow-300">
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
