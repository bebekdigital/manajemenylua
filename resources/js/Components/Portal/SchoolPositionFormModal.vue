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
    allPositions: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'success']);

const mode = ref('select'); // 'select' | 'create'
const selectedPositionId = ref('');
const newPositionName = ref('');

const form = useForm({
    school_position_id: '',
    name: '',
});

const isEditing = computed(() => !!props.editing);
const modalTitle = computed(() => (isEditing.value ? 'Edit Jabatan' : 'Tambah Jabatan Unit'));

const assignedPositionIds = computed(() => {
    if (!props.schoolUnit || !props.schoolUnit.positions) return [];
    return props.schoolUnit.positions.map((p) => p.id);
});

const hasAvailablePositions = computed(() => {
    return props.allPositions && props.allPositions.length > 0;
});

// Mengecek apakah semua jabatan yang ada sudah masuk ke unit ini
const allAssigned = computed(() => {
    if (!hasAvailablePositions.value) return false;
    return props.allPositions.every((pos) => assignedPositionIds.value.includes(pos.id));
});

function isPositionAssigned(posId) {
    return assignedPositionIds.value.includes(posId);
}

watch(
    () => props.show,
    (val) => {
        if (val) {
            form.clearErrors();
            if (props.editing) {
                form.name = props.editing.name;
                mode.value = 'create';
            } else {
                form.reset();
                selectedPositionId.value = '';
                newPositionName.value = '';

                // Jika ada opsi jabatan dan belum semuanya terpasang di unit ini, buka tab select
                if (hasAvailablePositions.value && !allAssigned.value) {
                    mode.value = 'select';
                    // Auto select the first unassigned position if convenient
                    const firstUnassigned = props.allPositions.find(
                        (p) => !assignedPositionIds.value.includes(p.id)
                    );
                    if (firstUnassigned) {
                        selectedPositionId.value = firstUnassigned.id;
                    }
                } else {
                    mode.value = 'create';
                }
            }
        }
    }
);

function submit() {
    if (isEditing.value) {
        form.name = form.name?.trim() || '';
        form.school_position_id = '';
        form.put(`/portal/school-positions/${props.editing.id}`, {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        });
    } else {
        if (mode.value === 'select') {
            form.school_position_id = selectedPositionId.value;
            form.name = '';
        } else {
            form.school_position_id = '';
            form.name = newPositionName.value?.trim() || '';
        }

        form.post(`/portal/school-units/${props.schoolUnit.id}/positions`, {
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
                                        {{ isEditing ? 'edit' : 'badge' }}
                                    </span>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-on-surface">{{ modalTitle }}</h3>
                                    <p v-if="schoolUnit && !isEditing" class="text-xs text-on-surface-variant">
                                        Unit: <strong class="text-on-surface font-semibold">{{ schoolUnit.name }}</strong>
                                    </p>
                                </div>
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
                            <!-- Mode switcher (Pilih dari daftar vs Buat baru) -->
                            <div
                                v-if="!isEditing && hasAvailablePositions"
                                class="flex p-1 bg-surface-container-low rounded-xl border border-outline-variant/40"
                            >
                                <button
                                    type="button"
                                    @click="mode = 'select'"
                                    class="flex-1 py-2 px-3 rounded-lg text-xs font-semibold transition-all cursor-pointer text-center flex items-center justify-center gap-1.5"
                                    :class="
                                        mode === 'select'
                                            ? 'bg-surface-container-lowest text-primary shadow-xs'
                                            : 'text-on-surface-variant hover:text-on-surface'
                                    "
                                >
                                    <span class="material-symbols-outlined text-[16px]">list_alt</span>
                                    <span>Pilih dari Daftar</span>
                                </button>
                                <button
                                    type="button"
                                    @click="mode = 'create'"
                                    class="flex-1 py-2 px-3 rounded-lg text-xs font-semibold transition-all cursor-pointer text-center flex items-center justify-center gap-1.5"
                                    :class="
                                        mode === 'create'
                                            ? 'bg-surface-container-lowest text-primary shadow-xs'
                                            : 'text-on-surface-variant hover:text-on-surface'
                                    "
                                >
                                    <span class="material-symbols-outlined text-[16px]">add_circle</span>
                                    <span>+ Buat Jabatan Baru</span>
                                </button>
                            </div>

                            <!-- Opsi 1: Pilih dari Dropdown Master Jabatan -->
                            <div v-if="!isEditing && mode === 'select' && hasAvailablePositions" class="space-y-2">
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                                    Pilih Jabatan Terdaftar <span class="text-error">*</span>
                                </label>
                                <div class="relative">
                                    <select
                                        v-model="selectedPositionId"
                                        required
                                        class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer appearance-none pr-10"
                                        :class="{ 'border-error': form.errors.name || form.errors.school_position_id }"
                                    >
                                        <option value="" disabled>-- Pilih Jabatan Yang Sudah Ada --</option>
                                        <option
                                            v-for="pos in allPositions"
                                            :key="pos.id"
                                            :value="pos.id"
                                            :disabled="isPositionAssigned(pos.id)"
                                        >
                                            {{ pos.name }}{{ isPositionAssigned(pos.id) ? ' (Sudah ditambahkan di unit ini)' : '' }}
                                        </option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-on-surface-variant">
                                        <span class="material-symbols-outlined text-lg">arrow_drop_down</span>
                                    </div>
                                </div>
                                <div v-if="form.errors.name || form.errors.school_position_id" class="text-error text-xs mt-1">
                                    {{ form.errors.name || form.errors.school_position_id }}
                                </div>
                                <p class="text-xs text-on-surface-variant flex items-center gap-1 pt-1">
                                    <span>Tidak menemukan jabatan yang sesuai?</span>
                                    <button
                                        type="button"
                                        @click="mode = 'create'"
                                        class="text-primary font-semibold hover:underline cursor-pointer inline-flex items-center"
                                    >
                                        Buat jabatan baru
                                    </button>
                                </p>
                            </div>

                            <!-- Opsi 2: Buat Jabatan Baru / Edit Jabatan -->
                            <div v-else class="space-y-2">
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                                    {{ isEditing ? 'Nama Jabatan' : 'Nama Jabatan Baru' }} <span class="text-error">*</span>
                                </label>
                                <input
                                    v-if="isEditing"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                    placeholder="Contoh: Guru Mata Pelajaran, Kepala Sekolah, TU"
                                    :class="{ 'border-error': form.errors.name }"
                                />
                                <input
                                    v-else
                                    v-model="newPositionName"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                    placeholder="Contoh: Guru Mata Pelajaran, Kepala Sekolah, TU"
                                    :class="{ 'border-error': form.errors.name }"
                                />
                                <div v-if="form.errors.name" class="text-error text-xs mt-1">{{ form.errors.name }}</div>
                                <p v-if="!isEditing" class="text-xs text-on-surface-variant pt-1 leading-relaxed">
                                    Jabatan baru ini akan disimpan sebagai master jabatan sehingga dapat langsung dipilih untuk unit-unit sekolah lainnya (1 ID bersama).
                                    <button
                                        v-if="hasAvailablePositions && !allAssigned"
                                        type="button"
                                        @click="mode = 'select'"
                                        class="text-primary font-semibold hover:underline ml-1 cursor-pointer"
                                    >
                                        Pilih dari daftar
                                    </button>
                                </p>
                            </div>

                            <!-- Modal Footer Buttons -->
                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-outline-variant/30">
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
                                    <span>{{ form.processing ? 'Menyimpan...' : isEditing ? 'Simpan Perubahan' : 'Tambahkan' }}</span>
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
