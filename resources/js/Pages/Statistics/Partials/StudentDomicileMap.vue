<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import PsbStudentRowCard from '@/Pages/Psb/Partials/PsbStudentRowCard.vue';

// Perbesar toleransi geser Leaflet untuk layar sentuh/mobile
if (L.Draggable?.prototype?.options) {
    L.Draggable.prototype.options.clickTolerance = 25;
}

const props = defineProps({
    students: {
        type: Array,
        default: () => [],
    },
    academicYear: {
        type: String,
        default: '',
    },
    semester: {
        type: String,
        default: 'Semua',
    },
    unit: {
        type: String,
        default: 'Semua Unit',
    },
    isAllTime: {
        type: Boolean,
        default: false,
    },
});

const mapContainer = ref(null);
let map = null;
let geojsonLayer = null;
let desaGeojsonLayer = null;
let desaCanvasRenderer = null;
let desaLoadingPromise = null;
let kabupatenLayer = null;
let bordersSvgRenderer = null;
let areaLabelLayer = null;
let schoolMarker = null;
let activeHoverLayer = null;
let currentHoveredDesaKey = null;
let currentHoveredKecamatanKey = null;
let singleTooltip = null;
let isLockedByClick = false;
let lastInteractionTime = 0;

// Lokasi Kampus Sekolah (SMPIT & PPTQ Ulil Albab Karanganyar)
const SCHOOL_COORDS = [-7.4989289, 110.822498];
const SCHOOL_NAME = 'PPTQ & SMPIT TQ Ulil Albab Karanganyar';
const SCHOOL_ADDRESS = 'Sidorejo, Selokaton, Kec. Gondangrejo, Kab. Karanganyar';

// Level Agregasi: 'kecamatan' atau 'desa' & Drill-down Kecamatan
const aggregationLevel = ref('kecamatan');
const drillKecamatan = ref(null); // { kecamatan, kabupaten, kecKey, kabKey } | null
const isFullscreen = ref(false);

// Filter status keaktifan internal peta: 'all' | 'aktif' | 'lulus' | 'tidak_aktif'
const statusFilter = ref(props.isAllTime ? 'all' : 'aktif');

watch(() => props.isAllTime, (newVal) => {
    statusFilter.value = newVal ? 'all' : 'aktif';
});

function getStudentCategory(student) {
    if (!student) return 'tidak_aktif';
    const raw = (student.status_keaktifan || student.student_status || 'aktif').toLowerCase().trim();
    if (raw === 'lulus') return 'lulus';
    if (raw.includes('tidak') || raw === 'dropout' || raw === 'mutasi_keluar') return 'tidak_aktif';
    return 'aktif';
}

function matchesStatusFilter(student) {
    if (statusFilter.value === 'all') return true;
    return getStudentCategory(student) === statusFilter.value;
}

const statusCounts = computed(() => {
    const list = props.students || [];
    let aktif = 0;
    let lulus = 0;
    let tidak_aktif = 0;
    list.forEach(s => {
        const cat = getStudentCategory(s);
        if (cat === 'aktif') aktif++;
        else if (cat === 'lulus') lulus++;
        else tidak_aktif++;
    });
    return {
        all: list.length,
        aktif,
        lulus,
        tidak_aktif,
    };
});

const statusLabelText = computed(() => {
    if (statusFilter.value === 'all') return 'seluruh siswa (Aktif, Lulus & Tidak Aktif)';
    if (statusFilter.value === 'lulus') return 'siswa lulus (alumni)';
    if (statusFilter.value === 'tidak_aktif') return 'siswa tidak aktif';
    return 'siswa aktif';
});

// Switcher mode tampilan: 'peta' atau 'bar'
const activeView = ref('peta');
const barSearchQuery = ref('');
const barFilterLimit = ref('15'); // '10' | '15' | '25' | 'all'

const geojsonData = ref(null);
const desaGeojsonData = ref(null);
const isLoading = ref(true);
const isDesaLoading = ref(false);
const loadError = ref(null);

const hoveredFeature = ref(null);
const hoveredDesa = ref(null);
const showOutsideModal = ref(false);
const isLegendOpenMobile = ref(false);

function isMobileScreen() {
    return typeof window !== 'undefined' && window.innerWidth < 640;
}

// State Modal Pop-up Rincian Siswa
const selectedKecamatanModal = ref(null);
const modalSearch = ref('');

// Helper normalisasi string umum (robust terhadap awalan Kec., Kab., Kota, Ds., Kel., spasi, tanda baca)
function normalizeKey(str) {
    if (!str) return '';
    let s = String(str).trim();
    let prev = '';
    while (s !== prev) {
        prev = s;
        s = s.replace(/^(kec(amatan)?|kab(upaten)?|kota(madya)?|desa|kel(urahan)?|ds)\.?(?=\s|\/|\.)\s*(?:\/\s*)?/i, '').trim();
    }
    s = s.replace(/[^a-zA-Z0-9]/g, '').toLowerCase();
    if (s === 'solo' || s === 'kotasolo') {
        s = 'surakarta';
    }
    return s;
}

// Helper perapian nama desa (menghilangkan awalan "Desa", "Ds.", "Kelurahan", "Desa/Kel.", dsb)
function cleanDesaName(raw) {
    if (!raw) return '';
    let s = String(raw).trim();
    let prev = '';
    while (s !== prev) {
        prev = s;
        s = s.replace(/^(desa|kel(urahan)?|ds)\.?(?=\s|\/|\.)\s*(?:\/\s*)?/i, '').trim();
    }
    s = s.replace(/[^a-zA-Z0-9\s]/g, ' ').replace(/\s+/g, ' ').trim();
    if (!s) return '';
    return s.split(/\s+/).map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()).join(' ').trim();
}

// Map normalized kecamatan to feature properties for disambiguation
const soloRayaKecMap = computed(() => {
    if (!geojsonData.value?.features) return {};
    const mapObj = {};
    geojsonData.value.features.forEach(f => {
        const kec = f.properties.kecamatan;
        const kab = f.properties.kabupaten;
        const key = normalizeKey(kec);
        if (!mapObj[key]) {
            mapObj[key] = [];
        }
        mapObj[key].push({
            kecamatan: kec,
            kabupaten: kab,
            kode: f.properties.kode,
        });
    });
    return mapObj;
});

// Helper pencocokan kecamatan yang fleksibel dan toleran terhadap variasi penulisan
function findMatchedKecamatan(rawKec, rawKab) {
    if (!rawKec || !geojsonData.value?.features) return null;
    const normKec = normalizeKey(rawKec);
    const normKab = normalizeKey(rawKab);

    if (!normKec) return null;

    // 1. Pencocokan langsung dengan key normalisasi
    let candidates = soloRayaKecMap.value[normKec];

    // 2. Fallback pencocokan parsial jika key langsung tidak ditemukan
    if (!candidates || candidates.length === 0) {
        const allKecKeys = Object.keys(soloRayaKecMap.value);
        const matches = allKecKeys.filter(k => k.length >= 4 && (normKec.includes(k) || (normKec.length >= 4 && k.includes(normKec))));
        if (matches.length > 0) {
            matches.sort((a, b) => b.length - a.length);
            candidates = soloRayaKecMap.value[matches[0]];
        }
    }

    if (!candidates || candidates.length === 0) return null;

    // 3. Disambiguasi kabupaten jika ada lebih dari 1 kandidat (misal Karanganyar)
    let matched = candidates[0];
    if (candidates.length > 1 && normKab) {
        const found = candidates.find(c => {
            const cKab = normalizeKey(c.kabupaten);
            return cKab === normKab || normKab.includes(cKab) || cKab.includes(normKab);
        });
        if (found) matched = found;
    }

    return matched;
}

// Agregasi sebaran siswa per kecamatan di Solo Raya & Grobogan serta Luar Wilayah
const distribution = computed(() => {
    const list = props.students || [];
    const countsByKey = {}; // featureKey -> count
    const studentsByKey = {}; // featureKey -> [student, ...]
    const outsideMap = {};  // regency / text -> { count, students }
    let outsideTotal = 0;
    let coverageTotal = 0;
    let activeTotal = 0;

    list.forEach(s => {
        if (!matchesStatusFilter(s)) return;
        activeTotal++;

        const rawKec = s.kecamatan || '';
        const rawKab = s.kabupaten || '';

        if (!rawKec) {
            outsideTotal++;
            const groupName = rawKab || 'Alamat Belum Terdata';
            if (!outsideMap[groupName]) {
                outsideMap[groupName] = { count: 0, students: [] };
            }
            outsideMap[groupName].count++;
            outsideMap[groupName].students.push(s);
            return;
        }

        const matched = findMatchedKecamatan(rawKec, rawKab);
        if (matched) {
            const featureKey = `${normalizeKey(matched.kecamatan)}_${normalizeKey(matched.kabupaten)}`;
            countsByKey[featureKey] = (countsByKey[featureKey] || 0) + 1;
            if (!studentsByKey[featureKey]) {
                studentsByKey[featureKey] = [];
            }
            studentsByKey[featureKey].push(s);
            coverageTotal++;
        } else {
            outsideTotal++;
            const groupName = rawKab ? `${rawKab} (Kec. ${rawKec || '-'})` : (rawKec || 'Luar Wilayah');
            if (!outsideMap[groupName]) {
                outsideMap[groupName] = { count: 0, students: [] };
            }
            outsideMap[groupName].count++;
            outsideMap[groupName].students.push(s);
        }
    });

    const outsideList = Object.entries(outsideMap)
        .map(([region, val]) => ({ region, count: val.count, students: val.students }))
        .sort((a, b) => b.count - a.count);

    return {
        countsByKey,
        studentsByKey,
        outsideList,
        outsideTotal,
        coverageTotal,
        soloRayaTotal: coverageTotal,
        activeTotal,
    };
});

