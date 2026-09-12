<script setup>
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    editing: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'success']);

const form = ref({
    name: '',
    semester: 'Ganjil',
    start_date: '',
    end_date: '',
});

const isSubmitting = ref(false);
const errors = ref({});

const isEditing = computed(() => !!props.editing);
const modalTitle = computed(() => isEditing.value ? 'Edit Tahun Ajaran' : 'Tambah Tahun Ajaran Baru');

watch(() => props.show, (newVal) => {
    if (newVal) {
        errors.value = {};
        if (props.editing) {
            form.value = {
                name: props.editing.name || '',
                semester: props.editing.semester || 'Ganjil',
                start_date: props.editing.start_date || '',
                end_date: props.editing.end_date || '',
            };
        } else {
            form.value = {
                name: '',
                semester: 'Ganjil',
                start_date: '',
                end_date: '',
            };
        }
    }
});

function submit() {
    errors.value = {};

    if (!form.value.name.trim()) {
        errors.value.name = 'Nama Tahun Ajaran wajib diisi.';
        return;
    }

    isSubmitting.value = true;

    const payload = { ...form.value };

    if (isEditing.value) {
        router.put(`/portal/academic-years/${props.editing.id}`, payload, {
            preserveScroll: true,
            onSuccess: () => {
                emit('success');
            },
            onError: (errs) => {
                errors.value = errs;
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        });
    } else {
        router.post('/portal/academic-years', payload, {
            preserveScroll: true,
            onSuccess: () => {
                emit('success');
            },
            onError: (errs) => {
                errors.value = errs;
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        });
    }
}
</script>

<template>
    <!-- Backdrop -->
    <Teleport to="body">
        <Transition name="modal-backdrop">
            <div
                v-if="show"
                class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
                @click.self="$emit('close')"
            >
                <Transition name="modal-content">
                    <div
                        v-if="show"
                        class="bg-surface-container-lowest rounded-2xl shadow-xl w-full max-w-lg border border-outline-variant/30 overflow-hidden"
                    >
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/30">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-primary text-lg">
                                        {{ isEditing ? 'edit_calendar' : 'calendar_add_on' }}
                                    </span>
                                </div>
                                <h3 class="text-lg font-bold text-on-surface">{{ modalTitle }}</h3>
                            </div>
                            <button
                                type="button"
                                @click="$emit('close')"
                                class="p-2 rounded-full text-on-surface-variant hover:bg-surface-variant hover:text-on-surface transition-colors cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-xl">close</span>
                            </button>
                        </div>

                        <!-- Form Body -->
                        <form @submit.prevent="submit" class="px-6 py-5 space-y-5">
                            <!-- Name -->
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                    Nama Tahun Ajaran <span class="text-error">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="contoh: 2025/2026"
                                    class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                    :class="{ 'border-error focus:border-error focus:ring-error/20': errors.name }"
                                />
                                <p v-if="errors.name" class="text-xs text-error mt-1">{{ errors.name }}</p>
                            </div>

                            <!-- Semester -->
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                    Semester <span class="text-error">*</span>
                                </label>
                                <div class="flex gap-3">
                                    <label
                                        v-for="sem in ['Ganjil', 'Genap']"
                                        :key="sem"
                                        class="flex-1 relative cursor-pointer"
                                    >
                                        <input
                                            type="radio"
                                            :value="sem"
                                            v-model="form.semester"
                                            class="peer sr-only"
                                        />
                                        <div
                                            class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border text-sm font-semibold transition-all peer-checked:bg-primary peer-checked:text-on-primary peer-checked:border-primary peer-checked:shadow-xs border-outline-variant/60 text-on-surface-variant hover:bg-surface-container-high"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">
                                                {{ sem === 'Ganjil' ? 'looks_one' : 'looks_two' }}
                                            </span>
                                            {{ sem }}
                                        </div>
                                    </label>
                                </div>
                                <p v-if="errors.semester" class="text-xs text-error mt-1">{{ errors.semester }}</p>
                            </div>

                            <!-- Date Range -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                        Tanggal Mulai
                                    </label>
                                    <input
                                        v-model="form.start_date"
                                        type="date"
                                        class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                        :class="{ 'border-error': errors.start_date }"
                                    />
                                    <p v-if="errors.start_date" class="text-xs text-error mt-1">{{ errors.start_date }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                        Tanggal Selesai
                                    </label>
                                    <input
                                        v-model="form.end_date"
                                        type="date"
                                        class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                        :class="{ 'border-error': errors.end_date }"
                                    />
                                    <p v-if="errors.end_date" class="text-xs text-error mt-1">{{ errors.end_date }}</p>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-3 pt-3 border-t border-outline-variant/30">
                                <button
                                    type="button"
                                    @click="$emit('close')"
                                    class="px-5 py-2.5 rounded-xl text-sm font-semibold text-on-surface-variant hover:bg-surface-container-high border border-outline-variant/50 transition-colors cursor-pointer"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    :disabled="isSubmitting"
                                    class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-sm transition-all shadow-xs hover:shadow-md cursor-pointer disabled:opacity-60"
                                >
                                    <span
                                        v-if="isSubmitting"
                                        class="material-symbols-outlined text-[16px] animate-spin"
                                    >sync</span>
                                    <span class="material-symbols-outlined text-[16px]" v-else>
                                        {{ isEditing ? 'save' : 'add_circle' }}
                                    </span>
                                    <span>{{ isEditing ? 'Simpan Perubahan' : 'Tambah Tahun Ajaran' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-backdrop-enter-active,
.modal-backdrop-leave-active {
    transition: opacity 0.2s ease;
}
.modal-backdrop-enter-from,
.modal-backdrop-leave-to {
    opacity: 0;
}
.modal-content-enter-active {
    transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-content-leave-active {
    transition: all 0.15s ease-in;
}
.modal-content-enter-from {
    opacity: 0;
    transform: scale(0.95) translateY(8px);
}
.modal-content-leave-to {
    opacity: 0;
    transform: scale(0.98) translateY(4px);
}
</style>
