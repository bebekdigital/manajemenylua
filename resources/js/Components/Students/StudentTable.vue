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
        default: 10,
    },
    totalEntries: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['page-change', 'show-detail', 'update:per-page']);

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

/**
 * Returns initials from a student name (first 2 chars of first + last name)
 */
function getInitials(name) {
    const parts = name.split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
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
    <div class="bg-surface-container-lowest rounded-xl border border-primary/10 shadow-[0px_4px_20px_rgba(0,40,20,0.08)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-emerald-700 bg-emerald-600">
                        <th class="p-2.5 sm:p-4 text-[10px] sm:text-sm font-semibold text-white uppercase tracking-wider w-10 sm:w-12">No</th>
                        <th class="p-2.5 sm:p-4 text-[10px] sm:text-sm font-semibold text-white uppercase tracking-wider">NISN</th>
                        <th class="p-2.5 sm:p-4 text-[10px] sm:text-sm font-semibold text-white uppercase tracking-wider">Nama</th>
                        <th class="p-2.5 sm:p-4 text-[10px] sm:text-sm font-semibold text-white uppercase tracking-wider">NIPD</th>
                        <th class="p-2.5 sm:p-4 text-[10px] sm:text-sm font-semibold text-white uppercase tracking-wider">Jenjang</th>
                        <th class="p-2.5 sm:p-4 text-[10px] sm:text-sm font-semibold text-white uppercase tracking-wider">Unit</th>
                        <th class="p-2.5 sm:p-4 text-[10px] sm:text-sm font-semibold text-white uppercase tracking-wider">Program</th>
                        <th class="p-2.5 sm:p-4 text-[10px] sm:text-sm font-semibold text-white uppercase tracking-wider w-14 sm:w-16">Tingkat</th>
                        <th class="p-2.5 sm:p-4 text-[10px] sm:text-sm font-semibold text-white uppercase tracking-wider w-14 sm:w-16">Kelas</th>
                        <th class="p-2.5 sm:p-4 text-[10px] sm:text-sm font-semibold text-white uppercase tracking-wider w-10 sm:w-14">JK</th>
                        <th class="p-2.5 sm:p-4 text-[10px] sm:text-sm font-semibold text-white uppercase tracking-wider">TTL</th>
                        <th class="p-2.5 sm:p-4 text-[10px] sm:text-sm font-semibold text-white uppercase tracking-wider">No WA</th>
                        <th class="p-2.5 sm:p-4 text-[10px] sm:text-sm font-semibold text-white uppercase tracking-wider">Alamat Lengkap</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    <tr
                        v-for="(student, index) in students"
                        :key="student.nisn"
                        class="hover:bg-surface-container-high/50 transition-colors group"
                    >
                        <!-- No -->
                        <td class="p-2.5 sm:p-4 text-[11px] sm:text-sm text-on-surface-variant">
                            {{ startEntry + index }}
                        </td>
                        <!-- NISN -->
                        <td class="p-2.5 sm:p-4 text-[11px] sm:text-sm text-on-surface-variant font-mono">
                            {{ student.nisn }}
                        </td>
                        <!-- Nama -->
                        <td class="p-2.5 sm:p-4">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[11px] sm:text-sm font-semibold text-on-surface whitespace-nowrap">{{ student.nama }}</span>
                                <Link
                                    :href="`/students/${student.nisn}`"
                                    class="inline-flex items-center justify-center p-1 text-primary bg-primary/10 hover:bg-primary/20 rounded-md transition-colors"
                                    title="Detail Siswa"
                                >
                                    <span class="material-symbols-outlined text-[14px] sm:text-[16px]">visibility</span>
                                </Link>
                            </div>
                        </td>
                        <!-- NIPD -->
                        <td class="p-2.5 sm:p-4 text-[11px] sm:text-sm text-on-surface-variant font-mono">
                            {{ student.nipd }}
                        </td>
                        <!-- Jenjang -->
                        <td class="p-2.5 sm:p-4 text-[11px] sm:text-sm text-on-surface-variant">
                            {{ student.jenjang || '-' }}
                        </td>
                        <!-- Unit -->
                        <td class="p-2.5 sm:p-4 text-[11px] sm:text-sm text-on-surface-variant whitespace-nowrap">
                            {{ student.unit || '-' }}
                        </td>
                        <!-- Program -->
                        <td class="p-2.5 sm:p-4 text-center">
                            <span :class="[
                                'inline-flex items-center justify-center px-1.5 py-0.5 sm:px-2 sm:py-0.5 rounded-md text-[9px] sm:text-xs font-semibold',
                                student.program === 'Boarding' ? 'bg-error/10 text-error' :
                                student.program === 'Fullday' ? 'bg-secondary/10 text-secondary' :
                                'bg-outline-variant/10 text-on-surface-variant'
                            ]">
                                {{ student.program || 'Umum' }}
                            </span>
                        </td>
                        <!-- Tingkat -->
                        <td class="p-2.5 sm:p-4 text-center">
                            <span class="inline-flex items-center justify-center px-1.5 py-0.5 sm:px-2 sm:py-0.5 bg-tertiary/10 text-tertiary rounded-md text-[9px] sm:text-xs font-semibold">
                                {{ student.tingkat || '-' }}
                            </span>
                        </td>
                        <!-- Kelas -->
                        <td class="p-2.5 sm:p-4 text-center">
                            <span class="inline-flex items-center justify-center px-1.5 py-0.5 sm:px-2 sm:py-0.5 bg-primary/10 text-primary rounded-md text-[9px] sm:text-xs font-semibold">
                                {{ student.kelas }}
                            </span>
                        </td>
                        <!-- JK -->
                        <td class="p-2.5 sm:p-4 text-[11px] sm:text-sm text-on-surface text-center">
                            {{ student.jk }}
                        </td>
                        <!-- TTL -->
                        <td class="p-2.5 sm:p-4 text-[11px] sm:text-sm text-on-surface-variant whitespace-nowrap">
                            {{ student.ttl }}
                        </td>
                        <!-- No WA -->
                        <td class="p-2.5 sm:p-4 text-[11px] sm:text-sm text-on-surface-variant whitespace-nowrap">
                            {{ student.no_wa || '-' }}
                        </td>
                        <!-- Alamat Lengkap -->
                        <td class="p-2.5 sm:p-4 text-[11px] sm:text-sm text-on-surface-variant max-w-[150px] sm:max-w-[220px] truncate" :title="getAlamatLengkap(student)">
                            {{ getAlamatLengkap(student) }}
                        </td>
                    </tr>

                    <!-- Empty state -->
                    <tr v-if="students.length === 0">
                        <td colspan="13" class="p-12 text-center">
                            <span class="material-symbols-outlined text-5xl text-outline-variant mb-4 block">search_off</span>
                            <p class="font-body-lg text-body-lg text-on-surface-variant">Tidak ada data siswa ditemukan.</p>
                            <p class="font-body-sm text-body-sm text-outline mt-1">Coba ubah filter atau kata kunci pencarian.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <div class="p-3 sm:p-4 border-t border-outline-variant/30 flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4 bg-surface-container-lowest">
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-[11px] sm:text-sm sm:font-body-sm text-on-surface-variant">
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

            <!-- Page Buttons (Only shown when paginated and multiple pages exist) -->
            <div v-if="perPage > 0 && totalPages > 1" class="flex items-center gap-1.5 sm:gap-1.5">
                <button
                    :disabled="currentPage <= 1"
                    class="p-1.5 sm:p-2 rounded-md sm:rounded-lg border border-outline-variant/60 text-on-surface-variant hover:border-primary hover:text-primary transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                    @click="$emit('page-change', currentPage - 1)"
                    title="Halaman sebelumnya"
                >
                    <span class="material-symbols-outlined text-[16px] sm:text-sm">chevron_left</span>
                </button>

                <template v-for="(page, i) in visiblePages" :key="i">
                    <span v-if="page === '...'" class="px-2 py-1 text-on-surface-variant text-[11px] sm:text-xs font-bold">...</span>
                    <button
                        v-else
                        :class="[
                            'px-2.5 py-1 sm:px-3 sm:py-1 rounded-md sm:rounded-lg text-[11px] sm:text-sm font-semibold transition-all cursor-pointer',
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
                    <span class="material-symbols-outlined text-[16px] sm:text-sm">chevron_right</span>
                </button>
            </div>
        </div>
    </div>
</template>
