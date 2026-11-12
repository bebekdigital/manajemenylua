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
    schoolUnit: {
        type: Object,
        default: null,
    },
    existingPrograms: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'success']);

const form = useForm({
    name: '',
});

const isEditing = computed(() => !!props.editing);
const modalTitle = computed(() => isEditing.value ? 'Edit Program' : 'Tambah Program');

const selectedProgram = ref('');
const isCustomMode = ref(false);

const defaultPrograms = ['Umum', 'Boarding', 'Fullday', 'Tahfidz', 'Reguler', 'Kitab'];

// Kumpulan seluruh program yang sudah pernah ditambahkan di seluruh unit
const allAvailablePrograms = computed(() => {
    const set = new Set();

    (props.existingPrograms || []).forEach(p => {
        const str = typeof p === 'string' ? p : p?.name;
        if (str && typeof str === 'string' && str.trim()) {
            set.add(str.trim());
        }
    });

    defaultPrograms.forEach(p => set.add(p));

    return Array.from(set).sort((a, b) => a.localeCompare(b));
});

// Daftar nama program yang sudah ada pada unit yang sedang dipilih saat ini
const currentUnitProgramNames = computed(() => {
    if (!props.schoolUnit?.programs) return new Set();
    return new Set(
        props.schoolUnit.programs.map(p => (typeof p === 'string' ? p : p.name)?.trim().toLowerCase())
    );
});

function isProgramAlreadyInUnit(name) {
    if (!name) return false;
    if (isEditing.value && props.editing?.name?.toLowerCase() === name.toLowerCase()) {
        return false;
    }
    return currentUnitProgramNames.value.has(name.trim().toLowerCase());
}

watch(
    () => props.show,
    (val) => {
        if (val) {
            form.clearErrors();
            if (props.editing) {
                form.name = props.editing.name;
                const match = allAvailablePrograms.value.find(
                    p => p.toLowerCase() === (props.editing.name || '').toLowerCase()
                );
                if (match) {
                    selectedProgram.value = match;
                    isCustomMode.value = false;
                } else {
                    selectedProgram.value = '__CUSTOM__';
                    isCustomMode.value = true;
                }
            } else {
                form.reset();
                selectedProgram.value = '';
                isCustomMode.value = false;
            }
        }
    }
);

function onSelectChange() {
    if (selectedProgram.value === '__CUSTOM__') {
        isCustomMode.value = true;
        form.name = '';
    } else {
        isCustomMode.value = false;
        form.name = selectedProgram.value;
    }
    form.clearErrors('name');
}

function switchToCustomMode() {
    isCustomMode.value = true;
    selectedProgram.value = '__CUSTOM__';
    form.name = '';
    form.clearErrors('name');
}

function switchToSelectMode() {
    isCustomMode.value = false;
    selectedProgram.value = '';
    form.name = '';
    form.clearErrors('name');
}

function submit() {
    if (isCustomMode.value) {
        form.name = (form.name || '').trim();
    } else {
        form.name = (selectedProgram.value || '').trim();
    }

    if (!form.name) {
        form.setError('name', 'Nama program wajib diisi atau dipilih.');
        return;
    }

    if (props.editing) {
        form.put(`/portal/school-programs/${props.editing.id}`, {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        });
    } else {
        form.post(`/portal/school-units/${props.schoolUnit.id}/programs`, {
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

                        <form @submit.prevent="submit" class="px-6 py-5 space-y-4">
                            <div v-if="schoolUnit && !isEditing" class="p-3 bg-surface-container-low rounded-xl border border-outline-variant/40 text-sm">
                                <span class="text-on-surface-variant">Menambahkan program untuk unit: </span>
                                <strong class="text-on-surface">{{ schoolUnit.name }}</strong>
                            </div>

                            <!-- Pilihan 1: Dropdown Sederhana -->
                            <div v-if="!isCustomMode">
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                                        Pilih Program <span class="text-error">*</span>
                                    </label>
                                    <button
                                        type="button"
                                        @click="switchToCustomMode"
                                        class="text-xs text-primary hover:underline font-semibold cursor-pointer"
                                    >
                                        Atau isi sendiri &rarr;
                                    </button>
                                </div>

                                <select
                                    v-model="selectedProgram"
                                    @change="onSelectChange"
                                    class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                                    :class="{ 'border-error': form.errors.name }"
                                >
                                    <option value="" disabled>-- Pilih dari Program yang Pernah Ditambahkan --</option>
                                    <option
                                        v-for="prog in allAvailablePrograms"
                                        :key="prog"
                                        :value="prog"
                                        :disabled="isProgramAlreadyInUnit(prog)"
                                    >
                                        {{ prog }}{{ isProgramAlreadyInUnit(prog) ? ' (Sudah ada di unit ini)' : '' }}
                                    </option>
                                    <option value="__CUSTOM__">+ Isi Sendiri (Program Baru)...</option>
                                </select>
                                <div v-if="form.errors.name" class="text-error text-xs mt-1.5">{{ form.errors.name }}</div>
                            </div>

                            <!-- Pilihan 2: Input Ketik Sendiri (Manual) -->
                            <div v-else>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                                        Nama Program Baru <span class="text-error">*</span>
                                    </label>
                                    <button
                                        type="button"
                                        @click="switchToSelectMode"
                                        class="text-xs text-primary hover:underline font-semibold cursor-pointer"
                                    >
                                        &larr; Pilih dari dropdown
                                    </button>
                                </div>

                                <input
                                    v-model="form.name"
                                    type="text"
                                    autofocus
                                    class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                    placeholder="Ketik nama program baru di sini..."
                                    :class="{ 'border-error': form.errors.name }"
                                />
                                <div v-if="form.errors.name" class="text-error text-xs mt-1.5">{{ form.errors.name }}</div>
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