// Agregasi sebaran siswa per desa/kelurahan
const desaDistribution = computed(() => {
    const list = props.students || [];
    const countsByKey = {}; // featureKey -> count
    const studentsByKey = {}; // featureKey -> [student, ...]
    const desasByKey = {};
    let totalCovered = 0;

    list.forEach(s => {
        if (!matchesStatusFilter(s)) return;

        const rawKec = s.kecamatan || '';
        const rawKab = s.kabupaten || '';

        const matchedKec = findMatchedKecamatan(rawKec, rawKab);
        if (!matchedKec) return;

        const rawDesa = cleanDesaName(s.desa) || '(Belum Terdata)';
        const dKey = normalizeKey(rawDesa);
        const kecKey = normalizeKey(matchedKec.kecamatan);
        const kabKey = normalizeKey(matchedKec.kabupaten);
        const featureKey = `${dKey}_${kecKey}_${kabKey}`;

        countsByKey[featureKey] = (countsByKey[featureKey] || 0) + 1;
        if (!studentsByKey[featureKey]) {
            studentsByKey[featureKey] = [];
        }
        studentsByKey[featureKey].push(s);

        if (!desasByKey[featureKey]) {
            desasByKey[featureKey] = {
                key: featureKey,
                desa: rawDesa,
                kecamatan: matchedKec.kecamatan,
                kabupaten: matchedKec.kabupaten,
                count: 0,
                students: [],
            };
        }

        desasByKey[featureKey].count++;
        desasByKey[featureKey].students.push(s);
        totalCovered++;
    });

    const activeTotal = distribution.value.activeTotal || 1;
    const desaList = Object.values(desasByKey).map(d => ({
        ...d,
        percentage: Math.round((d.count / activeTotal) * 100),
    })).sort((a, b) => b.count - a.count);

    return {
        countsByKey,
        studentsByKey,
        desasByKey,
        desaList,
        totalCovered,
        totalDesaCount: desaList.length,
    };
});

// Daftar kecamatan untuk Bar Chart
const barKecamatans = computed(() => {
    if (!geojsonData.value?.features) return [];
    const list = [];
    const seen = new Set();
    geojsonData.value.features.forEach(f => {
        const key = `${normalizeKey(f.properties.kecamatan)}_${normalizeKey(f.properties.kabupaten)}`;
        if (seen.has(key)) return;
        seen.add(key);

        const count = distribution.value.countsByKey[key] || 0;
        if (count > 0) {
            list.push({
                key,
                kecamatan: f.properties.kecamatan,
                kabupaten: f.properties.kabupaten,
                count,
                percentage: distribution.value.activeTotal > 0
                    ? Math.round((count / distribution.value.activeTotal) * 100)
                    : 0,
                students: distribution.value.studentsByKey[key] || [],
            });
        }
    });
    return list.sort((a, b) => b.count - a.count);
});

const displayedKecamatans = computed(() => {
    let list = barKecamatans.value;
    const q = barSearchQuery.value.trim().toLowerCase();
    if (q) {
        list = list.filter(item =>
            item.kecamatan.toLowerCase().includes(q) ||
            item.kabupaten.toLowerCase().includes(q)
        );
    } else if (barFilterLimit.value !== 'all') {
        list = list.slice(0, Number(barFilterLimit.value));
    }
    return list;
});

const maxBarCount = computed(() => {
    return displayedKecamatans.value[0]?.count || 1;
});

// Daftar Desa untuk Bar Chart
const displayedDesas = computed(() => {
    let list = desaDistribution.value.desaList;
    const q = barSearchQuery.value.trim().toLowerCase();
    if (q) {
        list = list.filter(item =>
            item.desa.toLowerCase().includes(q) ||
            item.kecamatan.toLowerCase().includes(q) ||
            item.kabupaten.toLowerCase().includes(q)
        );
    } else if (barFilterLimit.value !== 'all') {
        list = list.slice(0, Number(barFilterLimit.value));
    }
    return list;
});

const maxDesaBarCount = computed(() => {
    return displayedDesas.value[0]?.count || 1;
});

function openKecamatanModal(item) {
    selectedKecamatanModal.value = item;
    modalSearch.value = '';
}

function closeKecamatanModal() {
    selectedKecamatanModal.value = null;
    modalSearch.value = '';
}

function openFeatureModal(feature) {
    if (!feature) return;
    const key = `${normalizeKey(feature.kecamatan)}_${normalizeKey(feature.kabupaten)}`;
    const students = feature.students || distribution.value.studentsByKey[key] || [];
    openKecamatanModal({
        kecamatan: feature.kecamatan,
        kabupaten: feature.kabupaten,
        count: feature.count,
        percentage: feature.percentage,
        students,
    });
}

function openDesaFeatureModal(feature) {
    if (!feature) return;
    const dKey = normalizeKey(feature.properties.desa);
    const kecKey = normalizeKey(feature.properties.kecamatan);
    const kabKey = normalizeKey(feature.properties.kabupaten);
    const key = `${dKey}_${kecKey}_${kabKey}`;
    const count = desaDistribution.value.countsByKey[key] || 0;
    const pct = distribution.value.activeTotal > 0
        ? Math.round((count / distribution.value.activeTotal) * 100)
        : 0;
    const students = desaDistribution.value.studentsByKey[key] || [];

    openKecamatanModal({
        desa: feature.properties.desa,
        kecamatan: feature.properties.kecamatan,
        kabupaten: feature.properties.kabupaten,
        count,
        percentage: pct,
        students,
    });
}

function openDesaModal(item) {
    if (!item) return;
    openKecamatanModal({
        desa: item.desa,
        kecamatan: item.kecamatan,
        kabupaten: item.kabupaten,
        count: item.count,
        percentage: item.percentage,
        students: item.students || [],
    });
}

async function toggleFullscreen(forceVal = null) {
    const nextVal = typeof forceVal === 'boolean' ? forceVal : !isFullscreen.value;
    if (isFullscreen.value === nextVal) return;
    isFullscreen.value = nextVal;
    if (typeof document !== 'undefined') {
        document.body.style.overflow = nextVal ? 'hidden' : '';
    }
    await nextTick();
    if (map) {
        map.invalidateSize({ animate: false });
        renderMapLabels();
    }
}

function handleKeyDown(e) {
    if (e.key === 'Escape') {
        if (selectedKecamatanModal.value) {
            closeKecamatanModal();
        } else if (showOutsideModal.value) {
            showOutsideModal.value = false;
        } else if (isFullscreen.value) {
            toggleFullscreen(false);
        }
    }
}

function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// Siswa yang difilter di dalam modal pencarian pop-up
const modalFilteredStudents = computed(() => {
    if (!selectedKecamatanModal.value) return [];
    const q = modalSearch.value.trim().toLowerCase();
    const students = selectedKecamatanModal.value.students || [];
    if (!q) return students;

    return students.filter(s => {
        const nama = (s.nama || '').toLowerCase();
        const nisn = (s.nisn || '').toLowerCase();
        const dusun = (s.dusun || '').toLowerCase();
        const desa = (s.desa || '').toLowerCase();
        const kelas = (s.kelas || '').toLowerCase();
        return nama.includes(q) || nisn.includes(q) || dusun.includes(q) || desa.includes(q) || kelas.includes(q);
    });
});

// Skala warna Choropleth Emerald
function getColor(count) {
    if (count >= 20) return '#064e3b'; // Sangat padat (>20)
    if (count >= 10) return '#047857'; // Padat (10-19)
    if (count >= 5)  return '#10b981'; // Sedang (5-9)
    if (count >= 1)  return '#6ee7b7'; // Sedikit (1-4)
    return '#ffffff'; // 0 siswa
}

function getFeatureCount(feature) {
    const key = `${normalizeKey(feature.properties.kecamatan)}_${normalizeKey(feature.properties.kabupaten)}`;
    return distribution.value.countsByKey[key] || 0;
}

function featureStyle(feature) {
    const count = getFeatureCount(feature);
    return {
        fill: true,
        fillColor: getColor(count),
        weight: 0.8,
        opacity: 0.8,
        color: '#94a3b8',
        dashArray: null,
        fillOpacity: count > 0 ? 0.92 : 0.6,
    };
}

function getDesaFeatureCount(feature) {
    const dKey = normalizeKey(feature.properties.desa);
    const kecKey = normalizeKey(feature.properties.kecamatan);
    const kabKey = normalizeKey(feature.properties.kabupaten);
    const key = `${dKey}_${kecKey}_${kabKey}`;
    return desaDistribution.value.countsByKey[key] || 0;
}

function desaFeatureStyle(feature) {
    const count = getDesaFeatureCount(feature);
    return {
        fill: true,
        fillColor: getColor(count),
        weight: 0.5,
        opacity: 0.85,
        color: count > 0 ? '#059669' : '#cbd5e1',
        dashArray: null,
        fillOpacity: count > 0 ? 0.92 : 0.45,
    };
}

function selectFeature(feature, layer, latlng = null, isClick = false) {
    const kecKey = normalizeKey(feature.properties.kecamatan);
    const kabKey = normalizeKey(feature.properties.kabupaten);
    const key = `${kecKey}_${kabKey}`;

    if (currentHoveredKecamatanKey === key) {
        if (isClick) {
            if (singleTooltip && map && map.hasLayer(singleTooltip)) {
                map.removeLayer(singleTooltip);
            }
        } else if (!isMobileScreen() && latlng && singleTooltip && map && map.hasLayer(singleTooltip)) {
            singleTooltip.setLatLng(latlng);
        }
        return;
    }
    currentHoveredKecamatanKey = key;

    hoveredDesa.value = null;
    if (activeHoverLayer && activeHoverLayer !== layer) {
        if (activeHoverLayer.feature) {
            activeHoverLayer.setStyle(featureStyle(activeHoverLayer.feature));
        }
    }
    activeHoverLayer = layer;

    layer.setStyle({
        fill: true,
        weight: 2.5,
        color: '#047857',
        fillOpacity: 1,
    });

    if (layer.bringToFront) {
        layer.bringToFront();
    }

    const count = getFeatureCount(feature);
    const pct = distribution.value.activeTotal > 0
        ? Math.round((count / distribution.value.activeTotal) * 100)
        : 0;

    const students = distribution.value.studentsByKey[key] || [];

    hoveredFeature.value = {
        kecamatan: feature.properties.kecamatan,
        kabupaten: feature.properties.kabupaten,
        count,
        percentage: pct,
        students,
        featureLayer: layer,
    };

    if (isClick) {
        if (singleTooltip && map && map.hasLayer(singleTooltip)) {
            map.removeLayer(singleTooltip);
        }
        return;
    }

    // Pada mobile, jangan tampilkan tooltip pop-up di atas poligon wilayah karena sudah ada kartu rincian di bawah peta (agar tidak dobel)
    if (!isMobileScreen() && singleTooltip && map) {
        const pos = latlng || layer.getBounds().getCenter();
        singleTooltip
            .setLatLng(pos)
            .setContent(`
                <div class="px-2.5 py-1.5 text-xs text-slate-800 leading-tight">
                    <div class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider mb-0.5">Kecamatan</div>
                    <div class="font-extrabold text-slate-900 text-[13px]">Kec. ${feature.properties.kecamatan}</div>
                    <div class="text-[11px] text-slate-500 font-medium">Kab/Kota ${feature.properties.kabupaten}</div>
                    <div class="mt-1 pt-1 border-t border-slate-200/80 flex items-center justify-between gap-3">
                        <span class="font-semibold text-emerald-800">${count} Siswa</span>
                        <span class="text-[11px] text-slate-400">(${pct}%)</span>
                    </div>
                </div>
            `);
        if (!map.hasLayer(singleTooltip)) {
            singleTooltip.addTo(map);
        }
    } else if (singleTooltip && map && map.hasLayer(singleTooltip)) {
        map.removeLayer(singleTooltip);
    }
}

