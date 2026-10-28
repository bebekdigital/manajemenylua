<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    positions: {
        type: Array,
        default: () => [],
    },
    departments: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits([
    'add-position',
    'edit-position',
    'delete-position',
    'add-department',
    'edit-department',
    'delete-department',
]);

const searchQuery = ref('');
const selectedDeptFilter = ref('all');
const activeTab = ref('diagram'); // 'diagram' | 'departments'
const hoveredPosId = ref(null);

const diagramContainerRef = ref(null);
const tableRef = ref(null);
const connectionLines = ref([]);
const svgWidth = ref(1200);
const svgHeight = ref(800);

const levelDefinitions = [
    {
        level: 1,
        title: 'Level 1: Dewan Pembina & Pengawas Yayasan',
        subtitle: 'Pemegang otoritas tertinggi, arahan strategis, dan pengawasan yayasan',
        badgeClass: 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 border-emerald-500/30',
        rowHeaderBg: 'bg-emerald-500/[0.04]',
        accentText: 'text-emerald-700 dark:text-emerald-400',
        iconBg: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300',
        btnClass: 'bg-emerald-600 hover:bg-emerald-700 text-white',
        cardBorder: 'hover:border-emerald-500/70',
        dotColor: 'bg-emerald-600',
        strokeColor: '#059669', // emerald-600
        icon: 'verified_user',
    },
    {
        level: 2,
        title: 'Level 2: Pengurus Harian & Direksi Yayasan',
        subtitle: 'Pimpinan harian & direksi: Ketua Umum, Sekretaris, Bendahara, Direktur Eksekutif',
        badgeClass: 'bg-indigo-500/15 text-indigo-800 dark:text-indigo-300 border-indigo-500/30',
        rowHeaderBg: 'bg-indigo-500/[0.04]',
        accentText: 'text-indigo-700 dark:text-indigo-400',
        iconBg: 'bg-indigo-500/15 text-indigo-700 dark:text-indigo-300',
        btnClass: 'bg-indigo-600 hover:bg-indigo-700 text-white',
        cardBorder: 'hover:border-indigo-500/70',
        dotColor: 'bg-indigo-600',
        strokeColor: '#4f46e5', // indigo-600
        icon: 'corporate_fare',
    },
    {
        level: 3,
        title: 'Level 3: Kepala Bidang Yayasan',
        subtitle: 'Pimpinan bidang fungsional: Pendidikan, SDM, Keuangan, Sarpras, Dakwah & Humas',
        badgeClass: 'bg-amber-500/15 text-amber-800 dark:text-amber-300 border-amber-500/30',
        rowHeaderBg: 'bg-amber-500/[0.04]',
        accentText: 'text-amber-700 dark:text-amber-400',
        iconBg: 'bg-amber-500/15 text-amber-700 dark:text-amber-300',
        btnClass: 'bg-amber-600 hover:bg-amber-700 text-white',
        cardBorder: 'hover:border-amber-500/70',
        dotColor: 'bg-amber-600',
        strokeColor: '#d97706', // amber-600
        icon: 'manage_accounts',
    },
    {
        level: 4,
        title: 'Level 4: Koordinator & Kepala Bagian / Unit',
        subtitle: 'Koordinator operasional program kerja, pengawas sekolah, dan penjamin teknis',
        badgeClass: 'bg-cyan-500/15 text-cyan-800 dark:text-cyan-300 border-cyan-500/30',
        rowHeaderBg: 'bg-cyan-500/[0.04]',
        accentText: 'text-cyan-700 dark:text-cyan-400',
        iconBg: 'bg-cyan-500/15 text-cyan-700 dark:text-cyan-300',
        btnClass: 'bg-cyan-600 hover:bg-cyan-700 text-white',
        cardBorder: 'hover:border-cyan-500/70',
        dotColor: 'bg-cyan-600',
        strokeColor: '#0891b2', // cyan-600
        icon: 'hub',
    },
    {
        level: 5,
        title: 'Level 5: Staf Pelaksana & Tim Teknis',
        subtitle: 'Staf pelaksana administrasi, logistik, pengadaan, dan teknis operasional harian',
        badgeClass: 'bg-slate-500/15 text-slate-800 dark:text-slate-300 border-slate-500/30',
        rowHeaderBg: 'bg-slate-500/[0.04]',
        accentText: 'text-slate-700 dark:text-slate-400',
        iconBg: 'bg-slate-500/15 text-slate-700 dark:text-slate-300',
        btnClass: 'bg-slate-700 hover:bg-slate-800 text-white',
        cardBorder: 'hover:border-slate-500/70',
        dotColor: 'bg-slate-600',
        strokeColor: '#475569', // slate-600
        icon: 'group',
    },
];

