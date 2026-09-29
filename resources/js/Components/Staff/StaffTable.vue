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

function getInitials(name) {
    const parts = name.split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

function getStatusColor(status) {
    const map = {
        'Aktif': 'bg-emerald-100 text-emerald-700',
        'Non-Aktif': 'bg-slate-100 text-slate-500',
        'Pensiun': 'bg-amber-100 text-amber-700',
        'Cuti': 'bg-blue-100 text-blue-700',
    };
    return map[status] ?? 'bg-outline-variant/10 text-on-surface-variant';
}
</script>

<template>
    <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider w-10">#</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Pegawai</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">NIPY</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">JK</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Unit / Jabatan</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">No. WA</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="employees.length === 0">
                        <td colspan="8" class="px-4 py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-on-surface-variant">
                                <span class="material-symbols-outlined text-5xl text-outline/60">badge</span>
                                <p class="text-sm font-medium">Belum ada data pegawai</p>
                                <p class="text-xs text-outline">Import data melalui template Excel untuk memulai</p>
                            </div>
                        </td>
                    </tr>
                    <tr
                        v-for="(emp, index) in employees"
                        :key="emp.nipy"
                        class="hover:bg-slate-50/70 transition-colors group"
                    >
                        <td class="px-4 py-3 text-xs text-slate-400 font-mono">
                            {{ startEntry + index }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary font-bold text-xs flex items-center justify-center shrink-0">
                                    {{ getInitials(emp.nama) }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-on-surface leading-tight">{{ emp.nama }}</p>
                                    <p class="text-xs text-on-surface-variant mt-0.5">{{ emp.ttl || '-' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ emp.nipy }}</td>
                        <td class="px-4 py-3 text-xs text-slate-600">
                            {{ emp.jk === 'L' ? 'Laki-laki' : emp.jk === 'P' ? 'Perempuan' : '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-xs font-medium text-on-surface">{{ emp.unit || '-' }}</p>
                            <p class="text-xs text-on-surface-variant mt-0.5">{{ emp.jabatan || '-' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span
                                v-if="emp.status_keaktifan"
                                :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold', getStatusColor(emp.status_keaktifan)]"
                            >
                                {{ emp.status_keaktifan }}
                            </span>
                            <span v-else class="text-xs text-slate-400">-</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600">{{ emp.no_wa || '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <Link
                                :href="`/staff/${emp.nipy}`"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-primary bg-primary/8 hover:bg-primary/15 transition-colors"
                            >
                                <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                                Detail
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card List -->
        <div class="md:hidden divide-y divide-slate-100">
            <div v-if="employees.length === 0" class="p-10 text-center">
                <span class="material-symbols-outlined text-4xl text-outline/60 block mb-2">badge</span>
                <p class="text-sm text-on-surface-variant">Belum ada data pegawai</p>
            </div>
            <Link
                v-for="emp in employees"
                :key="emp.nipy"
                :href="`/staff/${emp.nipy}`"
                class="flex items-center gap-3 p-4 hover:bg-slate-50 transition-colors active:bg-slate-100"
            >
                <div class="w-11 h-11 rounded-xl bg-primary/10 text-primary font-bold text-sm flex items-center justify-center shrink-0">
                    {{ getInitials(emp.nama) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-on-surface truncate">{{ emp.nama }}</p>
                    <p class="text-xs text-on-surface-variant mt-0.5">NIPY: {{ emp.nipy }} · {{ emp.jabatan || 'Jabatan belum diisi' }}</p>
                </div>
                <span
                    v-if="emp.status_keaktifan"
                    :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold shrink-0', getStatusColor(emp.status_keaktifan)]"
                >
                    {{ emp.status_keaktifan }}
                </span>
            </Link>
        </div>

        <!-- Pagination -->
        <div
            v-if="totalPages > 1 || employees.length > 0"
            class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3"
        >
            <p class="text-xs text-slate-500">
                Menampilkan <span class="font-semibold text-slate-700">{{ startEntry }}–{{ endEntry }}</span>
                dari <span class="font-semibold text-slate-700">{{ totalEntries }}</span> pegawai
            </p>

            <div v-if="totalPages > 1" class="flex items-center gap-1">
                <button
                    @click="$emit('page-change', currentPage - 1)"
                    :disabled="currentPage === 1"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-500 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                >
                    <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                </button>
                <template v-for="page in visiblePages" :key="page">
                    <button
                        v-if="page !== '...'"
                        @click="$emit('page-change', page)"
                        :class="[
                            'w-8 h-8 rounded-lg text-xs font-semibold transition-colors',
                            page === currentPage
                                ? 'bg-primary text-on-primary'
                                : 'text-slate-600 hover:bg-slate-100'
                        ]"
                    >
                        {{ page }}
                    </button>
                    <span v-else class="w-8 h-8 flex items-center justify-center text-xs text-slate-400">…</span>
                </template>
                <button
                    @click="$emit('page-change', currentPage + 1)"
                    :disabled="currentPage === totalPages"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-500 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                >
                    <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                </button>
            </div>
        </div>
    </div>
</template>
