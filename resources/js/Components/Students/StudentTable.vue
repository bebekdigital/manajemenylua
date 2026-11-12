<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    students: {
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
});

const emit = defineEmits(['page-change', 'update:per-page', 'sort-change', 'show-detail']);

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
    if (props.sortBy === field) {
        emit('sort-change', {
            field,
            direction: props.sortDirection === 'asc' ? 'desc' : 'asc',
        });
    } else {
        emit('sort-change', {
            field,
            direction: 'asc',
        });
    }
}

/**
 * Builds a combined address string from address parts
 */
function getAlamatLengkap(s) {
    return [s.jalan, s.rt_rw, s.dusun, s.desa, s.kecamatan, s.kabupaten, s.provinsi]
        .filter(Boolean)
        .join(', ');
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
                        <th class="py-3.5 px-3 w-12 text-center text-[11.5px] font-bold uppercase tracking-wider select-none">
                            No
                        </th>

                        <!-- NISN -->
                        <th
                            @click="handleSort('nisn')"
                            :class="[
                                'py-3.5 px-3 text-[11.5px] font-bold uppercase tracking-wider cursor-pointer transition-colors select-none',
                                sortBy === 'nisn' ? 'text-yellow-200' : 'text-white hover:text-yellow-200'
                            ]"
                        >
                            <div class="inline-flex items-center gap-1">
                                <span>NISN</span>
                                <span class="material-symbols-outlined text-[14px]" :class="sortBy === 'nisn' ? 'text-yellow-300' : 'text-emerald-300/60'">
                                    {{ sortBy === 'nisn' ? (sortDirection === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' }}
                                </span>
                            </div>
                        </th>

                        <!-- Nama Siswa -->
                        <th
                            @click="handleSort('nama')"
                            :class="[
                                'py-3.5 px-3 text-[11.5px] font-bold uppercase tracking-wider cursor-pointer transition-colors select-none',
                                sortBy === 'nama' ? 'text-yellow-200' : 'text-white hover:text-yellow-200'
                            ]"
                        >
                            <div class="inline-flex items-center gap-1">
                                <span>Nama Siswa</span>
                                <span class="material-symbols-outlined text-[14px]" :class="sortBy === 'nama' ? 'text-yellow-300' : 'text-emerald-300/60'">
                                    {{ sortBy === 'nama' ? (sortDirection === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' }}
                                </span>
                            </div>
                        </th>

                        <!-- NIPD -->
                        <th
                            @click="handleSort('nipd')"
                            :class="[
                                'py-3.5 px-3 text-[11.5px] font-bold uppercase tracking-wider cursor-pointer transition-colors select-none',
                                sortBy === 'nipd' ? 'text-yellow-200' : 'text-white hover:text-yellow-200'
                            ]"
                        >
                            <div class="inline-flex items-center gap-1">
                                <span>NIPD</span>
                                <span class="material-symbols-outlined text-[14px]" :class="sortBy === 'nipd' ? 'text-yellow-300' : 'text-emerald-300/60'">
                                    {{ sortBy === 'nipd' ? (sortDirection === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' }}
                                </span>
                            </div>
                        </th>

                        <!-- Jenjang -->
                        <th
                            @click="handleSort('jenjang')"
                            :class="[
                                'py-3.5 px-3 text-[11.5px] font-bold uppercase tracking-wider cursor-pointer transition-colors select-none',
                                sortBy === 'jenjang' ? 'text-yellow-200' : 'text-white hover:text-yellow-200'
                            ]"
                        >
                            <div class="inline-flex items-center gap-1">
                                <span>Jenjang</span>
                                <span class="material-symbols-outlined text-[14px]" :class="sortBy === 'jenjang' ? 'text-yellow-300' : 'text-emerald-300/60'">
                                    {{ sortBy === 'jenjang' ? (sortDirection === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' }}
                                </span>
                            </div>
                        </th>

                        <!-- Unit -->
                        <th
                            @click="handleSort('unit')"
                            :class="[
                                'py-3.5 px-3 text-[11.5px] font-bold uppercase tracking-wider cursor-pointer transition-colors select-none',
                                sortBy === 'unit' ? 'text-yellow-200' : 'text-white hover:text-yellow-200'
                            ]"
                        >
                            <div class="inline-flex items-center gap-1">
                                <span>Unit</span>
                                <span class="material-symbols-outlined text-[14px]" :class="sortBy === 'unit' ? 'text-yellow-300' : 'text-emerald-300/60'">
                                    {{ sortBy === 'unit' ? (sortDirection === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' }}
                                </span>
                            </div>
                        </th>

                        <!-- Angkatan -->
                        <th
                            @click="handleSort('angkatan')"
                            :class="[
                                'py-3.5 px-3 text-[11.5px] font-bold uppercase tracking-wider text-center cursor-pointer transition-colors select-none',
                                sortBy === 'angkatan' ? 'text-yellow-200' : 'text-white hover:text-yellow-200'
                            ]"
                        >
                            <div class="inline-flex items-center justify-center gap-1">
                                <span>Angkatan</span>
                                <span class="material-symbols-outlined text-[14px]" :class="sortBy === 'angkatan' ? 'text-yellow-300' : 'text-emerald-300/60'">
                                    {{ sortBy === 'angkatan' ? (sortDirection === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' }}
                                </span>
                            </div>
                        </th>

                        <!-- JK -->
                        <th class="py-3.5 px-3 w-14 text-center text-[11.5px] font-bold uppercase tracking-wider select-none">
                            JK
                        </th>

                        <!-- TTL -->
                        <th class="py-3.5 px-3 text-[11.5px] font-bold uppercase tracking-wider select-none">
                            TTL
                        </th>

                        <!-- Kontak WhatsApp -->
                        <th
                            @click="handleSort('no_wa')"
                            :class="[
                                'py-3.5 px-3 text-[11.5px] font-bold uppercase tracking-wider cursor-pointer transition-colors select-none',
                                sortBy === 'no_wa' ? 'text-yellow-200' : 'text-white hover:text-yellow-200'
                            ]"
                        >
                            <div class="inline-flex items-center gap-1">
                                <span>No. WhatsApp</span>
                                <span class="material-symbols-outlined text-[14px]" :class="sortBy === 'no_wa' ? 'text-yellow-300' : 'text-emerald-300/60'">
                                    {{ sortBy === 'no_wa' ? (sortDirection === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' }}
                                </span>
                            </div>
                        </th>

                        <!-- Alamat Lengkap -->
                        <th class="py-3.5 pl-3 pr-4 text-[11.5px] font-bold uppercase tracking-wider select-none">
                            Alamat Lengkap
                        </th>
                    </tr>
                </thead>

                <!-- Table Body: Garis kisi horizontal ditebalkan (divide-y-2 divide-slate-200) -->
                <tbody class="divide-y-2 divide-slate-200">
                    <tr
                        v-for="(student, index) in students"
                        :key="student.nisn || index"
                        class="hover:bg-slate-50/80 transition-colors group"
                    >
                        <!-- No -->
                        <td class="py-3.5 px-3 text-[12px] text-slate-500 font-medium text-center align-middle">
                            {{ startEntry + index }}
                        </td>

                        <!-- NISN -->
                        <td class="py-3.5 px-3 font-mono text-[12px] text-slate-700 font-semibold align-middle whitespace-nowrap">
                            {{ student.nisn }}
                        </td>

                        <!-- Nama Siswa (Link bersih tanpa ikon mata yang mengganggu) -->
                        <td class="py-3.5 px-3 align-middle">
                            <Link
                                :href="`/students/${student.nisn}`"
                                class="font-bold text-slate-900 hover:text-emerald-700 transition-colors text-[13px] whitespace-nowrap block"
                                title="Buka Detail Siswa"
                            >
                                {{ student.nama }}
                            </Link>
                        </td>

                        <!-- NIPD -->
                        <td class="py-3.5 px-3 font-mono text-[12px] text-slate-600 font-medium align-middle whitespace-nowrap">
                            {{ student.nipd || '-' }}
                        </td>

                        <!-- Jenjang -->
                        <td class="py-3.5 px-3 align-middle whitespace-nowrap">
                            <span
                                v-if="student.jenjang"
                                class="inline-flex items-center px-2 py-0.5 rounded-md text-[11.5px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200"
                            >
                                {{ student.jenjang }}
                            </span>
                            <span v-else class="text-slate-400 text-[12px]">-</span>
                        </td>

                        <!-- Unit -->
                        <td class="py-3.5 px-3 text-[12.5px] font-medium text-slate-700 align-middle whitespace-nowrap">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span>{{ student.unit || '-' }}</span>
                                <span
                                    v-if="student.program && student.program !== 'Umum'"
                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[10.5px] font-bold"
                                    :class="student.program.toLowerCase().includes('boarding') ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-emerald-100 text-emerald-800 border border-emerald-200'"
                                >
                                    {{ student.program }}
                                </span>
                            </div>
                        </td>

                        <!-- Angkatan -->
                        <td class="py-3.5 px-3 align-middle text-center whitespace-nowrap">
                            <span
                                v-if="student.angkatan"
                                class="inline-flex items-center justify-center px-2 py-0.5 bg-slate-100 text-slate-700 border border-slate-200 rounded-md text-[11px] font-semibold"
                            >
                                {{ student.angkatan }}
                            </span>
                            <span v-else class="text-slate-400 text-[12px]">-</span>
                        </td>

                        <!-- JK (Warna Badge Dibedakan: L = Biru, P = Pink/Fuchsia) -->
                        <td class="py-3.5 px-3 align-middle text-center whitespace-nowrap">
                            <span
                                v-if="student.jk"
                                :class="[
                                    'inline-flex items-center justify-center w-6 h-6 rounded-md text-[11px] font-bold mx-auto',
                                    student.jk === 'L'
                                        ? 'bg-blue-50 text-blue-700 border border-blue-200'
                                        : student.jk === 'P'
                                            ? 'bg-rose-50 text-rose-700 border border-rose-200'
                                            : 'bg-slate-100 text-slate-600 border border-slate-200'
                                ]"
                                :title="student.jk === 'L' ? 'Laki-laki' : (student.jk === 'P' ? 'Perempuan' : student.jk)"
                            >
                                {{ student.jk }}
                            </span>
                            <span v-else class="text-slate-400 text-[12px]">-</span>
                        </td>

                        <!-- TTL -->
                        <td class="py-3.5 px-3 text-[12.5px] text-slate-600 align-middle whitespace-nowrap">
                            {{ student.ttl || '-' }}
                        </td>

                        <!-- No WA -->
                        <td class="py-3.5 px-3 text-[12.5px] font-medium text-slate-800 align-middle whitespace-nowrap">
                            {{ student.no_wa || '-' }}
                        </td>

                        <!-- Alamat Lengkap -->
                        <td class="py-3.5 pl-3 pr-4 text-[12.5px] text-slate-600 align-middle max-w-[200px] truncate" :title="getAlamatLengkap(student)">
                            {{ getAlamatLengkap(student) || '-' }}
                        </td>
                    </tr>

                    <!-- Empty state -->
                    <tr v-if="students.length === 0">
                        <td colspan="11" class="py-12 text-center">
                            <span class="material-symbols-outlined text-4xl text-slate-300 mb-2 block">person_search</span>
                            <p class="text-[13.5px] font-medium text-slate-600">Tidak ada data siswa ditemukan.</p>
                            <p class="text-[12px] text-slate-400 mt-0.5">Coba sesuaikan kata kunci pencarian atau filter yang dipilih.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer: Selaras dengan Desain Data Pegawai -->
        <div class="p-3.5 sm:p-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 bg-white">
            <div class="text-[12.5px] sm:text-[13px] text-slate-500 font-medium">
                <template v-if="perPage === 0">
                    Menampilkan seluruh <strong class="text-slate-900 font-bold">{{ totalEntries }}</strong> data siswa
                </template>
                <template v-else-if="totalEntries === 0">
                    Menampilkan <strong class="text-slate-900 font-bold">0</strong> data siswa
                </template>
                <template v-else>
                    Menampilkan <strong class="text-slate-900 font-bold">{{ startEntry }} - {{ endEntry }}</strong> dari <strong class="text-slate-900 font-bold">{{ totalEntries }}</strong> data siswa
                </template>
            </div>

            <!-- Page Buttons -->
            <div v-if="perPage > 0 && totalPages > 1" class="flex items-center gap-1.5">
                <button
                    :disabled="currentPage <= 1"
                    @click="$emit('page-change', currentPage - 1)"
                    class="w-8 h-8 rounded-lg border border-slate-200 text-slate-600 hover:border-emerald-600 hover:text-emerald-900 flex items-center justify-center transition-colors disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer"
                    title="Halaman sebelumnya"
                >
                    <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                </button>

                <template v-for="(p, i) in visiblePages" :key="i">
                    <span v-if="p === '...'" class="w-8 h-8 flex items-center justify-center text-slate-400 text-xs font-bold">...</span>
                    <button
                        v-else
                        @click="$emit('page-change', p)"
                        :class="[
                            'w-8 h-8 rounded-lg text-[12.5px] font-bold transition-all cursor-pointer flex items-center justify-center',
                            p === currentPage
                                ? 'bg-emerald-900 text-yellow-200 shadow-xs'
                                : 'border border-slate-200 text-slate-600 hover:border-emerald-600 hover:text-emerald-900'
                        ]"
                    >
                        {{ p }}
                    </button>
                </template>

                <button
                    :disabled="currentPage >= totalPages"
                    @click="$emit('page-change', currentPage + 1)"
                    class="w-8 h-8 rounded-lg border border-slate-200 text-slate-600 hover:border-emerald-600 hover:text-emerald-900 flex items-center justify-center transition-colors disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer"
                    title="Halaman berikutnya"
                >
                    <span class="material-symbols-outlined text-[16px]">chevron_right</span>
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
