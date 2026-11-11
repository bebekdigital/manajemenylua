<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
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

// Perbesar toleransi geser Leaflet untuk layar sentuh/mobile (mencegah tap dianggap drag)
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
let kabupatenLayer = null;
let activeHoverLayer = null;
let singleTooltip = null;
let isLockedByClick = false;
let lastInteractionTime = 0;

const geojsonData = ref(null);
const isLoading = ref(true);
const loadError = ref(null);

const hoveredFeature = ref(null);
const showOutsideModal = ref(false);

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

// Agregasi sebaran siswa aktif per kecamatan di Solo Raya & Grobogan serta Luar Wilayah
const distribution = computed(() => {
    const list = props.students || [];
    const countsByKey = {}; // featureKey -> count
    const outsideMap = {};  // regency / text -> count
    let outsideTotal = 0;
    let coverageTotal = 0;
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
            coverageTotal++;
        } else {
            outsideTotal++;
            const groupName = rawKab ? `${rawKab} (Kec. ${rawKec || '-'})` : (rawKec || 'Luar Wilayah');
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
        coverageTotal,
        soloRayaTotal: coverageTotal, // for backwards compat
        activeTotal,
    };
});

// Switcher mode: 'peta' atau 'bar'
const activeView = ref('peta');
const barSearchQuery = ref('');
const barFilterLimit = ref('15'); // '10' | '15' | '25' | 'all'

// Daftar seluruh kecamatan dengan jumlah siswa untuk tampilan Horizontal Bar
const barKecamatans = computed(() => {
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

const horizontalBarChartData = computed(() => {
    const list = displayedKecamatans.value;
    return {
        labels: list.map(item => `Kec. ${item.kecamatan} (${item.kabupaten})`),
        datasets: [
            {
                label: 'Jumlah Siswa',
                data: list.map(item => item.count),
                backgroundColor: '#047857',
                hoverBackgroundColor: '#064e3b',
                borderRadius: 6,
                borderSkipped: false,
                barThickness: 18,
                maxBarThickness: 22,
            },
        ],
    };
});

const horizontalBarChartOptions = computed(() => ({
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: '#0f172a',
            padding: 10,
            cornerRadius: 10,
            titleFont: { size: 12, weight: 'bold', family: "'Plus Jakarta Sans', 'Inter', sans-serif" },
            bodyFont: { size: 11, family: "'Plus Jakarta Sans', 'Inter', sans-serif" },
            callbacks: {
                label: (ctx) => {
                    const item = displayedKecamatans.value[ctx.dataIndex];
                    if (!item) return '';
                    return ` ${item.count} Siswa (${item.percentage}%)`;
                },
            },
        },
        datalabels: {
            anchor: 'end',
            align: 'right',
            color: '#064e3b',
            font: { weight: 'bold', size: 11, family: "'Plus Jakarta Sans', 'Inter', sans-serif" },
            offset: 4,
            formatter: (value) => `${value} siswa`,
        },
    },
    scales: {
        x: {
            beginAtZero: true,
            grid: {
                color: 'rgba(226, 232, 240, 0.8)',
                drawBorder: false,
            },
            ticks: {
                font: { size: 10.5, family: "'Plus Jakarta Sans', 'Inter', sans-serif" },
                color: '#94a3b8',
                precision: 0,
            },
            title: {
                display: true,
                text: 'Jumlah Siswa Aktif',
                color: '#64748b',
                font: { size: 11, weight: '600', family: "'Plus Jakarta Sans', 'Inter', sans-serif" },
            },
            border: { display: false },
        },
        y: {
            grid: {
                display: false,
            },
            ticks: {
                font: { size: 11, weight: '600', family: "'Plus Jakarta Sans', 'Inter', sans-serif" },
                color: '#1e293b',
            },
            border: { display: false },
        },
    },
    animation: {
        duration: 500,
        easing: 'easeOutQuart',
    },
}));

const chartHeight = computed(() => {
    const count = displayedKecamatans.value.length;
    if (count === 0) return 360;
    return Math.max(count * 38, 360);
});