// Map lookup by ID
const positionsMap = computed(() => {
    const map = new Map();
    props.positions.forEach(p => map.set(p.id, p));
    return map;
});

// Map of children names grouped by parent_id
const childrenByParent = computed(() => {
    const map = new Map();
    props.positions.forEach(p => {
        if (p.parent_id) {
            if (!map.has(p.parent_id)) {
                map.set(p.parent_id, []);
            }
            map.get(p.parent_id).push(p.name);
        }
    });
    return map;
});

// Enriched positions with parent info and direct children info
const enrichedPositions = computed(() => {
    return props.positions.map(p => {
        const parent = p.parent_id ? positionsMap.value.get(p.parent_id) : null;
        const children = childrenByParent.value.get(p.id) || [];
        return {
            ...p,
            level: Number(p.level) || 3,
            order: Number(p.order) || 0,
            parent_name: parent ? parent.name : p.parent_name || null,
            parent_level: parent ? (Number(parent.level) || null) : null,
            subordinates: children,
        };
    });
});

// Filtered positions based on search and department filter
const filteredPositions = computed(() => {
    return enrichedPositions.value.filter(p => {
        if (searchQuery.value) {
            const q = searchQuery.value.toLowerCase().trim();
            const matchName = p.name.toLowerCase().includes(q);
            const matchDept = p.department_name ? p.department_name.toLowerCase().includes(q) : false;
            const matchParent = p.parent_name ? p.parent_name.toLowerCase().includes(q) : false;
            if (!matchName && !matchDept && !matchParent) return false;
        }

        if (selectedDeptFilter.value !== 'all') {
            if (selectedDeptFilter.value === 'null') {
                if (p.department_id) return false;
            } else if (p.department_id !== Number(selectedDeptFilter.value)) {
                return false;
            }
        }

        return true;
    });
});

// Group filtered positions by level (1 to 5), sorted so children of same parent sit near each other
const positionsByLevel = computed(() => {
    const groups = { 1: [], 2: [], 3: [], 4: [], 5: [] };
    filteredPositions.value.forEach(p => {
        const lvl = Math.min(5, Math.max(1, p.level));
        if (!groups[lvl]) groups[lvl] = [];
        groups[lvl].push(p);
    });

    // Sort in each level by parent_id, then order, then name
    for (let l = 1; l <= 5; l++) {
        groups[l].sort((a, b) => {
            const pA = a.parent_id || 0;
            const pB = b.parent_id || 0;
            if (pA !== pB) return pA - pB;
            if (a.order !== b.order) return a.order - b.order;
            return a.name.localeCompare(b.name);
        });
    }

    return groups;
});

// ========================================================
// GARIS HIERARKI SVG DINAMIS (ANTAR ATASAN DAN BAWAHAN)
// ========================================================
function updateConnectionLines() {
    if (!diagramContainerRef.value || !tableRef.value) return;

    const containerEl = diagramContainerRef.value;
    const containerRect = containerEl.getBoundingClientRect();
    const scrollLeft = containerEl.scrollLeft || 0;
    const scrollTop = containerEl.scrollTop || 0;

    svgWidth.value = Math.max(containerEl.scrollWidth, tableRef.value.offsetWidth);
    svgHeight.value = Math.max(containerEl.scrollHeight, tableRef.value.offsetHeight);

    const lines = [];

    // Loop through all positions that have a parent
    props.positions.forEach(pos => {
        if (!pos.parent_id) return;

        const childEl = document.getElementById(`pos-card-${pos.id}`);
        const parentEl = document.getElementById(`pos-card-${pos.parent_id}`);

        if (childEl && parentEl) {
            const childRect = childEl.getBoundingClientRect();
            const parentRect = parentEl.getBoundingClientRect();

            // Coordinate from bottom center of parent card
            const x1 = (parentRect.left + parentRect.width / 2) - containerRect.left + scrollLeft;
            const y1 = parentRect.bottom - containerRect.top + scrollTop;

            // Coordinate to top center of child card
            const x2 = (childRect.left + childRect.width / 2) - containerRect.left + scrollLeft;
            const y2 = childRect.top - containerRect.top + scrollTop;

            // Smooth bezier connector path
            const midY = y1 + (y2 - y1) * 0.5;
            const path = `M ${x1} ${y1} C ${x1} ${midY}, ${x2} ${midY}, ${x2} ${y2}`;

            const parentLevelDef = levelDefinitions.find(d => d.level === (Number(parentEl.dataset.level) || 1)) || levelDefinitions[0];

            lines.push({
                id: `line-${pos.parent_id}-${pos.id}`,
                parentId: pos.parent_id,
                childId: pos.id,
                x1,
                y1,
                x2,
                y2,
                path,
                color: parentLevelDef.strokeColor,
            });
        }
    });

    connectionLines.value = lines;
}

