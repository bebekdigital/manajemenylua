<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AcademicYearFormModal from '@/Components/Portal/AcademicYearFormModal.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    academicYears: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

const showFormModal = ref(false);
const editingYear = ref(null);
const dismissedFlash = ref(false);
const settingActiveId = ref(null);
const deletingId = ref(null);

const flashSuccess = computed(() => {
    return !dismissedFlash.value ? page.props.flash?.success : null;
});

const flashError = computed(() => {
    return !dismissedFlash.value ? page.props.flash?.error : null;
});

function openCreateModal() {
    editingYear.value = null;
    showFormModal.value = true;
}

function openEditModal(year) {
    editingYear.value = { ...year };
    showFormModal.value = true;
}

function closeFormModal() {
    showFormModal.value = false;
    editingYear.value = null;
}

function onFormSuccess() {
    closeFormModal();
    dismissedFlash.value = false;
}

function setActive(year) {
    if (year.is_active || settingActiveId.value) return;

    settingActiveId.value = year.id;
    dismissedFlash.value = false;

    router.post(`/portal/academic-years/${year.id}/set-active`, {}, {
        preserveScroll: true,
        onFinish: () => {
            settingActiveId.value = null;
        },
    });
}

function confirmDelete(year) {
    if (!confirm(`Yakin ingin menghapus Tahun Ajaran "${year.name} ${year.semester}"?\n\nAksi ini tidak bisa dibatalkan.`)) {
        return;
    }

    deletingId.value = year.id;
    dismissedFlash.value = false;

    router.delete(`/portal/academic-years/${year.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}
</script>

<template>
    <Head title="Manajemen Portal - Foundation Data Center" />

    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop">
        <!-- Page Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl text-primary font-bold">
                    Manajemen Portal
                </h2>
                <p class="font-body-md text-body-md text-on-surface-variant mt-1">
                    Kelola Tahun Ajaran dan pengaturan sistem data yayasan
                </p>
            </div>
        </div>

        <!-- Flash Notification Banners -->
        <div
            v-if="flashSuccess"
            class="mb-5 p-4 bg-primary/10 border border-primary/30 rounded-2xl flex items-start justify-between gap-3 animate-in fade-in duration-200"
        >
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-primary text-2xl shrink-0 mt-0.5">check_circle</span>
                <p class="text-sm text-on-surface font-medium">{{ flashSuccess }}</p>
            </div>
            <button
                type="button"
                @click="dismissedFlash = true"
                class="text-on-surface-variant hover:text-on-surface p-1 rounded-full hover:bg-primary/15 transition-colors cursor-pointer"
            >
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <div
            v-if="flashError"
            class="mb-5 p-4 bg-error/10 border border-error/30 rounded-2xl flex items-start justify-between gap-3 animate-in fade-in duration-200"
        >
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-error text-2xl shrink-0 mt-0.5">error</span>
                <p class="text-sm text-on-surface font-medium">{{ flashError }}</p>
            </div>
            <button
                type="button"
                @click="dismissedFlash = true"
                class="text-on-surface-variant hover:text-on-surface p-1 rounded-full hover:bg-error/15 transition-colors cursor-pointer"
            >
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <!-- Academic Year Section -->
        <div class="bg-surface-container-lowest rounded-2xl border border-primary/10 shadow-[0px_4px_20px_rgba(0,40,20,0.06)] overflow-hidden">
            <!-- Section Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant/30">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-xl">calendar_month</span>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-on-surface">Tahun Ajaran</h3>
                        <p class="text-xs text-on-surface-variant">Kelola periode akademik dan tentukan TA yang aktif</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="openCreateModal"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-sm transition-all shadow-xs hover:shadow-md cursor-pointer"
                >
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    <span>Tambah TA</span>
                </button>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-container-low/50">
                            <th class="text-left px-5 py-3 font-semibold text-on-surface-variant text-xs uppercase tracking-wider">Tahun Ajaran</th>
                            <th class="text-left px-5 py-3 font-semibold text-on-surface-variant text-xs uppercase tracking-wider">Semester</th>
                            <th class="text-left px-5 py-3 font-semibold text-on-surface-variant text-xs uppercase tracking-wider">Periode</th>
                            <th class="text-center px-5 py-3 font-semibold text-on-surface-variant text-xs uppercase tracking-wider">Siswa</th>
                            <th class="text-center px-5 py-3 font-semibold text-on-surface-variant text-xs uppercase tracking-wider">Kelas</th>
                            <th class="text-center px-5 py-3 font-semibold text-on-surface-variant text-xs uppercase tracking-wider">Status</th>
                            <th class="text-center px-5 py-3 font-semibold text-on-surface-variant text-xs uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        <tr
                            v-for="year in academicYears"
                            :key="year.id"
                            class="hover:bg-surface-container-low/40 transition-colors"
                            :class="{ 'bg-primary/[0.03]': year.is_active }"
                        >
                            <td class="px-5 py-3.5">
                                <span class="font-semibold text-on-surface">{{ year.name }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold"
                                    :class="year.semester === 'Ganjil'
                                        ? 'bg-primary/10 text-primary border border-primary/20'
                                        : 'bg-secondary-container/30 text-on-secondary-container border border-secondary-container/50'"
                                >
                                    {{ year.semester }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-on-surface-variant text-xs">
                                {{ formatDate(year.start_date) }} — {{ formatDate(year.end_date) }}
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="text-on-surface font-semibold">{{ year.students_count }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="text-on-surface font-semibold">{{ year.classrooms_count }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <button
                                    type="button"
                                    @click="setActive(year)"
                                    :disabled="year.is_active || settingActiveId === year.id"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer"
                                    :class="year.is_active
                                        ? 'bg-primary text-on-primary shadow-xs'
                                        : 'bg-surface-container-high text-on-surface-variant hover:bg-primary/10 hover:text-primary border border-outline-variant/50'"
                                    :title="year.is_active ? 'Tahun Ajaran Aktif saat ini' : 'Klik untuk menetapkan sebagai TA Aktif'"
                                >
                                    <span
                                        v-if="settingActiveId === year.id"
                                        class="material-symbols-outlined text-[14px] animate-spin"
                                    >sync</span>
                                    <span
                                        v-else
                                        class="material-symbols-outlined text-[14px]"
                                    >{{ year.is_active ? 'check_circle' : 'radio_button_unchecked' }}</span>
                                    <span>{{ year.is_active ? 'Aktif' : 'Set Aktif' }}</span>
                                </button>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button
                                        type="button"
                                        @click="openEditModal(year)"
                                        class="p-2 rounded-lg text-on-surface-variant hover:text-primary hover:bg-primary/10 transition-colors cursor-pointer"
                                        title="Edit Tahun Ajaran"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="confirmDelete(year)"
                                        :disabled="deletingId === year.id || year.is_active"
                                        class="p-2 rounded-lg transition-colors cursor-pointer"
                                        :class="year.is_active
                                            ? 'text-outline/40 cursor-not-allowed'
                                            : 'text-on-surface-variant hover:text-error hover:bg-error/10'"
                                        :title="year.is_active ? 'Tidak bisa menghapus TA aktif' : 'Hapus Tahun Ajaran'"
                                    >
                                        <span
                                            v-if="deletingId === year.id"
                                            class="material-symbols-outlined text-[18px] animate-spin"
                                        >sync</span>
                                        <span v-else class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div v-if="academicYears.length === 0" class="px-5 py-16 text-center">
                <span class="material-symbols-outlined text-5xl text-outline/40 mb-3">calendar_month</span>
                <h4 class="text-base font-semibold text-on-surface mb-1">Belum Ada Tahun Ajaran</h4>
                <p class="text-sm text-on-surface-variant mb-6">
                    Buat Tahun Ajaran pertama untuk mulai mengelola data siswa.
                </p>
                <button
                    type="button"
                    @click="openCreateModal"
                    class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-sm transition-all shadow-xs hover:shadow-md cursor-pointer"
                >
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    <span>Tambah Tahun Ajaran Pertama</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Form Modal -->
    <AcademicYearFormModal
        :show="showFormModal"
        :editing="editingYear"
        @close="closeFormModal"
        @success="onFormSuccess"
    />
</template>
