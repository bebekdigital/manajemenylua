<script setup>
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    editing: {
        type: Object,
        default: null,
    },
    schoolLevel: {
        type: Object,
        default: null,
    }
});

const emit = defineEmits(['close', 'success']);

const form = useForm({
    name: '',
});

const isEditing = computed(() => !!props.editing);
const modalTitle = computed(() => isEditing.value ? 'Edit Unit Sekolah' : 'Tambah Unit Sekolah');

watch(
    () => props.show,
    (val) => {
        if (val) {
            if (props.editing) {
                form.name = props.editing.name;
            } else {
                form.reset();
            }
            form.clearErrors();
        }
    }
);

function submit() {
    if (props.editing) {
        form.put(`/portal/school-units/${props.editing.id}`, {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        });
    } else {
        form.post(`/portal/school-levels/${props.schoolLevel.id}/units`, {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        });
    }
}
</script>

<template>
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
                        <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/30">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-primary text-lg">
                                        {{ isEditing ? 'edit' : 'add_circle' }}
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

                        <form @submit.prevent="submit" class="px-6 py-5 space-y-5">
                            <div v-if="schoolLevel && !isEditing" class="p-3 bg-surface-container-low rounded-xl border border-outline-variant/40 text-sm">
                                <span class="text-on-surface-variant">Menambahkan unit untuk jenjang: </span>
                                <strong class="text-on-surface">{{ schoolLevel.name }}</strong>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                    Nama Unit <span class="text-error">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                    placeholder="Contoh: SDIT Insantama, SMPIT, dll."
                                    :class="{ 'border-error': form.errors.name }"
                                />
                                <div v-if="form.errors.name" class="text-error text-xs mt-1">{{ form.errors.name }}</div>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-3 border-t border-outline-variant/30">
                                <button
                                    type="button"
                                    @click="$emit('close')"
                                    class="px-5 py-2.5 rounded-xl text-sm font-semibold text-on-surface-variant hover:bg-surface-container-high border border-outline-variant/50 transition-colors cursor-pointer"
                                    :disabled="form.processing"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-sm transition-all shadow-xs hover:shadow-md cursor-pointer disabled:opacity-60"
                                    :disabled="form.processing"
                                >
                                    <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                    <span v-else class="material-symbols-outlined text-[16px]">{{ isEditing ? 'save' : 'add_circle' }}</span>
                                    <span>{{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan') }}</span>
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
