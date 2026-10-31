<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: 'aktif',
    },
    statuses: {
        type: Array,
        default: () => [
            { value: 'aktif', label: 'Aktif', colorClass: 'text-primary bg-primary/10 border-primary/20 hover:bg-primary/20', dotClass: 'bg-primary' },
            { value: 'tidak_aktif', label: 'Tidak Aktif', colorClass: 'text-error bg-error/10 border-error/20 hover:bg-error/20', dotClass: 'bg-error' },
            { value: 'semua', label: 'Semua Status', colorClass: 'text-slate-700 bg-slate-100 border-slate-300 hover:bg-slate-200', dotClass: 'bg-slate-500' },
        ],
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const dropdownRef = ref(null);

const currentStatusObj = computed(() => {
    return props.statuses.find(s => s.value === props.modelValue) || props.statuses[0];
});

function toggleDropdown(e) {
    e.stopPropagation();
    isOpen.value = !isOpen.value;
}

function selectStatus(val) {
    emit('update:modelValue', val);
    emit('change', val);
    isOpen.value = false;
}

function handleClickOutside(event) {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="dropdownRef" class="relative w-[calc(50%-4px)] sm:w-auto sm:min-w-[130px] flex-grow sm:flex-grow-0 shrink-0">
        <button
            type="button"
            @click="toggleDropdown"
            :class="[
                'flex items-center justify-between w-full px-3 py-2 sm:py-2 rounded-lg sm:rounded-xl text-[12px] sm:text-sm font-semibold border transition-all cursor-pointer',
                currentStatusObj.colorClass
            ]"
        >
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 sm:w-2.5 sm:h-2.5 rounded-full" :class="currentStatusObj.dotClass"></span>
                <span>{{ currentStatusObj.label }}</span>
            </div>
            <span class="material-symbols-outlined text-[16px] sm:text-[18px] transition-transform duration-200" :class="{ 'rotate-180': isOpen }">
                expand_more
            </span>
        </button>

        <div v-show="isOpen" class="absolute z-[100] top-full left-0 mt-1 w-full min-w-[140px] bg-white border border-slate-200 rounded-xl shadow-lg py-1 overflow-hidden animate-in fade-in slide-in-from-top-1 duration-150">
            <button
                v-for="s in statuses"
                :key="s.value"
                type="button"
                @click="selectStatus(s.value)"
                class="flex items-center gap-2 w-full px-3 py-2 text-[12px] sm:text-sm text-left hover:bg-slate-50 transition-colors font-medium text-slate-700 cursor-pointer"
                :class="{ 'bg-slate-50 font-bold': s.value === modelValue }"
            >
                <span class="w-2 h-2 sm:w-2.5 sm:h-2.5 rounded-full" :class="s.dotClass"></span>
                <span>{{ s.label }}</span>
            </button>
        </div>
    </div>
</template>
