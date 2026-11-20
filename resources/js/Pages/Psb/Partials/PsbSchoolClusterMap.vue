<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import {
    CAMPUS_RING_META,
    PSB_TIERS,
    PSB_WELFARE_TIERS,
    SOLO_RAYA_KABUPATENS,
    normalizeWilayahKey,
} from '@/Utils/psbMatcher';

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
let pointsCanvasRenderer = null;
let campusMarker = null;
let activeHoverLayer = null;
let currentHoveredKey = null;
let singleTooltip = null;
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
const selectedDesaCard = ref(null); // { desa, kecamatan, kabupaten, schools, studentsCount, reachedCount } | null
const hoveredInfo = ref(null);

const showSchoolDots = ref(false);
const showCampusRadius = ref(true);
const showTollGates = ref(true);
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

function normDesa(raw) {
    if (!raw || typeof raw !== 'string') return '';
    return raw
        .toLowerCase()
        .replace(/^(desa|ds\.?|kel\.?|kelurahan)\s+/i, '')
        .replace(/[^a-z0-9]/g, '');
}

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
            });
        }
        const kBucket = byKec.get(kKey);
        kBucket.total++;
        kBucket.schools.push(s);
        kBucket.students += s.totalStudents;
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
                });
            }
            const dBucket = byDesa.get(dFullKey);
            dBucket.total++;
            dBucket.schools.push(s);
            dBucket.students += s.totalStudents;
            if (s.totalStudents > 0) dBucket.reached++;
        }
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

// Agregasi Domisili & Potensi Desa (Meta RWI + Kampus + Tol) per Kecamatan & per Desa
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
                rwiSum: 0,
                avgRwi: 0,
                campusSum: 0,
                avgCampusKm: 0,
                closestTollKm: null,
                closestTollName: '-',
                welfareSum: 0,
                avgWelfare: 0,
                villages: [],
            });
        }

        const kb = byKec.get(kKey);
        kb.villageCount++;
        kb.students += v.totalStudents;
        kb.rwiSum += Number(v.rwi || 0);
        kb.campusSum += Number(v.campus_km || 0);
        kb.welfareSum += Number(v.welfare_score || 0);
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
        kb.avgWelfare = kb.villageCount > 0 ? Number((kb.welfareSum / kb.villageCount).toFixed(1)) : 0;
        kb.avgRwi = kb.villageCount > 0 ? Number((kb.rwiSum / kb.villageCount).toFixed(2)) : 0;
        kb.avgCampusKm = kb.villageCount > 0 ? Number((kb.campusSum / kb.villageCount).toFixed(1)) : 0;
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
                avgRwi: 0,
                avgCampusKm: 0,
                closestTollKm: null,
                closestTollName: '-',
                avgWelfare: 0,
                villages: [],
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
                rwi: 0,
                campus_km: null,
                campus_ring: 4,
                toll_km: null,
                toll_name: '-',
                welfare_score: 50,
                welfare_level: 'menengah_atas',
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

function clearHover() {
    currentHoveredKey = null;
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
    hoveredInfo.value = null;
}

