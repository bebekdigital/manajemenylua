<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import {
    CAMPUS_RING_META,
    PSB_TIERS,
    SOLO_RAYA_KABUPATENS,
    normalizeDesaKey,
    normalizeWilayahKey,
} from '@/Utils/psbMatcher';
import PsbStudentRowCard from './PsbStudentRowCard.vue';

// Perbesar toleransi geser Leaflet untuk layar sentuh/mobile
if (L.Draggable?.prototype?.options) {
    L.Draggable.prototype.options.clickTolerance = 25;
}

const props = defineProps({
    mode: {
        type: String,
        default: 'school', // 'school' | 'welfare'
    },
    schools: {
        type: Array,
        default: () => [],
    },
    villages: {
        type: Array,
        default: () => [],
    },
    tollGates: {
        type: Array,
        default: () => [],
    },
    selectedKabupaten: {
        type: String,
        default: 'Semua',
    },
    selectedKecamatan: {
        type: String,
        default: 'Semua',
    },
});

const emit = defineEmits(['update:mode', 'select-school', 'select-village', 'select-kecamatan', 'reset-kecamatan']);

const isWelfareMode = computed(() => props.mode === 'welfare');

const mapContainerRef = ref(null);
let map = null;
let kecamatanLayer = null;
let desaLayer = null;
let desaCanvasRenderer = null;
let kabupatenLayer = null;
let schoolPointsLayer = null;
let areaLabelLayer = null;
let schoolLabelLayer = null;
let radiusCirclesLayer = null;
let tollGatesLayer = null;
let bordersSvgRenderer = null;
let pointsSvgRenderer = null;
let campusMarker = null;
let activeHoverLayer = null;
let activeSchoolMarker = null;
let currentHoveredKey = null;
let singleTooltip = null;
let isLockedByClick = false;
let lastInteractionTime = 0;
let lastMarkerClickTime = 0;
let desaLoadingPromise = null;
let currentDotCandidates = [];

// Lokasi Kampus Sekolah (sama dengan StudentDomicileMap)
const SCHOOL_COORDS = [-7.4989289, 110.822498];
const SCHOOL_NAME = 'PPTQ & SMPIT TQ Ulil Albab Karanganyar';
const SCHOOL_ADDRESS = 'Sidorejo, Selokaton, Kec. Gondangrejo, Kab. Karanganyar';

const SOLO_RAYA_KEYS = new Set(SOLO_RAYA_KABUPATENS.map((k) => normalizeWilayahKey(k)));

// State Peta
const aggregationLevel = ref('kecamatan'); // 'kecamatan' | 'desa'
const drillKecamatan = ref(null); // { kecamatan, kabupaten, kecKey, kabKey } | null
const hoveredRegion = ref(null);
const showSchoolsInCard = ref(false);
const regionStudentModal = ref(null);
const modalSearch = ref('');

const showSchoolDots = ref(false);
const showCampusRadius = ref(true);
const showTollGates = ref(true);
const isLayerDropdownOpen = ref(false);
const isFullscreen = ref(false);
const visibleTiers = reactive({
    basis_utama: true,
    potensial: true,
    vakum: true,
    belum_tergarap: true,
});

const isLoading = ref(true);
const isDesaLoading = ref(false);
const loadError = ref(null);
const isLegendOpenMobile = ref(false);

const kecamatanGeoData = ref(null);
const desaGeoData = ref(null);

const normDesa = normalizeDesaKey;

function isMobileScreen() {
    return typeof window !== 'undefined' && window.innerWidth < 640;
}

// Agregasi Statistik Sekolah per Kecamatan & per Desa
const geoAggregations = computed(() => {
    const byKec = new Map();
    const byDesa = new Map();
    let totalReachedSchools = 0;
    let totalStudents = 0;

    props.schools.forEach((s) => {
        const kabKey = normalizeWilayahKey(s.kabupaten);
        const kecKey = normalizeWilayahKey(s.kecamatan);
        const desaKey = normDesa(s.desa);

        const kKey = `${kecKey}_${kabKey}`;
        if (!byKec.has(kKey)) {
            byKec.set(kKey, {
                kecamatan: s.kecamatan,
                kabupaten: s.kabupaten,
                total: 0,
                reached: 0,
                basis: 0,
                potensial: 0,
                vakum: 0,
                zero: 0,
                students: 0,
                schools: [],
                studentList: [],
            });
        }
        const kBucket = byKec.get(kKey);
        kBucket.total++;
        kBucket.schools.push(s);
        kBucket.students += s.totalStudents;
        if (Array.isArray(s.students) && s.students.length > 0) {
            kBucket.studentList.push(...s.students);
        }
        if (s.tier === 'basis_utama') kBucket.basis++;
        else if (s.tier === 'potensial') kBucket.potensial++;
        else if (s.tier === 'vakum') kBucket.vakum++;
        else kBucket.zero++;
        if (s.totalStudents > 0) {
            kBucket.reached++;
            totalReachedSchools++;
        }
        totalStudents += s.totalStudents;

        if (desaKey) {
            const dFullKey = `${desaKey}_${kecKey}_${kabKey}`;
            if (!byDesa.has(dFullKey)) {
                byDesa.set(dFullKey, {
                    desa: s.desa,
                    kecamatan: s.kecamatan,
                    kabupaten: s.kabupaten,
                    total: 0,
                    reached: 0,
                    students: 0,
                    schools: [],
                    studentList: [],
                });
            }
            const dBucket = byDesa.get(dFullKey);
            dBucket.total++;
            dBucket.schools.push(s);
            dBucket.students += s.totalStudents;
            if (Array.isArray(s.students) && s.students.length > 0) {
                dBucket.studentList.push(...s.students);
            }
            if (s.totalStudents > 0) dBucket.reached++;
        }
    });

    byKec.forEach((b) => {
        b.studentList.sort((a, c) => (a.nama || '').localeCompare(c.nama || ''));
    });
    byDesa.forEach((b) => {
        b.studentList.sort((a, c) => (a.nama || '').localeCompare(c.nama || ''));
    });

    return {
        byKec,
        byDesa,
        totalReachedSchools,
        totalStudents,
        activeKecCount: Array.from(byKec.values()).filter((b) => b.reached > 0).length,
        activeDesaCount: Array.from(byDesa.values()).filter((b) => b.reached > 0).length,
    };
});

// Agregasi Domisili & Akses Wilayah (Kampus + Tol) per Kecamatan & per Desa
const welfareAggregations = computed(() => {
    const byKec = new Map();
    const byDesa = new Map();
    let reachedVillages = 0;
    let totalDomicileStudents = 0;

    props.villages.forEach((v) => {
        const kabKey = normalizeWilayahKey(v.kabupaten);
        const kecKey = normalizeWilayahKey(v.kecamatan);
        const dKey = normDesa(v.desa);

        const kKey = `${kecKey}_${kabKey}`;
        if (!byKec.has(kKey)) {
            byKec.set(kKey, {
                kecamatan: v.kecamatan,
                kabupaten: v.kabupaten,
                villageCount: 0,
                reachedVillages: 0,
                students: 0,
                campusSum: 0,
                avgCampusKm: 0,
                closestTollKm: null,
                closestTollName: '-',
                villages: [],
                studentList: [],
            });
        }

        const kb = byKec.get(kKey);
        kb.villageCount++;
        kb.students += v.totalStudents;
        kb.campusSum += Number(v.campus_km || 0);
        if (Array.isArray(v.students) && v.students.length > 0) {
            kb.studentList.push(...v.students);
        }
        if (v.toll_km !== undefined && v.toll_km !== null) {
            const tKm = Number(v.toll_km);
            if (kb.closestTollKm === null || tKm < kb.closestTollKm) {
                kb.closestTollKm = Number(tKm.toFixed(1));
                kb.closestTollName = v.toll_name || '-';
            }
        }
        kb.villages.push(v);
        if (v.totalStudents > 0) {
            kb.reachedVillages++;
            reachedVillages++;
        }
        totalDomicileStudents += v.totalStudents;

        if (dKey) {
            byDesa.set(`${dKey}_${kecKey}_${kabKey}`, v);
        }
    });

    byKec.forEach((kb) => {
        kb.avgCampusKm = kb.villageCount > 0 ? Number((kb.campusSum / kb.villageCount).toFixed(1)) : 0;
        kb.studentList.sort((a, c) => (a.nama || '').localeCompare(c.nama || ''));
    });

    return {
        byKec,
        byDesa,
        reachedVillages,
        totalVillages: props.villages.length,
        totalDomicileStudents,
    };
});

function getWelfareChoroplethColor(score) {
    const s = Number(score ?? 0);
    if (s >= 75) return '#059669';
    if (s >= 50) return '#6ee7b7';
    if (s >= 25) return '#fde68a';
    return '#fda4af';
}

// Skala Choropleth Emerald identik dengan Peta Domisili
function getChoroplethColor(studentCount) {
    if (studentCount >= 20) return '#064e3b';
    if (studentCount >= 10) return '#047857';
    if (studentCount >= 5) return '#10b981';
    if (studentCount >= 1) return '#6ee7b7';
    return '#ffffff';
}

function getKecStats(feature) {
    const kecKey = normalizeWilayahKey(feature?.properties?.kecamatan);
    const kabKey = normalizeWilayahKey(feature?.properties?.kabupaten);
    if (isWelfareMode.value) {
        return (
            welfareAggregations.value.byKec.get(`${kecKey}_${kabKey}`) || {
                kecamatan: feature?.properties?.kecamatan || '-',
                kabupaten: feature?.properties?.kabupaten || '-',
                villageCount: 0,
                reachedVillages: 0,
                students: 0,
                avgCampusKm: 0,
                closestTollKm: null,
                closestTollName: '-',
                villages: [],
                studentList: [],
            }
        );
    }
    return (
        geoAggregations.value.byKec.get(`${kecKey}_${kabKey}`) || {
            kecamatan: feature?.properties?.kecamatan || '-',
            kabupaten: feature?.properties?.kabupaten || '-',
            total: 0,
            reached: 0,
            basis: 0,
            potensial: 0,
            vakum: 0,
            zero: 0,
            students: 0,
            schools: [],
            studentList: [],
        }
    );
}

