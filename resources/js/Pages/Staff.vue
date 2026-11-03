<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StaffToolbar from '@/Components/Staff/StaffToolbar.vue';
import StaffTable from '@/Components/Staff/StaffTable.vue';
import StaffImportModal from '@/Components/Staff/StaffImportModal.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    employees: {
        type: Array,
        default: () => [],
    },
    isTeacher: {
        type: Boolean,
        default: false,
    },
    teacherEmployee: {
        type: Object,
        default: null,
    },
    permissions: {
        type: Object,
        default: () => ({
            can_create: true,
            can_update: true,
            can_export: true,
            can_import: true,
        }),
    },
    selectedAcademicYear: {
        type: Object,
        default: null,
    },
});

const page = usePage();

// ============================================================
// State
// ============================================================
const searchQuery = ref('');
const unitFilter = ref('');
const statusFilter = ref('aktif'); // Default Aktif sesuai permintaan user
const jenjangFilters = ref([]); // Multi-select array untuk Jenjang
const sortBy = ref('nama');
const sortDirection = ref('asc');
const currentPage = ref(1);
const perPage = ref(15);
const showImportModal = ref(false);
const dismissedFlash = ref(false);

const flashSuccess = computed(() => !dismissedFlash.value ? page.props.flash?.success : null);
const flashError = computed(() => !dismissedFlash.value ? page.props.flash?.error : null);

const uniqueUnitsCount = computed(() => {
    return new Set(props.employees.map(e => e.unit_kerja).filter(Boolean)).size;
});

const filteredEmployees = computed(() => {
    let result = [...props.employees];

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase().trim();
        result = result.filter(e =>
            (e.nama || '').toLowerCase().includes(query) ||
            (e.nipy || '').toLowerCase().includes(query) ||
            (e.jabatan || '').toLowerCase().includes(query) ||
            (e.unit_kerja || '').toLowerCase().includes(query) ||
            (e.no_wa || '').toLowerCase().includes(query)
        );
    }

    if (unitFilter.value) {
        result = result.filter(e => e.unit_kerja === unitFilter.value);
    }

    // Multi-select jenjang filter
    if (jenjangFilters.value.length > 0) {
        const activeList = jenjangFilters.value.map(j => j.toUpperCase());
        result = result.filter(e => activeList.includes((e.jenjang_kepegawaian || '').toUpperCase()));
    }

    if (statusFilter.value && statusFilter.value !== 'semua') {
        const val = statusFilter.value.toLowerCase();
        if (val === 'aktif') {
            result = result.filter(e => !e.status_keaktifan || e.status_keaktifan.toLowerCase() === 'aktif');
        } else if (val === 'tidak_aktif' || val === 'non-aktif') {
            result = result.filter(e => e.status_keaktifan && ['non-aktif', 'tidak aktif', 'tidak_aktif', 'resign', 'keluar'].includes(e.status_keaktifan.toLowerCase()));
        } else {
            result = result.filter(e => (e.status_keaktifan || '').toLowerCase() === val);
        }
    }

    // Sorting via table header
    result.sort((a, b) => {
        const field = sortBy.value;
        const valA = (a[field] || '').toString().toLowerCase();
        const valB = (b[field] || '').toString().toLowerCase();
        if (valA < valB) return sortDirection.value === 'asc' ? -1 : 1;
        if (valA > valB) return sortDirection.value === 'asc' ? 1 : -1;
        return 0;
    });

    return result;
});

const totalEntries = computed(() => filteredEmployees.value.length);

const paginatedEmployees = computed(() => {
    if (perPage.value === 0) return filteredEmployees.value;
    const start = (currentPage.value - 1) * perPage.value;
    return filteredEmployees.value.slice(start, start + perPage.value);
});

function onPerPageChange(val) {
    perPage.value = Number(val);
    currentPage.value = 1;
}

function onSearch(query) {
    searchQuery.value = query;
    currentPage.value = 1;
}

function onFilterUnit(unit) {
    unitFilter.value = unit;
    currentPage.value = 1;
}

function onFilterStatus(status) {
    statusFilter.value = status;
    currentPage.value = 1;
}

function onFilterJenjang(jenjangs) {
    jenjangFilters.value = jenjangs;
    currentPage.value = 1;
}

function onSortChange({ field, direction }) {
    if (sortBy.value === field && !direction) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDirection.value = direction || 'asc';
    }
    currentPage.value = 1;
}

function onResetFilters() {
    searchQuery.value = '';
    unitFilter.value = '';
    statusFilter.value = 'aktif';
    jenjangFilters.value = [];
    sortBy.value = 'nama';
    sortDirection.value = 'asc';
    currentPage.value = 1;
}

function onPageChange(p) {
    currentPage.value = p;
}
</script>