function handleHoverFeature(type, feature, layer, latlng) {
    const kecKey = normalizeWilayahKey(feature?.properties?.kecamatan);
    const kabKey = normalizeWilayahKey(feature?.properties?.kabupaten);
    const dKey = type === 'desa' ? normDesa(feature?.properties?.desa) : '';
    const key = type === 'desa' ? `d_${dKey}_${kecKey}_${kabKey}` : `k_${kecKey}_${kabKey}`;

    if (currentHoveredKey === key) {
        if (!isMobileScreen() && latlng && singleTooltip && map && map.hasLayer(singleTooltip)) {
            singleTooltip.setLatLng(latlng);
        }
        return;
    }

    clearHover();
    currentHoveredKey = key;
    activeHoverLayer = layer;

    layer.setStyle({
        fill: true,
        weight: 2.5,
        color: '#047857',
        fillOpacity: 1,
    });
    if (layer.bringToFront) layer.bringToFront();
    if (kabupatenLayer && kabupatenLayer.bringToFront) kabupatenLayer.bringToFront();
    if (schoolPointsLayer && schoolPointsLayer.bringToFront) schoolPointsLayer.bringToFront();

    const stats = type === 'desa' ? getDesaStats(feature) : getKecStats(feature);
    hoveredInfo.value = {
        type,
        desa: feature?.properties?.desa || null,
        kecamatan: feature?.properties?.kecamatan || '',
        kabupaten: feature?.properties?.kabupaten || '',
        ...stats,
    };

    if (!isMobileScreen() && singleTooltip && map) {
        const pos = latlng || layer.getBounds().getCenter();
        const headingLabel = type === 'desa' ? 'Desa / Kelurahan' : 'Kecamatan';
        const titleText = type === 'desa' ? `Desa ${feature.properties.desa}` : `Kec. ${feature.properties.kecamatan}`;
        const subText =
            type === 'desa'
                ? `Kec. ${feature.properties.kecamatan}, ${feature.properties.kabupaten}`
                : `Kab/Kota ${feature.properties.kabupaten}`;
        const actionHint = isWelfareMode.value
            ? (type === 'kecamatan'
                ? 'Klik kecamatan untuk masuk ke rincian Desa'
                : 'Klik desa untuk melihat jarak Kampus, Tol & daftar siswa')
            : (type === 'kecamatan'
                ? 'Klik kecamatan untuk masuk ke Desa & titik sekolah'
                : 'Klik desa untuk melihat daftar SD/MI/PKBM');

        const metricRow = isWelfareMode.value
            ? (type === 'desa'
                ? `<div class="mt-1.5 pt-1 border-t border-slate-200/80 space-y-1">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-bold text-emerald-800">${stats.totalStudents || 0} Siswa Domisili</span>
                            <span class="text-[11px] font-bold text-sky-700">${stats.campus_km ?? '-'} km ke Kampus</span>
                        </div>
                        <div class="text-[10.5px] text-slate-500">
                            Tol terdekat: <strong>${escapeHtml(stats.toll_name || '-')}</strong> (${stats.toll_km ?? '-'} km)
                        </div>
                   </div>`
                : `<div class="mt-1.5 pt-1 border-t border-slate-200/80 space-y-1">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-bold text-emerald-800">${stats.students} Siswa Domisili</span>
                            <span class="text-[11px] font-bold text-sky-700">Rata ${stats.avgCampusKm ?? '-'} km</span>
                        </div>
                        <div class="text-[10.5px] text-slate-500">
                            ${stats.reachedVillages} / ${stats.villageCount} desa terisi siswa
                        </div>
                   </div>`)
            : `<div class="mt-1.5 pt-1 border-t border-slate-200/80 flex items-center justify-between gap-3">
                    <span class="font-bold text-emerald-800">${stats.students} Siswa</span>
                    <span class="text-[11px] font-semibold text-slate-600">${stats.reached} / ${stats.total} SD tergarap</span>
               </div>`;

        singleTooltip.setLatLng(pos).setContent(`
            <div class="px-2.5 py-1.5 text-xs text-slate-800 leading-tight min-w-[200px]">
                <div class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider mb-0.5">${headingLabel}</div>
                <div class="font-extrabold text-slate-900 text-[13px]">${escapeHtml(titleText)}</div>
                <div class="text-[11px] text-slate-500 font-medium">${escapeHtml(subText)}</div>
                ${metricRow}
                <div class="text-[10px] text-slate-400 italic mt-1">${actionHint}</div>
            </div>
        `);
        if (!map.hasLayer(singleTooltip)) {
            singleTooltip.addTo(map);
        }
    }
}