function selectDesaFeature(feature, layer, latlng = null, isClick = false) {
    const dKey = normalizeKey(feature.properties.desa);
    const kecKey = normalizeKey(feature.properties.kecamatan);
    const kabKey = normalizeKey(feature.properties.kabupaten);
    const key = `${dKey}_${kecKey}_${kabKey}`;

    if (currentHoveredDesaKey === key) {
        if (isClick) {
            if (singleTooltip && map && map.hasLayer(singleTooltip)) {
                map.removeLayer(singleTooltip);
            }
        } else if (!isMobileScreen() && latlng && singleTooltip && map && map.hasLayer(singleTooltip)) {
            singleTooltip.setLatLng(latlng);
        }
        return;
    }
    currentHoveredDesaKey = key;

    hoveredFeature.value = null;
    if (activeHoverLayer && activeHoverLayer !== layer) {
        if (activeHoverLayer.feature) {
            activeHoverLayer.setStyle(desaFeatureStyle(activeHoverLayer.feature));
        }
    }
    activeHoverLayer = layer;

    layer.setStyle({
        fill: true,
        weight: 2,
        color: '#047857',
        fillOpacity: 1,
    });

    if (layer.bringToFront) {
        layer.bringToFront();
    }
    if (kabupatenLayer && kabupatenLayer.bringToFront) {
        kabupatenLayer.bringToFront();
    }

    const count = getDesaFeatureCount(feature);
    const pct = distribution.value.activeTotal > 0
        ? Math.round((count / distribution.value.activeTotal) * 100)
        : 0;

    const students = desaDistribution.value.studentsByKey[key] || [];

    hoveredDesa.value = {
        desa: feature.properties.desa,
        kecamatan: feature.properties.kecamatan,
        kabupaten: feature.properties.kabupaten,
        count,
        percentage: pct,
        students,
    };

    if (isClick) {
        if (singleTooltip && map && map.hasLayer(singleTooltip)) {
            map.removeLayer(singleTooltip);
        }
        return;
    }

    // Pada mobile, jangan tampilkan tooltip pop-up di atas poligon wilayah karena sudah ada kartu rincian di bawah peta (agar tidak dobel)
    if (!isMobileScreen() && singleTooltip && map) {
        const pos = latlng || layer.getBounds().getCenter();
        singleTooltip
            .setLatLng(pos)
            .setContent(`
                <div class="px-2.5 py-1.5 text-xs text-slate-800 leading-tight">
                    <div class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider mb-0.5">Desa / Kelurahan</div>
                    <div class="font-extrabold text-slate-900 text-[13px]">Desa ${feature.properties.desa}</div>
                    <div class="text-[11px] text-slate-500 font-medium">Kec. ${feature.properties.kecamatan}, Kab/Kota ${feature.properties.kabupaten}</div>
                    <div class="mt-1 pt-1 border-t border-slate-200/80 flex items-center justify-between gap-3">
                        <span class="font-semibold text-emerald-800">${count} Siswa</span>
                        <span class="text-[11px] text-slate-400">(${pct}%)</span>
                    </div>
                </div>
            `);
        if (!map.hasLayer(singleTooltip)) {
            singleTooltip.addTo(map);
        }
    } else if (singleTooltip && map && map.hasLayer(singleTooltip)) {
        map.removeLayer(singleTooltip);
    }
}

function handleSelect(feature, layer, latlng = null) {
    lastInteractionTime = Date.now();
    isLockedByClick = true;
    if (aggregationLevel.value === 'kecamatan') {
        selectFeature(feature, layer, latlng, true);
    } else {
        selectDesaFeature(feature, layer, latlng, true);
    }
}

function clearHover() {
    isLockedByClick = false;
    currentHoveredDesaKey = null;
    currentHoveredKecamatanKey = null;
    if (activeHoverLayer) {
        if (aggregationLevel.value === 'kecamatan') {
            activeHoverLayer.setStyle(featureStyle(activeHoverLayer.feature));
        } else if (activeHoverLayer.feature) {
            activeHoverLayer.setStyle(desaFeatureStyle(activeHoverLayer.feature));
        }
        activeHoverLayer = null;
    }
    if (singleTooltip && map && map.hasLayer(singleTooltip)) {
        map.removeLayer(singleTooltip);
    }
    hoveredFeature.value = null;
    hoveredDesa.value = null;
}

function attachFeatureBbox(features) {
    (features || []).forEach(f => {
        if (f._bbox || !f.geometry?.coordinates) return;
        let minLng = Infinity, maxLng = -Infinity, minLat = Infinity, maxLat = -Infinity;
        function scan(coords) {
            if (typeof coords[0] === 'number') {
                const cLng = coords[0], cLat = coords[1];
                if (cLng < minLng) minLng = cLng;
                if (cLng > maxLng) maxLng = cLng;
                if (cLat < minLat) minLat = cLat;
                if (cLat > maxLat) maxLat = cLat;
            } else {
                coords.forEach(scan);
            }
        }
        scan(f.geometry.coordinates);
        f._bbox = { minLng, maxLng, minLat, maxLat };
    });
}

function buildDesaLayer() {
    if (!map || !desaGeojsonData.value) return;
    if (desaGeojsonLayer) {
        desaGeojsonLayer.remove();
        desaGeojsonLayer = null;
    }
    if (desaCanvasRenderer) {
        desaCanvasRenderer.remove();
        desaCanvasRenderer = null;
    }

    let features = desaGeojsonData.value.features || [];
    if (drillKecamatan.value) {
        features = features.filter(
            (f) =>
                normalizeKey(f.properties?.kecamatan) === drillKecamatan.value.kecKey &&
                normalizeKey(f.properties?.kabupaten) === drillKecamatan.value.kabKey
        );
    }

    // Saat drill-down ke 1 kecamatan (10-25 desa), gunakan SVG agar hover/klik 100% akurat
    const useSvgForDesa = Boolean(drillKecamatan.value) || features.length <= 150;
    if (!useSvgForDesa) {
        desaCanvasRenderer = L.canvas({ padding: 0.25 });
    }

    desaGeojsonLayer = L.geoJSON(
        { type: 'FeatureCollection', features },
        {
            ...(useSvgForDesa ? {} : { renderer: desaCanvasRenderer }),
            style: desaFeatureStyle,
            onEachFeature: (feature, layer) => {
                layer.on({
                    mouseover: (e) => {
                        if (aggregationLevel.value !== 'desa' || isLockedByClick) return;
                        selectDesaFeature(feature, layer, e.latlng, false);
                    },
                    mousemove: (e) => {
                        if (aggregationLevel.value !== 'desa' || isLockedByClick) return;
                        if (singleTooltip && map && map.hasLayer(singleTooltip)) {
                            singleTooltip.setLatLng(e.latlng);
                        }
                    },
                    mouseout: () => {
                        if (aggregationLevel.value !== 'desa' || isLockedByClick) return;
                        clearHover();
                    },
                    click: (e) => {
                        if (aggregationLevel.value !== 'desa') return;
                        if (e.originalEvent) {
                            L.DomEvent.stopPropagation(e.originalEvent);
                        }
                        lastInteractionTime = Date.now();
                        isLockedByClick = true;
                        selectDesaFeature(feature, layer, e.latlng, true);
                    },
                });
            },
        }
    );

    if (aggregationLevel.value === 'desa') {
        if (geojsonLayer && map.hasLayer(geojsonLayer)) {
            map.removeLayer(geojsonLayer);
        }
        desaGeojsonLayer.addTo(map);
        if (kabupatenLayer && kabupatenLayer.bringToFront) {
            kabupatenLayer.bringToFront();
        }
    }
}

async function loadDesaGeoJson() {
    if (desaGeojsonData.value) return desaGeojsonData.value;
    if (desaLoadingPromise) return desaLoadingPromise;

    isDesaLoading.value = true;
    desaLoadingPromise = (async () => {
        try {
            const res = await fetch('/data/solo-raya-desa.geojson?v=20261123grobogan');
            if (!res.ok) throw new Error('Gagal memuat batas desa');
            const raw = await res.json();
            attachFeatureBbox(raw.features);
            desaGeojsonData.value = raw;
            return raw;
        } catch (err) {
            console.error('Error loading desa geojson:', err);
            return null;
        } finally {
            isDesaLoading.value = false;
            desaLoadingPromise = null;
        }
    })();
    return desaLoadingPromise;
}

