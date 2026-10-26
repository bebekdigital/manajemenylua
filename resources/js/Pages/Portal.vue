<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AcademicYearFormModal from '@/Components/Portal/AcademicYearFormModal.vue';
import UserFormModal from '@/Components/Portal/UserFormModal.vue';
import SchoolLevelFormModal from '@/Components/Portal/SchoolLevelFormModal.vue';
import SchoolUnitFormModal from '@/Components/Portal/SchoolUnitFormModal.vue';
import SchoolProgramFormModal from '@/Components/Portal/SchoolProgramFormModal.vue';
import SchoolClassFormModal from '@/Components/Portal/SchoolClassFormModal.vue';
import DepartmentFormModal from '@/Components/Portal/DepartmentFormModal.vue';
import DepartmentPositionFormModal from '@/Components/Portal/DepartmentPositionFormModal.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    academicYears: {
        type: Array,
        default: () => [],
    },
    users: {
        type: Array,
        default: () => [],
    },
    schoolLevels: {
        type: Array,
        default: () => [],
    },
    schoolPrograms: {
        type: Array,
        default: () => [],
    },
    departments: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

const activeTab = ref('akademik');
const tabs = computed(() => {
    const list = [
        { id: 'akademik', label: 'Akademik', icon: 'school' },
        { id: 'unit_bidang', label: 'Unit & Bidang', icon: 'domain' },
    ];
    if (page.props.auth?.user?.role === 'superadmin') {
        list.push({ id: 'pengguna', label: 'Pengguna', icon: 'manage_accounts' });
    }
    return list;
});

const showFormModal = ref(false);
const editingYear = ref(null);

const showLevelModal = ref(false);
const editingLevel = ref(null);

const showUnitModal = ref(false);
const editingUnit = ref(null);
const selectedLevelForUnit = ref(null);

function openLevelCreateModal() {
    editingLevel.value = null;
    showLevelModal.value = true;
}

function openLevelEditModal(level) {
    editingLevel.value = { ...level };
    showLevelModal.value = true;
}

function closeLevelModal() {
    showLevelModal.value = false;
    editingLevel.value = null;
}

function onLevelSuccess() {
    closeLevelModal();
    dismissedFlash.value = false;
}

function confirmLevelDelete(level) {
    if (!confirm(`Yakin ingin menghapus Jenjang "${level.name}"?`)) return;
    router.delete(`/portal/school-levels/${level.id}`, { preserveScroll: true });
}

function openUnitCreateModal(level) {
    editingUnit.value = null;
    selectedLevelForUnit.value = level;
    showUnitModal.value = true;
}

function openUnitEditModal(unit, level) {
    editingUnit.value = { ...unit };
    selectedLevelForUnit.value = level;
    showUnitModal.value = true;
}

function closeUnitModal() {
    showUnitModal.value = false;
    editingUnit.value = null;
    selectedLevelForUnit.value = null;
}

function onUnitSuccess() {
    closeUnitModal();
    dismissedFlash.value = false;
}

function confirmUnitDelete(unit) {
    if (!confirm(`Yakin ingin menghapus Unit "${unit.name}"?`)) return;
    router.delete(`/portal/school-units/${unit.id}`, { preserveScroll: true });
}

const showProgramModal = ref(false);
const editingProgram = ref(null);
const selectedUnitForProgram = ref(null);

function openProgramCreateModal(unit) {
    editingProgram.value = null;
    selectedUnitForProgram.value = unit;
    showProgramModal.value = true;
}

function openProgramEditModal(program, unit) {
    editingProgram.value = { ...program };
    selectedUnitForProgram.value = unit;
    showProgramModal.value = true;
}

function closeProgramModal() {
    showProgramModal.value = false;
    editingProgram.value = null;
    selectedUnitForProgram.value = null;
}

function onProgramSuccess() {
    closeProgramModal();
    dismissedFlash.value = false;
}

