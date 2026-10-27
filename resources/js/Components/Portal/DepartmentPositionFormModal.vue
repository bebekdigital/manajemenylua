<script setup>
import { watch, computed, ref } from 'vue';
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
    department: {
        type: Object,
        default: null,
    },
    departments: {
        type: Array,
        default: () => [],
    },
    allPositions: {
        type: Array,
        default: () => [],
    },
    presetLevel: {
        type: Number,
        default: 3,
    },
    presetParentId: {
        type: [Number, String],
        default: null,
    },
    presetDepartmentId: {
        type: [Number, String],
        default: null,
    },
    modeTitle: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close', 'success']);

const levelOptions = [
    {
        level: 1,
        title: 'Level 1: Dewan Pembina & Pengawas',
        desc: 'Pemegang kebijakan tertinggi & dewan pengawas yayasan',
        badgeClass: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/30',
    },
    {
        level: 2,
        title: 'Level 2: Pengurus Harian & Direksi',
        desc: 'Ketua Umum, Sekretaris, Bendahara, & Direktur Eksekutif',
        badgeClass: 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border-indigo-500/30',
    },
    {
        level: 3,
        title: 'Level 3: Kepala Bidang Yayasan',
        desc: 'Pimpinan bidang (Pendidikan, SDM, Keuangan, Sarpras, Dakwah)',
        badgeClass: 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/30',
    },
    {
        level: 4,
        title: 'Level 4: Koordinator & Kepala Bagian',
        desc: 'Koordinator program, unit teknis, dan kepala sub-bagian',
        badgeClass: 'bg-cyan-500/10 text-cyan-700 dark:text-cyan-300 border-cyan-500/30',
    },
    {
        level: 5,
        title: 'Level 5: Staf Pelaksana & Tim Teknis',
        desc: 'Staf administrasi, akuntansi, logistik, dan pelaksana teknis',
        badgeClass: 'bg-slate-500/10 text-slate-700 dark:text-slate-300 border-slate-500/30',
    },
];

const form = useForm({
    name: '',
    level: 3,
    department_id: '',
    parent_id: '',
    order: 0,
});

const isEditing = computed(() => !!props.editing);
const modalTitle = computed(() => {
    if (props.modeTitle) return props.modeTitle;
    return isEditing.value ? 'Edit Jabatan' : 'Tambah Jabatan Organisasi';
});

// Candidate parents: all positions excluding self and descendants
const availableParents = computed(() => {
    if (!props.allPositions || props.allPositions.length === 0) return [];

    let excludedIds = new Set();
    if (props.editing) {
        excludedIds.add(props.editing.id);
        // Find descendants
        const findDescendants = (parentId) => {
            props.allPositions.forEach(p => {
                if (p.parent_id === parentId && !excludedIds.has(p.id)) {
                    excludedIds.add(p.id);
                    findDescendants(p.id);
                }
            });
        };
        findDescendants(props.editing.id);
    }

    return props.allPositions.filter(p => !excludedIds.has(p.id));
});

watch(
    () => props.show,
    (val) => {
        if (val) {
            if (props.editing) {
                form.name = props.editing.name || '';
                form.level = props.editing.level ? Number(props.editing.level) : 3;
                form.department_id = props.editing.department_id || '';
                form.parent_id = props.editing.parent_id || '';
                form.order = props.editing.order || 0;
            } else {
                form.reset();
                form.level = props.presetLevel || 3;
                form.parent_id = props.presetParentId || '';
                form.department_id = props.presetDepartmentId || (props.department?.id || '');
                form.order = 0;
            }
            form.clearErrors();
        }
    }
);

