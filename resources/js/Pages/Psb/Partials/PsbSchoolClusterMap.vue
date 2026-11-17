<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import { PSB_TIERS, SOLO_RAYA_KABUPATENS, normalizeWilayahKey } from '@/Utils/psbMatcher';

const props = defineProps({
    schools: {
        type: Array,
        default: () => [],
    },
    selectedKabupaten: {
        type: String,
        default: 'Semua',
    },
});

const emit = defineEmits(['select-school']);

const mapContainerRef = ref(null);
let mapInstance = null;
let boundaryLayer = null;
let clusterLayer = null;
let canvasRenderer = null;
let boundaryFeatures = [];

const visibleTiers = reactive({
    basis_utama: true,
    potensial: true,
    vakum: true,
    belum_tergarap: true,
});

const tierCounts = computed(() => {
    const counts = { basis_utama: 0, potensial: 0, vakum: 0, belum_tergarap: 0 };
    props.schools.forEach((s) => {
        if (counts[s.tier] !== undefined) counts[s.tier]++;
    });
    return counts;
});

const filteredMapSchools = computed(() =>
    props.schools.filter(
        (s) =>
            visibleTiers[s.tier] &&
            typeof s.lat === 'number' &&
            typeof s.lng === 'number' &&
            !Number.isNaN(s.lat) &&
            !Number.isNaN(s.lng)
    )
);

const SOLO_RAYA_KEYS = new Set(SOLO_RAYA_KABUPATENS.map((k) => normalizeWilayahKey(k)));

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
            ? 'Titik koordinat sekolah'
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
                ${escapeHtml(school.desa ? school.desa + ', ' : '')}Kec. ${escapeHtml(school.kecamatan)}, ${escapeHtml(school.kabupaten)}
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

function getClusterDominantColor(cell) {
    if (cell.counts.basis_utama > 0) return { bg: '#059669', ring: 'rgba(16, 185, 129, 0.28)', text: '#ffffff' };
    if (cell.counts.potensial > 0) return { bg: '#d97706', ring: 'rgba(245, 158, 11, 0.28)', text: '#ffffff' };
    if (cell.counts.vakum > 0) return { bg: '#e11d48', ring: 'rgba(244, 63, 94, 0.25)', text: '#ffffff' };
    return { bg: '#475569', ring: 'rgba(100, 116, 139, 0.22)', text: '#ffffff' };
}

