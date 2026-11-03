<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    options: {
        type: Array,
        required: true,
    },
    icon: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Pilih...',
    },
    placement: {
        type: String,
        default: 'right', // 'right' or 'left'
    },
    size: {
        type: String,
        default: 'md', // 'sm' or 'md'
    },
    buttonClass: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const selectRef = ref(null);

const selectedOption = computed(() => {
    return props.options.find(opt => String(opt.value) === String(props.modelValue));
});

const currentLabel = computed(() => {
    return selectedOption.value?.label ?? props.placeholder;
});

function toggleDropdown(e) {
    e?.stopPropagation();
    isOpen.value = !isOpen.value;
}

function selectOption(opt) {
    emit('update:modelValue', opt.value);
    emit('change', opt.value);
    isOpen.value = false;
}

function handleClickOutside(event) {
    if (selectRef.value && !selectRef.value.contains(event.target)) {
        isOpen.value = false;
    }
}

function handleKeydown(event) {
    if (event.key === 'Escape' && isOpen.value) {
        isOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <div ref="selectRef" class="relative inline-block text-left" :title="title">
        <!-- Trigger Button -->
        <button
            type="button"
            @click="toggleDropdown"
            :class="[
                'inline-flex items-center justify-between gap-2 bg-white hover:bg-slate-50 border transition-all cursor-pointer select-none',
                size === 'sm'
                    ? 'px-2 py-1 rounded-lg text-[11px] font-medium'
                    : 'px-3 py-2 rounded-xl text-[12.5px] sm:text-[13px] font-medium shadow-2xs',
                isOpen
                    ? 'border-emerald-600 ring-2 ring-emerald-500/20 text-slate-900'
                    : 'border-slate-300 text-slate-700',
                buttonClass
            ]"
        >
            <div class="inline-flex items-center gap-1.5 truncate">
                <span
                    v-if="icon"
                    class="material-symbols-outlined text-slate-500 shrink-0"
                    :class="size === 'sm' ? 'text-[14px]' : 'text-[16px]'"
                >
                    {{ icon }}
                </span>
                <span class="truncate">{{ currentLabel }}</span>
            </div>
            <span
                class="material-symbols-outlined text-slate-400 transition-transform duration-200 shrink-0"
                :class="[
                    size === 'sm' ? 'text-[13px]' : 'text-[16px]',
                    isOpen ? 'rotate-180 text-emerald-600' : ''
                ]"
            >
                expand_more
            </span>
        </button>

        <!-- Custom Dropdown Menu (Sederhana, bersih, tanpa ornamen berlebih) -->
        <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0 -translate-y-1"
            enter-to-class="transform scale-100 opacity-100 translate-y-0"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100 translate-y-0"
            leave-to-class="transform scale-95 opacity-0 -translate-y-1"
        >
            <div
                v-if="isOpen"
                :class="[
                    'absolute mt-1.5 z-50 bg-white border border-slate-200 rounded-xl shadow-lg py-1 max-h-64 overflow-y-auto text-left w-max max-w-[280px] sm:max-w-xs',
                    placement === 'right' ? 'right-0' : 'left-0'
                ]"
                style="min-width: 100%;"
            >
                <button
                    v-for="opt in options"
                    :key="opt.value"
                    type="button"
                    @click="selectOption(opt)"
                    class="w-full flex items-center justify-between gap-3 px-3.5 py-2 text-left transition-colors cursor-pointer"
                    :class="[
                        size === 'sm' ? 'text-[11.5px] py-1.5' : 'text-[12.5px] sm:text-[13px]',
                        String(opt.value) === String(modelValue)
                            ? 'bg-emerald-50 text-emerald-950 font-semibold'
                            : 'text-slate-700 hover:bg-slate-50 font-medium'
                    ]"
                >
                    <div class="flex items-center gap-2 truncate">
                        <span
                            v-if="opt.icon"
                            class="material-symbols-outlined text-[16px] text-slate-400 shrink-0"
                        >
                            {{ opt.icon }}
                        </span>
                        <span class="truncate">{{ opt.label }}</span>
                    </div>
                    <span
                        v-if="String(opt.value) === String(modelValue)"
                        class="material-symbols-outlined text-[16px] text-emerald-700 shrink-0"
                    >
                        check
                    </span>
                </button>
            </div>
        </Transition>
    </div>
</template>