function renderMapLabels() {
    if (!map || !areaLabelLayer) return;

    areaLabelLayer.clearLayers();
    const zoom = map.getZoom();
    const viewBounds = map.getBounds().pad(0.08);

    if (aggregationLevel.value === 'kecamatan' && geojsonData.value?.features) {
        const minGapPx = zoom >= 11 ? 26 : (zoom >= 10 ? 34 : 44);
        const items = [];

        geojsonData.value.features.forEach((f) => {
            const lat = Number(f.properties?.lat);
            const lng = Number(f.properties?.lng);
            const name = f.properties?.kecamatan;
            if (!name || !lat || !lng || !viewBounds.contains([lat, lng])) return;
            items.push({ lat, lng, name, count: getFeatureCount(f) });
        });

        items.sort((a, b) => b.count - a.count);

        const placedPts = [];
        items.forEach((item) => {
            const pt = map.latLngToContainerPoint([item.lat, item.lng]);
            const crowded = placedPts.some((p) => Math.hypot(pt.x - p.x, pt.y - p.y) < minGapPx);
            if (crowded) return;
            placedPts.push(pt);

            const isDarkBg = item.count >= 10;
            const textColor = isDarkBg ? '#ffffff' : '#1e293b';
            const shadow = isDarkBg
                ? '0 1px 2px rgba(6,78,59,0.95), 0 0 3px rgba(6,78,59,0.85)'
                : '0 0 3px rgba(255,255,255,0.95), 0 0 4px rgba(255,255,255,0.95)';
            const fontSize = zoom >= 11 ? '10px' : '9px';

            L.marker([item.lat, item.lng], {
                interactive: false,
                icon: L.divIcon({
                    className: 'domicile-map-text-label',
                    iconSize: [0, 0],
                    html: `<div style="display:inline-block; width:max-content; transform:translate(-50%,-50%); white-space:nowrap; font-size:${fontSize}; font-weight:700; color:${textColor}; text-shadow:${shadow}; pointer-events:none; letter-spacing:0.01em;">${escapeHtml(item.name)}</div>`,
                }),
            }).addTo(areaLabelLayer);
        });
    } else if (aggregationLevel.value === 'desa' && desaGeojsonData.value?.features) {
        const shouldRenderDesa = Boolean(drillKecamatan.value) || zoom >= 12;
        if (!shouldRenderDesa) return;

        const minGapPx = zoom >= 13 ? 24 : 36;
        let features = desaGeojsonData.value.features;
        if (drillKecamatan.value) {
            features = features.filter(
                (f) =>
                    normalizeKey(f.properties?.kecamatan) === drillKecamatan.value.kecKey &&
                    normalizeKey(f.properties?.kabupaten) === drillKecamatan.value.kabKey
            );
        }

        const items = [];
        features.forEach((f) => {
            const lat = Number(f.properties?.lat);
            const lng = Number(f.properties?.lng);
            const name = f.properties?.desa;
            if (!name || !lat || !lng || !viewBounds.contains([lat, lng])) return;
            items.push({ lat, lng, name, count: getDesaFeatureCount(f) });
        });

        items.sort((a, b) => b.count - a.count);

        const placedPts = [];
        items.forEach((item) => {
            const pt = map.latLngToContainerPoint([item.lat, item.lng]);
            const crowded = placedPts.some((p) => Math.hypot(pt.x - p.x, pt.y - p.y) < minGapPx);
            if (crowded) return;
            placedPts.push(pt);

            const isDarkBg = item.count >= 10;
            const textColor = isDarkBg ? '#ffffff' : '#334155';
            const shadow = isDarkBg
                ? '0 1px 2px rgba(6,78,59,0.95), 0 0 3px rgba(6,78,59,0.85)'
                : '0 0 3px rgba(255,255,255,0.98), 0 0 4px rgba(255,255,255,0.98)';

            L.marker([item.lat, item.lng], {
                interactive: false,
                icon: L.divIcon({
                    className: 'domicile-map-text-label',
                    iconSize: [0, 0],
                    html: `<div style="display:inline-block; width:max-content; transform:translate(-50%,-50%); white-space:nowrap; font-size:9.5px; font-weight:700; color:${textColor}; text-shadow:${shadow}; pointer-events:none;">${escapeHtml(item.name)}</div>`,
                }),
            }).addTo(areaLabelLayer);
        });
    }
}

function updateMapLayers(specificKecLayer = null) {
    if (!map) return;

    if (aggregationLevel.value === 'kecamatan') {
        if (desaGeojsonLayer && map.hasLayer(desaGeojsonLayer)) {
            map.removeLayer(desaGeojsonLayer);
        }
        if (geojsonLayer) {
            if (!map.hasLayer(geojsonLayer)) {
                geojsonLayer.addTo(map);
            }
            geojsonLayer.eachLayer(layer => {
                layer.setStyle(featureStyle(layer.feature));
            });
        }
    } else {
        if (geojsonLayer && map.hasLayer(geojsonLayer)) {
            map.removeLayer(geojsonLayer);
        }
        if (desaGeojsonData.value) {
            buildDesaLayer();
        } else if (!isDesaLoading.value) {
            loadDesaGeoJson().then(() => {
                if (aggregationLevel.value === 'desa') {
                    buildDesaLayer();
                    renderMapLabels();
                }
            });
        }
    }

    if (kabupatenLayer && kabupatenLayer.bringToFront) {
        kabupatenLayer.bringToFront();
    }

    renderMapLabels();

    if (specificKecLayer && specificKecLayer.getBounds && specificKecLayer.getBounds().isValid()) {
        map.fitBounds(specificKecLayer.getBounds(), { padding: [24, 24], animate: true });
    } else if (drillKecamatan.value && desaGeojsonLayer && desaGeojsonLayer.getBounds().isValid()) {
        map.fitBounds(desaGeojsonLayer.getBounds(), { padding: [24, 24], animate: true });
    }
}

async function drillIntoKecamatan(kecamatanName, kabupatenName, kecFeatureLayer = null) {
    const kecKey = normalizeKey(kecamatanName);
    const kabKey = normalizeKey(kabupatenName);

    clearHover();
    drillKecamatan.value = {
        kecamatan: kecamatanName,
        kabupaten: kabupatenName,
        kecKey,
        kabKey,
    };
    aggregationLevel.value = 'desa';

    if (!desaGeojsonData.value) {
        await loadDesaGeoJson();
    }

    updateMapLayers(kecFeatureLayer);
}

function backToAllKecamatan() {
    clearHover();
    drillKecamatan.value = null;
    aggregationLevel.value = 'kecamatan';
    updateMapLayers();
    resetZoom();
}

async function setAggregation(level) {
    if (aggregationLevel.value === level && !drillKecamatan.value) return;
    clearHover();
    if (level === 'kecamatan') {
        drillKecamatan.value = null;
        aggregationLevel.value = 'kecamatan';
    } else {
        aggregationLevel.value = 'desa';
        if (!desaGeojsonData.value) {
            await loadDesaGeoJson();
        }
    }
    updateMapLayers();
}

function resetZoom() {
    clearHover();
    if (!map) return;
    if (drillKecamatan.value && desaGeojsonLayer && desaGeojsonLayer.getBounds().isValid()) {
        map.fitBounds(desaGeojsonLayer.getBounds(), { padding: [24, 24], animate: true });
        return;
    }
    if (geojsonLayer && geojsonLayer.getBounds().isValid()) {
        map.fitBounds(geojsonLayer.getBounds(), { padding: [20, 20], animate: true });
    }
}

function attachDirectTouchListeners(feature, layer) {
    const el = layer.getElement ? layer.getElement() : layer._path;
    if (!el) return;

    let startX = 0;
    let startY = 0;
    let startTime = 0;

    el.addEventListener('touchstart', (e) => {
        if (e.touches && e.touches.length === 1) {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            startTime = Date.now();
        }
    }, { passive: true });

    el.addEventListener('touchend', (e) => {
        if (!startTime) return;
        const touch = e.changedTouches ? e.changedTouches[0] : null;
        if (touch) {
            const dx = Math.abs(touch.clientX - startX);
            const dy = Math.abs(touch.clientY - startY);
            const dt = Date.now() - startTime;
            startTime = 0;

            if (dx < 25 && dy < 25 && dt < 600) {
                let tapLatLng = null;
                if (map && mapContainer.value) {
                    try {
                        const rect = mapContainer.value.getBoundingClientRect();
                        const pt = L.point(touch.clientX - rect.left, touch.clientY - rect.top);
                        tapLatLng = map.containerPointToLatLng(pt);
                    } catch (_) {}
                }
                if (aggregationLevel.value === 'kecamatan') {
                    handleSelect(feature, layer, tapLatLng);
                }
            }
        }
    }, { passive: true });
}

function isPointInRing(lng, lat, ring) {
    let inside = false;
    for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
        const xi = ring[i][0], yi = ring[i][1];
        const xj = ring[j][0], yj = ring[j][1];
        const intersect = ((yi > lat) !== (yj > lat)) &&
            (lng < (xj - xi) * (lat - yi) / (yj - yi) + xi);
        if (intersect) inside = !inside;
    }
    return inside;
}

function isPointInPolygonGeom(lng, lat, coordinates, type) {
    if (type === 'Polygon') {
        if (!isPointInRing(lng, lat, coordinates[0])) return false;
        for (let i = 1; i < coordinates.length; i++) {
            if (isPointInRing(lng, lat, coordinates[i])) return false;
        }
        return true;
    } else if (type === 'MultiPolygon') {
        for (let p = 0; p < coordinates.length; p++) {
            if (isPointInPolygonGeom(lng, lat, coordinates[p], 'Polygon')) return true;
        }
    }
    return false;
}

function findFeatureAndLayerAtLatLng(latlng) {
    if (!latlng) return null;
    const targetLayer = aggregationLevel.value === 'desa' ? desaGeojsonLayer : geojsonLayer;
    if (!targetLayer) return null;
    const lng = latlng.lng;
    const lat = latlng.lat;

    let matched = null;
    targetLayer.eachLayer(layer => {
        if (matched) return;
        const f = layer.feature;
        if (!f?.geometry) return;
        const b = f._bbox;
        if (b && (lng < b.minLng || lng > b.maxLng || lat < b.minLat || lat > b.maxLat)) {
            return;
        }
        if (isPointInPolygonGeom(lng, lat, f.geometry.coordinates, f.geometry.type)) {
            matched = { feature: f, layer };
        }
    });
    return matched;
}

