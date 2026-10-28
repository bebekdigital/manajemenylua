<script setup>
defineProps({
    pos: {
        type: Object,
        required: true,
    },
    levelDef: {
        type: Object,
        required: true,
    },
    hoveredPosId: {
        type: [Number, String, null],
        default: null,
    },
});

defineEmits([
    'hover',
    'leave',
    'add-subordinate-same-level',
    'add-subordinate-next-level',
    'add-peer',
    'edit',
    'delete',
]);
</script>

<template>
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
        <button
            type="button"
            @click.stop="Number(pos.level) < 5 ? $emit('add-subordinate-next-level', pos) : $emit('add-subordinate-same-level', pos)"
            class="absolute -bottom-3 left-1/2 -translate-x-1/2 w-6 h-6 rounded-full border-2 border-white shadow-md flex items-center justify-center transition-all duration-150 hover:scale-125 z-30 cursor-pointer"
            :class="levelDef.btnClass"
            :title="Number(pos.level) < 5 ? `Tambah bawahan di Level ${Number(pos.level) + 1} untuk ${pos.name}` : `Tambah bawahan untuk ${pos.name}`"
        >
            <span class="material-symbols-outlined text-[14px] font-bold leading-none select-none">add</span>
        </button>

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
</template>
