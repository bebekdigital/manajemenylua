<script setup>
import { computed } from 'vue';
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
        default: 10,
    },
    totalEntries: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['page-change', 'update:per-page']);

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

function getStatusColor(status) {
    const map = {
        'Aktif': 'bg-emerald-100 text-emerald-700',
        'Non-Aktif': 'bg-slate-200 text-slate-500',
        'Pensiun': 'bg-amber-100 text-amber-700',
        'Cuti': 'bg-blue-100 text-blue-700',
    };
    return map[status] ?? 'bg-outline-variant/10 text-on-surface-variant';
}

function getJenjangColor(jenjang) {
    const map = {
        'GTY': 'bg-primary/10 text-primary',
        'GTT': 'bg-secondary/10 text-secondary',
        'PTY': 'bg-tertiary/10 text-tertiary',
        'PTT': 'bg-orange-100 text-orange-700',
    };
    return map[jenjang] ?? 'bg-outline-variant/10 text-on-surface-variant';
}
</script>

<template>
    <div class="bg-surface-container-lowest rounded-xl border border-primary/10 shadow-[0px_4px_20px_rgba(0,40,20,0.08)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-emerald-700 bg-emerald-600">
                        <th class="p-2.5 sm:p-4 text-[12px] sm:text-sm font-semibold text-white uppercase tracking-wider w-10 sm:w-12">No</th>
                        <th class="p-2.5 sm:p-4 text-[12px] sm:text-sm font-semibold text-white uppercase tracking-wider">NIPY</th>
                        <th class="p-2.5 sm:p-4 text-[12px] sm:text-sm font-semibold text-white uppercase tracking-wider">Nama Lengkap</th>
                        <th class="p-2.5 sm:p-4 text-[12px] sm:text-sm font-semibold text-white uppercase tracking-wider w-10 sm:w-14">JK</th>
                        <th class="p-2.5 sm:p-4 text-[12px] sm:text-sm font-semibold text-white uppercase tracking-wider">TTL</th>
                        <th class="p-2.5 sm:p-4 text-[12px] sm:text-sm font-semibold text-white uppercase tracking-wider">Unit</th>
                        <th class="p-2.5 sm:p-4 text-[12px] sm:text-sm font-semibold text-white uppercase tracking-wider">Jabatan</th>
                        <th class="p-2.5 sm:p-4 text-[12px] sm:text-sm font-semibold text-white uppercase tracking-wider">Jenjang</th>
                        <th class="p-2.5 sm:p-4 text-[12px] sm:text-sm font-semibold text-white uppercase tracking-wider">Status</th>
                        <th class="p-2.5 sm:p-4 text-[12px] sm:text-sm font-semibold text-white uppercase tracking-wider">No WA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    <tr
                        v-for="(emp, index) in employees"
                        :key="emp.nipy"
                        class="hover:bg-surface-container-high/50 transition-colors group"
                    >
                        <!-- No -->
                        <td class="p-2.5 sm:p-4 text-[12px] sm:text-sm text-on-surface-variant">
                            {{ startEntry + index }}
                        </td>
                        <!-- NIPY -->
                        <td class="p-2.5 sm:p-4 text-[12px] sm:text-sm text-on-surface-variant font-mono whitespace-nowrap">
                            {{ emp.nipy }}
                        </td>
                        <!-- Nama Lengkap + eye icon -->
                        <td class="p-2.5 sm:p-4">
                            <div class="flex items-center gap-2">
                                <Link
                                    :href="`/staff/${emp.nipy}`"
                                    class="inline-flex items-center justify-center p-1 sm:p-1.5 text-primary bg-primary/10 hover:bg-primary/20 rounded-md transition-colors shrink-0"
                                    title="Detail Pegawai"
                                >
                                    <span class="material-symbols-outlined text-[16px] sm:text-[18px]">visibility</span>
                                </Link>
                                <span class="text-[12px] sm:text-sm font-semibold text-on-surface whitespace-nowrap">{{ emp.nama }}</span>
                            </div>
                        </td>
                        <!-- JK -->
                        <td class="p-2.5 sm:p-4 text-[12px] sm:text-sm text-on-surface text-center">
                            {{ emp.jk || '-' }}
                        </td>
                        <!-- TTL -->
                        <td class="p-2.5 sm:p-4 text-[12px] sm:text-sm text-on-surface-variant whitespace-nowrap">
                            {{ emp.ttl || '-' }}
                        </td>
                        <!-- Unit -->
                        <td class="p-2.5 sm:p-4 text-[12px] sm:text-sm text-on-surface-variant whitespace-nowrap">
                            {{ emp.unit_kerja || '-' }}
                        </td>
                        <!-- Jabatan -->
                        <td class="p-2.5 sm:p-4 text-[12px] sm:text-sm text-on-surface-variant whitespace-nowrap">
                            {{ emp.jabatan || '-' }}
                        </td>
                        <!-- Jenjang Kepegawaian -->
                        <td class="p-2.5 sm:p-4 text-center">
                            <span
                                v-if="emp.jenjang_kepegawaian"
                                :class="['inline-flex items-center justify-center px-1.5 py-0.5 sm:px-2 sm:py-0.5 rounded-md text-[11px] sm:text-xs font-semibold', getJenjangColor(emp.jenjang_kepegawaian)]"
                            >
                                {{ emp.jenjang_kepegawaian }}
                            </span>
                            <span v-else class="text-[12px] sm:text-sm text-on-surface-variant">-</span>
                        </td>
                        <!-- Status Keaktifan -->
                        <td class="p-2.5 sm:p-4 text-center">
                            <span
                                v-if="emp.status_keaktifan"
                                :class="['inline-flex items-center gap-1 px-1.5 py-0.5 sm:px-2 sm:py-0.5 rounded-full text-[11px] sm:text-xs font-semibold', getStatusColor(emp.status_keaktifan)]"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-current shrink-0"></span>
                                {{ emp.status_keaktifan }}
                            </span>
                            <span v-else class="text-[12px] sm:text-sm text-on-surface-variant">-</span>
                        </td>
                        <!-- No WA -->
                        <td class="p-2.5 sm:p-4 text-[12px] sm:text-sm text-on-surface-variant whitespace-nowrap">
                            {{ emp.no_wa || '-' }}
                        </td>
                    </tr>

                    <!-- Empty state -->
                    <tr v-if="employees.length === 0">
                        <td colspan="10" class="p-12 text-center">
                            <span class="material-symbols-outlined text-5xl text-outline-variant mb-4 block">search_off</span>
                            <p class="font-body-lg text-body-lg text-on-surface-variant">Tidak ada data pegawai ditemukan.</p>
                            <p class="font-body-sm text-body-sm text-outline mt-1">Coba ubah filter atau kata kunci pencarian.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <div class="p-3 sm:p-4 border-t border-outline-variant/30 flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4 bg-surface-container-lowest">
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-[12px] sm:text-sm sm:font-body-sm text-on-surface-variant">
                    <template v-if="perPage === 0">
                        Menampilkan seluruh <strong class="text-on-surface font-semibold">{{ totalEntries }}</strong> data
                    </template>
                    <template v-else-if="totalEntries === 0">
                        Menampilkan <strong class="text-on-surface font-semibold">0</strong> data
                    </template>
                    <template v-else>
                        Menampilkan <strong class="text-on-surface font-semibold">{{ startEntry }} - {{ endEntry }}</strong> dari <strong class="text-on-surface font-semibold">{{ totalEntries }}</strong> data
                    </template>
                </span>
            </div>

            <!-- Page Buttons -->
            <div v-if="perPage > 0 && totalPages > 1" class="flex items-center gap-1.5 sm:gap-1.5">
                <button
                    :disabled="currentPage <= 1"
                    class="p-1.5 sm:p-2 rounded-md sm:rounded-lg border border-outline-variant/60 text-on-surface-variant hover:border-primary hover:text-primary transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                    @click="$emit('page-change', currentPage - 1)"
                    title="Halaman sebelumnya"
                >
                    <span class="material-symbols-outlined text-[18px] sm:text-sm">chevron_left</span>
                </button>

                <template v-for="(page, i) in visiblePages" :key="i">
                    <span v-if="page === '...'" class="px-2 py-1 text-on-surface-variant text-[12px] sm:text-xs font-bold">...</span>
                    <button
                        v-else
                        :class="[
                            'px-2.5 py-1 sm:px-3 sm:py-1 rounded-md sm:rounded-lg text-[12px] sm:text-sm font-semibold transition-all cursor-pointer',
                            page === currentPage
                                ? 'bg-primary text-on-primary shadow-xs'
                                : 'border border-outline-variant/60 text-on-surface-variant hover:border-primary hover:text-primary'
                        ]"
                        @click="$emit('page-change', page)"
                    >
                        {{ page }}
                    </button>
                </template>

                <button
                    :disabled="currentPage >= totalPages"
                    class="p-1.5 sm:p-2 rounded-md sm:rounded-lg border border-outline-variant/60 text-on-surface-variant hover:border-primary hover:text-primary transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                    @click="$emit('page-change', currentPage + 1)"
                    title="Halaman berikutnya"
                >
                    <span class="material-symbols-outlined text-[18px] sm:text-sm">chevron_right</span>
                </button>
            </div>
        </div>
    </div>
</template>