function getDesaStats(feature) {
    const dKey = normDesa(feature?.properties?.desa);
    const kecKey = normalizeWilayahKey(feature?.properties?.kecamatan);
    const kabKey = normalizeWilayahKey(feature?.properties?.kabupaten);
    if (isWelfareMode.value) {
        return (
            welfareAggregations.value.byDesa.get(`${dKey}_${kecKey}_${kabKey}`) || {
                desa: feature?.properties?.desa || '-',
                kecamatan: feature?.properties?.kecamatan || '-',
                kabupaten: feature?.properties?.kabupaten || '-',
                campus_km: null,
                campus_ring: 4,
                toll_km: null,
                toll_name: '-',
                totalStudents: 0,
                taCount: 0,
                students: [],
                schoolsCount: 0,
                tier: 'sejahtera_potensial',
            }
        );
    }
    return (
        geoAggregations.value.byDesa.get(`${dKey}_${kecKey}_${kabKey}`) || {
            desa: feature?.properties?.desa || '-',
            kecamatan: feature?.properties?.kecamatan || '-',
            kabupaten: feature?.properties?.kabupaten || '-',
            total: 0,
            reached: 0,
            students: 0,
            schools: [],
            studentList: [],
        }
    );
}

function kecamatanStyle(feature) {
    const stats = getKecStats(feature);
    return {
        fill: true,
        fillColor: getChoroplethColor(stats.students),
        weight: 0.9,
        opacity: 0.85,
        color: '#94a3b8',
        dashArray: null,
        fillOpacity: stats.students > 0 ? 0.9 : 0.65,
    };
}

function desaStyle(feature) {
    const stats = getDesaStats(feature);
    const count = isWelfareMode.value ? (stats.totalStudents || 0) : (stats.students || 0);
    return {
        fill: true,
        fillColor: getChoroplethColor(count),
        weight: 0.7,
        opacity: 0.9,
        color: count > 0 ? '#059669' : '#cbd5e1',
        dashArray: null,
        fillOpacity: count > 0 ? 0.88 : 0.55,
    };
}

function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function buildSchoolPopupElement(school) {
    const tierInfo = PSB_TIERS[school.tier] || PSB_TIERS.belum_tergarap;
    const wrapper = document.createElement('div');
    wrapper.className = 'min-w-[230px] max-w-[270px] font-sans';

    const coordNote =
        school.coord_source === 'exact'
            ? 'Titik koordinat GPS sekolah'
            : 'Perkiraan titik wilayah desa';

    wrapper.innerHTML = `
        <div class="space-y-1.5 text-slate-800">
            <div class="flex items-start justify-between gap-2">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border" style="border-color:${tierInfo.strokeHex}; color:${tierInfo.strokeHex}; background:${tierInfo.fillRgba.replace(/[\d.]+\)$/, '0.12)')}">
                    ${escapeHtml(tierInfo.label)}
                </span>
                <span class="text-[10px] font-mono text-slate-400">NPSN ${escapeHtml(school.npsn)}</span>
            </div>
            <div class="font-bold text-xs text-slate-900 leading-snug">${escapeHtml(school.nama)}</div>
            <div class="flex items-center gap-1.5 text-[10.5px] font-semibold text-slate-600">
                <span class="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200">${escapeHtml(school.bentuk)}</span>
                <span class="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200">${escapeHtml(school.status)}</span>
            </div>
            <div class="text-[11px] text-slate-500 leading-snug">
                ${escapeHtml(school.desa ? `Ds. ${school.desa}, ` : '')}Kec. ${escapeHtml(school.kecamatan)}, ${escapeHtml(school.kabupaten)}
            </div>
            <div class="pt-1.5 mt-1.5 border-t border-slate-100 flex items-center justify-between gap-2">
                <div>
                    <div class="text-[10px] text-slate-400 uppercase font-bold">Total Masuk</div>
                    <div class="text-xs font-extrabold text-slate-900">${school.totalStudents} Siswa <span class="text-[10px] font-medium text-slate-500">(${school.taCount} TA)</span></div>
                </div>
                <button type="button" data-psb-detail="1" class="px-2.5 py-1.5 rounded-lg bg-emerald-900 hover:bg-emerald-800 text-yellow-200 text-[11px] font-bold cursor-pointer transition-colors">
                    Rincian →
                </button>
            </div>
            <div class="text-[9.5px] text-slate-400 italic">${coordNote}</div>
        </div>
    `;

    const btn = wrapper.querySelector('[data-psb-detail="1"]');
    if (btn) {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            emit('select-school', school);
        });
    }

    return wrapper;
}

function resetActiveSchoolMarker() {
    if (!activeSchoolMarker) return;
    const sc = activeSchoolMarker._psbSchool;
    if (sc && activeSchoolMarker.setStyle) {
        const isZero = sc.tier === 'belum_tergarap';
        activeSchoolMarker.setStyle({
            color: '#ffffff',
            weight: isZero ? 1.2 : 2,
            radius: isZero ? (drillKecamatan.value ? 6 : 4) : (sc.tier === 'basis_utama' ? 8.5 : 7),
        });
    }
    activeSchoolMarker = null;
}

function clearHover() {
    isLockedByClick = false;
    currentHoveredKey = null;
    resetActiveSchoolMarker();
    if (activeHoverLayer && activeHoverLayer.feature) {
        if (aggregationLevel.value === 'kecamatan') {
            activeHoverLayer.setStyle(kecamatanStyle(activeHoverLayer.feature));
        } else {
            activeHoverLayer.setStyle(desaStyle(activeHoverLayer.feature));
        }
        activeHoverLayer = null;
    }
    if (singleTooltip && map && map.hasLayer(singleTooltip)) {
        map.removeLayer(singleTooltip);
    }
    hoveredRegion.value = null;
    showSchoolsInCard.value = false;
}

function selectSchoolPoint(school, marker) {
    if (!school) return;
    resetActiveSchoolMarker();
    if (activeHoverLayer && activeHoverLayer.feature) {
        if (aggregationLevel.value === 'kecamatan') {
            activeHoverLayer.setStyle(kecamatanStyle(activeHoverLayer.feature));
        } else {
            activeHoverLayer.setStyle(desaStyle(activeHoverLayer.feature));
        }
        activeHoverLayer = null;
    }
    currentHoveredKey = null;
    isLockedByClick = true;
    showSchoolsInCard.value = false;

    if (singleTooltip && map && map.hasLayer(singleTooltip)) {
        map.removeLayer(singleTooltip);
    }
    if (marker && marker.closeTooltip) {
        marker.closeTooltip();
    }

    if (marker && marker.setStyle) {
        const isZero = school.tier === 'belum_tergarap';
        const baseR = isZero ? (drillKecamatan.value ? 6 : 4) : (school.tier === 'basis_utama' ? 8.5 : 7);
        marker.setStyle({
            color: '#0f172a',
            weight: 3,
            radius: baseR + 2,
        });
        if (marker.bringToFront) marker.bringToFront();
        activeSchoolMarker = marker;
    }

    const globalTotal = geoAggregations.value.totalStudents || 0;
    const studentCount = school.totalStudents || 0;
    hoveredRegion.value = {
        type: 'school_point',
        mode: 'school',
        school,
        desa: school.desa || null,
        kecamatan: school.kecamatan || '-',
        kabupaten: school.kabupaten || '-',
        students: studentCount,
        percentage: globalTotal > 0 ? Math.round((studentCount / globalTotal) * 100) : 0,
        studentList: school.students || [],
    };
}

function selectRegionFeature(type, feature, layer, latlng = null, isClick = false) {
    resetActiveSchoolMarker();
    const kecKey = normalizeWilayahKey(feature?.properties?.kecamatan);
    const kabKey = normalizeWilayahKey(feature?.properties?.kabupaten);
    const dKey = type === 'desa' ? normDesa(feature?.properties?.desa) : '';
    const key = type === 'desa' ? `d_${dKey}_${kecKey}_${kabKey}` : `k_${kecKey}_${kabKey}`;

    if (currentHoveredKey === key) {
        if (isClick) {
            if (singleTooltip && map && map.hasLayer(singleTooltip)) {
                map.removeLayer(singleTooltip);
            }
        } else if (!isMobileScreen() && latlng && singleTooltip && map && map.hasLayer(singleTooltip)) {
            singleTooltip.setLatLng(latlng);
        }
        return;
    }

    if (activeHoverLayer && activeHoverLayer !== layer && activeHoverLayer.feature) {
        if (aggregationLevel.value === 'kecamatan') {
            activeHoverLayer.setStyle(kecamatanStyle(activeHoverLayer.feature));
        } else {
            activeHoverLayer.setStyle(desaStyle(activeHoverLayer.feature));
        }
    }

    currentHoveredKey = key;
    activeHoverLayer = layer;
    showSchoolsInCard.value = false;

    layer.setStyle({
        fill: true,
        weight: 2.5,
        color: '#047857',
        fillOpacity: 1,
    });
    if (layer.bringToFront) layer.bringToFront();
    if (schoolPointsLayer && schoolPointsLayer.bringToFront) schoolPointsLayer.bringToFront();

    const stats = type === 'desa' ? getDesaStats(feature) : getKecStats(feature);
    const isWelfare = isWelfareMode.value;
    const studentCount = isWelfare && type === 'desa' ? (stats.totalStudents || 0) : (stats.students || 0);
    const studentList = isWelfare && type === 'desa' ? (stats.students || []) : (stats.studentList || []);
    const globalTotal = isWelfare
        ? (welfareAggregations.value.totalDomicileStudents || 0)
        : (geoAggregations.value.totalStudents || 0);
    const percentage = globalTotal > 0 ? Math.round((studentCount / globalTotal) * 100) : 0;

    hoveredRegion.value = {
        type,
        mode: isWelfare ? 'welfare' : 'school',
        desa: type === 'desa' ? feature?.properties?.desa || '-' : null,
        kecamatan: feature?.properties?.kecamatan || '-',
        kabupaten: feature?.properties?.kabupaten || '-',
        students: studentCount,
        percentage,
        studentList,
        total: stats.total || 0,
        reached: stats.reached || 0,
        schools: [...(stats.schools || [])].sort(
            (a, b) => b.totalStudents - a.totalStudents || a.nama.localeCompare(b.nama, 'id')
        ),
        village: isWelfare && type === 'desa' ? stats : null,
        campus_km: stats.campus_km ?? null,
        campus_ring: stats.campus_ring ?? 4,
        toll_km: stats.toll_km ?? null,
        toll_name: stats.toll_name || '-',
        avgCampusKm: stats.avgCampusKm ?? null,
        villageCount: stats.villageCount || 0,
        reachedVillages: stats.reachedVillages || 0,
        featureLayer: layer,
    };

    if (isClick) {
        if (singleTooltip && map && map.hasLayer(singleTooltip)) {
            map.removeLayer(singleTooltip);
        }
        return;
    }

    if (!isMobileScreen() && singleTooltip && map) {
        const pos = latlng || layer.getBounds().getCenter();
        const headingLabel = type === 'desa' ? 'Desa / Kelurahan' : 'Kecamatan';
        const titleText = type === 'desa' ? `Desa ${feature.properties.desa}` : `Kec. ${feature.properties.kecamatan}`;
        const subText =
            type === 'desa'
                ? `Kec. ${feature.properties.kecamatan}, ${feature.properties.kabupaten}`
                : `Kab/Kota ${feature.properties.kabupaten}`;

        const metricRow = isWelfare
            ? (type === 'desa'
                ? `<div class="mt-1.5 pt-1 border-t border-slate-200/80 space-y-1">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-bold text-emerald-800">${studentCount} Siswa Domisili</span>
                            <span class="text-[11px] font-bold text-sky-700">${stats.campus_km ?? '-'} km ke Sekolah</span>
                        </div>
                        <div class="text-[10.5px] text-slate-500">
                            Tol terdekat: <strong>${escapeHtml(stats.toll_name || '-')}</strong> (${stats.toll_km ?? '-'} km)
                        </div>
                   </div>`
                : `<div class="mt-1.5 pt-1 border-t border-slate-200/80 space-y-1">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-bold text-emerald-800">${studentCount} Siswa Domisili</span>
                            <span class="text-[11px] font-bold text-sky-700">Rata ${stats.avgCampusKm ?? '-'} km</span>
                        </div>
                        <div class="text-[10.5px] text-slate-500">
                            ${stats.reachedVillages} / ${stats.villageCount} desa terisi siswa
                        </div>
                   </div>`)
            : `<div class="mt-1.5 pt-1 border-t border-slate-200/80 flex items-center justify-between gap-3">
                    <span class="font-bold text-emerald-800">${studentCount} Siswa</span>
                    <span class="text-[11px] font-semibold text-slate-600">${stats.reached} / ${stats.total} SD tergarap</span>
               </div>`;

        singleTooltip.setLatLng(pos).setContent(`
            <div class="px-2.5 py-1.5 text-xs text-slate-800 leading-tight min-w-[200px]">
                <div class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider mb-0.5">${headingLabel}</div>
                <div class="font-extrabold text-slate-900 text-[13px]">${escapeHtml(titleText)}</div>
                <div class="text-[11px] text-slate-500 font-medium">${escapeHtml(subText)}</div>
                ${metricRow}
            </div>
        `);
        if (!map.hasLayer(singleTooltip)) {
            singleTooltip.addTo(map);
        }
    } else if (singleTooltip && map && map.hasLayer(singleTooltip)) {
        map.removeLayer(singleTooltip);
    }
}