// Watch positions and re-render lines
watch(
    () => [props.positions, searchQuery.value, selectedDeptFilter.value, activeTab.value],
    () => {
        nextTick(() => {
            updateConnectionLines();
            // Second pass after browser layout settles
            setTimeout(updateConnectionLines, 100);
        });
    },
    { deep: true }
);

let resizeObserver = null;
onMounted(() => {
    nextTick(() => {
        updateConnectionLines();
        setTimeout(updateConnectionLines, 150);
    });

    window.addEventListener('resize', updateConnectionLines);

    if (window.ResizeObserver && diagramContainerRef.value) {
        resizeObserver = new ResizeObserver(() => {
            updateConnectionLines();
        });
        resizeObserver.observe(diagramContainerRef.value);
    }
});

onUnmounted(() => {
    if (resizeObserver) resizeObserver.disconnect();
    window.removeEventListener('resize', updateConnectionLines);
});

// Global add new position
function handleAddDirect() {
    emit('add-position', {
        presetLevel: 1,
        presetParentId: null,
        presetDepartmentId: null,
        modeTitle: 'Tambah Jabatan Baru',
    });
}

// Add directly inside a specific level row
function handleAddAtLevel(level) {
    emit('add-position', {
        presetLevel: level,
        presetParentId: null,
        presetDepartmentId: null,
        modeTitle: `Tambah Jabatan di Level ${level}`,
    });
}

// Add peer (sejajar) at the same level
function handleAddPeer(pos) {
    emit('add-position', {
        presetLevel: pos.level,
        presetParentId: pos.parent_id || null,
        presetDepartmentId: pos.department_id || null,
        modeTitle: `Tambah Jabatan Sejajar di Level ${pos.level}`,
    });
}

// Add subordinate (bawahan) reporting to this position
function handleAddSubordinate(pos) {
    const nextLevel = Math.min(5, (Number(pos.level) || 1) + 1);
    emit('add-position', {
        presetLevel: nextLevel,
        presetParentId: pos.id,
        presetDepartmentId: pos.department_id || null,
        modeTitle: `Tambah Bawahan untuk: ${pos.name}`,
    });
}

function handleEdit(pos) {
    emit('edit-position', pos);
}

function handleDelete(pos) {
    emit('delete-position', pos);
}
</script>

