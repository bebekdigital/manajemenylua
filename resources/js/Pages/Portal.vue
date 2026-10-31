<script setup>
import { ref, computed, watch } from 'vue';
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
import OrgChartDiagram from '@/Components/Portal/OrgChartDiagram.vue';
import GenerateUserModal from '@/Components/Portal/GenerateUserModal.vue';
import RolePermissionPanel from '@/Components/Portal/RolePermissionPanel.vue';
import SuccessModal from '@/Components/Shared/SuccessModal.vue';
import ConfirmDeleteModal from '@/Components/Shared/ConfirmDeleteModal.vue';

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
    organizationPositions: {
        type: Array,
        default: () => [],
    },
    organizationLevels: {
        type: Array,
        default: () => [],
    },
    availableRoles: {
        type: Array,
        default: () => ['superadmin'],
    },
    schoolRoles: {
        type: Array,
        default: () => [],
    },
    deptRoles: {
        type: Array,
        default: () => [],
    },
    employees: {
        type: Array,
        default: () => [],
    },
    permissionsData: {
        type: Object,
        default: () => ({}),
    },
    primaryRoles: {
        type: Array,
        default: () => ['superadmin', 'Kepala Bidang SDM', 'Guru'],
    },
    defaultFeatures: {
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
        list.push({ id: 'hak_akses', label: 'Hak Akses', icon: 'shield_person' });
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
    openConfirmModal({
        title: 'Hapus Jenjang',
        message: 'Yakin ingin menghapus jenjang pendidikan ini beserta unit dan kelas di dalamnya?',
        itemName: level.name,
        onConfirm: (done) => {
            router.delete(`/portal/school-levels/${level.id}`, {
                preserveScroll: true,
                onFinish: done,
            });
        },
    });
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
    openConfirmModal({
        title: 'Hapus Unit Sekolah',
        message: 'Yakin ingin menghapus unit sekolah ini beserta kelas di dalamnya?',
        itemName: unit.name,
        onConfirm: (done) => {
            router.delete(`/portal/school-units/${unit.id}`, {
                preserveScroll: true,
                onFinish: done,
            });
        },
    });
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
    openConfirmModal({
        title: 'Hapus Program Pendidikan',
        message: 'Yakin ingin menghapus program pendidikan ini?',
        itemName: program.name,
        onConfirm: (done) => {
            router.delete(`/portal/school-programs/${program.id}`, {
                preserveScroll: true,
                onFinish: done,
            });
        },
    });
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
    openConfirmModal({
        title: 'Hapus Kelas',
        message: 'Yakin ingin menghapus kelas ini dari master data?',
        itemName: cls.name,
        onConfirm: (done) => {
            router.delete(`/portal/school-classes/${cls.id}`, {
                preserveScroll: true,
                onFinish: done,
            });
        },
    });
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
        openConfirmModal({
            title: 'Tidak Dapat Dihapus',
            message: `Bidang "${department.name}" masih memiliki ${department.positions.length} jabatan terdaftar. Hapus semua jabatan di dalamnya terlebih dahulu.`,
            itemName: department.name,
            hideConfirmButton: true,
        });
        return;
    }

    openConfirmModal({
        title: 'Hapus Bidang',
        message: 'Yakin ingin menghapus bidang yayasan ini?',
        itemName: department.name,
        onConfirm: (done) => {
            router.delete(`/portal/departments/${department.id}`, {
                preserveScroll: true,
                onFinish: done,
            });
        },
    });
}

// Department Position (Jabatan Organisasi) Modals
const showPositionModal = ref(false);
const editingPosition = ref(null);
const selectedDepartmentForPosition = ref(null);
const presetPositionLevel = ref(3);
const presetPositionParentId = ref(null);
const presetPositionDepartmentId = ref(null);
const positionModalTitle = ref('');

function handleAddPosition({ presetLevel, presetParentId, presetDepartmentId, modeTitle } = {}) {
    editingPosition.value = null;
    selectedDepartmentForPosition.value = null;
    presetPositionLevel.value = presetLevel ? Number(presetLevel) : 3;
    presetPositionParentId.value = presetParentId || null;
    presetPositionDepartmentId.value = presetDepartmentId || null;
    positionModalTitle.value = modeTitle || 'Tambah Jabatan Organisasi';
    showPositionModal.value = true;
}

