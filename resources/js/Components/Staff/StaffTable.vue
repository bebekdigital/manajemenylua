<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    employees: {
        type: Array,
        required: true,
    },
    currentPage: {
        type: Number,
        default: 1,
    },
    perPage: {
        type: Number,
        default: 15,
    },
    totalEntries: {
        type: Number,
        default: 0,
    },
    sortBy: {
        type: String,
        default: 'nama',
    },
    sortDirection: {
        type: String,
        default: 'asc',
    },
    canUpdate: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['page-change', 'update:per-page', 'sort-change']);

const selectedIds = ref([]);

const allSelected = computed(() => {
    if (props.employees.length === 0) return false;
    return props.employees.every(emp => selectedIds.value.includes(emp.id));
});

const someSelected = computed(() => {
    if (props.employees.length === 0) return false;
    const count = props.employees.filter(emp => selectedIds.value.includes(emp.id)).length;
    return count > 0 && count < props.employees.length;
});

function toggleSelectAll() {
    if (allSelected.value) {
        selectedIds.value = selectedIds.value.filter(
            id => !props.employees.some(emp => emp.id === id)
        );
    } else {
        const currentIds = props.employees.map(emp => emp.id);
        selectedIds.value = [...new Set([...selectedIds.value, ...currentIds])];
    }
}

function toggleSelectRow(id) {
    const idx = selectedIds.value.indexOf(id);
    if (idx >= 0) {
        selectedIds.value.splice(idx, 1);
    } else {
        selectedIds.value.push(id);
    }
}

const totalPages = computed(() => {
    if (props.perPage === 0) return 1;
    return Math.max(1, Math.ceil(props.totalEntries / props.perPage));
});

const startEntry = computed(() => {
    if (props.totalEntries === 0) return 0;
    if (props.perPage === 0) return 1;
    return (props.currentPage - 1) * props.perPage + 1;
});

const endEntry = computed(() => {
    if (props.totalEntries === 0) return 0;
    if (props.perPage === 0) return props.totalEntries;
    return Math.min(props.currentPage * props.perPage, props.totalEntries);
});

const visiblePages = computed(() => {
    const pages = [];
    const total = totalPages.value;
    const current = props.currentPage;

    if (total <= 5) {
        for (let i = 1; i <= total; i++) pages.push(i);
    } else {
        pages.push(1);
        if (current > 3) pages.push('...');
        for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
            pages.push(i);
        }
        if (current < total - 2) pages.push('...');
        pages.push(total);
    }
    return pages;
});

function handleSort(field) {
    emit('sort-change', { field });
}

// Badge warna untuk Jenis Kelamin (L = Biru, P = Pink/Fuchsia)
function getJkBadgeClass(jk) {
    if (jk === 'L') {
        return 'bg-blue-50 text-blue-700 border border-blue-200';
    }
    if (jk === 'P') {
        return 'bg-rose-50 text-rose-700 border border-rose-200';
    }
    return 'bg-slate-100 text-slate-600 border border-slate-200';
}

// Badge warna untuk Jenjang Kepegawaian (GTY, GTT, PTT, PTY, dll)
function getJenjangBadgeClass(jenjang) {
    if (!jenjang) return 'bg-slate-100 text-slate-600 border border-slate-200';
    const j = jenjang.toUpperCase();
    if (j.startsWith('GTY')) {
        return 'bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold';
    }
    if (j.startsWith('GTT')) {
        return 'bg-teal-50 text-teal-800 border border-teal-200 font-semibold';
    }
    if (j.startsWith('PTY')) {
        return 'bg-indigo-50 text-indigo-800 border border-indigo-200 font-semibold';
    }
    if (j.startsWith('PTT')) {
        return 'bg-amber-50 text-amber-800 border border-amber-200 font-semibold';
    }
    return 'bg-slate-100 text-slate-700 border border-slate-200 font-medium';
}

function getStatusStyle(status) {
    const s = (status || 'Aktif').toLowerCase();
    if (s === 'aktif') {
        return {
            wrapper: 'text-emerald-700',
            dot: 'bg-emerald-600',
        };
    }
    if (s === 'pensiun') {
        return {
            wrapper: 'text-amber-800 bg-amber-50 px-2.5 py-0.5 rounded-md border border-amber-200',
            dot: 'bg-amber-500',
        };
    }
    return {
        wrapper: 'text-rose-700',
        dot: 'bg-rose-600',
    };
}
</script>

