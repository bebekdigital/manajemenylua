<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import OrgChartNode from '@/Components/Portal/OrgChartNode.vue';

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
const selectedLevelFilter = ref('all');
const selectedDeptFilter = ref('all');
const activeView = ref('diagram'); // 'diagram' (DEFAULT) | 'tier' | 'dept'
const orientation = ref('vertical'); // 'vertical' | 'horizontal'
const showApex = ref(true); // Puncak Yayasan (Apex Node)
const zoom = ref(1);
const panX = ref(0);
const panY = ref(0);
const isDragging = ref(false);
const dragStartX = ref(0);
const dragStartY = ref(0);
const isFullscreen = ref(false);
const collapsedIds = ref(new Set());
const canvasContainerRef = ref(null);

const levelDefinitions = [
    {
        level: 1,
        title: 'Level 1: Dewan Pembina & Pengawas Yayasan',
        subtitle: 'Pemegang kebijakan tertinggi, arahan strategis, dan pengawasan yayasan',
        badgeClass: 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 border-emerald-500/30',
        borderClass: 'border-emerald-500/50 hover:border-emerald-600',
        accentBg: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
        headerGradient: 'bg-gradient-to-r from-emerald-600 to-teal-700',
        cardBorder: 'hover:border-emerald-500/60',
        dotColor: 'bg-emerald-500',
        icon: 'verified_user',
    },
    {
        level: 2,
        title: 'Level 2: Pengurus Harian & Direksi Yayasan',
        subtitle: 'Pimpinan harian & direksi: Ketua Umum, Sekretaris, Bendahara, Direktur Eksekutif',
        badgeClass: 'bg-indigo-500/15 text-indigo-800 dark:text-indigo-300 border-indigo-500/30',
        borderClass: 'border-indigo-500/50 hover:border-indigo-600',
        accentBg: 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300',
        headerGradient: 'bg-gradient-to-r from-indigo-600 to-blue-700',
        cardBorder: 'hover:border-indigo-500/60',
        dotColor: 'bg-indigo-500',
        icon: 'corporate_fare',
    },
    {
        level: 3,
        title: 'Level 3: Kepala Bidang Yayasan',
        subtitle: 'Kepala bidang kerja fungsional: Pendidikan, SDM, Keuangan, Sarpras, Dakwah & Humas',
        badgeClass: 'bg-amber-500/15 text-amber-800 dark:text-amber-300 border-amber-500/30',
        borderClass: 'border-amber-500/50 hover:border-amber-600',
        accentBg: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
        headerGradient: 'bg-gradient-to-r from-amber-600 to-orange-600',
        cardBorder: 'hover:border-amber-500/60',
        dotColor: 'bg-amber-500',
        icon: 'manage_accounts',
    },
    {
        level: 4,
        title: 'Level 4: Koordinator & Kepala Bagian / Unit',
        subtitle: 'Koordinator operasional, penjamin mutu, bendahara bidang, pengawas sekolah',
        badgeClass: 'bg-cyan-500/15 text-cyan-800 dark:text-cyan-300 border-cyan-500/30',
        borderClass: 'border-cyan-500/50 hover:border-cyan-600',
        accentBg: 'bg-cyan-500/10 text-cyan-700 dark:text-cyan-300',
        headerGradient: 'bg-gradient-to-r from-cyan-600 to-sky-700',
        cardBorder: 'hover:border-cyan-500/60',
        dotColor: 'bg-cyan-500',
        icon: 'hub',
    },
    {
        level: 5,
        title: 'Level 5: Staf Pelaksana & Tim Teknis',
        subtitle: 'Staf pelaksana administrasi, logistik, pengadaan, dan teknis operasional harian',
        badgeClass: 'bg-slate-500/15 text-slate-800 dark:text-slate-300 border-slate-500/30',
        borderClass: 'border-slate-500/50 hover:border-slate-600',
        accentBg: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
        headerGradient: 'bg-gradient-to-r from-slate-600 to-gray-700',
        cardBorder: 'hover:border-slate-500/60',
        dotColor: 'bg-slate-500',
        icon: 'group',
    },
];

// Map lookup by ID
const positionsMap = computed(() => {
    const map = new Map();
    props.positions.forEach(p => map.set(p.id, p));
    return map;
});

// Enriched positions with parent & children details
const enrichedPositions = computed(() => {
    return props.positions.map(p => {
        const parent = p.parent_id ? positionsMap.value.get(p.parent_id) : null;
        return {
            ...p,
            level: Number(p.level) || 3,
            order: Number(p.order) || 0,
            parent_name: parent ? parent.name : p.parent_name || null,
            parent_level: parent ? (Number(parent.level) || 3) : null,
        };
    });
});

