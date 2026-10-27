<script setup>
import { ref, computed } from 'vue';

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
const activeView = ref('tier'); // 'tier' | 'tree' | 'dept'

const levelDefinitions = [
    {
        level: 1,
        title: 'Level 1: Dewan Pembina & Pengawas Yayasan',
        subtitle: 'Pemegang kebijakan tertinggi, arahan strategis, dan pengawasan yayasan',
        badgeClass: 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 border-emerald-500/30',
        borderClass: 'border-emerald-500/30 dark:border-emerald-500/20',
        accentBg: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
        cardBorder: 'hover:border-emerald-500/60',
        dotColor: 'bg-emerald-500',
        icon: 'verified_user',
    },
    {
        level: 2,
        title: 'Level 2: Pengurus Harian & Direksi Yayasan',
        subtitle: 'Pimpinan harian & direksi: Ketua Umum, Sekretaris, Bendahara, Direktur Eksekutif',
        badgeClass: 'bg-indigo-500/15 text-indigo-800 dark:text-indigo-300 border-indigo-500/30',
        borderClass: 'border-indigo-500/30 dark:border-indigo-500/20',
        accentBg: 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300',
        cardBorder: 'hover:border-indigo-500/60',
        dotColor: 'bg-indigo-500',
        icon: 'corporate_fare',
    },
    {
        level: 3,
        title: 'Level 3: Kepala Bidang Yayasan',
        subtitle: 'Kepala bidang kerja fungsional: Pendidikan, SDM, Keuangan, Sarpras, Dakwah & Humas',
        badgeClass: 'bg-amber-500/15 text-amber-800 dark:text-amber-300 border-amber-500/30',
        borderClass: 'border-amber-500/30 dark:border-amber-500/20',
        accentBg: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
        cardBorder: 'hover:border-amber-500/60',
        dotColor: 'bg-amber-500',
        icon: 'manage_accounts',
    },
    {
        level: 4,
        title: 'Level 4: Koordinator & Kepala Bagian / Unit',
        subtitle: 'Koordinator operasional, penjamin mutu, bendahara bidang, pengawas sekolah',
        badgeClass: 'bg-cyan-500/15 text-cyan-800 dark:text-cyan-300 border-cyan-500/30',
        borderClass: 'border-cyan-500/30 dark:border-cyan-500/20',
        accentBg: 'bg-cyan-500/10 text-cyan-700 dark:text-cyan-300',
        cardBorder: 'hover:border-cyan-500/60',
        dotColor: 'bg-cyan-500',
        icon: 'hub',
    },
    {
        level: 5,
        title: 'Level 5: Staf Pelaksana & Tim Teknis',
        subtitle: 'Staf pelaksana administrasi, logistik, pengadaan, dan teknis operasional harian',
        badgeClass: 'bg-slate-500/15 text-slate-800 dark:text-slate-300 border-slate-500/30',
        borderClass: 'border-slate-500/30 dark:border-slate-500/20',
        accentBg: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
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
            parent_name: parent ? parent.name : p.parent_name || null,
            parent_level: parent ? (Number(parent.level) || 3) : null,
        };
    });
});

