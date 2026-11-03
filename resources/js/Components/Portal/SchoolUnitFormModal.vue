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
    },
    departments: {
        type: Array,
        default: () => [],
    },
    schoolLevels: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'success']);

const form = useForm({
    name: '',
});

const isEditing = computed(() => !!props.editing);
const modalTitle = computed(() => isEditing.value ? 'Edit Unit Sekolah' : 'Tambah Unit Sekolah');

const isManualInput = ref(false);
const customName = ref('');

// Filter hanya unit dengan kelompok "Satuan Pendidikan" dari Diagram Organisasi
const satuanPendidikanUnits = computed(() => {
    if (!props.departments || !Array.isArray(props.departments)) return [];
    return props.departments.filter(
        d => (d.unit_group || '').toLowerCase() === 'satuan pendidikan'
    );
});

const hasSatuanPendidikan = computed(() => satuanPendidikanUnits.value.length > 0);

// Daftar nama unit yang sudah terdaftar di seluruh jenjang (untuk mencegah duplikasi)
const registeredUnitNames = computed(() => {
    const list = [];
    if (props.schoolLevels && Array.isArray(props.schoolLevels)) {
        props.schoolLevels.forEach(lvl => {
            if (lvl && Array.isArray(lvl.units)) {
                lvl.units.forEach(u => {
                    if (u && u.name) list.push(u.name.trim().toLowerCase());
                });
            }
        });
    }
    return list;
});

function isUnitDisabled(deptName) {
    if (!deptName) return false;
    const cleanName = deptName.trim().toLowerCase();
    // Jika sedang edit dan merupakan nama unit ini sendiri, jangan di-disable
    if (props.editing && props.editing.name && props.editing.name.trim().toLowerCase() === cleanName) {
        return false;
    }
    return registeredUnitNames.value.includes(cleanName);
}

watch(
    () => props.show,
    (val) => {
        if (val) {
            form.clearErrors();
            if (props.editing) {
                form.name = props.editing.name || '';
                const matchInDropdown = satuanPendidikanUnits.value.some(
                    d => d.name.trim().toLowerCase() === (props.editing.name || '').trim().toLowerCase()
                );
                isManualInput.value = !matchInDropdown && !!props.editing.name;
                customName.value = isManualInput.value ? props.editing.name : '';
            } else {
                form.reset();
                isManualInput.value = !hasSatuanPendidikan.value;
                customName.value = '';
            }
        }
    }
);

function onSelectUnit(event) {
    const val = event.target.value;
    if (val === '__custom__') {
        isManualInput.value = true;
        form.name = customName.value;
    } else {
        isManualInput.value = false;
        form.name = val;
    }
}