// Full tree hierarchy for the diagram
const treeRoots = computed(() => {
    const nodes = enrichedPositions.value;
    const nodeMap = new Map();
    nodes.forEach(n => nodeMap.set(n.id, { ...n, children: [] }));

    const roots = [];
    nodes.forEach(n => {
        const current = nodeMap.get(n.id);
        if (n.parent_id && nodeMap.has(n.parent_id) && n.parent_id !== n.id) {
            nodeMap.get(n.parent_id).children.push(current);
        } else {
            roots.push(current);
        }
    });

    // Sorter function (level asc, order asc, name asc)
    const sortNodes = (arr) => {
        arr.sort((a, b) => (a.level - b.level) || (a.order - b.order) || a.name.localeCompare(b.name));
        arr.forEach(item => {
            if (item.children && item.children.length > 0) {
                sortNodes(item.children);
            }
        });
    };

    sortNodes(roots);
    return roots;
});

// Filtered positions for Tier view
const filteredPositions = computed(() => {
    return enrichedPositions.value.filter(p => {
        if (searchQuery.value) {
            const q = searchQuery.value.toLowerCase().trim();
            const matchName = p.name ? p.name.toLowerCase().includes(q) : false;
            const matchDept = p.department_name ? p.department_name.toLowerCase().includes(q) : false;
            const matchParent = p.parent_name ? p.parent_name.toLowerCase().includes(q) : false;
            if (!matchName && !matchDept && !matchParent) return false;
        }

        if (selectedLevelFilter.value !== 'all') {
            if (p.level !== Number(selectedLevelFilter.value)) return false;
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

// Search matches count
const searchMatches = computed(() => {
    if (!searchQuery.value.trim()) return [];
    const q = searchQuery.value.toLowerCase().trim();
    return enrichedPositions.value.filter(p => {
        const matchName = p.name ? p.name.toLowerCase().includes(q) : false;
        const matchDept = p.department_name ? p.department_name.toLowerCase().includes(q) : false;
        return matchName || matchDept;
    });
});

// Auto-expand ancestors when searching
watch(searchQuery, (newVal) => {
    if (!newVal || !newVal.trim()) return;
    const uncollapse = new Set(collapsedIds.value);
    searchMatches.value.forEach(match => {
        let curr = match;
        while (curr && curr.parent_id && positionsMap.value.has(curr.parent_id)) {
            uncollapse.delete(curr.parent_id);
            curr = positionsMap.value.get(curr.parent_id);
        }
    });
    collapsedIds.value = uncollapse;
});

// Grouped by level (for Tier view)
const positionsByLevel = computed(() => {
    const groups = { 1: [], 2: [], 3: [], 4: [], 5: [] };
    filteredPositions.value.forEach(p => {
        const lvl = Math.min(5, Math.max(1, p.level));
        if (!groups[lvl]) groups[lvl] = [];
        groups[lvl].push(p);
    });
    return groups;
});

// Counts for stat badges
const statsByLevel = computed(() => {
    const counts = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 };
    props.positions.forEach(p => {
        const lvl = Math.min(5, Math.max(1, Number(p.level) || 3));
        counts[lvl] = (counts[lvl] || 0) + 1;
    });
    return counts;
});

// Helper for level definition
function getLevelDef(level) {
    const lvl = Math.min(5, Math.max(1, Number(level) || 3));
    return levelDefinitions.find(d => d.level === lvl) || levelDefinitions[2];
}

// Tree Collapse Handlers
function toggleCollapse(id) {
    const next = new Set(collapsedIds.value);
    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }
    collapsedIds.value = next;
}

function expandAll() {
    collapsedIds.value = new Set();
}

function collapseAll() {
    const set = new Set();
    enrichedPositions.value.forEach(p => {
        if (p.children_count > 0) {
            set.add(p.id);
        }
    });
    collapsedIds.value = set;
}

// Canvas Zoom & Pan Handlers
function zoomIn() {
    zoom.value = Math.min(1.8, Number((zoom.value + 0.15).toFixed(2)));
}

function zoomOut() {
    zoom.value = Math.max(0.4, Number((zoom.value - 0.15).toFixed(2)));
}

function resetView() {
    zoom.value = 1;
    panX.value = 0;
    panY.value = 0;
}

function handleMouseDown(e) {
    if (e.button !== 0) return;
    const target = e.target;
    if (target.closest('button') || target.closest('input') || target.closest('select') || target.closest('a')) {
        return;
    }
    isDragging.value = true;
    dragStartX.value = e.clientX - panX.value;
    dragStartY.value = e.clientY - panY.value;
}

function handleMouseMove(e) {
    if (!isDragging.value) return;
    panX.value = e.clientX - dragStartX.value;
    panY.value = e.clientY - dragStartY.value;
}

function handleMouseUp() {
    isDragging.value = false;
}

function toggleFullscreen() {
    isFullscreen.value = !isFullscreen.value;
    resetView();
}

function handleKeyDown(e) {
    if (e.key === 'Escape' && isFullscreen.value) {
        isFullscreen.value = false;
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
});

// Quick action handlers
function handleAddDirect() {
    emit('add-position', {
        presetLevel: 3,
        presetParentId: null,
        presetDepartmentId: null,
        modeTitle: 'Tambah Jabatan Baru',
    });
}

function handleAddAtLevel(level) {
    emit('add-position', {
        presetLevel: level,
        presetParentId: null,
        presetDepartmentId: null,
        modeTitle: `Tambah Jabatan di Level ${level}`,
    });
}

function handleAddPeer(pos) {
    emit('add-position', {
        presetLevel: pos.level,
        presetParentId: pos.parent_id || null,
        presetDepartmentId: pos.department_id || null,
        modeTitle: `Tambah Jabatan Sejajar (Level ${pos.level})`,
    });
}

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
        <!-- Main Card Container -->
        <div
            class="bg-surface-container-lowest rounded-2xl border border-primary/10 shadow-[0px_4px_24px_rgba(0,40,20,0.06)] overflow-hidden transition-all"
            :class="{
                'fixed inset-0 z-50 rounded-none border-none shadow-none flex flex-col bg-background': isFullscreen,
            }"
        >
            <!-- Header Section -->
            <div class="p-5 sm:p-6 border-b border-outline-variant/30 bg-surface-container-low/30 shrink-0">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex items-start sm:items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary shadow-xs shrink-0">
                            <span class="material-symbols-outlined text-2xl">account_tree</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <h3 class="text-lg sm:text-xl font-bold text-on-surface">Diagram Susunan Organisasi</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-primary/10 text-primary border border-primary/20">
                                    {{ positions.length }} Total Jabatan
                                </span>
                                <span
                                    v-if="isFullscreen"
                                    class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30"
                                >
                                    Mode Layar Penuh (ESC)
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm text-on-surface-variant mt-0.5">
                                Diagram pohon hierarki vertikal (garis atasan-bawahan) dan horizontal (jabatan sejajar)
                            </p>
                        </div>
                    </div>

                    <!-- Top Action Buttons -->
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <button
                            v-if="isFullscreen"
                            type="button"
                            @click="toggleFullscreen"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-error/10 hover:bg-error/20 text-error font-semibold text-xs sm:text-sm transition-all cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-[18px]">fullscreen_exit</span>
                            <span>Keluar Layar Penuh</span>
                        </button>

                        <button
                            type="button"
                            @click="$emit('add-department')"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-outline-variant/60 hover:bg-surface-container-high text-on-surface font-semibold text-xs sm:text-sm transition-all cursor-pointer"
                            title="Tambah bidang kerja baru"
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
                            <span>Tambah Jabatan</span>
                        </button>
                    </div>
                </div>

                <!-- 5 Level Counter Badges -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5 mt-5">
                    <div
                        v-for="def in levelDefinitions"
                        :key="def.level"
                        @click="selectedLevelFilter = selectedLevelFilter === String(def.level) ? 'all' : String(def.level)"
                        class="p-2.5 rounded-xl border transition-all cursor-pointer text-left select-none"
                        :class="selectedLevelFilter === String(def.level)
                            ? 'bg-primary/10 border-primary ring-2 ring-primary/20 shadow-xs'
                            : 'bg-surface-container-low/60 border-outline-variant/40 hover:bg-surface-container-low hover:border-outline-variant/80'"
                    >
                        <div class="flex items-center justify-between">
                            <span :class="['text-[10px] font-bold px-1.5 py-0.5 rounded border', def.badgeClass]">
                                Level {{ def.level }}
                            </span>
                            <span class="text-xs font-bold text-on-surface">
                                {{ statsByLevel[def.level] || 0 }} Jabatan
                            </span>
                        </div>
                        <div class="text-[11px] font-semibold text-on-surface truncate mt-1" :title="def.title">
                            {{ def.title.replace(`Level ${def.level}: `, '') }}
                        </div>
                    </div>
                </div>

                <!-- Filters & View Switcher Bar -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mt-5 pt-4 border-t border-outline-variant/20">
                    <!-- Search & Dropdowns -->
                    <div class="flex items-center gap-2.5 flex-1 flex-wrap">
                        <div class="relative flex-1 min-w-[220px]">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">
                                search
                            </span>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari jabatan atau bidang di diagram..."
                                class="w-full pl-9 pr-8 py-1.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 transition-all"
                            />
                            <button
                                v-if="searchQuery"
                                type="button"
                                @click="searchQuery = ''"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-outline hover:text-on-surface"
                            >
                                <span class="material-symbols-outlined text-[16px]">close</span>
                            </button>
                        </div>

                        <select
                            v-model="selectedLevelFilter"
                            class="px-3 py-1.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary cursor-pointer"
                        >
                            <option value="all">Semua Level (1-5)</option>
                            <option v-for="def in levelDefinitions" :key="def.level" :value="String(def.level)">
                                Level {{ def.level }} ({{ statsByLevel[def.level] || 0 }})
                            </option>
                        </select>

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

                        <!-- Search matches indicator -->
                        <span
                            v-if="searchQuery.trim()"
                            class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-primary/10 text-primary border border-primary/20"
                        >
                            {{ searchMatches.length }} ditemukan
                        </span>
                    </div>

                    <!-- View Switcher -->
                    <div class="inline-flex items-center p-1 bg-surface-container-low border border-outline-variant/60 rounded-xl shrink-0 self-start md:self-auto">
                        <!-- DIAGRAM POHON (DEFAULT) -->
                        <button
                            type="button"
                            @click="activeView = 'diagram'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                            :class="activeView === 'diagram'
                                ? 'bg-primary text-on-primary shadow-xs'
                                : 'text-on-surface-variant hover:text-on-surface'"
                        >
                            <span class="material-symbols-outlined text-[16px]">schema</span>
                            <span>Bagan Diagram Pohon</span>
                        </button>

                        <!-- DAFTAR PER TINGKAT -->
                        <button
                            type="button"
                            @click="activeView = 'tier'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                            :class="activeView === 'tier'
                                ? 'bg-primary text-on-primary shadow-xs'
                                : 'text-on-surface-variant hover:text-on-surface'"
                        >
                            <span class="material-symbols-outlined text-[16px]">view_column</span>
                            <span>Daftar Tingkat</span>
                        </button>

                        <!-- KELOLA BIDANG -->
                        <button
                            type="button"
                            @click="activeView = 'dept'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                            :class="activeView === 'dept'
                                ? 'bg-primary text-on-primary shadow-xs'
                                : 'text-on-surface-variant hover:text-on-surface'"
                        >
                            <span class="material-symbols-outlined text-[16px]">domain</span>
                            <span>Kelola Bidang</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- VIEW 1: DIAGRAM POHON DENGAN KOTAK & GARIS PENGHUBUNG     -->
            <!-- ======================================================== -->
            <div
                v-if="activeView === 'diagram'"
                class="relative flex flex-col"
                :class="isFullscreen ? 'flex-1 overflow-hidden' : ''"
            >
                <!-- Canvas Floating Control Toolbar -->
                <div class="p-3 sm:px-6 sm:py-2.5 bg-surface-container-low/60 border-b border-outline-variant/30 flex items-center justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-2 flex-wrap">
                        <!-- Orientation Toggle (Vertikal vs Horizontal) -->
                        <div class="inline-flex items-center p-0.5 bg-surface-container-lowest border border-outline-variant/60 rounded-lg text-xs font-medium">
                            <button
                                type="button"
                                @click="orientation = 'vertical'"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md transition-all cursor-pointer"
                                :class="orientation === 'vertical' ? 'bg-primary text-on-primary font-bold shadow-2xs' : 'text-on-surface-variant hover:text-on-surface'"
                                title="Susunan Top-Down Vertikal"
                            >
                                <span class="material-symbols-outlined text-[15px]">vertical_align_bottom</span>
                                <span>Vertikal</span>
                            </button>
                            <button
                                type="button"
                                @click="orientation = 'horizontal'"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md transition-all cursor-pointer"
                                :class="orientation === 'horizontal' ? 'bg-primary text-on-primary font-bold shadow-2xs' : 'text-on-surface-variant hover:text-on-surface'"
                                title="Susunan Left-to-Right Horizontal"
                            >
                                <span class="material-symbols-outlined text-[15px]">align_horizontal_left</span>
                                <span>Horizontal</span>
                            </button>
                        </div>

                        <!-- Toggle Puncak Yayasan (Apex Node) -->
                        <button
                            type="button"
                            @click="showApex = !showApex"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border text-xs font-semibold transition-all cursor-pointer"
                            :class="showApex
                                ? 'bg-primary/10 border-primary text-primary'
                                : 'bg-surface-container-lowest border-outline-variant/60 text-on-surface-variant hover:text-on-surface'"
                            title="Tampilkan / sembunyikan kotak lembaga induk yayasan di puncak diagram"
                        >
                            <span class="material-symbols-outlined text-[15px]">
                                {{ showApex ? 'check_box' : 'check_box_outline_blank' }}
                            </span>
                            <span>Puncak Yayasan</span>
                        </button>

                        <!-- Expand / Collapse All -->
                        <div class="inline-flex items-center gap-1">
                            <button
                                type="button"
                                @click="expandAll"
                                class="px-2.5 py-1.5 rounded-lg bg-surface-container-lowest border border-outline-variant/60 hover:bg-surface-container-high text-on-surface text-xs font-semibold transition-all cursor-pointer"
                                title="Buka seluruh cabang bawahan"
                            >
                                Buka Semua
                            </button>
                            <button
                                type="button"
                                @click="collapseAll"
                                class="px-2.5 py-1.5 rounded-lg bg-surface-container-lowest border border-outline-variant/60 hover:bg-surface-container-high text-on-surface text-xs font-semibold transition-all cursor-pointer"
                                title="Tutup cabang bawahan"
                            >
                                Tutup Cabang
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Zoom Controls -->
                        <div class="inline-flex items-center bg-surface-container-lowest border border-outline-variant/60 rounded-lg p-0.5 shadow-2xs">
                            <button
                                type="button"
                                @click="zoomOut"
                                class="p-1 rounded text-on-surface-variant hover:text-primary hover:bg-surface-container-high transition-colors cursor-pointer"
                                title="Perkecil Diagram"
                            >
                                <span class="material-symbols-outlined text-[18px]">remove</span>
                            </button>
                            <button
                                type="button"
                                @click="resetView"
                                class="px-2 py-0.5 text-xs font-bold text-on-surface hover:text-primary transition-colors cursor-pointer"
                                title="Reset Zoom (100%)"
                            >
                                {{ Math.round(zoom * 100) }}%
                            </button>
                            <button
                                type="button"
                                @click="zoomIn"
                                class="p-1 rounded text-on-surface-variant hover:text-primary hover:bg-surface-container-high transition-colors cursor-pointer"
                                title="Perbesar Diagram"
                            >
                                <span class="material-symbols-outlined text-[18px]">add</span>
                            </button>
                        </div>

                        <!-- Reset Pan/Position -->
                        <button
                            type="button"
                            @click="resetView"
                            class="p-1.5 rounded-lg bg-surface-container-lowest border border-outline-variant/60 text-on-surface-variant hover:text-primary transition-colors cursor-pointer"
                            title="Tengahkan Tampilan"
                        >
                            <span class="material-symbols-outlined text-[18px]">filter_center_focus</span>
                        </button>

                        <!-- Fullscreen Toggle -->
                        <button
                            type="button"
                            @click="toggleFullscreen"
                            class="p-1.5 rounded-lg bg-surface-container-lowest border border-outline-variant/60 text-on-surface-variant hover:text-primary transition-colors cursor-pointer"
                            :title="isFullscreen ? 'Keluar Layar Penuh (ESC)' : 'Mode Layar Penuh'"
                        >
                            <span class="material-symbols-outlined text-[18px]">
                                {{ isFullscreen ? 'fullscreen_exit' : 'fullscreen' }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Interactive Blueprint Canvas Viewport -->
                <div
                    ref="canvasContainerRef"
                    class="relative w-full overflow-hidden select-none bg-surface-container-low/20 transition-all"
                    :class="[
                        isFullscreen ? 'flex-1' : 'h-[640px] lg:h-[740px]',
                        isDragging ? 'cursor-grabbing' : 'cursor-grab',
                    ]"
                    @mousedown="handleMouseDown"
                    @mousemove="handleMouseMove"
                    @mouseup="handleMouseUp"
                    @mouseleave="handleMouseUp"
                >
                    <!-- Background Grid Blueprint Pattern -->
                    <div
                        class="absolute inset-0 pointer-events-none opacity-40 dark:opacity-20"
                        style="background-image: radial-gradient(rgba(100, 116, 139, 0.4) 1px, transparent 1px); background-size: 24px 24px;"
                    ></div>

                    <!-- Diagram Content Transform Layer -->
                    <div
                        class="inline-block min-w-full p-8 sm:p-12 transition-transform duration-75 origin-top"
                        :style="{
                            transform: `translate(${panX}px, ${panY}px) scale(${zoom})`,
                        }"
                    >
                        <!-- STATE: EMPTY (Belum ada jabatan) -->
                        <div
                            v-if="treeRoots.length === 0"
                            class="py-24 text-center max-w-md mx-auto"
                        >
                            <div class="w-16 h-16 rounded-2xl bg-primary/10 flex items-center justify-center text-primary mx-auto mb-3 shadow-xs">
                                <span class="material-symbols-outlined text-3xl">account_tree</span>
                            </div>
                            <h4 class="text-base font-bold text-on-surface">Susunan Organisasi Belum Ada</h4>
                            <p class="text-xs text-on-surface-variant mt-1 mb-4">
                                Mulai buat struktur organisasi yayasan dengan menambahkan jabatan pimpinan pertama (Level 1 atau Level 2).
                            </p>
                            <button
                                type="button"
                                @click="handleAddDirect"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary text-on-primary font-semibold text-xs sm:text-sm cursor-pointer shadow-xs hover:shadow-md transition-all"
                            >
                                <span class="material-symbols-outlined text-[18px]">add</span>
                                <span>Tambah Jabatan Pertama</span>
                            </button>
                        </div>

                        <!-- STATE: TREE DIAGRAM -->
                        <div v-else>
                            <!-- ORIENTASI: VERTIKAL (TOP-DOWN) -->
                            <div v-if="orientation === 'vertical'" class="flex flex-col items-center">
                                <!-- Puncak Yayasan (Apex Block) -->
                                <div v-if="showApex" class="flex flex-col items-center">
                                    <div class="relative px-6 py-3.5 rounded-2xl bg-gradient-to-r from-primary to-primary-container text-white shadow-md border-2 border-primary-fixed/40 flex items-center gap-3.5 select-none hover:shadow-lg transition-all">
                                        <div class="w-11 h-11 rounded-xl bg-white/15 flex items-center justify-center shrink-0 shadow-inner">
                                            <span class="material-symbols-outlined text-2xl text-white">account_balance</span>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[9px] font-extrabold uppercase tracking-widest text-primary-fixed bg-white/15 px-2 py-0.5 rounded">
                                                    Induk Organisasi
                                                </span>
                                                <span class="text-[10px] text-white/80">
                                                    {{ positions.length }} Total Jabatan
                                                </span>
                                            </div>
                                            <div class="text-sm sm:text-base font-extrabold text-white mt-0.5 tracking-tight">
                                                Yayasan Lembaga Ummat Amanah (YLUA)
                                            </div>
                                            <div class="text-[11px] text-white/80">
                                                Struktur Hierarki Organisasi Yayasan
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Garis Vertikal Turun dari Puncak ke Palang Cabang Utama -->
                                    <div class="w-0.5 h-7 bg-slate-300 dark:bg-slate-600 shrink-0"></div>

                                    <!-- Palang Horizontal Penghubung Antar Root Nodes -->
                                    <div class="flex items-start justify-center">
                                        <div
                                            v-for="(rootNode, idx) in treeRoots"
                                            :key="rootNode.id"
                                            class="relative flex flex-col items-center px-4"
                                        >
                                            <!-- Garis Horizontal Palang Antar Root -->
                                            <div
                                                v-if="treeRoots.length > 1 && idx > 0"
                                                class="absolute top-0 left-0 right-1/2 h-0.5 bg-slate-300 dark:bg-slate-600"
                                            ></div>
                                            <div
                                                v-if="treeRoots.length > 1 && idx < treeRoots.length - 1"
                                                class="absolute top-0 left-1/2 right-0 h-0.5 bg-slate-300 dark:bg-slate-600"
                                            ></div>

                                            <!-- Garis Vertikal Turun ke Root Node -->
                                            <div class="w-0.5 h-7 bg-slate-300 dark:bg-slate-600 shrink-0"></div>

                                            <!-- Kotak Diagram Root Node -->
                                            <OrgChartNode
                                                :node="rootNode"
                                                :orientation="orientation"
                                                :search-query="searchQuery"
                                                :active-level-filter="selectedLevelFilter"
                                                :active-dept-filter="selectedDeptFilter"
                                                :collapsed-ids="collapsedIds"
                                                :level-definitions="levelDefinitions"
                                                @add-subordinate="handleAddSubordinate"
                                                @add-peer="handleAddPeer"
                                                @edit-position="handleEdit"
                                                @delete-position="handleDelete"
                                                @toggle-collapse="toggleCollapse"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Tanpa Apex Block (Root Nodes Sejajar) -->
                                <div v-else class="flex items-start justify-center gap-8">
                                    <div
                                        v-for="rootNode in treeRoots"
                                        :key="rootNode.id"
                                        class="flex flex-col items-center"
                                    >
                                        <OrgChartNode
                                            :node="rootNode"
                                            :orientation="orientation"
                                            :search-query="searchQuery"
                                            :active-level-filter="selectedLevelFilter"
                                            :active-dept-filter="selectedDeptFilter"
                                            :collapsed-ids="collapsedIds"
                                            :level-definitions="levelDefinitions"
                                            @add-subordinate="handleAddSubordinate"
                                            @add-peer="handleAddPeer"
                                            @edit-position="handleEdit"
                                            @delete-position="handleDelete"
                                            @toggle-collapse="toggleCollapse"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- ORIENTASI: HORIZONTAL (LEFT-TO-RIGHT) -->
                            <div v-else class="flex flex-row items-center justify-start">
                                <!-- Puncak Yayasan (Horizontal) -->
                                <div v-if="showApex" class="flex flex-row items-center">
                                    <div class="relative px-5 py-3.5 rounded-2xl bg-gradient-to-r from-primary to-primary-container text-white shadow-md border-2 border-primary-fixed/40 flex items-center gap-3 shrink-0">
                                        <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-2xl text-white">account_balance</span>
                                        </div>
                                        <div>
                                            <div class="text-[9px] font-extrabold uppercase tracking-widest text-primary-fixed">Induk Yayasan</div>
                                            <div class="text-xs sm:text-sm font-extrabold text-white">YLUA</div>
                                            <div class="text-[10px] text-white/80">{{ positions.length }} Jabatan</div>
                                        </div>
                                    </div>

                                    <!-- Garis Horizontal ke Palang Vertikal -->
                                    <div class="h-0.5 w-8 bg-slate-300 dark:bg-slate-600 shrink-0"></div>

                                    <!-- Palang Vertikal Antar Root -->
                                    <div class="flex flex-col items-start justify-center">
                                        <div
                                            v-for="(rootNode, idx) in treeRoots"
                                            :key="rootNode.id"
                                            class="relative flex flex-row items-center py-3"
                                        >
                                            <div
                                                v-if="treeRoots.length > 1 && idx > 0"
                                                class="absolute left-0 top-0 bottom-1/2 w-0.5 bg-slate-300 dark:bg-slate-600"
                                            ></div>
                                            <div
                                                v-if="treeRoots.length > 1 && idx < treeRoots.length - 1"
                                                class="absolute left-0 top-1/2 bottom-0 w-0.5 bg-slate-300 dark:bg-slate-600"
                                            ></div>

                                            <div class="h-0.5 w-8 bg-slate-300 dark:bg-slate-600 shrink-0"></div>

                                            <OrgChartNode
                                                :node="rootNode"
                                                :orientation="orientation"
                                                :search-query="searchQuery"
                                                :active-level-filter="selectedLevelFilter"
                                                :active-dept-filter="selectedDeptFilter"
                                                :collapsed-ids="collapsedIds"
                                                :level-definitions="levelDefinitions"
                                                @add-subordinate="handleAddSubordinate"
                                                @add-peer="handleAddPeer"
                                                @edit-position="handleEdit"
                                                @delete-position="handleDelete"
                                                @toggle-collapse="toggleCollapse"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Tanpa Apex Block (Horizontal) -->
                                <div v-else class="flex flex-col items-start justify-center gap-6">
                                    <div
                                        v-for="rootNode in treeRoots"
                                        :key="rootNode.id"
                                        class="flex flex-row items-center"
                                    >
                                        <OrgChartNode
                                            :node="rootNode"
                                            :orientation="orientation"
                                            :search-query="searchQuery"
                                            :active-level-filter="selectedLevelFilter"
                                            :active-dept-filter="selectedDeptFilter"
                                            :collapsed-ids="collapsedIds"
                                            :level-definitions="levelDefinitions"
                                            @add-subordinate="handleAddSubordinate"
                                            @add-peer="handleAddPeer"
                                            @edit-position="handleEdit"
                                            @delete-position="handleDelete"
                                            @toggle-collapse="toggleCollapse"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Canvas Quick Navigation / Legend Bar at Bottom -->
                    <div class="absolute bottom-3 left-3 right-3 sm:left-6 sm:right-6 pointer-events-none">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-surface-container-lowest/90 backdrop-blur-md border border-outline-variant/50 shadow-md text-[11px] text-on-surface-variant pointer-events-auto flex-wrap">
                            <span class="font-bold text-on-surface flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px] text-primary">touch_app</span>
                                <span>Petunjuk:</span>
                            </span>
                            <span>Geser/drag kanvas untuk menjelajah</span>
                            <span class="text-outline-variant">•</span>
                            <span>Gunakan tombol <strong class="text-on-surface">+</strong> / <strong class="text-on-surface">-</strong> pada kotak untuk melipat bawahan</span>
                            <span class="text-outline-variant">•</span>
                            <span>Ctrl + Scroll mouse untuk zoom</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- VIEW 2: DAFTAR PER TINGKAT (5 LEVEL GRID)                 -->
            <!-- ======================================================== -->
            <div v-else-if="activeView === 'tier'" class="p-5 sm:p-6 space-y-8">
                <div
                    v-for="def in levelDefinitions"
                    :key="def.level"
                    v-show="selectedLevelFilter === 'all' || selectedLevelFilter === String(def.level)"
                    class="rounded-2xl border bg-surface-container-lowest/50 overflow-hidden transition-all shadow-xs"
                    :class="def.borderClass"
                >
                    <!-- Tier Header Banner -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-3.5 border-b" :class="[def.borderClass, def.accentBg]">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-surface-container-lowest flex items-center justify-center shadow-2xs">
                                <span class="material-symbols-outlined text-lg">{{ def.icon }}</span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="text-sm sm:text-base font-bold text-on-surface">
                                        {{ def.title }}
                                    </h4>
                                    <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full border', def.badgeClass]">
                                        {{ positionsByLevel[def.level]?.length || 0 }} Jabatan
                                    </span>
                                </div>
                                <p class="text-xs text-on-surface-variant">
                                    {{ def.subtitle }}
                                </p>
                            </div>
                        </div>

                        <!-- Add at this level button -->
                        <button
                            type="button"
                            @click="handleAddAtLevel(def.level)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-surface-container-lowest border border-outline-variant/60 hover:border-primary text-on-surface hover:text-primary text-xs font-bold transition-all shadow-2xs cursor-pointer self-start sm:self-auto shrink-0"
                        >
                            <span class="material-symbols-outlined text-[16px]">add</span>
                            <span>Tambah di Level {{ def.level }}</span>
                        </button>
                    </div>

                    <!-- Cards Grid in this level -->
                    <div class="p-4 sm:p-5">
                        <div
                            v-if="positionsByLevel[def.level] && positionsByLevel[def.level].length > 0"
                            class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3.5"
                        >
                            <div
                                v-for="pos in positionsByLevel[def.level]"
                                :key="pos.id"
                                class="bg-surface-container-lowest rounded-xl border border-outline-variant/50 p-4 transition-all duration-200 hover:shadow-md flex flex-col justify-between group"
                                :class="def.cardBorder"
                            >
                                <div>
                                    <!-- Badges line -->
                                    <div class="flex items-center justify-between gap-2 mb-2 flex-wrap">
                                        <div class="flex items-center gap-1.5">
                                            <span :class="['text-[10px] font-bold px-2 py-0.5 rounded border', def.badgeClass]">
                                                Level {{ pos.level }}
                                            </span>
                                            <span
                                                v-if="pos.department_name"
                                                class="text-[10px] font-semibold px-2 py-0.5 rounded bg-surface-container-high text-on-surface-variant border border-outline-variant/40"
                                            >
                                                {{ pos.department_name }}
                                            </span>
                                            <span
                                                v-else
                                                class="text-[10px] font-semibold px-2 py-0.5 rounded bg-primary/5 text-primary border border-primary/20"
                                            >
                                                Yayasan (Lintas Bidang)
                                            </span>
                                        </div>

                                        <!-- Subordinate count badge -->
                                        <span
                                            v-if="pos.children_count > 0"
                                            class="inline-flex items-center gap-1 text-[10px] font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-full"
                                            title="Jumlah bawahan langsung"
                                        >
                                            <span class="material-symbols-outlined text-[12px]">subdirectory_arrow_right</span>
                                            {{ pos.children_count }} bawahan
                                        </span>
                                    </div>

                                    <!-- Position Name -->
                                    <h5 class="text-sm font-bold text-on-surface leading-snug group-hover:text-primary transition-colors">
                                        {{ pos.name }}
                                    </h5>

                                    <!-- Atasan Langsung Indicator -->
                                    <div class="mt-2.5 pt-2 border-t border-outline-variant/20 flex items-center gap-1.5 text-xs text-on-surface-variant">
                                        <span class="material-symbols-outlined text-[15px] text-primary/70 shrink-0">
                                            arrow_upward
                                        </span>
                                        <span class="truncate">
                                            <span class="text-on-surface-variant/70">Atasan:</span>
                                            <span class="font-medium text-on-surface ml-1">
                                                {{ pos.parent_name || 'Tingkat Tertinggi (Mandiri)' }}
                                            </span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Card Footer: Quick Action Buttons -->
                                <div class="mt-3.5 pt-2.5 border-t border-outline-variant/30 flex items-center justify-between gap-1 flex-wrap">
                                    <div class="flex items-center gap-1">
                                        <!-- + Bawahan (Vertikal) -->
                                        <button
                                            v-if="pos.level < 5"
                                            type="button"
                                            @click="handleAddSubordinate(pos)"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-primary/10 hover:bg-primary hover:text-on-primary text-primary text-[11px] font-bold transition-colors cursor-pointer"
                                            title="Tambah jabatan bawahan langsung di level bawahnya"
                                        >
                                            <span class="material-symbols-outlined text-[13px]">add</span>
                                            <span>+ Bawahan</span>
                                        </button>

                                        <!-- + Sejajar (Horizontal) -->
                                        <button
                                            type="button"
                                            @click="handleAddPeer(pos)"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-surface-container-high hover:bg-surface-container-highest text-on-surface text-[11px] font-bold transition-colors cursor-pointer"
                                            title="Tambah jabatan sejajar di level ini"
                                        >
                                            <span class="material-symbols-outlined text-[13px]">add</span>
                                            <span>+ Sejajar</span>
                                        </button>
                                    </div>

                                    <div class="flex items-center gap-1">
                                        <!-- Edit -->
                                        <button
                                            type="button"
                                            @click="handleEdit(pos)"
                                            class="p-1 rounded-md text-on-surface-variant hover:text-primary hover:bg-primary/10 transition-colors cursor-pointer"
                                            title="Edit Jabatan"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">edit</span>
                                        </button>

                                        <!-- Hapus -->
                                        <button
                                            type="button"
                                            @click="handleDelete(pos)"
                                            class="p-1 rounded-md text-on-surface-variant hover:text-error hover:bg-error/10 transition-colors cursor-pointer"
                                            title="Hapus Jabatan"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty state for level -->
                        <div
                            v-else
                            class="py-8 px-4 text-center border-2 border-dashed border-outline-variant/40 rounded-xl"
                        >
                            <span class="material-symbols-outlined text-3xl text-outline/50 mb-1">
                                badge
                            </span>
                            <p class="text-xs font-medium text-on-surface-variant mb-2">
                                Belum ada jabatan di {{ def.title }}.
                            </p>
                            <button
                                type="button"
                                @click="handleAddAtLevel(def.level)"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-on-primary text-xs font-bold transition-all cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[14px]">add</span>
                                <span>Tambah Jabatan Pertama di Level Ini</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- VIEW 3: KELOLA BIDANG KERJA (TABEL BIDANG)                -->
            <!-- ======================================================== -->
            <div v-else-if="activeView === 'dept'" class="p-5 sm:p-6 space-y-4">
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
                                            <span :class="['text-[9px] font-bold px-1 rounded', getLevelDef(p.level).badgeClass]">
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
