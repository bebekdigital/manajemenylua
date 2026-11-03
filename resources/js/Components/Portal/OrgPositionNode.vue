<script setup>
import { computed } from 'vue';
import OrgPositionNode from './OrgPositionNode.vue';

const props = defineProps({
    pos: {
        type: Object,
        required: true,
    },
    levelDef: {
        type: Object,
        required: true,
    },
    intraChildrenMap: {
        type: Object,
        default: () => ({}),
    },
    hoveredPosId: {
        type: [Number, String, null],
        default: null,
    },
});

const emit = defineEmits([
    'hover',
    'leave',
    'add-subordinate',
    'add-peer',
    'edit',
    'delete',
]);

// Children of this position that belong to the SAME level
const intraChildren = computed(() => {
    return props.intraChildrenMap[props.pos.id] || [];
});

const hasCenterTrunkLine = computed(() => {
    return !!props.pos.has_lower_children;
});

const intraLeftChildren = computed(() => {
    const list = intraChildren.value;
    if (list.length === 0) return [];
    if (list.length === 1) return [];
    const splitIndex = Math.floor(list.length / 2);
    return list.slice(0, splitIndex);
});

const intraRightChildren = computed(() => {
    const list = intraChildren.value;
    if (list.length === 0) return [];
    if (list.length === 1) return list;
    const splitIndex = Math.floor(list.length / 2);
    return list.slice(splitIndex);
});

const hasSubordinates = computed(() => {
    return (props.pos.subordinates && props.pos.subordinates.length > 0) || intraChildren.value.length > 0;
});
</script>