watch(activeView, (newView) => {
    if (newView === 'peta') {
        nextTick(() => {
            if (map) {
                map.invalidateSize();
            }
        });
    }
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

function updateMapLayers() {
    if (!geojsonLayer) return;
    geojsonLayer.eachLayer(layer => {
        layer.setStyle(featureStyle(layer.feature));
    });
}

function resetZoom() {
    clearHover();
    if (map && geojsonLayer) {
        map.fitBounds(geojsonLayer.getBounds(), { padding: [24, 24] });
    }
}

function clearHover() {
    isLockedByClick = false;
    if (activeHoverLayer) {
        activeHoverLayer.setStyle(featureStyle(activeHoverLayer.feature));
        activeHoverLayer = null;
    }
    if (singleTooltip && map && map.hasLayer(singleTooltip)) {
        map.removeLayer(singleTooltip);
    }
    hoveredFeature.value = null;
}

function selectFeature(feature, layer, latlng = null) {
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

function handleSelect(feature, layer, latlng = null) {
    lastInteractionTime = Date.now();
    isLockedByClick = true;
    selectFeature(feature, layer, latlng);
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

            // Jika pergeseran jari < 25px dan durasi < 600ms, ini adalah ketukan (TAP) valid!
            if (dx < 25 && dy < 25 && dt < 600) {
                let tapLatLng = null;
                if (map && mapContainer.value) {
                    try {
                        const rect = mapContainer.value.getBoundingClientRect();
                        const pt = L.point(touch.clientX - rect.left, touch.clientY - rect.top);
                        tapLatLng = map.containerPointToLatLng(pt);
                    } catch (_) {}
                }
                handleSelect(feature, layer, tapLatLng);
            }
        }
    }, { passive: true });
}

