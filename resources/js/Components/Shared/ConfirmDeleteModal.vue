<script setup>
import { onMounted, onUnmounted } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Konfirmasi Hapus',
    },
    message: {
        type: String,
        default: 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.',
    },
    itemName: {
        type: String,
        default: '',
    },
    confirmText: {
        type: String,
        default: 'Ya, Hapus',
    },
    cancelText: {
        type: String,
        default: 'Batal',
    },
    isDeleting: {
        type: Boolean,
        default: false,
    },
    hideConfirmButton: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'confirm']);

function handleKeydown(e) {
    if (e.key === 'Escape' && props.show && !props.isDeleting) {
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
                @click.self="!isDeleting && $emit('close')"
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
                        <!-- Top Accent Line -->
                        <div
                            class="absolute top-0 left-0 right-0 h-1.5"
                            :class="hideConfirmButton ? 'bg-amber-500' : 'bg-gradient-to-r from-red-500 via-rose-500 to-red-600'"
                        ></div>

                        <!-- Icon Container -->
                        <div
                            class="mx-auto w-16 h-16 rounded-full flex items-center justify-center mb-4 mt-1 shadow-inner"
                            :class="hideConfirmButton ? 'bg-amber-50 border-2 border-amber-100' : 'bg-red-50 border-2 border-red-100'"
                        >
                            <span
                                v-if="hideConfirmButton"
                                class="material-symbols-outlined text-3xl text-amber-600"
                            >warning</span>
                            <span
                                v-else
                                class="material-symbols-outlined text-3xl text-red-600"
                            >delete_forever</span>
                        </div>

                        <!-- Content -->
                        <h3 class="text-lg font-bold text-on-surface mb-2">
                            {{ title }}
                        </h3>
                        <p class="text-sm text-on-surface-variant mb-3 leading-relaxed">
                            {{ message }}
                        </p>

                        <!-- Highlighted Item Badge (if provided) -->
                        <div
                            v-if="itemName"
                            class="p-2.5 rounded-xl bg-surface-container-high border border-outline-variant/50 text-xs font-semibold text-on-surface mb-6 truncate"
                        >
                            {{ itemName }}
                        </div>
                        <div v-else class="mb-3"></div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="$emit('close')"
                                :disabled="isDeleting"
                                class="flex-1 py-2.5 px-4 rounded-xl border border-outline-variant/70 hover:bg-surface-container text-on-surface font-semibold text-sm transition-all cursor-pointer disabled:opacity-50"
                            >
                                {{ hideConfirmButton ? 'Mengerti' : cancelText }}
                            </button>

                            <button
                                v-if="!hideConfirmButton"
                                type="button"
                                @click="$emit('confirm')"
                                :disabled="isDeleting"
                                class="flex-1 py-2.5 px-4 rounded-xl bg-error hover:bg-error/90 text-on-error font-semibold text-sm transition-all shadow-xs hover:shadow-md cursor-pointer flex items-center justify-center gap-1.5 disabled:opacity-50"
                            >
                                <span v-if="isDeleting" class="material-symbols-outlined text-base animate-spin">progress_activity</span>
                                <span>{{ isDeleting ? 'Menghapus...' : confirmText }}</span>
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
