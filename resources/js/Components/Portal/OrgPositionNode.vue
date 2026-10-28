<script setup>
import { computed } from 'vue';

defineOptions({
    name: 'OrgPositionNode',
});

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
    'add-subordinate-same-level',
    'add-subordinate-next-level',
    'add-peer',
    'edit',
    'delete',
]);

// Children of this position that belong to the SAME level
const intraChildren = computed(() => {
    return props.intraChildrenMap[props.pos.id] || [];
});

const hasSubordinates = computed(() => {
    return (props.pos.subordinates && props.pos.subordinates.length > 0) || intraChildren.value.length > 0;
});
</script>

<template>
    <div class="flex flex-col items-center">
        <!-- KARTU JABATAN -->
        <div
            :id="`pos-card-${pos.id}`"
            :data-level="pos.level"
            @mouseenter="$emit('hover', pos.id)"
            @mouseleave="$emit('leave')"
            class="w-64 sm:w-72 bg-white rounded-xl border border-outline-variant/60 p-4 transition-all duration-200 hover:shadow-lg flex flex-col justify-between group relative select-none"
            :class="[
                levelDef.cardBorder,
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
                v-if="hasSubordinates"
                class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3 h-3 rounded-full bg-primary border-2 border-white shadow-xs z-20"
                title="Titik hubung vertikal ke bawahan langsung"
            ></div>

            <div>
                <!-- Header Kartu: Badge Level & Bidang Kerja -->
                <div class="flex items-center justify-between gap-1.5 mb-2 flex-wrap">
                    <div class="flex items-center gap-1">
                        <span :class="['text-[9px] font-bold px-1.5 py-0.5 rounded border', levelDef.badgeClass]">
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
                        v-if="hasSubordinates"
                        class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-primary/10 text-primary border border-primary/20 flex items-center gap-1 shrink-0"
                        title="Memiliki bawahan langsung"
                    >
                        <span class="material-symbols-outlined text-[12px]">account_tree</span>
                        <span>{{ pos.subordinates?.length || intraChildren.length }}</span>
                    </span>
                </div>

                <!-- Nama Jabatan -->
                <h5 class="text-sm font-bold text-on-surface group-hover:text-primary transition-colors leading-snug">
                    {{ pos.name }}
                </h5>

                <!-- Atasan Langsung Info -->
                <div
                    v-if="pos.parent_name"
                    class="mt-2 flex items-center gap-1.5 text-[11px] text-on-surface-variant bg-surface-container-low px-2 py-1 rounded-lg border border-outline-variant/30"
                >
                    <span class="material-symbols-outlined text-[13px] text-primary shrink-0">arrow_upward</span>
                    <span class="truncate">
                        <span class="font-medium text-outline">Atasan:</span> 
                        <span class="font-bold text-on-surface ml-1">{{ pos.parent_name }}</span>
                    </span>
                    <span
                        v-if="pos.parent_level === pos.level"
                        class="ml-auto text-[9px] font-bold px-1.5 py-0.2 rounded bg-primary/15 text-primary border border-primary/20 shrink-0"
                    >
                        Level Sama
                    </span>
                    <span
                        v-else-if="pos.parent_level"
                        class="ml-auto text-[9px] font-semibold text-outline shrink-0"
                    >
                        L{{ pos.parent_level }}
                    </span>
                </div>

                <!-- Bawahan Langsung Info (Chips) -->
                <div v-if="pos.subordinates && pos.subordinates.length > 0" class="mt-2.5">
                    <p class="text-[10px] font-semibold text-outline uppercase tracking-wider mb-1 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[12px]">subdirectory_arrow_right</span>
                        Bawahan Langsung:
                    </p>
                    <div class="flex flex-wrap gap-1">
                        <span
                            v-for="(subName, sIdx) in pos.subordinates.slice(0, 3)"
                            :key="sIdx"
                            class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-surface-container-low text-on-surface-variant border border-outline-variant/30 truncate max-w-[180px]"
                        >
                            {{ subName }}
                        </span>
                        <span
                            v-if="pos.subordinates.length > 3"
                            class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-surface-container-high text-outline"
                        >
                            +{{ pos.subordinates.length - 3 }} lainnya
                        </span>
                    </div>
                </div>
            </div>

            <!-- Footer Action Buttons -->
            <div class="mt-3.5 pt-3 border-t border-outline-variant/40 flex items-center justify-between gap-1 flex-wrap">
                <div class="flex items-center gap-1 flex-wrap">
                    <!-- + Bawahan di Level yang Sama (Tampil di Bawah Kartu Ini) -->
                    <button
                        type="button"
                        @click="$emit('add-subordinate-same-level', pos)"
                        class="inline-flex items-center gap-0.5 px-2 py-1 rounded-md bg-primary/10 hover:bg-primary/20 text-primary text-[10px] font-bold transition-colors cursor-pointer"
                        :title="`Tambah bawahan di Level ${pos.level} (tampil di bawah kartu ini)`"
                    >
                        <span class="material-symbols-outlined text-[13px]">south</span>
                        <span>+ Bwh L{{ pos.level }}</span>
                    </button>

                    <!-- + Bawahan di Level Bawah (L+1) jika pos.level < 5 -->
                    <button
                        v-if="Number(pos.level) < 5"
                        type="button"
                        @click="$emit('add-subordinate-next-level', pos)"
                        class="inline-flex items-center gap-0.5 px-2 py-1 rounded-md bg-secondary-container/40 hover:bg-secondary-container/60 text-on-secondary-container text-[10px] font-bold transition-colors cursor-pointer"
                        :title="`Tambah bawahan di Level ${Number(pos.level) + 1} (baris level bawahnya)`"
                    >
                        <span class="material-symbols-outlined text-[13px]">arrow_downward</span>
                        <span>+ Bwh L{{ Number(pos.level) + 1 }}</span>
                    </button>

                    <!-- + Sejajar (Horizontal di Level yang Sama) -->
                    <button
                        type="button"
                        @click="$emit('add-peer', pos)"
                        class="inline-flex items-center gap-0.5 px-2 py-1 rounded-md bg-surface-container-high hover:bg-surface-container-highest text-on-surface text-[10px] font-bold transition-colors cursor-pointer"
                        title="Tambah rekan jabatan sejajar di level ini"
                    >
                        <span class="material-symbols-outlined text-[13px]">east</span>
                        <span>+ Sejajar</span>
                    </button>
                </div>

                <div class="flex items-center gap-0.5">
                    <!-- Edit -->
                    <button
                        type="button"
                        @click="$emit('edit', pos)"
                        class="p-1 rounded-md text-on-surface-variant hover:text-primary hover:bg-primary/10 transition-colors cursor-pointer"
                        title="Edit Jabatan"
                    >
                        <span class="material-symbols-outlined text-[15px]">edit</span>
                    </button>

                    <!-- Hapus -->
                    <button
                        type="button"
                        @click="$emit('delete', pos)"
                        class="p-1 rounded-md text-on-surface-variant hover:text-error hover:bg-error/10 transition-colors cursor-pointer"
                        title="Hapus Jabatan"
                    >
                        <span class="material-symbols-outlined text-[15px]">delete</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- BAWAHAN DI LEVEL YANG SAMA (TAMPIL DI BAWAH KARTU ATASANNYA) -->
        <div v-if="intraChildren.length > 0" class="flex flex-col items-center mt-5 w-full">
            <!-- Indikator Jalur Penurunan Hierarki Internal Level -->
            <div class="flex flex-col items-center mb-2">
                <div class="h-3 w-0.5 bg-primary/40"></div>
                <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant text-[9px] font-bold border border-outline-variant/40 shadow-2xs">
                    <span class="material-symbols-outlined text-[11px] text-primary">south</span>
                    <span>Bawahan Langsung Level {{ pos.level }}</span>
                </div>
                <div class="h-2 w-0.5 bg-primary/40"></div>
            </div>

            <!-- Baris Kartu Bawahan di Level yang Sama -->
            <div class="flex items-start gap-6 justify-center flex-wrap">
                <OrgPositionNode
                    v-for="child in intraChildren"
                    :key="child.id"
                    :pos="child"
                    :level-def="levelDef"
                    :intra-children-map="intraChildrenMap"
                    :hovered-pos-id="hoveredPosId"
                    @hover="$emit('hover', $event)"
                    @leave="$emit('leave')"
                    @add-subordinate-same-level="$emit('add-subordinate-same-level', $event)"
                    @add-subordinate-next-level="$emit('add-subordinate-next-level', $event)"
                    @add-peer="$emit('add-peer', $event)"
                    @edit="$emit('edit', $event)"
                    @delete="$emit('delete', $event)"
                />
            </div>
        </div>
    </div>
</template>