<template>
    <!-- Table Container: Radius disamakan dengan tombol search di atasnya (rounded-xl) & Outline presisi (border border-emerald-800) -->
    <div class="bg-white rounded-xl border border-emerald-800 shadow-[0_4px_25px_rgba(0,40,20,0.08)] overflow-hidden">
        <div class="overflow-x-auto custom-scrollbar-x">
            <table class="w-full text-left border-collapse">
                <!-- Table Header: Warna Hijau Tema Kontras Menarik -->
                <thead>
                    <tr class="bg-emerald-800 text-white border-b border-emerald-900">
                        <!-- No -->
                        <th class="py-3.5 pl-4 pr-2.5 w-14 text-center text-[11.5px] font-bold uppercase tracking-wider select-none text-white">
                            No
                        </th>

                        <!-- Pegawai (Nama & NIPY) -->
                        <th
                            @click="handleSort('nama')"
                            :class="[
                                'py-3.5 px-3 text-[11.5px] font-bold uppercase tracking-wider cursor-pointer transition-colors select-none',
                                sortBy === 'nama' ? 'text-yellow-200' : 'text-white hover:text-yellow-200'
                            ]"
                        >
                            <div class="inline-flex items-center gap-1">
                                <span>Pegawai</span>
                                <span class="material-symbols-outlined text-[14px]" :class="sortBy === 'nama' ? 'text-yellow-300' : 'text-emerald-300/60'">
                                    {{ sortBy === 'nama' ? (sortDirection === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' }}
                                </span>
                            </div>
                        </th>

                        <!-- Unit Kerja -->
                        <th
                            @click="handleSort('unit_kerja')"
                            :class="[
                                'py-3.5 px-3 text-[11.5px] font-bold uppercase tracking-wider cursor-pointer transition-colors select-none',
                                sortBy === 'unit_kerja' ? 'text-yellow-200' : 'text-white hover:text-yellow-200'
                            ]"
                        >
                            <div class="inline-flex items-center gap-1">
                                <span>Unit Kerja</span>
                                <span class="material-symbols-outlined text-[14px]" :class="sortBy === 'unit_kerja' ? 'text-yellow-300' : 'text-emerald-300/60'">
                                    {{ sortBy === 'unit_kerja' ? (sortDirection === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' }}
                                </span>
                            </div>
                        </th>

                        <!-- Kolom Jenjang -->
                        <th
                            @click="handleSort('jenjang_kepegawaian')"
                            :class="[
                                'py-3.5 px-3 text-[11.5px] font-bold uppercase tracking-wider cursor-pointer transition-colors select-none',
                                sortBy === 'jenjang_kepegawaian' ? 'text-yellow-200' : 'text-white hover:text-yellow-200'
                            ]"
                        >
                            <div class="inline-flex items-center gap-1">
                                <span>Jenjang</span>
                                <span class="material-symbols-outlined text-[14px]" :class="sortBy === 'jenjang_kepegawaian' ? 'text-yellow-300' : 'text-emerald-300/60'">
                                    {{ sortBy === 'jenjang_kepegawaian' ? (sortDirection === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' }}
                                </span>
                            </div>
                        </th>

                        <!-- Jabatan -->
                        <th
                            @click="handleSort('jabatan')"
                            :class="[
                                'py-3.5 px-3 text-[11.5px] font-bold uppercase tracking-wider cursor-pointer transition-colors select-none',
                                sortBy === 'jabatan' ? 'text-yellow-200' : 'text-white hover:text-yellow-200'
                            ]"
                        >
                            <div class="inline-flex items-center gap-1">
                                <span>Jabatan</span>
                                <span class="material-symbols-outlined text-[14px]" :class="sortBy === 'jabatan' ? 'text-yellow-300' : 'text-emerald-300/60'">
                                    {{ sortBy === 'jabatan' ? (sortDirection === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' }}
                                </span>
                            </div>
                        </th>

                        <!-- Tempat, Tgl Lahir -->
                        <th class="py-3.5 px-3 text-[11.5px] font-bold text-white uppercase tracking-wider select-none">
                            Tempat, Tgl Lahir
                        </th>

                        <!-- Status -->
                        <th class="py-3.5 px-3 text-[11.5px] font-bold text-white uppercase tracking-wider select-none">
                            Status
                        </th>

                        <!-- Kontak WhatsApp -->
                        <th
                            @click="handleSort('no_wa')"
                            :class="[
                                'py-3.5 pl-3 pr-5 text-[11.5px] font-bold uppercase tracking-wider text-right cursor-pointer transition-colors select-none',
                                sortBy === 'no_wa' ? 'text-yellow-200' : 'text-white hover:text-yellow-200'
                            ]"
                        >
                            <div class="inline-flex items-center justify-end gap-1">
                                <span>No. WhatsApp</span>
                                <span class="material-symbols-outlined text-[14px]" :class="sortBy === 'no_wa' ? 'text-yellow-300' : 'text-emerald-300/60'">
                                    {{ sortBy === 'no_wa' ? (sortDirection === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' }}
                                </span>
                            </div>
                        </th>
                    </tr>
                </thead>

                <!-- Table Body: Garis kisi horizontal ditebalkan (divide-y-2 divide-slate-200) -->
                <tbody class="divide-y-2 divide-slate-200">
                    <tr
                        v-for="(emp, index) in employees"
                        :key="emp.id"
                        class="hover:bg-slate-50/80 transition-colors group"
                    >
                        <!-- No -->
                        <td class="py-3.5 pl-4 pr-2.5 text-[12px] text-slate-500 font-medium text-center align-middle whitespace-nowrap">
                            {{ startEntry + index }}
                        </td>

                        <!-- Pegawai: Nama Lengkap + NIPY Sub-text (Font NIPY lebih jelas & kontras) -->
                        <td class="py-3.5 px-3 align-middle">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <Link
                                            :href="`/staff/${emp.id}`"
                                            prefetch
                                            cache-for="1m"
                                            class="text-[13.5px] font-semibold text-slate-900 hover:text-emerald-800 transition-colors truncate"
                                        >
                                            {{ emp.nama }}
                                        </Link>
                                        <!-- Badge Jenis Kelamin dengan warna pembeda (Biru untuk L, Pink untuk P) -->
                                        <span
                                            v-if="emp.jk"
                                            :class="[
                                                'text-[10px] font-bold px-1.5 py-0.5 rounded shrink-0 leading-none',
                                                getJkBadgeClass(emp.jk)
                                            ]"
                                            :title="emp.jk === 'L' ? 'Laki-laki' : 'Perempuan'"
                                        >
                                            {{ emp.jk }}
                                        </span>
                                    </div>
                                    <!-- Font NIPY lebih jelas (slate-600 font-semibold) -->
                                    <div class="text-[12px] text-slate-600 font-mono font-medium mt-0.5 tracking-tight">
                                        {{ emp.nipy || 'NIPY -' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Unit Kerja (Tanpa Icon/Avatar) -->
                        <td class="py-3.5 px-3 align-middle whitespace-nowrap">
                            <span class="text-[13px] font-medium text-slate-700">
                                {{ emp.unit_kerja || '-' }}
                            </span>
                        </td>

                        <!-- Jenjang Kepegawaian (Soft Pill dengan warna berbeda per jenjang) -->
                        <td class="py-3.5 px-3 align-middle whitespace-nowrap">
                            <span
                                v-if="emp.jenjang_kepegawaian"
                                :class="[
                                    'inline-flex items-center justify-center px-2.5 py-1 rounded-md text-[12px]',
                                    getJenjangBadgeClass(emp.jenjang_kepegawaian)
                                ]"
                            >
                                {{ emp.jenjang_kepegawaian }}
                            </span>
                            <span v-else class="text-[13px] text-slate-400">-</span>
                        </td>

                        <!-- Jabatan (Tanpa Icon/Avatar) -->
                        <td class="py-3.5 px-3 align-middle whitespace-nowrap">
                            <span class="text-[13px] font-medium text-slate-700">
                                {{ emp.jabatan || '-' }}
                            </span>
                        </td>

                        <!-- Tempat, Tgl Lahir -->
                        <td class="py-3.5 px-3 align-middle whitespace-nowrap">
                            <span class="text-[13px] text-slate-600 font-medium">
                                {{ emp.ttl || '-' }}
                            </span>
                        </td>

                        <!-- Status -->
                        <td class="py-3.5 px-3 align-middle whitespace-nowrap">
                            <span
                                v-if="emp.status_keaktifan"
                                :class="[
                                    'inline-flex items-center gap-1.5 text-[12.5px] font-semibold',
                                    getStatusStyle(emp.status_keaktifan).wrapper
                                ]"
                            >
                                <span :class="['w-1.5 h-1.5 rounded-full shrink-0', getStatusStyle(emp.status_keaktifan).dot]"></span>
                                <span>{{ emp.status_keaktifan }}</span>
                            </span>
                            <span v-else class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-emerald-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 shrink-0"></span>
                                <span>Aktif</span>
                            </span>
                        </td>

                        <!-- No WA (Tanpa icon mata dan titik 3) -->
                        <td class="py-3.5 pl-3 pr-5 align-middle whitespace-nowrap text-right">
                            <span class="text-[13.5px] font-semibold text-slate-900 tabular-nums">
                                {{ emp.no_wa || '-' }}
                            </span>
                        </td>
                    </tr>

                    <!-- Empty state -->
                    <tr v-if="employees.length === 0">
                        <td colspan="8" class="py-14 px-6 text-center">
                            <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                                <span class="material-symbols-outlined text-2xl">search_off</span>
                            </div>
                            <p class="text-[14px] font-semibold text-slate-700">Tidak ada data pegawai ditemukan</p>
                            <p class="text-[12.5px] text-slate-400 mt-1">Coba sesuaikan filter atau kata kunci pencarian di atas.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <div class="px-5 py-3.5 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-50/50">
            <div class="flex items-center gap-3">
                <span class="text-[12.5px] text-slate-500">
                    <template v-if="perPage === 0">
                        Menampilkan seluruh <strong class="text-slate-800 font-semibold">{{ totalEntries }}</strong> pegawai
                    </template>
                    <template v-else-if="totalEntries === 0">
                        Menampilkan <strong class="text-slate-800 font-semibold">0</strong> pegawai
                    </template>
                    <template v-else>
                        Menampilkan <strong class="text-slate-800 font-semibold">{{ startEntry }}–{{ endEntry }}</strong> dari <strong class="text-slate-800 font-semibold">{{ totalEntries }}</strong> pegawai
                    </template>
                </span>
            </div>

            <!-- Page Buttons -->
            <div v-if="perPage > 0 && totalPages > 1" class="flex items-center gap-1">
                <button
                    :disabled="currentPage <= 1"
                    class="p-1.5 rounded-md border border-slate-300 bg-white text-slate-600 hover:border-emerald-700 hover:text-emerald-800 transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                    @click="$emit('page-change', currentPage - 1)"
                    title="Halaman sebelumnya"
                >
                    <span class="material-symbols-outlined text-[16px] block">chevron_left</span>
                </button>

                <template v-for="(page, i) in visiblePages" :key="i">
                    <span v-if="page === '...'" class="px-2 py-1 text-slate-400 text-[12px] font-bold">...</span>
                    <button
                        v-else
                        :class="[
                            'min-w-[30px] h-[30px] px-2 rounded-md text-[12.5px] font-semibold transition-all cursor-pointer',
                            page === currentPage
                                ? 'bg-emerald-900 text-yellow-200 shadow-2xs font-bold'
                                : 'bg-white border border-slate-300 text-slate-600 hover:border-emerald-700 hover:text-emerald-800'
                        ]"
                        @click="$emit('page-change', page)"
                    >
                        {{ page }}
                    </button>
                </template>

                <button
                    :disabled="currentPage >= totalPages"
                    class="p-1.5 rounded-md border border-slate-300 bg-white text-slate-600 hover:border-emerald-700 hover:text-emerald-800 transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                    @click="$emit('page-change', currentPage + 1)"
                    title="Halaman berikutnya"
                >
                    <span class="material-symbols-outlined text-[16px] block">chevron_right</span>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom Horizontal Scrollbar without arrow buttons */
.custom-scrollbar-x {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.custom-scrollbar-x::-webkit-scrollbar {
    height: 6px;
}

.custom-scrollbar-x::-webkit-scrollbar-button {
    display: none;
    width: 0;
    height: 0;
}

.custom-scrollbar-x::-webkit-scrollbar-track {
    background: #f8fafc;
}

.custom-scrollbar-x::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 9999px;
}

.custom-scrollbar-x::-webkit-scrollbar-thumb:hover {
    background-color: #047857;
}
</style>
