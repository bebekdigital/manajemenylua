<script setup>
defineProps({
    label: {
        type: String,
        default: 'Edit Data',
    },
    icon: {
        type: String,
        default: 'edit_note',
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
    variant: {
        type: String,
        default: 'solid-amber',
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
        :class="[
            'inline-flex items-center justify-between gap-2 px-4 py-2 rounded-xl font-semibold text-[13px] transition-all shadow-xs cursor-pointer active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed',
            variant === 'solid-amber'
                ? 'bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white'
                : 'border border-amber-500 text-amber-700 bg-amber-50 hover:bg-amber-100 active:bg-amber-200'
        ]"
    >
        <div class="flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[18px]">{{ icon }}</span>
            <span>{{ label }}</span>
        </div>
        <span
            v-if="isDropdown"
            class="material-symbols-outlined text-[16px] transition-transform duration-200"
            :class="{ 'rotate-180': isOpen }"
        >
            expand_more
        </span>
    </button>
</template>
