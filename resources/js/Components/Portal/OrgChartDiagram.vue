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
const selectedDeptFilter = ref('all');
const activeTab = ref('diagram'); // 'diagram' | 'departments'

const levelDefinitions = [
    {
        level: 1,
        title: 'Level 1: Dewan Pembina & Pengawas Yayasan',
        subtitle: 'Pemegang otoritas tertinggi, arahan strategis, dan dewan pengawas yayasan',
        colorName: 'emerald',
        badgeClass: 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 border-emerald-500/30',
        borderClass: 'border-emerald-500/30',
        headerBg: 'bg-emerald-500/10 text-emerald-900 dark:text-emerald-200 border-b border-emerald-500/20',
        accentText: 'text-emerald-700 dark:text-emerald-400',
        iconBg: 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300',
        cardBorder: 'hover:border-emerald-500/60',
        btnClass: 'bg-emerald-600 hover:bg-emerald-700 text-white',
        icon: 'verified_user',
        connectorText: 'Melapor ke Dewan Pembina (Level 1)',
    },
    {
        level: 2,
        title: 'Level 2: Pengurus Harian & Direksi Yayasan',
        subtitle: 'Pimpinan harian & dewan direksi: Ketua Umum, Sekretaris, Bendahara, Direktur Eksekutif',
        colorName: 'indigo',
        badgeClass: 'bg-indigo-500/15 text-indigo-800 dark:text-indigo-300 border-indigo-500/30',
        borderClass: 'border-indigo-500/30',
        headerBg: 'bg-indigo-500/10 text-indigo-900 dark:text-indigo-200 border-b border-indigo-500/20',
        accentText: 'text-indigo-700 dark:text-indigo-400',
        iconBg: 'bg-indigo-500/20 text-indigo-700 dark:text-indigo-300',
        cardBorder: 'hover:border-indigo-500/60',
        btnClass: 'bg-indigo-600 hover:bg-indigo-700 text-white',
        icon: 'corporate_fare',
        connectorText: 'Di bawah arahan Pengurus Harian (Level 2)',
    },
    {
        level: 3,
        title: 'Level 3: Kepala Bidang Yayasan',
        subtitle: 'Pimpinan bidang fungsional: Pendidikan, SDM, Keuangan, Sarpras, Dakwah & Humas',
        colorName: 'amber',
        badgeClass: 'bg-amber-500/15 text-amber-800 dark:text-amber-300 border-amber-500/30',
        borderClass: 'border-amber-500/30',
        headerBg: 'bg-amber-500/10 text-amber-900 dark:text-amber-200 border-b border-amber-500/20',
        accentText: 'text-amber-700 dark:text-amber-400',
        iconBg: 'bg-amber-500/20 text-amber-700 dark:text-amber-300',
        cardBorder: 'hover:border-amber-500/60',
        btnClass: 'bg-amber-600 hover:bg-amber-700 text-white',
        icon: 'manage_accounts',
        connectorText: 'Di bawah koordinasi Kepala Bidang (Level 3)',
    },
    {
        level: 4,
        title: 'Level 4: Koordinator & Kepala Bagian / Unit',
        subtitle: 'Koordinator program kerja, pengawas sekolah, penjamin mutu, kepala sub-bagian',
        colorName: 'cyan',
        badgeClass: 'bg-cyan-500/15 text-cyan-800 dark:text-cyan-300 border-cyan-500/30',
        borderClass: 'border-cyan-500/30',
        headerBg: 'bg-cyan-500/10 text-cyan-900 dark:text-cyan-200 border-b border-cyan-500/20',
        accentText: 'text-cyan-700 dark:text-cyan-400',
        iconBg: 'bg-cyan-500/20 text-cyan-700 dark:text-cyan-300',
        cardBorder: 'hover:border-cyan-500/60',
        btnClass: 'bg-cyan-600 hover:bg-cyan-700 text-white',
        icon: 'hub',
        connectorText: 'Di bawah supervisi Koordinator / Bagian (Level 4)',
    },
    {
        level: 5,
        title: 'Level 5: Staf Pelaksana & Tim Teknis',
        subtitle: 'Staf pelaksana administrasi, logistik, pengadaan, dan teknis operasional harian',
        colorName: 'slate',
        badgeClass: 'bg-slate-500/15 text-slate-800 dark:text-slate-300 border-slate-500/30',
        borderClass: 'border-slate-500/30',
        headerBg: 'bg-slate-500/10 text-slate-900 dark:text-slate-200 border-b border-slate-500/20',
        accentText: 'text-slate-700 dark:text-slate-400',
        iconBg: 'bg-slate-500/20 text-slate-700 dark:text-slate-300',
        cardBorder: 'hover:border-slate-500/60',
        btnClass: 'bg-slate-700 hover:bg-slate-800 text-white',
        icon: 'group',
        connectorText: '',
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

// Group filtered positions by level (1 to 5)
const positionsByLevel = computed(() => {
    const groups = { 1: [], 2: [], 3: [], 4: [], 5: [] };
    filteredPositions.value.forEach(p => {
        const lvl = Math.min(5, Math.max(1, p.level));
        if (!groups[lvl]) groups[lvl] = [];
        groups[lvl].push(p);
    });
    return groups;
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
                                    5 Tingkat / Level
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm text-on-surface-variant mt-0.5">
                                Struktur hierarki 5 baris level jabatan yayasan beserta relasi garis atasan dan bawahan
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
                            <span>5 Baris Level Organisasi</span>
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
            <!-- 5 BARIS CONTAINER SUSUNAN ORGANISASI (1 UNTUK SETIAP LEVEL) -->
            <!-- ======================================================== -->
            <div v-if="activeTab === 'diagram'" class="p-4 sm:p-6 space-y-2">
                <div
                    v-for="(def, idx) in levelDefinitions"
                    :key="def.level"
                    class="space-y-2"
                >
                    <!-- CONTAINER BARIS UNTUK LEVEL INI -->
                    <div
                        class="rounded-2xl border bg-surface-container-lowest overflow-hidden transition-all duration-200 shadow-xs hover:shadow-sm"
                        :class="def.borderClass"
                    >
                        <!-- Container Header Baris Level -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-4 sm:px-5 py-3" :class="def.headerBg">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 shadow-2xs" :class="def.iconBg">
                                    <span class="material-symbols-outlined text-xl">{{ def.icon }}</span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="text-sm sm:text-base font-bold text-on-surface">
                                            {{ def.title }}
                                        </h4>
                                        <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full border shadow-2xs', def.badgeClass]">
                                            {{ positionsByLevel[def.level]?.length || 0 }} Jabatan
                                        </span>
                                    </div>
                                    <p class="text-xs text-on-surface-variant/90 mt-0.5">
                                        {{ def.subtitle }}
                                    </p>
                                </div>
                            </div>

                            <!-- Tombol Tambah di Level Ini -->
                            <button
                                type="button"
                                @click="handleAddAtLevel(def.level)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all shadow-xs hover:shadow-md cursor-pointer self-start sm:self-auto shrink-0"
                                :class="def.btnClass"
                            >
                                <span class="material-symbols-outlined text-[16px]">add</span>
                                <span>+ Tambah di Level {{ def.level }}</span>
                            </button>
                        </div>

                        <!-- Content Area: Daftar Kartu Jabatan di Level Ini -->
                        <div class="p-4 sm:p-5 bg-surface-container-low/20">
                            <!-- JIKA SUDAH ADA JABATAN DI LEVEL INI -->
                            <div
                                v-if="positionsByLevel[def.level] && positionsByLevel[def.level].length > 0"
                                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3.5"
                            >
                                <div
                                    v-for="pos in positionsByLevel[def.level]"
                                    :key="pos.id"
                                    class="bg-surface-container-lowest rounded-xl border border-outline-variant/60 p-4 transition-all duration-200 hover:shadow-md flex flex-col justify-between group"
                                    :class="def.cardBorder"
                                >
                                    <div>
                                        <!-- Header Kartu: Badge Level & Bidang -->
                                        <div class="flex items-center justify-between gap-1.5 mb-2.5 flex-wrap">
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
                                                    class="text-[10px] font-semibold px-2 py-0.5 rounded bg-primary/10 text-primary border border-primary/20"
                                                >
                                                    Yayasan
                                                </span>
                                            </div>

                                            <!-- Jumlah Bawahan Badge -->
                                            <span
                                                v-if="pos.subordinates && pos.subordinates.length > 0"
                                                class="inline-flex items-center gap-1 text-[10px] font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-full"
                                                title="Membawahi jabatan secara langsung"
                                            >
                                                <span class="material-symbols-outlined text-[12px]">subdirectory_arrow_right</span>
                                                {{ pos.subordinates.length }} bawahan
                                            </span>
                                        </div>

                                        <!-- Nama Jabatan -->
                                        <h5 class="text-sm font-bold text-on-surface leading-snug group-hover:text-primary transition-colors">
                                            {{ pos.name }}
                                        </h5>

                                        <!-- Garis & Informasi Hierarki (Atasan Langsung) -->
                                        <div class="mt-3 pt-2 border-t border-outline-variant/20 space-y-1.5">
                                            <div class="flex items-center gap-1.5 text-xs text-on-surface-variant">
                                                <span class="material-symbols-outlined text-[15px] text-primary/70 shrink-0">
                                                    arrow_upward
                                                </span>
                                                <span class="truncate">
                                                    <span class="text-on-surface-variant/70 text-[11px]">Atasan:</span>
                                                    <span v-if="pos.parent_name" class="font-semibold text-on-surface ml-1">
                                                        {{ pos.parent_name }}
                                                    </span>
                                                    <span v-else class="font-medium text-emerald-700 dark:text-emerald-400 ml-1">
                                                        Tingkat Tertinggi (Mandiri)
                                                    </span>
                                                </span>
                                            </div>

                                            <!-- Daftar Bawahan Langsung (Jika ada) -->
                                            <div v-if="pos.subordinates && pos.subordinates.length > 0" class="flex items-start gap-1.5 text-[11px] text-on-surface-variant">
                                                <span class="material-symbols-outlined text-[14px] text-secondary/70 shrink-0 mt-0.5">
                                                    subdirectory_arrow_right
                                                </span>
                                                <div class="truncate text-on-surface-variant/90">
                                                    <span class="text-[10px] text-on-surface-variant/70">Bawahan:</span>
                                                    <span class="ml-1 font-medium">{{ pos.subordinates.join(', ') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Footer Kartu: Tombol Aksi (+ Bawahan, + Sejajar, Edit, Hapus) -->
                                    <div class="mt-3.5 pt-2.5 border-t border-outline-variant/30 flex items-center justify-between gap-1 flex-wrap">
                                        <div class="flex items-center gap-1">
                                            <!-- Tombol + Bawahan (Vertikal) -->
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

                                            <!-- Tombol + Sejajar (Horizontal) -->
                                            <button
                                                type="button"
                                                @click="handleAddPeer(pos)"
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-surface-container-high hover:bg-surface-container-highest text-on-surface text-[11px] font-bold transition-colors cursor-pointer"
                                                title="Tambah jabatan sejajar di level yang sama"
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

                            <!-- EMPTY STATE JIKA BELUM ADA JABATAN DI LEVEL INI -->
                            <div
                                v-else
                                class="py-8 px-4 text-center border-2 border-dashed border-outline-variant/40 rounded-xl bg-surface-container-lowest/60"
                            >
                                <span class="material-symbols-outlined text-3xl text-outline/40 mb-1">
                                    {{ def.icon }}
                                </span>
                                <p class="text-xs font-semibold text-on-surface-variant mb-2">
                                    Belum ada jabatan yang ditambahkan di {{ def.title.split(':')[0] }}.
                                </p>
                                <button
                                    type="button"
                                    @click="handleAddAtLevel(def.level)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all shadow-xs cursor-pointer"
                                    :class="def.btnClass"
                                >
                                    <span class="material-symbols-outlined text-[15px]">add_circle</span>
                                    <span>Tambah Jabatan Pertama di Level {{ def.level }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- GARIS PENGHUBUNG HIERARKI VERTIKAL ANTAR LEVEL (KECUALI SETELAH LEVEL 5) -->
                    <div
                        v-if="idx < levelDefinitions.length - 1"
                        class="flex items-center justify-center my-1"
                    >
                        <div class="flex flex-col items-center">
                            <!-- Garis Vertikal Atas -->
                            <div class="h-3 w-0.5 bg-primary/40"></div>
                            
                            <!-- Badge Penanda Garis Hierarki Antar-Level -->
                            <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-surface-container-lowest border border-primary/20 text-primary text-[10px] font-semibold shadow-2xs">
                                <span class="material-symbols-outlined text-[13px]">arrow_downward</span>
                                <span>{{ def.connectorText }}</span>
                            </div>

                            <!-- Garis Vertikal Bawah -->
                            <div class="h-3 w-0.5 bg-primary/40"></div>
                        </div>
                    </div>
                </div>
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
