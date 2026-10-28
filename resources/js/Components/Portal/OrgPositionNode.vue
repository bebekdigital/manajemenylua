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
                title="Titik hubung ke atasan langsung"
            ></div>

            <!-- TITIK ANCHOR KONEKTOR BAWAH (TITIK HUBUNG KE BAWAHAN) -->
            <div
                v-if="hasSubordinates"
                class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3 h-3 rounded-full bg-primary border-2 border-white shadow-xs z-20"
                title="Titik hubung ke bawahan langsung"
            ></div>

            <div>
                <!-- Header Kartu: Hanya Bidang Kerja Yayasan -->
                <div class="flex items-center justify-between gap-1.5 mb-2.5">
                    <span
                        v-if="pos.department_name"
                        class="text-[10px] font-semibold px-2 py-0.5 rounded bg-surface-container-high text-on-surface-variant border border-outline-variant/40 truncate max-w-[200px]"
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

                <!-- Nama Jabatan -->
                <h5 class="text-sm font-bold text-on-surface group-hover:text-primary transition-colors leading-snug">
                    {{ pos.name }}
                </h5>
            </div>

            <!-- Footer Action Buttons -->
            <div class="mt-4 pt-3 border-t border-outline-variant/40 flex items-center justify-between gap-1 flex-wrap">
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
        <div v-if="intraChildren.length > 0" class="flex flex-col items-center mt-7 w-full">
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
