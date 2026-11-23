<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import * as XLSX from 'xlsx';
import AppLayout from '@/Layouts/AppLayout.vue';
import PsbQuadrantChart from './Partials/PsbQuadrantChart.vue';
import PsbSchoolClusterMap from './Partials/PsbSchoolClusterMap.vue';
import PsbStudentRowCard from './Partials/PsbStudentRowCard.vue';
import {
    CAMPUS_RING_META,
    PSB_TIERS,
    PSB_WELFARE_TIERS,
    SOLO_RAYA_KABUPATENS,
    matchAndClassifySchools,
    matchAndClassifyVillages,
} from '@/Utils/psbMatcher';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    students: {
        type: Array,
        default: () => [],
    },
    academicYears: {
        type: Array,
        default: () => [],
    },
    currentAcademicYearName: {
        type: String,
        default: null,
    },
});

// ============================================================
// State Memuat Dataset Referensi Solo Raya (Static JSON)
// ============================================================
const referenceSchools = ref([]);
const datasetMeta = ref(null);
const referenceVillages = ref([]);
const tollGates = ref([]);
const welfareMeta = ref(null);
const isLoadingDataset = ref(true);
const datasetError = ref(null);

async function loadReferenceDataset() {
    isLoadingDataset.value = true;
    datasetError.value = null;
    const cacheBust = '20261123grobogan';
    try {
        const [schoolRes, welfareRes] = await Promise.all([
            fetch(`/data/solo-raya-sd-sederajat.json?v=${cacheBust}`),
            fetch(`/data/solo-raya-desa-potensi.json?v=${cacheBust}`),
        ]);
        if (!schoolRes.ok) {
            throw new Error(`Gagal memuat data (${schoolRes.status})`);
        }
        const json = await schoolRes.json();
        referenceSchools.value = Array.isArray(json.schools) ? json.schools : [];
        datasetMeta.value = json.meta || null;

        if (welfareRes.ok) {
            const wJson = await welfareRes.json();
            referenceVillages.value = Array.isArray(wJson.villages) ? wJson.villages : [];
            tollGates.value = Array.isArray(wJson.toll_gates) ? wJson.toll_gates : [];
            welfareMeta.value = wJson.meta || null;
        }
    } catch (err) {
        datasetError.value = err?.message || 'Tidak dapat memuat dataset sekolah Solo Raya.';
    } finally {
        isLoadingDataset.value = false;
    }
}

// ============================================================
// Sticky Scroll Observer (konsisten dengan Statistik Siswa)
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

function handleKeyDown(e) {
    if (e.key === 'Escape') {
        if (selectedSchoolModal.value) {
            selectedSchoolModal.value = null;
        } else if (selectedVillageModal.value) {
            selectedVillageModal.value = null;
        } else if (unmatchedGroupModal.value) {
            unmatchedGroupModal.value = null;
        } else if (quadrantGroupModal.value) {
            quadrantGroupModal.value = null;
        }
    }
}

onMounted(() => {
    loadReferenceDataset();
    window.addEventListener('scroll', checkTitleVisibility, { passive: true });
    window.addEventListener('keydown', handleKeyDown);
    if ('IntersectionObserver' in window && mainTitleRef.value) {
        titleObserver = new IntersectionObserver(
            ([entry]) => {
                if (!entry.isIntersecting && entry.boundingClientRect.top < 50) {
                    isScrolledPastTitle.value = true;
                } else if (entry.isIntersecting) {
                    isScrolledPastTitle.value = false;
                }
            },
            { root: null, threshold: 0 }
        );
        titleObserver.observe(mainTitleRef.value);
    }
});

onUnmounted(() => {
    window.removeEventListener('scroll', checkTitleVisibility);
    window.removeEventListener('keydown', handleKeyDown);
    if (titleObserver) titleObserver.disconnect();
});

// ============================================================
// Filter Global (Unit Tujuan, TA Masuk, Tipe Masuk, Wilayah, Bentuk, Status)
// ============================================================
function matchesUnit(student, targetUnit) {
    if (targetUnit === 'all') return true;
    const u = String(student?.unit || '').toUpperCase().trim();
    const up = String(student?.unit_pesantren || '').toUpperCase().trim();
    const p = String(student?.program || '').toUpperCase().trim();

    if (targetUnit === 'SMPIT') return u.includes('SMPIT') || u.includes('SMP IT') || u === 'SMP';
    if (targetUnit === 'SDIT') return u.includes('SDIT') || u.includes('SD IT') || u === 'SD';
    if (targetUnit === 'SMAIT') return u.includes('SMAIT') || u.includes('SMA IT') || u === 'SMA';
    if (targetUnit === 'PPTQ') return u.includes('PPTQ') || up.includes('PPTQ') || p.includes('BOARDING');
    return u === targetUnit.toUpperCase();
}

const hasSmpitStudents = computed(() =>
    props.students.some((s) => matchesUnit(s, 'SMPIT'))
);

const mappingMode = ref('school'); // 'school' | 'welfare'
const isWelfareMode = computed(() => mappingMode.value === 'welfare');

// Default ke SMPIT jika ada siswanya agar pemadanan SD/MI/PKBM langsung relevan, jika tidak gunakan Semua
const activeUnit = ref(hasSmpitStudents.value ? 'SMPIT' : 'all');
const selectedTa = ref('Semua');
const statusKeaktifanFilter = ref('all'); // 'all' | 'aktif' | 'lulus' | 'tidak_aktif'
const registrationFilter = ref('all'); // 'all' | 'baru' | 'mutasi'
const selectedKabupaten = ref('Semua');
const selectedKecamatan = ref('Semua');
const selectedBentuk = ref('Semua'); // 'Semua' | 'SD' | 'MI' | 'PKBM'
const selectedSchoolStatus = ref('Semua'); // 'Semua' | 'Negeri' | 'Swasta'

const activeTierTab = ref('all');
const searchQuery = ref('');
const sortBy = ref('students_desc');
const currentPage = ref(1);
const perPage = 25;

watch(mappingMode, () => {
    activeTierTab.value = 'all';
    searchQuery.value = '';
    sortBy.value = 'students_desc';
    currentPage.value = 1;
    quadrantGroupModal.value = null;
    selectedSchoolModal.value = null;
    selectedVillageModal.value = null;
    unmatchedGroupModal.value = null;
});

watch(selectedKabupaten, () => {
    selectedKecamatan.value = 'Semua';
    currentPage.value = 1;
});

watch(
    [
        activeUnit,
        selectedTa,
        statusKeaktifanFilter,
        registrationFilter,
        selectedKecamatan,
        selectedBentuk,
        selectedSchoolStatus,
        activeTierTab,
        searchQuery,
        sortBy,
    ],
    () => {
        currentPage.value = 1;
    }
);

const unitOptions = [
    { id: 'all', label: 'Semua Unit' },
    { id: 'SMPIT', label: 'SMPIT' },
    { id: 'PPTQ', label: 'PPTQ' },
    { id: 'SMAIT', label: 'SMAIT' },
    { id: 'SDIT', label: 'SDIT' },
];

const availableTaNames = computed(() => {
    const names = new Set(props.academicYears.map((y) => y.name).filter(Boolean));
    props.students.forEach((s) => {
        if (s.ta_masuk) names.add(s.ta_masuk);
    });
    return Array.from(names).sort((a, b) => b.localeCompare(a));
});

const latestTaName = computed(() => {
    if (selectedTa.value !== 'Semua') {
        return selectedTa.value;
    }
    return props.currentAcademicYearName || availableTaNames.value[0] || null;
});

function getStudentStatusCategory(student) {
    const raw = (student?.status_keaktifan || 'Aktif').toLowerCase().trim();
    if (raw === 'lulus') return 'lulus';
    if (raw.includes('tidak') || raw === 'dropout' || raw === 'mutasi_keluar') return 'tidak_aktif';
    return 'aktif';
}

const studentsBeforeStatusFilter = computed(() => {
    return props.students.filter((s) => {
        if (!matchesUnit(s, activeUnit.value)) return false;

        if (registrationFilter.value === 'baru') {
            if ((s.status_registrasi || '').toLowerCase().includes('mutasi')) return false;
        } else if (registrationFilter.value === 'mutasi') {
            if (!(s.status_registrasi || '').toLowerCase().includes('mutasi')) return false;
        }

        if (selectedTa.value !== 'Semua') {
            if (s.ta_masuk !== selectedTa.value) return false;
        }

        return true;
    });
});

const statusCounts = computed(() => {
    let aktif = 0;
    let lulus = 0;
    let tidak_aktif = 0;
    studentsBeforeStatusFilter.value.forEach((s) => {
        const cat = getStudentStatusCategory(s);
        if (cat === 'aktif') aktif++;
        else if (cat === 'lulus') lulus++;
        else tidak_aktif++;
    });

    return {
        all: studentsBeforeStatusFilter.value.length,
        aktif,
        lulus,
        tidak_aktif,
    };
});

const statusKeaktifanLabelText = computed(() => {
    if (statusKeaktifanFilter.value === 'aktif') return 'siswa aktif';
    if (statusKeaktifanFilter.value === 'lulus') return 'siswa lulus (alumni)';
    if (statusKeaktifanFilter.value === 'tidak_aktif') return 'siswa tidak aktif';
    return 'seluruh siswa (Aktif, Lulus & Non-Aktif)';
});

const filteredStudentsForMatching = computed(() => {
    if (statusKeaktifanFilter.value === 'all') {
        return studentsBeforeStatusFilter.value;
    }
    return studentsBeforeStatusFilter.value.filter(
        (s) => getStudentStatusCategory(s) === statusKeaktifanFilter.value
    );
});

const filterContext = computed(() => {
    const unitLabel = activeUnit.value === 'all' ? 'Semua Unit' : activeUnit.value;
    const taLabel = selectedTa.value === 'Semua' ? 'Semua TA' : `TA ${selectedTa.value}`;
    const registrationLabel =
        registrationFilter.value === 'baru'
            ? 'Siswa Baru'
            : registrationFilter.value === 'mutasi'
              ? 'Mutasi'
              : 'Semua Masuk';

    const subParts = [];
    if (selectedKecamatan.value !== 'Semua') {
        subParts.push(`Kec. ${selectedKecamatan.value}`);
    } else if (selectedKabupaten.value !== 'Semua') {
        subParts.push(selectedKabupaten.value === 'Surakarta' ? 'Kota Surakarta' : `Kab. ${selectedKabupaten.value}`);
    }
    if (!isWelfareMode.value) {
        if (selectedBentuk.value !== 'Semua') subParts.push(selectedBentuk.value);
        if (selectedSchoolStatus.value !== 'Semua') subParts.push(selectedSchoolStatus.value);
    }

    return {
        totalStudents: filteredStudentsForMatching.value.length,
        statusKeaktifan: statusKeaktifanFilter.value,
        statusLabelText: statusKeaktifanLabelText.value,
        unitLabel,
        taLabel,
        registrationLabel,
        hasSubFilter: subParts.length > 0,
        subFilterLabel: subParts.join(' • '),
    };
});

function formatPercent(part, total) {
    if (!total || total <= 0 || !part || part <= 0) return 0;
    return Number(((part / total) * 100).toFixed(1));
}

// ============================================================
// Pencocokan & Klasifikasi Data Referensi vs Siswa Baru
// ============================================================
const matchedResult = computed(() => {
    return matchAndClassifySchools(
        referenceSchools.value,
        filteredStudentsForMatching.value,
        latestTaName.value
    );
});

const matchedWelfareResult = computed(() => {
    return matchAndClassifyVillages(
        referenceVillages.value,
        filteredStudentsForMatching.value,
        matchedResult.value.schools,
        latestTaName.value
    );
});

