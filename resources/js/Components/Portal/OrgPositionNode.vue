<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
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
    'add-subordinate-same-level',
    'add-subordinate-next-level',
    'add-peer',
    'edit',
    'delete',
]);

const showSubmenu = ref(false);
const bottomNodeRef = ref(null);

// Children of this position that belong to the SAME level
const intraChildren = computed(() => {
    return props.intraChildrenMap[props.pos.id] || [];
});

const hasSubordinates = computed(() => {
    return (props.pos.subordinates && props.pos.subordinates.length > 0) || intraChildren.value.length > 0;
});

function toggleSubmenu() {
    if (Number(props.pos.level) >= 5) {
        emit('add-subordinate-same-level', props.pos);
        return;
    }
    showSubmenu.value = !showSubmenu.value;
}

function handleAddSubSameLevel() {
    showSubmenu.value = false;
    emit('add-subordinate-same-level', props.pos);
}

function handleAddSubNextLevel() {
    showSubmenu.value = false;
    emit('add-subordinate-next-level', props.pos);
}

function closeSubmenu(e) {
    if (bottomNodeRef.value && !bottomNodeRef.value.contains(e.target)) {
        showSubmenu.value = false;
    }
}

onMounted(() => {
    window.addEventListener('click', closeSubmenu);
});

onUnmounted(() => {
    window.removeEventListener('click', closeSubmenu);
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
            <!-- TITIK ANCHOR KONEKTOR ATAS (TITIK HUBUNG DARI ATASAN) -->
            <div
                v-if="pos.parent_id"
                class="absolute -top-1.5 left-1/2 -translate-x-1/2 w-3 h-3 rounded-full border-2 border-white shadow-xs z-20 pointer-events-none"
                :class="levelDef.dotColor || 'bg-primary'"
                title="Titik hubung hierarki dari atasan"
            ></div>

            <!-- NODE + DI BAWAH KARTU (TAMBAH HIERARKI BAWAHAN) -->
            <div ref="bottomNodeRef" class="absolute -bottom-3 left-1/2 -translate-x-1/2 z-30">
                <button
                    type="button"
                    @click.stop="toggleSubmenu"
                    class="w-6 h-6 rounded-full border-2 border-white shadow-md flex items-center justify-center transition-all duration-150 hover:scale-125 cursor-pointer"
                    :class="levelDef.btnClass"
                    :title="Number(pos.level) < 5 ? `Tambah bawahan (di Level ${pos.level} atau Level ${Number(pos.level) + 1})` : `Tambah bawahan di Level ${pos.level}`"
                >
                    <span class="material-symbols-outlined text-[14px] font-bold leading-none select-none">add</span>
                </button>

                <!-- POPOVER PILIHAN BAWAHAN (LEVEL INI ATAS-BAWAH vs LEVEL BAWAH) -->
                <div
                    v-if="showSubmenu"
                    class="absolute top-full mt-1.5 left-1/2 -translate-x-1/2 bg-surface-container-lowest rounded-xl shadow-2xl border border-outline-variant/50 p-1.5 z-50 flex flex-col gap-1 min-w-[220px] whitespace-nowrap text-left"
                >
                    <button
                        type="button"
                        @click.stop="handleAddSubSameLevel"
                        class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-on-surface hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer text-left"
                    >
                        <span class="material-symbols-outlined text-[15px] text-primary">south</span>
                        <span>Bawahan di Level Ini (L{{ pos.level }})</span>
                    </button>
                    <button
                        v-if="Number(pos.level) < 5"
                        type="button"
                        @click.stop="handleAddSubNextLevel"
                        class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-on-surface hover:bg-secondary-container/40 hover:text-on-secondary-container transition-colors cursor-pointer text-left"
                    >
                        <span class="material-symbols-outlined text-[15px] text-secondary">arrow_downward</span>
                        <span>Bawahan di Level Bawah (L{{ Number(pos.level) + 1 }})</span>
                    </button>
                </div>
            </div>

            <!-- NODE + DI SAMPING KANAN KARTU (TAMBAH HIERARKI SEJAJAR) -->
            <button
                type="button"
                @click.stop="$emit('add-peer', pos)"
                class="absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full border-2 border-white shadow-md flex items-center justify-center transition-all duration-150 hover:scale-125 z-30 cursor-pointer"
                :class="levelDef.btnClass"
                :title="`Tambah jabatan sejajar di Level ${pos.level}`"
            >
                <span class="material-symbols-outlined text-[14px] font-bold leading-none select-none">add</span>
            </button>

            <div>
                <!-- Header Kartu: Badge Bidang Kerja / Yayasan -->
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

            <!-- Footer Action Buttons: Hanya Edit & Hapus Ringkas di Pojok Kanan Bawah -->
            <div class="mt-3 pt-2 border-t border-outline-variant/30 flex items-center justify-end gap-1">
                <!-- Edit -->
                <button
                    type="button"
                    @click.stop="$emit('edit', pos)"
                    class="p-1 rounded-md text-on-surface-variant hover:text-primary hover:bg-primary/10 transition-colors cursor-pointer"
                    title="Edit Jabatan"
                >
                    <span class="material-symbols-outlined text-[15px]">edit</span>
                </button>

                <!-- Hapus -->
                <button
                    type="button"
                    @click.stop="$emit('delete', pos)"
                    class="p-1 rounded-md text-on-surface-variant hover:text-error hover:bg-error/10 transition-colors cursor-pointer"
                    title="Hapus Jabatan"
                >
                    <span class="material-symbols-outlined text-[15px]">delete</span>
                </button>
            </div>
        </div>

        <!-- BAWAHAN DI LEVEL YANG SAMA (TAMPIL DI BAWAH KARTU ATASANNYA DI CONTAINER INI) -->
        <div v-if="intraChildren.length > 0" class="flex flex-col items-center mt-7 w-full">
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
