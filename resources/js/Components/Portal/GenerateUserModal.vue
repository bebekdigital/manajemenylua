<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    employees: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'success']);

const searchQuery = ref('');
const filterMode = ref('unregistered'); // 'unregistered' | 'all'
const selectedIds = ref([]);

const form = useForm({
    employee_ids: [],
});

const unassignedCount = computed(() => {
    return (props.employees || []).filter((e) => !e.has_account).length;
});

const allCount = computed(() => {
    return (props.employees || []).length;
});

const filteredEmployees = computed(() => {
    let list = props.employees || [];

    if (filterMode.value === 'unregistered') {
        list = list.filter((e) => !e.has_account);
    }

    if (searchQuery.value.trim()) {
        const q = searchQuery.value.trim().toLowerCase();
        list = list.filter(
            (e) =>
                (e.nama || '').toLowerCase().includes(q) ||
                (e.nama_lengkap_gelar || '').toLowerCase().includes(q) ||
                (e.nipy || '').toLowerCase().includes(q) ||
                (e.jabatan || '').toLowerCase().includes(q) ||
                (e.unit_kerja || '').toLowerCase().includes(q)
        );
    }

    return list;
});

const selectableEmployees = computed(() => {
    return filteredEmployees.value.filter((e) => !e.has_account);
});

const isAllSelected = computed(() => {
    if (selectableEmployees.value.length === 0) return false;
    return selectableEmployees.value.every((e) => selectedIds.value.includes(e.id));
});

function toggleSelectAll() {
    if (isAllSelected.value) {
        // Deselect all in filtered list
        const filteredIds = new Set(selectableEmployees.value.map((e) => e.id));
        selectedIds.value = selectedIds.value.filter((id) => !filteredIds.has(id));
    } else {
        // Select all in filtered list
        const toAdd = selectableEmployees.value.map((e) => e.id);
        selectedIds.value = Array.from(new Set([...selectedIds.value, ...toAdd]));
    }
}

function toggleSelect(id) {
    const idx = selectedIds.value.indexOf(id);
    if (idx > -1) {
        selectedIds.value.splice(idx, 1);
    } else {
        selectedIds.value.push(id);
    }
}

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            searchQuery.value = '';
            filterMode.value = 'unregistered';
            selectedIds.value = [];
            form.clearErrors();
        }
    }
);