// Daftar Kecamatan dinamis berdasarkan Kabupaten terpilih
const availableKecamatans = computed(() => {
    const set = new Set();
    const source = isWelfareMode.value ? matchedWelfareResult.value.villages : matchedResult.value.schools;
    source.forEach((item) => {
        if (selectedKabupaten.value === 'Semua' || item.kabupaten === selectedKabupaten.value) {
            if (item.kecamatan) set.add(item.kecamatan);
        }
    });
    return Array.from(set).sort((a, b) => a.localeCompare(b, 'id'));
});

// Sekolah terfilter oleh Wilayah, Bentuk, & Status (sebelum Tab Tier & Search)
const regionFilteredSchools = computed(() => {
    return matchedResult.value.schools.filter((s) => {
        if (selectedKabupaten.value !== 'Semua' && s.kabupaten !== selectedKabupaten.value) return false;
        if (selectedKecamatan.value !== 'Semua' && s.kecamatan !== selectedKecamatan.value) return false;
        if (selectedBentuk.value !== 'Semua' && s.bentuk !== selectedBentuk.value) return false;
        if (selectedSchoolStatus.value !== 'Semua' && s.status !== selectedSchoolStatus.value) return false;
        return true;
    });
});

// Desa/Kelurahan terfilter oleh Wilayah (untuk mode Domisili & Kesejahteraan)
const regionFilteredVillages = computed(() => {
    return matchedWelfareResult.value.villages.filter((v) => {
        if (selectedKabupaten.value !== 'Semua' && v.kabupaten !== selectedKabupaten.value) return false;
        if (selectedKecamatan.value !== 'Semua' && v.kecamatan !== selectedKecamatan.value) return false;
        return true;
    });
});

// Hitung metrik KPI & tab
const kpiStats = computed(() => {
    const list = regionFilteredSchools.value;
    const total = list.length;
    let basis = 0;
    let potensial = 0;
    let vakum = 0;
    let zero = 0;
    let sd = 0;
    let mi = 0;
    let pkbm = 0;
    let matchedStudentsCount = 0;

    list.forEach((s) => {
        if (s.tier === 'basis_utama') basis++;
        else if (s.tier === 'potensial') potensial++;
        else if (s.tier === 'vakum') vakum++;
        else zero++;

        if (s.bentuk === 'SD') sd++;
        else if (s.bentuk === 'MI') mi++;
        else if (s.bentuk === 'PKBM') pkbm++;

        matchedStudentsCount += s.totalStudents;
    });

    const reached = basis + potensial + vakum;
    const reachedPct = total > 0 ? Number(((reached / total) * 100).toFixed(1)) : 0;
    const zeroPct = total > 0 ? Number(((zero / total) * 100).toFixed(1)) : 0;

    return {
        total,
        reached,
        reachedPct,
        basis,
        potensial,
        vakum,
        zero,
        zeroPct,
        sd,
        mi,
        pkbm,
        matchedStudentsCount,
    };
});

// Rekap penetrasi per 7 Kab/Kota Solo Raya (memperhatikan Bentuk & Status terpilih, agar mudah dibandingkan lintas Kab)
const kabupatenBreakdown = computed(() => {
    const map = new Map();
    SOLO_RAYA_KABUPATENS.forEach((kab) => {
        map.set(kab, { kabupaten: kab, total: 0, reached: 0, basis: 0, potensial: 0, vakum: 0, zero: 0, students: 0 });
    });

    matchedResult.value.schools.forEach((s) => {
        if (selectedBentuk.value !== 'Semua' && s.bentuk !== selectedBentuk.value) return;
        if (selectedSchoolStatus.value !== 'Semua' && s.status !== selectedSchoolStatus.value) return;

        const bucket = map.get(s.kabupaten);
        if (!bucket) return;
        bucket.total++;
        if (s.tier === 'basis_utama') bucket.basis++;
        else if (s.tier === 'potensial') bucket.potensial++;
        else if (s.tier === 'vakum') bucket.vakum++;
        else bucket.zero++;
        if (s.totalStudents > 0) {
            bucket.reached++;
            bucket.students += s.totalStudents;
        }
    });

    return Array.from(map.values()).map((b) => ({
        ...b,
        pct: b.total > 0 ? Number(((b.reached / b.total) * 100).toFixed(1)) : 0,
    }));
});

const welfareKpiStats = computed(() => {
    const list = regionFilteredVillages.value;
    const total = list.length;
    let reached = 0;
    let sejahteraBasis = 0;
    let sejahteraPotensial = 0;
    let rentanTerjangkau = 0;
    let rentanMinim = 0;
    let matchedStudentsCount = 0;
    let ring12Count = 0;

    list.forEach((v) => {
        if (v.totalStudents > 0) reached++;
        if (v.tier === 'sejahtera_basis') sejahteraBasis++;
        else if (v.tier === 'sejahtera_potensial') sejahteraPotensial++;
        else if (v.tier === 'rentan_terjangkau') rentanTerjangkau++;
        else rentanMinim++;
        matchedStudentsCount += v.totalStudents;
        if (Number(v.campus_ring || 4) <= 2) ring12Count++;
    });

    const reachedPct = total > 0 ? Number(((reached / total) * 100).toFixed(1)) : 0;
    const potensialPct = total > 0 ? Number(((sejahteraPotensial / total) * 100).toFixed(1)) : 0;

    return {
        total,
        reached,
        reachedPct,
        sejahteraBasis,
        sejahteraPotensial,
        potensialPct,
        rentanTerjangkau,
        rentanMinim,
        matchedStudentsCount,
        ring12Count,
    };
});

const welfareKabupatenBreakdown = computed(() => {
    const map = new Map();
    SOLO_RAYA_KABUPATENS.forEach((kab) => {
        map.set(kab, { kabupaten: kab, total: 0, reached: 0, students: 0, welfareSum: 0, campusSum: 0 });
    });

    matchedWelfareResult.value.villages.forEach((v) => {
        const bucket = map.get(v.kabupaten);
        if (!bucket) return;
        bucket.total++;
        bucket.welfareSum += Number(v.welfare_score || 0);
        bucket.campusSum += Number(v.campus_km || 0);
        if (v.totalStudents > 0) {
            bucket.reached++;
            bucket.students += v.totalStudents;
        }
    });

    return Array.from(map.values()).map((b) => ({
        ...b,
        pct: b.total > 0 ? Number(((b.reached / b.total) * 100).toFixed(1)) : 0,
        avgWelfare: b.total > 0 ? Number((b.welfareSum / b.total).toFixed(1)) : 0,
        avgCampusKm: b.total > 0 ? Number((b.campusSum / b.total).toFixed(1)) : 0,
    }));
});

// Daftar sekolah di tabel berdasarkan Tab Tier + Search + Sort
const displayedSchools = computed(() => {
    if (activeTierTab.value === 'unmatched') return [];

    let list = regionFilteredSchools.value;
    if (activeTierTab.value !== 'all') {
        list = list.filter((s) => s.tier === activeTierTab.value);
    }

    const q = searchQuery.value.trim().toLowerCase();
    if (q) {
        list = list.filter(
            (s) =>
                (s.nama || '').toLowerCase().includes(q) ||
                (s.npsn || '').toLowerCase().includes(q) ||
                (s.kecamatan || '').toLowerCase().includes(q) ||
                (s.desa || '').toLowerCase().includes(q) ||
                (s.alamat || '').toLowerCase().includes(q)
        );
    }

    const sorted = [...list];
    if (sortBy.value === 'students_desc') {
        sorted.sort((a, b) => b.totalStudents - a.totalStudents || b.taCount - a.taCount || a.nama.localeCompare(b.nama, 'id'));
    } else if (sortBy.value === 'ta_desc') {
        sorted.sort((a, b) => b.taCount - a.taCount || b.totalStudents - a.totalStudents || a.nama.localeCompare(b.nama, 'id'));
    } else if (sortBy.value === 'zero_first') {
        sorted.sort((a, b) => a.totalStudents - b.totalStudents || a.kabupaten.localeCompare(b.kabupaten, 'id') || a.nama.localeCompare(b.nama, 'id'));
    } else {
        sorted.sort((a, b) => a.nama.localeCompare(b.nama, 'id'));
    }

    return sorted;
});

const displayedUnmatched = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    let list = matchedResult.value.unmatchedGroups;
    if (q) {
        list = list.filter(
            (g) =>
                (g.name || '').toLowerCase().includes(q) ||
                (g.npsn || '').toLowerCase().includes(q) ||
                (g.kabupaten || '').toLowerCase().includes(q)
        );
    }
    return list;
});

const displayedVillages = computed(() => {
    if (activeTierTab.value === 'unmatched') return [];

    let list = regionFilteredVillages.value;
    if (activeTierTab.value !== 'all') {
        list = list.filter((v) => v.tier === activeTierTab.value);
    }

    const q = searchQuery.value.trim().toLowerCase();
    if (q) {
        list = list.filter(
            (v) =>
                (v.desa || '').toLowerCase().includes(q) ||
                (v.kecamatan || '').toLowerCase().includes(q) ||
                (v.kabupaten || '').toLowerCase().includes(q) ||
                (v.toll_name || '').toLowerCase().includes(q) ||
                (v.kode || '').toLowerCase().includes(q)
        );
    }

    const sorted = [...list];
    if (sortBy.value === 'campus_asc') {
        sorted.sort((a, b) => (a.campus_km ?? 999) - (b.campus_km ?? 999) || b.totalStudents - a.totalStudents || a.desa.localeCompare(b.desa, 'id'));
    } else if (sortBy.value === 'toll_asc') {
        sorted.sort((a, b) => (a.toll_km ?? 999) - (b.toll_km ?? 999) || (a.campus_km ?? 999) - (b.campus_km ?? 999) || a.desa.localeCompare(b.desa, 'id'));
    } else if (sortBy.value === 'zero_first') {
        sorted.sort((a, b) => a.totalStudents - b.totalStudents || (a.campus_km ?? 999) - (b.campus_km ?? 999) || a.desa.localeCompare(b.desa, 'id'));
    } else if (sortBy.value === 'name_asc') {
        sorted.sort((a, b) => a.desa.localeCompare(b.desa, 'id'));
    } else {
        sorted.sort((a, b) => b.totalStudents - a.totalStudents || (a.campus_km ?? 999) - (b.campus_km ?? 999) || a.desa.localeCompare(b.desa, 'id'));
    }

    return sorted;
});

const displayedUnmatchedVillages = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    let list = matchedWelfareResult.value.unmatchedGroups;
    if (q) {
        list = list.filter(
            (g) =>
                (g.desa || '').toLowerCase().includes(q) ||
                (g.kecamatan || '').toLowerCase().includes(q) ||
                (g.kabupaten || '').toLowerCase().includes(q)
        );
    }
    return list;
});

const totalTableItems = computed(() => {
    if (isWelfareMode.value) {
        return activeTierTab.value === 'unmatched'
            ? displayedUnmatchedVillages.value.length
            : displayedVillages.value.length;
    }
    return activeTierTab.value === 'unmatched'
        ? displayedUnmatched.value.length
        : displayedSchools.value.length;
});

const totalPages = computed(() => Math.max(1, Math.ceil(totalTableItems.value / perPage)));

const paginatedRows = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    if (isWelfareMode.value) {
        if (activeTierTab.value === 'unmatched') {
            return displayedUnmatchedVillages.value.slice(start, start + perPage);
        }
        return displayedVillages.value.slice(start, start + perPage);
    }
    if (activeTierTab.value === 'unmatched') {
        return displayedUnmatched.value.slice(start, start + perPage);
    }
    return displayedSchools.value.slice(start, start + perPage);
});