function handleEditPosition(position) {
    editingPosition.value = { ...position };
    selectedDepartmentForPosition.value = null;
    presetPositionLevel.value = position.level ? Number(position.level) : 3;
    presetPositionParentId.value = position.parent_id || null;
    presetPositionDepartmentId.value = position.department_id || null;
    positionModalTitle.value = `Edit Jabatan: ${position.name}`;
    showPositionModal.value = true;
}

function openPositionCreateModal(department) {
    editingPosition.value = null;
    selectedDepartmentForPosition.value = department;
    presetPositionLevel.value = 3;
    presetPositionParentId.value = null;
    presetPositionDepartmentId.value = department?.id || null;
    positionModalTitle.value = department ? `Tambah Jabatan di ${department.name}` : 'Tambah Jabatan Organisasi';
    showPositionModal.value = true;
}

function openPositionEditModal(position, department) {
    editingPosition.value = { ...position };
    selectedDepartmentForPosition.value = department;
    presetPositionLevel.value = position.level ? Number(position.level) : 3;
    presetPositionParentId.value = position.parent_id || null;
    presetPositionDepartmentId.value = department?.id || position.department_id || null;
    positionModalTitle.value = `Edit Jabatan: ${position.name}`;
    showPositionModal.value = true;
}

function closePositionModal() {
    showPositionModal.value = false;
    editingPosition.value = null;
    selectedDepartmentForPosition.value = null;
    presetPositionParentId.value = null;
    presetPositionDepartmentId.value = null;
}

function onPositionSuccess() {
    closePositionModal();
    dismissedFlash.value = false;
}

function confirmPositionDelete(position) {
    openConfirmModal({
        title: 'Hapus Jabatan Organisasi',
        message: `Yakin ingin menghapus jabatan "${position.name}" dari susunan organisasi? Jika memiliki bawahan, jabatan bawahan akan otomatis dialihkan ke atasan langsung jabatan ini.`,
        itemName: position.name,
        onConfirm: (done) => {
            router.delete(`/portal/department-positions/${position.id}`, {
                preserveScroll: true,
                onFinish: done,
            });
        },
    });
}

const showUserModal = ref(false);
const showGenerateUserModal = ref(false);
const editingUser = ref(null);
const deletingUserId = ref(null);
const togglingUserId = ref(null);
const resettingUserId = ref(null);
const copiedPassId = ref(null);

const dismissedFlash = ref(false);
const settingActiveId = ref(null);
const deletingId = ref(null);



const showSuccessModal = ref(false);
const successMessage = ref('');

// Confirmation Modal State
const confirmModal = ref({
    show: false,
    title: 'Konfirmasi Hapus',
    message: '',
    itemName: '',
    confirmText: 'Ya, Hapus',
    isDeleting: false,
    hideConfirmButton: false,
    onConfirm: null,
});

function openConfirmModal(config) {
    confirmModal.value = {
        show: true,
        title: config.title || 'Konfirmasi Hapus',
        message: config.message || 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.',
        itemName: config.itemName || '',
        confirmText: config.confirmText || 'Ya, Hapus',
        isDeleting: false,
        hideConfirmButton: !!config.hideConfirmButton,
        onConfirm: config.onConfirm || null,
    };
}

function closeConfirmModal() {
    if (!confirmModal.value.isDeleting) {
        confirmModal.value.show = false;
        confirmModal.value.onConfirm = null;
    }
}

function handleConfirmModalAction() {
    if (typeof confirmModal.value.onConfirm === 'function') {
        confirmModal.value.isDeleting = true;
        confirmModal.value.onConfirm(() => {
            confirmModal.value.isDeleting = false;
            confirmModal.value.show = false;
            confirmModal.value.onConfirm = null;
        });
    } else {
        closeConfirmModal();
    }
}

watch(
    () => page.props.flash?.success,
    (msg) => {
        if (msg) {
            successMessage.value = msg;
            showSuccessModal.value = true;
            dismissedFlash.value = false;
        }
    },
    { immediate: true }
);

