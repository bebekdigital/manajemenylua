<script setup>
/**
 * SearchFilter - Reusable search + filter bar component.
 *
 * Props:
 *   modelValue (string)  — search query (v-model)
 *   filters (Array)      — list of filter groups:
 *       [{ key, label, options: [{ value, label }] }]
 *   activeFilters (Object) — current active filter values { key: value }
 *   placeholder (string) — search input placeholder
 *   resultCount (number) — optional result count to display
 *
 * Emits:
 *   update:modelValue — search query changed
 *   update:activeFilters — filter changed
 *   clear — clear all
 */

import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    filters: { type: Array, default: () => [] },
    activeFilters: { type: Object, default: () => ({}) },
    placeholder: { type: String, default: 'Cari...' },
    resultCount: { type: Number, default: null },
});

const emit = defineEmits(['update:modelValue', 'update:activeFilters', 'clear']);

const hasActiveFilters = computed(() => {
    if (props.modelValue) return true;
    return Object.values(props.activeFilters).some(v => v !== '' && v != null);
});

function onSearch(e) {
    emit('update:modelValue', e.target.value);
}

function onFilter(key, value) {
    emit('update:activeFilters', { ...props.activeFilters, [key]: value });
}

function clearAll() {
    emit('update:modelValue', '');
    const cleared = {};
    props.filters.forEach(f => { cleared[f.key] = ''; });
    emit('update:activeFilters', cleared);
    emit('clear');
}
</script>

<template>
    <div class="flex flex-col sm:flex-row gap-3">
        <!-- Search -->
        <div class="relative flex-1">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-on-surface-variant/50 text-[18px] pointer-events-none">
                search
            </span>
            <input
                :value="modelValue"
                @input="onSearch"
                :placeholder="placeholder"
                class="w-full pl-9 pr-4 py-2 text-sm bg-surface-container-low border border-outline-variant/40 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all placeholder:text-on-surface-variant/40"
            />
            <button
                v-if="modelValue"
                @click="$emit('update:modelValue', '')"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 hover:text-on-surface transition-colors cursor-pointer"
            >
                <span class="material-symbols-outlined text-[16px]">close</span>
            </button>
        </div>

        <!-- Filter Dropdowns -->
        <div class="flex gap-2 flex-wrap">
            <select
                v-for="filter in filters"
                :key="filter.key"
                :value="activeFilters[filter.key] ?? ''"
                @change="onFilter(filter.key, $event.target.value)"
                class="text-sm py-2 pl-3 pr-8 bg-surface-container-low border border-outline-variant/40 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all text-on-surface cursor-pointer appearance-none"
                :class="(activeFilters[filter.key]) ? 'border-primary text-primary font-semibold' : ''"
            >
                <option value="">{{ filter.label }}</option>
                <option
                    v-for="opt in filter.options"
                    :key="opt.value"
                    :value="opt.value"
                >{{ opt.label }}</option>
            </select>

            <!-- Clear all -->
            <button
                v-if="hasActiveFilters"
                @click="clearAll"
                class="flex items-center gap-1 px-3 py-2 text-xs font-semibold text-error bg-error/5 border border-error/20 rounded-xl hover:bg-error/10 transition-colors cursor-pointer whitespace-nowrap"
            >
                <span class="material-symbols-outlined text-[14px]">filter_alt_off</span>
                Reset
            </button>
        </div>

        <!-- Result count badge -->
        <div
            v-if="resultCount !== null"
            class="hidden sm:flex items-center shrink-0 text-xs font-medium text-on-surface-variant bg-surface-container-low border border-outline-variant/30 px-3 py-2 rounded-xl whitespace-nowrap"
        >
            {{ resultCount.toLocaleString('id') }} hasil
        </div>
    </div>
</template>