function renderClusters() {
    if (!mapInstance || !clusterLayer) return;

    clusterLayer.clearLayers();

    const list = filteredMapSchools.value;
    if (list.length === 0) return;

    const bounds = mapInstance.getBounds().pad(0.12);
    const inView = list.filter((s) => bounds.contains([s.lat, s.lng]));
    const zoom = mapInstance.getZoom();

    // Tampilkan titik individual jika sudah zoom dekat atau jumlah titik sedikit
    if (zoom >= 14 || inView.length <= 160) {
        // Urutkan agar titik belum_tergarap di bawah dan titik basis_utama di paling atas
        const order = { belum_tergarap: 0, vakum: 1, potensial: 2, basis_utama: 3 };
        const sorted = [...inView].sort((a, b) => (order[a.tier] || 0) - (order[b.tier] || 0));

        sorted.forEach((school) => {
            const tier = PSB_TIERS[school.tier] || PSB_TIERS.belum_tergarap;
            const isZero = school.tier === 'belum_tergarap';
            const marker = L.circleMarker([school.lat, school.lng], {
                renderer: canvasRenderer,
                radius: isZero ? 5 : (school.tier === 'basis_utama' ? 9 : 7),
                fillColor: tier.hex,
                color: '#ffffff',
                weight: isZero ? 1 : 2,
                opacity: 0.95,
                fillOpacity: isZero ? 0.65 : 0.92,
            });

            marker.bindPopup(() => buildSchoolPopupElement(school), { maxWidth: 280 });
            marker.addTo(clusterLayer);
        });
        return;
    }

    // Spatial Grid Clustering berdasarkan koordinat layar
    const cellSize = zoom <= 9 ? 62 : (zoom <= 11 ? 54 : 46);
    const cells = new Map();

    inView.forEach((school) => {
        const pt = mapInstance.latLngToLayerPoint([school.lat, school.lng]);
        const cx = Math.floor(pt.x / cellSize);
        const cy = Math.floor(pt.y / cellSize);
        const key = `${cx}_${cy}`;

        if (!cells.has(key)) {
            cells.set(key, {
                schools: [],
                latSum: 0,
                lngSum: 0,
                minLat: school.lat,
                maxLat: school.lat,
                minLng: school.lng,
                maxLng: school.lng,
                counts: { basis_utama: 0, potensial: 0, vakum: 0, belum_tergarap: 0 },
            });
        }

        const cell = cells.get(key);
        cell.schools.push(school);
        cell.latSum += school.lat;
        cell.lngSum += school.lng;
        if (school.lat < cell.minLat) cell.minLat = school.lat;
        if (school.lat > cell.maxLat) cell.maxLat = school.lat;
        if (school.lng < cell.minLng) cell.minLng = school.lng;
        if (school.lng > cell.maxLng) cell.maxLng = school.lng;
        if (cell.counts[school.tier] !== undefined) {
            cell.counts[school.tier]++;
        }
    });

    cells.forEach((cell) => {
        const total = cell.schools.length;
        if (total === 1) {
            const school = cell.schools[0];
            const tier = PSB_TIERS[school.tier] || PSB_TIERS.belum_tergarap;
            const isZero = school.tier === 'belum_tergarap';
            const marker = L.circleMarker([school.lat, school.lng], {
                renderer: canvasRenderer,
                radius: isZero ? 5.5 : 8,
                fillColor: tier.hex,
                color: '#ffffff',
                weight: isZero ? 1.2 : 2,
                opacity: 0.95,
                fillOpacity: isZero ? 0.7 : 0.92,
            });
            marker.bindPopup(() => buildSchoolPopupElement(school), { maxWidth: 280 });
            marker.addTo(clusterLayer);
            return;
        }

        const avgLat = cell.latSum / total;
        const avgLng = cell.lngSum / total;
        const colors = getClusterDominantColor(cell);
        const size = total >= 100 ? 42 : (total >= 20 ? 36 : 30);

        const icon = L.divIcon({
            className: 'psb-cluster-icon',
            iconSize: [size, size],
            iconAnchor: [size / 2, size / 2],
            html: `
                <div style="
                    width:${size}px;
                    height:${size}px;
                    border-radius:9999px;
                    background:${colors.bg};
                    color:${colors.text};
                    border:3px solid #ffffff;
                    box-shadow:0 0 0 5px ${colors.ring}, 0 4px 10px rgba(15,23,42,0.22);
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    font-weight:800;
                    font-size:${total >= 100 ? '11px' : '12px'};
                    font-family:Inter,system-ui,sans-serif;
                    cursor:pointer;
                ">${total}</div>
            `,
        });

        const clusterMarker = L.marker([avgLat, avgLng], { icon });
        const activeCount = total - cell.counts.belum_tergarap;
        clusterMarker.bindTooltip(
            activeCount > 0
                ? `${total} Sekolah (${activeCount} tergarap, ${cell.counts.belum_tergarap} berstatus 0)`
                : `${total} Sekolah (Belum Tergarap 0)`,
            { direction: 'top', offset: [0, -10] }
        );

        clusterMarker.on('click', () => {
            const box = L.latLngBounds(
                [cell.minLat, cell.minLng],
                [cell.maxLat, cell.maxLng]
            );
            if (box.getNorthEast().equals(box.getSouthWest())) {
                mapInstance.setView([avgLat, avgLng], Math.min(zoom + 2, 16), { animate: true });
            } else {
                mapInstance.fitBounds(box.pad(0.2), { maxZoom: Math.max(zoom + 2, 15), animate: true });
            }
        });

        clusterMarker.addTo(clusterLayer);
    });
}

function fitMapToRegion(kabName = props.selectedKabupaten) {
    if (!mapInstance) return;

    if (kabName && kabName !== 'Semua' && boundaryFeatures.length > 0) {
        const targetKey = normalizeWilayahKey(kabName);
        const matched = boundaryFeatures.find((f) => {
            const nm = f.properties?.kabupaten || f.properties?.WADMKK || f.properties?.name || '';
            return normalizeWilayahKey(nm) === targetKey;
        });
        if (matched) {
            const tmp = L.geoJSON(matched);
            const b = tmp.getBounds();
            if (b.isValid()) {
                mapInstance.fitBounds(b.pad(0.08), { animate: true });
                return;
            }
        }
    }

    if (boundaryLayer && boundaryLayer.getBounds().isValid()) {
        mapInstance.fitBounds(boundaryLayer.getBounds().pad(0.05), { animate: true });
    } else {
        mapInstance.setView([-7.59, 110.85], 10);
    }
}