<template>
    <div class="flex flex-col items-center shrink-0">
        <!-- KARTU JABATAN: COMPACT & RESPONSIVE UNTUK LAPTOP DAN MOBILE -->
        <div
            :id="`pos-card-${pos.id}`"
            :data-level="pos.level"
            @mouseenter="$emit('hover', pos.id)"
            @mouseleave="$emit('leave')"
            class="w-auto min-w-[96px] sm:min-w-[112px] max-w-[140px] sm:max-w-[165px] md:max-w-[180px] bg-linear-to-b from-white to-slate-50/80 rounded-lg sm:rounded-xl border sm:border-2 border-slate-300 px-2 py-1.5 sm:px-2.5 sm:py-2 transition-all duration-200 flex flex-col justify-center group relative select-none shadow-[inset_0_1px_2px_rgba(0,0,0,0.04),0_1px_4px_rgba(0,0,0,0.06)] hover:shadow-[0_4px_12px_rgba(0,0,0,0.1)]"
            :class="[
                levelDef.cardBorder,
                hoveredPosId === pos.id ? 'ring-2 ring-primary border-primary' : ''
            ]"
        >
            <!-- TITIK ANCHOR KONEKTOR ATAS (TITIK HUBUNG DARI ATASAN) -->
            <div
                v-if="pos.parent_id"
                class="absolute -top-1.5 left-1/2 -translate-x-1/2 w-2 h-2 rounded-full border border-white shadow-2xs z-20 pointer-events-none"
                :class="levelDef.dotColor || 'bg-primary'"
                title="Titik hubung hierarki dari atasan"
            ></div>

            <!-- NODE + DI BAWAH KARTU (TAMBAH HIERARKI BAWAHAN: LANGSUNG KE MODAL) -->
            <button
                type="button"
                @click.stop="$emit('add-subordinate', pos)"
                class="absolute -bottom-2 left-1/2 -translate-x-1/2 w-4 h-4 sm:w-4.5 sm:h-4.5 rounded-full border border-white shadow-xs flex items-center justify-center transition-all duration-150 hover:scale-125 z-30 cursor-pointer"
                :class="levelDef.btnClass"
                title="Tambah bawahan untuk jabatan ini"
            >
                <span class="material-symbols-outlined text-[10px] sm:text-[11px] font-bold leading-none select-none">add</span>
            </button>

            <!-- NODE + DI SAMPING KANAN KARTU (TAMBAH HIERARKI SEJAJAR) -->
            <button
                type="button"
                @click.stop="$emit('add-peer', pos)"
                class="absolute -right-2 top-1/2 -translate-y-1/2 w-4 h-4 sm:w-4.5 sm:h-4.5 rounded-full border border-white shadow-xs flex items-center justify-center transition-all duration-150 hover:scale-125 z-30 cursor-pointer"
                :class="levelDef.btnClass"
                :title="`Tambah jabatan sejajar di Level ${pos.level}`"
            >
                <span class="material-symbols-outlined text-[10px] sm:text-[11px] font-bold leading-none select-none">add</span>
            </button>

            <!-- KONTEN KARTU: HEADER (BADGE BIDANG & TOMBOL EDIT/HAPUS DI POJOK KANAN ATAS) -->
            <div class="flex items-center justify-between gap-1 mb-1">
                <!-- Badge Bidang Kerja / Yayasan -->
                <span
                    v-if="pos.department_name"
                    class="text-[8px] sm:text-[9px] font-semibold px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200 truncate max-w-[78px] sm:max-w-[98px]"
                    :title="pos.department_name"
                >
                    {{ pos.department_name }}
                </span>
                <span
                    v-else
                    class="text-[8px] sm:text-[9px] font-semibold px-1.5 py-0.5 rounded bg-primary/10 text-primary border border-primary/20 shrink-0"
                >
                    Yayasan
                </span>

                <!-- Tombol Edit & Hapus di Pojok Kanan Atas Card -->
                <div class="flex items-center gap-0.5 shrink-0 ml-auto">
                    <button
                        type="button"
                        @click.stop="$emit('edit', pos)"
                        class="w-4 h-4 sm:w-4.5 sm:h-4.5 rounded flex items-center justify-center text-slate-500 hover:text-primary hover:bg-primary/10 transition-colors cursor-pointer"
                        title="Edit Jabatan"
                    >
                        <span class="material-symbols-outlined text-[11px] sm:text-[12.5px]">edit</span>
                    </button>
                    <button
                        type="button"
                        @click.stop="$emit('delete', pos)"
                        class="w-4 h-4 sm:w-4.5 sm:h-4.5 rounded flex items-center justify-center text-slate-500 hover:text-error hover:bg-error/10 transition-colors cursor-pointer"
                        title="Hapus Jabatan"
                    >
                        <span class="material-symbols-outlined text-[11px] sm:text-[12.5px]">delete</span>
                    </button>
                </div>
            </div>

            <!-- Nama Jabatan -->
            <h5 class="text-[10.5px] sm:text-xs font-bold text-slate-800 group-hover:text-primary transition-colors leading-tight break-words text-left">
                {{ pos.name }}
            </h5>
        </div>

        <!-- BAWAHAN DI LEVEL YANG SAMA (TAMPIL DI BAWAH KARTU ATASANNYA DI CONTAINER INI) -->
        <div v-if="intraChildren.length > 0" class="flex flex-col items-center mt-3.5 sm:mt-4 w-max">
            <!-- Jika memiliki garis komando lurus ke level bawah, bagi ke sayap kiri dan sayap kanan dengan koridor tengah terbuka -->
            <div v-if="hasCenterTrunkLine" class="grid grid-cols-2 w-max mx-auto">
                <!-- SAYAP KIRI -->
                <div class="flex items-start gap-2.5 sm:gap-3.5 justify-end flex-nowrap shrink-0 pr-3.5 sm:pr-5">
                    <OrgPositionNode
                        v-for="child in intraLeftChildren"
                        :key="child.id"
                        :pos="child"
                        class="shrink-0"
                        :level-def="levelDef"
                        :intra-children-map="intraChildrenMap"
                        :hovered-pos-id="hoveredPosId"
                        @hover="$emit('hover', $event)"
                        @leave="$emit('leave')"
                        @add-subordinate="$emit('add-subordinate', $event)"
                        @add-peer="$emit('add-peer', $event)"
                        @edit="$emit('edit', $event)"
                        @delete="$emit('delete', $event)"
                    />
                </div>

                <!-- SAYAP KANAN -->
                <div class="flex items-start gap-2.5 sm:gap-3.5 justify-start flex-nowrap shrink-0 pl-3.5 sm:pl-5">
                    <OrgPositionNode
                        v-for="child in intraRightChildren"
                        :key="child.id"
                        :pos="child"
                        class="shrink-0"
                        :level-def="levelDef"
                        :intra-children-map="intraChildrenMap"
                        :hovered-pos-id="hoveredPosId"
                        @hover="$emit('hover', $event)"
                        @leave="$emit('leave')"
                        @add-subordinate="$emit('add-subordinate', $event)"
                        @add-peer="$emit('add-peer', $event)"
                        @edit="$emit('edit', $event)"
                        @delete="$emit('delete', $event)"
                    />
                </div>
            </div>

            <!-- Jika tidak ada garis komando ke level bawah, tampilkan sejajar terpusat tanpa wrap vertikal -->
            <div v-else class="flex items-start gap-2.5 sm:gap-3.5 justify-center flex-nowrap">
                <OrgPositionNode
                    v-for="child in intraChildren"
                    :key="child.id"
                    :pos="child"
                    :level-def="levelDef"
                    :intra-children-map="intraChildrenMap"
                    :hovered-pos-id="hoveredPosId"
                    @hover="$emit('hover', $event)"
                    @leave="$emit('leave')"
                    @add-subordinate="$emit('add-subordinate', $event)"
                    @add-peer="$emit('add-peer', $event)"
                    @edit="$emit('edit', $event)"
                    @delete="$emit('delete', $event)"
                />
            </div>
        </div>
    </div>
</template>
