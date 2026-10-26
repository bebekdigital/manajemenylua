<script setup>
import { onMounted, onUnmounted } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Berhasil!',
    },
    message: {
        type: String,
        default: '',
    },
    buttonText: {
        type: String,
        default: 'Selesai',
    },
});

const emit = defineEmits(['close']);

function handleKeydown(e) {
    if (e.key === 'Escape' && props.show) {
        emit('close');
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
                @click.self="$emit('close')"
            >
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-2"
                >
                    <div
                        v-if="show"
                        class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-6 text-center border border-outline-variant/30 transform relative overflow-hidden"
                    >
                        <!-- Top decorative accent -->
                        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-emerald-400 via-primary to-emerald-500"></div>

                        <!-- Icon Container -->
                        <div class="mx-auto w-16 h-16 rounded-full bg-emerald-50 border-2 border-emerald-100 flex items-center justify-center mb-4 mt-1 shadow-inner">
                            <span class="material-symbols-outlined text-3xl text-emerald-600">check_circle</span>
                        </div>

                        <!-- Content -->
                        <h3 class="text-lg font-bold text-on-surface mb-2">
                            {{ title }}
                        </h3>
                        <p class="text-sm text-on-surface-variant mb-6 leading-relaxed">
                            {{ message || 'Data telah berhasil disimpan.' }}
                        </p>

                        <!-- Action Button -->
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="w-full py-2.5 px-4 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-sm transition-all shadow-xs hover:shadow-md cursor-pointer flex items-center justify-center gap-2"
                        >
                            <span>{{ buttonText }}</span>
                        </button>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