<template>
    <div class="flex-1 overflow-y-auto px-4 py-6 md:px-8 md:py-7 bg-white min-h-screen">
        <Head title="Database Pegawai - Foundation Data Center" />
        <div class="max-w-[1440px] mx-auto">
            <!-- Page Header (Tanpa TA / 2026/2027) -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-[26px] font-bold text-slate-900 tracking-tight leading-none">
                        Database Pegawai
                    </h1>
                    <p class="text-[13.5px] text-slate-500 mt-2 font-medium">
                        <strong class="text-slate-700 font-semibold">{{ employees.length }}</strong> pegawai di <strong class="text-slate-700 font-semibold">{{ uniqueUnitsCount }}</strong> unit kerja
                    </p>
                </div>

                <!-- Action Buttons (Khusus Non-Guru & memiliki izin) -->
                <div v-if="!isTeacher" class="flex items-center gap-2.5 shrink-0 flex-wrap sm:flex-nowrap">
                    <a
                        v-if="permissions?.can_export"
                        href="/staff/template"
                        download
                        class="inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-xl border border-slate-300 text-slate-700 bg-white hover:bg-slate-50 hover:border-slate-400 font-semibold text-[13px] transition-all shadow-2xs"
                        title="Download template XLSX untuk import data pegawai"
                    >
                        <span class="material-symbols-outlined text-[18px] text-emerald-700">download</span>
                        <span>Download Template</span>
                    </a>

                    <button
                        v-if="permissions?.can_import"
                        type="button"
                        @click="showImportModal = true"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-semibold text-[13px] transition-all shadow-xs cursor-pointer active:scale-95"
                        title="Import data pegawai dari file template Excel"
                    >
                        <span class="material-symbols-outlined text-[18px]">upload_file</span>
                        <span>Import Data Pegawai</span>
                    </button>
                </div>
                <div v-else-if="teacherEmployee" class="flex items-center gap-2 shrink-0">
                    <Link
                        :href="`/staff/${teacherEmployee.id}`"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-semibold text-[13px] transition-all shadow-xs"
                    >
                        <span class="material-symbols-outlined text-[18px]">person</span>
                        <span>Buka Profil Saya</span>
                    </Link>
                </div>
            </div>

            <!-- Teacher Role Scoping Notice Banner -->
            <div
                v-if="isTeacher"
                class="mb-5 p-4 bg-emerald-50/70 border border-emerald-200 rounded-2xl flex items-start gap-3.5"
            >
                <span class="material-symbols-outlined text-emerald-700 text-2xl shrink-0 mt-0.5">shield_person</span>
                <div class="flex-1">
                    <h4 class="text-[13.5px] font-bold text-emerald-950">Mode Akses Guru</h4>
                    <p v-if="teacherEmployee" class="text-[12.5px] text-emerald-800 mt-0.5 leading-relaxed">
                        Sesuai kebijakan hak akses, Anda hanya dapat melihat dan mengelola data kepegawaian atas nama Anda sendiri:
                        <strong class="font-semibold text-emerald-950 underline">{{ teacherEmployee.nama }}</strong>.
                    </p>
                    <p v-else class="text-[12.5px] text-amber-800 mt-0.5 leading-relaxed">
                        Akun login Anda belum terhubung dengan data profil pegawai di sistem. Harap hubungi Superadmin atau Bagian SDM untuk menautkan NIPY atau Nama Anda.
                    </p>
                </div>
            </div>

            <!-- Flash Notifications -->
            <div
                v-if="flashSuccess"
                class="mb-5 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-start justify-between gap-3 animate-in fade-in duration-200"
            >
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-emerald-700 text-2xl shrink-0 mt-0.5">check_circle</span>
                    <div>
                        <h4 class="text-[13.5px] font-bold text-emerald-900">Import Berhasil</h4>
                        <p class="text-[12.5px] text-emerald-800 mt-0.5 leading-relaxed">{{ flashSuccess }}</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="dismissedFlash = true"
                    class="text-emerald-700 hover:text-emerald-950 p-1 rounded-full hover:bg-emerald-100 transition-colors cursor-pointer"
                >
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <div
                v-if="flashError"
                class="mb-5 p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-start justify-between gap-3 animate-in fade-in duration-200"
            >
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-rose-600 text-2xl shrink-0 mt-0.5">error</span>
                    <div>
                        <h4 class="text-[13.5px] font-bold text-rose-900">Import Gagal</h4>
                        <p class="text-[12.5px] text-rose-800 mt-0.5 leading-relaxed">{{ flashError }}</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="dismissedFlash = true"
                    class="text-rose-600 hover:text-rose-900 p-1 rounded-full hover:bg-rose-100 transition-colors cursor-pointer"
                >
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <!-- Toolbar (Modern layout matching image: Status tabs, Search Bar Database Siswa, Multi-select Jenjang Filter) -->
            <StaffToolbar
                :employees="employees"
                :total-count="employees.length"
                :filtered-count="totalEntries"
                :per-page="perPage"
                :active-status="statusFilter"
                :active-unit="unitFilter"
                :active-jenjang="jenjangFilters"
                @update:per-page="onPerPageChange"
                @search="onSearch"
                @filter-unit="onFilterUnit"
                @filter-status="onFilterStatus"
                @filter-jenjang="onFilterJenjang"
                @reset-filters="onResetFilters"
            />

            <!-- Table (Modern design with green header, thicker horizontal dividers, and reduced rounded corners) -->
            <StaffTable
                :employees="paginatedEmployees"
                :current-page="currentPage"
                :per-page="perPage"
                :total-entries="totalEntries"
                :sort-by="sortBy"
                :sort-direction="sortDirection"
                :can-update="permissions?.can_update ?? true"
                @update:per-page="onPerPageChange"
                @page-change="onPageChange"
                @sort-change="onSortChange"
            />
        </div>

        <!-- Import Modal -->
        <StaffImportModal
            :show="showImportModal"
            @close="showImportModal = false"
            @success="dismissedFlash = false"
        />
    </div>
</template>