// Filtered positions based on search, level filter, and department filter
const filteredPositions = computed(() => {
    return enrichedPositions.value.filter(p => {
        if (searchQuery.value) {
            const q = searchQuery.value.toLowerCase();
            const matchName = p.name.toLowerCase().includes(q);
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

// Grouped by level
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

// Tree structure generation
const treeRoots = computed(() => {
    const nodes = filteredPositions.value;
    const nodeMap = new Map();
    nodes.forEach(n => nodeMap.set(n.id, { ...n, children: [] }));

    const roots = [];
    nodes.forEach(n => {
        const current = nodeMap.get(n.id);
        if (n.parent_id && nodeMap.has(n.parent_id)) {
            nodeMap.get(n.parent_id).children.push(current);
        } else {
            roots.push(current);
        }
    });

    // Sort roots by level, then name
    roots.sort((a, b) => (a.level - b.level) || a.name.localeCompare(b.name));
    return roots;
});

// Helper for level definition
function getLevelDef(level) {
    const lvl = Math.min(5, Math.max(1, Number(level) || 3));
    return levelDefinitions.find(d => d.level === lvl) || levelDefinitions[2];
}

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
        <div class="bg-surface-container-lowest rounded-2xl border border-primary/10 shadow-[0px_4px_24px_rgba(0,40,20,0.06)] overflow-hidden">
            <!-- Header Section -->
            <div class="p-5 sm:p-6 border-b border-outline-variant/30 bg-surface-container-low/30">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex items-start sm:items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary shadow-xs shrink-0">
                            <span class="material-symbols-outlined text-2xl">account_tree</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <h3 class="text-lg sm:text-xl font-bold text-on-surface">Diagram Susunan Organisasi</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-primary/10 text-primary border border-primary/20">
                                    5 Tingkat Jabatan
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm text-on-surface-variant mt-0.5">
                                Struktur hierarki organisasi yayasan secara vertikal (atasan-bawahan) dan horizontal (sejajar)
                            </p>
                        </div>
                    </div>

                    <!-- Top Action Buttons -->
                    <div class="flex items-center gap-2.5 flex-wrap">
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
                        <div class="relative flex-1 min-w-[200px]">
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
                    </div>

                    <!-- View Switcher -->
                    <div class="inline-flex items-center p-1 bg-surface-container-low border border-outline-variant/60 rounded-xl shrink-0 self-start md:self-auto">
                        <button
                            type="button"
                            @click="activeView = 'tier'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                            :class="activeView === 'tier'
                                ? 'bg-primary text-on-primary shadow-xs'
                                : 'text-on-surface-variant hover:text-on-surface'"
                        >
                            <span class="material-symbols-outlined text-[16px]">view_column</span>
                            <span>Tingkat (5 Level)</span>
                        </button>

                        <button
                            type="button"
                            @click="activeView = 'tree'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                            :class="activeView === 'tree'
                                ? 'bg-primary text-on-primary shadow-xs'
                                : 'text-on-surface-variant hover:text-on-surface'"
                        >
                            <span class="material-symbols-outlined text-[16px]">device_hub</span>
                            <span>Bagan Hierarki</span>
                        </button>

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

            <!-- VIEW 1: 5-LEVEL TIER VIEW (DEFAULT) -->
            <div v-if="activeView === 'tier'" class="p-5 sm:p-6 space-y-8">
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

            <!-- VIEW 2: TREE FLOW VIEW -->
            <div v-else-if="activeView === 'tree'" class="p-5 sm:p-6">
                <div class="bg-surface-container-low/30 rounded-2xl p-4 sm:p-6 border border-outline-variant/30 overflow-x-auto min-h-[400px]">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-on-surface">Bagan Alur Hierarki Organisasi</h4>
                            <p class="text-xs text-on-surface-variant">Hierarki vertikal mulai dari Level 1 turun ke tingkat pelaksana</p>
                        </div>
                        <span class="text-xs text-on-surface-variant font-medium">
                            Menampilkan {{ treeRoots.length }} jalur utama
                        </span>
                    </div>

                    <!-- Recursive Tree Nodes Container -->
                    <div class="space-y-6">
                        <div
                            v-for="rootNode in treeRoots"
                            :key="rootNode.id"
                            class="border border-outline-variant/40 rounded-xl p-4 bg-surface-container-lowest shadow-2xs"
                        >
                            <!-- Root Node Card -->
                            <div class="flex items-start justify-between gap-3 p-3 rounded-lg border bg-surface-container-low/50" :class="getLevelDef(rootNode.level).borderClass">
                                <div class="flex items-start gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-surface-container-lowest flex items-center justify-center shrink-0 shadow-2xs">
                                        <span class="material-symbols-outlined text-base text-primary">{{ getLevelDef(rootNode.level).icon }}</span>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span :class="['text-[10px] font-bold px-1.5 py-0.2 rounded border', getLevelDef(rootNode.level).badgeClass]">
                                                Level {{ rootNode.level }}
                                            </span>
                                            <span v-if="rootNode.department_name" class="text-[10px] px-1.5 py-0.2 rounded bg-surface-container-high text-on-surface-variant">
                                                {{ rootNode.department_name }}
                                            </span>
                                        </div>
                                        <div class="text-sm font-bold text-on-surface mt-0.5">
                                            {{ rootNode.name }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1 shrink-0">
                                    <button
                                        v-if="rootNode.level < 5"
                                        type="button"
                                        @click="handleAddSubordinate(rootNode)"
                                        class="px-2 py-1 rounded bg-primary/10 hover:bg-primary hover:text-on-primary text-primary text-[10px] font-bold transition-colors cursor-pointer"
                                        title="+ Bawahan"
                                    >
                                        + Bawahan
                                    </button>
                                    <button
                                        type="button"
                                        @click="handleAddPeer(rootNode)"
                                        class="px-2 py-1 rounded bg-surface-container-high text-on-surface text-[10px] font-bold transition-colors cursor-pointer"
                                        title="+ Sejajar"
                                    >
                                        + Sejajar
                                    </button>
                                    <button
                                        type="button"
                                        @click="handleEdit(rootNode)"
                                        class="p-1 rounded text-on-surface-variant hover:text-primary cursor-pointer"
                                        title="Edit"
                                    >
                                        <span class="material-symbols-outlined text-[15px]">edit</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="handleDelete(rootNode)"
                                        class="p-1 rounded text-on-surface-variant hover:text-error cursor-pointer"
                                        title="Hapus"
                                    >
                                        <span class="material-symbols-outlined text-[15px]">delete</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Children list -->
                            <div v-if="rootNode.children && rootNode.children.length > 0" class="pl-6 mt-3 space-y-3 border-l-2 border-primary/20 ml-4">
                                <div
                                    v-for="child in rootNode.children"
                                    :key="child.id"
                                    class="border border-outline-variant/40 rounded-lg p-3 bg-surface-container-low/20"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span :class="['text-[10px] font-bold px-1.5 py-0.2 rounded border', getLevelDef(child.level).badgeClass]">
                                                    Level {{ child.level }}
                                                </span>
                                                <span v-if="child.department_name" class="text-[10px] px-1.5 py-0.2 rounded bg-surface-container-high text-on-surface-variant">
                                                    {{ child.department_name }}
                                                </span>
                                            </div>
                                            <div class="text-xs sm:text-sm font-bold text-on-surface mt-0.5">
                                                {{ child.name }}
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-1 shrink-0">
                                            <button
                                                v-if="child.level < 5"
                                                type="button"
                                                @click="handleAddSubordinate(child)"
                                                class="px-2 py-0.5 rounded bg-primary/10 hover:bg-primary hover:text-on-primary text-primary text-[10px] font-bold transition-colors cursor-pointer"
                                            >
                                                + Bawahan
                                            </button>
                                            <button
                                                type="button"
                                                @click="handleAddPeer(child)"
                                                class="px-2 py-0.5 rounded bg-surface-container-high text-on-surface text-[10px] font-bold transition-colors cursor-pointer"
                                            >
                                                + Sejajar
                                            </button>
                                            <button
                                                type="button"
                                                @click="handleEdit(child)"
                                                class="p-1 rounded text-on-surface-variant hover:text-primary cursor-pointer"
                                            >
                                                <span class="material-symbols-outlined text-[14px]">edit</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="handleDelete(child)"
                                                class="p-1 rounded text-on-surface-variant hover:text-error cursor-pointer"
                                            >
                                                <span class="material-symbols-outlined text-[14px]">delete</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Grandchildren list -->
                                    <div v-if="child.children && child.children.length > 0" class="pl-5 mt-2.5 space-y-2 border-l-2 border-primary/20 ml-3">
                                        <div
                                            v-for="grandChild in child.children"
                                            :key="grandChild.id"
                                            class="flex items-center justify-between gap-2 p-2 rounded border border-outline-variant/30 bg-surface-container-lowest"
                                        >
                                            <div>
                                                <div class="flex items-center gap-1.5">
                                                    <span :class="['text-[9px] font-bold px-1.5 py-0.2 rounded border', getLevelDef(grandChild.level).badgeClass]">
                                                        Level {{ grandChild.level }}
                                                    </span>
                                                    <span class="text-xs font-semibold text-on-surface">
                                                        {{ grandChild.name }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-1 shrink-0">
                                                <button
                                                    v-if="grandChild.level < 5"
                                                    type="button"
                                                    @click="handleAddSubordinate(grandChild)"
                                                    class="px-1.5 py-0.5 rounded bg-primary/10 text-primary text-[10px] font-bold"
                                                >
                                                    + Bawahan
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="handleAddPeer(grandChild)"
                                                    class="px-1.5 py-0.5 rounded bg-surface-container-high text-on-surface text-[10px] font-bold"
                                                >
                                                    + Sejajar
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="handleEdit(grandChild)"
                                                    class="p-0.5 rounded text-on-surface-variant hover:text-primary"
                                                >
                                                    <span class="material-symbols-outlined text-[14px]">edit</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="handleDelete(grandChild)"
                                                    class="p-0.5 rounded text-on-surface-variant hover:text-error"
                                                >
                                                    <span class="material-symbols-outlined text-[14px]">delete</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="treeRoots.length === 0" class="py-12 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-4xl mb-2 opacity-50">account_tree</span>
                            <p class="text-sm">Tidak ada jabatan yang sesuai dengan filter pencarian.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW 3: DEPARTMENT MANAGEMENT (TABLE BIDANG) -->
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