async function initMap() {
    if (!mapContainerRef.value) return;

    mapInstance = L.map(mapContainerRef.value, {
        center: [-7.59, 110.85],
        zoom: 10,
        minZoom: 8,
        maxZoom: 18,
        zoomControl: true,
        scrollWheelZoom: false,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 18,
    }).addTo(mapInstance);

    canvasRenderer = L.canvas({ padding: 0.4 });
    clusterLayer = L.layerGroup().addTo(mapInstance);

    try {
        const res = await fetch('/data/solo-raya-kabupaten.geojson');
        if (res.ok) {
            const geo = await res.json();
            boundaryFeatures = (geo.features || []).filter((f) => {
                const nm = f.properties?.kabupaten || f.properties?.WADMKK || f.properties?.name || '';
                return SOLO_RAYA_KEYS.has(normalizeWilayahKey(nm));
            });

            if (boundaryFeatures.length > 0) {
                boundaryLayer = L.geoJSON(
                    { type: 'FeatureCollection', features: boundaryFeatures },
                    {
                        style: {
                            color: '#065f46',
                            weight: 1.6,
                            opacity: 0.55,
                            fillColor: '#10b981',
                            fillOpacity: 0.04,
                            dashArray: '4 4',
                        },
                        interactive: false,
                    }
                ).addTo(mapInstance);

                fitMapToRegion();
            }
        }
    } catch {
        // Jika geojson gagal dimuat, tetap tampilkan cluster marker normal
    }

    mapInstance.on('moveend zoomend', renderClusters);
    renderClusters();
}

onMounted(() => {
    initMap();
});

onBeforeUnmount(() => {
    if (mapInstance) {
        mapInstance.off('moveend zoomend', renderClusters);
        mapInstance.remove();
        mapInstance = null;
    }
});

watch(
    () => [props.schools, visibleTiers.basis_utama, visibleTiers.potensial, visibleTiers.vakum, visibleTiers.belum_tergarap],
    () => {
        renderClusters();
    },
    { deep: false }
);

watch(
    () => props.selectedKabupaten,
    (newKab) => {
        fitMapToRegion(newKab);
    }
);
</script>

<template>
    <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-6 shadow-xs">
        <!-- Header & Layer Filter -->
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
            <div class="flex items-start gap-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block mt-1.5 shrink-0"></span>
                <div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                        Peta Sebaran Titik Sekolah Solo Raya (Marker Cluster)
                    </h3>
                    <p class="text-[11.5px] text-slate-500 mt-0.5">
                        Klik angka cluster untuk memperbesar wilayah, atau nyalakan filter layer untuk mencari SD/MI `0` di sekitar Basis Utama.
                    </p>
                </div>
            </div>

            <!-- Layer Toggle Pills + Reset -->
            <div class="flex items-center gap-1.5 flex-wrap">
                <button
                    v-for="tierKey in ['basis_utama', 'potensial', 'vakum', 'belum_tergarap']"
                    :key="tierKey"
                    type="button"
                    @click="visibleTiers[tierKey] = !visibleTiers[tierKey]"
                    :class="[
                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border transition-all cursor-pointer select-none',
                        visibleTiers[tierKey]
                            ? PSB_TIERS[tierKey].badgeClass
                            : 'bg-slate-50 text-slate-400 border-slate-200 line-through opacity-70',
                    ]"
                >
                    <span
                        :class="[
                            'w-2 h-2 rounded-full',
                            visibleTiers[tierKey] ? PSB_TIERS[tierKey].dotClass : 'bg-slate-300',
                        ]"
                    ></span>
                    <span>{{ PSB_TIERS[tierKey].shortLabel }} ({{ tierCounts[tierKey].toLocaleString('id-ID') }})</span>
                </button>

                <button
                    type="button"
                    @click="fitMapToRegion(selectedKabupaten)"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition-colors cursor-pointer"
                    title="Reset posisi zoom peta"
                >
                    <span class="material-symbols-outlined text-[14px]">my_location</span>
                    <span>Reset Zoom</span>
                </button>
            </div>
        </div>

        <!-- Leaflet Map Canvas -->
        <div class="relative rounded-2xl overflow-hidden border border-slate-200 bg-slate-100 h-[360px] sm:h-[440px]">
            <div ref="mapContainerRef" class="w-full h-full z-10"></div>

            <!-- Info hint floating -->
            <div class="absolute bottom-3 left-3 z-20 pointer-events-none bg-white/95 backdrop-blur-xs border border-slate-200/90 rounded-xl px-3 py-1.5 shadow-xs text-[10.5px] font-semibold text-slate-600 hidden sm:flex items-center gap-2">
                <span class="material-symbols-outlined text-[14px] text-emerald-700">layers</span>
                <span>Menampilkan {{ filteredMapSchools.length.toLocaleString('id-ID') }} titik sekolah</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
:deep(.psb-cluster-icon) {
    background: transparent;
    border: none;
}
</style>

