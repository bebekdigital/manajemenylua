<script setup>
import { computed } from 'vue';

defineOptions({
    name: 'OrgChartNode',
});

const props = defineProps({
    node: {
        type: Object,
        required: true,
    },
    orientation: {
        type: String,
        default: 'vertical', // 'vertical' | 'horizontal'
    },
    searchQuery: {
        type: String,
        default: '',
    },
    activeLevelFilter: {
        type: [String, Number],
        default: 'all',
    },
    activeDeptFilter: {
        type: [String, Number],
        default: 'all',
    },
    collapsedIds: {
        type: Object,
        default: () => new Set(),
    },
    levelDefinitions: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits([
    'add-subordinate',
    'add-peer',
    'edit-position',
    'delete-position',
    'toggle-collapse',
]);

const hasChildren = computed(() => {
    return Array.isArray(props.node.children) && props.node.children.length > 0;
});

const isCollapsed = computed(() => {
    return props.collapsedIds ? props.collapsedIds.has(props.node.id) : false;
});

const levelDef = computed(() => {
    const lvl = Math.min(5, Math.max(1, Number(props.node.level) || 3));
    return props.levelDefinitions.find(d => d.level === lvl) || props.levelDefinitions[2];
});

const isMatch = computed(() => {
    if (!props.searchQuery) return false;
    const q = props.searchQuery.toLowerCase().trim();
    if (!q) return false;
    const nameMatch = props.node.name ? props.node.name.toLowerCase().includes(q) : false;
    const deptMatch = props.node.department_name ? props.node.department_name.toLowerCase().includes(q) : false;
    return nameMatch || deptMatch;
});

const isDimmed = computed(() => {
    if (props.activeLevelFilter !== 'all' && Number(props.activeLevelFilter) !== props.node.level) {
        return true;
    }
    if (props.activeDeptFilter !== 'all') {
        if (props.activeDeptFilter === 'null' && props.node.department_id) {
            return true;
        }
        if (props.activeDeptFilter !== 'null' && props.node.department_id !== Number(props.activeDeptFilter)) {
            return true;
        }
    }
    return false;
});
</script>

<template>
    <!-- VERTICAL / TOP-DOWN ORIENTATION -->
    <div v-if="orientation === 'vertical'" class="flex flex-col items-center">
        <!-- NODE BOX (KOTAK DIAGRAM) -->
        <div
            class="group relative w-[240px] rounded-xl bg-surface-container-lowest border-2 transition-all duration-200 select-none text-left shadow-sm"
            :class="[
                levelDef.borderClass,
                isMatch ? 'ring-4 ring-primary shadow-xl scale-105 z-20 border-primary' : 'hover:shadow-md hover:-translate-y-0.5',
                isDimmed ? 'opacity-35 hover:opacity-100' : 'opacity-100',
            ]"
        >
            <!-- Header Strip (Warna Sesuai Level) -->
            <div
                class="px-3 py-1.5 rounded-t-[9px] flex items-center justify-between text-white text-[10px] font-bold tracking-wider"
                :class="levelDef.headerGradient"
            >
                <div class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[15px]">{{ levelDef.icon }}</span>
                    <span>LEVEL {{ node.level }}</span>
                </div>
                <div class="flex items-center gap-1">
                    <span
                        v-if="hasChildren"
                        class="bg-black/25 px-1.5 py-0.5 rounded text-[9px] font-semibold flex items-center gap-0.5"
                        :title="`${node.children.length} bawahan langsung`"
                    >
                        <span class="material-symbols-outlined text-[11px]">fork_right</span>
                        {{ node.children.length }}
                    </span>
                    <span
                        v-if="isMatch"
                        class="bg-white text-emerald-800 font-bold px-1.5 py-0.2 rounded text-[9px] animate-pulse"
                    >
                        Cocok
                    </span>
                </div>
            </div>

            <!-- Body Kotak Diagram -->
            <div class="p-3">
                <!-- Nama Jabatan -->
                <h5
                    class="text-xs sm:text-[13px] font-bold text-on-surface text-center line-clamp-2 min-h-[36px] flex items-center justify-center leading-snug group-hover:text-primary transition-colors"
                    :title="node.name"
                >
                    {{ node.name }}
                </h5>

                <!-- Tag Bidang Kerja -->
                <div class="flex items-center justify-center mt-1.5">
                    <span
                        v-if="node.department_name"
                        class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-md bg-surface-container-high text-on-surface-variant truncate max-w-[210px]"
                        :title="`Bidang: ${node.department_name}`"
                    >
                        <span class="material-symbols-outlined text-[12px] opacity-70">business</span>
                        <span class="truncate">{{ node.department_name }}</span>
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-md bg-primary/10 text-primary border border-primary/20"
                        title="Yayasan / Lintas Bidang"
                    >
                        <span class="material-symbols-outlined text-[12px]">account_balance</span>
                        <span>Yayasan (Lintas Bidang)</span>
                    </span>
                </div>

                <!-- Tombol Aksi Kotak -->
                <div class="mt-2.5 pt-2 border-t border-outline-variant/30 flex items-center justify-between gap-1">
                    <div class="flex items-center gap-1">
                        <!-- + Bawahan -->
                        <button
                            v-if="node.level < 5"
                            type="button"
                            @click.stop="$emit('add-subordinate', node)"
                            class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded bg-primary/10 hover:bg-primary hover:text-white text-primary text-[10px] font-bold transition-colors cursor-pointer"
                            title="Tambah Bawahan Langsung"
                        >
                            <span class="material-symbols-outlined text-[12px]">add</span>
                            <span>Bawahan</span>
                        </button>

                        <!-- + Sejajar -->
                        <button
                            type="button"
                            @click.stop="$emit('add-peer', node)"
                            class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded bg-surface-container-high hover:bg-surface-container-highest text-on-surface text-[10px] font-bold transition-colors cursor-pointer"
                            title="Tambah Jabatan Sejajar"
                        >
                            <span class="material-symbols-outlined text-[12px]">group_add</span>
                            <span>Sejajar</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-0.5">
                        <!-- Edit -->
                        <button
                            type="button"
                            @click.stop="$emit('edit-position', node)"
                            class="p-1 rounded text-on-surface-variant hover:text-primary hover:bg-primary/10 transition-colors cursor-pointer"
                            title="Edit Jabatan"
                        >
                            <span class="material-symbols-outlined text-[15px]">edit</span>
                        </button>
                        <!-- Delete -->
                        <button
                            type="button"
                            @click.stop="$emit('delete-position', node)"
                            class="p-1 rounded text-on-surface-variant hover:text-error hover:bg-error/10 transition-colors cursor-pointer"
                            title="Hapus Jabatan"
                        >
                            <span class="material-symbols-outlined text-[15px]">delete</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tombol Buka / Tutup Cabang (Bawah Kotak) -->
            <button
                v-if="hasChildren"
                type="button"
                @click.stop="$emit('toggle-collapse', node.id)"
                class="absolute -bottom-3 left-1/2 -translate-x-1/2 z-10 h-6 min-w-6 px-1.5 rounded-full border shadow-xs flex items-center justify-center gap-0.5 text-[10px] font-bold transition-all cursor-pointer"
                :class="isCollapsed
                    ? 'bg-primary text-white border-primary hover:bg-primary-container shadow-sm ring-2 ring-primary/30'
                    : 'bg-surface-container-lowest text-on-surface-variant border-outline-variant hover:border-primary hover:text-primary hover:bg-primary/10'"
                :title="isCollapsed ? `Buka ${node.children.length} bawahan` : 'Tutup bawahan'"
            >
                <span class="material-symbols-outlined text-[13px]">
                    {{ isCollapsed ? 'add' : 'remove' }}
                </span>
                <span v-if="isCollapsed">{{ node.children.length }}</span>
            </button>
        </div>

        <!-- GARIS PENGHUBUNG & SUB-CABANG BAWAHAN (VERTICAL) -->
        <template v-if="hasChildren && !isCollapsed">
            <!-- Garis Vertikal Turun dari Kotak Atasan -->
            <div class="w-0.5 h-6 bg-slate-300 dark:bg-slate-600 shrink-0"></div>

            <!-- Kontainer Anak / Bawahan Sejajar -->
            <div class="flex items-start justify-center">
                <div
                    v-for="(child, idx) in node.children"
                    :key="child.id"
                    class="relative flex flex-col items-center px-3"
                >
                    <!-- Garis Horizontal Penghubung Antar Bawahan (Palang) -->
                    <div
                        v-if="node.children.length > 1 && idx > 0"
                        class="absolute top-0 left-0 right-1/2 h-0.5 bg-slate-300 dark:bg-slate-600"
                    ></div>
                    <div
                        v-if="node.children.length > 1 && idx < node.children.length - 1"
                        class="absolute top-0 left-1/2 right-0 h-0.5 bg-slate-300 dark:bg-slate-600"
                    ></div>

                    <!-- Garis Vertikal Masuk ke Kotak Bawahan -->
                    <div class="w-0.5 h-6 bg-slate-300 dark:bg-slate-600 shrink-0"></div>

                    <!-- Rekursi Komponen Kotak Bawahan -->
                    <OrgChartNode
                        :node="child"
                        :orientation="orientation"
                        :search-query="searchQuery"
                        :active-level-filter="activeLevelFilter"
                        :active-dept-filter="activeDeptFilter"
                        :collapsed-ids="collapsedIds"
                        :level-definitions="levelDefinitions"
                        @add-subordinate="$emit('add-subordinate', $event)"
                        @add-peer="$emit('add-peer', $event)"
                        @edit-position="$emit('edit-position', $event)"
                        @delete-position="$emit('delete-position', $event)"
                        @toggle-collapse="$emit('toggle-collapse', $event)"
                    />
                </div>
            </div>
        </template>
    </div>

    <!-- HORIZONTAL / LEFT-TO-RIGHT ORIENTATION -->
    <div v-else class="flex flex-row items-center">
        <!-- NODE BOX (KOTAK DIAGRAM) -->
        <div
            class="group relative w-[230px] rounded-xl bg-surface-container-lowest border-2 transition-all duration-200 select-none text-left shadow-sm shrink-0"
            :class="[
                levelDef.borderClass,
                isMatch ? 'ring-4 ring-primary shadow-xl scale-105 z-20 border-primary' : 'hover:shadow-md hover:-translate-y-0.5',
                isDimmed ? 'opacity-35 hover:opacity-100' : 'opacity-100',
            ]"
        >
            <!-- Header Strip -->
            <div
                class="px-3 py-1 rounded-t-[9px] flex items-center justify-between text-white text-[10px] font-bold tracking-wider"
                :class="levelDef.headerGradient"
            >
                <div class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[14px]">{{ levelDef.icon }}</span>
                    <span>LEVEL {{ node.level }}</span>
                </div>
                <span
                    v-if="hasChildren"
                    class="bg-black/25 px-1.5 py-0.2 rounded text-[9px] font-semibold"
                >
                    {{ node.children.length }} bawahan
                </span>
            </div>

            <!-- Body -->
            <div class="p-2.5">
                <h5
                    class="text-xs font-bold text-on-surface line-clamp-2 min-h-[32px] flex items-center leading-snug group-hover:text-primary transition-colors"
                    :title="node.name"
                >
                    {{ node.name }}
                </h5>

                <div class="flex items-center mt-1">
                    <span
                        v-if="node.department_name"
                        class="inline-flex items-center gap-1 text-[9px] font-semibold px-1.5 py-0.5 rounded bg-surface-container-high text-on-surface-variant truncate max-w-[200px]"
                    >
                        <span class="material-symbols-outlined text-[11px] opacity-70">business</span>
                        <span class="truncate">{{ node.department_name }}</span>
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center gap-1 text-[9px] font-semibold px-1.5 py-0.5 rounded bg-primary/10 text-primary border border-primary/20"
                    >
                        Yayasan
                    </span>
                </div>

                <!-- Aksi -->
                <div class="mt-2 pt-1.5 border-t border-outline-variant/30 flex items-center justify-between gap-1">
                    <div class="flex items-center gap-1">
                        <button
                            v-if="node.level < 5"
                            type="button"
                            @click.stop="$emit('add-subordinate', node)"
                            class="px-1.5 py-0.5 rounded bg-primary/10 hover:bg-primary hover:text-white text-primary text-[9px] font-bold transition-colors cursor-pointer"
                        >
                            +Bawahan
                        </button>
                        <button
                            type="button"
                            @click.stop="$emit('add-peer', node)"
                            class="px-1.5 py-0.5 rounded bg-surface-container-high hover:bg-surface-container-highest text-on-surface text-[9px] font-bold transition-colors cursor-pointer"
                        >
                            +Sejajar
                        </button>
                    </div>

                    <div class="flex items-center gap-0.5">
                        <button
                            type="button"
                            @click.stop="$emit('edit-position', node)"
                            class="p-0.5 rounded text-on-surface-variant hover:text-primary transition-colors cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-[14px]">edit</span>
                        </button>
                        <button
                            type="button"
                            @click.stop="$emit('delete-position', node)"
                            class="p-0.5 rounded text-on-surface-variant hover:text-error transition-colors cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-[14px]">delete</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tombol Buka / Tutup Cabang (Kanan Kotak) -->
            <button
                v-if="hasChildren"
                type="button"
                @click.stop="$emit('toggle-collapse', node.id)"
                class="absolute -right-3 top-1/2 -translate-y-1/2 z-10 w-6 h-6 rounded-full border shadow-xs flex items-center justify-center text-[10px] font-bold transition-all cursor-pointer"
                :class="isCollapsed
                    ? 'bg-primary text-white border-primary hover:bg-primary-container shadow-sm ring-2 ring-primary/30'
                    : 'bg-surface-container-lowest text-on-surface-variant border-outline-variant hover:border-primary hover:text-primary hover:bg-primary/10'"
                :title="isCollapsed ? `Buka ${node.children.length} bawahan` : 'Tutup bawahan'"
            >
                <span class="material-symbols-outlined text-[13px]">
                    {{ isCollapsed ? 'add' : 'remove' }}
                </span>
            </button>
        </div>

        <!-- GARIS PENGHUBUNG & SUB-CABANG BAWAHAN (HORIZONTAL) -->
        <template v-if="hasChildren && !isCollapsed">
            <!-- Garis Horizontal Keluar dari Kotak Atasan -->
            <div class="h-0.5 w-8 bg-slate-300 dark:bg-slate-600 shrink-0"></div>

            <!-- Kontainer Anak / Bawahan Vertikal -->
            <div class="flex flex-col items-start justify-center">
                <div
                    v-for="(child, idx) in node.children"
                    :key="child.id"
                    class="relative flex flex-row items-center py-2.5"
                >
                    <!-- Garis Vertikal Penghubung Antar Bawahan (Palang Vertikal) -->
                    <div
                        v-if="node.children.length > 1 && idx > 0"
                        class="absolute left-0 top-0 bottom-1/2 w-0.5 bg-slate-300 dark:bg-slate-600"
                    ></div>
                    <div
                        v-if="node.children.length > 1 && idx < node.children.length - 1"
                        class="absolute left-0 top-1/2 bottom-0 w-0.5 bg-slate-300 dark:bg-slate-600"
                    ></div>

                    <!-- Garis Horizontal Masuk ke Kotak Bawahan -->
                    <div class="h-0.5 w-8 bg-slate-300 dark:bg-slate-600 shrink-0"></div>

                    <!-- Rekursi Komponen Kotak Bawahan -->
                    <OrgChartNode
                        :node="child"
                        :orientation="orientation"
                        :search-query="searchQuery"
                        :active-level-filter="activeLevelFilter"
                        :active-dept-filter="activeDeptFilter"
                        :collapsed-ids="collapsedIds"
                        :level-definitions="levelDefinitions"
                        @add-subordinate="$emit('add-subordinate', $event)"
                        @add-peer="$emit('add-peer', $event)"
                        @edit-position="$emit('edit-position', $event)"
                        @delete-position="$emit('delete-position', $event)"
                        @toggle-collapse="$emit('toggle-collapse', $event)"
                    />
                </div>
            </div>
        </template>
    </div>
</template>