// ============================================================
// Aksi Interaktif (Klik Kartu, Titik Kuadran, Peta, Modal, Export)
// ============================================================
const tableSectionRef = ref(null);
const selectedSchoolModal = ref(null);
const selectedVillageModal = ref(null);
const unmatchedGroupModal = ref(null);
const quadrantGroupModal = ref(null);

function buildStudentGroupModalPayload(students, { mode, title, npsn = '-', kecamatan = '-', kabupaten = '-' }) {
    const list = [...(students || [])].sort((a, b) => (a.nama || '').localeCompare(b.nama || '', 'id'));
    const taBreakdownMap = {};
    list.forEach((st) => {
        const taKey = st.ta_masuk || (st.angkatan ? `Angk. ${st.angkatan}` : 'Lainnya');
        taBreakdownMap[taKey] = (taBreakdownMap[taKey] || 0) + 1;
    });
    const taBreakdown = Object.entries(taBreakdownMap).sort((a, b) => b[0].localeCompare(a[0]));
    const distinctTaKeys = Object.keys(taBreakdownMap).filter((k) => k !== 'Lainnya');
    const taCount = distinctTaKeys.length > 0 ? distinctTaKeys.length : (list.length > 0 ? 1 : 0);

    return {
        mode,
        title,
        npsn,
        kecamatan,
        kabupaten,
        totalStudents: list.length,
        taCount,
        taBreakdown,
        students: list,
    };
}

function openUnmatchedModal(grp) {
    if (!grp) return;
    unmatchedGroupModal.value = buildStudentGroupModalPayload(grp.students, {
        mode: isWelfareMode.value ? 'welfare' : 'school',
        title: isWelfareMode.value ? `Ds. ${grp.desa}` : grp.name,
        npsn: grp.npsn || '-',
        kecamatan: grp.kecamatan || '-',
        kabupaten: grp.kabupaten || '-',
    });
}

function openUnrecordedStudentsModal() {
    const list = isWelfareMode.value
        ? matchedWelfareResult.value.unrecordedStudents
        : matchedResult.value.unrecordedStudents;
    if (!list || list.length === 0) return;

    unmatchedGroupModal.value = buildStudentGroupModalPayload(list, {
        mode: isWelfareMode.value ? 'welfare' : 'school',
        title: isWelfareMode.value ? 'Siswa Tanpa Data Desa Domisili' : 'Siswa Tanpa Data Sekolah Asal',
        npsn: '-',
        kecamatan: '-',
        kabupaten: isWelfareMode.value
            ? 'Data desa/kelurahan domisili belum terisi pada profil siswa'
            : 'Data nama atau NPSN sekolah asal belum terisi pada profil siswa',
    });
}