async function loadDesaGeoJson() {
    if (desaGeoData.value) return desaGeoData.value;
    if (desaLoadingPromise) return desaLoadingPromise;

    isDesaLoading.value = true;
    desaLoadingPromise = (async () => {
        try {
            const res = await fetch('/data/solo-raya-desa.geojson');
            if (!res.ok) throw new Error('Gagal memuat batas desa');
            const raw = await res.json();
            raw.features = (raw.features || []).filter((f) =>
                SOLO_RAYA_KEYS.has(normalizeWilayahKey(f.properties?.kabupaten))
            );
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

    if (!desaCanvasRenderer) {
        desaCanvasRenderer = L.canvas({ padding: 0.25 });
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

    desaLayer = L.geoJSON(
        { type: 'FeatureCollection', features },
        {
            renderer: desaCanvasRenderer,
            style: desaStyle,
            onEachFeature: (feature, layer) => {
                layer.on({
                    mouseover: (e) => {
                        if (aggregationLevel.value !== 'desa') return;
                        handleHoverFeature('desa', feature, layer, e.latlng);
                    },
                    mousemove: (e) => {
                        if (aggregationLevel.value !== 'desa') return;
                        if (singleTooltip && map && map.hasLayer(singleTooltip)) {
                            singleTooltip.setLatLng(e.latlng);
                        }
                    },
                    mouseout: () => {
                        if (aggregationLevel.value !== 'desa') return;
                        clearHover();
                    },
                    click: (e) => {
                        if (e.originalEvent) L.DomEvent.stopPropagation(e.originalEvent);
                        const stats = getDesaStats(feature);
                        if (isWelfareMode.value) {
                            selectedDesaCard.value = {
                                mode: 'welfare',
                                village: stats,
                                desa: feature.properties.desa,
                                kecamatan: feature.properties.kecamatan,
                                kabupaten: feature.properties.kabupaten,
                                students: stats.totalStudents || 0,
                                welfare_score: stats.welfare_score ?? 0,
                                rwi: stats.rwi ?? 0,
                                campus_km: stats.campus_km ?? null,
                                campus_ring: stats.campus_ring ?? 4,
                                toll_km: stats.toll_km ?? null,
                                toll_name: stats.toll_name || '-',
                                tier: stats.tier,
                                studentList: stats.students || [],
                                schools: stats.schools || [],
                            };
                            return;
                        }
                        selectedDesaCard.value = {
                            mode: 'school',
                            desa: feature.properties.desa,
                            kecamatan: feature.properties.kecamatan,
                            kabupaten: feature.properties.kabupaten,
                            total: stats.total,
                            reached: stats.reached,
                            students: stats.students,
                            schools: [...stats.schools].sort(
                                (a, b) => b.totalStudents - a.totalStudents || a.nama.localeCompare(b.nama, 'id')
                            ),
                        };
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

    if (!showSchoolDots.value) return;

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
            renderer: pointsCanvasRenderer,
            radius: isZero ? (drillKecamatan.value ? 6 : 4) : (school.tier === 'basis_utama' ? 8.5 : 7),
            fillColor: tier.hex,
            color: '#ffffff',
            weight: isZero ? 1.2 : 2,
            opacity: 0.95,
            fillOpacity: isZero ? 0.72 : 0.94,
        });

        marker.bindTooltip(
            `${school.nama} (${school.totalStudents} Siswa)`,
            { direction: 'top', offset: [0, -6] }
        );
        marker.bindPopup(() => buildSchoolPopupElement(school), { maxWidth: 280 });
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

        priorityList.forEach((school) => {
            const pt = map.latLngToContainerPoint([school.lat, school.lng]);
            const collides = placedBoxes.some(
                (b) => Math.abs(pt.x - b.x) < boxGapX && Math.abs(pt.y - b.y) < boxGapY
            );
            if (collides) return;
            placedBoxes.push(pt);

            const tier = PSB_TIERS[school.tier] || PSB_TIERS.belum_tergarap;
            const shortName = school.nama.length > 26 ? `${school.nama.slice(0, 25)}…` : school.nama;

            L.marker([school.lat, school.lng], {
                interactive: false,
                icon: L.divIcon({
                    className: 'psb-school-point-label',
                    iconSize: [0, 0],
                    html: `<div style="display:inline-block; width:max-content; transform:translate(-50%, 7px); white-space:nowrap; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; font-size:8px; font-weight:600; color:${tier.strokeHex}; text-shadow:0 0 3px rgba(255,255,255,0.98), 0 0 2px rgba(255,255,255,0.98); letter-spacing:-0.02em; pointer-events:none;">${escapeHtml(shortName)}</div>`,
                }),
            }).addTo(schoolLabelLayer);
        });
    }
}

async function drillIntoKecamatan(kecamatanName, kabupatenName, kecFeatureLayer = null) {
    const kecKey = normalizeWilayahKey(kecamatanName);
    const kabKey = normalizeWilayahKey(kabupatenName);

    clearHover();
    selectedDesaCard.value = null;
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
    selectedDesaCard.value = null;
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
    selectedDesaCard.value = null;
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

    if (kabupatenLayer && kabupatenLayer.bringToFront) {
        kabupatenLayer.bringToFront();
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
            fetch('/data/solo-raya-kecamatan.geojson'),
            fetch('/data/solo-raya-kabupaten.geojson'),
        ]);

        if (!kecRes.ok) throw new Error('Gagal memuat peta kecamatan Solo Raya.');
        const kecJson = await kecRes.json();
        kecJson.features = (kecJson.features || []).filter((f) =>
            SOLO_RAYA_KEYS.has(normalizeWilayahKey(f.properties?.kabupaten))
        );
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
                        if (aggregationLevel.value !== 'kecamatan') return;
                        handleHoverFeature('kecamatan', feature, layer, e.latlng);
                    },
                    mousemove: (e) => {
                        if (aggregationLevel.value !== 'kecamatan') return;
                        if (singleTooltip && map && map.hasLayer(singleTooltip)) {
                            singleTooltip.setLatLng(e.latlng);
                        }
                    },
                    mouseout: () => {
                        if (aggregationLevel.value !== 'kecamatan') return;
                        clearHover();
                    },
                    click: (e) => {
                        if (e.originalEvent) L.DomEvent.stopPropagation(e.originalEvent);
                        drillIntoKecamatan(feature.properties.kecamatan, feature.properties.kabupaten, layer);
                    },
                });
            },
        }).addTo(map);

        // Layer 2: Batas Kabupaten putus-putus
        if (kabJson) {
            kabupatenLayer = L.geoJSON(kabJson, {
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
                <div style="font-size: 10.5px; font-weight: 800; color: #be123c; margin-bottom: 3px;">📍 LOKASI KAMPUS SEKOLAH</div>
                <div style="font-weight: 800; font-size: 12px; color: #0f172a;">${SCHOOL_NAME}</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 3px;">${SCHOOL_ADDRESS}</div>
            </div>
        `);

        // Layer 4: Titik SD/MI/PKBM Overlay, Ring Kampus, Gerbang Tol & Label
        pointsCanvasRenderer = L.canvas({ padding: 0.3 });
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
        loadError.value = err?.message || 'Gagal memuat peta wilayah Solo Raya.';
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
    initMap();
});

onBeforeUnmount(() => {
    if (map) {
        map.remove();
        map = null;
    }
});

watch(
    () => [
        props.mode,
        props.schools,
        props.villages,
        props.tollGates,
        showSchoolDots.value,
        showCampusRadius.value,
        showTollGates.value,
        visibleTiers.basis_utama,
        visibleTiers.potensial,
        visibleTiers.vakum,
        visibleTiers.belum_tergarap,
    ],
    () => {
        selectedDesaCard.value = null;
        clearHover();
        updateLayersAndFocus();
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
            selectedDesaCard.value = null;
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
    <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-6 shadow-xs overflow-hidden w-full max-w-full min-w-0">
        <!-- Header: Identik dengan Peta Domisili Stat Siswa -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block shrink-0"></span>
                <div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                        {{ isWelfareMode ? 'Peta Domisili Siswa, Radius Kampus & Tol (Solo Raya)' : 'Peta Sebaran Sekolah Asal & Wilayah (Solo Raya)' }}
                    </h3>
                    <p class="text-[11px] text-slate-500 font-medium mt-0.5">
                        <template v-if="isWelfareMode">
                            <template v-if="drillKecamatan">
                                Mode Desa: <span class="font-bold text-emerald-800">Kec. {{ drillKecamatan.kecamatan }} ({{ drillKecamatan.kabupaten }})</span> — klik desa untuk Radius Kampus, Tol &amp; daftar siswa
                            </template>
                            <template v-else-if="aggregationLevel === 'desa'">
                                Warna per <span class="font-bold text-slate-700">Desa / Kelurahan</span> berdasarkan jumlah siswa domisili — klik desa untuk rincian
                            </template>
                            <template v-else>
                                Warna per <span class="font-bold text-slate-700">Kecamatan</span> berdasarkan siswa domisili + ring radius kampus &amp; gerbang tol
                            </template>
                        </template>
                        <template v-else>
                            <template v-if="drillKecamatan">
                                Mode Desa: <span class="font-bold text-emerald-800">Kec. {{ drillKecamatan.kecamatan }} ({{ drillKecamatan.kabupaten }})</span> — klik titik sekolah atau poligon desa
                            </template>
                            <template v-else-if="aggregationLevel === 'desa'">
                                Menampilkan warna per <span class="font-bold text-slate-700">Desa / Kelurahan</span> se-Solo Raya — klik desa untuk daftar SD/MI/PKBM
                            </template>
                            <template v-else>
                                Warna per <span class="font-bold text-slate-700">Kecamatan</span> berdasarkan siswa masuk — klik kecamatan untuk masuk ke level Desa &amp; titik SD
                            </template>
                        </template>
                    </p>
                </div>
            </div>

            <!-- Kontrol Kanan: Toggle Mode Peta, [Kecamatan | Desa], Titik SD/MI, Badge Ringkasan -->
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
                        Domisili Desa
                    </button>
                </div>

                <!-- Toggle Pilihan Wilayah: Kecamatan / Desa (Warna Kuning/Amber persis Peta Domisili) -->
                <div class="inline-flex items-center p-0.5 bg-amber-50 rounded-xl border border-amber-200/90 shadow-2xs">
                    <button
                        type="button"
                        @click="setAggregation('kecamatan')"
                        class="inline-flex items-center gap-1 sm:gap-1.5 px-2.5 sm:px-3 py-1 rounded-lg text-xs transition-all cursor-pointer"
                        :class="aggregationLevel === 'kecamatan'
                            ? 'bg-amber-400 text-amber-950 font-bold shadow-xs'
                            : 'text-amber-800/80 hover:text-amber-950 font-semibold'"
                    >
                        <span class="material-symbols-outlined text-[15px]">location_city</span>
                        <span>Kecamatan</span>
                    </button>
                    <button
                        type="button"
                        @click="setAggregation('desa')"
                        class="inline-flex items-center gap-1 sm:gap-1.5 px-2.5 sm:px-3 py-1 rounded-lg text-xs transition-all cursor-pointer"
                        :class="aggregationLevel === 'desa'
                            ? 'bg-amber-400 text-amber-950 font-bold shadow-xs'
                            : 'text-amber-800/80 hover:text-amber-950 font-semibold'"
                    >
                        <span class="material-symbols-outlined text-[15px]">holiday_village</span>
                        <span>Desa</span>
                    </button>
                </div>

                <!-- Toggle Overlay Khusus Mode Desa -->
                <template v-if="isWelfareMode">
                    <button
                        type="button"
                        @click="showCampusRadius = !showCampusRadius"
                        :class="[
                            'inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-[11px] font-bold border transition-all cursor-pointer',
                            showCampusRadius
                                ? 'bg-sky-700 text-white border-sky-800 shadow-2xs'
                                : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200',
                        ]"
                        title="Tampilkan ring radius 5 / 10 / 15 km dari Kampus"
                    >
                        <span class="material-symbols-outlined text-[14px]">radar</span>
                        <span>Radius</span>
                    </button>
                    <button
                        type="button"
                        @click="showTollGates = !showTollGates"
                        :class="[
                            'inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-[11px] font-bold border transition-all cursor-pointer',
                            showTollGates
                                ? 'bg-indigo-700 text-yellow-200 border-indigo-800 shadow-2xs'
                                : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200',
                        ]"
                        title="Tampilkan titik Gerbang Tol Solo Raya"
                    >
                        <span class="material-symbols-outlined text-[14px]">toll</span>
                        <span>Gerbang Tol</span>
                    </button>
                </template>

                <!-- Toggle Overlay Titik Sekolah -->
                <button
                    type="button"
                    @click="showSchoolDots = !showSchoolDots"
                    :class="[
                        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all cursor-pointer',
                        showSchoolDots
                            ? 'bg-emerald-900 text-yellow-200 border-emerald-950 shadow-2xs'
                            : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200',
                    ]"
                >
                    <span class="material-symbols-outlined text-[15px]">location_on</span>
                    <span>Titik SD/MI</span>
                </button>

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
            </div>
        </div>

        <!-- Baris Filter Layer Titik Sekolah (Saat Titik SD/MI Aktif) -->
        <div
            v-if="showSchoolDots"
            class="mb-3 p-2.5 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-wrap items-center justify-between gap-2"
        >
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-[10.5px] font-bold text-slate-500 uppercase tracking-wider mr-1">Filter Titik Tier:</span>
                <button
                    v-for="tierKey in ['basis_utama', 'potensial', 'vakum', 'belum_tergarap']"
                    :key="tierKey"
                    type="button"
                    @click="visibleTiers[tierKey] = !visibleTiers[tierKey]"
                    :class="[
                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border transition-all cursor-pointer select-none',
                        visibleTiers[tierKey]
                            ? PSB_TIERS[tierKey].badgeClass
                            : 'bg-white text-slate-400 border-slate-200 line-through opacity-70',
                    ]"
                >
                    <span
                        :class="[
                            'w-2 h-2 rounded-full',
                            visibleTiers[tierKey] ? PSB_TIERS[tierKey].dotClass : 'bg-slate-300',
                        ]"
                    ></span>
                    <span>{{ PSB_TIERS[tierKey].shortLabel }}</span>
                </button>
            </div>
            <span class="text-[11px] text-slate-500 font-medium">Klik titik mana pun untuk melihat profil &amp; siswa sekolahnya</span>
        </div>

        <!-- Container Peta (selalu render dimensi penuh agar Leaflet & fitBounds tidak 0x0) -->
        <div class="relative w-full h-[400px] sm:h-[480px] rounded-2xl border border-slate-200/90 bg-[#f8fafc] overflow-hidden">
            <!-- Loading State Overlay -->
            <div v-if="isLoading" class="absolute inset-0 bg-slate-50/95 z-[500] flex flex-col items-center justify-center gap-2">
                <span class="material-symbols-outlined text-3xl animate-spin text-emerald-700">progress_activity</span>
                <span class="text-xs font-semibold text-slate-500">Memuat peta kecamatan Solo Raya...</span>
            </div>

            <!-- Error State Overlay -->
            <div v-else-if="loadError" class="absolute inset-0 bg-white z-[500] flex flex-col items-center justify-center p-6 text-center">
                <span class="material-symbols-outlined text-3xl text-rose-500 mb-1">map</span>
                <p class="text-xs font-bold text-rose-800">{{ loadError }}</p>
            </div>

            <div ref="mapContainerRef" class="w-full h-full z-10 psb-leaflet-bg"></div>

            <!-- Breadcrumb & Back to Kecamatan (Saat Drill-Down ke Desa) -->
            <div
                v-if="drillKecamatan"
                class="absolute top-3 left-3 z-[400] flex flex-wrap items-center gap-2 max-w-[calc(100%-64px)]"
            >
                <button
                    type="button"
                    @click="backToAllKecamatan"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 text-xs font-bold shadow-md border border-emerald-950/30 cursor-pointer active:scale-95 transition-all"
                >
                    <span class="material-symbols-outlined text-[15px]">arrow_back</span>
                    <span>Semua Kecamatan</span>
                </button>
                <div class="px-3 py-1.5 rounded-xl bg-white/95 backdrop-blur-xs border border-slate-200 shadow-xs text-xs font-bold text-slate-800 flex items-center gap-2">
                    <span>Kec. {{ drillKecamatan.kecamatan }}, {{ drillKecamatan.kabupaten }}</span>
                    <button
                        type="button"
                        @click="emit('select-kecamatan', drillKecamatan)"
                        class="text-[11px] font-bold text-emerald-700 hover:text-emerald-900 underline cursor-pointer"
                        title="Terapkan ke filter tabel di bawah"
                    >
                        Filter Tabel ↓
                    </button>
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

            <!-- Floating Card Rincian Desa saat Desa Diklik -->
            <div
                v-if="selectedDesaCard"
                class="absolute bottom-3 right-3 z-[420] w-[calc(100%-24px)] sm:w-80 bg-white/98 backdrop-blur-sm rounded-2xl border border-emerald-200 shadow-xl overflow-hidden flex flex-col max-h-[280px]"
            >
                <div class="px-3.5 py-2.5 bg-emerald-50/80 border-b border-emerald-100 flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">
                            Desa {{ selectedDesaCard.desa }} • Kec. {{ selectedDesaCard.kecamatan }}
                        </span>
                        <span v-if="selectedDesaCard.mode === 'welfare'" class="text-xs font-extrabold text-slate-900">
                            {{ selectedDesaCard.students }} Siswa Domisili • {{ selectedDesaCard.campus_km ?? '-' }} km ke Kampus
                        </span>
                        <span v-else class="text-xs font-extrabold text-slate-900">
                            {{ selectedDesaCard.reached }} / {{ selectedDesaCard.total }} SD Tergarap ({{ selectedDesaCard.students }} Siswa)
                        </span>
                    </div>
                    <button
                        type="button"
                        @click="selectedDesaCard = null"
                        class="w-6 h-6 rounded-full hover:bg-emerald-100 flex items-center justify-center text-slate-500 cursor-pointer shrink-0"
                    >
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </button>
                </div>

                <!-- Welfare Mode Card Body -->
                <div v-if="selectedDesaCard.mode === 'welfare'" class="p-3 overflow-y-auto space-y-2.5 custom-bar-scrollbar">
                    <div class="grid grid-cols-2 gap-2 text-center">
                        <div class="p-2 rounded-xl bg-sky-50 border border-sky-200">
                            <div class="text-[9.5px] font-bold text-sky-700">Radius Kampus</div>
                            <div class="text-xs font-extrabold text-sky-950">{{ selectedDesaCard.campus_km ?? '-' }} km</div>
                            <div class="text-[9.5px] text-sky-700 font-semibold">{{ CAMPUS_RING_META[selectedDesaCard.campus_ring]?.label || 'Ring ' + selectedDesaCard.campus_ring }}</div>
                        </div>
                        <div class="p-2 rounded-xl bg-indigo-50 border border-indigo-200">
                            <div class="text-[9.5px] font-bold text-indigo-700">Gerbang Tol</div>
                            <div class="text-xs font-extrabold text-indigo-950">{{ selectedDesaCard.toll_km ?? '-' }} km</div>
                            <div class="text-[9.5px] text-indigo-700 font-semibold truncate" :title="selectedDesaCard.toll_name">{{ selectedDesaCard.toll_name }}</div>
                        </div>
                    </div>

                    <button
                        v-if="selectedDesaCard.village"
                        type="button"
                        @click="emit('select-village', selectedDesaCard.village)"
                        class="w-full py-2 px-3 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 text-xs font-bold transition-colors cursor-pointer"
                    >
                        Lihat Rincian Desa &amp; Daftar Siswa ({{ selectedDesaCard.students }}) →
                    </button>
                </div>

                <!-- School Mode Card Body -->
                <div v-else class="p-2.5 overflow-y-auto space-y-1.5 custom-bar-scrollbar">
                    <p v-if="selectedDesaCard.schools.length === 0" class="text-[11px] text-slate-400 text-center py-4">
                        Tidak ada SD/MI/PKBM terdata di desa ini pada filter aktif.
                    </p>
                    <button
                        v-for="sc in selectedDesaCard.schools"
                        :key="sc.npsn"
                        type="button"
                        @click="emit('select-school', sc)"
                        class="w-full text-left p-2 rounded-xl border border-slate-200/80 hover:border-emerald-300 hover:bg-emerald-50/40 transition-all flex items-center justify-between gap-2 cursor-pointer"
                    >
                        <div class="min-w-0">
                            <p class="text-[11.5px] font-bold text-slate-900 truncate">{{ sc.nama }}</p>
                            <p class="text-[10px] text-slate-500">{{ sc.bentuk }} • {{ sc.status }}</p>
                        </div>
                        <span
                            :class="[
                                'px-2 py-0.5 rounded-full text-[10px] font-bold border shrink-0',
                                PSB_TIERS[sc.tier].badgeClass,
                            ]"
                        >
                            {{ sc.totalStudents }} Siswa
                        </span>
                    </button>
                </div>
            </div>

            <!-- Floating Hint Kanan Bawah (jika tidak ada card desa terbuka) -->
            <div
                v-else-if="hoveredInfo"
                class="sm:hidden absolute bottom-3 right-3 z-[400] bg-white/95 backdrop-blur-xs rounded-xl border border-slate-200 px-3 py-2 shadow-md text-xs pointer-events-none"
            >
                <div class="font-bold text-slate-900">{{ hoveredInfo.desa ? `Desa ${hoveredInfo.desa}` : `Kec. ${hoveredInfo.kecamatan}` }}</div>
                <div v-if="isWelfareMode" class="text-[11px] text-emerald-800 font-semibold">
                    {{ hoveredInfo.desa ? `${hoveredInfo.totalStudents || 0} Siswa • ${hoveredInfo.campus_km ?? '-'} km ke Kampus` : `${hoveredInfo.students} Siswa • Rata ${hoveredInfo.avgCampusKm ?? '-'} km` }}
                </div>
                <div v-else class="text-[11px] text-emerald-800 font-semibold">{{ hoveredInfo.reached }} / {{ hoveredInfo.total }} SD • {{ hoveredInfo.students }} Siswa</div>
            </div>

            <!-- Tombol Legenda Mobile -->
            <button
                v-if="!isLegendOpenMobile && !selectedDesaCard"
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
                    { 'hidden sm:flex': selectedDesaCard },
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
                        <span class="font-bold text-rose-700">Kampus</span>
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
                    <div v-if="showSchoolDots" class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span><span>Basis</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span><span>Potensial</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-600 inline-block"></span><span>Vakum</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-slate-500 inline-block"></span><span>0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.psb-leaflet-bg.leaflet-container {
    background-color: #f8fafc !important;
}
.psb-map-text-label,
.psb-school-point-label {
    background: transparent !important;
    border: none !important;
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