async function loadGeoJsonAndInitMap() {
    try {
        isLoading.value = true;
        loadError.value = null;

        // 1. Muat batas kecamatan (146 features)
        const response = await fetch('/data/solo-raya-kecamatan.geojson');
        if (!response.ok) {
            throw new Error(`Gagal memuat data batas kecamatan (status ${response.status})`);
        }
        const data = await response.json();
        geojsonData.value = data;

        // 2. Muat batas kabupaten untuk batas putus-putus (8 features)
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

        // Inisialisasi Map Leaflet TANPA layer gambar jalan (pure clean background)
        map = L.map(mapContainer.value, {
            zoomControl: false,
            attributionControl: false,
            minZoom: 8,
            maxZoom: 15,
            scrollWheelZoom: true,
            tapTolerance: 25,
        });

        // Kontrol zoom di kanan atas
        L.control.zoom({ position: 'topright' }).addTo(map);

        // Inisialisasi SATU-SATUNYA tooltip mengambang untuk seluruh peta (mencegah tooltip menumpuk)
        singleTooltip = L.tooltip({
            direction: 'auto',
            className: 'leaflet-custom-tooltip',
            offset: [0, -10],
            opacity: 0.98,
        });

        // Layer 1: Batas Kecamatan (Interaktif: hover desktop & sentuh/klik mobile)
        geojsonLayer = L.geoJSON(data, {
            style: featureStyle,
            onEachFeature: (feature, layer) => {
                layer.on({
                    mouseover: (e) => {
                        if (Date.now() - lastInteractionTime < 800) return;
                        if (!isLockedByClick) {
                            selectFeature(feature, layer, e.latlng);
                        }
                    },
                    mousemove: (e) => {
                        if (Date.now() - lastInteractionTime < 800) return;
                        if (!isLockedByClick && singleTooltip && map && map.hasLayer(singleTooltip)) {
                            singleTooltip.setLatLng(e.latlng);
                        }
                    },
                    mouseout: (e) => {
                        if (Date.now() - lastInteractionTime < 800) return;
                        if (!isLockedByClick) {
                            clearHover();
                        } else if (activeHoverLayer !== layer) {
                            layer.setStyle(featureStyle(feature));
                        }
                    },
                    click: (e) => {
                        if (e.originalEvent) {
                            L.DomEvent.stopPropagation(e.originalEvent);
                        }
                        handleSelect(feature, layer, e.latlng);
                    },
                });
            },
        }).addTo(map);

        // Pasang direct touch listener ke setiap elemen SVG path poligon kecamatan
        geojsonLayer.eachLayer((layer) => {
            attachDirectTouchListeners(layer.feature, layer);
        });

        // Klik pada area kosong peta mereset pilihan
        map.on('click', () => {
            if (Date.now() - lastInteractionTime < 600) return;
            isLockedByClick = false;
            clearHover();
        });

        // Layer 2: Batas Kabupaten Putus-putus (Dashed Line)
        if (kabData) {
            kabupatenLayer = L.geoJSON(kabData, {
                style: {
                    fill: false,
                    weight: 1.8,             // Tidak ketebalan, proporsional
                    color: '#1e293b',          // Dark slate jelas
                    dashArray: '6, 5',        // Garis putus-putus rapi
                    opacity: 0.9,
                },
                interactive: false,          // Klik/hover tembus langsung ke kecamatan di bawahnya
            }).addTo(map);

            kabupatenLayer.eachLayer(l => {
                const el = l.getElement ? l.getElement() : l._path;
                if (el) {
                    el.style.pointerEvents = 'none';
                }
            });
        }

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

watch(activeView, (newVal) => {
    if (newVal === 'peta' && map) {
        nextTick(() => {
            map.invalidateSize();
        });
    }
});

onMounted(() => {
    loadGeoJsonAndInitMap();
});

onBeforeUnmount(() => {
    clearHover();
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
        <!-- Header: Judul, Tab Switcher [Peta / Bar], & Ringkasan Siswa -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                <div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                        Sebaran Domisili Siswa (Solo Raya & Grobogan)
                    </h3>
                    <p class="text-[11px] text-slate-400 font-medium mt-0.5">
                        Berdasarkan kecamatan domisili siswa aktif — {{ unit }} (TA {{ academicYear }})
                    </p>
                </div>
            </div>

            <!-- Bagian Kanan: Toggle View [Peta | Bar] & Ringkasan -->
            <div class="flex items-center gap-2 self-start sm:self-auto shrink-0 flex-wrap">
                <!-- Toggle Pilihan Peta / Bar -->
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

                <!-- Badge Total Wilayah Tercakup -->
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-800 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                    <span>{{ distribution.coverageTotal }} Siswa</span>
                    <span class="text-[11px] text-emerald-600 font-medium">
                        ({{ distribution.activeTotal > 0 ? Math.round((distribution.coverageTotal / distribution.activeTotal) * 100) : 0 }}%)
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
            <div v-if="isLoading" class="absolute inset-0 bg-slate-50/90 z-20 flex flex-col items-center justify-center gap-2">
                <span class="material-symbols-outlined text-3xl text-emerald-600 animate-spin">progress_activity</span>
                <span class="text-xs font-semibold text-slate-500">Memuat batas wilayah kecamatan Solo Raya & Grobogan...</span>
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

            <!-- Floating Tool / Reset Button di Kiri Atas -->
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
            </div>

            <!-- Live Status Hover & Touch Bar (Responsive: Bottom Sheet di Mobile, Pojok Kanan Bawah di Desktop) -->
            <div
                v-if="hoveredFeature"
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
                </div>
            </div>
            <div
                v-else
                class="hidden sm:block absolute bottom-3 right-3 z-[400] max-w-xs bg-white/90 backdrop-blur-xs rounded-xl border border-slate-200/80 px-3 py-2 shadow-xs text-xs pointer-events-none"
            >
                <span class="text-[11px] text-slate-400 font-medium">
                    Arahkan kursor atau sentuh kecamatan untuk detail
                </span>
            </div>

            <!-- Floating Legend (Skala Gradasi Warna & Garis Batas) di Kiri Bawah -->
            <div
                class="absolute bottom-3 left-3 z-[400] bg-white/95 backdrop-blur-xs rounded-xl border border-slate-200/90 p-2 sm:p-2.5 shadow-md flex flex-col gap-1.5"
                :class="{ 'hidden sm:flex': hoveredFeature }"
            >
                <div>
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

                <!-- Legenda Garis Batas Wilayah -->
                <div class="pt-1.5 border-t border-slate-200/70 flex items-center gap-3 text-[10px] text-slate-600 font-medium">
                    <div class="flex items-center gap-1.5" title="Garis putus-putus batas kabupaten">
                        <span class="w-4 border-t-2 border-dashed border-[#1e293b] inline-block"></span>
                        <span class="font-bold text-slate-800">Batas Kabupaten</span>
                    </div>
                    <div class="flex items-center gap-1.5" title="Garis halus batas kecamatan">
                        <span class="w-4 h-[1px] bg-[#94a3b8] inline-block"></span>
                        <span>Kecamatan</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- VIEW 2: TAMPILAN GRAFIK HORIZONTAL BAR SEBARAN KECAMATAN     -->
        <!-- ============================================================ -->
        <div v-show="activeView === 'bar'" class="w-full h-[380px] sm:h-[460px] rounded-2xl border border-slate-200/90 bg-white p-3 sm:p-4 flex flex-col">
            <!-- Toolbar: Pencarian Cepat & Filter Jumlah Top -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-3 mb-2 border-b border-slate-100 shrink-0">
                <div class="relative w-full sm:w-72">
                    <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                    <input
                        v-model="barSearchQuery"
                        type="text"
                        placeholder="Cari kecamatan atau kabupaten..."
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
                        ({{ displayedKecamatans.length }} kecamatan)
                    </span>
                </div>
            </div>

            <!-- Kontainer Grafik Horizontal Bar (Scrollable jika data banyak) -->
            <div class="flex-1 overflow-y-auto pr-2 custom-bar-scrollbar">
                <div
                    v-if="displayedKecamatans.length > 0"
                    class="w-full relative"
                    :style="{ height: `${chartHeight}px`, minHeight: '340px' }"
                >
                    <Bar
                        :data="horizontalBarChartData"
                        :options="horizontalBarChartOptions"
                    />
                </div>

                <!-- Empty State jika tidak ada data -->
                <div v-else class="h-full flex flex-col items-center justify-center py-12 text-center">
                    <span class="material-symbols-outlined text-3xl text-slate-300 mb-1">bar_chart</span>
                    <p class="text-xs font-bold text-slate-700">Tidak ada data kecamatan ditemukan</p>
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