function closeSuccessModal() {
    showSuccessModal.value = false;
    dismissedFlash.value = true;
}

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

function openGenerateUserModal() {
    showGenerateUserModal.value = true;
}

function closeGenerateUserModal() {
    showGenerateUserModal.value = false;
}

function onGenerateUserSuccess() {
    closeGenerateUserModal();
    dismissedFlash.value = false;
}

function onRolePermissionSaved(message) {
    successMessage.value = message || 'Hak akses berhasil disimpan.';
    showSuccessModal.value = true;
    dismissedFlash.value = false;
}

function confirmUserDelete(user) {
    openConfirmModal({
        title: 'Hapus Akun Pengguna',
        message: 'Yakin ingin menghapus akun pengguna ini? Tindakan ini tidak dapat dibatalkan.',
        itemName: `${user.name} (${user.username})`,
        onConfirm: (done) => {
            deletingUserId.value = user.id;
            router.delete(`/portal/users/${user.id}`, {
                preserveScroll: true,
                onFinish: () => {
                    deletingUserId.value = null;
                    done();
                },
            });
        },
    });
}

function confirmResetPassword(user) {
    const passInfo = user.pass_default ? ` (${user.pass_default})` : '';
    openConfirmModal({
        title: 'Reset Password Akun',
        message: `Kembalikan password akun "${user.name}" ke password default${passInfo}? Pengguna akan diwajibkan membuat password baru saat login berikutnya.`,
        itemName: `${user.name} (${user.username})`,
        confirmText: 'Ya, Reset Password',
        onConfirm: (done) => {
            resettingUserId.value = user.id;
            router.post(`/portal/users/${user.id}/reset-password`, {}, {
                preserveScroll: true,
                onFinish: () => {
                    resettingUserId.value = null;
                    done();
                },
            });
        },
    });
}

function copyPassDefault(user) {
    if (!user.pass_default) return;
    navigator.clipboard.writeText(user.pass_default).then(() => {
        copiedPassId.value = user.id;
        setTimeout(() => {
            if (copiedPassId.value === user.id) {
                copiedPassId.value = null;
            }
        }, 2000);
    });
}

function toggleUserStatus(user) {
    if (user.id === page.props.auth?.user?.id) {
        alert('Anda tidak dapat menonaktifkan akun yang sedang Anda gunakan.');
        return;
    }
    togglingUserId.value = user.id;
    router.patch(`/portal/users/${user.id}/toggle-status`, {}, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            togglingUserId.value = null;
        },
    });
}