function confirmProgramDelete(program) {
    if (!confirm(`Yakin ingin menghapus Program "${program.name}"?`)) return;
    router.delete(`/portal/school-programs/${program.id}`, { preserveScroll: true });
}

const showClassModal = ref(false);
const editingClass = ref(null);
const selectedUnitForClass = ref(null);

function openClassCreateModal(unit) {
    editingClass.value = null;
    selectedUnitForClass.value = unit;
    showClassModal.value = true;
}

function openClassEditModal(cls, unit) {
    editingClass.value = { ...cls };
    selectedUnitForClass.value = unit;
    showClassModal.value = true;
}

function closeClassModal() {
    showClassModal.value = false;
    editingClass.value = null;
    selectedUnitForClass.value = null;
}

function onClassSuccess() {
    closeClassModal();
    dismissedFlash.value = false;
}

function confirmClassDelete(cls) {
    if (!confirm(`Yakin ingin menghapus Kelas "${cls.name}"?`)) return;
    router.delete(`/portal/school-classes/${cls.id}`, { preserveScroll: true });
}

// Department (Bidang) Modals
const showDepartmentModal = ref(false);
const editingDepartment = ref(null);

function openDepartmentCreateModal() {
    editingDepartment.value = null;
    showDepartmentModal.value = true;
}

function openDepartmentEditModal(department) {
    editingDepartment.value = { ...department };
    showDepartmentModal.value = true;
}

function closeDepartmentModal() {
    showDepartmentModal.value = false;
    editingDepartment.value = null;
}

function onDepartmentSuccess() {
    closeDepartmentModal();
    dismissedFlash.value = false;
}

function confirmDepartmentDelete(department) {
    if (department.positions && department.positions.length > 0) {
        alert(`Tidak dapat menghapus Bidang "${department.name}" karena masih memiliki ${department.positions.length} jabatan terdaftar.`);
        return;
    }
    if (!confirm(`Yakin ingin menghapus Bidang "${department.name}"?`)) return;
    router.delete(`/portal/departments/${department.id}`, { preserveScroll: true });
}

// Department Position (Jabatan) Modals
const showPositionModal = ref(false);
const editingPosition = ref(null);
const selectedDepartmentForPosition = ref(null);

function openPositionCreateModal(department) {
    editingPosition.value = null;
    selectedDepartmentForPosition.value = department;
    showPositionModal.value = true;
}

function openPositionEditModal(position, department) {
    editingPosition.value = { ...position };
    selectedDepartmentForPosition.value = department;
    showPositionModal.value = true;
}

function closePositionModal() {
    showPositionModal.value = false;
    editingPosition.value = null;
    selectedDepartmentForPosition.value = null;
}

function onPositionSuccess() {
    closePositionModal();
    dismissedFlash.value = false;
}

function confirmPositionDelete(position) {
    if (!confirm(`Yakin ingin menghapus Jabatan "${position.name}"?`)) return;
    router.delete(`/portal/department-positions/${position.id}`, { preserveScroll: true });
}

const showUserModal = ref(false);
const editingUser = ref(null);
const deletingUserId = ref(null);

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

// User Modal Functions
function openUserCreateModal() {
    editingUser.value = null;
    showUserModal.value = true;
}

function openUserEditModal(user) {
    editingUser.value = { ...user };
    showUserModal.value = true;
}

function closeUserModal() {
    showUserModal.value = false;
    editingUser.value = null;
}

function onUserFormSuccess() {
    closeUserModal();
    dismissedFlash.value = false;
}