async function loadGeoJsonAndInitMap() {
    try {
        isLoading.value = true;
        loadError.value = null;

        const [response, kabResponse] = await Promise.all([
            fetch('/data/solo-raya-kecamatan.geojson?v=20261123grobogan'),
            fetch('/data/solo-raya-kabupaten.geojson?v=20261123grobogan').catch(() => null),
        ]);
        if (!response.ok) {
            throw new Error(`Gagal memuat data batas kecamatan (status ${response.status})`);
        }
        const data = await response.json();
        attachFeatureBbox(data.features);
        geojsonData.value = data;

        let kabData = null;
        if (kabResponse && kabResponse.ok) {
            kabData = await kabResponse.json();
        }

        await nextTick();
        if (!mapContainer.value) return;

        map = L.map(mapContainer.value, {
            zoomControl: false,
            attributionControl: false,
            minZoom: 8,
            maxZoom: 16,
            scrollWheelZoom: true,
            tapTolerance: 25,
        });

        L.control.zoom({ position: 'topright' }).addTo(map);

        const bordersPane = map.createPane('domicileBordersPane');
        bordersPane.style.zIndex = '420';
        bordersPane.style.pointerEvents = 'none';

        singleTooltip = L.tooltip({
            direction: 'auto',
            className: 'leaflet-custom-tooltip',
            offset: [0, -10],
            opacity: 0.98,
        });

        // Layer 1: Poligon Kecamatan
        geojsonLayer = L.geoJSON(data, {
            style: featureStyle,
            onEachFeature: (feature, layer) => {
                layer.on({
                    mouseover: (e) => {
                        if (aggregationLevel.value !== 'kecamatan' || isLockedByClick) return;
                        selectFeature(feature, layer, e.latlng, false);
                    },
                    mousemove: (e) => {
                        if (aggregationLevel.value !== 'kecamatan' || isLockedByClick) return;
                        if (singleTooltip && map && map.hasLayer(singleTooltip)) {
                            singleTooltip.setLatLng(e.latlng);
                        }
                    },
                    mouseout: () => {
                        if (aggregationLevel.value !== 'kecamatan' || isLockedByClick) return;
                        clearHover();
                    },
                    click: (e) => {
                        if (aggregationLevel.value !== 'kecamatan') return;
                        if (e.originalEvent) {
                            L.DomEvent.stopPropagation(e.originalEvent);
                        }
                        lastInteractionTime = Date.now();
                        isLockedByClick = true;
                        selectFeature(feature, layer, e.latlng, true);
                    },
                });
            },
        }).addTo(map);

        map.on('click', (e) => {
            if (Date.now() - lastInteractionTime < 150) return;
            const result = findFeatureAndLayerAtLatLng(e.latlng);
            if (result && result.layer) {
                lastInteractionTime = Date.now();
                isLockedByClick = true;
                if (aggregationLevel.value === 'desa') {
                    selectDesaFeature(result.feature, result.layer, e.latlng, true);
                } else {
                    selectFeature(result.feature, result.layer, e.latlng, true);
                }
                return;
            }
            isLockedByClick = false;
            clearHover();
        });

        // Layer 2: Batas Kabupaten Putus-putus
        bordersSvgRenderer = L.svg({ pane: 'domicileBordersPane', padding: 0.3 });
        if (kabData) {
            kabupatenLayer = L.geoJSON(kabData, {
                renderer: bordersSvgRenderer,
                pane: 'domicileBordersPane',
                style: {
                    fill: false,
                    weight: 1.8,
                    color: '#1e293b',
                    dashArray: '6, 5',
                    opacity: 0.9,
                },
                interactive: false,
            }).addTo(map);
        }

        // Layer 3: Marker Titik Lokasi Kampus Sekolah
        const schoolIcon = L.divIcon({
            className: 'custom-school-marker',
            html: `
                <div style="position: relative; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                    <div style="position: absolute; inset: 0; border-radius: 9999px; background-color: #ef4444; opacity: 0.45; animation: ping 1.8s cubic-bezier(0, 0, 0.2, 1) infinite;"></div>
                    <div style="position: relative; width: 30px; height: 30px; border-radius: 9999px; background: linear-gradient(135deg, #e11d48, #be123c); color: white; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(190, 18, 60, 0.45); border: 2.5px solid white;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
                        </svg>
                    </div>
                </div>
            `,
            iconSize: [36, 36],
            iconAnchor: [18, 18],
            popupAnchor: [0, -18],
        });

        schoolMarker = L.marker(SCHOOL_COORDS, {
            icon: schoolIcon,
            zIndexOffset: 1200,
            title: SCHOOL_NAME,
        }).addTo(map);

        const schoolPopupHtml = `
            <div style="padding: 4px; min-width: 220px; font-family: 'Plus Jakarta Sans', system-ui, sans-serif;">
                <div style="display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; color: #be123c; margin-bottom: 3px;">
                    <span style="font-size: 13px;">📍</span>
                    <span>LOKASI KAMPUS SEKOLAH</span>
                </div>
                <div style="font-weight: 800; font-size: 12.5px; color: #0f172a; line-height: 1.25;">${SCHOOL_NAME}</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 4px; line-height: 1.35;">${SCHOOL_ADDRESS}</div>
                <div style="margin-top: 8px; padding-top: 6px; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                    <a href="https://maps.app.goo.gl/t2T45yELKUhNxgBv6" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; color: #e11d48; text-decoration: none;">
                        Buka di Google Maps ↗
                    </a>
                    <span style="font-size: 10px; color: #047857; font-weight: 700; background: #ecfdf5; padding: 2px 6px; border-radius: 4px;">Gondangrejo</span>
                </div>
            </div>
        `;

        schoolMarker.bindPopup(schoolPopupHtml, {
            className: 'leaflet-custom-tooltip school-popup',
            closeButton: true,
            autoPan: true,
        });

        schoolMarker.on('click', (e) => {
            if (e.originalEvent) {
                L.DomEvent.stopPropagation(e.originalEvent);
            }
            lastInteractionTime = Date.now();
            schoolMarker.openPopup();
        });

        areaLabelLayer = L.layerGroup().addTo(map);
        map.on('zoomend moveend', renderMapLabels);

        // Muat data desa di background agar klik kecamatan langsung instan
        loadDesaGeoJson();
    } catch (err) {
        loadError.value = err.message || 'Gagal memuat peta';
    } finally {
        isLoading.value = false;
        await nextTick();
        if (map) {
            map.invalidateSize();
            resetZoom();
            renderMapLabels();
        }
    }
}

watch(() => props.students, () => {
    updateMapLayers();
}, { deep: true });

watch(statusFilter, () => {
    clearHover();
    updateMapLayers();
});

watch(activeView, (newVal) => {
    if (newVal === 'peta' && map) {
        nextTick(() => {
            map.invalidateSize();
            renderMapLabels();
        });
    }
});

onMounted(() => {
    loadGeoJsonAndInitMap();
    window.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeyDown);
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
    clearHover();
    if (areaLabelLayer) {
        areaLabelLayer.remove();
        areaLabelLayer = null;
    }
    if (desaGeojsonLayer) {
        desaGeojsonLayer.remove();
        desaGeojsonLayer = null;
    }
    if (schoolMarker) {
        schoolMarker.remove();
        schoolMarker = null;
    }
    if (singleTooltip) {
        singleTooltip.remove();
        singleTooltip = null;
    }
    if (kabupatenLayer) {
        kabupatenLayer.remove();
        kabupatenLayer = null;
    }
    if (geojsonLayer) {
        geojsonLayer.remove();
        geojsonLayer = null;
    }
    if (map) {
        map.remove();
        map = null;
    }
});
</script>