function confirmDelete(year) {
    openConfirmModal({
        title: 'Hapus Tahun Ajaran',
        message: 'Yakin ingin menghapus Tahun Ajaran ini? Tindakan ini tidak dapat dibatalkan.',
        itemName: `${year.name} ${year.semester}`,
        onConfirm: (done) => {
            deletingId.value = year.id;
            router.delete(`/portal/academic-years/${year.id}`, {
                preserveScroll: true,
                onFinish: () => {
                    deletingId.value = null;
                    done();
                },
            });
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

    <div class="w-full p-4 md:px-6 md:py-5">
        <!-- Page Header -->
        <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl text-primary font-bold">
                    Manajemen Portal
                </h2>
                <p class="font-body-md text-body-md text-on-surface-variant mt-1">
                    Kelola Tahun Ajaran dan pengaturan sistem data yayasan
                </p>
            </div>
        </div>

        <!-- Flash Error Banner (jika ada error sistem) -->

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
        <div class="mb-4 flex border-b border-outline-variant/30 overflow-x-auto">
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
        <div v-show="activeTab === 'akademik'" class="space-y-6">
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
        <!-- School Levels & Units Section -->
        <div class="bg-surface-container-lowest rounded-2xl border border-primary/10 shadow-[0px_4px_20px_rgba(0,40,20,0.06)] overflow-hidden">
                <!-- Section Header -->
                <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary text-xl">apartment</span>
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
                                <th class="border border-outline-variant/60 px-5 py-3 text-left w-1/4">Program Pendidikan</th>
                                <th class="border border-outline-variant/60 px-5 py-3 text-left w-1/4">Kelas</th>
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
                                    <span class="material-symbols-outlined text-4xl mb-2 opacity-50">apartment</span>
                                    <p class="text-sm">Belum ada jenjang pendidikan.<br/>Silakan tambah jenjang pertama Anda.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab Content: Unit & Bidang -->
        <div v-show="activeTab === 'unit_bidang'" class="space-y-6">
            <!-- Diagram Susunan Organisasi (Rombak Tabel Bidang & Jabatan) -->
            <OrgChartDiagram
                :positions="organizationPositions"
                :departments="departments"
                :organization-levels="organizationLevels"
                @add-position="handleAddPosition"
                @edit-position="handleEditPosition"
                @delete-position="confirmPositionDelete"
                @add-department="openDepartmentCreateModal"
                @edit-department="openDepartmentEditModal"
                @delete-department="confirmDepartmentDelete"
            />
        </div>
        
        <!-- Tab Content: Pengguna -->
        <div v-show="activeTab === 'pengguna'" class="space-y-6">
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
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="openGenerateUserModal"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-surface-container-high hover:bg-surface-container-highest text-primary font-semibold text-xs sm:text-sm transition-all border border-primary/20 hover:border-primary/40 shadow-2xs cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[18px]">auto_mode</span>
                        <span>Generate Akun</span>
                    </button>
                    <button
                        type="button"
                        @click="openUserCreateModal"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-xs sm:text-sm transition-all shadow-xs hover:shadow-md cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[18px]">person_add</span>
                        <span>Tambah Akun</span>
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-xs sm:text-sm border-collapse border border-outline-variant/60">
                    <thead>
                        <tr class="bg-surface-container-low/70 text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                            <th class="border border-outline-variant/60 px-4 py-3 text-center w-12">No</th>
                            <th class="border border-outline-variant/60 px-5 py-3 text-left">Nama Lengkap</th>
                            <th class="border border-outline-variant/60 px-5 py-3 text-left">NIP / Username</th>
                            <th class="border border-outline-variant/60 px-4 py-3 text-center w-36">Pass Default</th>
                            <th class="border border-outline-variant/60 px-4 py-3 text-center w-28">Role</th>
                            <th class="border border-outline-variant/60 px-4 py-3 text-center w-32">Status Akses</th>
                            <th class="border border-outline-variant/60 px-4 py-3 text-center w-36">Dibuat Pada</th>
                            <th class="border border-outline-variant/60 px-4 py-3 text-center w-32">Aksi</th>
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
                            <!-- PASS DEFAULT -->
                            <td class="border border-outline-variant/60 px-4 py-3.5 text-center">
                                <div v-if="u.pass_default" class="inline-flex items-center gap-1.5 bg-surface-container-high/80 px-2.5 py-1 rounded-lg border border-outline-variant/40">
                                    <span class="font-mono text-xs font-bold text-on-surface tracking-wider">{{ u.pass_default }}</span>
                                    <button
                                        type="button"
                                        @click="copyPassDefault(u)"
                                        class="text-outline hover:text-primary transition-colors p-0.5 rounded cursor-pointer"
                                        :title="copiedPassId === u.id ? 'Berhasil disalin!' : 'Salin password default'"
                                    >
                                        <span class="material-symbols-outlined text-[15px]" :class="copiedPassId === u.id ? 'text-emerald-600' : ''">
                                            {{ copiedPassId === u.id ? 'check' : 'content_copy' }}
                                        </span>
                                    </button>
                                </div>
                                <span v-else class="text-on-surface-variant/40 text-xs">—</span>
                            </td>
                            <td class="border border-outline-variant/60 px-4 py-3.5 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider"
                                      :class="u.role === 'superadmin' ? 'bg-error/10 text-error border border-error/20' : 'bg-secondary-container/40 text-on-secondary-container border border-secondary-container/60'">
                                    {{ u.role }}
                                </span>
                            </td>
                            <!-- TOGGLE AKTIF / TIDAK -->
                            <td class="border border-outline-variant/60 px-4 py-3.5 text-center">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        type="button"
                                        role="switch"
                                        :aria-checked="u.is_active"
                                        :disabled="togglingUserId === u.id || u.id === $page.props.auth?.user?.id"
                                        @click="toggleUserStatus(u)"
                                        :class="[
                                            'relative inline-flex h-5 w-10 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary/20',
                                            u.id === $page.props.auth?.user?.id ? 'cursor-not-allowed opacity-50' : '',
                                            u.is_active ? 'bg-emerald-500' : 'bg-slate-300'
                                        ]"
                                        :title="u.id === $page.props.auth?.user?.id ? 'Tidak dapat menonaktifkan akun sendiri' : (u.is_active ? 'Klik untuk nonaktifkan akses akun' : 'Klik untuk aktifkan akses akun')"
                                    >
                                        <span
                                            aria-hidden="true"
                                            :class="[
                                                'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                                u.is_active ? 'translate-x-5' : 'translate-x-0'
                                            ]"
                                        >
                                            <span v-if="togglingUserId === u.id" class="material-symbols-outlined text-[10px] text-slate-500 animate-spin flex items-center justify-center h-full">
                                                sync
                                            </span>
                                        </span>
                                    </button>
                                    <span
                                        :class="[
                                            'text-xs font-semibold select-none',
                                            u.is_active ? 'text-emerald-700' : 'text-slate-400'
                                        ]"
                                    >
                                        {{ u.is_active ? 'Aktif' : 'Tidak' }}
                                    </span>
                                </div>
                            </td>
                            <td class="border border-outline-variant/60 px-4 py-3.5 text-center text-on-surface-variant text-xs">
                                {{ u.created_at }}
                            </td>
                            <td class="border border-outline-variant/60 px-4 py-3.5 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button
                                        type="button"
                                        @click="confirmResetPassword(u)"
                                        :disabled="resettingUserId === u.id"
                                        class="p-2 rounded-lg text-on-surface-variant hover:text-amber-600 hover:bg-amber-500/10 transition-colors cursor-pointer"
                                        title="Reset Password ke Default"
                                    >
                                        <span
                                            v-if="resettingUserId === u.id"
                                            class="material-symbols-outlined text-[18px] animate-spin text-amber-600"
                                        >sync</span>
                                        <span v-else class="material-symbols-outlined text-[18px]">lock_reset</span>
                                    </button>
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
                            <td colspan="8" class="border border-outline-variant/60 px-5 py-10 text-center text-on-surface-variant">
                                Belum ada data pengguna lainnya.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        </div>

        <!-- Tab Content: Hak Akses -->
        <div v-show="activeTab === 'hak_akses'" class="space-y-6">
            <RolePermissionPanel
                :permissions-data="permissionsData"
                :default-features="defaultFeatures"
                :primary-roles="primaryRoles"
                :available-roles="availableRoles"
                :departments="departments"
                :organization-positions="organizationPositions"
                @saved="onRolePermissionSaved"
            />
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
        :available-roles="availableRoles"
        :school-roles="schoolRoles"
        :dept-roles="deptRoles"
        @close="closeUserModal"
        @success="onUserFormSuccess"
    />

    <!-- Generate User Modal -->
    <GenerateUserModal
        :show="showGenerateUserModal"
        :employees="employees"
        @close="closeGenerateUserModal"
        @success="onGenerateUserSuccess"
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
        :departments="departments"
        :all-positions="organizationPositions"
        :preset-level="presetPositionLevel"
        :preset-parent-id="presetPositionParentId"
        :preset-department-id="presetPositionDepartmentId"
        :mode-title="positionModalTitle"
        @close="closePositionModal"
        @success="onPositionSuccess"
    />

    <!-- Pop-up Modal Sukses -->
    <SuccessModal
        :show="showSuccessModal"
        :message="successMessage"
        @close="closeSuccessModal"
    />

    <!-- Pop-up Modal Konfirmasi Hapus -->
    <ConfirmDeleteModal
        :show="confirmModal.show"
        :title="confirmModal.title"
        :message="confirmModal.message"
        :item-name="confirmModal.itemName"
        :confirm-text="confirmModal.confirmText"
        :is-deleting="confirmModal.isDeleting"
        :hide-confirm-button="confirmModal.hideConfirmButton"
        @close="closeConfirmModal"
        @confirm="handleConfirmModalAction"
    />

        </template>