function submit() {
    if (!form.name || !form.name.trim()) {
        form.setError('name', 'Silakan pilih atau masukkan nama unit sekolah.');
        return;
    }

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
                        class="bg-white rounded-2xl shadow-xl w-full max-w-lg border border-slate-200 overflow-hidden"
                    >
                        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-slate-50/70">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-primary text-lg">
                                        {{ isEditing ? 'edit' : 'add_circle' }}
                                    </span>
                                </div>
                                <h3 class="text-lg font-bold text-slate-900">{{ modalTitle }}</h3>
                            </div>
                            <button
                                type="button"
                                @click="$emit('close')"
                                class="p-2 rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-xl">close</span>
                            </button>
                        </div>

                        <form @submit.prevent="submit" class="px-6 py-5 space-y-5">
                            <div v-if="schoolLevel && !isEditing" class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-sm">
                                <span class="text-slate-500">Menambahkan unit untuk jenjang: </span>
                                <strong class="text-slate-800 font-bold">{{ schoolLevel.name }}</strong>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                        Unit Sekolah (Satuan Pendidikan) <span class="text-error">*</span>
                                    </label>
                                    <button
                                        v-if="hasSatuanPendidikan"
                                        type="button"
                                        @click="isManualInput = !isManualInput; if (!isManualInput) { form.name = ''; } else { form.name = customName; }"
                                        class="text-[11px] font-semibold text-emerald-800 hover:text-emerald-950 hover:underline cursor-pointer flex items-center gap-1 transition-colors"
                                    >
                                        <span class="material-symbols-outlined text-[13px]">
                                            {{ isManualInput ? 'list' : 'edit_note' }}
                                        </span>
                                        <span>{{ isManualInput ? 'Pilih dari Dropdown' : 'Input Manual' }}</span>
                                    </button>
                                </div>

                                <!-- Mode Dropdown (Ngambil dari Kelompok Unit: Satuan Pendidikan pada Diagram Organisasi) -->
                                <div v-if="!isManualInput && hasSatuanPendidikan" class="space-y-1.5">
                                    <div class="relative">
                                        <select
                                            :value="form.name"
                                            @change="onSelectUnit"
                                            required
                                            class="w-full pl-4 pr-10 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer font-medium appearance-none shadow-2xs"
                                            :class="{ 'border-error': form.errors.name }"
                                        >
                                            <option value="" disabled>-- Pilih Unit Sekolah (Satuan Pendidikan) --</option>
                                            <option
                                                v-for="dept in satuanPendidikanUnits"
                                                :key="dept.id"
                                                :value="dept.name"
                                                :disabled="isUnitDisabled(dept.name)"
                                            >
                                                {{ dept.name }} {{ isUnitDisabled(dept.name) ? '(Sudah Terdaftar)' : '' }}
                                            </option>
                                            <option value="__custom__" class="font-semibold text-emerald-800">
                                                + Tulis Nama Unit Lainnya (Manual)...
                                            </option>
                                        </select>
                                        <span class="material-symbols-outlined text-slate-400 text-[18px] absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                                            expand_more
                                        </span>
                                    </div>
                                    <p class="text-[11.5px] text-slate-500 flex items-center gap-1.5 pt-0.5">
                                        <span class="material-symbols-outlined text-[15px] text-emerald-700">info</span>
                                        <span>Pilihan unit diambil dari <strong>Diagram Organisasi</strong> (Kelompok Unit: <em>Satuan Pendidikan</em>).</span>
                                    </p>
                                </div>

                                <!-- Mode Input Manual (Jika memilih input manual atau belum ada Satuan Pendidikan) -->
                                <div v-else class="space-y-1.5">
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        required
                                        class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs"
                                        placeholder="Contoh: SDIT Ulil Albab, SMPIT, dll."
                                        :class="{ 'border-error': form.errors.name }"
                                    />
                                    <p v-if="!hasSatuanPendidikan" class="text-[11.5px] text-amber-800 bg-amber-50 border border-amber-200/90 p-2.5 rounded-xl flex items-start gap-1.5 mt-1 leading-relaxed">
                                        <span class="material-symbols-outlined text-[16px] text-amber-700 shrink-0 mt-0.5">info</span>
                                        <span>Belum ada unit dengan kelompok <strong>Satuan Pendidikan</strong> di Diagram Organisasi. Anda dapat menambahkannya terlebih dahulu di tab <em>Unit & Bidang &rarr; Daftar Unit</em> atau ketik nama secara manual di atas.</span>
                                    </p>
                                </div>

                                <div v-if="form.errors.name" class="text-error text-xs mt-1 font-medium">{{ form.errors.name }}</div>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                                <button
                                    type="button"
                                    @click="$emit('close')"
                                    class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100 border border-slate-300 transition-colors cursor-pointer"
                                    :disabled="form.processing"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-white font-semibold text-sm transition-all shadow-xs hover:shadow-md cursor-pointer disabled:opacity-60"
                                    :disabled="form.processing"
                                >
                                    <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                    <span v-else class="material-symbols-outlined text-[16px]">{{ isEditing ? 'save' : 'add_circle' }}</span>
                                    <span>{{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan Unit') }}</span>
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