function openRegionStudentModal(region) {
    if (!region) return;
    regionStudentModal.value = region;
    modalSearch.value = '';
}

function closeRegionStudentModal() {
    regionStudentModal.value = null;
    modalSearch.value = '';
}

const modalFilteredStudents = computed(() => {
    const region = regionStudentModal.value;
    if (!region) return [];
    const list = region.studentList || [];
    const q = modalSearch.value.trim().toLowerCase();
    if (!q) return list;
    return list.filter(
        (st) =>
            (st.nama || '').toLowerCase().includes(q) ||
            (st.nisn || '').toLowerCase().includes(q) ||
            (st.sekolah_asal || '').toLowerCase().includes(q) ||
            (st.dusun || '').toLowerCase().includes(q) ||
            (st.desa || '').toLowerCase().includes(q) ||
            (st.program || '').toLowerCase().includes(q)
    );
});

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
        if (regionStudentModal.value) {
            closeRegionStudentModal();
        } else if (isFullscreen.value) {
            toggleFullscreen(false);
        }
    }
}

function attachFeatureBbox(features) {
    (features || []).forEach((f) => {
        if (f._bbox || !f.geometry?.coordinates) return;
        let minLng = Infinity;
        let maxLng = -Infinity;
        let minLat = Infinity;
        let maxLat = -Infinity;
        function scan(coords) {
            if (typeof coords[0] === 'number') {
                const cLng = coords[0];
                const cLat = coords[1];
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

function isPointInRing(lng, lat, ring) {
    let inside = false;
    for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
        const xi = ring[i][0];
        const yi = ring[i][1];
        const xj = ring[j][0];
        const yj = ring[j][1];
        const intersect =
            (yi > lat) !== (yj > lat) &&
            lng < ((xj - xi) * (lat - yi)) / (yj - yi) + xi;
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
    }
    if (type === 'MultiPolygon') {
        for (let p = 0; p < coordinates.length; p++) {
            if (isPointInPolygonGeom(lng, lat, coordinates[p], 'Polygon')) return true;
        }
    }
    return false;
}

function findActivePolygonAtLatLng(latlng) {
    if (!latlng) return null;
    const targetLayer = aggregationLevel.value === 'desa' ? desaLayer : kecamatanLayer;
    if (!targetLayer) return null;
    const lng = latlng.lng;
    const lat = latlng.lat;

    let matched = null;
    targetLayer.eachLayer((layer) => {
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

async function loadDesaGeoJson() {
    if (desaGeoData.value) return desaGeoData.value;
    if (desaLoadingPromise) return desaLoadingPromise;

    isDesaLoading.value = true;
    desaLoadingPromise = (async () => {
        try {
            const res = await fetch('/data/solo-raya-desa.geojson?v=20261123grobogan');
            if (!res.ok) throw new Error('Gagal memuat batas desa');
            const raw = await res.json();
            raw.features = (raw.features || []).filter((f) =>
                SOLO_RAYA_KEYS.has(normalizeWilayahKey(f.properties?.kabupaten))
            );
            attachFeatureBbox(raw.features);
            desaGeoData.value = raw;
            return raw;
        } catch (err) {
            console.error(err);
            return null;
        } finally {
            isDesaLoading.value = false;
            desaLoadingPromise = null;
        }
    })();
    return desaLoadingPromise;
}

function buildDesaLayer() {
    if (!map || !desaGeoData.value) return;
    if (desaLayer) {
        desaLayer.remove();
        desaLayer = null;
    }
    if (desaCanvasRenderer) {
        desaCanvasRenderer.remove();
        desaCanvasRenderer = null;
    }

    let features = desaGeoData.value.features || [];
    if (drillKecamatan.value) {
        features = features.filter(
            (f) =>
                normalizeWilayahKey(f.properties?.kecamatan) === drillKecamatan.value.kecKey &&
                normalizeWilayahKey(f.properties?.kabupaten) === drillKecamatan.value.kabKey
        );
    } else if (props.selectedKabupaten && props.selectedKabupaten !== 'Semua') {
        const kabKey = normalizeWilayahKey(props.selectedKabupaten);
        features = features.filter((f) => normalizeWilayahKey(f.properties?.kabupaten) === kabKey);
    }

    // Saat drill-down (10-25 desa), gunakan SVG agar klik/hover 100% akurat; saat seluruh Solo Raya gunakan Canvas
    const useSvgForDesa = Boolean(drillKecamatan.value) || features.length <= 150;
    if (!useSvgForDesa) {
        desaCanvasRenderer = L.canvas({ padding: 0.25 });
    }

    desaLayer = L.geoJSON(
        { type: 'FeatureCollection', features },
        {
            ...(useSvgForDesa ? {} : { renderer: desaCanvasRenderer }),
            style: desaStyle,
            onEachFeature: (feature, layer) => {
                layer.on({
                    mouseover: (e) => {
                        if (aggregationLevel.value !== 'desa' || isLockedByClick) return;
                        selectRegionFeature('desa', feature, layer, e.latlng, false);
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
                        if (e.originalEvent) L.DomEvent.stopPropagation(e.originalEvent);
                        lastInteractionTime = Date.now();
                        isLockedByClick = true;
                        selectRegionFeature('desa', feature, layer, e.latlng, true);
                    },
                });
            },
        }
    );

    if (aggregationLevel.value === 'desa') {
        desaLayer.addTo(map);
    }
}

function renderSchoolDots() {
    if (!map || !schoolPointsLayer) return;
    schoolPointsLayer.clearLayers();
    currentDotCandidates = [];

    if (isWelfareMode.value || !showSchoolDots.value) return;

    // Pada level Kecamatan seluruh Solo Raya, gambar hanya sekolah sesuai filter/tier agar ringan
    // Pada level Drill-down Kecamatan, tampilkan seluruh titik SD/MI/PKBM dalam kecamatan tersebut
    let candidates = props.schools.filter(
        (s) =>
            visibleTiers[s.tier] &&
            typeof s.lat === 'number' &&
            typeof s.lng === 'number' &&
            !Number.isNaN(s.lat) &&
            !Number.isNaN(s.lng)
    );

    if (drillKecamatan.value) {
        candidates = candidates.filter(
            (s) =>
                normalizeWilayahKey(s.kecamatan) === drillKecamatan.value.kecKey &&
                normalizeWilayahKey(s.kabupaten) === drillKecamatan.value.kabKey
        );
    }

    // Urutkan supaya Basis Utama di lapisan paling atas
    const order = { belum_tergarap: 0, vakum: 1, potensial: 2, basis_utama: 3 };
    candidates.sort((a, b) => (order[a.tier] || 0) - (order[b.tier] || 0));
    currentDotCandidates = candidates;

    candidates.forEach((school) => {
        const tier = PSB_TIERS[school.tier] || PSB_TIERS.belum_tergarap;
        const isZero = school.tier === 'belum_tergarap';
        const marker = L.circleMarker([school.lat, school.lng], {
            renderer: pointsSvgRenderer,
            pane: 'psbPointsPane',
            radius: isZero ? (drillKecamatan.value ? 6 : 4) : (school.tier === 'basis_utama' ? 8.5 : 7),
            fillColor: tier.hex,
            color: '#ffffff',
            weight: isZero ? 1.2 : 2,
            opacity: 0.95,
            fillOpacity: isZero ? 0.72 : 0.94,
        });

        marker._psbSchool = school;
        marker.bindTooltip(
            `${school.nama} (${school.totalStudents} Siswa)`,
            { direction: 'top', offset: [0, -6] }
        );
        marker.on('click', (e) => {
            if (e.originalEvent) L.DomEvent.stopPropagation(e.originalEvent);
            lastInteractionTime = Date.now();
            lastMarkerClickTime = Date.now();
            selectSchoolPoint(school, marker);
        });
        marker.addTo(schoolPointsLayer);
    });

    if (schoolPointsLayer.bringToFront) {
        schoolPointsLayer.bringToFront();
    }
}

function renderCampusRadiusOverlay() {
    if (!map || !radiusCirclesLayer) return;
    radiusCirclesLayer.clearLayers();
    if (!isWelfareMode.value || !showCampusRadius.value) return;

    const rings = [
        { km: 5, color: '#0369a1', label: 'Radius 5 km' },
        { km: 10, color: '#0284c7', label: 'Radius 10 km' },
        { km: 15, color: '#64748b', label: 'Radius 15 km' },
    ];

    rings.forEach(({ km, color, label }) => {
        L.circle(SCHOOL_COORDS, {
            renderer: bordersSvgRenderer,
            pane: 'psbBordersPane',
            radius: km * 1000,
            color,
            weight: 1.6,
            dashArray: '5, 5',
            opacity: 0.78,
            fill: false,
            interactive: false,
        }).addTo(radiusCirclesLayer);

        const latOffset = km / 111.32;
        L.marker([SCHOOL_COORDS[0] + latOffset, SCHOOL_COORDS[1]], {
            interactive: false,
            icon: L.divIcon({
                className: 'psb-radius-label',
                html: `<span style="display:inline-block;padding:1px 6px;border-radius:9999px;background:rgba(255,255,255,0.92);border:1px solid ${color};color:${color};font-size:10px;font-weight:800;white-space:nowrap;box-shadow:0 1px 3px rgba(0,0,0,0.12);">${label}</span>`,
                iconSize: [72, 18],
                iconAnchor: [36, 9],
            }),
        }).addTo(radiusCirclesLayer);
    });
}

function renderTollGatesOverlay() {
    if (!map || !tollGatesLayer) return;
    tollGatesLayer.clearLayers();
    if (!isWelfareMode.value || !showTollGates.value || !Array.isArray(props.tollGates)) return;

    props.tollGates.forEach((gate) => {
        if (typeof gate?.lat !== 'number' || typeof gate?.lng !== 'number') return;
        const icon = L.divIcon({
            className: 'psb-toll-marker',
            html: `<div style="width:22px;height:22px;border-radius:6px;background:#4338ca;color:#fef08a;border:2px solid #ffffff;box-shadow:0 2px 6px rgba(30,27,75,0.4);display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:900;cursor:pointer;" title="${escapeHtml(gate.name)}">TOL</div>`,
            iconSize: [22, 22],
            iconAnchor: [11, 11],
            popupAnchor: [0, -10],
        });

        const marker = L.marker([gate.lat, gate.lng], {
            icon,
            zIndexOffset: 900,
            title: gate.name,
        });
        marker.bindTooltip(`${gate.name}`, { direction: 'top', offset: [0, -8] });
        marker.bindPopup(`
            <div style="padding:4px;min-width:190px;font-family:'Plus Jakarta Sans',system-ui,sans-serif;">
                <div style="font-size:10px;font-weight:800;color:#4338ca;margin-bottom:2px;">🛣️ GERBANG TOL SOLO RAYA</div>
                <div style="font-weight:800;font-size:12.5px;color:#0f172a;">${escapeHtml(gate.name)}</div>
                <div style="font-size:11px;color:#475569;margin-top:2px;">Ruas: ${escapeHtml(gate.corridor || '-')}</div>
            </div>
        `);
        marker.on('click', (e) => {
            if (e.originalEvent) L.DomEvent.stopPropagation(e.originalEvent);
            lastMarkerClickTime = Date.now();
        });
        marker.addTo(tollGatesLayer);
    });
}

function renderMapLabels() {
    if (!map) return;

    const zoom = map.getZoom();
    const viewBounds = map.getBounds().pad(0.08);

    // 1. Label Nama Kecamatan / Nama Desa
    if (areaLabelLayer) {
        areaLabelLayer.clearLayers();

        if (aggregationLevel.value === 'kecamatan' && kecamatanGeoData.value?.features) {
            const minGapPx = zoom >= 11 ? 26 : (zoom >= 10 ? 34 : 44);
            const items = [];

            kecamatanGeoData.value.features.forEach((f) => {
                const lat = Number(f.properties?.lat);
                const lng = Number(f.properties?.lng);
                const name = f.properties?.kecamatan;
                if (!name || !lat || !lng || !viewBounds.contains([lat, lng])) return;
                const stats = getKecStats(f);
                items.push({ lat, lng, name, students: stats.students || 0, welfareScore: stats.avgWelfare || 0 });
            });

            // Prioritaskan kecamatan dengan siswa masuk agar labelnya selalu muncul dulu
            items.sort((a, b) => b.students - a.students);

            const placedPts = [];
            items.forEach((item) => {
                const pt = map.latLngToContainerPoint([item.lat, item.lng]);
                const crowded = placedPts.some((p) => Math.hypot(pt.x - p.x, pt.y - p.y) < minGapPx);
                if (crowded) return;
                placedPts.push(pt);

                const isDarkBg = item.students >= 10;
                const textColor = isDarkBg ? '#ffffff' : '#1e293b';
                const shadow = isDarkBg
                    ? '0 1px 2px rgba(6,78,59,0.95), 0 0 3px rgba(6,78,59,0.85)'
                    : '0 0 3px rgba(255,255,255,0.95), 0 0 4px rgba(255,255,255,0.95)';
                const fontSize = zoom >= 11 ? '10px' : '9px';

                L.marker([item.lat, item.lng], {
                    interactive: false,
                    icon: L.divIcon({
                        className: 'psb-map-text-label',
                        iconSize: [0, 0],
                        html: `<div style="display:inline-block; width:max-content; transform:translate(-50%,-50%); white-space:nowrap; font-size:${fontSize}; font-weight:700; color:${textColor}; text-shadow:${shadow}; pointer-events:none; letter-spacing:0.01em;">${escapeHtml(item.name)}</div>`,
                    }),
                }).addTo(areaLabelLayer);
            });
        } else if (aggregationLevel.value === 'desa' && desaGeoData.value?.features) {
            // Tampilkan semua nama desa saat masuk 1 kecamatan, atau saat zoom >= 12 di mode seluruh Solo Raya
            const shouldRenderDesa = Boolean(drillKecamatan.value) || zoom >= 12;
            if (shouldRenderDesa) {
                const minGapPx = zoom >= 13 ? 24 : 36;
                let features = desaGeoData.value.features;
                if (drillKecamatan.value) {
                    features = features.filter(
                        (f) =>
                            normalizeWilayahKey(f.properties?.kecamatan) === drillKecamatan.value.kecKey &&
                            normalizeWilayahKey(f.properties?.kabupaten) === drillKecamatan.value.kabKey
                    );
                } else if (props.selectedKabupaten && props.selectedKabupaten !== 'Semua') {
                    const kabKey = normalizeWilayahKey(props.selectedKabupaten);
                    features = features.filter((f) => normalizeWilayahKey(f.properties?.kabupaten) === kabKey);
                }

                const items = [];
                features.forEach((f) => {
                    const lat = Number(f.properties?.lat);
                    const lng = Number(f.properties?.lng);
                    const name = f.properties?.desa;
                    if (!name || !lat || !lng || !viewBounds.contains([lat, lng])) return;
                    const stats = getDesaStats(f);
                    items.push({
                        lat,
                        lng,
                        name,
                        students: isWelfareMode.value ? (stats.totalStudents || 0) : (stats.students || 0),
                        welfareScore: stats.welfare_score ?? 0,
                    });
                });

                items.sort((a, b) => b.students - a.students);

                const placedPts = [];
                items.forEach((item) => {
                    const pt = map.latLngToContainerPoint([item.lat, item.lng]);
                    const crowded = placedPts.some((p) => Math.hypot(pt.x - p.x, pt.y - p.y) < minGapPx);
                    if (crowded) return;
                    placedPts.push(pt);

                    const isDarkBg = item.students >= 10;
                    const textColor = isDarkBg ? '#ffffff' : '#334155';
                    const shadow = isDarkBg
                        ? '0 1px 2px rgba(6,78,59,0.95), 0 0 3px rgba(6,78,59,0.85)'
                        : '0 0 3px rgba(255,255,255,0.98), 0 0 4px rgba(255,255,255,0.98)';

                    L.marker([item.lat, item.lng], {
                        interactive: false,
                        icon: L.divIcon({
                            className: 'psb-map-text-label',
                            iconSize: [0, 0],
                            html: `<div style="display:inline-block; width:max-content; transform:translate(-50%,-50%); white-space:nowrap; font-size:9.5px; font-weight:700; color:${textColor}; text-shadow:${shadow}; pointer-events:none;">${escapeHtml(item.name)}</div>`,
                        }),
                    }).addTo(areaLabelLayer);
                });
            }
        }
    }

    // 2. Label Nama Sekolah pada Tiap Titik (hanya muncul saat di-zoom agar tidak sesak)
    if (schoolLabelLayer) {
        schoolLabelLayer.clearLayers();

        const shouldShowSchoolNames =
            !isWelfareMode.value &&
            showSchoolDots.value &&
            currentDotCandidates.length > 0 &&
            (zoom >= 13 || (Boolean(drillKecamatan.value) && zoom >= 12));

        if (!shouldShowSchoolNames) return;

        const visibleSchools = currentDotCandidates.filter((s) => viewBounds.contains([s.lat, s.lng]));
        // Prioritaskan Basis -> Potensial -> Vakum -> Belum Tergarap (reverse dari array urut render)
        const priorityList = [...visibleSchools].reverse();

        const boxGapX = zoom >= 15 ? 64 : (zoom >= 14 ? 82 : 105);
        const boxGapY = zoom >= 15 ? 14 : 18;
        const placedBoxes = [];

        const lightLabelPalette = {
            basis_utama: '#a7f3d0',
            potensial: '#fde68a',
            vakum: '#fecdd3',
            belum_tergarap: '#ffffff',
        };

        priorityList.forEach((school) => {
            const pt = map.latLngToContainerPoint([school.lat, school.lng]);
            const collides = placedBoxes.some(
                (b) => Math.abs(pt.x - b.x) < boxGapX && Math.abs(pt.y - b.y) < boxGapY
            );
            if (collides) return;
            placedBoxes.push(pt);

            const tier = PSB_TIERS[school.tier] || PSB_TIERS.belum_tergarap;
            const shortName = school.nama.length > 26 ? `${school.nama.slice(0, 25)}…` : school.nama;

            // Tentukan gelap/terangnya poligon latar di titik sekolah berada agar teks selalu kontras
            const polygonHit = findActivePolygonAtLatLng({ lat: school.lat, lng: school.lng });
            let bgStudents = 0;
            if (polygonHit?.feature) {
                const polyStats = aggregationLevel.value === 'desa'
                    ? getDesaStats(polygonHit.feature)
                    : getKecStats(polygonHit.feature);
                bgStudents = Number(polyStats?.students || 0);
            } else if (aggregationLevel.value === 'desa') {
                const dKey = `${normDesa(school.desa)}_${normalizeWilayahKey(school.kecamatan)}_${normalizeWilayahKey(school.kabupaten)}`;
                bgStudents = Number(geoAggregations.value.byDesa.get(dKey)?.students || 0);
            } else {
                const kKey = `${normalizeWilayahKey(school.kecamatan)}_${normalizeWilayahKey(school.kabupaten)}`;
                bgStudents = Number(geoAggregations.value.byKec.get(kKey)?.students || 0);
            }

            const isDarkBg = bgStudents >= 10;
            const labelColor = isDarkBg
                ? (lightLabelPalette[school.tier] || lightLabelPalette.belum_tergarap)
                : tier.strokeHex;
            const labelStroke = isDarkBg
                ? 'rgba(6,78,59,0.92)'
                : 'rgba(255,255,255,0.96)';

            L.marker([school.lat, school.lng], {
                interactive: false,
                icon: L.divIcon({
                    className: 'psb-school-point-label',
                    iconSize: [0, 0],
                    html: `<div style="display:inline-block; width:max-content; transform:translate(-50%, 7px); white-space:nowrap; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; font-size:8px; font-weight:700; color:${labelColor}; -webkit-text-stroke:2.5px ${labelStroke}; paint-order:stroke fill; text-shadow:0 1px 2px ${labelStroke}; letter-spacing:-0.02em; pointer-events:none;">${escapeHtml(shortName)}</div>`,
                }),
            }).addTo(schoolLabelLayer);
        });
    }
}

async function drillIntoKecamatan(kecamatanName, kabupatenName, kecFeatureLayer = null) {
    const kecKey = normalizeWilayahKey(kecamatanName);
    const kabKey = normalizeWilayahKey(kabupatenName);

    clearHover();
    drillKecamatan.value = {
        kecamatan: kecamatanName,
        kabupaten: kabupatenName,
        kecKey,
        kabKey,
    };
    aggregationLevel.value = 'desa';
    showSchoolDots.value = true;

    if (!desaGeoData.value) {
        await loadDesaGeoJson();
    }

    updateLayersAndFocus(kecFeatureLayer);
}

async function backToAllKecamatan() {
    clearHover();
    drillKecamatan.value = null;
    aggregationLevel.value = 'kecamatan';
    showSchoolDots.value = false;
    emit('reset-kecamatan');
    updateLayersAndFocus();
    resetZoom();
}

async function setAggregation(level) {
    if (aggregationLevel.value === level && !drillKecamatan.value) return;
    clearHover();
    if (level === 'kecamatan') {
        drillKecamatan.value = null;
        aggregationLevel.value = 'kecamatan';
        showSchoolDots.value = false;
    } else {
        aggregationLevel.value = 'desa';
        if (!desaGeoData.value) {
            await loadDesaGeoJson();
        }
    }
    updateLayersAndFocus();
}

function updateLayersAndFocus(specificKecLayer = null) {
    if (!map) return;

    if (aggregationLevel.value === 'kecamatan') {
        if (desaLayer && map.hasLayer(desaLayer)) {
            map.removeLayer(desaLayer);
        }
        if (kecamatanLayer) {
            if (!map.hasLayer(kecamatanLayer)) {
                kecamatanLayer.addTo(map);
            }
            kecamatanLayer.eachLayer((layer) => {
                layer.setStyle(kecamatanStyle(layer.feature));
            });
        }
    } else {
        if (kecamatanLayer && map.hasLayer(kecamatanLayer)) {
            map.removeLayer(kecamatanLayer);
        }
        if (desaGeoData.value) {
            buildDesaLayer();
        }
    }

    renderSchoolDots();
    renderCampusRadiusOverlay();
    renderTollGatesOverlay();
    renderMapLabels();

    if (specificKecLayer && specificKecLayer.getBounds().isValid()) {
        map.fitBounds(specificKecLayer.getBounds(), { padding: [24, 24], animate: true });
    } else if (drillKecamatan.value && desaLayer && desaLayer.getBounds().isValid()) {
        map.fitBounds(desaLayer.getBounds(), { padding: [24, 24], animate: true });
    }
}

function resetZoom() {
    clearHover();
    if (!map) return;

    if (drillKecamatan.value && desaLayer && desaLayer.getBounds().isValid()) {
        map.fitBounds(desaLayer.getBounds(), { padding: [24, 24], animate: true });
        return;
    }

    if (props.selectedKabupaten && props.selectedKabupaten !== 'Semua' && kecamatanLayer) {
        const kabKey = normalizeWilayahKey(props.selectedKabupaten);
        const matchingBounds = L.latLngBounds([]);
        kecamatanLayer.eachLayer((layer) => {
            if (normalizeWilayahKey(layer.feature?.properties?.kabupaten) === kabKey) {
                matchingBounds.extend(layer.getBounds());
            }
        });
        if (matchingBounds.isValid()) {
            map.fitBounds(matchingBounds, { padding: [20, 20], animate: true });
            return;
        }
    }

    if (kecamatanLayer && kecamatanLayer.getBounds().isValid()) {
        map.fitBounds(kecamatanLayer.getBounds(), { padding: [20, 20], animate: true });
    }
}

async function initMap() {
    try {
        isLoading.value = true;
        loadError.value = null;

        const [kecRes, kabRes] = await Promise.all([
            fetch('/data/solo-raya-kecamatan.geojson?v=20261123grobogan'),
            fetch('/data/solo-raya-kabupaten.geojson?v=20261123grobogan'),
        ]);

        if (!kecRes.ok) throw new Error('Gagal memuat peta kecamatan Solo Raya & Grobogan.');
        const kecJson = await kecRes.json();
        kecJson.features = (kecJson.features || []).filter((f) =>
            SOLO_RAYA_KEYS.has(normalizeWilayahKey(f.properties?.kabupaten))
        );
        attachFeatureBbox(kecJson.features);
        kecamatanGeoData.value = kecJson;

        let kabJson = null;
        if (kabRes.ok) {
            kabJson = await kabRes.json();
            kabJson.features = (kabJson.features || []).filter((f) =>
                SOLO_RAYA_KEYS.has(normalizeWilayahKey(f.properties?.kabupaten || f.properties?.WADMKK || f.properties?.name))
            );
        }

        await nextTick();
        if (!mapContainerRef.value) return;

        map = L.map(mapContainerRef.value, {
            zoomControl: false,
            attributionControl: false,
            minZoom: 8,
            maxZoom: 16,
            scrollWheelZoom: true,
            tapTolerance: 25,
        });

        L.control.zoom({ position: 'topright' }).addTo(map);

        // Panes terpisah agar batas kabupaten/radius/titik SD tidak memblokir hover/klik poligon desa
        const bordersPane = map.createPane('psbBordersPane');
        bordersPane.style.zIndex = '420';
        bordersPane.style.pointerEvents = 'none';

        const pointsPane = map.createPane('psbPointsPane');
        pointsPane.style.zIndex = '440';
        pointsPane.style.pointerEvents = 'none';

        singleTooltip = L.tooltip({
            direction: 'auto',
            className: 'leaflet-custom-tooltip',
            offset: [0, -10],
            opacity: 0.98,
        });

        // Layer 1: Poligon Kecamatan (Choropleth Emerald)
        kecamatanLayer = L.geoJSON(kecJson, {
            style: kecamatanStyle,
            onEachFeature: (feature, layer) => {
                layer.on({
                    mouseover: (e) => {
                        if (aggregationLevel.value !== 'kecamatan' || isLockedByClick) return;
                        selectRegionFeature('kecamatan', feature, layer, e.latlng, false);
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
                        if (e.originalEvent) L.DomEvent.stopPropagation(e.originalEvent);
                        lastInteractionTime = Date.now();
                        isLockedByClick = true;
                        selectRegionFeature('kecamatan', feature, layer, e.latlng, true);
                    },
                });
            },
        }).addTo(map);

        map.on('click', (e) => {
            if (Date.now() - lastMarkerClickTime < 150) return;
            const hit = findActivePolygonAtLatLng(e.latlng);
            if (hit && hit.layer) {
                lastInteractionTime = Date.now();
                isLockedByClick = true;
                selectRegionFeature(aggregationLevel.value, hit.feature, hit.layer, e.latlng, true);
                return;
            }
            clearHover();
        });

        // Layer 2: Batas Kabupaten putus-putus
        bordersSvgRenderer = L.svg({ pane: 'psbBordersPane', padding: 0.3 });
        if (kabJson) {
            kabupatenLayer = L.geoJSON(kabJson, {
                renderer: bordersSvgRenderer,
                pane: 'psbBordersPane',
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

        // Layer 3: Titik Lokasi Kampus Sekolah
        const schoolIcon = L.divIcon({
            className: 'custom-school-marker',
            html: `
                <div style="position: relative; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                    <div style="position: absolute; inset: 0; border-radius: 9999px; background-color: #ef4444; opacity: 0.42; animation: ping 1.8s cubic-bezier(0, 0, 0.2, 1) infinite;"></div>
                    <div style="position: relative; width: 28px; height: 28px; border-radius: 9999px; background: linear-gradient(135deg, #e11d48, #be123c); color: white; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(190, 18, 60, 0.45); border: 2px solid white;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
                        </svg>
                    </div>
                </div>
            `,
            iconSize: [34, 34],
            iconAnchor: [17, 17],
            popupAnchor: [0, -16],
        });

        campusMarker = L.marker(SCHOOL_COORDS, {
            icon: schoolIcon,
            zIndexOffset: 1200,
            title: SCHOOL_NAME,
        }).addTo(map);

        campusMarker.bindPopup(`
            <div style="padding: 4px; min-width: 210px; font-family: 'Plus Jakarta Sans', system-ui, sans-serif;">
                <div style="font-size: 10.5px; font-weight: 800; color: #be123c; margin-bottom: 3px;">📍 LOKASI SEKOLAH</div>
                <div style="font-weight: 800; font-size: 12px; color: #0f172a;">${SCHOOL_NAME}</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 3px;">${SCHOOL_ADDRESS}</div>
            </div>
        `);
        campusMarker.on('click', (e) => {
            if (e.originalEvent) L.DomEvent.stopPropagation(e.originalEvent);
            lastMarkerClickTime = Date.now();
        });

        // Layer 4: Titik SD/MI/PKBM Overlay (SVG di psbPointsPane agar area kosong meneruskan klik ke poligon desa), Ring Kampus, Gerbang Tol & Label
        pointsSvgRenderer = L.svg({ pane: 'psbPointsPane', padding: 0.3 });
        radiusCirclesLayer = L.layerGroup().addTo(map);
        tollGatesLayer = L.layerGroup().addTo(map);
        schoolPointsLayer = L.layerGroup().addTo(map);
        areaLabelLayer = L.layerGroup().addTo(map);
        schoolLabelLayer = L.layerGroup().addTo(map);

        renderCampusRadiusOverlay();
        renderTollGatesOverlay();

        map.on('zoomend moveend', renderMapLabels);

        // Preload desa GeoJSON di background agar klik kecamatan langsung instan
        loadDesaGeoJson();
    } catch (err) {
        loadError.value = err?.message || 'Gagal memuat peta wilayah Solo Raya & Grobogan.';
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

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
    initMap();
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeyDown);
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
    if (map) {
        map.remove();
        map = null;
    }
});

watch(
    [() => props.mode, () => props.schools, () => props.villages],
    () => {
        isLayerDropdownOpen.value = false;
        clearHover();
        updateLayersAndFocus();
    },
    { deep: false }
);

watch(
    () => [
        showSchoolDots.value,
        visibleTiers.basis_utama,
        visibleTiers.potensial,
        visibleTiers.vakum,
        visibleTiers.belum_tergarap,
    ],
    () => {
        renderSchoolDots();
        renderMapLabels();
    },
    { deep: false }
);

watch(
    () => [props.tollGates, showCampusRadius.value, showTollGates.value],
    () => {
        renderCampusRadiusOverlay();
        renderTollGatesOverlay();
    },
    { deep: false }
);

watch(
    () => [props.selectedKabupaten, props.selectedKecamatan],
    async ([newKab, newKec]) => {
        if (newKec && newKec !== 'Semua') {
            const targetKab = newKab !== 'Semua' ? newKab : (props.schools[0]?.kabupaten || '');
            await drillIntoKecamatan(newKec, targetKab);
        } else {
            drillKecamatan.value = null;
            clearHover();
            if (aggregationLevel.value === 'desa') {
                aggregationLevel.value = 'kecamatan';
                showSchoolDots.value = false;
            }
            updateLayersAndFocus();
            resetZoom();
        }
    }
);
</script>

<template>
    <div
        :class="[
            'bg-white overflow-hidden transition-colors',
            isFullscreen
                ? 'fixed inset-0 z-[900] w-screen h-screen p-3 sm:p-5 flex flex-col'
                : 'rounded-3xl border border-slate-200/90 p-4 sm:p-6 shadow-xs w-full max-w-full min-w-0',
        ]"
    >
        <!-- Header: Identik dengan Peta Domisili Stat Siswa -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3 sm:mb-4 pb-3 border-b border-slate-100 shrink-0">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block shrink-0"></span>
                <div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                        {{ isWelfareMode ? 'Peta Domisili Siswa, Radius Sekolah & Tol (Solo Raya & Grobogan)' : 'Peta Sebaran Sekolah Asal & Wilayah (Solo Raya & Grobogan)' }}
                    </h3>
                    <p class="text-[11px] text-slate-500 font-medium mt-0.5">
                        <template v-if="isWelfareMode">
                            <template v-if="drillKecamatan">
                                Mode Desa: <span class="font-bold text-emerald-800">Kec. {{ drillKecamatan.kecamatan }} ({{ drillKecamatan.kabupaten }})</span> — klik desa untuk Radius Sekolah, Tol &amp; daftar siswa
                            </template>
                            <template v-else-if="aggregationLevel === 'desa'">
                                Warna per <span class="font-bold text-slate-700">Desa / Kelurahan</span> berdasarkan jumlah siswa domisili — klik desa untuk rincian
                            </template>
                            <template v-else>
                                Warna per <span class="font-bold text-slate-700">Kecamatan</span> berdasarkan siswa domisili + ring radius sekolah &amp; gerbang tol
                            </template>
                        </template>
                        <template v-else>
                            <template v-if="drillKecamatan">
                                Mode Desa: <span class="font-bold text-emerald-800">Kec. {{ drillKecamatan.kecamatan }} ({{ drillKecamatan.kabupaten }})</span> — klik titik sekolah atau poligon desa
                            </template>
                            <template v-else-if="aggregationLevel === 'desa'">
                                Menampilkan warna per <span class="font-bold text-slate-700">Desa / Kelurahan</span> se-Solo Raya &amp; Grobogan — klik desa untuk daftar SD/MI/PKBM
                            </template>
                            <template v-else>
                                Warna per <span class="font-bold text-slate-700">Kecamatan</span> berdasarkan siswa masuk — klik kecamatan untuk detail siswa &amp; SD
                            </template>
                        </template>
                    </p>
                </div>
            </div>

            <!-- Kontrol Kanan: Hanya Toolbar Mode Peta [Sekolah Asal | Domisili] + Badge Ringkasan -->
            <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
                <!-- Toggle Mode Peta -->
                <div class="inline-flex items-center p-0.5 bg-slate-100 rounded-xl border border-slate-200/90 shadow-2xs">
                    <button
                        type="button"
                        @click="emit('update:mode', 'school')"
                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all cursor-pointer"
                        :class="!isWelfareMode
                            ? 'bg-emerald-900 text-yellow-200 shadow-xs'
                            : 'text-slate-600 hover:text-slate-900'"
                    >
                        Sekolah Asal
                    </button>
                    <button
                        type="button"
                        @click="emit('update:mode', 'welfare')"
                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all cursor-pointer"
                        :class="isWelfareMode
                            ? 'bg-emerald-900 text-yellow-200 shadow-xs'
                            : 'text-slate-600 hover:text-slate-900'"
                    >
                        Domisili
                    </button>
                </div>

                <!-- Badge Total Tercakup -->
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-800">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                    <template v-if="isWelfareMode">
                        <span>{{ welfareAggregations.reachedVillages }} Desa Terjangkau</span>
                        <span class="text-[11px] text-emerald-600 font-medium">({{ welfareAggregations.totalDomicileStudents }} Siswa)</span>
                    </template>
                    <template v-else>
                        <span>{{ geoAggregations.totalReachedSchools }} SD Tergarap</span>
                        <span class="text-[11px] text-emerald-600 font-medium">({{ geoAggregations.totalStudents }} Siswa)</span>
                    </template>
                </div>

                <!-- Tombol Fullscreen Pop-up Peta -->
                <button
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

        <!-- Baris Filter Layer Titik Sekolah (Hanya Mode Sekolah Asal saat Titik SD Aktif, 1 Baris) -->
        <div
            v-if="!isWelfareMode && showSchoolDots"
            class="mb-3 px-2.5 py-2 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-2 shrink-0 overflow-x-auto no-scrollbar"
        >
            <div class="flex items-center gap-1.5 flex-nowrap shrink-0">
                <span class="text-[10.5px] font-bold text-slate-500 uppercase tracking-wider mr-0.5 shrink-0 whitespace-nowrap">Filter Titik Tier:</span>
                <button
                    v-for="tierKey in ['basis_utama', 'potensial', 'vakum', 'belum_tergarap']"
                    :key="tierKey"
                    type="button"
                    @click="visibleTiers[tierKey] = !visibleTiers[tierKey]"
                    :class="[
                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border transition-all cursor-pointer select-none shrink-0 whitespace-nowrap',
                        visibleTiers[tierKey]
                            ? PSB_TIERS[tierKey].badgeClass
                            : 'bg-white text-slate-400 border-slate-200 line-through opacity-70',
                    ]"
                >
                    <span
                        :class="[
                            'w-2 h-2 rounded-full shrink-0',
                            visibleTiers[tierKey] ? PSB_TIERS[tierKey].dotClass : 'bg-slate-300',
                        ]"
                    ></span>
                    <span>{{ PSB_TIERS[tierKey].shortLabel }}</span>
                </button>
            </div>
            <span class="hidden xl:inline text-[11px] text-slate-500 font-medium shrink-0 whitespace-nowrap">Klik titik mana pun untuk melihat profil &amp; siswa sekolahnya</span>
        </div>

        <!-- Container Peta (selalu render dimensi penuh agar Leaflet & fitBounds tidak 0x0) -->
        <div
            :class="[
                'relative w-full rounded-2xl border border-slate-200/90 bg-[#f8fafc] overflow-hidden',
                isFullscreen ? 'flex-1 min-h-0' : 'h-[460px] sm:h-[560px] lg:h-[620px]',
            ]"
        >
            <!-- Loading State Overlay -->
            <div v-if="isLoading" class="absolute inset-0 bg-slate-50/95 z-[500] flex flex-col items-center justify-center gap-2">
                <span class="material-symbols-outlined text-3xl animate-spin text-emerald-700">progress_activity</span>
                <span class="text-xs font-semibold text-slate-500">Memuat peta kecamatan Solo Raya &amp; Grobogan...</span>
            </div>

            <!-- Error State Overlay -->
            <div v-else-if="loadError" class="absolute inset-0 bg-white z-[500] flex flex-col items-center justify-center p-6 text-center">
                <span class="material-symbols-outlined text-3xl text-rose-500 mb-1">map</span>
                <p class="text-xs font-bold text-rose-800">{{ loadError }}</p>
            </div>

            <div ref="mapContainerRef" class="w-full h-full z-10 psb-leaflet-bg"></div>

            <!-- Kontrol Pojok Kiri Atas di Dalam Peta: [Kec. | Desa] + Overlay (Titik SD / Radius / GT) -->
            <div class="absolute top-3 left-3 z-[410] flex items-center gap-1.5 flex-wrap max-w-[calc(100%-56px)]">
                <!-- Tab Kecamatan / Desa Ringkas -->
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

                <!-- Desktop: Tombol Overlay Langsung Sesuai Mode -->
                <div class="hidden sm:inline-flex items-center gap-1.5">
                    <template v-if="!isWelfareMode">
                        <button
                            type="button"
                            @click="showSchoolDots = !showSchoolDots"
                            :class="[
                                'inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-[11px] font-bold border shadow-md backdrop-blur-xs transition-all cursor-pointer',
                                showSchoolDots
                                    ? 'bg-emerald-900 text-yellow-200 border-emerald-950'
                                    : 'bg-white/95 hover:bg-white text-slate-700 border-slate-200',
                            ]"
                        >
                            <span class="material-symbols-outlined text-[14px]">location_on</span>
                            <span>Titik SD</span>
                        </button>
                    </template>
                    <template v-else>
                        <button
                            type="button"
                            @click="showCampusRadius = !showCampusRadius"
                            :class="[
                                'inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-[11px] font-bold border shadow-md backdrop-blur-xs transition-all cursor-pointer',
                                showCampusRadius
                                    ? 'bg-sky-700 text-white border-sky-800'
                                    : 'bg-white/95 hover:bg-white text-slate-700 border-slate-200',
                            ]"
                            title="Ring radius 5 / 10 / 15 km dari Sekolah"
                        >
                            <span class="material-symbols-outlined text-[14px]">radar</span>
                            <span>Radius</span>
                        </button>
                        <button
                            type="button"
                            @click="showTollGates = !showTollGates"
                            :class="[
                                'inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-[11px] font-bold border shadow-md backdrop-blur-xs transition-all cursor-pointer',
                                showTollGates
                                    ? 'bg-indigo-700 text-yellow-200 border-indigo-800'
                                    : 'bg-white/95 hover:bg-white text-slate-700 border-slate-200',
                            ]"
                            title="Titik Gerbang Tol Solo Raya"
                        >
                            <span class="material-symbols-outlined text-[14px]">toll</span>
                            <span>GT</span>
                        </button>
                    </template>
                </div>

                <!-- Mobile: Dropdown Layer Overlay Responsif -->
                <div class="sm:hidden relative">
                    <button
                        type="button"
                        @click="isLayerDropdownOpen = !isLayerDropdownOpen"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-white/95 backdrop-blur-xs border border-slate-200 text-slate-700 text-[11px] font-bold shadow-md cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[14px] text-emerald-700">layers</span>
                        <span>Layer</span>
                        <span class="material-symbols-outlined text-[14px]">{{ isLayerDropdownOpen ? 'expand_less' : 'expand_more' }}</span>
                    </button>

                    <div
                        v-if="isLayerDropdownOpen"
                        class="absolute left-0 mt-1.5 w-44 rounded-xl bg-white border border-slate-200 shadow-xl p-1.5 space-y-1 z-[450]"
                    >
                        <template v-if="!isWelfareMode">
                            <button
                                type="button"
                                @click="showSchoolDots = !showSchoolDots"
                                class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-[11px] font-bold transition-colors cursor-pointer"
                                :class="showSchoolDots ? 'bg-emerald-50 text-emerald-900' : 'text-slate-600 hover:bg-slate-50'"
                            >
                                <span class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[14px]">location_on</span>
                                    <span>Titik SD</span>
                                </span>
                                <span class="material-symbols-outlined text-[15px]">{{ showSchoolDots ? 'check_box' : 'check_box_outline_blank' }}</span>
                            </button>
                        </template>
                        <template v-else>
                            <button
                                type="button"
                                @click="showCampusRadius = !showCampusRadius"
                                class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-[11px] font-bold transition-colors cursor-pointer"
                                :class="showCampusRadius ? 'bg-sky-50 text-sky-900' : 'text-slate-600 hover:bg-slate-50'"
                            >
                                <span class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[14px]">radar</span>
                                    <span>Radius</span>
                                </span>
                                <span class="material-symbols-outlined text-[15px]">{{ showCampusRadius ? 'check_box' : 'check_box_outline_blank' }}</span>
                            </button>
                            <button
                                type="button"
                                @click="showTollGates = !showTollGates"
                                class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-[11px] font-bold transition-colors cursor-pointer"
                                :class="showTollGates ? 'bg-indigo-50 text-indigo-900' : 'text-slate-600 hover:bg-slate-50'"
                            >
                                <span class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[14px]">toll</span>
                                    <span>Gerbang Tol (GT)</span>
                                </span>
                                <span class="material-symbols-outlined text-[15px]">{{ showTollGates ? 'check_box' : 'check_box_outline_blank' }}</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Loading Desa Indicator -->
            <div
                v-if="isDesaLoading"
                class="absolute top-3 left-1/2 -translate-x-1/2 z-[450] px-3.5 py-1.5 rounded-full bg-white/95 border border-emerald-200 shadow-md flex items-center gap-2 text-xs font-bold text-emerald-900"
            >
                <span class="material-symbols-outlined text-[16px] animate-spin text-emerald-700">sync</span>
                <span>Memuat batas desa...</span>
            </div>

            <!-- Tombol Reset Zoom -->
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
                        hoveredRegion ? 'hidden sm:inline-flex' : 'inline-flex',
                    ]"
                >
                    <span class="material-symbols-outlined text-[15px]">arrow_back</span>
                    <span>Kembali</span>
                </button>

                <!-- Card Detail Sekolah Asal Saat Titik Sekolah Diklik (Menggantikan Card Wilayah) -->
                <div
                    v-if="hoveredRegion && hoveredRegion.type === 'school_point'"
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
                        <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">
                            Sekolah Asal • {{ hoveredRegion.school.bentuk }} ({{ hoveredRegion.school.status }})
                        </span>
                        <span class="text-[13px] font-extrabold text-slate-900 pr-6 leading-snug mt-0.5 break-words">
                            {{ hoveredRegion.school.nama }}
                        </span>
                        <span class="text-[11px] text-slate-500 font-medium mt-0.5 break-words">
                            NPSN {{ hoveredRegion.school.npsn }} • {{ hoveredRegion.school.desa ? `Ds. ${hoveredRegion.school.desa}, ` : '' }}Kec. {{ hoveredRegion.school.kecamatan }}
                        </span>

                        <div class="mt-1.5 pt-1.5 border-t border-slate-100 flex items-center justify-between gap-2">
                            <span class="text-xs font-bold text-slate-900">
                                {{ hoveredRegion.students }} Siswa <span class="text-[10.5px] font-medium text-slate-500">({{ hoveredRegion.school.taCount }} TA)</span>
                            </span>
                            <span
                                :class="[
                                    'px-2 py-0.5 rounded-full text-[10px] font-bold border shrink-0',
                                    PSB_TIERS[hoveredRegion.school.tier].badgeClass,
                                ]"
                            >
                                {{ PSB_TIERS[hoveredRegion.school.tier].label }}
                            </span>
                        </div>

                        <button
                            v-if="hoveredRegion.students > 0"
                            type="button"
                            @click="openRegionStudentModal(hoveredRegion)"
                            class="mt-2 w-full py-1.5 px-2.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-[11px] font-bold flex items-center justify-center gap-1.5 transition-colors shadow-2xs cursor-pointer"
                        >
                            <span>Lihat {{ hoveredRegion.students }} Siswa</span>
                            <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                        </button>

                        <div class="mt-1.5 flex items-center gap-1.5">
                            <button
                                type="button"
                                @click="emit('select-school', hoveredRegion.school)"
                                class="flex-1 py-1 px-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10.5px] font-bold transition-colors cursor-pointer text-center"
                            >
                                Profil &amp; Histori SD →
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Live Status Hover/Click Card Kanan Bawah (Kecamatan & Desa, selaras Peta Domisili Stat Siswa) -->
                <div
                    v-else-if="hoveredRegion"
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
                        <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">
                            {{ hoveredRegion.type === 'desa' ? 'Desa / Kelurahan' : 'Kecamatan' }}
                        </span>
                        <span class="text-[13px] font-extrabold text-slate-900 pr-6 leading-tight mt-0.5">
                            {{ hoveredRegion.type === 'desa' ? `Desa ${hoveredRegion.desa}` : `Kec. ${hoveredRegion.kecamatan}` }}
                        </span>
                        <span class="text-[11px] text-slate-500 font-medium mt-0.5">
                            {{ hoveredRegion.type === 'desa'
                                ? `Kec. ${hoveredRegion.kecamatan} • Kab/Kota ${hoveredRegion.kabupaten}`
                                : `Kab/Kota ${hoveredRegion.kabupaten}` }}
                        </span>

                        <div class="mt-1.5 pt-1.5 border-t border-slate-100 flex items-center justify-between gap-2">
                            <span class="text-xs font-bold text-slate-900">
                                {{ hoveredRegion.students }} Siswa
                            </span>
                            <span
                                v-if="hoveredRegion.mode === 'welfare'"
                                class="text-[10.5px] font-semibold text-sky-700 bg-sky-50 px-2 py-0.5 rounded-md border border-sky-200/70"
                            >
                                {{ hoveredRegion.type === 'desa'
                                    ? `${hoveredRegion.campus_km ?? '-'} km ke Sekolah`
                                    : `Rata ${hoveredRegion.avgCampusKm ?? '-'} km (${hoveredRegion.reachedVillages}/${hoveredRegion.villageCount} desa)` }}
                            </span>
                            <span
                                v-else
                                class="text-[10.5px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/60"
                            >
                                {{ hoveredRegion.reached }} / {{ hoveredRegion.total }} SD • {{ hoveredRegion.percentage }}%
                            </span>
                        </div>

                        <!-- Detail Gerbang Tol untuk Desa Mode Domisili -->
                        <div
                            v-if="hoveredRegion.mode === 'welfare' && hoveredRegion.type === 'desa'"
                            class="mt-1.5 flex items-center justify-between gap-2 text-[10.5px] text-indigo-800 bg-indigo-50/70 px-2 py-1 rounded-lg border border-indigo-200/60"
                        >
                            <span class="font-semibold truncate" :title="hoveredRegion.toll_name">Tol: {{ hoveredRegion.toll_name }}</span>
                            <span class="font-bold shrink-0">{{ hoveredRegion.toll_km ?? '-' }} km</span>
                        </div>

                        <!-- Tombol Lihat Detail Siswa (Tersedia di Kecamatan maupun Desa) -->
                        <button
                            v-if="hoveredRegion.students > 0"
                            type="button"
                            @click="openRegionStudentModal(hoveredRegion)"
                            class="mt-2 w-full py-1.5 px-2.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-[11px] font-bold flex items-center justify-center gap-1.5 transition-colors shadow-2xs cursor-pointer"
                        >
                            <span>Lihat {{ hoveredRegion.students }} Siswa</span>
                            <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                        </button>

                        <!-- Aksi Pendukung: Buka Level Desa (saat di Kecamatan) / Daftar SD / Profil Desa -->
                        <div class="mt-1.5 flex items-center gap-1.5 flex-wrap">
                            <button
                                v-if="hoveredRegion.type === 'kecamatan'"
                                type="button"
                                @click="drillIntoKecamatan(hoveredRegion.kecamatan, hoveredRegion.kabupaten, hoveredRegion.featureLayer)"
                                class="flex-1 py-1 px-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10.5px] font-bold transition-colors cursor-pointer text-center"
                            >
                                Masuk Level Desa →
                            </button>
                            <button
                                v-if="hoveredRegion.mode === 'school' && hoveredRegion.schools.length > 0"
                                type="button"
                                @click="showSchoolsInCard = !showSchoolsInCard"
                                class="flex-1 py-1 px-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10.5px] font-bold transition-colors cursor-pointer text-center"
                            >
                                {{ showSchoolsInCard ? 'Tutup SD' : `Daftar SD (${hoveredRegion.schools.length})` }}
                            </button>
                            <button
                                v-if="hoveredRegion.mode === 'welfare' && hoveredRegion.type === 'desa' && hoveredRegion.village"
                                type="button"
                                @click="emit('select-village', hoveredRegion.village)"
                                class="flex-1 py-1 px-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10.5px] font-bold transition-colors cursor-pointer text-center"
                            >
                                Profil Desa →
                            </button>
                        </div>

                        <!-- Daftar SD Ringkas Opsional di Dalam Card (Saat Toggle Daftar SD Diklik) -->
                        <div
                            v-if="showSchoolsInCard && hoveredRegion.mode === 'school' && hoveredRegion.schools.length > 0"
                            class="mt-2 pt-2 border-t border-slate-100 max-h-36 overflow-y-auto space-y-1 custom-bar-scrollbar"
                        >
                            <button
                                v-for="sc in hoveredRegion.schools"
                                :key="sc.npsn"
                                type="button"
                                @click="emit('select-school', sc)"
                                class="w-full text-left p-1.5 rounded-lg border border-slate-200/80 hover:border-emerald-300 hover:bg-emerald-50/40 transition-all flex items-center justify-between gap-2 cursor-pointer"
                            >
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold text-slate-900 truncate">{{ sc.nama }}</p>
                                    <p class="text-[9.5px] text-slate-500">{{ sc.bentuk }} • {{ sc.status }}</p>
                                </div>
                                <span
                                    :class="[
                                        'px-1.5 py-0.5 rounded-full text-[9.5px] font-bold border shrink-0',
                                        PSB_TIERS[sc.tier].badgeClass,
                                    ]"
                                >
                                    {{ sc.totalStudents }}
                                </span>
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

            <!-- Tombol Legenda Mobile -->
            <button
                v-if="!isLegendOpenMobile && !hoveredRegion"
                type="button"
                @click="isLegendOpenMobile = true"
                class="sm:hidden absolute bottom-3 left-3 z-[400] px-2.5 py-1.5 rounded-xl bg-white/95 backdrop-blur-xs text-slate-700 text-[11px] font-bold shadow-md border border-slate-200/80 flex items-center gap-1.5 cursor-pointer"
            >
                <span class="material-symbols-outlined text-[15px] text-emerald-700">palette</span>
                <span>Legenda</span>
            </button>

            <!-- Floating Legend Kiri Bawah (identik Peta Domisili) -->
            <div
                class="absolute bottom-3 left-3 z-[400] bg-white/95 backdrop-blur-xs rounded-xl border border-slate-200/90 p-2 sm:p-2.5 shadow-md flex-col gap-1.5 transition-all"
                :class="[
                    isLegendOpenMobile ? 'flex' : 'hidden sm:flex',
                    { 'hidden sm:flex': hoveredRegion },
                ]"
            >
                <div>
                    <div class="flex items-center justify-between gap-3 mb-1">
                        <span class="text-[10px] font-bold text-slate-700 uppercase tracking-wider">
                            <template v-if="isWelfareMode">
                                {{ aggregationLevel === 'desa' ? 'Siswa Domisili per Desa' : 'Siswa Domisili per Kecamatan' }}
                            </template>
                            <template v-else>
                                {{ aggregationLevel === 'desa' ? 'Siswa Masuk per Desa' : 'Siswa Masuk per Kecamatan' }}
                            </template>
                        </span>
                        <button
                            type="button"
                            @click="isLegendOpenMobile = false"
                            class="sm:hidden p-0.5 -mr-1 text-slate-400 hover:text-slate-700 rounded-md cursor-pointer"
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

                <div class="pt-1.5 border-t border-slate-200/70 flex items-center gap-2.5 text-[10px] text-slate-600 font-medium flex-wrap">
                    <div class="flex items-center gap-1">
                        <span class="w-3 h-3 rounded-full bg-rose-600 border border-white inline-block"></span>
                        <span class="font-bold text-rose-700">Sekolah</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="w-4 border-t-2 border-dashed border-[#1e293b] inline-block"></span>
                        <span>Batas Kab</span>
                    </div>
                    <div v-if="isWelfareMode && showCampusRadius" class="flex items-center gap-1">
                        <span class="w-4 border-t-2 border-dashed border-[#0284c7] inline-block"></span>
                        <span>Radius 5/10/15 km</span>
                    </div>
                    <div v-if="isWelfareMode && showTollGates" class="flex items-center gap-1">
                        <span class="px-1 py-0.2 rounded bg-indigo-700 text-yellow-200 text-[8px] font-black leading-tight">TOL</span>
                        <span>Gerbang Tol</span>
                    </div>
                    <div v-if="!isWelfareMode && showSchoolDots" class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span><span>Basis</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span><span>Potensial</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-600 inline-block"></span><span>Vakum</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-slate-500 inline-block"></span><span>0</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Detail Siswa per Kecamatan / Desa (Identik Peta Domisili Stat Siswa) -->
        <Teleport to="body">
            <div
                v-if="regionStudentModal"
                class="fixed inset-0 z-[999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 overflow-y-auto"
                @click.self="closeRegionStudentModal"
            >
                <div class="bg-white rounded-3xl border border-slate-200/90 shadow-2xl max-w-2xl w-full max-h-[85vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <!-- Modal Header -->
                    <div class="px-4 sm:px-6 py-4 border-b border-slate-100 flex items-start justify-between gap-3 bg-slate-50/70 shrink-0">
                        <div class="flex items-start gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 border shadow-2xs mt-0.5 bg-emerald-100 border-emerald-300 text-emerald-800">
                                <span class="material-symbols-outlined text-[22px]">
                                    {{ regionStudentModal.type === 'desa' ? 'holiday_village' : 'location_on' }}
                                </span>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider inline-block mb-1 bg-emerald-100 text-emerald-900">
                                    {{ regionStudentModal.mode === 'welfare'
                                        ? (regionStudentModal.type === 'desa' ? 'Domisili Desa / Kelurahan' : 'Domisili Kecamatan')
                                        : (regionStudentModal.type === 'desa' ? 'Asal SD/MI Desa' : 'Asal SD/MI Kecamatan') }}
                                </span>
                                <h3 class="text-sm sm:text-base font-extrabold text-slate-900 break-words leading-snug">
                                    <template v-if="regionStudentModal.type === 'school_point'">
                                        {{ regionStudentModal.school.nama }}
                                        <span class="text-slate-500 font-semibold text-xs sm:text-sm">
                                            (Kec. {{ regionStudentModal.kecamatan }}, {{ regionStudentModal.kabupaten }})
                                        </span>
                                    </template>
                                    <template v-else-if="regionStudentModal.type === 'desa'">
                                        Desa {{ regionStudentModal.desa }}
                                        <span class="text-slate-500 font-semibold text-xs sm:text-sm">
                                            (Kec. {{ regionStudentModal.kecamatan }}, {{ regionStudentModal.kabupaten }})
                                        </span>
                                    </template>
                                    <template v-else>
                                        Kec. {{ regionStudentModal.kecamatan }}
                                        <span class="text-slate-500 font-semibold text-xs sm:text-sm">
                                            ({{ regionStudentModal.kabupaten }})
                                        </span>
                                    </template>
                                </h3>
                                <p class="text-[11px] text-slate-500 font-medium mt-0.5">
                                    Total <span class="font-bold text-slate-800">{{ regionStudentModal.students }} Siswa</span>
                                    ({{ regionStudentModal.percentage }}% dari total filter aktif)
                                    <template v-if="regionStudentModal.mode === 'school'">
                                        • {{ regionStudentModal.reached }} / {{ regionStudentModal.total }} SD tergarap
                                    </template>
                                    <template v-else-if="regionStudentModal.type === 'desa'">
                                        • {{ regionStudentModal.campus_km ?? '-' }} km ke Sekolah
                                    </template>
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="closeRegionStudentModal"
                            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-xl hover:bg-slate-200/60 transition-colors cursor-pointer shrink-0"
                            title="Tutup (ESC)"
                        >
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <!-- Modal Toolbar Search -->
                    <div class="px-4 sm:px-6 py-2.5 bg-white border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 shrink-0">
                        <div class="relative w-full sm:w-80">
                            <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[16px]">search</span>
                            <input
                                v-model="modalSearch"
                                type="text"
                                :placeholder="regionStudentModal.mode === 'welfare' ? 'Cari nama, NISN, dusun, atau desa...' : 'Cari nama, NISN, atau SD asal...'"
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
                            Menampilkan {{ modalFilteredStudents.length }} dari {{ regionStudentModal.students }} siswa
                        </span>
                    </div>

                    <!-- Modal Body: Daftar Siswa -->
                    <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-3 space-y-2 custom-bar-scrollbar">
                        <div v-if="modalFilteredStudents.length > 0" class="space-y-2">
                            <PsbStudentRowCard
                                v-for="(student, sIdx) in modalFilteredStudents"
                                :key="student.id || sIdx"
                                :student="student"
                                :index="sIdx"
                                :mode="regionStudentModal.mode"
                                :fallback-school-name="regionStudentModal.school?.nama || ''"
                            />
                        </div>
                        <div v-else class="py-12 text-center">
                            <span class="material-symbols-outlined text-3xl text-slate-300 mb-1 block">person_search</span>
                            <p class="text-xs font-bold text-slate-700">Tidak ada siswa yang cocok</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">Coba kata kunci pencarian lain.</p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-4 sm:px-6 py-3 border-t border-slate-100 bg-slate-50/70 flex items-center justify-between gap-3 shrink-0">
                        <span class="text-[11px] text-slate-400 font-medium">
                            Tekan <kbd class="px-1.5 py-0.5 rounded bg-white border border-slate-300 text-[10px] font-mono text-slate-600 shadow-2xs">ESC</kbd> untuk menutup
                        </span>
                        <button
                            type="button"
                            @click="closeRegionStudentModal"
                            class="px-4 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition-all cursor-pointer shadow-xs"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style>
.psb-leaflet-bg.leaflet-container {
    background-color: #f8fafc !important;
}
.psb-leaflet-bg .leaflet-pane > svg {
    pointer-events: none !important;
}
.psb-map-text-label,
.psb-school-point-label,
.psb-radius-label {
    background: transparent !important;
    border: none !important;
    pointer-events: none !important;
}
.psb-leaflet-bg .leaflet-tooltip-pane,
.psb-leaflet-bg .leaflet-custom-tooltip {
    pointer-events: none !important;
}
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
</style>
