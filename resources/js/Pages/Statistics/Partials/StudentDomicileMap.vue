<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

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

const geojsonData = ref(null);
const isLoading = ref(true);
const loadError = ref(null);

const activeRegencyFilter = ref('Semua'); // 'Semua' | 'Karanganyar' | 'Surakarta' | ...
const hoveredFeature = ref(null);
const showOutsideModal = ref(false);

const soloRayaRegencies = [
    'Semua',
    'Karanganyar',
    'Surakarta',
    'Sukoharjo',
    'Boyolali',
    'Sragen',
    'Klaten',
    'Wonogiri',
];

// Helper normalisasi string kecamatan
function normalizeKey(str) {
    if (!str) return '';
    let s = String(str).trim();
    // Hilangkan awalan 'Kec.', 'Kecamatan', 'Distrik'
    s = s.replace(/^kec(amatan)?\.?\s+/i, '');
    // Hilangkan semua karakter non-alfanumerik
    s = s.replace(/[^a-zA-Z0-9]/g, '').toLowerCase();
    return s;
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

// Agregasi sebaran siswa aktif per kecamatan di Solo Raya & Luar Solo Raya
const distribution = computed(() => {
    const list = props.students || [];
    const countsByKey = {}; // featureKey -> count
    const outsideMap = {};  // regency / text -> count
    let outsideTotal = 0;
    let soloRayaTotal = 0;
    let activeTotal = 0;

    list.forEach(s => {
        const isActive = (s.student_status || 'aktif').toLowerCase() === 'aktif';
        if (!isActive) return;
        activeTotal++;

        const rawKec = s.kecamatan || '';
        const rawKab = s.kabupaten || '';
        const normKec = normalizeKey(rawKec);
        const normKab = normalizeKey(rawKab);

        if (!normKec) {
            outsideTotal++;
            const groupName = rawKab || 'Alamat Belum Terdata';
            outsideMap[groupName] = (outsideMap[groupName] || 0) + 1;
            return;
        }

        const candidates = soloRayaKecMap.value[normKec];
        if (candidates && candidates.length > 0) {
            let matched = candidates[0];
            if (candidates.length > 1) {
                // Disambiguate by kabupaten
                const found = candidates.find(c => normalizeKey(c.kabupaten) === normKab || normKab.includes(normalizeKey(c.kabupaten)));
                if (found) matched = found;
            }

            const featureKey = `${normalizeKey(matched.kecamatan)}_${normalizeKey(matched.kabupaten)}`;
            countsByKey[featureKey] = (countsByKey[featureKey] || 0) + 1;
            soloRayaTotal++;
        } else {
            outsideTotal++;
            const groupName = rawKab ? `${rawKab} (Kec. ${rawKec || '-'})` : (rawKec || 'Luar Solo Raya');
            outsideMap[groupName] = (outsideMap[groupName] || 0) + 1;
        }
    });

    const outsideList = Object.entries(outsideMap)
        .map(([region, count]) => ({ region, count }))
        .sort((a, b) => b.count - a.count);

    return {
        countsByKey,
        outsideList,
        outsideTotal,
        soloRayaTotal,
        activeTotal,
    };
});

// Ranking Top Kecamatan di Solo Raya
const topKecamatans = computed(() => {
    if (!geojsonData.value?.features) return [];
    const list = [];
    geojsonData.value.features.forEach(f => {
        const key = `${normalizeKey(f.properties.kecamatan)}_${normalizeKey(f.properties.kabupaten)}`;
        const count = distribution.value.countsByKey[key] || 0;
        if (count > 0) {
            list.push({
                kecamatan: f.properties.kecamatan,
                kabupaten: f.properties.kabupaten,
                count,
                percentage: distribution.value.activeTotal > 0
                    ? Math.round((count / distribution.value.activeTotal) * 100)
                    : 0,
            });
        }
    });
    return list.sort((a, b) => b.count - a.count).slice(0, 5);
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
    const isFiltered = activeRegencyFilter.value === 'Semua' ||
        feature.properties.kabupaten === activeRegencyFilter.value;
    const count = getFeatureCount(feature);

    return {
        fillColor: getColor(count),
        weight: isFiltered ? 1 : 0.4,
        opacity: isFiltered ? 0.9 : 0.25,
        color: isFiltered ? '#64748b' : '#cbd5e1',
        fillOpacity: isFiltered ? (count > 0 ? 0.92 : 0.6) : 0.15,
    };
}

function updateMapLayers() {
    if (!geojsonLayer) return;
    geojsonLayer.eachLayer(layer => {
        const feature = layer.feature;
        layer.setStyle(featureStyle(feature));

        // Update tooltip content
        const count = getFeatureCount(feature);
        const pct = distribution.value.activeTotal > 0
            ? Math.round((count / distribution.value.activeTotal) * 100)
            : 0;

        layer.unbindTooltip();
        layer.bindTooltip(`
            <div class="px-2 py-1.5 text-xs text-slate-800 leading-tight">
                <div class="font-bold text-slate-900 text-[12.5px]">Kec. ${feature.properties.kecamatan}</div>
                <div class="text-[11px] text-slate-500 font-medium">Kab/Kota ${feature.properties.kabupaten}</div>
                <div class="mt-1 pt-1 border-t border-slate-200/80 flex items-center justify-between gap-3">
                    <span class="font-semibold text-emerald-800">${count} Siswa</span>
                    <span class="text-[11px] text-slate-400">(${pct}%)</span>
                </div>
            </div>
        `, {
            sticky: true,
            direction: 'top',
            className: 'leaflet-custom-tooltip',
            opacity: 0.98,
        });
    });
}

function filterByRegency(regName) {
    activeRegencyFilter.value = regName;
    updateMapLayers();

    if (!map || !geojsonLayer) return;

    if (regName === 'Semua') {
        map.fitBounds(geojsonLayer.getBounds(), { padding: [24, 24] });
    } else {
        const bounds = L.latLngBounds();
        let found = false;
        geojsonLayer.eachLayer(layer => {
            if (layer.feature.properties.kabupaten === regName) {
                bounds.extend(layer.getBounds());
                found = true;
            }
        });
        if (found) {
            map.fitBounds(bounds, { padding: [32, 32] });
        }
    }
}

function resetZoom() {
    filterByRegency('Semua');
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
        geojsonData.value = data;

        await nextTick();
        if (!mapContainer.value) return;

        // Inisialisasi Map Leaflet TANPA layer gambar jalan (pure clean background)
        map = L.map(mapContainer.value, {
            zoomControl: false,
            attributionControl: false,
            minZoom: 8,
            maxZoom: 15,
            scrollWheelZoom: true,
        });

        // Kontrol zoom di kanan atas
        L.control.zoom({ position: 'topright' }).addTo(map);

        geojsonLayer = L.geoJSON(data, {
            style: featureStyle,
            onEachFeature: (feature, layer) => {
                layer.on({
                    mouseover: (e) => {
                        const target = e.target;
                        target.setStyle({
                            weight: 2.5,
                            color: '#047857',
                            fillOpacity: 1,
                        });
                        target.bringToFront();
                        hoveredFeature.value = {
                            kecamatan: feature.properties.kecamatan,
                            kabupaten: feature.properties.kabupaten,
                            count: getFeatureCount(feature),
                        };
                    },
                    mouseout: (e) => {
                        geojsonLayer.resetStyle(e.target);
                        hoveredFeature.value = null;
                    },
                    click: (e) => {
                        map.fitBounds(e.target.getBounds(), { padding: [50, 50], maxZoom: 12 });
                    },
                });
            },
        }).addTo(map);

        map.fitBounds(geojsonLayer.getBounds(), { padding: [24, 24] });
        updateMapLayers();
    } catch (err) {
        loadError.value = err.message || 'Gagal memuat peta';
    } finally {
        isLoading.value = false;
    }
}

watch(() => props.students, () => {
    updateMapLayers();
}, { deep: true });

onMounted(() => {
    loadGeoJsonAndInitMap();
});

onBeforeUnmount(() => {
    if (map) {
        map.remove();
        map = null;
    }
});
</script>

<template>
    <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-6 shadow-xs mb-5">
        <!-- Header Peta & Stat Ringkas -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                <div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                        Peta Sebaran Domisili Siswa (Solo Raya)
                    </h3>
                    <p class="text-[11px] text-slate-400 font-medium mt-0.5">
                        Berdasarkan kecamatan domisili siswa aktif — {{ unit }} (TA {{ academicYear }})
                    </p>
                </div>
            </div>

            <!-- Ringkasan Solo Raya vs Luar Solo Raya -->
            <div class="flex items-center gap-2 self-start sm:self-auto shrink-0 flex-wrap">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-800 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                    <span>Solo Raya: {{ distribution.soloRayaTotal }} Siswa</span>
                    <span class="text-[11px] text-emerald-600 font-medium">
                        ({{ distribution.activeTotal > 0 ? Math.round((distribution.soloRayaTotal / distribution.activeTotal) * 100) : 0 }}%)
                    </span>
                </div>

                <button
                    v-if="distribution.outsideTotal > 0"
                    type="button"
                    @click="showOutsideModal = true"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-50 hover:bg-amber-100 border border-amber-300 text-xs font-bold text-amber-900 transition-all cursor-pointer shadow-2xs"
                    title="Klik untuk melihat rincian wilayah luar Solo Raya"
                >
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span>Luar Solo Raya: {{ distribution.outsideTotal }} Siswa</span>
                    <span class="material-symbols-outlined text-[14px]">info</span>
                </button>
            </div>
        </div>

        <!-- Filter Kapsul Daerah (Solo Raya: 7 Daerah) -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-2 mb-3 scrollbar-none">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider shrink-0 mr-1">Fokus:</span>
            <button
                v-for="reg in soloRayaRegencies"
                :key="reg"
                type="button"
                @click="filterByRegency(reg)"
                class="px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap transition-all cursor-pointer border shadow-2xs"
                :class="activeRegencyFilter === reg
                    ? 'bg-emerald-900 text-yellow-200 border-emerald-950 font-bold'
                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100'"
            >
                {{ reg }}
            </button>
        </div>

        <!-- Kontainer Kanvas Peta -->
        <div class="relative w-full h-[380px] sm:h-[460px] rounded-2xl border border-slate-200/90 overflow-hidden bg-slate-50">
            <!-- Loading State -->
            <div v-if="isLoading" class="absolute inset-0 bg-slate-50/90 z-20 flex flex-col items-center justify-center gap-2">
                <span class="material-symbols-outlined text-3xl text-emerald-600 animate-spin">progress_activity</span>
                <span class="text-xs font-semibold text-slate-500">Memuat batas wilayah kecamatan Solo Raya...</span>
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

            <!-- Floating Tool / Reset Button di Kanan Atas -->
            <div class="absolute top-3 left-3 z-[400] flex items-center gap-2">
                <button
                    type="button"
                    @click="resetZoom"
                    class="px-2.5 py-1.5 rounded-xl bg-white/95 hover:bg-white text-slate-700 text-xs font-bold shadow-md border border-slate-200/80 transition-all flex items-center gap-1 cursor-pointer"
                    title="Kembalikan zoom ke seluruh Solo Raya"
                >
                    <span class="material-symbols-outlined text-[15px]">crop_free</span>
                    <span class="hidden sm:inline">Reset Zoom</span>
                </button>
            </div>

            <!-- Live Status Hover Bar di Bawah Peta -->
            <div class="absolute bottom-3 right-3 z-[400] max-w-[260px] sm:max-w-xs bg-white/95 backdrop-blur-xs rounded-xl border border-slate-200/90 p-2 sm:p-2.5 shadow-md text-xs pointer-events-none">
                <div v-if="hoveredFeature" class="flex flex-col">
                    <span class="text-[11px] font-bold text-emerald-800">Kec. {{ hoveredFeature.kecamatan }}</span>
                    <span class="text-[10px] text-slate-500">{{ hoveredFeature.kabupaten }}</span>
                    <span class="text-xs font-bold text-slate-900 mt-0.5">
                        {{ hoveredFeature.count }} Siswa
                        <span class="text-[10px] font-normal text-slate-500">
                            ({{ distribution.activeTotal > 0 ? Math.round((hoveredFeature.count / distribution.activeTotal) * 100) : 0 }}%)
                        </span>
                    </span>
                </div>
                <div v-else class="text-[11px] text-slate-400 font-medium">
                    Arahkan kursor atau sentuh kecamatan untuk melihat detail
                </div>
            </div>

            <!-- Floating Legend (Skala Gradasi Warna) di Kiri Bawah -->
            <div class="absolute bottom-3 left-3 z-[400] bg-white/95 backdrop-blur-xs rounded-xl border border-slate-200/90 p-2 sm:p-2.5 shadow-md">
                <div class="text-[10px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                    Jumlah Siswa
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
        </div>

        <!-- Rincian Top Kecamatan & Wilayah Lainnya -->
        <div class="mt-4 pt-3 border-t border-slate-100 flex flex-col md:flex-row items-start md:items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="font-bold text-slate-700">Top Sebaran:</span>
                <span
                    v-for="(item, idx) in topKecamatans"
                    :key="idx"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-200 text-slate-700 font-medium text-[11px]"
                >
                    <span class="font-bold text-slate-900">{{ item.kecamatan }}</span>
                    <span class="text-slate-400">({{ item.kabupaten }}):</span>
                    <span class="font-bold text-emerald-700">{{ item.count }}</span>
                </span>
                <span v-if="topKecamatans.length === 0" class="text-slate-400 text-[11px]">
                    Belum ada data kecamatan terdata pada filter ini.
                </span>
            </div>

            <div v-if="distribution.outsideTotal > 0" class="text-[11px] text-slate-500 font-medium">
                Terdapat <span class="font-bold text-amber-700">{{ distribution.outsideTotal }} siswa</span> di luar 7 kab/kota Solo Raya.
            </div>
        </div>

        <!-- Modal Detail Siswa Luar Solo Raya -->
        <div
            v-if="showOutsideModal"
            class="fixed inset-0 bg-slate-900/40 backdrop-blur-2xs z-[999] flex items-center justify-center p-4"
            @click.self="showOutsideModal = false"
        >
            <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-5 shadow-xl animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        <h4 class="text-sm font-bold text-slate-900">Domisili Luar Solo Raya</h4>
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
                    Total {{ distribution.outsideTotal }} siswa terdata beralamat di luar 7 kabupaten/kota Solo Raya:
                </p>

                <div class="max-h-60 overflow-y-auto space-y-1.5 pr-1">
                    <div
                        v-for="(item, idx) in distribution.outsideList"
                        :key="idx"
                        class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs"
                    >
                        <span class="font-semibold text-slate-800">{{ item.region }}</span>
                        <span class="font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200">
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
</template>

<style>
/* Styling tooltip peta agar senada dengan UI modern */
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
</style>
