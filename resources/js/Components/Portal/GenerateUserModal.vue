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
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 overflow-y-auto">
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity"
                    @click="emit('close')"
                ></div>

                <!-- Modal Dialog -->
                <div class="bg-surface-container-lowest rounded-3xl shadow-2xl border border-outline-variant/30 overflow-hidden w-full max-w-3xl relative z-10 my-auto flex flex-col max-h-[90vh]">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-outline-variant/30 flex items-center justify-between bg-surface-container-low/40 shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                                <span class="material-symbols-outlined text-2xl">auto_mode</span>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-on-surface">Generate Akun Pegawai Otomatis</h3>
                                <p class="text-xs text-on-surface-variant">Pilih pegawai untuk dibuatkan akun login sistem</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="emit('close')"
                            class="text-on-surface-variant hover:text-on-surface p-2 rounded-full hover:bg-surface-variant transition-colors cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <!-- Rules Info Banner -->
                    <div class="p-4 mx-6 mt-4 rounded-2xl bg-primary/5 border border-primary/20 text-xs text-on-surface shrink-0 space-y-1.5">
                        <div class="font-bold text-primary flex items-center gap-1.5 text-sm">
                            <span class="material-symbols-outlined text-[18px]">info</span>
                            Aturan Generate Akun Otomatis
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-1 text-on-surface-variant">
                            <div class="bg-white/80 p-2.5 rounded-xl border border-primary/10">
                                <div class="font-bold text-on-surface text-[11px] mb-0.5">Username</div>
                                <div class="text-[11px]">Kata pertama + kata terakhir + 2 angka acak (misal: <code class="bg-surface-container-high px-1 py-0.5 rounded text-primary font-mono font-bold">ahmadrahman42</code>)</div>
                            </div>
                            <div class="bg-white/80 p-2.5 rounded-xl border border-primary/10">
                                <div class="font-bold text-on-surface text-[11px] mb-0.5">Password Default</div>
                                <div class="text-[11px]">Tanggal lahir format <code class="bg-surface-container-high px-1 py-0.5 rounded text-primary font-mono font-bold">YYYYMMDD</code> (misal: <code class="bg-surface-container-high px-1 py-0.5 rounded font-mono">19880521</code>)</div>
                            </div>
                            <div class="bg-white/80 p-2.5 rounded-xl border border-primary/10">
                                <div class="font-bold text-on-surface text-[11px] mb-0.5">Role / Kewenangan</div>
                                <div class="text-[11px]">Otomatis diambil dari <strong>Jabatan Penugasan</strong> pegawai di data kepegawaian.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter & Search Toolbar -->
                    <div class="px-6 py-3 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
                        <!-- Mode Tabs -->
                        <div class="flex p-1 bg-surface-container-low rounded-xl border border-outline-variant/40 w-full sm:w-auto">
                            <button
                                type="button"
                                @click="filterMode = 'unregistered'"
                                class="flex-1 sm:flex-initial py-1.5 px-3 rounded-lg text-xs font-semibold transition-all cursor-pointer text-center"
                                :class="filterMode === 'unregistered' ? 'bg-surface-container-lowest text-primary shadow-xs' : 'text-on-surface-variant hover:text-on-surface'"
                            >
                                Belum Punya Akun ({{ unassignedCount }})
                            </button>
                            <button
                                type="button"
                                @click="filterMode = 'all'"
                                class="flex-1 sm:flex-initial py-1.5 px-3 rounded-lg text-xs font-semibold transition-all cursor-pointer text-center"
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
                    <div v-if="form.errors.employee_ids" class="px-6 py-1">
                        <div class="p-2.5 rounded-xl bg-error/10 text-error text-xs font-medium border border-error/20">
                            {{ form.errors.employee_ids }}
                        </div>
                    </div>

                    <!-- Employee List Table -->
                    <div class="flex-1 overflow-y-auto px-6 py-2 min-h-[220px]">
                        <div class="rounded-2xl border border-outline-variant/60 overflow-hidden bg-white">
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
                                        <th class="p-3">Jabatan & Unit</th>
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
                                                {{ emp.nama_lengkap_gelar || emp.nama }}
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
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-outline-variant/30 flex items-center justify-between bg-surface-container-low/30 shrink-0">
                        <div class="text-xs text-on-surface-variant">
                            <strong>{{ selectedIds.length }}</strong> pegawai dipilih dari {{ unassignedCount }} yang belum memiliki akun
                        </div>

                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="emit('close')"
                                class="px-4 py-2.5 rounded-xl font-semibold text-sm text-on-surface-variant hover:bg-surface-variant hover:text-on-surface transition-colors cursor-pointer"
                                :disabled="form.processing"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                @click="submit"
                                :disabled="form.processing || selectedIds.length === 0"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm bg-primary text-on-primary hover:bg-primary-container hover:text-primary transition-colors cursor-pointer shadow-xs shadow-primary/20 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="form.processing" class="material-symbols-outlined text-[18px] animate-spin">sync</span>
                                <span v-else class="material-symbols-outlined text-[18px]">auto_mode</span>
                                <span>{{ form.processing ? 'Memproses...' : `Generate ${selectedIds.length} Akun` }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