function confirmUserDelete(user) {
    if (!confirm(`Yakin ingin menghapus akun pengguna "${user.name}"?\n\nAksi ini tidak bisa dibatalkan.`)) {
        return;
    }

    deletingUserId.value = user.id;
    dismissedFlash.value = false;

    router.delete(`/portal/users/${user.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingUserId.value = null;
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

        <!-- Tabs -->
        <div class="mb-6 flex border-b border-outline-variant/30 overflow-x-auto">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                @click="activeTab = tab.id"
                :class="[
                    'flex items-center gap-2 px-5 py-3 text-sm font-medium transition-colors whitespace-nowrap border-b-2 rounded-t-lg',
                    activeTab === tab.id
                        ? 'text-primary border-primary bg-gradient-to-t from-primary/20 to-transparent'
                        : 'text-on-surface-variant border-transparent hover:text-on-surface hover:border-outline-variant'
                ]"
            >
                <span class="material-symbols-outlined text-[18px]">{{ tab.icon }}</span>
                <span>{{ tab.label }}</span>
            </button>
        </div>

        <!-- Tab Content: Akademik -->
        <div v-show="activeTab === 'akademik'" class="space-y-8 animate-in fade-in duration-300">
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
                <table class="w-full text-xs sm:text-sm border-collapse border border-outline-variant/60">
                    <thead>
                        <tr class="bg-surface-container-low/70 text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                            <th class="border border-outline-variant/60 px-4 py-3 text-center w-12">No</th>
                            <th class="border border-outline-variant/60 px-5 py-3 text-left">Tahun Ajaran</th>
                            <th class="border border-outline-variant/60 px-4 py-3 text-center w-32">Semester</th>
                            <th class="border border-outline-variant/60 px-5 py-3 text-left">Periode</th>
                            <th class="border border-outline-variant/60 px-4 py-3 text-center w-24">Kelas</th>
                            <th class="border border-outline-variant/60 px-4 py-3 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(year, index) in academicYears"
                            :key="year.id"
                            class="hover:bg-surface-container-low/40 transition-colors"
                        >
                            <td class="border border-outline-variant/60 px-4 py-3.5 text-center text-on-surface-variant font-mono text-xs">
                                {{ index + 1 }}
                            </td>
                            <td class="border border-outline-variant/60 px-5 py-3.5">
                                <span class="font-bold text-on-surface">{{ year.name }}</span>
                            </td>
                            <td class="border border-outline-variant/60 px-4 py-3.5 text-center">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold"
                                    :class="year.semester === 'Ganjil'
                                        ? 'bg-primary/10 text-primary border border-primary/20'
                                        : 'bg-secondary-container/30 text-on-secondary-container border border-secondary-container/50'"
                                >
                                    {{ year.semester }}
                                </span>
                            </td>
                            <td class="border border-outline-variant/60 px-5 py-3.5 text-on-surface-variant text-xs">
                                {{ formatDate(year.start_date) }} — {{ formatDate(year.end_date) }}
                            </td>

                            <td class="border border-outline-variant/60 px-4 py-3.5 text-center">
                                <span class="text-on-surface font-semibold">{{ year.classrooms_count }}</span>
                            </td>

                            <td class="border border-outline-variant/60 px-4 py-3.5 text-center">
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
                                        :disabled="deletingId === year.id"
                                        class="p-2 rounded-lg transition-colors cursor-pointer text-on-surface-variant hover:text-error hover:bg-error/10"
                                        title="Hapus Tahun Ajaran"
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
                        <tr v-if="academicYears.length === 0">
                            <td colspan="6" class="border border-outline-variant/60 px-5 py-12 text-center">
                                <span class="material-symbols-outlined text-4xl text-outline/40 mb-2">calendar_month</span>
                                <h4 class="text-sm font-semibold text-on-surface mb-1">Belum Ada Tahun Ajaran</h4>
                                <p class="text-xs text-on-surface-variant mb-4">
                                    Buat Tahun Ajaran pertama untuk mulai mengelola data siswa.
                                </p>
                                <button
                                    type="button"
                                    @click="openCreateModal"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-xs transition-all shadow-xs cursor-pointer"
                                >
                                    <span class="material-symbols-outlined text-[16px]">add</span>
                                    <span>Tambah Tahun Ajaran Pertama</span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

        <!-- Tab Content: Unit & Bidang -->
        <div v-show="activeTab === 'unit_bidang'" class="space-y-8 animate-in fade-in duration-300">
            <!-- Departments & Positions Section (Tabel Bidang) -->
            <div class="bg-surface-container-lowest rounded-2xl border border-primary/10 shadow-[0px_4px_20px_rgba(0,40,20,0.06)] overflow-hidden">
                <!-- Section Header -->
                <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary text-xl">corporate_fare</span>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-on-surface">Bidang & Jabatan</h3>
                            <p class="text-xs text-on-surface-variant">Kelola struktur bidang kerja yayasan dan daftar jabatan terkait</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="openDepartmentCreateModal"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-sm transition-all shadow-xs hover:shadow-md cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        <span>Tambah Bidang</span>
                    </button>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-xs sm:text-sm border-collapse border border-outline-variant/60">
                        <thead>
                            <tr class="bg-surface-container-low/70 text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                                <th class="border border-outline-variant/60 px-5 py-3 text-left w-1/3">Bidang</th>
                                <th class="border border-outline-variant/60 px-5 py-3 text-left">Jabatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="dept in departments"
                                :key="dept.id"
                                class="hover:bg-surface-container-low/40 transition-colors group/row"
                            >
                                <!-- Kolom Bidang -->
                                <td class="border border-outline-variant/60 px-5 py-4 align-top bg-surface-container-low/20">
                                    <div class="font-bold text-on-surface text-base mb-2">{{ dept.name }}</div>
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <button
                                            type="button"
                                            @click="openDepartmentEditModal(dept)"
                                            class="p-1.5 rounded-md bg-surface-container-high text-on-surface-variant hover:text-primary hover:bg-primary/10 transition-colors"
                                            title="Edit Bidang"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">edit</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="confirmDepartmentDelete(dept)"
                                            class="p-1.5 rounded-md bg-surface-container-high text-on-surface-variant hover:text-error hover:bg-error/10 transition-colors"
                                            title="Hapus Bidang"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="openPositionCreateModal(dept)"
                                            class="inline-flex items-center gap-1 px-2 py-1.5 rounded-md bg-primary/10 text-primary hover:bg-primary hover:text-on-primary transition-colors text-xs font-semibold"
                                        >
                                            <span class="material-symbols-outlined text-[14px]">add</span> Tambah Jabatan
                                        </button>
                                    </div>
                                </td>

                                <!-- Kolom Jabatan -->
                                <td class="border border-outline-variant/60 px-5 py-4 align-top">
                                    <div class="flex flex-col gap-2.5">
                                        <div v-if="dept.positions && dept.positions.length > 0" class="flex flex-wrap gap-2">
                                            <div
                                                v-for="pos in dept.positions"
                                                :key="pos.id"
                                                class="group/pill inline-flex items-center gap-1.5 bg-surface-container-high border border-outline-variant/60 pl-3 pr-1.5 py-1.5 rounded-lg hover:border-primary/40 transition-colors shadow-2xs"
                                            >
                                                <span class="material-symbols-outlined text-[15px] text-primary/70">badge</span>
                                                <span class="text-xs font-semibold text-on-surface">{{ pos.name }}</span>
                                                <div class="flex items-center ml-1">
                                                    <button
                                                        type="button"
                                                        @click="openPositionEditModal(pos, dept)"
                                                        class="text-on-surface-variant hover:text-primary transition-colors p-0.5 rounded"
                                                        title="Edit Jabatan"
                                                    >
                                                        <span class="material-symbols-outlined text-[14px]">edit</span>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="confirmPositionDelete(pos)"
                                                        class="text-on-surface-variant hover:text-error transition-colors p-0.5 rounded"
                                                        title="Hapus Jabatan"
                                                    >
                                                        <span class="material-symbols-outlined text-[14px]">close</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="text-xs text-on-surface-variant italic">Belum ada jabatan ditambahkan pada bidang ini.</div>

                                        <div>
                                            <button
                                                type="button"
                                                @click="openPositionCreateModal(dept)"
                                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary hover:bg-primary/10 px-2 py-1 rounded-md transition-colors border border-primary/20 hover:border-primary/40"
                                            >
                                                <span class="material-symbols-outlined text-[14px]">add</span> Tambah Jabatan
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="departments.length === 0">
                                <td colspan="2" class="border border-outline-variant/60 px-5 py-12 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl mb-2 opacity-50">corporate_fare</span>
                                    <p class="text-sm">Belum ada bidang kerja.<br/>Silakan tambah bidang pertama Anda.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- School Levels & Units Section -->
            <div class="bg-surface-container-lowest rounded-2xl border border-primary/10 shadow-[0px_4px_20px_rgba(0,40,20,0.06)] overflow-hidden">
                <!-- Section Header -->
                <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary text-xl">domain</span>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-on-surface">Jenjang & Unit Sekolah</h3>
                            <p class="text-xs text-on-surface-variant">Kelola jenjang pendidikan dan unit di bawah naungan yayasan</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="openLevelCreateModal"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-sm transition-all shadow-xs hover:shadow-md cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        <span>Tambah Jenjang</span>
                    </button>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-xs sm:text-sm border-collapse border border-outline-variant/60">
                        <thead>
                            <tr class="bg-surface-container-low/70 text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                                <th class="border border-outline-variant/60 px-5 py-3 text-left w-1/4">Jenjang</th>
                                <th class="border border-outline-variant/60 px-5 py-3 text-left w-1/4">Unit Sekolah</th>
                                <th class="border border-outline-variant/60 px-5 py-3 text-left">Program Pendidikan</th>
                                <th class="border border-outline-variant/60 px-5 py-3 text-left">Kelas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="level in schoolLevels" :key="level.id">
                                <!-- State: Belum ada unit -->
                                <tr v-if="!level.units || level.units.length === 0" class="hover:bg-surface-container-low/40 transition-colors">
                                    <td class="border border-outline-variant/60 px-5 py-4 align-top bg-surface-container-low/20">
                                        <div class="font-bold text-on-surface text-base mb-2">{{ level.name }}</div>
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <button type="button" @click="openLevelEditModal(level)" class="p-1.5 rounded-md bg-surface-container-high text-on-surface-variant hover:text-primary hover:bg-primary/10 transition-colors" title="Edit Jenjang">
                                                <span class="material-symbols-outlined text-[16px]">edit</span>
                                            </button>
                                            <button type="button" @click="confirmLevelDelete(level)" class="p-1.5 rounded-md bg-surface-container-high text-on-surface-variant hover:text-error hover:bg-error/10 transition-colors" title="Hapus Jenjang">
                                                <span class="material-symbols-outlined text-[16px]">delete</span>
                                            </button>
                                            <button type="button" @click="openUnitCreateModal(level)" class="inline-flex items-center gap-1 px-2 py-1.5 rounded-md bg-primary/10 text-primary hover:bg-primary hover:text-on-primary transition-colors text-xs font-semibold">
                                                <span class="material-symbols-outlined text-[14px]">add</span> Unit Baru
                                            </button>
                                        </div>
                                    </td>
                                    <td colspan="3" class="border border-outline-variant/60 px-5 py-4 align-middle text-on-surface-variant italic text-sm bg-surface-container-low/10">
                                        Belum ada unit sekolah ditambahkan.
                                    </td>
                                </tr>

                                <!-- State: Ada unit -->
                                <template v-else>
                                    <tr v-for="(unit, index) in level.units" :key="unit.id" class="hover:bg-surface-container-low/40 transition-colors group/row">
                                        
                                        <!-- Kolom Jenjang (Hanya di baris pertama, di-rowspan) -->
                                        <td v-if="index === 0" :rowspan="level.units.length" class="border border-outline-variant/60 px-5 py-4 align-top bg-surface-container-low/20">
                                            <div class="font-bold text-on-surface text-base mb-2">{{ level.name }}</div>
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <button type="button" @click="openLevelEditModal(level)" class="p-1.5 rounded-md bg-surface-container-high text-on-surface-variant hover:text-primary hover:bg-primary/10 transition-colors" title="Edit Jenjang">
                                                    <span class="material-symbols-outlined text-[16px]">edit</span>
                                                </button>
                                                <button type="button" @click="confirmLevelDelete(level)" class="p-1.5 rounded-md bg-surface-container-high text-on-surface-variant hover:text-error hover:bg-error/10 transition-colors" title="Hapus Jenjang">
                                                    <span class="material-symbols-outlined text-[16px]">delete</span>
                                                </button>
                                                <button type="button" @click="openUnitCreateModal(level)" class="inline-flex items-center gap-1 px-2 py-1.5 rounded-md bg-primary/10 text-primary hover:bg-primary hover:text-on-primary transition-colors text-xs font-semibold">
                                                    <span class="material-symbols-outlined text-[14px]">add</span> Unit Baru
                                                </button>
                                            </div>
                                        </td>

                                        <!-- Kolom Unit -->
                                        <td class="border border-outline-variant/60 px-5 py-4 align-top">
                                            <div class="flex items-start justify-between gap-2">
                                                <span class="font-semibold text-on-surface">{{ unit.name }}</span>
                                                <div class="flex items-center gap-1 shrink-0">
                                                    <button type="button" @click="openUnitEditModal(unit, level)" class="p-1 rounded text-on-surface-variant hover:text-primary hover:bg-primary/10 transition-colors" title="Edit Unit">
                                                        <span class="material-symbols-outlined text-[16px]">edit</span>
                                                    </button>
                                                    <button type="button" @click="confirmUnitDelete(unit)" class="p-1 rounded text-on-surface-variant hover:text-error hover:bg-error/10 transition-colors" title="Hapus Unit">
                                                        <span class="material-symbols-outlined text-[16px]">close</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Kolom Program -->
                                        <td class="border border-outline-variant/60 px-5 py-4 align-top">
                                            <div class="flex flex-col gap-2.5">
                                                <div v-if="unit.programs && unit.programs.length > 0" class="flex flex-wrap gap-2">
                                                    <div v-for="program in unit.programs" :key="program.id" class="group/pill inline-flex items-center gap-1 bg-surface-container-high border border-outline-variant/60 pl-2.5 pr-1 py-1 rounded-lg hover:border-primary/40 transition-colors">
                                                        <span class="text-xs font-medium text-on-surface">{{ program.name }}</span>
                                                        <div class="flex items-center ml-1">
                                                            <button type="button" @click="openProgramEditModal(program, unit)" class="text-on-surface-variant hover:text-primary transition-colors p-0.5 rounded" title="Edit Program">
                                                                <span class="material-symbols-outlined text-[14px]">edit</span>
                                                            </button>
                                                            <button type="button" @click="confirmProgramDelete(program)" class="text-on-surface-variant hover:text-error transition-colors p-0.5 rounded" title="Hapus Program">
                                                                <span class="material-symbols-outlined text-[14px]">close</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-else class="text-xs text-on-surface-variant italic">Belum ada program.</div>
                                                
                                                <div>
                                                    <button type="button" @click="openProgramCreateModal(unit)" class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary hover:bg-primary/10 px-2 py-1 rounded-md transition-colors border border-primary/20 hover:border-primary/40">
                                                        <span class="material-symbols-outlined text-[14px]">add</span> Tambah Program
                                                    </button>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Kolom Kelas -->
                                        <td class="border border-outline-variant/60 px-5 py-4 align-top">
                                            <div class="flex flex-col gap-2.5">
                                                <div v-if="unit.classes && unit.classes.length > 0" class="flex flex-wrap gap-2">
                                                    <div v-for="cls in unit.classes" :key="cls.id" class="group/pill inline-flex items-center gap-1 bg-surface-container-high border border-outline-variant/60 pl-2.5 pr-1 py-1 rounded-lg hover:border-primary/40 transition-colors">
                                                        <span class="text-xs font-medium text-on-surface">{{ cls.name }}</span>
                                                        <div class="flex items-center ml-1">
                                                            <button type="button" @click="openClassEditModal(cls, unit)" class="text-on-surface-variant hover:text-primary transition-colors p-0.5 rounded" title="Edit Kelas">
                                                                <span class="material-symbols-outlined text-[14px]">edit</span>
                                                            </button>
                                                            <button type="button" @click="confirmClassDelete(cls)" class="text-on-surface-variant hover:text-error transition-colors p-0.5 rounded" title="Hapus Kelas">
                                                                <span class="material-symbols-outlined text-[14px]">close</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-else class="text-xs text-on-surface-variant italic">Belum ada kelas.</div>
                                                
                                                <div>
                                                    <button type="button" @click="openClassCreateModal(unit)" class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary hover:bg-primary/10 px-2 py-1 rounded-md transition-colors border border-primary/20 hover:border-primary/40">
                                                        <span class="material-symbols-outlined text-[14px]">add</span> Tambah Kelas
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </template>

                            <tr v-if="schoolLevels.length === 0">
                                <td colspan="4" class="border border-outline-variant/60 px-5 py-12 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl mb-2 opacity-50">domain_disabled</span>
                                    <p class="text-sm">Belum ada jenjang pendidikan.<br/>Silakan tambah jenjang pertama Anda.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Tab Content: Pengguna -->
        <div v-show="activeTab === 'pengguna'" class="space-y-8 animate-in fade-in duration-300">
            <!-- User Management Section (Superadmin Only) -->
            <div v-if="$page.props.auth?.user?.role === 'superadmin'" class="bg-surface-container-lowest rounded-2xl border border-primary/10 shadow-[0px_4px_20px_rgba(0,40,20,0.06)] overflow-hidden">
            <!-- Section Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant/30">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-xl">manage_accounts</span>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-on-surface">Manajemen Pengguna</h3>
                        <p class="text-xs text-on-surface-variant">Kelola akun dan kewenangan staf/guru di sistem</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="openUserCreateModal"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-sm transition-all shadow-xs hover:shadow-md cursor-pointer"
                >
                    <span class="material-symbols-outlined text-[18px]">person_add</span>
                    <span>Tambah Akun</span>
                </button>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-xs sm:text-sm border-collapse border border-outline-variant/60">
                    <thead>
                        <tr class="bg-surface-container-low/70 text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                            <th class="border border-outline-variant/60 px-4 py-3 text-center w-12">No</th>
                            <th class="border border-outline-variant/60 px-5 py-3 text-left">Nama Lengkap</th>
                            <th class="border border-outline-variant/60 px-5 py-3 text-left">NIP / Username</th>
                            <th class="border border-outline-variant/60 px-5 py-3 text-left">Email</th>
                            <th class="border border-outline-variant/60 px-4 py-3 text-center w-32">Role</th>
                            <th class="border border-outline-variant/60 px-4 py-3 text-center w-36">Dibuat Pada</th>
                            <th class="border border-outline-variant/60 px-4 py-3 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(u, idx) in users"
                            :key="u.id"
                            class="hover:bg-surface-container-low/40 transition-colors"
                        >
                            <td class="border border-outline-variant/60 px-4 py-3.5 text-center text-on-surface-variant font-mono text-xs">
                                {{ idx + 1 }}
                            </td>
                            <td class="border border-outline-variant/60 px-5 py-3.5">
                                <span class="font-bold text-on-surface">{{ u.name }}</span>
                                <span v-if="u.id === $page.props.auth?.user?.id" class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-primary/10 text-primary border border-primary/20">
                                    Anda
                                </span>
                            </td>
                            <td class="border border-outline-variant/60 px-5 py-3.5 text-on-surface-variant">
                                {{ u.username }}
                            </td>
                            <td class="border border-outline-variant/60 px-5 py-3.5 text-on-surface-variant">
                                {{ u.email || '—' }}
                            </td>
                            <td class="border border-outline-variant/60 px-4 py-3.5 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider"
                                      :class="u.role === 'superadmin' ? 'bg-error/10 text-error border border-error/20' : 'bg-secondary-container/40 text-on-secondary-container border border-secondary-container/60'">
                                    {{ u.role }}
                                </span>
                            </td>
                            <td class="border border-outline-variant/60 px-4 py-3.5 text-center text-on-surface-variant text-xs">
                                {{ u.created_at }}
                            </td>
                            <td class="border border-outline-variant/60 px-4 py-3.5 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button
                                        type="button"
                                        @click="openUserEditModal(u)"
                                        class="p-2 rounded-lg text-on-surface-variant hover:text-primary hover:bg-primary/10 transition-colors cursor-pointer"
                                        title="Edit Akun"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="confirmUserDelete(u)"
                                        :disabled="deletingUserId === u.id || u.id === $page.props.auth?.user?.id"
                                        class="p-2 rounded-lg transition-colors cursor-pointer"
                                        :class="u.id === $page.props.auth?.user?.id
                                            ? 'text-outline/40 cursor-not-allowed'
                                            : 'text-on-surface-variant hover:text-error hover:bg-error/10'"
                                        :title="u.id === $page.props.auth?.user?.id ? 'Tidak bisa menghapus akun sendiri' : 'Hapus Akun'"
                                    >
                                        <span
                                            v-if="deletingUserId === u.id"
                                            class="material-symbols-outlined text-[18px] animate-spin"
                                        >sync</span>
                                        <span v-else class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.length === 0">
                            <td colspan="7" class="border border-outline-variant/60 px-5 py-10 text-center text-on-surface-variant">
                                Belum ada data pengguna lainnya.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
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

    <!-- User Form Modal -->
    <UserFormModal
        :show="showUserModal"
        :editing="editingUser"
        @close="closeUserModal"
        @success="onUserFormSuccess"
    />

    <!-- School Level Form Modal -->
    <SchoolLevelFormModal
        :show="showLevelModal"
        :editing="editingLevel"
        @close="closeLevelModal"
        @success="onLevelSuccess"
    />

    <!-- School Unit Form Modal -->
    <SchoolUnitFormModal
        :show="showUnitModal"
        :editing="editingUnit"
        :school-level="selectedLevelForUnit"
        @close="closeUnitModal"
        @success="onUnitSuccess"
    />

    <!-- School Program Form Modal -->
    <SchoolProgramFormModal
        :show="showProgramModal"
        :editing="editingProgram"
        :school-unit="selectedUnitForProgram"
        @close="closeProgramModal"
        @success="onProgramSuccess"
    />

    <!-- School Class Form Modal -->
    <SchoolClassFormModal
        :show="showClassModal"
        :editing="editingClass"
        :school-unit="selectedUnitForClass"
        @close="closeClassModal"
        @success="onClassSuccess"
    />

    <!-- Department Form Modal -->
    <DepartmentFormModal
        :show="showDepartmentModal"
        :editing="editingDepartment"
        @close="closeDepartmentModal"
        @success="onDepartmentSuccess"
    />

    <!-- Department Position Form Modal -->
    <DepartmentPositionFormModal
        :show="showPositionModal"
        :editing="editingPosition"
        :department="selectedDepartmentForPosition"
        @close="closePositionModal"
        @success="onPositionSuccess"
    />

        </template>