function jumpToTier(tierKey) {
    activeTierTab.value = tierKey;
    if (tierKey === 'belum_tergarap' || tierKey === 'sejahtera_potensial') {
        sortBy.value = isWelfareMode.value ? 'zero_first' : 'name_asc';
    } else {
        sortBy.value = 'students_desc';
    }
    currentPage.value = 1;
    if (tableSectionRef.value) {
        tableSectionRef.value.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function handleQuadrantSelectSchools(payload) {
    if (!payload?.schools || payload.schools.length === 0) return;
    if (payload.schools.length === 1) {
        selectedSchoolModal.value = payload.schools[0];
    } else {
        quadrantGroupModal.value = { mode: 'school', ...payload };
    }
}

function handleQuadrantSelectVillages(payload) {
    if (!payload?.villages || payload.villages.length === 0) return;
    if (payload.villages.length === 1) {
        selectedVillageModal.value = payload.villages[0];
    } else {
        quadrantGroupModal.value = { mode: 'welfare', ...payload };
    }
}

async function handleSelectKecamatanFromMap(payload) {
    if (!payload?.kecamatan) return;
    if (payload.kabupaten && selectedKabupaten.value !== payload.kabupaten) {
        selectedKabupaten.value = payload.kabupaten;
        await nextTick();
    }
    selectedKecamatan.value = payload.kecamatan;
    if (tableSectionRef.value) {
        tableSectionRef.value.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function resetFilters() {
    activeUnit.value = hasSmpitStudents.value ? 'SMPIT' : 'all';
    selectedTa.value = 'Semua';
    statusKeaktifanFilter.value = 'all';
    registrationFilter.value = 'all';
    selectedKabupaten.value = 'Semua';
    selectedKecamatan.value = 'Semua';
    selectedBentuk.value = 'Semua';
    selectedSchoolStatus.value = 'Semua';
    activeTierTab.value = 'all';
    searchQuery.value = '';
    sortBy.value = 'students_desc';
    currentPage.value = 1;
}

const hasNonDefaultFilters = computed(() => {
    const defaultUnit = hasSmpitStudents.value ? 'SMPIT' : 'all';
    return (
        activeUnit.value !== defaultUnit ||
        selectedTa.value !== 'Semua' ||
        statusKeaktifanFilter.value !== 'all' ||
        registrationFilter.value !== 'all' ||
        selectedKabupaten.value !== 'Semua' ||
        selectedKecamatan.value !== 'Semua' ||
        selectedBentuk.value !== 'Semua' ||
        selectedSchoolStatus.value !== 'Semua' ||
        activeTierTab.value !== 'all' ||
        searchQuery.value.trim() !== ''
    );
});

function exportExcel() {
    const schoolsToExport = [...regionFilteredSchools.value].sort(
        (a, b) => b.totalStudents - a.totalStudents || b.taCount - a.taCount || a.nama.localeCompare(b.nama, 'id')
    );
    const villagesToExport = [...regionFilteredVillages.value].sort(
        (a, b) => b.totalStudents - a.totalStudents || (a.campus_km ?? 999) - (b.campus_km ?? 999) || a.desa.localeCompare(b.desa, 'id')
    );

    if (schoolsToExport.length === 0 && villagesToExport.length === 0) return;

    const kabSuffix = selectedKabupaten.value === 'Semua' ? 'SoloRaya_Grobogan' : selectedKabupaten.value;
    const unitSuffix = activeUnit.value === 'all' ? 'SemuaUnit' : activeUnit.value;

    const wb = XLSX.utils.book_new();

    if (schoolsToExport.length > 0) {
        const schoolSheetData = schoolsToExport.map((s, idx) => ({
            No: idx + 1,
            NPSN: s.npsn,
            'Nama Sekolah': s.nama,
            Bentuk: s.bentuk,
            Status: s.status,
            Alamat: s.alamat,
            'Desa/Kelurahan': s.desa,
            Kecamatan: s.kecamatan,
            Kabupaten: s.kabupaten,
            'Kategori Potensi': PSB_TIERS[s.tier]?.label || s.tier,
            'Jumlah TA Menyumbang': s.taCount,
            'Total Siswa Masuk': s.totalStudents,
            [`Siswa TA ${latestTaName.value || 'Terbaru'}`]: s.latestTaCount,
        }));
        const wsSchool = XLSX.utils.json_to_sheet(schoolSheetData);
        XLSX.utils.book_append_sheet(wb, wsSchool, 'Asal SD');
    }

    if (villagesToExport.length > 0) {
        const villageSheetData = villagesToExport.map((v, idx) => ({
            No: idx + 1,
            'Kode Wilayah': v.kode,
            'Desa / Kelurahan': v.desa,
            Kecamatan: v.kecamatan,
            Kabupaten: v.kabupaten,
            'Jarak ke Sekolah (km)': v.campus_km,
            'Ring Radius Sekolah': CAMPUS_RING_META[v.campus_ring]?.label || `Ring ${v.campus_ring}`,
            'Gerbang Tol Terdekat': v.toll_name,
            'Jarak Gerbang Tol (km)': v.toll_km,
            'Segmen Wilayah': PSB_WELFARE_TIERS[v.tier]?.label || v.tier,
            'Siswa Domisili Masuk': v.totalStudents,
            'Konsistensi (TA)': v.taCount,
            'Jumlah SD Asal di Desa': v.schoolsCount,
        }));
        const wsVillage = XLSX.utils.json_to_sheet(villageSheetData);
        XLSX.utils.book_append_sheet(wb, wsVillage, 'Domisili Desa');
    }

    XLSX.writeFile(wb, `PSB_Pemetaan_${kabSuffix}_${unitSuffix}.xlsx`);
}
</script>

<template>
    <div>
        <Head title="Pemetaan Potensi PSB" />

        <div ref="pageContainerRef" class="flex-grow max-w-container-max mx-auto w-full p-4 md:p-margin-desktop bg-white min-h-screen">
            <!-- Sticky Mobile Header (Muncul saat di-scroll) -->
            <div
                class="md:hidden fixed top-0 inset-x-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200 px-4 py-3 pt-[max(0.85rem,env(safe-area-inset-top))] flex items-center justify-between transition-all duration-200"
                :class="isScrolledPastTitle ? 'translate-y-0 opacity-100 shadow-2xs' : '-translate-y-full opacity-0 pointer-events-none'"
            >
                <div class="min-w-0">
                    <h2 class="text-base font-bold text-slate-900 tracking-tight truncate">Pemetaan Potensi PSB</h2>
                </div>
                <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-900 border border-emerald-200 text-[11px] font-bold shrink-0">
                    <template v-if="isWelfareMode">
                        {{ welfareKpiStats.reached }} / {{ welfareKpiStats.total.toLocaleString('id-ID') }} Desa
                    </template>
                    <template v-else>
                        {{ kpiStats.reached }} / {{ kpiStats.total.toLocaleString('id-ID') }}
                    </template>
                </span>
            </div>

            <!-- Page Header -->
            <div ref="mainTitleRef" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-3 sm:pt-4 md:pt-0 mb-5">
                <div>
                    <h1 class="text-2xl sm:text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Pemetaan Potensi PSB
                    </h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 text-[11px] font-semibold">
                            <span class="material-symbols-outlined text-[14px] text-emerald-700">verified</span>
                            <span>{{ isWelfareMode ? 'Sumber : Peta Wilayah Solo Raya & Grobogan • Radius Sekolah & Gerbang Tol' : 'Sumber : Pusdatin Kemendikdasmen' }}</span>
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap self-start sm:self-auto shrink-0">
                    <button
                        v-if="hasNonDefaultFilters"
                        type="button"
                        @click="resetFilters"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold shadow-2xs transition-all cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[16px]">restart_alt</span>
                        <span>Reset</span>
                    </button>
                    <button
                        type="button"
                        @click="exportExcel"
                        :disabled="isLoadingDataset || (regionFilteredSchools.length === 0 && regionFilteredVillages.length === 0)"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 disabled:opacity-50 text-yellow-200 text-xs font-bold shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                    >
                        <span class="material-symbols-outlined text-[17px]">download</span>
                        <span>Unduh Excel</span>
                    </button>
                </div>
            </div>

            <!-- Baris Filter Utama -->
            <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-5 shadow-xs mb-5 space-y-3.5">
                <!-- Baris 1: Unit Tujuan & Periode -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                    <!-- Unit Tabs (Pil Aktif Hijau Muda, Container Netral) -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mr-1">Unit Tujuan:</span>
                        <div class="bg-slate-100 p-1 rounded-full inline-flex items-center gap-0.5 border border-slate-200/90 overflow-x-auto max-w-full">
                            <button
                                v-for="u in unitOptions"
                                :key="u.id"
                                type="button"
                                @click="activeUnit = u.id"
                                :class="[
                                    'px-3 py-1 rounded-full text-xs font-bold transition-all cursor-pointer shrink-0 border',
                                    activeUnit === u.id
                                        ? 'bg-emerald-200 text-emerald-950 border-emerald-300 shadow-2xs'
                                        : 'border-transparent text-slate-600 hover:text-slate-900',
                                ]"
                            >
                                {{ u.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Filter TA, Status Keaktifan & Status Pendaftaran -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:flex md:flex-wrap items-center gap-2 sm:gap-2.5 w-full lg:w-auto">
                        <!-- Filter TA (Warna Hitam) -->
                        <div class="flex flex-col md:flex-row md:items-center gap-1 md:gap-1.5 min-w-0">
                            <label class="text-[10.5px] font-bold text-slate-900 uppercase tracking-wider shrink-0">TA Masuk</label>
                            <select
                                v-model="selectedTa"
                                class="w-full md:w-auto rounded-xl border border-slate-950 bg-slate-900 hover:bg-slate-800 text-white px-3 py-1.5 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-slate-500/30 cursor-pointer transition-colors"
                            >
                                <option value="Semua" class="bg-white text-slate-900">Semua TA</option>
                                <option v-for="ta in availableTaNames" :key="ta" :value="ta" class="bg-white text-slate-900">TA {{ ta }}</option>
                            </select>
                        </div>

                        <!-- Filter Status Keaktifan -->
                        <div class="flex flex-col md:flex-row md:items-center gap-1 md:gap-1.5 min-w-0">
                            <label class="text-[10.5px] font-bold text-slate-500 uppercase tracking-wider shrink-0">Keaktifan</label>
                            <select
                                v-model="statusKeaktifanFilter"
                                :class="[
                                    'w-full md:w-auto rounded-xl border px-3 py-1.5 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500/20 cursor-pointer transition-colors',
                                    statusKeaktifanFilter !== 'all'
                                        ? 'bg-emerald-100/90 border-emerald-400 text-emerald-950'
                                        : 'bg-slate-100 border-slate-200 text-slate-800 hover:bg-slate-200/70',
                                ]"
                            >
                                <option value="all" class="bg-white text-slate-900">Semua Status ({{ statusCounts.all }})</option>
                                <option value="aktif" class="bg-white text-slate-900">Siswa Aktif ({{ statusCounts.aktif }})</option>
                                <option value="lulus" class="bg-white text-slate-900">Lulus / Alumni ({{ statusCounts.lulus }})</option>
                                <option value="tidak_aktif" class="bg-white text-slate-900">Tidak Aktif ({{ statusCounts.tidak_aktif }})</option>
                            </select>
                        </div>

                        <!-- Mobile: Dropdown Semua Masuk sebaris di samping TA -->
                        <div class="flex md:hidden flex-col gap-1 min-w-0 col-span-2 sm:col-span-1">
                            <label class="text-[10.5px] font-bold text-slate-500 uppercase tracking-wider shrink-0">Status Masuk</label>
                            <select
                                v-model="registrationFilter"
                                :class="[
                                    'w-full rounded-xl border px-3 py-1.5 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500/20 cursor-pointer transition-colors',
                                    registrationFilter === 'mutasi'
                                        ? 'bg-amber-100/90 border-amber-300 text-amber-950'
                                        : 'bg-emerald-100/90 border-emerald-300 text-emerald-950',
                                ]"
                            >
                                <option value="all" class="bg-white text-slate-900">Semua Masuk</option>
                                <option value="baru" class="bg-white text-slate-900">Siswa Baru</option>
                                <option value="mutasi" class="bg-white text-slate-900">Mutasi</option>
                            </select>
                        </div>

                        <!-- Desktop: Tab Semua Masuk | Siswa Baru | Mutasi (Pil Semua Masuk Hijau Muda, Container Netral) -->
                        <div class="hidden md:inline-flex bg-slate-100 p-1 rounded-full items-center gap-0.5 border border-slate-200/90">
                            <button
                                v-for="opt in [
                                    { key: 'all', label: 'Semua Masuk', activeClass: 'bg-emerald-200 text-emerald-950 border-emerald-300 shadow-2xs' },
                                    { key: 'baru', label: 'Siswa Baru', activeClass: 'bg-emerald-600 text-white border-emerald-700 shadow-2xs' },
                                    { key: 'mutasi', label: 'Mutasi', activeClass: 'bg-amber-600 text-white border-amber-700 shadow-2xs' },
                                ]"
                                :key="opt.key"
                                type="button"
                                @click="registrationFilter = opt.key"
                                :class="[
                                    'px-3 py-1 rounded-full text-[11px] font-bold transition-all cursor-pointer border',
                                    registrationFilter === opt.key
                                        ? opt.activeClass
                                        : 'border-transparent text-slate-600 hover:text-slate-900',
                                ]"
                            >
                                {{ opt.label }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Baris 2: Filter Wilayah (Hijau) + Bentuk & Status Sekolah (Oranye) -->
                <div class="pt-3 border-t border-slate-100 grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <div>
                        <label class="block text-[10.5px] font-bold text-emerald-700 uppercase tracking-wider mb-1">Kabupaten / Kota</label>
                        <select
                            v-model="selectedKabupaten"
                            :class="[
                                'w-full rounded-xl border px-3 py-2 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500/20 cursor-pointer transition-colors',
                                selectedKabupaten !== 'Semua'
                                    ? 'bg-emerald-100/90 border-emerald-400 text-emerald-950'
                                    : 'bg-emerald-50/90 hover:bg-emerald-100/70 border-emerald-300 text-emerald-900',
                            ]"
                        >
                            <option value="Semua">Semua Solo Raya &amp; Grobogan (8 Kab/Kota)</option>
                            <option v-for="kab in SOLO_RAYA_KABUPATENS" :key="kab" :value="kab">{{ kab === 'Surakarta' ? 'Kota Surakarta' : `Kab. ${kab}` }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-bold text-emerald-700 uppercase tracking-wider mb-1">Kecamatan</label>
                        <select
                            v-model="selectedKecamatan"
                            :class="[
                                'w-full rounded-xl border px-3 py-2 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500/20 cursor-pointer transition-colors',
                                selectedKecamatan !== 'Semua'
                                    ? 'bg-emerald-100/90 border-emerald-400 text-emerald-950'
                                    : 'bg-emerald-50/90 hover:bg-emerald-100/70 border-emerald-300 text-emerald-900',
                            ]"
                        >
                            <option value="Semua">Semua Kecamatan ({{ availableKecamatans.length }})</option>
                            <option v-for="kec in availableKecamatans" :key="kec" :value="kec">{{ kec }}</option>
                        </select>
                    </div>

                    <div :class="{ 'opacity-50 pointer-events-none': isWelfareMode }">
                        <label class="block text-[10.5px] font-bold text-orange-700 uppercase tracking-wider mb-1">Bentuk Sekolah</label>
                        <select
                            v-model="selectedBentuk"
                            :disabled="isWelfareMode"
                            :class="[
                                'w-full rounded-xl border px-3 py-2 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-orange-500/20 cursor-pointer transition-colors',
                                selectedBentuk !== 'Semua'
                                    ? 'bg-orange-100/90 border-orange-400 text-orange-950'
                                    : 'bg-orange-50/90 hover:bg-orange-100/70 border-orange-300 text-orange-900',
                            ]"
                        >
                            <option value="Semua">Semua (SD, MI, PKBM)</option>
                            <option value="SD">SD Sederajat</option>
                            <option value="MI">MI (Madrasah Ibtidaiyah)</option>
                            <option value="PKBM">PKBM</option>
                        </select>
                    </div>

                    <div :class="{ 'opacity-50 pointer-events-none': isWelfareMode }">
                        <label class="block text-[10.5px] font-bold text-orange-700 uppercase tracking-wider mb-1">Status Sekolah</label>
                        <select
                            v-model="selectedSchoolStatus"
                            :disabled="isWelfareMode"
                            :class="[
                                'w-full rounded-xl border px-3 py-2 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-orange-500/20 cursor-pointer transition-colors',
                                selectedSchoolStatus !== 'Semua'
                                    ? 'bg-orange-100/90 border-orange-400 text-orange-950'
                                    : 'bg-orange-50/90 hover:bg-orange-100/70 border-orange-300 text-orange-900',
                            ]"
                        >
                            <option value="Semua">Semua Status (Negeri &amp; Swasta)</option>
                            <option value="Negeri">Negeri</option>
                            <option value="Swasta">Swasta</option>
                        </select>
                    </div>
                </div>

                <!-- Baris 3: Ringkasan Populasi Siswa yang Sedang Dibaca (Basis Persentase) -->
                <div class="pt-2.5 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 border border-slate-200 font-bold text-slate-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                            Membaca {{ filterContext.totalStudents.toLocaleString('id-ID') }} {{ filterContext.statusLabelText }}
                        </span>
                        <span>• {{ filterContext.unitLabel }} • {{ filterContext.taLabel }} • {{ filterContext.registrationLabel }}</span>
                    </div>
                    <span v-if="statusKeaktifanFilter === 'all'" class="text-[10.5px] text-slate-400 font-medium">
                        Rincian: {{ statusCounts.aktif }} Aktif • {{ statusCounts.lulus }} Lulus • {{ statusCounts.tidak_aktif }} Tidak Aktif
                    </span>
                </div>
            </div>

            <!-- Loading State / Error State -->
            <div v-if="isLoadingDataset" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5 animate-pulse">
                <div v-for="n in 4" :key="n" class="h-28 rounded-2xl bg-slate-100 border border-slate-200"></div>
            </div>

            <div v-else-if="datasetError" class="p-6 rounded-3xl bg-rose-50 border border-rose-200 text-rose-900 mb-5 flex items-center justify-between gap-4">
                <div>
                    <h3 class="font-bold text-sm">Gagal Memuat Dataset Sekolah Solo Raya</h3>
                    <p class="text-xs text-rose-700 mt-1">{{ datasetError }}</p>
                </div>
                <button
                    type="button"
                    @click="loadReferenceDataset"
                    class="px-4 py-2 rounded-xl bg-rose-700 text-white text-xs font-bold hover:bg-rose-800 cursor-pointer"
                >
                    Coba Lagi
                </button>
            </div>

            <template v-else>
                <!-- 4 KPI Summary Cards (Mode Domisili & Kesejahteraan) -->
                <div v-if="isWelfareMode" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4 mb-5">
                    <button
                        type="button"
                        @click="jumpToTier('all')"
                        class="text-left p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 hover:border-slate-300 shadow-2xs flex items-center gap-3.5 transition-all cursor-pointer"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-700 shrink-0">
                            <span class="material-symbols-outlined text-[24px]">holiday_village</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Desa &amp; Kelurahan</p>
                            <p class="text-2xl font-extrabold text-slate-900 leading-tight mt-0.5">{{ welfareKpiStats.total.toLocaleString('id-ID') }}</p>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5 truncate">
                                {{ welfareKpiStats.ring12Count.toLocaleString('id-ID') }} desa dalam radius ≤ 10 km
                            </p>
                        </div>
                    </button>

                    <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-100 border border-emerald-200 flex items-center justify-center text-emerald-900 shrink-0">
                            <span class="material-symbols-outlined text-[24px]">home_pin</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Desa Berdomisili Siswa</p>
                            <div class="flex items-baseline gap-2 mt-0.5">
                                <p class="text-2xl font-extrabold text-slate-900 leading-tight">{{ welfareKpiStats.reached.toLocaleString('id-ID') }}</p>
                                <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">{{ welfareKpiStats.reachedPct }}%</span>
                            </div>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5 truncate">
                                {{ welfareKpiStats.matchedStudentsCount.toLocaleString('id-ID') }} siswa berdomisili terpadankan
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="jumpToTier('sejahtera_basis')"
                        class="text-left p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 hover:border-emerald-300 shadow-2xs flex items-center gap-3.5 transition-all cursor-pointer"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-800 shrink-0">
                            <span class="material-symbols-outlined text-[24px]">workspace_premium</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Basis Dekat &amp; Luar</p>
                            <p class="text-2xl font-extrabold text-slate-900 leading-tight mt-0.5">{{ (welfareKpiStats.sejahteraBasis + welfareKpiStats.rentanTerjangkau).toLocaleString('id-ID') }}</p>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5 truncate">
                                {{ welfareKpiStats.sejahteraBasis }} Dekat (≤10km) • <span class="text-sky-700 font-semibold">{{ welfareKpiStats.rentanTerjangkau }} Luar (&gt;10km)</span>
                            </p>
                        </div>
                    </button>

                    <button
                        type="button"
                        @click="jumpToTier('sejahtera_potensial')"
                        class="text-left p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 hover:border-amber-300 shadow-2xs flex items-center gap-3.5 transition-all cursor-pointer group"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-amber-100 border border-amber-200 flex items-center justify-center text-amber-800 group-hover:bg-amber-200 transition-colors shrink-0">
                            <span class="material-symbols-outlined text-[24px]">travel_explore</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Target Dekat (≤10 km)</p>
                            <div class="flex items-baseline gap-2 mt-0.5">
                                <p class="text-2xl font-extrabold text-slate-900 leading-tight">{{ welfareKpiStats.sejahteraPotensial.toLocaleString('id-ID') }}</p>
                                <span class="text-xs font-bold text-amber-800 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">{{ welfareKpiStats.potensialPct }}%</span>
                            </div>
                            <p class="text-[11px] text-emerald-700 font-semibold mt-0.5 truncate group-hover:underline">
                                Klik untuk lihat target desa ≤ 10 km →
                            </p>
                        </div>
                    </button>
                </div>

                <!-- 4 KPI Summary Cards (Mode Asal SD/MI) -->
                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4 mb-5">
                    <!-- Card 1: Total Sekolah Referensi -->
                    <button
                        type="button"
                        @click="jumpToTier('all')"
                        class="text-left p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 hover:border-slate-300 shadow-2xs flex items-center gap-3.5 transition-all cursor-pointer"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-700 shrink-0">
                            <span class="material-symbols-outlined text-[24px]">account_balance</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Populasi Sekolah</p>
                            <p class="text-2xl font-extrabold text-slate-900 leading-tight mt-0.5">{{ kpiStats.total.toLocaleString('id-ID') }}</p>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5 truncate">
                                SD {{ kpiStats.sd.toLocaleString('id-ID') }} • MI {{ kpiStats.mi }} • PKBM {{ kpiStats.pkbm }}
                            </p>
                        </div>
                    </button>

                    <!-- Card 2: Sekolah Terjangkau (> 0) -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-100 border border-emerald-200 flex items-center justify-center text-emerald-900 shrink-0">
                            <span class="material-symbols-outlined text-[24px]">school</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Sekolah Terjangkau</p>
                            <div class="flex items-baseline gap-2 mt-0.5">
                                <p class="text-2xl font-extrabold text-slate-900 leading-tight">{{ kpiStats.reached.toLocaleString('id-ID') }}</p>
                                <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">{{ kpiStats.reachedPct }}%</span>
                            </div>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5 truncate">
                                Menyumbang {{ kpiStats.matchedStudentsCount.toLocaleString('id-ID') }} siswa terpadankan
                            </p>
                        </div>
                    </div>

                    <!-- Card 3: Basis Utama & Potensial -->
                    <button
                        type="button"
                        @click="jumpToTier('basis_utama')"
                        class="text-left p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 hover:border-emerald-300 shadow-2xs flex items-center gap-3.5 transition-all cursor-pointer"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-amber-100 border border-amber-200 flex items-center justify-center text-amber-900 shrink-0">
                            <span class="material-symbols-outlined text-[24px]">trending_up</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Basis &amp; Potensial</p>
                            <p class="text-2xl font-extrabold text-slate-900 leading-tight mt-0.5">{{ (kpiStats.basis + kpiStats.potensial).toLocaleString('id-ID') }}</p>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5 truncate">
                                {{ kpiStats.basis }} Basis • {{ kpiStats.potensial }} Potensial • <span class="text-rose-600 font-semibold">{{ kpiStats.vakum }} Vakum</span>
                            </p>
                        </div>
                    </button>

                    <!-- Card 4: Belum Tergarap (0) -->
                    <button
                        type="button"
                        @click="jumpToTier('belum_tergarap')"
                        class="text-left p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 hover:border-slate-400 shadow-2xs flex items-center gap-3.5 transition-all cursor-pointer group"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 border border-slate-300 flex items-center justify-center text-slate-600 group-hover:bg-slate-200 transition-colors shrink-0">
                            <span class="material-symbols-outlined text-[24px]">explore</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Belum Tergarap (0)</p>
                            <div class="flex items-baseline gap-2 mt-0.5">
                                <p class="text-2xl font-extrabold text-slate-900 leading-tight">{{ kpiStats.zero.toLocaleString('id-ID') }}</p>
                                <span class="text-xs font-bold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">{{ kpiStats.zeroPct }}%</span>
                            </div>
                            <p class="text-[11px] text-emerald-700 font-semibold mt-0.5 truncate group-hover:underline">
                                Klik untuk lihat daftar target →
                            </p>
                        </div>
                    </button>
                </div>

                <!-- Rekap Penetrasi 7 Kabupaten/Kota Solo Raya -->
                <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-5 shadow-xs mb-5">
                    <div class="flex items-center justify-between mb-3 pb-2.5 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                {{ isWelfareMode ? 'Jangkauan Domisili & Radius Sekolah per Kabupaten / Kota' : 'Jangkauan per Kabupaten / Kota (Solo Raya & Grobogan)' }}
                            </h3>
                        </div>
                        <span class="text-[11px] text-slate-400 font-medium hidden sm:inline">Klik kartu kabupaten untuk memfilter</span>
                    </div>

                    <div v-if="isWelfareMode" class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-4 xl:grid-cols-8 gap-2.5">
                        <button
                            v-for="kb in welfareKabupatenBreakdown"
                            :key="kb.kabupaten"
                            type="button"
                            @click="selectedKabupaten = selectedKabupaten === kb.kabupaten ? 'Semua' : kb.kabupaten"
                            :class="[
                                'text-left p-3 rounded-2xl border transition-all cursor-pointer flex flex-col justify-between gap-2',
                                selectedKabupaten === kb.kabupaten
                                    ? 'bg-emerald-50/80 border-emerald-500 ring-2 ring-emerald-500/20 shadow-2xs'
                                    : 'bg-slate-50/70 hover:bg-slate-100/80 border-slate-200/80',
                            ]"
                        >
                            <div>
                                <div class="text-xs font-bold text-slate-900 truncate">{{ kb.kabupaten }}</div>
                                <div class="text-[10.5px] text-slate-500 font-medium mt-0.5">
                                    <span class="font-extrabold text-emerald-800">{{ kb.reached }}</span> / {{ kb.total }} desa
                                </div>
                            </div>

                            <div>
                                <div class="w-full h-1.5 rounded-full bg-slate-200 overflow-hidden mb-1">
                                    <div
                                        class="h-full rounded-full bg-emerald-600 transition-all duration-500"
                                        :style="{ width: `${Math.max(kb.pct, kb.reached > 0 ? 4 : 0)}%` }"
                                    ></div>
                                </div>
                                <div class="flex items-center justify-between text-[10px] font-bold">
                                    <span class="text-sky-800">Avg {{ kb.avgCampusKm }} km</span>
                                    <span class="text-slate-500">{{ kb.students }} siswa</span>
                                </div>
                            </div>
                        </button>
                    </div>

                    <div v-else class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-4 xl:grid-cols-8 gap-2.5">
                        <button
                            v-for="kb in kabupatenBreakdown"
                            :key="kb.kabupaten"
                            type="button"
                            @click="selectedKabupaten = selectedKabupaten === kb.kabupaten ? 'Semua' : kb.kabupaten"
                            :class="[
                                'text-left p-3 rounded-2xl border transition-all cursor-pointer flex flex-col justify-between gap-2',
                                selectedKabupaten === kb.kabupaten
                                    ? 'bg-emerald-50/80 border-emerald-500 ring-2 ring-emerald-500/20 shadow-2xs'
                                    : 'bg-slate-50/70 hover:bg-slate-100/80 border-slate-200/80',
                            ]"
                        >
                            <div>
                                <div class="text-xs font-bold text-slate-900 truncate">{{ kb.kabupaten }}</div>
                                <div class="text-[10.5px] text-slate-500 font-medium mt-0.5">
                                    <span class="font-extrabold text-emerald-800">{{ kb.reached }}</span> / {{ kb.total }} sekolah
                                </div>
                            </div>

                            <div>
                                <div class="w-full h-1.5 rounded-full bg-slate-200 overflow-hidden mb-1">
                                    <div
                                        class="h-full rounded-full bg-emerald-600 transition-all duration-500"
                                        :style="{ width: `${Math.max(kb.pct, kb.reached > 0 ? 4 : 0)}%` }"
                                    ></div>
                                </div>
                                <div class="flex items-center justify-between text-[10px] font-bold">
                                    <span class="text-emerald-800">{{ kb.pct }}%</span>
                                    <span class="text-slate-500">{{ kb.students }} siswa</span>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Diagram 1: Matriks / Kuadran Potensi dengan Toggle Mode -->
                <div class="mb-5">
                    <PsbQuadrantChart
                        v-model:mode="mappingMode"
                        :schools="regionFilteredSchools"
                        :villages="regionFilteredVillages"
                        @select-tier="jumpToTier"
                        @select-schools="handleQuadrantSelectSchools"
                        @select-villages="handleQuadrantSelectVillages"
                    />
                </div>

                <!-- Diagram 2: Peta Wilayah Kecamatan -> Desa (2 Mode: Asal Sekolah vs Kesejahteraan Desa) -->
                <div class="mb-5">
                    <PsbSchoolClusterMap
                        v-model:mode="mappingMode"
                        :schools="regionFilteredSchools"
                        :villages="regionFilteredVillages"
                        :toll-gates="tollGates"
                        :selected-kabupaten="selectedKabupaten"
                        :selected-kecamatan="selectedKecamatan"
                        :total-filtered-students="filteredStudentsForMatching.length"
                        :filter-context="filterContext"
                        @select-school="selectedSchoolModal = $event"
                        @select-village="selectedVillageModal = $event"
                        @select-kecamatan="handleSelectKecamatanFromMap"
                        @reset-kecamatan="selectedKecamatan = 'Semua'"
                    />
                </div>

                <!-- Tabel & Kartu Sandingan Sekolah / Desa Kesejahteraan -->
                <div ref="tableSectionRef" class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-6 shadow-xs scroll-mt-16">
                    <!-- Header Tab Tiering -->
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
                        <div v-if="isWelfareMode" class="bg-slate-100 p-1 rounded-full inline-flex items-center gap-0.5 overflow-x-auto max-w-full border border-slate-200/90">
                            <button
                                v-for="tab in [
                                    { key: 'all', label: 'Semua Desa', count: welfareKpiStats.total, activeClass: 'bg-emerald-900 text-yellow-200 shadow-2xs' },
                                    { key: 'sejahtera_basis', label: 'Basis ≤10km', count: welfareKpiStats.sejahteraBasis, activeClass: PSB_WELFARE_TIERS.sejahtera_basis.activeTabClass },
                                    { key: 'sejahtera_potensial', label: 'Target ≤10km', count: welfareKpiStats.sejahteraPotensial, activeClass: PSB_WELFARE_TIERS.sejahtera_potensial.activeTabClass },
                                    { key: 'rentan_terjangkau', label: 'Basis >10km', count: welfareKpiStats.rentanTerjangkau, activeClass: PSB_WELFARE_TIERS.rentan_terjangkau.activeTabClass },
                                    { key: 'rentan_minim', label: 'Luar (0–2)', count: welfareKpiStats.rentanMinim, activeClass: PSB_WELFARE_TIERS.rentan_minim.activeTabClass },
                                    { key: 'unmatched', label: 'Luar / Belum Terdata', count: matchedWelfareResult.unmatchedGroups.length, activeClass: 'bg-slate-800 text-white shadow-2xs' },
                                ]"
                                :key="tab.key"
                                type="button"
                                @click="activeTierTab = tab.key"
                                :class="[
                                    'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer shrink-0',
                                    activeTierTab === tab.key ? tab.activeClass : 'text-slate-600 hover:text-slate-900',
                                ]"
                            >
                                <span>{{ tab.label }}</span>
                                <span class="text-[10.5px] opacity-80">({{ tab.count.toLocaleString('id-ID') }})</span>
                            </button>
                        </div>
                        <div v-else class="bg-slate-100 p-1 rounded-full inline-flex items-center gap-0.5 overflow-x-auto max-w-full border border-slate-200/90">
                            <button
                                v-for="tab in [
                                    { key: 'all', label: 'Semua', count: kpiStats.total, activeClass: 'bg-emerald-900 text-yellow-200 shadow-2xs' },
                                    { key: 'basis_utama', label: 'Basis Utama', count: kpiStats.basis, activeClass: PSB_TIERS.basis_utama.activeTabClass },
                                    { key: 'potensial', label: 'Potensial', count: kpiStats.potensial, activeClass: PSB_TIERS.potensial.activeTabClass },
                                    { key: 'vakum', label: 'Vakum', count: kpiStats.vakum, activeClass: PSB_TIERS.vakum.activeTabClass },
                                    { key: 'belum_tergarap', label: 'Belum Tergarap (0)', count: kpiStats.zero, activeClass: PSB_TIERS.belum_tergarap.activeTabClass },
                                    { key: 'unmatched', label: 'Luar / Belum Terdata', count: matchedResult.unmatchedGroups.length, activeClass: 'bg-slate-800 text-white shadow-2xs' },
                                ]"
                                :key="tab.key"
                                type="button"
                                @click="activeTierTab = tab.key"
                                :class="[
                                    'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer shrink-0',
                                    activeTierTab === tab.key ? tab.activeClass : 'text-slate-600 hover:text-slate-900',
                                ]"
                            >
                                <span>{{ tab.label }}</span>
                                <span class="text-[10.5px] opacity-80">({{ tab.count.toLocaleString('id-ID') }})</span>
                            </button>
                        </div>

                        <!-- Search & Sort -->
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="relative flex-1 sm:w-64">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[17px]">search</span>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    :placeholder="isWelfareMode ? 'Cari desa, kecamatan, kabupaten...' : 'Cari nama SD/MI, NPSN, desa...'"
                                    class="w-full pl-9 pr-8 py-1.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-emerald-600"
                                />
                                <button
                                    v-if="searchQuery"
                                    type="button"
                                    @click="searchQuery = ''"
                                    class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 text-[15px] cursor-pointer"
                                >
                                    close
                                </button>
                            </div>

                            <select
                                v-if="activeTierTab !== 'unmatched' && isWelfareMode"
                                v-model="sortBy"
                                class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-600 cursor-pointer"
                            >
                                <option value="students_desc">Urut: Siswa Domisili Terbanyak</option>
                                <option value="campus_asc">Urut: Terdekat ke Sekolah</option>
                                <option value="toll_asc">Urut: Terdekat Gerbang Tol</option>
                                <option value="zero_first">Urut: Siswa 0 / Minim Dulu</option>
                                <option value="name_asc">Urut: Nama Desa (A-Z)</option>
                            </select>
                            <select
                                v-else-if="activeTierTab !== 'unmatched'"
                                v-model="sortBy"
                                class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-600 cursor-pointer"
                            >
                                <option value="students_desc">Urut: Siswa Terbanyak</option>
                                <option value="ta_desc">Urut: Paling Konsisten (TA)</option>
                                <option value="name_asc">Urut: Nama Sekolah (A-Z)</option>
                                <option value="zero_first">Urut: Belum Tergarap (0) Dulu</option>
                            </select>
                        </div>
                    </div>

                    <!-- Info Banner saat di Tab Luar Wilayah / Belum Terdata -->
                    <div
                        v-if="activeTierTab === 'unmatched'"
                        class="mb-4 p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-600 flex flex-col sm:flex-row sm:items-center justify-between gap-2"
                    >
                        <span v-if="isWelfareMode">
                            Menampilkan desa/kelurahan domisili siswa yang berasal dari luar Solo Raya &amp; Kab. Grobogan atau penamaannya belum terpadankan dengan batas wilayah peta.
                        </span>
                        <span v-else>
                            Menampilkan nama sekolah asal siswa yang belum cocok dengan SD/MI/PKBM Solo Raya &amp; Kab. Grobogan (bisa berasal dari luar wilayah cakupan, TK/RA, atau perlu dilengkapi NPSN-nya).
                        </span>
                        <button
                            v-if="(isWelfareMode ? matchedWelfareResult.unrecordedStudents.length : matchedResult.unrecordedStudents.length) > 0"
                            type="button"
                            @click="openUnrecordedStudentsModal"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white hover:bg-emerald-50 border border-slate-300 hover:border-emerald-400 text-slate-800 hover:text-emerald-950 font-bold shrink-0 shadow-2xs transition-all cursor-pointer active:scale-95"
                            title="Klik untuk melihat daftar siswa"
                        >
                            <span>
                                {{ isWelfareMode ? 'Siswa tanpa data desa domisili:' : 'Siswa tanpa data sekolah asal:' }}
                                {{ isWelfareMode ? matchedWelfareResult.unrecordedStudents.length : matchedResult.unrecordedStudents.length }} siswa
                            </span>
                            <span class="text-emerald-700">→</span>
                        </button>
                        <span v-else class="font-bold text-slate-800 shrink-0">
                            {{ isWelfareMode ? 'Siswa tanpa data desa domisili: 0 siswa' : 'Siswa tanpa data sekolah asal: 0 siswa' }}
                        </span>
                    </div>

                    <!-- Empty State -->
                    <div v-if="paginatedRows.length === 0" class="py-12 text-center text-slate-400">
                        <span class="material-symbols-outlined text-4xl text-slate-300 mb-2 block">search_off</span>
                        <p class="text-sm font-bold text-slate-600">Tidak ada data {{ isWelfareMode ? 'desa' : 'sekolah' }} yang sesuai filter</p>
                        <p class="text-xs mt-1">Ubah filter wilayah atau kata kunci pencarian.</p>
                    </div>

                    <!-- Tab Unmatched Content -->
                    <template v-else-if="activeTierTab === 'unmatched'">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                            <button
                                v-for="grp in paginatedRows"
                                :key="grp.key"
                                type="button"
                                @click="openUnmatchedModal(grp)"
                                class="w-full text-left p-3.5 rounded-2xl border border-slate-200/90 bg-slate-50/70 hover:border-emerald-300 hover:bg-emerald-50/40 active:scale-[0.99] transition-all cursor-pointer flex items-center justify-between gap-3 group"
                            >
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-900 group-hover:text-emerald-950 break-words">{{ isWelfareMode ? `Ds. ${grp.desa}` : grp.name }}</p>
                                    <p class="text-[11px] text-slate-500 mt-0.5 truncate">
                                        <template v-if="isWelfareMode">
                                            {{ grp.kecamatan !== '-' ? `Kec. ${grp.kecamatan}, ` : '' }}{{ grp.kabupaten }}
                                        </template>
                                        <template v-else>
                                            NPSN: {{ grp.npsn }} • {{ grp.kecamatan !== '-' ? `${grp.kecamatan}, ` : '' }}{{ grp.kabupaten }}
                                        </template>
                                    </p>
                                </div>
                                <span class="px-2.5 py-1 rounded-full bg-slate-200 group-hover:bg-emerald-100 text-slate-800 group-hover:text-emerald-900 text-xs font-bold shrink-0 transition-colors">
                                    {{ grp.students.length }} Siswa →
                                </span>
                            </button>
                        </div>
                    </template>

                    <!-- Tab Desa & Kesejahteraan (Desktop Table + Mobile Cards) -->
                    <template v-else-if="isWelfareMode">
                        <!-- Mobile Cards -->
                        <div class="md:hidden space-y-2.5">
                            <div
                                v-for="v in paginatedRows"
                                :key="v.kode"
                                @click="selectedVillageModal = v"
                                class="p-3.5 rounded-2xl border border-slate-200/90 bg-slate-50/70 hover:bg-emerald-50/40 active:scale-[0.99] transition-all cursor-pointer space-y-2"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-900 leading-snug break-words">{{ v.desa }}</p>
                                        <p class="text-[11px] text-slate-500 mt-0.5">
                                            Kec. {{ v.kecamatan }}, {{ v.kabupaten }}
                                        </p>
                                    </div>
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border shrink-0',
                                            PSB_WELFARE_TIERS[v.tier].badgeClass,
                                        ]"
                                    >
                                        <span :class="['w-1.5 h-1.5 rounded-full', PSB_WELFARE_TIERS[v.tier].dotClass]"></span>
                                        <span>{{ PSB_WELFARE_TIERS[v.tier].shortLabel }}</span>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between pt-1.5 border-t border-slate-200/70 text-[11px] gap-1.5 flex-wrap">
                                    <div class="flex items-center gap-1 flex-wrap">
                                        <span class="px-1.5 py-0.5 rounded bg-sky-50 border border-sky-200 font-semibold text-sky-800">{{ v.campus_km ?? '-' }} km Sekolah</span>
                                        <span class="px-1.5 py-0.5 rounded bg-indigo-50 border border-indigo-200 font-semibold text-indigo-800">{{ v.toll_name }} ({{ v.toll_km ?? '-' }} km)</span>
                                    </div>
                                    <div class="font-bold text-slate-800 shrink-0">
                                        {{ v.totalStudents }} Siswa <span class="text-slate-400 font-normal">({{ v.taCount }} TA)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Table -->
                        <div class="hidden md:block overflow-x-auto rounded-2xl border border-slate-200/90">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 uppercase text-[10.5px] font-bold tracking-wider">
                                    <tr>
                                        <th class="py-3 px-3.5 w-12 text-center">No</th>
                                        <th class="py-3 px-3.5">Desa / Kelurahan</th>
                                        <th class="py-3 px-3.5 text-center w-36">Radius Sekolah</th>
                                        <th class="py-3 px-3.5 w-44">Gerbang Tol Terdekat</th>
                                        <th class="py-3 px-3.5 text-center w-24">SD di Desa</th>
                                        <th class="py-3 px-3.5 text-center w-28">Siswa Domisili</th>
                                        <th class="py-3 px-3.5 text-center w-36">Segmen</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <tr
                                        v-for="(v, idx) in paginatedRows"
                                        :key="v.kode"
                                        @click="selectedVillageModal = v"
                                        class="hover:bg-emerald-50/40 transition-colors cursor-pointer group"
                                    >
                                        <td class="py-3 px-3.5 text-center text-slate-400 font-medium">
                                            {{ (currentPage - 1) * perPage + idx + 1 }}
                                        </td>
                                        <td class="py-3 px-3.5">
                                            <div class="font-bold text-slate-900 group-hover:text-emerald-900">{{ v.desa }}</div>
                                            <div class="text-[11px] text-slate-500 mt-0.5">Kec. {{ v.kecamatan }}, {{ v.kabupaten }}</div>
                                        </td>
                                        <td class="py-3 px-3.5 text-center">
                                            <div class="font-bold text-slate-800">{{ v.campus_km ?? '-' }} km</div>
                                            <span
                                                :class="[
                                                    'inline-block mt-0.5 px-1.5 py-0.5 rounded text-[10px] font-bold border',
                                                    CAMPUS_RING_META[v.campus_ring]?.badgeClass || 'bg-slate-100 text-slate-600 border-slate-200',
                                                ]"
                                            >
                                                {{ CAMPUS_RING_META[v.campus_ring]?.label || 'Ring ' + v.campus_ring }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3.5">
                                            <div class="font-bold text-indigo-900">{{ v.toll_name || '-' }}</div>
                                            <div class="text-[11px] text-slate-500 font-medium">{{ v.toll_km ?? '-' }} km ke gerbang</div>
                                        </td>
                                        <td class="py-3 px-3.5 text-center font-semibold text-slate-600">
                                            {{ v.schoolsCount }} SD
                                        </td>
                                        <td class="py-3 px-3.5 text-center">
                                            <span
                                                :class="[
                                                    'font-extrabold text-sm',
                                                    v.totalStudents > 0 ? 'text-emerald-800' : 'text-slate-400',
                                                ]"
                                            >
                                                {{ v.totalStudents }}
                                            </span>
                                            <span v-if="latestTaName && v.latestTaCount > 0" class="block text-[10px] text-emerald-700 font-semibold">
                                                +{{ v.latestTaCount }} ({{ latestTaName }})
                                            </span>
                                        </td>
                                        <td class="py-3 px-3.5 text-center">
                                            <span
                                                :class="[
                                                    'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border',
                                                    PSB_WELFARE_TIERS[v.tier].badgeClass,
                                                ]"
                                            >
                                                <span :class="['w-1.5 h-1.5 rounded-full', PSB_WELFARE_TIERS[v.tier].dotClass]"></span>
                                                <span>{{ PSB_WELFARE_TIERS[v.tier].shortLabel }}</span>
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>

                    <!-- Tab Sekolah Solo Raya (Desktop Table + Mobile Cards) -->
                    <template v-else>
                        <!-- Mobile Cards -->
                        <div class="md:hidden space-y-2.5">
                            <div
                                v-for="school in paginatedRows"
                                :key="school.npsn"
                                @click="selectedSchoolModal = school"
                                class="p-3.5 rounded-2xl border border-slate-200/90 bg-slate-50/70 hover:bg-emerald-50/40 active:scale-[0.99] transition-all cursor-pointer space-y-2"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-900 leading-snug break-words">{{ school.nama }}</p>
                                        <p class="text-[11px] text-slate-500 mt-0.5">
                                            NPSN {{ school.npsn }} • {{ school.kecamatan }}, {{ school.kabupaten }}
                                        </p>
                                    </div>
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border shrink-0',
                                            PSB_TIERS[school.tier].badgeClass,
                                        ]"
                                    >
                                        <span :class="['w-1.5 h-1.5 rounded-full', PSB_TIERS[school.tier].dotClass]"></span>
                                        <span>{{ PSB_TIERS[school.tier].shortLabel }}</span>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between pt-1.5 border-t border-slate-200/70 text-[11px]">
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-1.5 py-0.5 rounded bg-white border border-slate-200 font-semibold text-slate-700">{{ school.bentuk }}</span>
                                        <span class="px-1.5 py-0.5 rounded bg-white border border-slate-200 font-semibold text-slate-600">{{ school.status }}</span>
                                    </div>
                                    <div class="font-bold text-slate-800">
                                        {{ school.totalStudents }} Siswa <span class="text-slate-400 font-normal">({{ school.taCount }} TA)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Table -->
                        <div class="hidden md:block overflow-x-auto rounded-2xl border border-slate-200/90">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 uppercase text-[10.5px] font-bold tracking-wider">
                                    <tr>
                                        <th class="py-3 px-3.5 w-12 text-center">No</th>
                                        <th class="py-3 px-3.5">Sekolah &amp; NPSN</th>
                                        <th class="py-3 px-3.5 text-center w-24">Bentuk</th>
                                        <th class="py-3 px-3.5 text-center w-24">Status</th>
                                        <th class="py-3 px-3.5">Wilayah (Desa / Kec / Kab)</th>
                                        <th class="py-3 px-3.5 text-center w-24">Konsistensi</th>
                                        <th class="py-3 px-3.5 text-center w-28">Siswa Masuk</th>
                                        <th class="py-3 px-3.5 text-center w-36">Tier Potensi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <tr
                                        v-for="(school, idx) in paginatedRows"
                                        :key="school.npsn"
                                        @click="selectedSchoolModal = school"
                                        class="hover:bg-emerald-50/40 transition-colors cursor-pointer group"
                                    >
                                        <td class="py-3 px-3.5 text-center text-slate-400 font-medium">
                                            {{ (currentPage - 1) * perPage + idx + 1 }}
                                        </td>
                                        <td class="py-3 px-3.5">
                                            <div class="font-bold text-slate-900 group-hover:text-emerald-900">{{ school.nama }}</div>
                                            <div class="text-[11px] font-mono text-slate-400 mt-0.5">NPSN: {{ school.npsn }}</div>
                                        </td>
                                        <td class="py-3 px-3.5 text-center">
                                            <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 border border-slate-200 font-semibold text-[11px]">
                                                {{ school.bentuk }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3.5 text-center">
                                            <span
                                                :class="[
                                                    'px-2 py-0.5 rounded-md font-semibold text-[11px] border',
                                                    school.status === 'Negeri'
                                                        ? 'bg-slate-100 text-slate-700 border-slate-300'
                                                        : 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                                ]"
                                            >
                                                {{ school.status }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3.5 text-slate-600">
                                            <div class="font-medium text-slate-800">Kec. {{ school.kecamatan }}, {{ school.kabupaten }}</div>
                                            <div class="text-[11px] text-slate-400 truncate max-w-[260px]" :title="school.alamat">
                                                {{ school.desa ? `Ds. ${school.desa} • ` : '' }}{{ school.alamat || '-' }}
                                            </div>
                                        </td>
                                        <td class="py-3 px-3.5 text-center font-bold text-slate-700">
                                            {{ school.taCount }} TA
                                        </td>
                                        <td class="py-3 px-3.5 text-center">
                                            <span
                                                :class="[
                                                    'font-extrabold text-sm',
                                                    school.totalStudents > 0 ? 'text-emerald-800' : 'text-slate-400',
                                                ]"
                                            >
                                                {{ school.totalStudents }}
                                            </span>
                                            <span v-if="latestTaName && school.latestTaCount > 0" class="block text-[10px] text-emerald-700 font-semibold">
                                                +{{ school.latestTaCount }} ({{ latestTaName }})
                                            </span>
                                        </td>
                                        <td class="py-3 px-3.5 text-center">
                                            <span
                                                :class="[
                                                    'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border',
                                                    PSB_TIERS[school.tier].badgeClass,
                                                ]"
                                            >
                                                <span :class="['w-1.5 h-1.5 rounded-full', PSB_TIERS[school.tier].dotClass]"></span>
                                                <span>{{ PSB_TIERS[school.tier].label }}</span>
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>

                    <!-- Paginasi -->
                    <div v-if="totalPages > 1" class="mt-4 pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                        <span class="text-slate-500 font-medium">
                            Menampilkan {{ ((currentPage - 1) * perPage + 1).toLocaleString('id-ID') }}–{{ Math.min(currentPage * perPage, totalTableItems).toLocaleString('id-ID') }} dari {{ totalTableItems.toLocaleString('id-ID') }} data
                        </span>
                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                :disabled="currentPage <= 1"
                                @click="currentPage--"
                                class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 font-semibold text-slate-700 cursor-pointer"
                            >
                                Sebelumnya
                            </button>
                            <span class="px-3 py-1.5 rounded-xl bg-emerald-900 text-yellow-200 font-bold">
                                {{ currentPage }} / {{ totalPages }}
                            </span>
                            <button
                                type="button"
                                :disabled="currentPage >= totalPages"
                                @click="currentPage++"
                                class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 font-semibold text-slate-700 cursor-pointer"
                            >
                                Berikutnya
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Modal Daftar Sekolah / Desa dari Titik Kuadran -->
        <Teleport to="body">
            <div
                v-if="quadrantGroupModal"
                @click.self="quadrantGroupModal = null"
                class="fixed inset-0 z-[999] bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
            >
                <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[85vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">{{ quadrantGroupModal.title }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                <template v-if="quadrantGroupModal.mode === 'welfare'">
                                    {{ (quadrantGroupModal.villages || []).length }} desa/kelurahan pada posisi ini — klik desa untuk rincian jarak Sekolah, Tol &amp; siswa
                                </template>
                                <template v-else>
                                    {{ (quadrantGroupModal.schools || []).length }} sekolah berada pada titik posisi ini — klik sekolah untuk rincian siswa
                                </template>
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="quadrantGroupModal = null"
                            class="w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-500 cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>
                    <div v-if="quadrantGroupModal.mode === 'welfare'" class="p-4 overflow-y-auto space-y-2">
                        <button
                            v-for="v in quadrantGroupModal.villages"
                            :key="v.kode"
                            type="button"
                            @click="selectedVillageModal = v; quadrantGroupModal = null"
                            class="w-full text-left p-3 rounded-2xl border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/40 transition-all flex items-center justify-between gap-3 cursor-pointer"
                        >
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-900">{{ v.desa }}</p>
                                <p class="text-[11px] text-slate-500 mt-0.5">Kec. {{ v.kecamatan }}, {{ v.kabupaten }} • Sekolah {{ v.campus_km ?? '-' }} km • {{ v.toll_name }} ({{ v.toll_km ?? '-' }} km)</p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-900 text-xs font-bold shrink-0">
                                {{ v.totalStudents }} Siswa →
                            </span>
                        </button>
                    </div>
                    <div v-else class="p-4 overflow-y-auto space-y-2">
                        <button
                            v-for="sc in quadrantGroupModal.schools"
                            :key="sc.npsn"
                            type="button"
                            @click="selectedSchoolModal = sc; quadrantGroupModal = null"
                            class="w-full text-left p-3 rounded-2xl border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/40 transition-all flex items-center justify-between gap-3 cursor-pointer"
                        >
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-900">{{ sc.nama }}</p>
                                <p class="text-[11px] text-slate-500 mt-0.5">NPSN {{ sc.npsn }} • Kec. {{ sc.kecamatan }}, {{ sc.kabupaten }}</p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-900 text-xs font-bold shrink-0">
                                {{ sc.totalStudents }} Siswa →
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Rincian Desa & Daftar Siswa Domisili -->
        <Teleport to="body">
            <div
                v-if="selectedVillageModal"
                @click.self="selectedVillageModal = null"
                class="fixed inset-0 z-[999] bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
            >
                <div class="bg-white rounded-3xl max-w-3xl w-full max-h-[88vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-start justify-between gap-3 bg-slate-50/60">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <span
                                    :class="[
                                        'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold border',
                                        PSB_WELFARE_TIERS[selectedVillageModal.tier].badgeClass,
                                    ]"
                                >
                                    <span :class="['w-1.5 h-1.5 rounded-full', PSB_WELFARE_TIERS[selectedVillageModal.tier].dotClass]"></span>
                                    <span>{{ PSB_WELFARE_TIERS[selectedVillageModal.tier].label }}</span>
                                </span>
                                <span class="text-[11px] font-mono text-slate-400">{{ selectedVillageModal.kode }}</span>
                            </div>
                            <h3 class="text-base font-extrabold text-slate-900 leading-snug">Desa / Kel. {{ selectedVillageModal.desa }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Kec. {{ selectedVillageModal.kecamatan }}, {{ selectedVillageModal.kabupaten }}
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="selectedVillageModal = null"
                            class="w-8 h-8 rounded-full hover:bg-slate-200/70 flex items-center justify-center text-slate-500 cursor-pointer shrink-0"
                        >
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <div class="p-5 overflow-y-auto space-y-4">
                        <!-- Ringkasan Indikator Wilayah (Radius Sekolah & Gerbang Tol) -->
                        <div class="grid grid-cols-2 gap-2.5">
                            <div class="p-3 rounded-2xl bg-sky-50 border border-sky-200 text-center">
                                <div class="text-[10px] font-bold text-sky-700 uppercase">Radius ke Sekolah</div>
                                <div class="text-base font-extrabold text-sky-950 mt-0.5">{{ selectedVillageModal.campus_km ?? '-' }} km</div>
                                <div class="text-[10.5px] font-semibold text-sky-700">{{ CAMPUS_RING_META[selectedVillageModal.campus_ring]?.label || 'Ring ' + selectedVillageModal.campus_ring }}</div>
                            </div>
                            <div class="p-3 rounded-2xl bg-indigo-50 border border-indigo-200 text-center">
                                <div class="text-[10px] font-bold text-indigo-700 uppercase">Gerbang Tol Terdekat</div>
                                <div class="text-base font-extrabold text-indigo-950 mt-0.5">{{ selectedVillageModal.toll_km ?? '-' }} km</div>
                                <div class="text-[10.5px] font-semibold text-indigo-700 truncate" :title="selectedVillageModal.toll_name">{{ selectedVillageModal.toll_name || '-' }}</div>
                            </div>
                        </div>

                        <!-- Ringkasan Siswa Domisili -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <div class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">Histori Siswa Berdomisili di Desa Ini</div>
                                <div class="text-sm font-extrabold text-slate-900 mt-0.5">
                                    {{ selectedVillageModal.totalStudents }} Siswa dari {{ selectedVillageModal.taCount }} Tahun Ajaran
                                    <span class="text-xs font-semibold text-emerald-700">
                                        ({{ formatPercent(selectedVillageModal.totalStudents, filterContext.totalStudents) }}% dari {{ filterContext.totalStudents.toLocaleString('id-ID') }} {{ filterContext.statusLabelText }})
                                    </span>
                                </div>
                                <div class="text-[10.5px] text-slate-400 font-medium mt-0.5">
                                    Filter aktif: {{ filterContext.unitLabel }} • {{ filterContext.taLabel }} • {{ filterContext.registrationLabel }}
                                </div>
                            </div>
                            <div v-if="selectedVillageModal.taBreakdown.length > 0" class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="[ta, cnt] in selectedVillageModal.taBreakdown"
                                    :key="ta"
                                    class="px-2.5 py-1 rounded-xl bg-white border border-emerald-200 text-emerald-900 text-xs font-bold shadow-2xs"
                                >
                                    {{ ta }}: {{ cnt }} siswa
                                </span>
                            </div>
                        </div>

                        <!-- Daftar Siswa Domisili -->
                        <div v-if="selectedVillageModal.students.length > 0" class="space-y-2">
                            <div class="text-xs font-bold text-slate-700 uppercase tracking-wider">Daftar Siswa Berdomisili</div>
                            <PsbStudentRowCard
                                v-for="(st, sIdx) in selectedVillageModal.students"
                                :key="st.id"
                                :student="st"
                                :index="sIdx"
                                mode="welfare"
                            />
                        </div>
                        <div v-else class="py-8 text-center rounded-2xl bg-slate-50 border border-dashed border-slate-300">
                            <span class="material-symbols-outlined text-3xl text-slate-300 mb-1 block">home_work</span>
                            <p class="text-xs font-bold text-slate-700">Belum ada siswa berdomisili dari desa ini (0 Siswa)</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                {{ (selectedVillageModal.campus_ring || 4) <= 2 ? 'Desa berada dalam radius <= 10 km dari sekolah dan berpotensi dijadikan target sosialisasi.' : 'Desa berada di luar radius 10 km dan belum memiliki siswa domisili.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Rincian Sekolah & Daftar Siswa Masuk -->
        <Teleport to="body">
            <div
                v-if="selectedSchoolModal"
                @click.self="selectedSchoolModal = null"
                class="fixed inset-0 z-[999] bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
            >
            <div class="bg-white rounded-3xl max-w-3xl w-full max-h-[88vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-start justify-between gap-3 bg-slate-50/60">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <span
                                :class="[
                                    'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold border',
                                    PSB_TIERS[selectedSchoolModal.tier].badgeClass,
                                ]"
                            >
                                <span :class="['w-1.5 h-1.5 rounded-full', PSB_TIERS[selectedSchoolModal.tier].dotClass]"></span>
                                <span>{{ PSB_TIERS[selectedSchoolModal.tier].label }}</span>
                            </span>
                            <span class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[11px] font-bold text-slate-700">{{ selectedSchoolModal.bentuk }}</span>
                            <span class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[11px] font-semibold text-slate-600">{{ selectedSchoolModal.status }}</span>
                            <span class="text-[11px] font-mono text-slate-400">NPSN {{ selectedSchoolModal.npsn }}</span>
                        </div>
                        <h3 class="text-base font-extrabold text-slate-900 leading-snug">{{ selectedSchoolModal.nama }}</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ selectedSchoolModal.alamat ? `${selectedSchoolModal.alamat}, ` : '' }}
                            {{ selectedSchoolModal.desa ? `Ds. ${selectedSchoolModal.desa}, ` : '' }}
                            Kec. {{ selectedSchoolModal.kecamatan }}, {{ selectedSchoolModal.kabupaten }}
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="selectedSchoolModal = null"
                        class="w-8 h-8 rounded-full hover:bg-slate-200/70 flex items-center justify-center text-slate-500 cursor-pointer shrink-0"
                    >
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <div class="p-5 overflow-y-auto space-y-4">
                    <!-- Ringkasan TA Masuk -->
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <div class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">Histori Kontribusi Siswa</div>
                            <div class="text-sm font-extrabold text-slate-900 mt-0.5">
                                {{ selectedSchoolModal.totalStudents }} Siswa dari {{ selectedSchoolModal.taCount }} Tahun Ajaran
                                <span class="text-xs font-semibold text-emerald-700">
                                    ({{ formatPercent(selectedSchoolModal.totalStudents, filterContext.totalStudents) }}% dari {{ filterContext.totalStudents.toLocaleString('id-ID') }} {{ filterContext.statusLabelText }})
                                </span>
                            </div>
                            <div class="text-[10.5px] text-slate-400 font-medium mt-0.5">
                                Filter aktif: {{ filterContext.unitLabel }} • {{ filterContext.taLabel }} • {{ filterContext.registrationLabel }}
                            </div>
                        </div>
                        <div v-if="selectedSchoolModal.taBreakdown.length > 0" class="flex flex-wrap gap-1.5">
                            <span
                                v-for="[ta, cnt] in selectedSchoolModal.taBreakdown"
                                :key="ta"
                                class="px-2.5 py-1 rounded-xl bg-white border border-emerald-200 text-emerald-900 text-xs font-bold shadow-2xs"
                            >
                                {{ ta }}: {{ cnt }} siswa
                            </span>
                        </div>
                    </div>

                    <!-- Daftar Siswa Asal Sekolah Tersebut -->
                    <div v-if="selectedSchoolModal.students.length > 0" class="space-y-2">
                        <div class="text-xs font-bold text-slate-700 uppercase tracking-wider">Daftar Siswa Masuk</div>
                        <PsbStudentRowCard
                            v-for="(st, sIdx) in selectedSchoolModal.students"
                            :key="st.id"
                            :student="st"
                            :index="sIdx"
                            mode="school"
                            :fallback-school-name="selectedSchoolModal.nama"
                        />
                    </div>

                    <div v-else class="py-8 text-center rounded-2xl bg-slate-50 border border-dashed border-slate-300">
                        <span class="material-symbols-outlined text-3xl text-slate-300 mb-1 block">person_search</span>
                        <p class="text-xs font-bold text-slate-700">Belum ada siswa masuk dari sekolah ini (0 Siswa)</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">Sekolah ini siap dijadikan sasaran kunjungan atau sosialisasi PSB.</p>
                    </div>
                </div>
            </div>
        </div>
        </Teleport>

        <!-- Modal Rincian Data Luar / Belum Terdata & Daftar Siswa -->
        <Teleport to="body">
            <div
                v-if="unmatchedGroupModal"
                @click.self="unmatchedGroupModal = null"
                class="fixed inset-0 z-[999] bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
            >
                <div class="bg-white rounded-3xl max-w-3xl w-full max-h-[88vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-start justify-between gap-3 bg-slate-50/60">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold border bg-slate-100 text-slate-700 border-slate-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                                    <span>Luar / Belum Terdata</span>
                                </span>
                                <span v-if="unmatchedGroupModal.mode === 'school' && unmatchedGroupModal.npsn && unmatchedGroupModal.npsn !== '-'" class="text-[11px] font-mono text-slate-400">
                                    NPSN {{ unmatchedGroupModal.npsn }}
                                </span>
                            </div>
                            <h3 class="text-base font-extrabold text-slate-900 leading-snug break-words">{{ unmatchedGroupModal.title }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ unmatchedGroupModal.kecamatan !== '-' ? `Kec. ${unmatchedGroupModal.kecamatan}, ` : '' }}{{ unmatchedGroupModal.kabupaten }}
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="unmatchedGroupModal = null"
                            class="w-8 h-8 rounded-full hover:bg-slate-200/70 flex items-center justify-center text-slate-500 cursor-pointer shrink-0"
                        >
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <div class="p-5 overflow-y-auto space-y-4">
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <div class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">
                                    {{ unmatchedGroupModal.mode === 'welfare' ? 'Histori Siswa Berdomisili' : 'Histori Kontribusi Siswa' }}
                                </div>
                                <div class="text-sm font-extrabold text-slate-900 mt-0.5">
                                    {{ unmatchedGroupModal.totalStudents }} Siswa dari {{ unmatchedGroupModal.taCount }} Tahun Ajaran
                                    <span class="text-xs font-semibold text-emerald-700">
                                        ({{ formatPercent(unmatchedGroupModal.totalStudents, filterContext.totalStudents) }}% dari {{ filterContext.totalStudents.toLocaleString('id-ID') }} {{ filterContext.statusLabelText }})
                                    </span>
                                </div>
                                <div class="text-[10.5px] text-slate-400 font-medium mt-0.5">
                                    Filter aktif: {{ filterContext.unitLabel }} • {{ filterContext.taLabel }} • {{ filterContext.registrationLabel }}
                                </div>
                            </div>
                            <div v-if="unmatchedGroupModal.taBreakdown.length > 0" class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="[ta, cnt] in unmatchedGroupModal.taBreakdown"
                                    :key="ta"
                                    class="px-2.5 py-1 rounded-xl bg-white border border-emerald-200 text-emerald-900 text-xs font-bold shadow-2xs"
                                >
                                    {{ ta }}: {{ cnt }} siswa
                                </span>
                            </div>
                        </div>

                        <div v-if="unmatchedGroupModal.students.length > 0" class="space-y-2">
                            <div class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                {{ unmatchedGroupModal.mode === 'welfare' ? 'Daftar Siswa Berdomisili' : 'Daftar Siswa Masuk' }}
                            </div>
                            <PsbStudentRowCard
                                v-for="(st, sIdx) in unmatchedGroupModal.students"
                                :key="st.id"
                                :student="st"
                                :index="sIdx"
                                :mode="unmatchedGroupModal.mode"
                                :fallback-school-name="unmatchedGroupModal.title"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

