<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StudentDomicileMap from './Partials/StudentDomicileMap.vue';
import StudentSchoolOriginChart from './Partials/StudentSchoolOriginChart.vue';
import {
    BADGE_COLORS,
    getGenderBadgeClass,
    getGenderShortLabel,
    getProgramBadgeClass,
    getRegistrationTypeBadgeClass,
} from '@/Utils/studentBadges';
import { computeEnrollmentForecast } from '@/Utils/enrollmentForecast';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    LineController,
    LineElement,
    PointElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    LineController,
    LineElement,
    PointElement,
    Title,
    Tooltip,
    Legend,
    ChartDataLabels
);

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
    currentAcademicYear: {
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
    allStudents: {
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
const selectedSemester = ref(props.selectedAcademicYear?.semester || 'Semua');
const isAllTime = computed(() => selectedYearName.value === 'Semua');

watch(() => props.selectedAcademicYear, (newVal) => {
    if (newVal) {
        if (newVal.name) selectedYearName.value = newVal.name;
        if (newVal.semester) selectedSemester.value = newVal.semester;
    }
}, { deep: true });

function handleFilterChange() {
    if (selectedYearName.value === 'Semua') {
        selectedSemester.value = 'Semua';
    }
    router.get('/statistics', {
        academic_year: selectedYearName.value,
        semester: selectedSemester.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

const isDifferentFromCurrent = computed(() => {
    const currentYear = props.currentAcademicYear?.name || (props.availableAcademicYears?.[0] ?? '2026/2027');
    return selectedYearName.value !== currentYear || selectedSemester.value !== 'Semua';
});

function resetToCurrentAcademicYear() {
    const currentYear = props.currentAcademicYear?.name || (props.availableAcademicYears?.[0] ?? '2026/2027');
    selectedYearName.value = currentYear;
    selectedSemester.value = 'Semua';
    selectedChartYear.value = currentYear;
    studentTypeTab.value = 'all';
    studentGenderTab.value = 'all';
    showStudentsTab.value = false;
    handleFilterChange();
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

function matchesUnit(studentOrUnit, targetUnit) {
    if (targetUnit === 'all') return true;
    if (!studentOrUnit) return false;
    const u = String(typeof studentOrUnit === 'object' ? studentOrUnit.unit : studentOrUnit || '').toUpperCase().trim();
    const up = String(typeof studentOrUnit === 'object' ? studentOrUnit.unit_pesantren : '').toUpperCase().trim();
    const p = String(typeof studentOrUnit === 'object' ? studentOrUnit.program : '').toUpperCase().trim();
    if (targetUnit === 'SDIT') {
        return u.includes('SDIT') || u.includes('SD IT') || u === 'SD';
    }
    if (targetUnit === 'SMPIT') {
        return u.includes('SMPIT') || u.includes('SMP IT') || u === 'SMP';
    }
    if (targetUnit === 'SMAIT') {
        return u.includes('SMAIT') || u.includes('SMA IT') || u === 'SMA';
    }
    if (targetUnit === 'PPTQ') {
        return u.includes('PPTQ') || up.includes('PPTQ') || p.includes('BOARDING');
    }
    return u === targetUnit.toUpperCase();
}

const unitTabs = computed(() => [
    { id: 'all', label: 'Semua', icon: 'school' },
    { id: 'SDIT', label: 'SDIT', icon: 'school' },
    { id: 'SMPIT', label: 'SMPIT', icon: 'domain' },
    { id: 'SMAIT', label: 'SMAIT', icon: 'account_balance' },
    { id: 'PPTQ', label: 'PPTQ', icon: 'mosque' },
]);

const activeUnitLabel = computed(() => {
    if (activeUnit.value === 'all') return 'Semua';
    return unitTabs.value.find(t => t.id === activeUnit.value)?.label ?? activeUnit.value;
});

// ============================================================
// Data Siswa Terfilter Berdasarkan Tab Unit
// ============================================================
const filteredStudents = computed(() => {
    if (!props.students || props.students.length === 0) return [];
    return props.students.filter(s => matchesUnit(s, activeUnit.value));
});

// ============================================================
// Perhitungan Metrik Utama & Siswa Baru per TA & Semester
// Mengacu ke kolom Periode Tanggal dari TA dan Semester di Tab Akademik
// ============================================================
function formatIndoDate(dateStr) {
    if (!dateStr) return '';
    try {
        const parts = String(dateStr).slice(0, 10).split('-');
        if (parts.length !== 3) return dateStr;
        const [y, m, d] = parts;
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const monthName = months[parseInt(m, 10) - 1] || m;
        return `${parseInt(d, 10)} ${monthName} ${y}`;
    } catch {
        return dateStr;
    }
}

function getAcademicYearPeriod(yearName, semesterFilter, allYearsList = []) {
    const matchingRecords = (allYearsList || []).filter(y => y.name === yearName);
    const filteredRecords = semesterFilter && semesterFilter !== 'Semua'
        ? matchingRecords.filter(y => (y.semester || '').toLowerCase() === semesterFilter.toLowerCase())
        : matchingRecords;

    let startDate = null;
    let endDate = null;

    filteredRecords.forEach(rec => {
        if (rec.start_date) {
            const s = String(rec.start_date).slice(0, 10);
            if (!startDate || s < startDate) startDate = s;
        }
        if (rec.end_date) {
            const e = String(rec.end_date).slice(0, 10);
            if (!endDate || e > endDate) endDate = e;
        }
    });

    // Fallback jika periode tanggal belum diisi pada database
    if (!startDate || !endDate) {
        const match = yearName.match(/(\d{4})\s*[\/-]\s*(\d{4})/);
        if (match) {
            const startYear = match[1];
            const endYear = match[2];
            const sem = (semesterFilter || 'Semua').toLowerCase();
            if (sem === 'ganjil') {
                startDate = startDate || `${startYear}-07-01`;
                endDate = endDate || `${startYear}-12-31`;
            } else if (sem === 'genap') {
                startDate = startDate || `${endYear}-01-01`;
                endDate = endDate || `${endYear}-06-30`;
            } else {
                startDate = startDate || `${startYear}-07-01`;
                endDate = endDate || `${endYear}-06-30`;
            }
        }
    }

    return { startDate, endDate };
}

const currentPeriod = computed(() => {
    return getAcademicYearPeriod(selectedYearName.value, selectedSemester.value, props.allAcademicYears);
});

const selectedPeriodLabel = computed(() => {
    if (currentPeriod.value.startDate && currentPeriod.value.endDate) {
        return `${formatIndoDate(currentPeriod.value.startDate)} — ${formatIndoDate(currentPeriod.value.endDate)}`;
    }
    return '';
});

function isNewStudentForYear(student, yearName, semesterFilter, allYearsList = []) {
    if (!student?.tanggal_diterima && !student?.angkatan) return false;
    const tgl = student.tanggal_diterima ? String(student.tanggal_diterima).slice(0, 10) : null;

    const { startDate, endDate } = getAcademicYearPeriod(yearName, semesterFilter, allYearsList);

    if (tgl && startDate && endDate) {
        if (tgl >= startDate && tgl <= endDate) {
            return true;
        }
    }

    // Fallback: cek berdasarkan angkatan jika tanggal_diterima kosong
    if (!tgl && student.angkatan) {
        const match = yearName.match(/(\d{4})/);
        if (match && String(student.angkatan).trim() === match[1]) {
            return true;
        }
    }

    return false;
}

function isMutasiMasuk(student) {
    const reg = String(student?.status_registrasi || '').toLowerCase().trim();
    return reg.includes('mutasi');
}

const newStudentsCount = computed(() => {
    const currentYear = selectedYearName.value;
    const currentSem = selectedSemester.value;

    let sourceList;
    if (isAllTime.value) {
        const fromFiltered = filteredStudents.value;
        const fromChart = chartStudentsSource.value.filter(s => matchesUnit(s, activeUnit.value));
        sourceList = fromChart.length >= fromFiltered.length ? fromChart : fromFiltered;
    } else {
        const fromFiltered = filteredStudents.value.filter(s =>
            isNewStudentForYear(s, currentYear, currentSem, props.allAcademicYears)
        );

        const fromChart = chartStudentsSource.value
            .filter(s => matchesUnit(s, activeUnit.value))
            .filter(s => isNewStudentForYear(s, currentYear, currentSem, props.allAcademicYears));

        sourceList = fromChart.length >= fromFiltered.length ? fromChart : fromFiltered;
    }

    let siswaBaru = 0;
    let mutasiMasuk = 0;

    sourceList.forEach(s => {
        if (isMutasiMasuk(s)) {
            mutasiMasuk++;
        } else {
            siswaBaru++;
        }
    });

    return {
        total: sourceList.length,
        siswaBaru,
        mutasiMasuk,
    };
});

// ============================================================
// Perhitungan Metrik Utama (Total, Aktif, Lulus, Tidak Aktif, L, P)
// ============================================================
const metrics = computed(() => {
    if (props.students && props.students.length > 0) {
        const list = filteredStudents.value;
        const total = list.length;
        let active = 0;
        let lulus = 0;
        let tidakAktif = 0;
        let inactive = 0;
        let male = 0;
        let female = 0;

        list.forEach(s => {
            const rawStatus = (s.status_keaktifan || s.student_status || 'aktif').toLowerCase().trim();
            const isLulus = rawStatus === 'lulus';
            const isTidakAktif = !isLulus && (rawStatus.includes('tidak') || rawStatus === 'dropout' || rawStatus === 'mutasi_keluar');
            const isActive = !isLulus && !isTidakAktif;
            const jk = String(s.jk || '').toUpperCase().trim();

            if (isActive) {
                active++;
            } else if (isLulus) {
                lulus++;
                inactive++;
            } else {
                tidakAktif++;
                inactive++;
            }

            if (isAllTime.value || isActive) {
                if (jk === 'L') male++;
                else if (jk === 'P') female++;
            }
        });

        return {
            total,
            active,
            lulus,
            tidakAktif,
            inactive,
            male,
            female,
        };
    }

    // Fallback jika hanya studentsPerClass yang tersedia
    const classList = props.studentsPerClass.filter(item => matchesUnit(item.unit, activeUnit.value));
    const active = classList.reduce((sum, item) => sum + (Number(item.total) || 0), 0);
    const male = classList.reduce((sum, item) => sum + (Number(item.male) || 0), 0);
    const female = classList.reduce((sum, item) => sum + (Number(item.female) || 0), 0);

    return {
        total: active,
        active,
        lulus: 0,
        tidakAktif: 0,
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

const programMetrics = computed(() => {
    const list = filteredStudents.value;
    let umum = 0;
    let boarding = 0;
    let fullday = 0;

    list.forEach(s => {
        const rawStatus = (s.status_keaktifan || s.student_status || 'aktif').toLowerCase().trim();
        const isLulus = rawStatus === 'lulus';
        const isTidakAktif = !isLulus && (rawStatus.includes('tidak') || rawStatus === 'dropout' || rawStatus === 'mutasi_keluar');
        const isActive = !isLulus && !isTidakAktif;

        if (isAllTime.value || isActive) {
            const p = String(s.program || '').trim().toLowerCase();
            if (p === 'boarding' || p.includes('boarding') || p.includes('pondok') || p.includes('pesantren') || p.includes('asrama')) {
                boarding++;
            } else if (p === 'fullday' || p.includes('fullday')) {
                fullday++;
            } else {
                // Default ke 'Umum' sesuai tampilan Tab Identitas
                umum++;
            }
        }
    });

    const total = umum + boarding + fullday;
    let umumPercentage = 0;
    let boardingPercentage = 0;
    let fulldayPercentage = 0;

    if (total > 0) {
        umumPercentage = Math.round((umum / total) * 100);
        boardingPercentage = Math.round((boarding / total) * 100);
        if (fullday > 0) {
            fulldayPercentage = 100 - (umumPercentage + boardingPercentage);
        } else {
            boardingPercentage = 100 - umumPercentage;
        }
    }

    return {
        total,
        umum,
        boarding,
        fullday,
        umumPercentage,
        boardingPercentage,
        fulldayPercentage,
    };
});

// ============================================================
// Grafik Batang Bertumpuk Siswa Baru & Mutasi Masuk (x = TA, y = Jumlah Siswa)
// ============================================================
const newStudentsChartKey = ref(0);
const selectedChartYear = ref(
    props.selectedAcademicYear?.name && props.selectedAcademicYear.name !== 'Semua'
        ? props.selectedAcademicYear.name
        : (props.currentAcademicYear?.name || (props.availableAcademicYears?.[0] ?? '2026/2027'))
);
const showStudentsTab = ref(false);
const showMutasiInChart = ref(true);
const showPrediction = ref(true);
const showTrendLine = ref(true);
const showForecastHint = ref(false);
const studentGenderTab = ref('all'); // 'all' | 'L' | 'P'
const studentTypeTab = ref('all'); // 'all' | 'baru' | 'mutasi'
const studentSearchQuery = ref('');

watch(selectedYearName, (newVal) => {
    if (newVal && newVal !== 'Semua') {
        selectedChartYear.value = newVal;
    } else if (newVal === 'Semua') {
        selectedChartYear.value = props.currentAcademicYear?.name || (props.availableAcademicYears?.[0] ?? '2026/2027');
    }
});

watch(
    [
        activeUnit,
        selectedYearName,
        selectedSemester,
        selectedChartYear,
        showMutasiInChart,
        showPrediction,
        showTrendLine,
        () => props.students,
    ],
    () => {
        newStudentsChartKey.value++;
    }
);

const chartStudentsSource = computed(() => {
    return (props.allStudents && props.allStudents.length > 0) ? props.allStudents : (props.students || []);
});

const chartAcademicYears = computed(() => {
    const set = new Set(props.availableAcademicYears || []);

    chartStudentsSource.value.forEach(s => {
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
    const list = chartStudentsSource.value.filter(s => matchesUnit(s, activeUnit.value));
    return chartAcademicYears.value.map(year => {
        const matching = list.filter(s =>
            isNewStudentForYear(s, year, selectedSemester.value, props.allAcademicYears)
        );

        let siswaBaru = 0;
        let mutasiMasuk = 0;

        matching.forEach(s => {
            if (isMutasiMasuk(s)) {
                mutasiMasuk++;
            } else {
                siswaBaru++;
            }
        });

        return {
            year,
            total: matching.length,
            siswaBaru,
            mutasiMasuk,
        };
    });
});

const enrollmentForecast = computed(() => {
    const activeYear = props.currentAcademicYear?.name || null;
    return computeEnrollmentForecast(newStudentsPerYear.value, activeYear);
});

const showForecastBar = computed(() => showPrediction.value && enrollmentForecast.value.isAvailable);
const showForecastLine = computed(() => showTrendLine.value && enrollmentForecast.value.isAvailable);

const newStudentsChartData = computed(() => {
    const list = newStudentsPerYear.value;
    const forecast = enrollmentForecast.value;
    const isHighlighted = (yr) => !isAllTime.value && yr === selectedChartYear.value;

    const labels = list.map((item) => item.year);
    const siswaBaruData = list.map((item) => item.siswaBaru);
    const mutasiData = list.map((item) => item.mutasiMasuk);

    const siswaBaruBg = list.map((item) => (isHighlighted(item.year) ? '#047857' : '#10b981'));
    const siswaBaruHover = list.map((item) => (isHighlighted(item.year) ? '#065f46' : '#059669'));
    const siswaBaruBorderWidth = list.map((item) => (isHighlighted(item.year) ? 2 : 0));
    const siswaBaruBorderColor = list.map(() => '#064e3b');
    const siswaBaruRadius = list.map((item) =>
        showMutasiInChart.value && item.mutasiMasuk > 0
            ? { topLeft: 0, topRight: 0, bottomLeft: 6, bottomRight: 6 }
            : { topLeft: 6, topRight: 6, bottomLeft: 6, bottomRight: 6 }
    );

    const mutasiBg = list.map((item) => (isHighlighted(item.year) ? '#d97706' : '#f59e0b'));
    const mutasiHover = list.map((item) => (isHighlighted(item.year) ? '#b45309' : '#d97706'));
    const mutasiBorderWidth = list.map((item) => (isHighlighted(item.year) ? 2 : 0));
    const mutasiBorderColor = list.map(() => '#92400e');

    if (showForecastBar.value) {
        labels.push(`${forecast.nextYearLabel} (Est.)`);
        siswaBaruData.push(forecast.siswaBaruEst);
        mutasiData.push(forecast.mutasiEst);

        siswaBaruBg.push(forecast.status === 'warning' ? 'rgba(225, 29, 72, 0.20)' : 'rgba(16, 185, 129, 0.28)');
        siswaBaruHover.push(forecast.status === 'warning' ? 'rgba(225, 29, 72, 0.30)' : 'rgba(16, 185, 129, 0.38)');
        siswaBaruBorderWidth.push(2);
        siswaBaruBorderColor.push(forecast.status === 'warning' ? '#be123c' : '#059669');
        siswaBaruRadius.push(
            showMutasiInChart.value && forecast.mutasiEst > 0
                ? { topLeft: 0, topRight: 0, bottomLeft: 6, bottomRight: 6 }
                : { topLeft: 6, topRight: 6, bottomLeft: 6, bottomRight: 6 }
        );

        mutasiBg.push('rgba(245, 158, 11, 0.25)');
        mutasiHover.push('rgba(245, 158, 11, 0.36)');
        mutasiBorderWidth.push(2);
        mutasiBorderColor.push('#d97706');
    }

    const datasets = [
        {
            type: 'bar',
            label: 'Siswa Baru',
            data: siswaBaruData,
            backgroundColor: siswaBaruBg,
            hoverBackgroundColor: siswaBaruHover,
            borderWidth: siswaBaruBorderWidth,
            borderColor: siswaBaruBorderColor,
            borderRadius: siswaBaruRadius,
            borderSkipped: false,
            maxBarThickness: 48,
            stack: 'masuk',
            order: 2,
        },
    ];

    if (showMutasiInChart.value) {
        datasets.push({
            type: 'bar',
            label: 'Mutasi Masuk',
            data: mutasiData,
            backgroundColor: mutasiBg,
            hoverBackgroundColor: mutasiHover,
            borderWidth: mutasiBorderWidth,
            borderColor: mutasiBorderColor,
            borderRadius: { topLeft: 6, topRight: 6, bottomLeft: 0, bottomRight: 0 },
            borderSkipped: false,
            maxBarThickness: 48,
            stack: 'masuk',
            order: 2,
        });
    }

    if (showForecastLine.value) {
        const trendData = list.map((item) =>
            showMutasiInChart.value
                ? (forecast.trendByYearWithMutasi[item.year] ?? null)
                : (forecast.trendByYearSiswaBaru[item.year] ?? null)
        );
        if (showForecastBar.value) {
            trendData.push(showMutasiInChart.value ? forecast.totalEst : forecast.siswaBaruEst);
        }

        const lineColor =
            forecast.status === 'warning' ? '#e11d48' : forecast.status === 'growing' ? '#047857' : '#0284c7';

        datasets.push({
            type: 'line',
            label: 'Garis Tren (3 TA Terakhir)',
            data: trendData,
            borderColor: lineColor,
            backgroundColor: lineColor,
            borderWidth: 2.5,
            borderDash: [6, 5],
            pointRadius: 3.5,
            pointHoverRadius: 5.5,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: lineColor,
            pointBorderWidth: 2,
            tension: 0.15,
            fill: false,
            order: 1,
        });
    }

    return {
        labels,
        datasets,
    };
});

const newStudentsChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    layout: {
        padding: { top: 28 },
    },
    onHover: (event, chartElement) => {
        const canvas = event?.native?.target;
        if (canvas) {
            const idx = chartElement?.[0]?.index;
            const isClickableBar = chartElement && chartElement.length > 0 && idx < chartAcademicYears.value.length;
            canvas.style.cursor = isClickableBar ? 'pointer' : 'default';
        }
    },
    onClick: (event, elements) => {
        if (elements && elements.length > 0) {
            const index = elements[0].index;
            if (index >= chartAcademicYears.value.length) {
                return;
            }
            const clickedYear = chartAcademicYears.value[index];
            if (clickedYear) {
                selectedYearName.value = clickedYear;
                selectedChartYear.value = clickedYear;
                handleFilterChange();
            }
        } else {
            // Klik bebas di luar batang / canvas kosong: otomatis kembali ke TA Sekarang & Semua Semester
            const defaultYear = props.currentAcademicYear?.name || (props.availableAcademicYears?.[0] ?? '2026/2027');
            if (selectedYearName.value !== defaultYear || selectedSemester.value !== 'Semua') {
                selectedYearName.value = defaultYear;
                selectedSemester.value = 'Semua';
                selectedChartYear.value = defaultYear;
                showStudentsTab.value = false;
                handleFilterChange();
            }
        }
    },
    plugins: {
        legend: { display: false },
        title: { display: false },
        tooltip: {
            backgroundColor: 'rgba(15, 23, 42, 0.94)',
            titleFont: { size: 13, weight: 'bold', family: "'Plus Jakarta Sans', 'Inter', sans-serif" },
            bodyFont: { size: 12, family: "'Plus Jakarta Sans', 'Inter', sans-serif" },
            padding: 12,
            cornerRadius: 12,
            mode: 'index',
            intersect: false,
            callbacks: {
                title: (items) => {
                    const idx = items[0]?.dataIndex ?? 0;
                    if (showForecastBar.value && idx === chartAcademicYears.value.length) {
                        return `Proyeksi TA ${enrollmentForecast.value.nextYearLabel}`;
                    }
                    return `Tahun Ajaran ${items[0].label}`;
                },
                label: (item) => {
                    if (item.dataset?.type === 'line') {
                        return ` ${item.dataset.label}: ~${item.raw} Siswa`;
                    }
                    const prefix = showForecastBar.value && item.dataIndex === chartAcademicYears.value.length ? 'Est. ' : '';
                    return ` ${prefix}${item.dataset.label}: ${item.raw} Siswa`;
                },
                afterBody: (items) => {
                    const barItems = items.filter((it) => it.dataset?.type !== 'line');
                    const total = barItems.reduce((sum, it) => sum + (Number(it.raw) || 0), 0);
                    const idx = items[0]?.dataIndex ?? 0;
                    if (showForecastBar.value && idx === chartAcademicYears.value.length) {
                        const f = enrollmentForecast.value;
                        const low = showMutasiInChart.value ? f.totalLow : f.siswaBaruLow;
                        const high = showMutasiInChart.value ? f.totalHigh : f.siswaBaruHigh;
                        return [` Estimasi Total: ~${total} Siswa`, ` Rentang Perkiraan: ${low}–${high} Siswa`];
                    }
                    return ` Total Masuk: ${total} Siswa`;
                },
            },
        },
        datalabels: {
            display: (ctx) => {
                if (ctx.dataset?.type === 'line') return false;
                const targetBarIndex = showMutasiInChart.value ? 1 : 0;
                return ctx.datasetIndex === targetBarIndex;
            },
            anchor: 'end',
            align: 'top',
            formatter: (value, ctx) => {
                const dataIndex = ctx.dataIndex;
                const datasets = ctx.chart.data.datasets || [];
                const total = datasets.reduce((sum, ds) => {
                    if (ds.type === 'line') return sum;
                    return sum + (Number(ds.data?.[dataIndex]) || 0);
                }, 0);
                if (total <= 0) return '';
                const isEst = showForecastBar.value && dataIndex === chartAcademicYears.value.length;
                return isEst ? `~${total}` : total;
            },
            color: (ctx) => {
                const isEst = showForecastBar.value && ctx.dataIndex === chartAcademicYears.value.length;
                if (isEst) {
                    return enrollmentForecast.value.status === 'warning' ? '#be123c' : '#047857';
                }
                const year = chartAcademicYears.value[ctx.dataIndex];
                return year === selectedChartYear.value ? '#047857' : '#334155';
            },
            font: { weight: '800', size: 12, family: "'Plus Jakarta Sans', 'Inter', sans-serif" },
            offset: 4,
        },
    },
    scales: {
        x: {
            stacked: true,
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
            stacked: true,
            title: {
                display: true,
                text: 'Jumlah Siswa Masuk',
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

    const map = new Map();
    filteredStudents.value.forEach(s => {
        if (isNewStudentForYear(s, selectedChartYear.value, selectedSemester.value, props.allAcademicYears)) {
            map.set(s.id, s);
        }
    });

    chartStudentsSource.value
        .filter(s => matchesUnit(s, activeUnit.value))
        .forEach(s => {
            if (isNewStudentForYear(s, selectedChartYear.value, selectedSemester.value, props.allAcademicYears)) {
                if (!map.has(s.id)) {
                    map.set(s.id, s);
                }
            }
        });

    return Array.from(map.values());
});

const chartYearCounts = computed(() => {
    const list = studentsInSelectedChartYear.value;
    let male = 0;
    let female = 0;
    let siswaBaru = 0;
    let mutasiMasuk = 0;

    list.forEach(s => {
        const jk = String(s.jk || '').toUpperCase();
        if (jk === 'L') male++;
        else if (jk === 'P') female++;

        if (isMutasiMasuk(s)) {
            mutasiMasuk++;
        } else {
            siswaBaru++;
        }
    });

    return { total: list.length, male, female, siswaBaru, mutasiMasuk };
});

const displayedNewStudents = computed(() => {
    let list = studentsInSelectedChartYear.value;

    // Filter Tipe Pendaftaran (Siswa Baru / Mutasi Masuk)
    if (studentTypeTab.value === 'baru') {
        list = list.filter(s => !isMutasiMasuk(s));
    } else if (studentTypeTab.value === 'mutasi') {
        list = list.filter(s => isMutasiMasuk(s));
    }

    // Filter Gender
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
</script>

<template>
    <div ref="pageContainerRef" class="flex-1 overflow-y-auto overflow-x-hidden w-full max-w-full p-4 md:p-margin-desktop bg-white min-h-screen">
        <Head title="Statistik Siswa - Foundation Data Center" />

        <div class="max-w-[1440px] w-full mx-auto pb-16 md:pb-8 min-w-0">
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
            <!-- TOOLBAR FILTER                                               -->
            <!-- ============================================================ -->
            <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 max-w-full min-w-0">
                <!-- Segmented Capsule Tabs Unit Sekolah (Semua, SDIT, SMPIT, SMAIT, PPTQ) - Scrollable Horizontal di Mobile -->
                <div class="overflow-x-auto no-scrollbar max-w-full -mx-4 px-4 sm:mx-0 sm:px-0">
                    <div class="bg-slate-100 p-1 rounded-full inline-flex items-center gap-0.5 border border-slate-200/90 shrink-0">
                        <button
                            v-for="tab in unitTabs"
                            :key="tab.id"
                            type="button"
                            @click="activeUnit = tab.id"
                            :class="[
                                'inline-flex items-center gap-1.5 px-3.5 sm:px-4 py-1.5 rounded-full text-xs sm:text-[13px] transition-all duration-150 cursor-pointer shrink-0 whitespace-nowrap',
                                activeUnit === tab.id
                                    ? 'bg-emerald-100 text-emerald-950 font-bold border border-emerald-300 shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900 font-medium border border-transparent'
                            ]"
                        >
                            <span class="material-symbols-outlined text-[16px]" :class="{ 'filled-icon': activeUnit === tab.id }">{{ tab.icon }}</span>
                            <span>{{ tab.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Filter TA, Semester & Reset (Scrollable horizontal jika melebihi layar di Mobile) -->
                <div class="overflow-x-auto no-scrollbar max-w-full -mx-4 px-4 sm:mx-0 sm:px-0">
                    <div class="flex items-center gap-2 flex-nowrap shrink-0 w-max">
                        <!-- Tombol Reset ke TA Sekarang (Muncul jika filter berbeda dari TA aktif saat ini) -->
                        <button
                            v-if="isDifferentFromCurrent"
                            type="button"
                            @click="resetToCurrentAcademicYear"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 shadow-2xs transition-all cursor-pointer shrink-0 whitespace-nowrap"
                            title="Kembali ke Tahun Ajaran & Semester Aktif Sekarang"
                        >
                            <span class="material-symbols-outlined text-[15px]">restart_alt</span>
                            <span>Reset ke TA Sekarang</span>
                        </button>

                        <!-- Dropdown Tahun Ajaran -->
                        <div class="relative inline-flex items-center shrink-0">
                            <span class="absolute left-3 text-slate-400 material-symbols-outlined text-[17px] pointer-events-none">calendar_month</span>
                            <select
                                v-model="selectedYearName"
                                @change="handleFilterChange"
                                class="appearance-none bg-white border border-slate-200/90 hover:border-emerald-300 text-slate-800 text-xs font-bold rounded-full pl-8 pr-7 py-1.5 shadow-2xs transition-all focus:outline-none focus:ring-2 focus:ring-emerald-500/20 cursor-pointer whitespace-nowrap"
                            >
                                <option value="Semua">Semua TA (All Time)</option>
                                <option v-for="yr in (availableAcademicYears && availableAcademicYears.length ? availableAcademicYears : [])" :key="yr" :value="yr">
                                    TA {{ yr }}
                                </option>
                            </select>
                            <span class="absolute right-2 text-slate-400 material-symbols-outlined text-[16px] pointer-events-none">expand_more</span>
                        </div>

                        <!-- Dropdown Semester -->
                        <div class="relative inline-flex items-center shrink-0">
                            <span class="absolute left-3 text-slate-400 material-symbols-outlined text-[17px] pointer-events-none">schedule</span>
                            <select
                                v-model="selectedSemester"
                                @change="handleFilterChange"
                                :disabled="isAllTime"
                                :class="[
                                    'appearance-none border text-xs font-bold rounded-full pl-8 pr-7 py-1.5 shadow-2xs transition-all focus:outline-none focus:ring-2 focus:ring-emerald-500/20 whitespace-nowrap',
                                    isAllTime
                                        ? 'bg-slate-100 border-slate-200 text-slate-400 cursor-not-allowed'
                                        : 'bg-white border-slate-200/90 hover:border-emerald-300 text-slate-800 cursor-pointer'
                                ]"
                            >
                                <option value="Semua">Semua Semester</option>
                                <option value="Ganjil">Semester Ganjil</option>
                                <option value="Genap">Semester Genap</option>
                            </select>
                            <span class="absolute right-2 text-slate-400 material-symbols-outlined text-[16px] pointer-events-none">expand_more</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- 2 CARD STATISTIK UTAMA: SISWA AKTIF & SISWA BARU             -->
            <!-- ============================================================ -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 mb-5">
                <!-- Card 1: Siswa Aktif / Total All Time -->
                <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-yellow-200 border border-yellow-300/80 flex items-center justify-center text-emerald-950 shadow-2xs shrink-0">
                        <span class="material-symbols-outlined text-[24px] filled-icon">groups</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                            {{ isAllTime ? 'Total Siswa (All Time)' : 'Siswa Aktif' }}
                        </p>
                        <div class="flex items-baseline gap-2 mt-0.5 flex-wrap">
                            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 leading-tight">
                                {{ isAllTime ? metrics.total : metrics.active }}
                            </p>
                            <span v-if="isAllTime" class="text-xs text-slate-500 font-medium">
                                ({{ metrics.active }} Aktif • {{ metrics.lulus }} Lulus • {{ metrics.tidakAktif }} Keluar)
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Siswa Baru & Mutasi -->
                <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-yellow-200 border border-yellow-300/80 flex items-center justify-center text-emerald-950 shadow-2xs shrink-0">
                        <span class="material-symbols-outlined text-[24px]">person_add</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                            {{ isAllTime ? 'Total Masuk (All Time)' : 'Siswa Baru & Mutasi' }}
                        </p>
                        <div class="flex items-baseline gap-2 mt-0.5 flex-wrap">
                            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 leading-tight">{{ newStudentsCount.total }}</p>
                            <span v-if="newStudentsCount.mutasiMasuk > 0" class="text-xs text-slate-500 font-medium">
                                ({{ newStudentsCount.siswaBaru }} Baru + {{ newStudentsCount.mutasiMasuk }} Mutasi)
                            </span>
                        </div>
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
                            Jumlah Siswa Masuk per Tahun Ajaran ({{ activeUnitLabel }})
                        </h3>
                    </div>
                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 flex-wrap">
                        <!-- Toggle Legend: Siswa Baru & Mutasi Masuk -->
                        <div class="inline-flex items-center gap-1.5 flex-wrap">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-900 text-[11px] font-bold">
                                <span class="w-2.5 h-2.5 rounded-xs bg-emerald-600 inline-block"></span>
                                <span>Siswa Baru</span>
                            </span>
                            <button
                                type="button"
                                @click="showMutasiInChart = !showMutasiInChart"
                                :class="[
                                    'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border transition-all cursor-pointer select-none',
                                    showMutasiInChart
                                        ? 'bg-amber-50 text-amber-900 border-amber-200 hover:bg-amber-100/70'
                                        : 'bg-slate-100 text-slate-400 border-slate-200 line-through opacity-75',
                                ]"
                                title="Klik untuk menampilkan/menyembunyikan data Mutasi Masuk pada grafik"
                            >
                                <span
                                    class="w-2.5 h-2.5 rounded-xs inline-block"
                                    :class="showMutasiInChart ? 'bg-amber-500' : 'bg-slate-300'"
                                ></span>
                                <span>Mutasi Masuk</span>
                            </button>
                        </div>

                        <!-- Toggle Garis Tren & Prediksi + Hint -->
                        <div v-if="enrollmentForecast.isAvailable" class="inline-flex items-center gap-1.5 flex-wrap">
                            <button
                                type="button"
                                @click="showTrendLine = !showTrendLine"
                                :class="[
                                    'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold border transition-all cursor-pointer select-none',
                                    showTrendLine
                                        ? (enrollmentForecast.status === 'warning'
                                            ? 'bg-rose-50 text-rose-800 border-rose-200'
                                            : 'bg-sky-50 text-sky-800 border-sky-200')
                                        : 'bg-slate-100 text-slate-500 border-slate-200 hover:bg-slate-200/70',
                                ]"
                                title="Tampilkan atau sembunyikan garis tren historis"
                            >
                                <span class="material-symbols-outlined text-[14px]">show_chart</span>
                                <span>Garis Tren</span>
                            </button>
                            <button
                                type="button"
                                @click="showPrediction = !showPrediction"
                                :class="[
                                    'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold border transition-all cursor-pointer select-none',
                                    showPrediction
                                        ? 'bg-emerald-900 text-yellow-200 border-emerald-950 shadow-2xs'
                                        : 'bg-slate-100 text-slate-500 border-slate-200 hover:bg-slate-200/70',
                                ]"
                                title="Tampilkan atau sembunyikan proyeksi tahun ajaran berikutnya & early warning"
                            >
                                <span class="material-symbols-outlined text-[14px]">auto_graph</span>
                                <span>Prediksi</span>
                            </button>
                            <button
                                type="button"
                                @click="showForecastHint = !showForecastHint"
                                :class="[
                                    'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold border transition-all cursor-pointer select-none',
                                    showForecastHint
                                        ? 'bg-indigo-100 text-indigo-900 border-indigo-300'
                                        : 'bg-white hover:bg-slate-50 text-slate-600 border-slate-200',
                                ]"
                                title="Penjelasan cara membaca statistik & prediksi"
                            >
                                <span class="material-symbols-outlined text-[14px]">help</span>
                                <span>Hint</span>
                            </button>
                        </div>

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

                <!-- Ringkasan Compact Early Warning & Proyeksi (Di Bawah Diagram) -->
                <div
                    v-if="showPrediction && enrollmentForecast.isAvailable"
                    :class="[
                        'mt-3 px-3 py-2.5 rounded-2xl border flex flex-col lg:flex-row lg:items-center justify-between gap-2.5',
                        enrollmentForecast.status === 'warning'
                            ? 'bg-rose-50/70 border-rose-200/90'
                            : enrollmentForecast.status === 'growing'
                                ? 'bg-emerald-50/70 border-emerald-200/90'
                                : 'bg-slate-50/90 border-slate-200/90',
                    ]"
                >
                    <div class="flex items-start sm:items-center gap-2 min-w-0">
                        <span
                            :class="[
                                'material-symbols-outlined text-[18px] shrink-0 mt-0.5 sm:mt-0',
                                enrollmentForecast.status === 'warning'
                                    ? 'text-rose-600'
                                    : enrollmentForecast.status === 'growing'
                                        ? 'text-emerald-700'
                                        : 'text-sky-700',
                            ]"
                        >
                            {{ enrollmentForecast.status === 'warning' ? 'warning' : (enrollmentForecast.status === 'growing' ? 'trending_up' : 'insights') }}
                        </span>
                        <div class="min-w-0 flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span
                                :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider border shrink-0',
                                    enrollmentForecast.status === 'warning'
                                        ? 'bg-rose-100 text-rose-800 border-rose-300'
                                        : enrollmentForecast.status === 'growing'
                                            ? 'bg-emerald-100 text-emerald-800 border-emerald-300'
                                            : 'bg-sky-100 text-sky-800 border-sky-300',
                                ]"
                            >
                                {{ enrollmentForecast.statusLabel }}
                            </span>
                            <span class="text-[11px] font-semibold text-slate-600">
                                Basis {{ enrollmentForecast.historyYearsCount }} TA → TA {{ enrollmentForecast.nextYearLabel }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5 flex-wrap text-[11px]">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-white border border-slate-200/90 font-bold text-slate-900">
                            <span class="text-slate-400 font-semibold">Est. Baru:</span>
                            <span>~{{ enrollmentForecast.siswaBaruEst }}</span>
                        </span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-white border border-slate-200/90 font-bold text-slate-900">
                            <span class="text-slate-400 font-semibold">Rentang:</span>
                            <span>{{ showMutasiInChart ? `${enrollmentForecast.totalLow}–${enrollmentForecast.totalHigh}` : `${enrollmentForecast.siswaBaruLow}–${enrollmentForecast.siswaBaruHigh}` }}</span>
                        </span>
                        <span
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-white border border-slate-200/90 font-bold"
                            :class="(enrollmentForecast.recent3DeltaPerYear ?? 0) < 0 ? 'text-rose-700' : ((enrollmentForecast.recent3DeltaPerYear ?? 0) > 0 ? 'text-emerald-700' : 'text-slate-800')"
                        >
                            <span class="text-slate-400 font-semibold">Laju 3 TA:</span>
                            <span>{{ (enrollmentForecast.recent3DeltaPerYear ?? 0) > 0 ? '+' : '' }}{{ enrollmentForecast.recent3DeltaPerYear ?? 0 }}/TA</span>
                        </span>
                        <span
                            v-if="showMutasiInChart"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-amber-50/80 border border-amber-200/90 font-bold text-amber-900"
                        >
                            <span class="text-amber-700 font-semibold">Total (+Mutasi ~{{ enrollmentForecast.mutasiEst }}):</span>
                            <span>~{{ enrollmentForecast.totalEst }}</span>
                        </span>
                    </div>
                </div>

                <!-- Box Penjelasan Metode Statistik (Compact & Mudah Dipahami) -->
                <div
                    v-if="showForecastHint && enrollmentForecast.isAvailable"
                    class="mt-2.5 p-3 sm:p-3.5 rounded-2xl bg-indigo-50/70 border border-indigo-200/80 text-xs text-slate-700 space-y-1.5"
                >
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5 font-extrabold text-indigo-950 text-[11.5px]">
                            <span class="material-symbols-outlined text-[16px] text-indigo-700">lightbulb</span>
                            <span>Metode Statistik yang Digunakan</span>
                        </div>
                        <button
                            type="button"
                            @click="showForecastHint = false"
                            class="text-slate-400 hover:text-slate-700 cursor-pointer"
                            title="Tutup penjelasan"
                        >
                            <span class="material-symbols-outlined text-[16px]">close</span>
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-1 text-[11px] leading-snug">
                        <p>
                            <strong class="text-slate-900">1. Weighted Damped Trend (Siswa Baru):</strong>
                            Regresi berbobot yang mengutamakan <strong>3 TA terakhir</strong> agar penurunan/kenaikan terbaru terbaca, lalu diredam (×0,78) supaya angka estimasi tidak ekstrem.
                        </p>
                        <p>
                            <strong class="text-slate-900">2. Weighted RMSE (Rentang):</strong>
                            Mengukur naik–turun historis untuk membentuk kisaran batas bawah (konservatif) hingga batas atas (optimis).
                        </p>
                        <p>
                            <strong class="text-slate-900">3. Median Historis (Mutasi Masuk):</strong>
                            Mengambil nilai tengah mutasi dari TA penuh sebelumnya agar lonjakan acak tidak merusak tren reguler.
                        </p>
                        <p>
                            <strong class="text-slate-900">4. Early Warning:</strong>
                            Otomatis muncul jika siswa baru turun <strong>≥ 2 TA berturut-turut</strong> atau laju tren bernilai negatif.
                        </p>
                    </div>
                </div>

                <!-- Petunjuk interaktif klik batang -->
                <div v-if="chartAcademicYears.length > 0 && !showStudentsTab" class="mt-3 text-center">
                    <span class="text-[11.5px] text-slate-500 font-medium inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[15px] text-emerald-700">touch_app</span>
                        <span>Klik salah satu batang grafik untuk memfilter Tahun Ajaran</span>
                    </span>
                </div>

                <!-- ============================================================ -->
                <!-- TAB RINCIAN NAMA SISWA BARU (MASIH 1 CONTAINER)              -->
                <!-- ============================================================ -->
                <div v-if="showStudentsTab" class="mt-6 pt-5 border-t border-slate-100">
                    <!-- Tab Header: Judul & Sub-tabs (Tipe Pendaftaran + Gender) -->
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-yellow-400 inline-block"></span>
                                <h4 class="text-xs sm:text-sm font-bold text-slate-900">
                                    Daftar Nama Siswa Masuk — TA {{ selectedChartYear }}
                                </h4>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-950 border border-emerald-300">
                                    {{ chartYearCounts.total }} Siswa
                                </span>
                            </div>
                            <p class="text-[11.5px] text-slate-500 mt-0.5">
                                Unit: {{ activeUnitLabel }} • Periode: {{ selectedSemester === 'Semua' ? 'Semua Semester' : `Semester ${selectedSemester}` }}
                            </p>
                        </div>

                        <!-- Sub-tabs: Tipe & Gender Filter -->
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Filter Tipe: Semua / Siswa Baru / Mutasi Masuk -->
                            <div class="bg-slate-100 p-1 rounded-full inline-flex items-center gap-0.5 border border-slate-200/90">
                                <button
                                    type="button"
                                    @click="studentTypeTab = 'all'"
                                    :class="[
                                        'px-2.5 py-1 rounded-full text-xs font-bold transition-all cursor-pointer',
                                        studentTypeTab === 'all'
                                            ? 'bg-emerald-900 text-yellow-200 shadow-2xs'
                                            : 'text-slate-600 hover:text-slate-900'
                                    ]"
                                >
                                    Semua ({{ chartYearCounts.total }})
                                </button>
                                <button
                                    type="button"
                                    @click="studentTypeTab = 'baru'"
                                    :class="[
                                        'px-2.5 py-1 rounded-full text-xs font-bold transition-all cursor-pointer',
                                        studentTypeTab === 'baru'
                                            ? 'bg-emerald-700 text-white shadow-2xs'
                                            : 'text-slate-600 hover:text-slate-900'
                                    ]"
                                >
                                    Siswa Baru ({{ chartYearCounts.siswaBaru }})
                                </button>
                                <button
                                    type="button"
                                    @click="studentTypeTab = 'mutasi'"
                                    :class="[
                                        'px-2.5 py-1 rounded-full text-xs font-bold transition-all cursor-pointer',
                                        studentTypeTab === 'mutasi'
                                            ? 'bg-amber-600 text-white shadow-2xs'
                                            : 'text-slate-600 hover:text-slate-900'
                                    ]"
                                >
                                    Mutasi ({{ chartYearCounts.mutasiMasuk }})
                                </button>
                            </div>

                            <!-- Sub-tabs Gender: Semua, Laki-laki, Perempuan -->
                            <div class="bg-slate-100 p-1 rounded-full inline-flex items-center gap-0.5 border border-slate-200/90">
                                <button
                                    type="button"
                                    @click="studentGenderTab = 'all'"
                                    :class="[
                                        'px-2.5 py-1 rounded-full text-xs font-bold transition-all cursor-pointer',
                                        studentGenderTab === 'all'
                                            ? 'bg-emerald-900 text-yellow-200 shadow-2xs'
                                            : 'text-slate-600 hover:text-slate-900'
                                    ]"
                                >
                                    Semua Gender
                                </button>
                                <button
                                    type="button"
                                    @click="studentGenderTab = 'L'"
                                    :class="[
                                        'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold transition-all cursor-pointer',
                                        studentGenderTab === 'L'
                                            ? BADGE_COLORS.gender.L.pillActiveClass
                                            : 'text-slate-600 hover:text-slate-900'
                                    ]"
                                >
                                    <span :class="['px-1 py-0.2 rounded text-[9px] font-extrabold', BADGE_COLORS.gender.L.pillBadgeClass]">L</span>
                                    <span>Putra ({{ chartYearCounts.male }})</span>
                                </button>
                                <button
                                    type="button"
                                    @click="studentGenderTab = 'P'"
                                    :class="[
                                        'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold transition-all cursor-pointer',
                                        studentGenderTab === 'P'
                                            ? BADGE_COLORS.gender.P.pillActiveClass
                                            : 'text-slate-600 hover:text-slate-900'
                                    ]"
                                >
                                    <span :class="['px-1 py-0.2 rounded text-[9px] font-extrabold', BADGE_COLORS.gender.P.pillBadgeClass]">P</span>
                                    <span>Putri ({{ chartYearCounts.female }})</span>
                                </button>
                            </div>
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
                                        class="w-7 h-7 rounded-lg border flex items-center justify-center font-bold text-xs shrink-0"
                                        :class="getGenderBadgeClass(st.jk)"
                                    >
                                        {{ st.jk || '-' }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <p class="text-xs font-bold text-slate-900 truncate">{{ st.nama || 'Siswa Tanpa Nama' }}</p>
                                            <span
                                                class="px-1.5 py-0.2 rounded border text-[9.5px] font-bold"
                                                :class="getRegistrationTypeBadgeClass(isMutasiMasuk(st))"
                                            >
                                                {{ isMutasiMasuk(st) ? 'Mutasi' : 'Baru' }}
                                            </span>
                                        </div>
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
                                        <th class="py-2.5 px-3.5 text-center w-28">Status Masuk</th>
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
                                                class="inline-flex items-center justify-center px-2 py-0.5 rounded border text-[10.5px] font-bold"
                                                :class="getRegistrationTypeBadgeClass(isMutasiMasuk(st))"
                                            >
                                                {{ isMutasiMasuk(st) ? 'Mutasi Masuk' : 'Siswa Baru' }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3.5 text-center">
                                            <span
                                                class="inline-flex items-center justify-center px-2 py-0.5 rounded border text-[10.5px] font-bold"
                                                :class="getGenderBadgeClass(st.jk)"
                                            >
                                                {{ getGenderShortLabel(st.jk) }}
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
                        <span>Tidak ada siswa yang sesuai kriteria pada TA {{ selectedChartYear }}.</span>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- GENDER SISWA & PROGRAM SISWA (DESKTOP: SAMPING, MOBILE: BAWAH) -->
            <!-- ============================================================ -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 mb-5">
                <!-- ============================================================ -->
                <!-- GENDER SISWA (BAR LAKI-LAKI & PEREMPUAN)                     -->
                <!-- ============================================================ -->
                <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-6 shadow-xs flex flex-col justify-between">
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

                    <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs">
                        <!-- Baris Atas: [L] Laki-laki ... Perempuan [P] -->
                        <div class="flex items-center justify-between text-xs sm:text-[13.5px] font-bold mb-2.5 sm:mb-3">
                            <div class="flex items-center gap-2 text-cyan-950">
                                <span :class="['inline-flex items-center justify-center w-6 h-6 rounded-lg text-[11px] font-bold border shadow-2xs', getGenderBadgeClass('L')]">L</span>
                                <span>Laki-laki</span>
                            </div>
                            <div class="flex items-center gap-2 text-fuchsia-950">
                                <span>Perempuan</span>
                                <span :class="['inline-flex items-center justify-center w-6 h-6 rounded-lg text-[11px] font-bold border shadow-2xs', getGenderBadgeClass('P')]">P</span>
                            </div>
                        </div>

                        <!-- Dual Bar: Cyan untuk Laki-laki & Fuchsia untuk Perempuan -->
                        <div class="w-full h-3.5 sm:h-4 rounded-full bg-slate-100 overflow-hidden flex">
                            <div
                                v-if="genderTotal > 0"
                                :class="[BADGE_COLORS.gender.L.barClass, 'h-full transition-all duration-500']"
                                :style="{ width: `${malePercentage}%` }"
                                :title="`Laki-laki: ${metrics.male} siswa (${malePercentage}%)`"
                            ></div>
                            <div
                                v-if="genderTotal > 0"
                                :class="[BADGE_COLORS.gender.P.barClass, 'h-full transition-all duration-500']"
                                :style="{ width: `${femalePercentage}%` }"
                                :title="`Perempuan: ${metrics.female} siswa (${femalePercentage}%)`"
                            ></div>
                        </div>

                        <!-- Baris Bawah: Total Putra & Putri Beserta Persen di Sebelah Angka Total -->
                        <div class="flex items-center justify-between text-xs sm:text-[12.5px] text-slate-500 font-medium mt-2.5 sm:mt-3">
                            <span>{{ metrics.male }} siswa putra <span class="font-bold text-slate-700">({{ malePercentage }}%)</span></span>
                            <span>{{ metrics.female }} siswa putri <span class="font-bold text-slate-700">({{ femalePercentage }}%)</span></span>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!-- PROGRAM SISWA (BAR UMUM & BOARDING)                          -->
                <!-- ============================================================ -->
                <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-6 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                Program Siswa
                            </h3>
                        </div>
                        <span class="text-xs text-slate-500 font-semibold">
                            {{ programMetrics.total }} Siswa
                        </span>
                    </div>

                    <!-- Box Sesuai Style Gender Bar -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs">
                        <!-- Baris Atas: [U] Umum ... (Opsional [F] Fullday) ... Boarding [B] -->
                        <div class="flex items-center justify-between text-xs sm:text-[13.5px] font-bold mb-2.5 sm:mb-3">
                            <div class="flex items-center gap-2 text-slate-800">
                                <span :class="['inline-flex items-center justify-center w-6 h-6 rounded-lg text-[11px] font-bold border shadow-2xs', getProgramBadgeClass('Umum')]">U</span>
                                <span>Umum</span>
                            </div>
                            <div v-if="programMetrics.fullday > 0" class="flex items-center gap-2 text-teal-900">
                                <span :class="['inline-flex items-center justify-center w-6 h-6 rounded-lg text-[11px] font-bold border shadow-2xs', getProgramBadgeClass('Fullday')]">F</span>
                                <span>Fullday</span>
                            </div>
                            <div class="flex items-center gap-2 text-violet-950">
                                <span>Boarding</span>
                                <span :class="['inline-flex items-center justify-center w-6 h-6 rounded-lg text-[11px] font-bold border shadow-2xs', getProgramBadgeClass('Boarding')]">B</span>
                            </div>
                        </div>

                        <!-- Bar: Slate untuk Umum, Teal untuk Fullday, Violet untuk Boarding -->
                        <div class="w-full h-3.5 sm:h-4 rounded-full bg-slate-100 overflow-hidden flex">
                            <div
                                v-if="programMetrics.total > 0 && programMetrics.umum > 0"
                                :class="[BADGE_COLORS.program.umum.barClass, 'h-full transition-all duration-500']"
                                :style="{ width: `${programMetrics.umumPercentage}%` }"
                                :title="`Umum: ${programMetrics.umum} siswa (${programMetrics.umumPercentage}%)`"
                            ></div>
                            <div
                                v-if="programMetrics.total > 0 && programMetrics.fullday > 0"
                                :class="[BADGE_COLORS.program.fullday.barClass, 'h-full transition-all duration-500']"
                                :style="{ width: `${programMetrics.fulldayPercentage}%` }"
                                :title="`Fullday: ${programMetrics.fullday} siswa (${programMetrics.fulldayPercentage}%)`"
                            ></div>
                            <div
                                v-if="programMetrics.total > 0 && programMetrics.boarding > 0"
                                :class="[BADGE_COLORS.program.boarding.barClass, 'h-full transition-all duration-500']"
                                :style="{ width: `${programMetrics.boardingPercentage}%` }"
                                :title="`Boarding: ${programMetrics.boarding} siswa (${programMetrics.boardingPercentage}%)`"
                            ></div>
                        </div>

                        <!-- Baris Bawah: Total Siswa Tiap Program Beserta Persen -->
                        <div class="flex items-center justify-between text-xs sm:text-[12.5px] text-slate-500 font-medium mt-2.5 sm:mt-3">
                            <span>{{ programMetrics.umum }} siswa umum <span class="font-bold text-slate-700">({{ programMetrics.umumPercentage }}%)</span></span>
                            <span v-if="programMetrics.fullday > 0">{{ programMetrics.fullday }} siswa fullday <span class="font-bold text-slate-700">({{ programMetrics.fulldayPercentage }}%)</span></span>
                            <span>{{ programMetrics.boarding }} siswa boarding <span class="font-bold text-slate-700">({{ programMetrics.boardingPercentage }}%)</span></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- PETA SEBARAN DOMISILI SISWA (SOLO RAYA)                      -->
            <!-- ============================================================ -->
            <StudentDomicileMap
                :students="filteredStudents"
                :academic-year="selectedYearName"
                :semester="selectedSemester"
                :unit="activeUnitLabel"
                :is-all-time="isAllTime"
            />

            <!-- ============================================================ -->
            <!-- STATISTIK ASAL SEKOLAH SISWA                                 -->
            <!-- ============================================================ -->
            <StudentSchoolOriginChart
                :students="filteredStudents"
                :academic-year="selectedYearName"
                :unit="activeUnitLabel"
                :is-all-time="isAllTime"
            />
        </div>
    </div>
</template>

<style scoped>
.filled-icon {
    font-variation-settings: 'FILL' 1;
}
</style>