<template>
    <div class="space-y-6">
        <!-- Main Table Container: Seluruh Baris Level Menyatu Seperti Baris Tabel -->
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 shadow-sm overflow-hidden">
            <!-- Header Section Toolbar -->
            <div class="p-5 sm:p-6 border-b border-outline-variant/40 bg-surface-container-low/30">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex items-start sm:items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary shadow-xs shrink-0">
                            <span class="material-symbols-outlined text-2xl">account_tree</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <h3 class="text-lg sm:text-xl font-bold text-on-surface">Diagram Susunan Organisasi</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-primary/10 text-primary border border-primary/20">
                                    5 Baris Level Menyatu
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm text-on-surface-variant mt-0.5">
                                Struktur hierarki organisasi 5 level dengan garis penghubung langsung antara atasan dan bawahan
                            </p>
                        </div>
                    </div>

                    <!-- Top Action Buttons -->
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <button
                            type="button"
                            @click="$emit('add-department')"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-outline-variant/60 hover:bg-surface-container-high text-on-surface font-semibold text-xs sm:text-sm transition-all cursor-pointer"
                            title="Tambah bidang kerja yayasan"
                        >
                            <span class="material-symbols-outlined text-[18px]">add_business</span>
                            <span>Tambah Bidang</span>
                        </button>

                        <button
                            type="button"
                            @click="handleAddDirect"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-xs sm:text-sm transition-all shadow-xs hover:shadow-md cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-[18px]">add_circle</span>
                            <span>Tambah Jabatan Baru</span>
                        </button>
                    </div>
                </div>

                <!-- Filters & View Switcher Bar -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mt-5 pt-4 border-t border-outline-variant/20">
                    <!-- Search & Filter -->
                    <div class="flex items-center gap-2.5 flex-1 flex-wrap">
                        <div class="relative flex-1 min-w-[220px]">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">
                                search
                            </span>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari nama jabatan, bidang, atau atasan..."
                                class="w-full pl-9 pr-3 py-1.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 transition-all"
                            />
                        </div>

                        <select
                            v-model="selectedDeptFilter"
                            class="px-3 py-1.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary cursor-pointer"
                        >
                            <option value="all">Semua Bidang Kerja</option>
                            <option value="null">Yayasan / Lintas Bidang</option>
                            <option v-for="dept in departments" :key="dept.id" :value="String(dept.id)">
                                {{ dept.name }}
                            </option>
                        </select>
                    </div>

                    <!-- View Switcher -->
                    <div class="inline-flex items-center p-1 bg-surface-container-low border border-outline-variant/60 rounded-xl shrink-0 self-start md:self-auto">
                        <button
                            type="button"
                            @click="activeTab = 'diagram'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                            :class="activeTab === 'diagram'
                                ? 'bg-primary text-on-primary shadow-xs'
                                : 'text-on-surface-variant hover:text-on-surface'"
                        >
                            <span class="material-symbols-outlined text-[16px]">view_agenda</span>
                            <span>Tabel 5 Level Hierarki</span>
                        </button>

                        <button
                            type="button"
                            @click="activeTab = 'departments'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                            :class="activeTab === 'departments'
                                ? 'bg-primary text-on-primary shadow-xs'
                                : 'text-on-surface-variant hover:text-on-surface'"
                        >
                            <span class="material-symbols-outlined text-[16px]">domain</span>
                            <span>Daftar Bidang</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- TABEL 5 BARIS LEVEL MENYATU DENGAN GARIS HIERARKI DINAMIS -->
            <!-- ======================================================== -->
            <div
                v-if="activeTab === 'diagram'"
                ref="diagramContainerRef"
                class="relative overflow-x-auto min-w-full"
                @scroll="updateConnectionLines"
            >
                <!-- KANVAS SVG GARIS HIERARKI PENGHUBUNG ANTARA ATASAN DAN BAWAHAN -->
                <svg
                    class="absolute inset-0 pointer-events-none z-10 overflow-visible transition-all"
                    :style="{
                        width: `${svgWidth}px`,
                        height: `${svgHeight}px`,
                        minWidth: '100%',
                        minHeight: '100%',
                    }"
                >
                    <defs>
                        <!-- Marker Arrowhead untuk Ujung Garis Menuju Bawahan -->
                        <marker
                            id="arrow-head"
                            viewBox="0 0 10 10"
                            refX="7"
                            refY="5"
                            markerWidth="6"
                            markerHeight="6"
                            orient="auto-start-reverse"
                        >
                            <path d="M 0 1.5 L 8 5 L 0 8.5 z" fill="#059669" />
                        </marker>
                    </defs>

                    <!-- Render Setiap Garis Penghubung (Parent -> Child) -->
                    <g v-for="line in connectionLines" :key="line.id">
                        <!-- Glow / Shadow Line saat di-hover -->
                        <path
                            :d="line.path"
                            stroke="rgba(5, 150, 105, 0.25)"
                            :stroke-width="hoveredPosId === line.parentId || hoveredPosId === line.childId ? 8 : 4"
                            fill="none"
                            class="transition-all duration-200"
                        />
                        <!-- Garis Utama Penghubung Atasan & Bawahan -->
                        <path
                            :d="line.path"
                            :stroke="hoveredPosId === line.parentId || hoveredPosId === line.childId ? '#059669' : line.color"
                            :stroke-width="hoveredPosId === line.parentId || hoveredPosId === line.childId ? 3.5 : 2"
                            :stroke-opacity="hoveredPosId && hoveredPosId !== line.parentId && hoveredPosId !== line.childId ? 0.25 : 0.85"
                            fill="none"
                            marker-end="url(#arrow-head)"
                            class="transition-all duration-200"
                        />
                    </g>
                </svg>

                <!-- TABEL 5 BARIS LEVEL YANG MENYATU SECARA VERTIKAL -->
                <table ref="tableRef" class="w-full border-collapse min-w-[960px] text-left">
                    <tbody>
                        <tr
                            v-for="(def, idx) in levelDefinitions"
                            :key="def.level"
                            class="border-b border-outline-variant/40 last:border-b-0 hover:bg-slate-50/20 transition-colors"
                        >
                            <!-- KOLOM KIRI (HEADER BARIS LEVEL) -->
                            <td
                                class="w-64 sm:w-72 p-4 sm:p-5 align-top border-r border-outline-variant/40 select-none shrink-0"
                                :class="def.rowHeaderBg"
                            >
                                <div class="flex items-start gap-3">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 shadow-2xs mt-0.5" :class="def.iconBg">
                                        <span class="material-symbols-outlined text-xl">{{ def.icon }}</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full border shadow-2xs', def.badgeClass]">
                                                Level {{ def.level }}
                                            </span>
                                            <span class="text-[11px] font-semibold text-on-surface-variant">
                                                {{ positionsByLevel[def.level]?.length || 0 }} Jabatan
                                            </span>
                                        </div>
                                        <h4 class="text-xs sm:text-sm font-bold text-on-surface mt-1 leading-snug">
                                            {{ def.title.replace(`Level ${def.level}: `, '') }}
                                        </h4>
                                        <p class="text-[11px] text-on-surface-variant/80 mt-1 leading-relaxed">
                                            {{ def.subtitle }}
                                        </p>

                                        <!-- Tombol Tambah di Level Ini -->
                                        <div class="mt-3">
                                            <button
                                                type="button"
                                                @click="handleAddAtLevel(def.level)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all shadow-xs hover:shadow-md cursor-pointer"
                                                :class="def.btnClass"
                                            >
                                                <span class="material-symbols-outlined text-[15px]">add</span>
                                                <span>+ Tambah di Level {{ def.level }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- KOLOM KANAN (AREA KARTU JABATAN PADA BARIS LEVEL INI) -->
                            <td class="p-4 sm:p-6 align-middle relative min-h-[160px]">
                                <!-- JIKA SUDAH ADA JABATAN TERDAFTAR DI LEVEL INI -->
                                <div
                                    v-if="positionsByLevel[def.level] && positionsByLevel[def.level].length > 0"
                                    class="flex items-stretch gap-6 flex-wrap py-2"
                                >
                                    <div
                                        v-for="pos in positionsByLevel[def.level]"
                                        :key="pos.id"
                                        :id="`pos-card-${pos.id}`"
                                        :data-level="pos.level"
                                        @mouseenter="hoveredPosId = pos.id"
                                        @mouseleave="hoveredPosId = null"
                                        class="w-64 sm:w-72 bg-white rounded-xl border border-outline-variant/60 p-4 transition-all duration-200 hover:shadow-lg flex flex-col justify-between group relative select-none"
                                        :class="[
                                            def.cardBorder,
                                            hoveredPosId === pos.id ? 'ring-2 ring-primary border-primary shadow-md' : 'shadow-2xs'
                                        ]"
                                    >
                                        <!-- TITIK ANCHOR KONEKTOR ATAS (TITIK HUBUNG KE ATASAN) -->
                                        <div
                                            v-if="pos.parent_id"
                                            class="absolute -top-1.5 left-1/2 -translate-x-1/2 w-3 h-3 rounded-full bg-primary border-2 border-white shadow-xs z-20"
                                            title="Titik hubung vertikal ke atasan langsung"
                                        ></div>

                                        <!-- TITIK ANCHOR KONEKTOR BAWAH (TITIK HUBUNG KE BAWAHAN) -->
                                        <div
                                            v-if="pos.subordinates && pos.subordinates.length > 0"
                                            class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3 h-3 rounded-full bg-primary border-2 border-white shadow-xs z-20"
                                            title="Titik hubung vertikal ke bawahan langsung"
                                        ></div>

                                        <div>
                                            <!-- Header Kartu: Badge Level & Bidang Kerja -->
                                            <div class="flex items-center justify-between gap-1.5 mb-2 flex-wrap">
                                                <div class="flex items-center gap-1">
                                                    <span :class="['text-[9px] font-bold px-1.5 py-0.5 rounded border', def.badgeClass]">
                                                        L{{ pos.level }}
                                                    </span>
                                                    <span
                                                        v-if="pos.department_name"
                                                        class="text-[10px] font-semibold px-2 py-0.5 rounded bg-surface-container-high text-on-surface-variant border border-outline-variant/40 truncate max-w-[140px]"
                                                        :title="pos.department_name"
                                                    >
                                                        {{ pos.department_name }}
                                                    </span>
                                                    <span
                                                        v-else
                                                        class="text-[10px] font-semibold px-2 py-0.5 rounded bg-primary/10 text-primary border border-primary/20"
                                                    >
                                                        Yayasan
                                                    </span>
                                                </div>

                                                <!-- Subordinate Counter Badge -->
                                                <span
                                                    v-if="pos.subordinates && pos.subordinates.length > 0"
                                                    class="inline-flex items-center gap-0.5 text-[10px] font-bold text-primary bg-primary/10 px-1.5 py-0.5 rounded-full"
                                                >
                                                    <span class="material-symbols-outlined text-[12px]">subdirectory_arrow_right</span>
                                                    {{ pos.subordinates.length }}
                                                </span>
                                            </div>

                                            <!-- Nama Jabatan -->
                                            <h5 class="text-sm font-bold text-on-surface leading-snug group-hover:text-primary transition-colors">
                                                {{ pos.name }}
                                            </h5>

                                            <!-- Relasi Hierarki: Atasan & Bawahan -->
                                            <div class="mt-2.5 pt-2 border-t border-outline-variant/20 space-y-1">
                                                <!-- Atasan Langsung Indicator -->
                                                <div class="flex items-center gap-1.5 text-xs text-on-surface-variant">
                                                    <span class="material-symbols-outlined text-[14px] text-primary shrink-0">
                                                        arrow_upward
                                                    </span>
                                                    <span class="truncate">
                                                        <span class="text-on-surface-variant/70 text-[11px]">Atasan:</span>
                                                        <span v-if="pos.parent_name" class="font-bold text-on-surface ml-1">
                                                            {{ pos.parent_name }}
                                                        </span>
                                                        <span v-else class="font-medium text-emerald-700 dark:text-emerald-400 ml-1">
                                                            Pimpinan Tertinggi
                                                        </span>
                                                    </span>
                                                </div>

                                                <!-- Bawahan Langsung (Jika ada) -->
                                                <div v-if="pos.subordinates && pos.subordinates.length > 0" class="flex items-start gap-1 text-[11px] text-on-surface-variant">
                                                    <span class="material-symbols-outlined text-[13px] text-secondary shrink-0 mt-0.5">
                                                        subdirectory_arrow_right
                                                    </span>
                                                    <div class="truncate text-on-surface-variant/80">
                                                        <span class="text-[10px] text-on-surface-variant/70">Bawahan:</span>
                                                        <span class="ml-1 font-medium">{{ pos.subordinates.join(', ') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Footer Kartu: Tombol Aksi (+ Bawahan, + Sejajar, Edit, Hapus) -->
                                        <div class="mt-3.5 pt-2.5 border-t border-outline-variant/30 flex items-center justify-between gap-1 flex-wrap">
                                            <div class="flex items-center gap-1">
                                                <!-- + Bawahan (Vertikal ke Level Bawahnya) -->
                                                <button
                                                    v-if="pos.level < 5"
                                                    type="button"
                                                    @click="handleAddSubordinate(pos)"
                                                    class="inline-flex items-center gap-0.5 px-2 py-1 rounded-md bg-primary/10 hover:bg-primary hover:text-on-primary text-primary text-[10px] font-bold transition-colors cursor-pointer"
                                                    title="Tambah bawahan langsung di level bawahnya"
                                                >
                                                    <span class="material-symbols-outlined text-[13px]">add</span>
                                                    <span>+ Bawahan</span>
                                                </button>

                                                <!-- + Sejajar (Horizontal di Level yang Sama) -->
                                                <button
                                                    type="button"
                                                    @click="handleAddPeer(pos)"
                                                    class="inline-flex items-center gap-0.5 px-2 py-1 rounded-md bg-surface-container-high hover:bg-surface-container-highest text-on-surface text-[10px] font-bold transition-colors cursor-pointer"
                                                    title="Tambah rekan jabatan sejajar di level ini"
                                                >
                                                    <span class="material-symbols-outlined text-[13px]">add</span>
                                                    <span>+ Sejajar</span>
                                                </button>
                                            </div>

                                            <div class="flex items-center gap-0.5">
                                                <!-- Edit -->
                                                <button
                                                    type="button"
                                                    @click="handleEdit(pos)"
                                                    class="p-1 rounded-md text-on-surface-variant hover:text-primary hover:bg-primary/10 transition-colors cursor-pointer"
                                                    title="Edit Jabatan"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">edit</span>
                                                </button>

                                                <!-- Hapus -->
                                                <button
                                                    type="button"
                                                    @click="handleDelete(pos)"
                                                    class="p-1 rounded-md text-on-surface-variant hover:text-error hover:bg-error/10 transition-colors cursor-pointer"
                                                    title="Hapus Jabatan"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- EMPTY STATE SAAT BARIS LEVEL BELUM MEMILIKI JABATAN -->
                                <div
                                    v-else
                                    class="py-6 px-4 border-2 border-dashed border-outline-variant/40 rounded-xl bg-surface-container-lowest/50 text-center max-w-lg"
                                >
                                    <span class="material-symbols-outlined text-2xl text-outline/50 mb-1">
                                        {{ def.icon }}
                                    </span>
                                    <p class="text-xs font-semibold text-on-surface-variant mb-2">
                                        Belum ada jabatan pada baris {{ def.title.split(':')[0] }}.
                                    </p>
                                    <button
                                        type="button"
                                        @click="handleAddAtLevel(def.level)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all shadow-xs cursor-pointer"
                                        :class="def.btnClass"
                                    >
                                        <span class="material-symbols-outlined text-[15px]">add_circle</span>
                                        <span>+ Tambah Jabatan Pertama di Level {{ def.level }}</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ======================================================== -->
            <!-- TAB 2: DAFTAR BIDANG KERJA YAYASAN                        -->
            <!-- ======================================================== -->
            <div v-else-if="activeTab === 'departments'" class="p-5 sm:p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-base font-bold text-on-surface">Daftar Bidang Kerja Yayasan</h4>
                        <p class="text-xs text-on-surface-variant">Kelola nama bidang kerja dan grup posisi di bawah yayasan</p>
                    </div>
                    <button
                        type="button"
                        @click="$emit('add-department')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-primary text-on-primary text-xs sm:text-sm font-semibold cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[16px]">add</span>
                        <span>Tambah Bidang</span>
                    </button>
                </div>

                <div class="overflow-x-auto border border-outline-variant/60 rounded-xl">
                    <table class="w-full text-xs sm:text-sm border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low/70 text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                                <th class="border-b border-outline-variant/60 px-5 py-3 text-left w-1/3">Bidang Kerja</th>
                                <th class="border-b border-outline-variant/60 px-5 py-3 text-left">Jabatan Terdaftar di Bidang</th>
                                <th class="border-b border-outline-variant/60 px-5 py-3 text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="dept in departments"
                                :key="dept.id"
                                class="border-b border-outline-variant/40 hover:bg-surface-container-low/30 transition-colors"
                            >
                                <td class="px-5 py-3.5 align-top font-bold text-on-surface">
                                    {{ dept.name }}
                                </td>
                                <td class="px-5 py-3.5 align-top">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="p in positions.filter(x => x.department_id === dept.id)"
                                            :key="p.id"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-surface-container-high border border-outline-variant/40"
                                        >
                                            <span class="text-[9px] font-bold px-1 rounded bg-primary/10 text-primary">
                                                L{{ p.level || 3 }}
                                            </span>
                                            <span>{{ p.name }}</span>
                                        </span>
                                        <span
                                            v-if="!positions.some(x => x.department_id === dept.id)"
                                            class="text-xs text-on-surface-variant italic"
                                        >
                                            Belum ada jabatan di bidang ini
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 align-top text-center">
                                    <div class="inline-flex items-center gap-1">
                                        <button
                                            type="button"
                                            @click="$emit('edit-department', dept)"
                                            class="p-1.5 rounded-lg text-on-surface-variant hover:text-primary hover:bg-primary/10 transition-colors cursor-pointer"
                                            title="Edit Bidang"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">edit</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="$emit('delete-department', dept)"
                                            class="p-1.5 rounded-lg text-on-surface-variant hover:text-error hover:bg-error/10 transition-colors cursor-pointer"
                                            title="Hapus Bidang"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="departments.length === 0">
                                <td colspan="3" class="px-5 py-8 text-center text-on-surface-variant">
                                    Belum ada bidang terdaftar.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
