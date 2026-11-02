<script setup>
defineProps({
    label: {
        type: String,
        default: 'Edit Data',
    },
    icon: {
        type: String,
        default: 'edit',
    },
    isDropdown: {
        type: Boolean,
        default: false,
    },
    isOpen: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Edit Data',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['click']);
</script>

<template>
    <button
        type="button"
        :disabled="disabled"
        :title="title"
        @click="$emit('click', $event)"
        class="inline-flex items-center justify-between gap-2 px-3 py-1.5 sm:px-4 sm:py-2.5 rounded-lg sm:rounded-xl border border-amber-500 text-amber-700 bg-amber-50 hover:bg-amber-100 active:bg-amber-200 font-semibold text-[11px] sm:text-sm transition-all shadow-2xs hover:shadow-sm cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
    >
        <div class="flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px] sm:text-[18px]">{{ icon }}</span>
            <span>{{ label }}</span>
        </div>
        <span
            v-if="isDropdown"
            class="material-symbols-outlined text-[16px] sm:text-[18px] transition-transform duration-200"
            :class="{ 'rotate-180': isOpen }"
        >
            expand_more
        </span>
    </button>
</template>
