<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

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
});

const mapContainer = ref(null);
let map = null;
let geojsonLayer = null;
let desaGeojsonLayer = null;
let kabupatenLayer = null;
let schoolMarker = null;
let activeHoverLayer = null;
let singleTooltip = null;
let isLockedByClick = false;
let lastInteractionTime = 0;

// Lokasi Kampus Sekolah (SMPIT & PPTQ Ulil Albab Karanganyar)
const SCHOOL_COORDS = [-7.4989289, 110.822498];
const SCHOOL_NAME = 'PPTQ & SMPIT TQ Ulil Albab Karanganyar';
const SCHOOL_ADDRESS = 'Sidorejo, Selokaton, Kec. Gondangrejo, Kab. Karanganyar';

// Level Agregasi: 'kecamatan' atau 'desa'
const aggregationLevel = ref('kecamatan');

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
        s = s.replace(/^(kec(amatan)?|kab(upaten)?|kota(madya)?|desa|kel(urahan)?|ds)\.?\s*/i, '');
    }
    s = s.replace(/[^a-zA-Z0-9]/g, '').toLowerCase();
    if (s === 'solo' || s === 'kotasolo') {
        s = 'surakarta';
    }
    return s;
}

// Helper perapian nama desa (menghilangkan awalan "Desa", "Ds.", "Kelurahan", dsb)
function cleanDesaName(raw) {
    if (!raw) return '';
    let s = String(raw).trim();
    let prev = '';
    while (s !== prev) {
        prev = s;
        s = s.replace(/^(desa|kel(urahan)?|ds)\.?\s*/i, '');
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

// Agregasi sebaran siswa aktif per kecamatan di Solo Raya & Grobogan serta Luar Wilayah
const distribution = computed(() => {
    const list = props.students || [];
    const countsByKey = {}; // featureKey -> count
    const studentsByKey = {}; // featureKey -> [student, ...]
    const outsideMap = {};  // regency / text -> { count, students }
    let outsideTotal = 0;
    let coverageTotal = 0;
    let activeTotal = 0;

    list.forEach(s => {
        const rawStatus = (s.status_keaktifan || s.student_status || 'aktif').toLowerCase().trim();
        const isActive = !rawStatus.includes('tidak') && rawStatus !== 'dropout' && rawStatus !== 'mutasi_keluar';
        if (!isActive) return;
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

// Agregasi sebaran siswa aktif per desa/kelurahan
const desaDistribution = computed(() => {
    const list = props.students || [];
    const countsByKey = {}; // featureKey -> count
    const studentsByKey = {}; // featureKey -> [student, ...]
    const desasByKey = {};
    let totalCovered = 0;

    list.forEach(s => {
        const rawStatus = (s.status_keaktifan || s.student_status || 'aktif').toLowerCase().trim();
        const isActive = !rawStatus.includes('tidak') && rawStatus !== 'dropout' && rawStatus !== 'mutasi_keluar';
        if (!isActive) return;

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
    const students = distribution.value.studentsByKey[key] || [];
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

function handleKeyDown(e) {
    if (e.key === 'Escape') {
        if (selectedKecamatanModal.value) {
            closeKecamatanModal();
        }
        if (showOutsideModal.value) {
            showOutsideModal.value = false;
        }
    }
}

function isStudentActive(student) {
    if (!student) return false;
    const status = (student.status_keaktifan || student.student_status || '').toLowerCase();
    return !status.includes('tidak') && status !== 'dropout' && status !== 'mutasi_keluar' && status !== 'lulus';
}

function getStudentStatusLabel(student) {
    if (!student) return 'Tidak Aktif';
    if (student.status_keaktifan) {
        return student.status_keaktifan;
    }
    return isStudentActive(student) ? 'Aktif' : 'Tidak Aktif';
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
        const desa = (s.desa || '').toLowerCase();
        const kelas = (s.kelas || '').toLowerCase();
        return nama.includes(q) || nisn.includes(q) || desa.includes(q) || kelas.includes(q);
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
        fillColor: getColor(count),
        weight: 0.5,
        opacity: 0.85,
        color: count > 0 ? '#059669' : '#cbd5e1',
        fillOpacity: count > 0 ? 0.92 : 0.45,
    };
}

function selectFeature(feature, layer, latlng = null) {
    hoveredDesa.value = null;
    if (activeHoverLayer && activeHoverLayer !== layer) {
        activeHoverLayer.setStyle(featureStyle(activeHoverLayer.feature));
    }
    activeHoverLayer = layer;

    layer.setStyle({
        weight: 2.5,
        color: '#047857',
        fillOpacity: 1,
    });

    if (layer.bringToFront) {
        layer.bringToFront();
    }
    if (kabupatenLayer && kabupatenLayer.bringToFront) {
        kabupatenLayer.bringToFront();
    }

    const count = getFeatureCount(feature);
    const pct = distribution.value.activeTotal > 0
        ? Math.round((count / distribution.value.activeTotal) * 100)
        : 0;

    hoveredFeature.value = {
        kecamatan: feature.properties.kecamatan,
        kabupaten: feature.properties.kabupaten,
        count,
        percentage: pct,
    };

    if (singleTooltip && map) {
        const pos = latlng || layer.getBounds().getCenter();
        singleTooltip
            .setLatLng(pos)
            .setContent(`
                <div class="px-2 py-1.5 text-xs text-slate-800 leading-tight">
                    <div class="font-bold text-slate-900 text-[12.5px]">Kec. ${feature.properties.kecamatan}</div>
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
    }
}

function selectDesaFeature(feature, layer, latlng = null) {
    hoveredFeature.value = null;
    if (activeHoverLayer && activeHoverLayer !== layer) {
        if (activeHoverLayer.feature) {
            activeHoverLayer.setStyle(desaFeatureStyle(activeHoverLayer.feature));
        }
    }
    activeHoverLayer = layer;

    layer.setStyle({
        weight: 2.5,
        color: '#047857',
        fillOpacity: 1,
    });

    if (layer.bringToFront) {
        layer.bringToFront();
    }
    if (geojsonLayer && geojsonLayer.bringToFront) {
        geojsonLayer.bringToFront();
    }
    if (kabupatenLayer && kabupatenLayer.bringToFront) {
        kabupatenLayer.bringToFront();
    }

    const count = getDesaFeatureCount(feature);
    const pct = distribution.value.activeTotal > 0
        ? Math.round((count / distribution.value.activeTotal) * 100)
        : 0;

    const dKey = normalizeKey(feature.properties.desa);
    const kecKey = normalizeKey(feature.properties.kecamatan);
    const kabKey = normalizeKey(feature.properties.kabupaten);
    const key = `${dKey}_${kecKey}_${kabKey}`;
    const students = desaDistribution.value.studentsByKey[key] || [];

    hoveredDesa.value = {
        desa: feature.properties.desa,
        kecamatan: feature.properties.kecamatan,
        kabupaten: feature.properties.kabupaten,
        count,
        percentage: pct,
        students,
    };

    if (singleTooltip && map) {
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
    }
}

function handleSelect(feature, layer, latlng = null) {
    lastInteractionTime = Date.now();
    isLockedByClick = true;
    if (aggregationLevel.value === 'kecamatan') {
        selectFeature(feature, layer, latlng);
    } else {
        selectDesaFeature(feature, layer, latlng);
    }
}

function clearHover() {
    isLockedByClick = false;
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

function initDesaLayer() {
    if (!map || !desaGeojsonData.value) return;
    if (desaGeojsonLayer) {
        desaGeojsonLayer.remove();
    }

    desaGeojsonLayer = L.geoJSON(desaGeojsonData.value, {
        style: desaFeatureStyle,
        onEachFeature: (feature, layer) => {
            layer.on({
                mouseover: (e) => {
                    if (aggregationLevel.value !== 'desa') return;
                    if (Date.now() - lastInteractionTime < 600) return;
                    if (!isLockedByClick) {
                        selectDesaFeature(feature, layer, e.latlng);
                    }
                },
                mousemove: (e) => {
                    if (aggregationLevel.value !== 'desa') return;
                    if (Date.now() - lastInteractionTime < 600) return;
                    if (!isLockedByClick && singleTooltip && map && map.hasLayer(singleTooltip)) {
                        singleTooltip.setLatLng(e.latlng);
                    }
                },
                mouseout: (e) => {
                    if (aggregationLevel.value !== 'desa') return;
                    if (Date.now() - lastInteractionTime < 600) return;
                    if (!isLockedByClick) {
                        clearHover();
                    } else if (activeHoverLayer !== layer) {
                        layer.setStyle(desaFeatureStyle(feature));
                    }
                },
                click: (e) => {
                    if (aggregationLevel.value !== 'desa') return;
                    if (e.originalEvent) {
                        L.DomEvent.stopPropagation(e.originalEvent);
                    }
                    lastInteractionTime = Date.now();
                    isLockedByClick = true;
                    selectDesaFeature(feature, layer, e.latlng);
                },
            });
        },
    });

    if (aggregationLevel.value === 'desa') {
        desaGeojsonLayer.addTo(map);
        if (geojsonLayer) {
            geojsonLayer.setStyle({
                fill: false,
                weight: 1.2,
                color: '#64748b',
                opacity: 0.85,
            });
            geojsonLayer.bringToFront();
        }
        if (kabupatenLayer) {
            kabupatenLayer.bringToFront();
        }
    }
}

async function loadDesaGeoJson() {
    if (desaGeojsonData.value || isDesaLoading.value) return;
    try {
        isDesaLoading.value = true;
        const res = await fetch('/data/solo-raya-desa.geojson');
        if (!res.ok) throw new Error('Gagal memuat batas desa');
        desaGeojsonData.value = await res.json();
        initDesaLayer();
    } catch (err) {
        console.error('Error loading desa geojson:', err);
    } finally {
        isDesaLoading.value = false;
    }
}

function updateMapLayers() {
    if (!map) return;

    if (aggregationLevel.value === 'kecamatan') {
        // Sembunyikan layer desa
        if (desaGeojsonLayer && map.hasLayer(desaGeojsonLayer)) {
            map.removeLayer(desaGeojsonLayer);
        }
        // Kembalikan style choropleth poligon kecamatan
        if (geojsonLayer) {
            if (!map.hasLayer(geojsonLayer)) {
                geojsonLayer.addTo(map);
            }
            geojsonLayer.eachLayer(layer => {
                layer.setStyle(featureStyle(layer.feature));
            });
        }
    } else {
        // Mode Per Desa:
        if (desaGeojsonLayer) {
            if (!map.hasLayer(desaGeojsonLayer)) {
                desaGeojsonLayer.addTo(map);
            }
            desaGeojsonLayer.eachLayer(layer => {
                layer.setStyle(desaFeatureStyle(layer.feature));
            });
            // Poligon kecamatan di atasnya jadi garis batas transparan
            if (geojsonLayer) {
                geojsonLayer.setStyle({
                    fill: false,
                    weight: 1.2,
                    color: '#64748b',
                    opacity: 0.85,
                });
                geojsonLayer.bringToFront();
            }
            if (kabupatenLayer) {
                kabupatenLayer.bringToFront();
            }
        } else if (!isDesaLoading.value) {
            loadDesaGeoJson();
        }
    }
}

async function setAggregation(level) {
    if (aggregationLevel.value === level) return;
    aggregationLevel.value = level;
    clearHover();

    if (level === 'desa' && !desaGeojsonData.value) {
        await loadDesaGeoJson();
    }
    updateMapLayers();
}

function resetZoom() {
    clearHover();
    if (map && geojsonLayer) {
        map.fitBounds(geojsonLayer.getBounds(), { padding: [24, 24] });
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
    if (!geojsonData.value?.features || !geojsonLayer || !latlng) return null;
    const lng = latlng.lng;
    const lat = latlng.lat;

    for (const f of geojsonData.value.features) {
        const b = f._bbox;
        if (b && (lng < b.minLng || lng > b.maxLng || lat < b.minLat || lat > b.maxLat)) {
            continue;
        }
        if (isPointInPolygonGeom(lng, lat, f.geometry.coordinates, f.geometry.type)) {
            let matchedLayer = null;
            geojsonLayer.eachLayer(l => {
                if (l.feature === f || (l.feature?.properties?.kode && l.feature.properties.kode === f.properties.kode)) {
                    matchedLayer = l;
                }
            });
            return { feature: f, layer: matchedLayer };
        }
    }
    return null;
}

async function loadGeoJsonAndInitMap() {
    try {
        isLoading.value = true;
        loadError.value = null;

        const response = await fetch('/data/solo-raya-kecamatan.geojson');
        if (!response.ok) {
            throw new Error(`Gagal memuat data batas kecamatan (status ${response.status})`);
        }
        const data = await response.json();

        data.features.forEach(f => {
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

        geojsonData.value = data;

        let kabData = null;
        try {
            const kabResponse = await fetch('/data/solo-raya-kabupaten.geojson');
            if (kabResponse.ok) {
                kabData = await kabResponse.json();
            }
        } catch (e) {
            console.warn('Gagal memuat batas kabupaten:', e);
        }

        await nextTick();
        if (!mapContainer.value) return;

        map = L.map(mapContainer.value, {
            zoomControl: false,
            attributionControl: false,
            minZoom: 8,
            maxZoom: 15,
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

        // Layer 1: Poligon Kecamatan
        geojsonLayer = L.geoJSON(data, {
            style: featureStyle,
            onEachFeature: (feature, layer) => {
                layer.on({
                    mouseover: (e) => {
                        if (aggregationLevel.value !== 'kecamatan') return;
                        if (Date.now() - lastInteractionTime < 800) return;
                        if (!isLockedByClick) {
                            selectFeature(feature, layer, e.latlng);
                        }
                    },
                    mousemove: (e) => {
                        if (aggregationLevel.value !== 'kecamatan') return;
                        if (Date.now() - lastInteractionTime < 800) return;
                        if (!isLockedByClick && singleTooltip && map && map.hasLayer(singleTooltip)) {
                            singleTooltip.setLatLng(e.latlng);
                        }
                    },
                    mouseout: (e) => {
                        if (aggregationLevel.value !== 'kecamatan') return;
                        if (Date.now() - lastInteractionTime < 800) return;
                        if (!isLockedByClick) {
                            clearHover();
                        } else if (activeHoverLayer !== layer) {
                            layer.setStyle(featureStyle(feature));
                        }
                    },
                    click: (e) => {
                        if (aggregationLevel.value !== 'kecamatan') return;
                        if (e.originalEvent) {
                            L.DomEvent.stopPropagation(e.originalEvent);
                        }
                        handleSelect(feature, layer, e.latlng);
                    },
                });
            },
        }).addTo(map);

        geojsonLayer.eachLayer((layer) => {
            attachDirectTouchListeners(layer.feature, layer);
        });

        // Sentuhan pada container map
        let containerTouchStartX = 0;
        let containerTouchStartY = 0;
        let containerTouchStartTime = 0;

        mapContainer.value.addEventListener('touchstart', (e) => {
            if (e.touches && e.touches.length === 1) {
                containerTouchStartX = e.touches[0].clientX;
                containerTouchStartY = e.touches[0].clientY;
                containerTouchStartTime = Date.now();
            }
        }, { passive: true });

        mapContainer.value.addEventListener('touchend', (e) => {
            if (!containerTouchStartTime) return;
            const touch = e.changedTouches ? e.changedTouches[0] : null;
            if (touch) {
                const dx = Math.abs(touch.clientX - containerTouchStartX);
                const dy = Math.abs(touch.clientY - containerTouchStartY);
                const dt = Date.now() - containerTouchStartTime;
                containerTouchStartTime = 0;

                if (dx < 30 && dy < 30 && dt < 600) {
                    if (map && mapContainer.value && aggregationLevel.value === 'kecamatan') {
                        try {
                            const rect = mapContainer.value.getBoundingClientRect();
                            const pt = L.point(touch.clientX - rect.left, touch.clientY - rect.top);
                            const latlng = map.containerPointToLatLng(pt);
                            const result = findFeatureAndLayerAtLatLng(latlng);
                            if (result && result.layer) {
                                handleSelect(result.feature, result.layer, latlng);
                            }
                        } catch (_) {}
                    }
                }
            }
        }, { passive: true });

        map.on('click', (e) => {
            if (Date.now() - lastInteractionTime < 400) return;
            if (aggregationLevel.value === 'kecamatan') {
                const result = findFeatureAndLayerAtLatLng(e.latlng);
                if (result && result.layer) {
                    handleSelect(result.feature, result.layer, e.latlng);
                } else {
                    isLockedByClick = false;
                    clearHover();
                }
            } else {
                isLockedByClick = false;
                clearHover();
            }
        });

        // Layer 2: Batas Kabupaten Putus-putus
        if (kabData) {
            kabupatenLayer = L.geoJSON(kabData, {
                style: {
                    fill: false,
                    weight: 1.8,
                    color: '#1e293b',
                    dashArray: '6, 5',
                    opacity: 0.9,
                },
                interactive: false,
            }).addTo(map);

            kabupatenLayer.eachLayer(l => {
                const el = l.getElement ? l.getElement() : l._path;
                if (el) {
                    el.style.pointerEvents = 'none';
                }
            });
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

        map.fitBounds(geojsonLayer.getBounds(), { padding: [24, 24] });
        updateMapLayers();

        // Muat data desa di background agar langsung siap ketika user klik "Per Desa"
        loadDesaGeoJson();
    } catch (err) {
        loadError.value = err.message || 'Gagal memuat peta';
    } finally {
        isLoading.value = false;
    }
}

watch(() => props.students, () => {
    updateMapLayers();
}, { deep: true });

watch(activeView, (newVal) => {
    if (newVal === 'peta' && map) {
        nextTick(() => {
            map.invalidateSize();
        });
    }
});

onMounted(() => {
    loadGeoJsonAndInitMap();
    window.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeyDown);
    clearHover();
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
    <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-6 shadow-xs mb-5">
        <!-- Header: Judul, Tab Switcher [Per Kecamatan | Per Desa] & [Peta | Bar] & Ringkasan Siswa -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                <div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                        Sebaran Domisili Siswa (Solo Raya & Grobogan)
                    </h3>
                    <p class="text-[11px] text-slate-400 font-medium mt-0.5">
                        Berdasarkan <span class="font-bold text-slate-600">{{ aggregationLevel === 'desa' ? 'Desa / Kelurahan' : 'Kecamatan' }}</span> domisili siswa aktif — {{ unit }} (TA {{ academicYear }})
                    </p>
                </div>
            </div>

            <!-- Bagian Kanan: Toggle Agregasi [Per Kecamatan | Per Desa], Toggle View [Peta | Bar] & Ringkasan -->
            <div class="flex items-center gap-2 self-start sm:self-auto shrink-0 flex-wrap">
                <!-- Toggle Pilihan: Per Kecamatan vs Per Desa (Diatas container peta) -->
                <div class="inline-flex items-center p-0.5 bg-slate-100/90 rounded-xl border border-slate-200/90 shadow-2xs">
                    <button
                        type="button"
                        @click="setAggregation('kecamatan')"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs transition-all cursor-pointer"
                        :class="aggregationLevel === 'kecamatan'
                            ? 'bg-white text-emerald-900 font-bold shadow-xs'
                            : 'text-slate-500 hover:text-slate-800 font-semibold'"
                    >
                        <span class="material-symbols-outlined text-[15px]">location_city</span>
                        <span>Per Kecamatan</span>
                    </button>
                    <button
                        type="button"
                        @click="setAggregation('desa')"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs transition-all cursor-pointer"
                        :class="aggregationLevel === 'desa'
                            ? 'bg-white text-emerald-900 font-bold shadow-xs'
                            : 'text-slate-500 hover:text-slate-800 font-semibold'"
                    >
                        <span class="material-symbols-outlined text-[15px]">holiday_village</span>
                        <span>Per Desa</span>
                    </button>
                </div>

                <!-- Toggle Pilihan Tampilan: Peta / Bar -->
                <div class="inline-flex items-center p-0.5 bg-slate-100/90 rounded-xl border border-slate-200/90 shadow-2xs">
                    <button
                        type="button"
                        @click="activeView = 'peta'"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs transition-all cursor-pointer"
                        :class="activeView === 'peta'
                            ? 'bg-white text-emerald-900 font-bold shadow-xs'
                            : 'text-slate-500 hover:text-slate-800 font-semibold'"
                    >
                        <span class="material-symbols-outlined text-[15px]">map</span>
                        <span>Peta</span>
                    </button>
                    <button
                        type="button"
                        @click="activeView = 'bar'"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs transition-all cursor-pointer"
                        :class="activeView === 'bar'
                            ? 'bg-white text-emerald-900 font-bold shadow-xs'
                            : 'text-slate-500 hover:text-slate-800 font-semibold'"
                    >
                        <span class="material-symbols-outlined text-[15px]">bar_chart</span>
                        <span>Bar</span>
                    </button>
                </div>

                <!-- Badge Total Tercakup -->
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-800 shadow-2xs">
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
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- VIEW 1: TAMPILAN PETA VEKTOR                                 -->
        <!-- ============================================================ -->
        <div v-show="activeView === 'peta'" class="relative w-full h-[380px] sm:h-[460px] rounded-2xl border border-slate-200/90 overflow-hidden bg-slate-50">
            <!-- Loading State -->
            <div v-if="isLoading || isDesaLoading" class="absolute inset-0 bg-slate-50/90 z-20 flex flex-col items-center justify-center gap-2">
                <span class="material-symbols-outlined text-3xl text-emerald-600 animate-spin">progress_activity</span>
                <span class="text-xs font-semibold text-slate-500">
                    {{ isDesaLoading ? 'Memuat batas wilayah desa/kelurahan Solo Raya & Grobogan...' : 'Memuat batas wilayah Solo Raya & Grobogan...' }}
                </span>
            </div>

            <!-- Error State -->
            <div v-else-if="loadError" class="absolute inset-0 bg-white z-20 flex flex-col items-center justify-center p-4 text-center">
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
            <div ref="mapContainer" class="w-full h-full z-10"></div>

            <!-- Floating Controls di Kiri Atas Peta: Reset Zoom & Toggle Cepat [Kecamatan | Desa] -->
            <div class="absolute top-3 left-3 z-[400] flex items-center gap-2">
                <button
                    type="button"
                    @click="resetZoom"
                    class="px-2.5 py-1.5 rounded-xl bg-white/95 hover:bg-white text-slate-700 text-xs font-bold shadow-md border border-slate-200/80 transition-all flex items-center gap-1 cursor-pointer"
                    title="Kembalikan zoom ke seluruh wilayah"
                >
                    <span class="material-symbols-outlined text-[15px]">crop_free</span>
                    <span class="hidden sm:inline">Reset Zoom</span>
                </button>

                <!-- Pilihan per kecamatan / per desa di dalam peta -->
                <div class="inline-flex items-center p-0.5 bg-white/95 backdrop-blur-xs rounded-xl border border-slate-200/90 shadow-md">
                    <button
                        type="button"
                        @click="setAggregation('kecamatan')"
                        class="px-2 py-1 rounded-lg text-xs transition-all cursor-pointer flex items-center gap-1"
                        :class="aggregationLevel === 'kecamatan'
                            ? 'bg-emerald-700 text-white font-bold shadow-2xs'
                            : 'text-slate-600 hover:text-slate-900 font-semibold'"
                        title="Tampilkan peta per kecamatan"
                    >
                        <span class="material-symbols-outlined text-[14px]">location_city</span>
                        <span class="text-[11px]">Kecamatan</span>
                    </button>
                    <button
                        type="button"
                        @click="setAggregation('desa')"
                        class="px-2 py-1 rounded-lg text-xs transition-all cursor-pointer flex items-center gap-1"
                        :class="aggregationLevel === 'desa'
                            ? 'bg-emerald-700 text-white font-bold shadow-2xs'
                            : 'text-slate-600 hover:text-slate-900 font-semibold'"
                        title="Tampilkan peta per desa"
                    >
                        <span class="material-symbols-outlined text-[14px]">holiday_village</span>
                        <span class="text-[11px]">Desa</span>
                    </button>
                </div>
            </div>

            <!-- Live Status Hover/Touch Bar: Mode Desa -->
            <div
                v-if="hoveredDesa"
                class="absolute bottom-3 left-3 right-3 sm:left-auto sm:right-3 sm:bottom-3 sm:max-w-xs z-[500] bg-white/95 backdrop-blur-xs rounded-xl border border-slate-200/90 p-2.5 sm:p-3 shadow-lg text-xs pointer-events-auto touch-manipulation transition-all animate-in fade-in duration-150"
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

            <!-- Live Status Hover/Touch Bar: Mode Kecamatan -->
            <div
                v-else-if="hoveredFeature"
                class="absolute bottom-3 left-3 right-3 sm:left-auto sm:right-3 sm:bottom-3 sm:max-w-xs z-[500] bg-white/95 backdrop-blur-xs rounded-xl border border-slate-200/90 p-2.5 sm:p-3 shadow-lg text-xs pointer-events-auto touch-manipulation transition-all animate-in fade-in duration-150"
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
                    <span class="text-[12px] font-bold text-emerald-800 pr-6">Kec. {{ hoveredFeature.kecamatan }}</span>
                    <span class="text-[10.5px] text-slate-500 font-medium">Kab/Kota {{ hoveredFeature.kabupaten }}</span>
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
                </div>
            </div>

            <!-- Petunjuk Saat Belum Ada Hover -->
            <div
                v-else
                class="hidden sm:block absolute bottom-3 right-3 z-[400] max-w-xs bg-white/90 backdrop-blur-xs rounded-xl border border-slate-200/80 px-3 py-2 shadow-xs text-xs pointer-events-none"
            >
                <span class="text-[11px] text-slate-400 font-medium">
                    {{ aggregationLevel === 'desa' ? 'Arahkan kursor atau sentuh desa untuk detail' : 'Arahkan kursor atau sentuh kecamatan untuk detail' }}
                </span>
            </div>

            <!-- Floating Legend di Kiri Bawah -->
            <div
                class="absolute bottom-3 left-3 z-[400] bg-white/95 backdrop-blur-xs rounded-xl border border-slate-200/90 p-2 sm:p-2.5 shadow-md flex flex-col gap-1.5"
                :class="{ 'hidden sm:flex': hoveredFeature || hoveredDesa }"
            >
                <div>
                    <div class="text-[10px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                        {{ aggregationLevel === 'desa' ? 'Siswa per Desa' : 'Siswa per Kecamatan' }}
                    </div>
                    <div class="flex items-center gap-1 text-[10px] text-slate-600 font-medium">
                        <div class="flex items-center gap-0.5">
                            <span class="w-3.5 h-3 rounded-xs border border-slate-300 inline-block bg-[#ffffff]"></span>
                            <span class="mr-1">0</span>
                        </div>
                        <div class="flex items-center gap-0.5">
                            <span class="w-3.5 h-3 rounded-xs inline-block bg-[#6ee7b7]"></span>
                            <span class="mr-1">1-4</span>
                        </div>
                        <div class="flex items-center gap-0.5">
                            <span class="w-3.5 h-3 rounded-xs inline-block bg-[#10b981]"></span>
                            <span class="mr-1">5-9</span>
                        </div>
                        <div class="flex items-center gap-0.5">
                            <span class="w-3.5 h-3 rounded-xs inline-block bg-[#047857]"></span>
                            <span class="mr-1">10-19</span>
                        </div>
                        <div class="flex items-center gap-0.5">
                            <span class="w-3.5 h-3 rounded-xs inline-block bg-[#064e3b]"></span>
                            <span>20+</span>
                        </div>
                    </div>
                </div>

                <!-- Legenda Batas Wilayah & Titik Sekolah -->
                <div class="pt-1.5 border-t border-slate-200/70 flex items-center gap-2.5 sm:gap-3 text-[10px] text-slate-600 font-medium flex-wrap">
                    <div class="flex items-center gap-1.5" title="Lokasi Kampus Sekolah">
                        <span class="w-3.5 h-3.5 rounded-full bg-rose-600 border border-white shadow-2xs flex items-center justify-center text-white shrink-0">
                            <span class="material-symbols-outlined text-[10px] leading-none">school</span>
                        </span>
                        <span class="font-bold text-rose-700">Lokasi Sekolah</span>
                    </div>
                    <div class="flex items-center gap-1.5" title="Garis putus-putus batas kabupaten">
                        <span class="w-4 border-t-2 border-dashed border-[#1e293b] inline-block"></span>
                        <span class="font-bold text-slate-800">Batas Kabupaten</span>
                    </div>
                    <div class="flex items-center gap-1.5" title="Garis batas kecamatan">
                        <span class="w-4 h-[1.5px] bg-[#64748b] inline-block"></span>
                        <span>Batas Kecamatan</span>
                    </div>
                    <div v-if="aggregationLevel === 'desa'" class="flex items-center gap-1.5" title="Garis halus batas desa">
                        <span class="w-4 h-[1px] bg-[#cbd5e1] inline-block"></span>
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
                                ({{ selectedKecamatanModal.percentage }}% dari seluruh siswa aktif) • {{ unit }} (TA {{ academicYear }})
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
                        Menampilkan {{ modalFilteredStudents.length }} dari {{ selectedKecamatanModal.count }} siswa
                    </span>
                </div>

                <!-- Modal Body: Daftar Siswa (Scrollable) -->
                <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-3 space-y-2 custom-bar-scrollbar">
                    <div
                        v-if="modalFilteredStudents.length > 0"
                        class="space-y-2"
                    >
                        <a
                            v-for="(student, sIdx) in modalFilteredStudents"
                            :key="student.id || sIdx"
                            :href="student.id ? `/students/${student.id}` : '#'"
                            target="_blank"
                            class="p-2.5 sm:p-3 rounded-2xl bg-slate-50/80 hover:bg-emerald-50/40 hover:border-emerald-200 border border-slate-200/80 flex items-center justify-between gap-3 transition-colors group/row"
                            title="Buka profil siswa di tab baru"
                        >
                            <!-- Nomor & Nama Siswa -->
                            <div class="min-w-0 flex items-center gap-2.5 flex-1">
                                <span class="w-6 h-6 rounded-lg bg-white border border-slate-200 text-[10.5px] font-bold text-slate-600 flex items-center justify-center shrink-0">
                                    {{ sIdx + 1 }}
                                </span>

                                <div class="min-w-0">
                                    <div class="text-xs sm:text-[13px] font-bold text-slate-900 group-hover/row:text-emerald-950 break-words leading-tight">
                                        {{ student.nama }}
                                    </div>
                                    <div v-if="student.desa || student.kelas" class="text-[11px] text-slate-500 font-medium mt-0.5">
                                        <span v-if="student.desa">Desa {{ student.desa }}</span>
                                        <span v-if="student.desa && student.kelas"> • </span>
                                        <span v-if="student.kelas">Kelas {{ student.kelas }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- JK & Status Keaktifan -->
                            <div class="flex items-center gap-2 shrink-0">
                                <span
                                    class="text-[10px] sm:text-[11px] font-bold px-2 py-0.5 rounded-lg border text-center min-w-[24px]"
                                    :class="student.jk === 'L'
                                        ? 'bg-sky-50 text-sky-800 border-sky-200'
                                        : (student.jk === 'P' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-slate-50 text-slate-700 border-slate-200')"
                                    :title="student.jk === 'L' ? 'Laki-laki' : (student.jk === 'P' ? 'Perempuan' : student.jk)"
                                >
                                    <span class="hidden sm:inline">{{ student.jk === 'L' ? 'Laki-laki' : (student.jk === 'P' ? 'Perempuan' : student.jk) }}</span>
                                    <span class="sm:hidden">{{ student.jk || '-' }}</span>
                                </span>

                                <span
                                    class="text-[10px] sm:text-[11px] font-bold px-2.5 py-0.5 rounded-lg border flex items-center gap-1.5"
                                    :class="isStudentActive(student)
                                        ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                        : 'bg-rose-50 text-rose-800 border-rose-200'"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full"
                                        :class="isStudentActive(student) ? 'bg-emerald-600' : 'bg-rose-600'"
                                    ></span>
                                    <span>{{ getStudentStatusLabel(student) }}</span>
                                </span>
                            </div>
                        </a>
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