<template>
    <div
        :class="[
            'bg-white overflow-hidden transition-colors',
            isFullscreen
                ? 'fixed inset-0 z-[900] w-screen h-screen p-3 sm:p-5 flex flex-col'
                : 'rounded-3xl border border-slate-200/90 p-4 sm:p-6 shadow-xs mb-5 w-full max-w-full min-w-0',
        ]"
    >
        <!-- Header: Judul, Tab Switcher [Peta | Bar] + Badge Ringkasan + Fullscreen -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3 sm:mb-4 pb-3 border-b border-slate-100 shrink-0">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block shrink-0"></span>
                <div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                        Sebaran Domisili Siswa (Solo Raya &amp; Grobogan)
                    </h3>
                    <p class="text-[11px] text-slate-500 font-medium mt-0.5">
                        <template v-if="drillKecamatan && activeView === 'peta'">
                            Mode Desa: <span class="font-bold text-emerald-800">Kec. {{ drillKecamatan.kecamatan }} ({{ drillKecamatan.kabupaten }})</span> — {{ statusLabelText }} • {{ unit }}
                        </template>
                        <template v-else>
                            Berdasarkan <span class="font-bold text-slate-700">{{ aggregationLevel === 'desa' ? 'Desa / Kelurahan' : 'Kecamatan' }}</span> domisili {{ statusLabelText }} — {{ unit }} ({{ isAllTime ? 'Semua TA / All Time' : `TA ${academicYear}` }})
                        </template>
                    </p>
                </div>
            </div>

            <!-- Bagian Kanan: Tab Switcher [Peta|Bar], Badge Ringkasan, Luar Wilayah & Fullscreen -->
            <div class="flex items-center gap-2 self-start sm:self-auto shrink-0 flex-wrap">
                <!-- Toggle Pilihan Tampilan: Peta / Bar -->
                <div class="inline-flex items-center p-0.5 bg-slate-100/90 rounded-xl border border-slate-200/90 shadow-2xs">
                    <button
                        type="button"
                        @click="activeView = 'peta'"
                        class="inline-flex items-center gap-1 sm:gap-1.5 px-2.5 sm:px-3 py-1 rounded-lg text-xs transition-all cursor-pointer"
                        :class="activeView === 'peta'
                            ? 'bg-white text-emerald-900 font-bold shadow-xs'
                            : 'text-slate-500 hover:text-slate-800 font-semibold'"
                        title="Tampilan Peta"
                    >
                        <span class="material-symbols-outlined text-[15px]">map</span>
                        <span>Peta</span>
                    </button>
                    <button
                        type="button"
                        @click="activeView = 'bar'"
                        class="inline-flex items-center gap-1 sm:gap-1.5 px-2.5 sm:px-3 py-1 rounded-lg text-xs transition-all cursor-pointer"
                        :class="activeView === 'bar'
                            ? 'bg-white text-emerald-900 font-bold shadow-xs'
                            : 'text-slate-500 hover:text-slate-800 font-semibold'"
                        title="Tampilan Grafik Bar"
                    >
                        <span class="material-symbols-outlined text-[15px]">bar_chart</span>
                        <span>Bar</span>
                    </button>
                </div>

                <!-- Badge Total Tercakup -->
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-800 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                    <span>{{ distribution.coverageTotal }} Siswa</span>
                    <span class="text-[11px] text-emerald-600 font-medium">
                        ({{ aggregationLevel === 'desa' ? `${desaDistribution.totalDesaCount} Desa` : `${barKecamatans.length} Kec.` }})
                    </span>
                </div>

                <!-- Tombol Luar Wilayah Modal -->
                <button
                    v-if="distribution.outsideTotal > 0"
                    type="button"
                    @click="showOutsideModal = true"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-50 hover:bg-amber-100 border border-amber-300 text-xs font-bold text-amber-900 transition-all cursor-pointer shadow-2xs"
                    title="Klik untuk melihat rincian wilayah luar Solo Raya & Grobogan"
                >
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span>Luar Wilayah: {{ distribution.outsideTotal }}</span>
                    <span class="material-symbols-outlined text-[14px]">info</span>
                </button>

                <!-- Tombol Fullscreen Peta -->
                <button
                    v-if="activeView === 'peta'"
                    type="button"
                    @click="toggleFullscreen()"
                    :class="[
                        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all cursor-pointer',
                        isFullscreen
                            ? 'bg-rose-600 hover:bg-rose-700 text-white border-rose-700 shadow-xs'
                            : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200',
                    ]"
                    :title="isFullscreen ? 'Keluar layar penuh (ESC)' : 'Perbesar peta layar penuh'"
                >
                    <span class="material-symbols-outlined text-[16px]">{{ isFullscreen ? 'fullscreen_exit' : 'fullscreen' }}</span>
                    <span class="hidden sm:inline">{{ isFullscreen ? 'Tutup Fullscreen' : 'Fullscreen' }}</span>
                </button>
            </div>
        </div>

        <!-- Baris Filter Status Siswa pada Peta Domisili -->
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3.5 shrink-0">
            <div class="inline-flex items-center p-1 bg-slate-100 rounded-full border border-slate-200/90 overflow-x-auto max-w-full">
                <button
                    v-for="tab in [
                        { key: 'all', label: 'Semua', count: statusCounts.all },
                        { key: 'aktif', label: 'Aktif', count: statusCounts.aktif },
                        { key: 'lulus', label: 'Lulus', count: statusCounts.lulus },
                        { key: 'tidak_aktif', label: 'Tidak Aktif', count: statusCounts.tidak_aktif },
                    ]"
                    :key="tab.key"
                    type="button"
                    @click="statusFilter = tab.key"
                    :class="[
                        'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold transition-all cursor-pointer shrink-0',
                        statusFilter === tab.key
                            ? (tab.key === 'lulus'
                                ? 'bg-blue-700 text-white shadow-2xs'
                                : (tab.key === 'tidak_aktif'
                                    ? 'bg-rose-700 text-white shadow-2xs'
                                    : 'bg-emerald-900 text-yellow-200 shadow-2xs'))
                            : 'text-slate-600 hover:text-slate-900'
                    ]"
                >
                    <span>{{ tab.label }}</span>
                    <span class="text-[10.5px] opacity-80">({{ tab.count }})</span>
                </button>
            </div>

            <!-- Switcher Kec / Desa untuk Mode Bar -->
            <div v-if="activeView === 'bar'" class="inline-flex items-center p-0.5 bg-amber-50 rounded-xl border border-amber-200/90 shadow-2xs">
                <button
                    type="button"
                    @click="setAggregation('kecamatan')"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] transition-all cursor-pointer"
                    :class="aggregationLevel === 'kecamatan'
                        ? 'bg-amber-400 text-amber-950 font-bold shadow-2xs'
                        : 'text-amber-800/80 hover:text-amber-950 font-semibold'"
                >
                    <span>Kec.</span>
                </button>
                <button
                    type="button"
                    @click="setAggregation('desa')"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] transition-all cursor-pointer"
                    :class="aggregationLevel === 'desa'
                        ? 'bg-amber-400 text-amber-950 font-bold shadow-2xs'
                        : 'text-amber-800/80 hover:text-amber-950 font-semibold'"
                >
                    <span>Desa</span>
                </button>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- VIEW 1: TAMPILAN PETA VEKTOR (Selaras Pemetaan PSB Domisili) -->
        <!-- ============================================================ -->
        <div
            v-show="activeView === 'peta'"
            :class="[
                'relative w-full rounded-2xl border border-slate-200/90 bg-[#f8fafc] overflow-hidden',
                isFullscreen ? 'flex-1 min-h-0' : 'h-[460px] sm:h-[560px] lg:h-[620px]',
            ]"
        >
            <!-- Loading State -->
            <div v-if="isLoading || isDesaLoading" class="absolute inset-0 bg-slate-50/90 z-[500] flex flex-col items-center justify-center gap-2">
                <span class="material-symbols-outlined text-3xl text-emerald-700 animate-spin">progress_activity</span>
                <span class="text-xs font-semibold text-slate-500">
                    {{ isDesaLoading ? 'Memuat batas wilayah desa/kelurahan Solo Raya & Grobogan...' : 'Memuat batas wilayah Solo Raya & Grobogan...' }}
                </span>
            </div>

            <!-- Error State -->
            <div v-else-if="loadError" class="absolute inset-0 bg-white z-[500] flex flex-col items-center justify-center p-4 text-center">
                <span class="material-symbols-outlined text-3xl text-rose-500 mb-1">map</span>
                <span class="text-xs font-bold text-slate-800">Gagal Memuat Peta</span>
                <p class="text-[11px] text-slate-500 mt-1 max-w-xs">{{ loadError }}</p>
                <button
                    type="button"
                    @click="loadGeoJsonAndInitMap"
                    class="mt-3 px-3 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg text-xs font-semibold transition-all cursor-pointer"
                >
                    Coba Lagi
                </button>
            </div>

            <!-- Leaflet Container -->
            <div ref="mapContainer" class="w-full h-full z-10 domicile-leaflet-bg"></div>

            <!-- Kontrol Pojok Kiri Atas di Dalam Peta: [Kec. | Desa] -->
            <div class="absolute top-3 left-3 z-[410] flex items-center gap-1.5 flex-wrap max-w-[calc(100%-56px)]">
                <div class="inline-flex items-center p-0.5 bg-amber-50/95 backdrop-blur-xs rounded-xl border border-amber-200/90 shadow-md">
                    <button
                        type="button"
                        @click="setAggregation('kecamatan')"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] transition-all cursor-pointer"
                        :class="aggregationLevel === 'kecamatan'
                            ? 'bg-amber-400 text-amber-950 font-bold shadow-2xs'
                            : 'text-amber-800/80 hover:text-amber-950 font-semibold'"
                    >
                        <span>Kec.</span>
                    </button>
                    <button
                        type="button"
                        @click="setAggregation('desa')"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] transition-all cursor-pointer"
                        :class="aggregationLevel === 'desa'
                            ? 'bg-amber-400 text-amber-950 font-bold shadow-2xs'
                            : 'text-amber-800/80 hover:text-amber-950 font-semibold'"
                    >
                        <span>Desa</span>
                    </button>
                </div>
            </div>

            <!-- Tombol Reset Zoom di Bawah Kontrol Zoom Leaflet -->
            <button
                type="button"
                @click="resetZoom"
                class="absolute top-[74px] right-2.5 z-[400] w-8 h-8 rounded-lg bg-white/95 hover:bg-white border border-slate-300 shadow-xs flex items-center justify-center text-slate-700 cursor-pointer"
                title="Reset posisi zoom peta"
            >
                <span class="material-symbols-outlined text-[17px]">my_location</span>
            </button>

            <!-- Pojok Kanan Bawah Stack: Tombol Kembali (saat masuk Desa) + Card Rincian / Hint -->
            <div class="absolute bottom-3 left-3 right-3 sm:left-auto sm:right-3 sm:w-80 sm:max-w-xs z-[500] flex flex-col items-end gap-2 pointer-events-none">
                <button
                    v-if="drillKecamatan"
                    type="button"
                    @click="backToAllKecamatan"
                    :class="[
                        'pointer-events-auto items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 text-xs font-bold shadow-md border border-emerald-950/30 cursor-pointer active:scale-95 transition-all',
                        (hoveredFeature || hoveredDesa) ? 'hidden sm:inline-flex' : 'inline-flex',
                    ]"
                >
                    <span class="material-symbols-outlined text-[15px]">arrow_back</span>
                    <span>Kembali</span>
                </button>

                <!-- Live Status Hover/Click Card: Mode Desa -->
                <div
                    v-if="hoveredDesa"
                    class="w-full bg-white/95 backdrop-blur-xs rounded-xl border border-slate-200/90 p-2.5 sm:p-3 shadow-lg text-xs pointer-events-auto touch-manipulation transition-all animate-in fade-in duration-150"
                >
                    <div class="flex flex-col relative">
                        <button
                            type="button"
                            @click.stop="clearHover"
                            class="absolute -top-1 -right-1 text-slate-400 hover:text-slate-700 p-1 rounded-full cursor-pointer touch-manipulation"
                            title="Tutup info"
                        >
                            <span class="material-symbols-outlined text-[16px]">close</span>
                        </button>
                        <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Desa / Kelurahan</span>
                        <span class="text-[13px] font-extrabold text-slate-900 pr-6 leading-tight mt-0.5">Desa {{ hoveredDesa.desa }}</span>
                        <span class="text-[11px] text-slate-500 font-medium mt-0.5">Kec. {{ hoveredDesa.kecamatan }} • Kab/Kota {{ hoveredDesa.kabupaten }}</span>
                        <div class="mt-1.5 pt-1.5 border-t border-slate-100 flex items-center justify-between gap-2">
                            <span class="text-xs font-bold text-slate-900">
                                {{ hoveredDesa.count }} Siswa
                            </span>
                            <span class="text-[10.5px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/60">
                                {{ hoveredDesa.percentage }}% dari total
                            </span>
                        </div>
                        <button
                            v-if="hoveredDesa.count > 0"
                            type="button"
                            @click="openDesaModal(hoveredDesa)"
                            class="mt-2 w-full py-1.5 px-2.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-[11px] font-bold flex items-center justify-center gap-1.5 transition-colors shadow-2xs cursor-pointer"
                        >
                            <span>Lihat {{ hoveredDesa.count }} Siswa</span>
                            <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                        </button>
                    </div>
                </div>

                <!-- Live Status Hover/Click Card: Mode Kecamatan -->
                <div
                    v-else-if="hoveredFeature"
                    class="w-full bg-white/95 backdrop-blur-xs rounded-xl border border-slate-200/90 p-2.5 sm:p-3 shadow-lg text-xs pointer-events-auto touch-manipulation transition-all animate-in fade-in duration-150"
                >
                    <div class="flex flex-col relative">
                        <button
                            type="button"
                            @click.stop="clearHover"
                            class="absolute -top-1 -right-1 text-slate-400 hover:text-slate-700 p-1 rounded-full cursor-pointer touch-manipulation"
                            title="Tutup info"
                        >
                            <span class="material-symbols-outlined text-[16px]">close</span>
                        </button>
                        <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Kecamatan</span>
                        <span class="text-[13px] font-extrabold text-slate-900 pr-6 leading-tight mt-0.5">Kec. {{ hoveredFeature.kecamatan }}</span>
                        <span class="text-[11px] text-slate-500 font-medium mt-0.5">Kab/Kota {{ hoveredFeature.kabupaten }}</span>
                        <div class="mt-1.5 pt-1.5 border-t border-slate-100 flex items-center justify-between gap-2">
                            <span class="text-xs font-bold text-slate-900">
                                {{ hoveredFeature.count }} Siswa
                            </span>
                            <span class="text-[10.5px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/60">
                                {{ hoveredFeature.percentage }}% dari total
                            </span>
                        </div>
                        <button
                            v-if="hoveredFeature.count > 0"
                            type="button"
                            @click="openFeatureModal(hoveredFeature)"
                            class="mt-2 w-full py-1.5 px-2.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-[11px] font-bold flex items-center justify-center gap-1.5 transition-colors shadow-2xs cursor-pointer"
                        >
                            <span>Lihat {{ hoveredFeature.count }} Siswa</span>
                            <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                        </button>
                        <div class="mt-1.5 flex items-center gap-1.5 flex-wrap">
                            <button
                                type="button"
                                @click="drillIntoKecamatan(hoveredFeature.kecamatan, hoveredFeature.kabupaten, hoveredFeature.featureLayer)"
                                class="flex-1 py-1 px-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10.5px] font-bold transition-colors cursor-pointer text-center"
                            >
                                Masuk Level Desa →
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Petunjuk Saat Belum Ada Hover/Klik -->
                <div
                    v-else
                    class="hidden sm:block bg-white/90 backdrop-blur-xs rounded-xl border border-slate-200/80 px-3 py-2 shadow-xs text-xs pointer-events-none"
                >
                    <span class="text-[11px] text-slate-400 font-medium">
                        {{ aggregationLevel === 'desa' ? 'Arahkan kursor atau klik desa untuk detail' : 'Arahkan kursor atau klik kecamatan untuk detail' }}
                    </span>
                </div>
            </div>

            <!-- Tombol Buka Legenda khusus Mobile -->
            <button
                v-if="!isLegendOpenMobile && !hoveredFeature && !hoveredDesa"
                type="button"
                @click="isLegendOpenMobile = true"
                class="sm:hidden absolute bottom-3 left-3 z-[400] px-2.5 py-1.5 rounded-xl bg-white/95 backdrop-blur-xs text-slate-700 text-[11px] font-bold shadow-md border border-slate-200/80 flex items-center gap-1.5 cursor-pointer touch-manipulation transition-all"
                title="Tampilkan legenda peta"
            >
                <span class="material-symbols-outlined text-[15px] text-emerald-700">palette</span>
                <span>Legenda</span>
            </button>

            <!-- Floating Legend Kiri Bawah (identik Pemetaan PSB Domisili tanpa Radius & Tol) -->
            <div
                class="absolute bottom-3 left-3 z-[400] bg-white/95 backdrop-blur-xs rounded-xl border border-slate-200/90 p-2 sm:p-2.5 shadow-md flex-col gap-1.5 transition-all"
                :class="[
                    isLegendOpenMobile ? 'flex' : 'hidden sm:flex',
                    { 'hidden sm:flex': hoveredFeature || hoveredDesa }
                ]"
            >
                <div>
                    <div class="flex items-center justify-between gap-3 mb-1">
                        <span class="text-[10px] font-bold text-slate-700 uppercase tracking-wider">
                            {{ aggregationLevel === 'desa' ? 'Siswa Domisili per Desa' : 'Siswa Domisili per Kecamatan' }}
                        </span>
                        <button
                            type="button"
                            @click="isLegendOpenMobile = false"
                            class="sm:hidden p-0.5 -mr-1 text-slate-400 hover:text-slate-700 rounded-md cursor-pointer touch-manipulation flex items-center"
                            title="Sembunyikan legenda"
                        >
                            <span class="material-symbols-outlined text-[15px]">expand_more</span>
                        </button>
                    </div>
                    <div class="flex items-center gap-1 text-[10px] text-slate-600 font-medium">
                        <div class="flex items-center gap-0.5"><span class="w-3.5 h-3 rounded-xs border border-slate-300 inline-block bg-[#ffffff]"></span><span class="mr-1">0</span></div>
                        <div class="flex items-center gap-0.5"><span class="w-3.5 h-3 rounded-xs inline-block bg-[#6ee7b7]"></span><span class="mr-1">1-4</span></div>
                        <div class="flex items-center gap-0.5"><span class="w-3.5 h-3 rounded-xs inline-block bg-[#10b981]"></span><span class="mr-1">5-9</span></div>
                        <div class="flex items-center gap-0.5"><span class="w-3.5 h-3 rounded-xs inline-block bg-[#047857]"></span><span class="mr-1">10-19</span></div>
                        <div class="flex items-center gap-0.5"><span class="w-3.5 h-3 rounded-xs inline-block bg-[#064e3b]"></span><span>20+</span></div>
                    </div>
                </div>

                <!-- Legenda Batas Wilayah & Titik Sekolah -->
                <div class="pt-1.5 border-t border-slate-200/70 flex items-center gap-2.5 text-[10px] text-slate-600 font-medium flex-wrap">
                    <div class="flex items-center gap-1" title="Lokasi Kampus Sekolah">
                        <span class="w-3 h-3 rounded-full bg-rose-600 border border-white inline-block"></span>
                        <span class="font-bold text-rose-700">Sekolah</span>
                    </div>
                    <div class="flex items-center gap-1" title="Garis putus-putus batas kabupaten">
                        <span class="w-4 border-t-2 border-dashed border-[#1e293b] inline-block"></span>
                        <span>Batas Kab</span>
                    </div>
                    <div v-if="aggregationLevel === 'kecamatan'" class="flex items-center gap-1" title="Garis batas kecamatan">
                        <span class="w-4 h-[1.5px] bg-[#94a3b8] inline-block"></span>
                        <span>Batas Kec</span>
                    </div>
                    <div v-if="aggregationLevel === 'desa'" class="flex items-center gap-1" title="Garis batas desa">
                        <span class="w-4 h-[1.5px] bg-[#cbd5e1] inline-block"></span>
                        <span>Batas Desa</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- VIEW 2: TAMPILAN GRAFIK HORIZONTAL BAR                       -->
        <!-- ============================================================ -->
        <div v-show="activeView === 'bar'" class="w-full h-[380px] sm:h-[460px] rounded-2xl border border-slate-200/90 bg-white p-3 sm:p-4 flex flex-col">
            <!-- Toolbar: Pencarian Cepat & Filter Jumlah Top -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-3 mb-2 border-b border-slate-100 shrink-0">
                <div class="relative w-full sm:w-72">
                    <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                    <input
                        v-model="barSearchQuery"
                        type="text"
                        :placeholder="aggregationLevel === 'desa' ? 'Cari desa, kecamatan, atau kab...' : 'Cari kecamatan atau kabupaten...'"
                        class="w-full pl-8 pr-7 py-1.5 rounded-xl border border-slate-200 text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                    />
                    <button
                        v-if="barSearchQuery"
                        type="button"
                        @click="barSearchQuery = ''"
                        class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-[16px] cursor-pointer"
                    >
                        close
                    </button>
                </div>

                <!-- Filter Kapsul Limit (Top 10, Top 15, Top 25, Semua) -->
                <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider shrink-0">Tampilkan:</span>
                    <div class="inline-flex items-center p-0.5 bg-slate-100 rounded-lg border border-slate-200 text-xs">
                        <button
                            v-for="opt in [
                                { label: 'Top 10', value: '10' },
                                { label: 'Top 15', value: '15' },
                                { label: 'Top 25', value: '25' },
                                { label: 'Semua', value: 'all' },
                            ]"
                            :key="opt.value"
                            type="button"
                            @click="barFilterLimit = opt.value; barSearchQuery = ''"
                            class="px-2.5 py-1 rounded-md text-[11px] font-semibold transition-all cursor-pointer"
                            :class="barFilterLimit === opt.value && !barSearchQuery
                                ? 'bg-white text-emerald-900 font-bold shadow-2xs'
                                : 'text-slate-500 hover:text-slate-800'"
                        >
                            {{ opt.label }}
                        </button>
                    </div>
                    <span class="text-[11px] text-slate-400 font-medium hidden md:inline">
                        ({{ aggregationLevel === 'desa' ? `${displayedDesas.length} desa` : `${displayedKecamatans.length} kecamatan` }})
                    </span>
                </div>
            </div>

            <!-- Kontainer Daftar Horizontal Bar (Scrollable) -->
            <div class="flex-1 overflow-y-auto pr-2 custom-bar-scrollbar">
                <!-- Bar Mode Desa: Tampilkan Ranking Desa/Kelurahan -->
                <div
                    v-if="aggregationLevel === 'desa' && displayedDesas.length > 0"
                    class="space-y-3 py-1"
                >
                    <div
                        v-for="(item, idx) in displayedDesas"
                        :key="item.key"
                        @click="openDesaModal(item)"
                        class="group p-3 sm:p-3.5 rounded-2xl bg-slate-50/70 hover:bg-emerald-50/40 border border-slate-200/80 hover:border-emerald-200 transition-all cursor-pointer space-y-2"
                        :title="`Klik untuk melihat daftar ${item.count} siswa dari Desa ${item.desa} (Kec. ${item.kecamatan})`"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex items-start gap-2.5 flex-1">
                                <span
                                    class="w-6 h-6 rounded-lg text-[10.5px] font-bold flex items-center justify-center shrink-0 border shadow-2xs mt-0.5 group-hover:scale-105 transition-transform"
                                    :class="{
                                        'bg-amber-100 text-amber-900 border-amber-300 font-extrabold': idx === 0 && !barSearchQuery,
                                        'bg-slate-200 text-slate-800 border-slate-300 font-extrabold': idx === 1 && !barSearchQuery,
                                        'bg-amber-50 text-amber-800 border-amber-200 font-bold': idx === 2 && !barSearchQuery,
                                        'bg-white text-emerald-800 border-emerald-200/80': idx > 2 || barSearchQuery,
                                    }"
                                    :title="`Peringkat #${idx + 1}`"
                                >
                                    {{ idx + 1 }}
                                </span>

                                <div class="min-w-0 flex-1">
                                    <h4 class="text-xs sm:text-[13px] font-bold text-slate-900 group-hover:text-emerald-950 break-words leading-snug">
                                        Desa {{ item.desa }}
                                    </h4>
                                    <p class="text-[11px] text-slate-500 font-medium">
                                        Kec. {{ item.kecamatan }}, Kab/Kota {{ item.kabupaten }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0 text-xs mt-0.5">
                                <span class="font-extrabold text-emerald-800">
                                    {{ item.count }} Siswa
                                </span>
                                <span class="text-[10.5px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded-md border border-amber-200/80">
                                    {{ item.percentage }}%
                                </span>
                                <span class="material-symbols-outlined text-[16px] text-slate-400 group-hover:text-emerald-700 transition-colors ml-0.5">
                                    open_in_new
                                </span>
                            </div>
                        </div>

                        <!-- Bar Horizontal Progress -->
                        <div class="w-full bg-slate-200/70 rounded-full h-2.5 overflow-hidden">
                            <div
                                class="h-full rounded-full bar-progress-animate"
                                :class="{
                                    'bg-emerald-700': idx === 0 && !barSearchQuery,
                                    'bg-emerald-600': idx > 0 && idx < 3 && !barSearchQuery,
                                    'bg-emerald-500': idx >= 3 || barSearchQuery,
                                }"
                                :style="{ width: `${Math.max((item.count / maxDesaBarCount) * 100, 3)}%` }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Bar Mode Kecamatan: Tampilkan Ranking Kecamatan -->
                <div
                    v-else-if="aggregationLevel === 'kecamatan' && displayedKecamatans.length > 0"
                    class="space-y-3 py-1"
                >
                    <div
                        v-for="(item, idx) in displayedKecamatans"
                        :key="item.key"
                        @click="openKecamatanModal(item)"
                        class="group p-3 sm:p-3.5 rounded-2xl bg-slate-50/70 hover:bg-emerald-50/40 border border-slate-200/80 hover:border-emerald-200 transition-all cursor-pointer space-y-2"
                        :title="`Klik untuk melihat daftar ${item.count} siswa dari Kec. ${item.kecamatan} (${item.kabupaten})`"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex items-start gap-2.5 flex-1">
                                <span
                                    class="w-6 h-6 rounded-lg text-[10.5px] font-bold flex items-center justify-center shrink-0 border shadow-2xs mt-0.5 group-hover:scale-105 transition-transform"
                                    :class="{
                                        'bg-amber-100 text-amber-900 border-amber-300 font-extrabold': idx === 0 && !barSearchQuery,
                                        'bg-slate-200 text-slate-800 border-slate-300 font-extrabold': idx === 1 && !barSearchQuery,
                                        'bg-amber-50 text-amber-800 border-amber-200 font-bold': idx === 2 && !barSearchQuery,
                                        'bg-white text-emerald-800 border-emerald-200/80': idx > 2 || barSearchQuery,
                                    }"
                                    :title="`Peringkat #${idx + 1}`"
                                >
                                    {{ idx + 1 }}
                                </span>

                                <div class="min-w-0 flex-1">
                                    <h4 class="text-xs sm:text-[13px] font-bold text-slate-900 group-hover:text-emerald-950 break-words leading-snug">
                                        Kec. {{ item.kecamatan }}
                                    </h4>
                                    <p class="text-[11px] text-slate-500 font-medium">
                                        Kab/Kota {{ item.kabupaten }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0 text-xs mt-0.5">
                                <span class="font-extrabold text-emerald-800">
                                    {{ item.count }} Siswa
                                </span>
                                <span class="text-[10.5px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded-md border border-amber-200/80">
                                    {{ item.percentage }}%
                                </span>
                                <span class="material-symbols-outlined text-[16px] text-slate-400 group-hover:text-emerald-700 transition-colors ml-0.5">
                                    open_in_new
                                </span>
                            </div>
                        </div>

                        <!-- Bar Horizontal Progress -->
                        <div class="w-full bg-slate-200/70 rounded-full h-2.5 overflow-hidden">
                            <div
                                class="h-full rounded-full bar-progress-animate"
                                :class="{
                                    'bg-emerald-700': idx === 0 && !barSearchQuery,
                                    'bg-emerald-600': idx > 0 && idx < 3 && !barSearchQuery,
                                    'bg-emerald-500': idx >= 3 || barSearchQuery,
                                }"
                                :style="{ width: `${Math.max((item.count / maxBarCount) * 100, 3)}%` }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Empty State jika tidak ada data -->
                <div v-else class="h-full flex flex-col items-center justify-center py-12 text-center">
                    <span class="material-symbols-outlined text-3xl text-slate-300 mb-1">location_off</span>
                    <p class="text-xs font-bold text-slate-700">Tidak ada data ditemukan</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        {{ barSearchQuery ? 'Coba gunakan kata kunci pencarian lain.' : 'Belum ada data domisili siswa terdata.' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Modal Detail Siswa Luar Cakupan -->
        <div
            v-if="showOutsideModal"
            class="fixed inset-0 bg-slate-900/40 backdrop-blur-2xs z-[999] flex items-center justify-center p-4"
            @click.self="showOutsideModal = false"
        >
            <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-5 shadow-xl animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        <h4 class="text-sm font-bold text-slate-900">Domisili Luar Cakupan</h4>
                    </div>
                    <button
                        type="button"
                        @click="showOutsideModal = false"
                        class="text-slate-400 hover:text-slate-700 p-1 rounded-lg cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <p class="text-xs text-slate-500 mt-2 mb-3">
                    Total {{ distribution.outsideTotal }} siswa terdata beralamat di luar wilayah Solo Raya & Kab. Grobogan:
                </p>

                <div class="max-h-60 overflow-y-auto space-y-1.5 pr-1 custom-bar-scrollbar">
                    <div
                        v-for="(item, idx) in distribution.outsideList"
                        :key="idx"
                        @click="openKecamatanModal({ kecamatan: item.region, kabupaten: 'Luar Cakupan', count: item.count, percentage: distribution.activeTotal > 0 ? Math.round((item.count / distribution.activeTotal) * 100) : 0, students: item.students || [] })"
                        class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 hover:bg-emerald-50/50 border border-slate-200/80 hover:border-emerald-300 text-xs cursor-pointer group transition-colors"
                        title="Klik untuk melihat rincian siswa"
                    >
                        <div class="min-w-0 flex items-center gap-1.5">
                            <span class="font-semibold text-slate-800 group-hover:text-emerald-950 truncate">{{ item.region }}</span>
                            <span class="material-symbols-outlined text-[14px] text-slate-400 group-hover:text-emerald-700">open_in_new</span>
                        </div>
                        <span class="font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200 shrink-0">
                            {{ item.count }} Siswa
                        </span>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 flex justify-end">
                    <button
                        type="button"
                        @click="showOutsideModal = false"
                        class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL POP-UP: DAFTAR NAMA SISWA BERDASARKAN DOMISILI         -->
    <!-- ============================================================ -->
    <Teleport to="body">
        <div
            v-if="selectedKecamatanModal"
            class="fixed inset-0 z-[999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 overflow-y-auto"
            @click.self="closeKecamatanModal"
        >
            <div
                class="bg-white rounded-3xl border border-slate-200/90 shadow-2xl max-w-2xl w-full max-h-[85vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150"
            >
                <!-- Modal Header -->
                <div class="px-4 sm:px-6 py-4 border-b border-slate-100 flex items-start justify-between gap-3 bg-slate-50/70 shrink-0">
                    <div class="flex items-start gap-3 min-w-0">
                        <div
                            class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 border shadow-2xs mt-0.5 bg-emerald-100 border-emerald-300 text-emerald-800"
                        >
                            <span class="material-symbols-outlined text-[22px]">
                                {{ selectedKecamatanModal.desa ? 'holiday_village' : 'location_on' }}
                            </span>
                        </div>
                        <div class="min-w-0">
                            <span
                                class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider inline-block mb-1 bg-emerald-100 text-emerald-900"
                            >
                                {{ selectedKecamatanModal.desa ? 'Domisili Desa / Kelurahan' : 'Domisili Kecamatan' }}
                            </span>
                            <h3 class="text-sm sm:text-base font-extrabold text-slate-900 break-words leading-snug">
                                <template v-if="selectedKecamatanModal.desa">
                                    Desa {{ selectedKecamatanModal.desa }}
                                    <span class="text-slate-500 font-semibold text-xs sm:text-sm">
                                        (Kec. {{ selectedKecamatanModal.kecamatan }}, {{ selectedKecamatanModal.kabupaten }})
                                    </span>
                                </template>
                                <template v-else>
                                    Kec. {{ selectedKecamatanModal.kecamatan }}
                                    <span v-if="selectedKecamatanModal.kabupaten && selectedKecamatanModal.kabupaten !== 'Luar Cakupan'" class="text-slate-500 font-semibold text-xs sm:text-sm">
                                        ({{ selectedKecamatanModal.kabupaten }})
                                    </span>
                                </template>
                            </h3>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5">
                                Total <span class="font-bold text-slate-800">{{ selectedKecamatanModal.count }} Siswa</span>
                                ({{ selectedKecamatanModal.percentage }}% dari {{ statusLabelText }}) • {{ unit }} ({{ isAllTime ? 'Semua TA / All Time' : `TA ${academicYear}` }})
                            </p>
                        </div>
                    </div>

                    <!-- Tombol Tutup X -->
                    <button
                        type="button"
                        @click="closeKecamatanModal"
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
                            placeholder="Cari nama, NISN, dusun, atau desa..."
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
                        Menampilkan {{ modalFilteredStudents.length }} dari {{ selectedKecamatanModal.count }} siswa
                    </span>
                </div>

                <!-- Modal Body: Daftar Siswa (Identik Pemetaan PSB - Domisili) -->
                <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-3 space-y-2 custom-bar-scrollbar">
                    <div
                        v-if="modalFilteredStudents.length > 0"
                        class="space-y-2"
                    >
                        <PsbStudentRowCard
                            v-for="(student, sIdx) in modalFilteredStudents"
                            :key="student.id || sIdx"
                            :student="student"
                            :index="sIdx"
                            mode="welfare"
                        />
                    </div>

                    <!-- Empty Search Inside Modal -->
                    <div v-else class="py-12 text-center">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400">
                            <span class="material-symbols-outlined text-xl">person_search</span>
                        </div>
                        <p class="text-xs font-bold text-slate-700">Tidak ada siswa yang cocok</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            Coba kata kunci pencarian nama lain.
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
                        @click="closeKecamatanModal"
                        class="px-4 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition-all cursor-pointer shadow-xs"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style>
/* Animasi Bar Progress Khusus dari Kiri ke Kanan */
@keyframes barGrow {
    0% {
        transform: scaleX(0);
    }
    100% {
        transform: scaleX(1);
    }
}

.bar-progress-animate {
    transform-origin: left;
    animation: barGrow 0.75s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

/* Styling peta latar & label wilayah (selaras Pemetaan PSB) */
.domicile-leaflet-bg.leaflet-container {
    background-color: #f8fafc !important;
}
.domicile-leaflet-bg .leaflet-pane > svg {
    pointer-events: none !important;
}
.domicile-map-text-label {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
}

/* Styling tooltip peta */
.leaflet-custom-tooltip {
    background-color: rgba(255, 255, 255, 0.96) !important;
    border: 1px solid rgba(226, 232, 240, 0.9) !important;
    border-radius: 12px !important;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08) !important;
    padding: 0 !important;
}
.leaflet-custom-tooltip::before {
    display: none !important;
}

/* Scrollbar halus untuk daftar horizontal bar */
.custom-bar-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-bar-scrollbar::-webkit-scrollbar-track {
    background: #f8fafc;
    border-radius: 9999px;
}
.custom-bar-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 9999px;
}
.custom-bar-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