function submit() {
    if (selectedIds.value.length === 0) return;

    form.employee_ids = selectedIds.value;
    form.post('/portal/users/generate', {
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
        },
    });
}
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
            <div v-if="show" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-2 sm:p-6">
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity"
                    @click="emit('close')"
                ></div>

                <!-- Modal Dialog -->
                <div class="bg-surface-container-lowest rounded-2xl sm:rounded-3xl shadow-2xl border border-outline-variant/30 overflow-hidden w-full max-w-3xl relative z-10 flex flex-col max-h-[92dvh] sm:max-h-[90vh]">
                    <!-- Header -->
                    <div class="px-4 py-3 sm:px-6 sm:py-4 border-b border-outline-variant/30 flex items-center justify-between gap-3 bg-surface-container-low/40 shrink-0">
                        <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-xl sm:text-2xl">auto_mode</span>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm sm:text-lg font-bold text-on-surface leading-tight truncate">Generate Akun Pegawai</h3>
                                <p class="text-[11px] sm:text-xs text-on-surface-variant truncate">Pilih pegawai untuk dibuatkan akun login sistem</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="emit('close')"
                            class="text-on-surface-variant hover:text-on-surface p-1.5 sm:p-2 rounded-full hover:bg-surface-variant transition-colors cursor-pointer shrink-0"
                        >
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <!-- Filter & Search Toolbar -->
                    <div class="px-4 py-2.5 sm:px-6 sm:py-3 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 sm:gap-3 shrink-0 border-b border-outline-variant/20 sm:border-b-0">
                        <!-- Mode Tabs -->
                        <div class="flex p-1 bg-surface-container-low rounded-xl border border-outline-variant/40 w-full sm:w-auto">
                            <button
                                type="button"
                                @click="filterMode = 'unregistered'"
                                class="flex-1 sm:flex-initial py-1.5 px-2.5 sm:px-3 rounded-lg text-[11px] sm:text-xs font-semibold transition-all cursor-pointer text-center whitespace-nowrap"
                                :class="filterMode === 'unregistered' ? 'bg-surface-container-lowest text-primary shadow-xs' : 'text-on-surface-variant hover:text-on-surface'"
                            >
                                Belum Punya Akun ({{ unassignedCount }})
                            </button>
                            <button
                                type="button"
                                @click="filterMode = 'all'"
                                class="flex-1 sm:flex-initial py-1.5 px-2.5 sm:px-3 rounded-lg text-[11px] sm:text-xs font-semibold transition-all cursor-pointer text-center whitespace-nowrap"
                                :class="filterMode === 'all' ? 'bg-surface-container-lowest text-primary shadow-xs' : 'text-on-surface-variant hover:text-on-surface'"
                            >
                                Semua Pegawai ({{ allCount }})
                            </button>
                        </div>

                        <!-- Search -->
                        <div class="relative w-full sm:w-72">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-on-surface-variant">search</span>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari nama, jabatan, unit..."
                                class="w-full pl-9 pr-3 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                            />
                        </div>
                    </div>

                    <!-- Error Alert -->
                    <div v-if="form.errors.employee_ids" class="px-4 sm:px-6 py-1 shrink-0">
                        <div class="p-2.5 rounded-xl bg-error/10 text-error text-xs font-medium border border-error/20">
                            {{ form.errors.employee_ids }}
                        </div>
                    </div>

                    <!-- Employee List Container -->
                    <div class="flex-1 min-h-0 overflow-y-auto px-4 sm:px-6 py-2">
                        <!-- Desktop Table View -->
                        <div class="hidden sm:block rounded-2xl border border-outline-variant/60 overflow-x-auto bg-white">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-surface-container-low/70 text-on-surface-variant font-bold text-[10px] uppercase tracking-wider border-b border-outline-variant/60">
                                        <th class="p-3 w-10 text-center">
                                            <input
                                                type="checkbox"
                                                :checked="isAllSelected"
                                                :disabled="selectableEmployees.length === 0"
                                                @change="toggleSelectAll"
                                                class="rounded border-outline-variant text-primary focus:ring-primary/20 cursor-pointer disabled:opacity-40"
                                            />
                                        </th>
                                        <th class="p-3">Nama Pegawai</th>
                                        <th class="p-3">Jabatan &amp; Unit</th>
                                        <th class="p-3">Tanggal Lahir (Password)</th>
                                        <th class="p-3 text-center">Status Akun</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/40">
                                    <tr
                                        v-for="emp in filteredEmployees"
                                        :key="emp.id"
                                        @click="!emp.has_account && toggleSelect(emp.id)"
                                        class="transition-colors"
                                        :class="[
                                            emp.has_account ? 'opacity-60 bg-surface-container-low/20' : 'hover:bg-primary/5 cursor-pointer',
                                            selectedIds.includes(emp.id) ? 'bg-primary/10' : ''
                                        ]"
                                    >
                                        <td class="p-3 text-center" @click.stop>
                                            <input
                                                type="checkbox"
                                                :value="emp.id"
                                                :checked="selectedIds.includes(emp.id)"
                                                :disabled="emp.has_account"
                                                @change="toggleSelect(emp.id)"
                                                class="rounded border-outline-variant text-primary focus:ring-primary/20 cursor-pointer disabled:opacity-40"
                                            />
                                        </td>
                                        <td class="p-3">
                                            <div class="font-bold text-on-surface text-xs sm:text-sm">
                                                {{ emp.nama }}
                                            </div>
                                            <div v-if="emp.nipy" class="text-[11px] text-on-surface-variant font-mono">
                                                NIPY: {{ emp.nipy }}
                                            </div>
                                        </td>
                                        <td class="p-3">
                                            <div class="font-semibold text-primary text-xs">
                                                {{ emp.jabatan }}
                                            </div>
                                            <div class="text-[11px] text-on-surface-variant">
                                                {{ emp.unit_kerja }}
                                            </div>
                                        </td>
                                        <td class="p-3">
                                            <div class="text-on-surface font-mono font-bold text-xs">
                                                {{ emp.default_password }}
                                            </div>
                                            <div class="text-[10px] text-on-surface-variant">
                                                {{ emp.tanggal_lahir_formatted }}
                                            </div>
                                        </td>
                                        <td class="p-3 text-center">
                                            <span
                                                v-if="emp.has_account"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-secondary-container/40 text-on-secondary-container border border-secondary-container/60"
                                            >
                                                <span class="material-symbols-outlined text-[13px]">check_circle</span>
                                                Sudah Ada Akun
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200"
                                            >
                                                Belum Ada
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="filteredEmployees.length === 0">
                                        <td colspan="5" class="p-8 text-center text-on-surface-variant italic">
                                            <span class="material-symbols-outlined text-3xl text-outline/40 block mb-1">person_search</span>
                                            Tidak ada data pegawai yang cocok dengan filter pencarian.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card View -->
                        <div class="sm:hidden space-y-2">
                            <label
                                v-if="selectableEmployees.length > 0"
                                class="flex items-center justify-between px-3 py-2 rounded-xl bg-surface-container-low border border-outline-variant/50 text-xs font-bold text-on-surface cursor-pointer"
                            >
                                <span class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        :checked="isAllSelected"
                                        @change="toggleSelectAll"
                                        class="rounded border-outline-variant text-primary focus:ring-primary/20 cursor-pointer"
                                    />
                                    <span>Pilih Semua ({{ selectableEmployees.length }})</span>
                                </span>
                                <span class="text-[11px] font-semibold text-primary">
                                    {{ selectedIds.length }} dipilih
                                </span>
                            </label>

                            <div
                                v-for="emp in filteredEmployees"
                                :key="'mob_' + emp.id"
                                @click="!emp.has_account && toggleSelect(emp.id)"
                                class="p-3 rounded-2xl border transition-colors bg-white"
                                :class="[
                                    emp.has_account ? 'opacity-60 border-outline-variant/40 bg-surface-container-low/30' : 'border-outline-variant/60 cursor-pointer active:bg-primary/5',
                                    selectedIds.includes(emp.id) ? 'border-primary bg-primary/5 ring-1 ring-primary/20' : ''
                                ]"
                            >
                                <div class="flex items-start gap-2.5">
                                    <input
                                        type="checkbox"
                                        :value="emp.id"
                                        :checked="selectedIds.includes(emp.id)"
                                        :disabled="emp.has_account"
                                        @click.stop
                                        @change="toggleSelect(emp.id)"
                                        class="mt-0.5 rounded border-outline-variant text-primary focus:ring-primary/20 cursor-pointer disabled:opacity-40 shrink-0"
                                    />
                                    <div class="min-w-0 flex-1 space-y-1">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <div class="font-bold text-on-surface text-xs leading-snug truncate">
                                                    {{ emp.nama }}
                                                </div>
                                                <div v-if="emp.nipy" class="text-[10.5px] text-on-surface-variant font-mono">
                                                    NIPY: {{ emp.nipy }}
                                                </div>
                                            </div>
                                            <span
                                                v-if="emp.has_account"
                                                class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-secondary-container/40 text-on-secondary-container border border-secondary-container/60 shrink-0"
                                            >
                                                Sudah Ada
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 shrink-0"
                                            >
                                                Belum Ada
                                            </span>
                                        </div>

                                        <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 text-[11px] pt-1 border-t border-outline-variant/30">
                                            <div class="min-w-0 truncate">
                                                <span class="font-semibold text-primary">{{ emp.jabatan }}</span>
                                                <span class="text-on-surface-variant"> • {{ emp.unit_kerja }}</span>
                                            </div>
                                            <div class="font-mono font-bold text-on-surface shrink-0">
                                                Pass: {{ emp.default_password }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="filteredEmployees.length === 0" class="py-8 text-center text-on-surface-variant italic text-xs">
                                <span class="material-symbols-outlined text-3xl text-outline/40 block mb-1">person_search</span>
                                Tidak ada data pegawai yang cocok dengan filter pencarian.
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-4 py-3 sm:px-6 sm:py-4 border-t border-outline-variant/30 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 sm:gap-3 bg-surface-container-low/30 shrink-0">
                        <div class="text-[11px] sm:text-xs text-on-surface-variant text-center sm:text-left">
                            <strong>{{ selectedIds.length }}</strong> pegawai dipilih dari {{ unassignedCount }} yang belum memiliki akun
                        </div>

                        <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 sm:gap-3 w-full sm:w-auto">
                            <button
                                type="button"
                                @click="emit('close')"
                                class="w-full sm:w-auto px-4 py-2.5 rounded-xl font-semibold text-xs sm:text-sm text-on-surface-variant bg-surface-container-low sm:bg-transparent hover:bg-surface-variant hover:text-on-surface transition-colors cursor-pointer text-center"
                                :disabled="form.processing"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                @click="submit"
                                :disabled="form.processing || selectedIds.length === 0"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3 sm:px-5 py-2.5 rounded-xl font-semibold text-xs sm:text-sm bg-primary text-on-primary hover:bg-primary-container hover:text-primary transition-colors cursor-pointer shadow-xs shadow-primary/20 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
                            >
                                <span v-if="form.processing" class="material-symbols-outlined text-[16px] sm:text-[18px] animate-spin">sync</span>
                                <span v-else class="material-symbols-outlined text-[16px] sm:text-[18px]">auto_mode</span>
                                <span>{{ form.processing ? 'Memproses...' : `Generate ${selectedIds.length} Akun` }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