function submit() {
    const payload = {
        name: form.name,
        level: Number(form.level),
        department_id: form.department_id ? Number(form.department_id) : null,
        parent_id: form.parent_id ? Number(form.parent_id) : null,
        order: Number(form.order) || 0,
    };

    if (props.editing) {
        form.transform(() => payload).put(`/portal/department-positions/${props.editing.id}`, {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        });
    } else {
        // Use direct endpoint for adding anywhere in hierarchy
        form.transform(() => payload).post('/portal/department-positions', {
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
                class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 overflow-y-auto"
                @click.self="$emit('close')"
            >
                <Transition name="modal-content">
                    <div
                        v-if="show"
                        class="bg-surface-container-lowest rounded-2xl shadow-2xl w-full max-w-xl border border-outline-variant/30 overflow-hidden my-8"
                    >
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between px-6 py-4.5 border-b border-outline-variant/30 bg-surface-container-low/40">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shadow-xs">
                                    <span class="material-symbols-outlined text-xl">
                                        {{ isEditing ? 'edit_note' : 'account_tree' }}
                                    </span>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-on-surface">{{ modalTitle }}</h3>
                                    <p class="text-xs text-on-surface-variant">
                                        Susunan organisasi 5 level vertikal & horizontal yayasan
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

                        <!-- Form Body -->
                        <form @submit.prevent="submit" class="px-6 py-5 space-y-4">
                            <!-- Nama Jabatan -->
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                    Nama Jabatan <span class="text-error">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all font-medium"
                                    placeholder="Contoh: Ketua Dewan Pembina, Kepala Bidang SDM, Staff Kepegawaian"
                                    :class="{ 'border-error': form.errors.name }"
                                />
                                <div v-if="form.errors.name" class="text-error text-xs mt-1">{{ form.errors.name }}</div>
                            </div>

                            <!-- Level Jabatan (1 - 5) -->
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                    Tingkat / Level Jabatan <span class="text-error">*</span>
                                </label>
                                <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                                    <label
                                        v-for="opt in levelOptions"
                                        :key="opt.level"
                                        class="flex items-start gap-3 p-2.5 rounded-xl border transition-all cursor-pointer"
                                        :class="form.level === opt.level
                                            ? 'bg-primary/5 border-primary shadow-2xs'
                                            : 'bg-surface-container-low/50 border-outline-variant/50 hover:bg-surface-container-low'"
                                    >
                                        <input
                                            type="radio"
                                            v-model="form.level"
                                            :value="opt.level"
                                            class="mt-1 text-primary focus:ring-primary"
                                        />
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-on-surface">{{ opt.title }}</span>
                                                <span :class="['text-[10px] font-bold px-1.5 py-0.5 rounded border', opt.badgeClass]">
                                                    L{{ opt.level }}
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-on-surface-variant leading-tight mt-0.5">
                                                {{ opt.desc }}
                                            </p>
                                        </div>
                                    </label>
                                </div>
                                <div v-if="form.errors.level" class="text-error text-xs mt-1">{{ form.errors.level }}</div>
                            </div>

                            <!-- Atasan Langsung (Parent / Hierarki Vertikal) -->
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                    Atasan Langsung (Hierarki Vertikal)
                                </label>
                                <select
                                    v-model="form.parent_id"
                                    class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                                    :class="{ 'border-error': form.errors.parent_id }"
                                >
                                    <option value="">-- Tidak Ada Atasan (Tingkat Mandiri / Pembina Tertinggi) --</option>
                                    <option
                                        v-for="parent in availableParents"
                                        :key="parent.id"
                                        :value="parent.id"
                                    >
                                        [Level {{ parent.level || 3 }}] {{ parent.name }} {{ parent.department_name ? `(${parent.department_name})` : '' }}
                                    </option>
                                </select>
                                <p class="text-[11px] text-on-surface-variant mt-1">
                                    Pilih jabatan atasan langsung untuk membentuk cabang pohon vertikal.
                                </p>
                                <div v-if="form.errors.parent_id" class="text-error text-xs mt-1">{{ form.errors.parent_id }}</div>
                            </div>

                            <!-- Bidang Kerja / Departemen -->
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                    Bidang Kerja / Departemen Yayasan
                                </label>
                                <select
                                    v-model="form.department_id"
                                    class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                                    :class="{ 'border-error': form.errors.department_id }"
                                >
                                    <option value="">-- Yayasan / Lintas Bidang (Umum) --</option>
                                    <option
                                        v-for="dept in departments"
                                        :key="dept.id"
                                        :value="dept.id"
                                    >
                                        {{ dept.name }}
                                    </option>
                                </select>
                                <p class="text-[11px] text-on-surface-variant mt-1">
                                    Kelompokkan jabatan ke dalam bidang kerja atau pilih Lintas Bidang untuk eksekutif/pembina yayasan.
                                </p>
                                <div v-if="form.errors.department_id" class="text-error text-xs mt-1">{{ form.errors.department_id }}</div>
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
                                    class="inline-flex items-center gap-1.5 px-6 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-sm transition-all shadow-xs hover:shadow-md cursor-pointer disabled:opacity-60"
                                    :disabled="form.processing"
                                >
                                    <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                    <span v-else class="material-symbols-outlined text-[16px]">{{ isEditing ? 'save' : 'add_circle' }}</span>
                                    <span>{{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan Jabatan') }}</span>
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
