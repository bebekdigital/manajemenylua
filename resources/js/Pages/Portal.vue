<script setup>
import { ref, reactive, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
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
import { usePwaInstall } from '@/Composables/usePwaInstall';

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
    existingPrograms: {
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
    permissions: {
        type: Object,
        default: () => ({
            academic: { can_view: false, can_create: false, can_update: false, can_delete: false },
            organization: { can_view: false, can_create: false, can_update: false, can_delete: false },
        }),
    },
});

const page = usePage();
const { installPwa, isInstalled } = usePwaInstall();

// Tab definitions
const allTabs = [
    { id: 'akademik', label: 'Akademik', icon: 'school' },
    { id: 'unit_bidang', label: 'Organisasi', icon: 'domain' },
    { id: 'pengguna', label: 'Pengguna', icon: 'manage_accounts', superadminOnly: true },
    { id: 'hak_akses', label: 'Hak Akses', icon: 'shield_person', superadminOnly: true },
    { id: 'aplikasi_pwa', label: 'Aplikasi (PWA)', icon: 'install_mobile' },
];

const tabs = computed(() => {
    const isSuperadmin = page.props.auth?.user?.role === 'superadmin';
    return allTabs.filter((t) => {
        if (t.superadminOnly) {
            return isSuperadmin;
        }
        if (t.id === 'akademik') {
            return isSuperadmin || Boolean(props.permissions?.academic?.can_view);
        }
        if (t.id === 'unit_bidang') {
            return isSuperadmin || Boolean(props.permissions?.organization?.can_view);
        }
        return true;
    });
});

const canCreateAcademic = computed(() => {
    return page.props.auth?.user?.role === 'superadmin' || Boolean(props.permissions?.academic?.can_create);
});
const canUpdateAcademic = computed(() => {
    return page.props.auth?.user?.role === 'superadmin' || Boolean(props.permissions?.academic?.can_update);
});
const canDeleteAcademic = computed(() => {
    return page.props.auth?.user?.role === 'superadmin' || Boolean(props.permissions?.academic?.can_delete);
});

const canCreateOrg = computed(() => {
    return page.props.auth?.user?.role === 'superadmin' || Boolean(props.permissions?.organization?.can_create);
});
const canUpdateOrg = computed(() => {
    return page.props.auth?.user?.role === 'superadmin' || Boolean(props.permissions?.organization?.can_update);
});
const canDeleteOrg = computed(() => {
    return page.props.auth?.user?.role === 'superadmin' || Boolean(props.permissions?.organization?.can_delete);
});

const getInitialTab = () => {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        if (tabParam && tabs.value.some((t) => t.id === tabParam)) {
            return tabParam;
        }
    }
    return tabs.value[0]?.id || 'akademik';
};

const activeTab = ref(getInitialTab());

watch(() => page.url, (newUrl) => {
    try {
        const parsed = new URL(newUrl, window.location.origin);
        const tabParam = parsed.searchParams.get('tab');
        if (tabParam && tabs.value.some((t) => t.id === tabParam)) {
            activeTab.value = tabParam;
        }
    } catch {
        // ignore
    }
}, { immediate: true });

// Tabs Horizontal Scroll Controls
const tabsContainerRef = ref(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(false);

function checkScroll() {
    if (!tabsContainerRef.value) return;
    const { scrollLeft, scrollWidth, clientWidth } = tabsContainerRef.value;
    canScrollLeft.value = scrollLeft > 10;
    canScrollRight.value = scrollLeft + clientWidth < scrollWidth - 10;
}

function scrollTabs(direction) {
    if (!tabsContainerRef.value) return;
    const scrollAmount = 200;
    tabsContainerRef.value.scrollBy({
        left: direction === 'left' ? -scrollAmount : scrollAmount,
        behavior: 'smooth',
    });
}

function selectTab(id) {
    activeTab.value = id;
    if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', id);
        window.history.replaceState({}, '', url.toString());
    }
    nextTick(() => {
        const activeBtn = tabsContainerRef.value?.querySelector(`[data-tab-id="${id}"]`);
        if (activeBtn) {
            activeBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
        checkScroll();
    });
}

// User Search Query & Filtering
const userSearchQuery = ref('');
const filteredUsers = computed(() => {
    const list = props.users || [];
    const q = userSearchQuery.value.trim().toLowerCase();
    if (!q) return list;
    return list.filter((u) => {
        const nameMatch = u.name?.toLowerCase().includes(q);
        const userMatch = u.username?.toLowerCase().includes(q);
        const roleMatch = u.role?.toLowerCase().includes(q);
        return nameMatch || userMatch || roleMatch;
    });
});

// Floating Toast Notification State & Logic (Melayang dengan progress bar mundur)
const toast = reactive({
    show: false,
    type: 'success', // 'success' | 'error'
    title: '',
    message: '',
    progress: 100,
});
let toastTimer = null;
let toastInterval = null;

function showToast(type, message, title = null) {
    if (!message) return;

    // Otomatis tutup modal konfirmasi hapus bila masih terbuka
    isDeleteConfirmModalOpen.value = false;
    deleteConfirmData.isDeleting = false;

    if (toastTimer) {
        clearTimeout(toastTimer);
        toastTimer = null;
    }
    if (toastInterval) {
        clearInterval(toastInterval);
        toastInterval = null;
    }

    toast.type = type;
    toast.title = title || (type === 'success' ? 'Berhasil' : 'Terjadi Kesalahan');
    toast.message = message;
    toast.progress = 100;
    toast.show = true;

    const duration = 4000;
    const startTime = Date.now();

    toastInterval = setInterval(() => {
        const elapsed = Date.now() - startTime;
        const remaining = Math.max(0, 100 - (elapsed / duration) * 100);
        toast.progress = remaining;
        if (remaining <= 0) {
            clearInterval(toastInterval);
            toastInterval = null;
        }
    }, 25);

    toastTimer = setTimeout(() => {
        closeToast();
    }, duration);
}

function closeToast() {
    toast.show = false;
    if (toastTimer) {
        clearTimeout(toastTimer);
        toastTimer = null;
    }
    if (toastInterval) {
        clearInterval(toastInterval);
        toastInterval = null;
    }
}

// Pantau flash message dari server
watch(
    () => [page.props.flash?.success, page.props.flash?.error],
    ([newSuccess, newError]) => {
        if (newSuccess) {
            showToast('success', newSuccess, 'Berhasil Disimpan');
        } else if (newError) {
            showToast('error', newError, 'Terjadi Kesalahan');
        }
    },
    { deep: true, immediate: true }
);

// State & Fungsi Pop-up Modal Konfirmasi Hapus Kustom
const isDeleteConfirmModalOpen = ref(false);
const deleteConfirmData = reactive({
    title: 'Konfirmasi Hapus Data',
    message: '',
    itemName: '',
    confirmText: 'Ya, Hapus',
    hideConfirmButton: false,
    isDeleting: false,
    onConfirm: null,
});

function openConfirmModal(config) {
    deleteConfirmData.title = config.title || 'Konfirmasi Hapus Data';
    deleteConfirmData.message = config.message || 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.';
    deleteConfirmData.itemName = config.itemName || '';
    deleteConfirmData.confirmText = config.confirmText || 'Ya, Hapus';
    deleteConfirmData.hideConfirmButton = !!config.hideConfirmButton;
    deleteConfirmData.isDeleting = false;
    deleteConfirmData.onConfirm = config.onConfirm || null;
    isDeleteConfirmModalOpen.value = true;
}

function closeConfirmModal() {
    if (deleteConfirmData.isDeleting) return;
    isDeleteConfirmModalOpen.value = false;
    deleteConfirmData.onConfirm = null;
}

function handleConfirmModalAction() {
    if (typeof deleteConfirmData.onConfirm === 'function') {
        deleteConfirmData.isDeleting = true;
        deleteConfirmData.onConfirm(() => {
            deleteConfirmData.isDeleting = false;
            isDeleteConfirmModalOpen.value = false;
            deleteConfirmData.onConfirm = null;
        });
    } else {
        closeConfirmModal();
    }
}

// Academic Year Modal State
const showFormModal = ref(false);
const editingYear = ref(null);
const deletingId = ref(null);

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
    showToast('success', 'Data Tahun Ajaran berhasil disimpan.');
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
                onSuccess: () => {
                    showToast('success', `Tahun Ajaran "${year.name}" berhasil dihapus.`);
                },
            });
        },
    });
}

// School Level Modals
const showLevelModal = ref(false);
const editingLevel = ref(null);

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
    showToast('success', 'Jenjang pendidikan berhasil disimpan.');
}

function confirmLevelDelete(level) {
    openConfirmModal({
        title: 'Hapus Jenjang Pendidikan',
        message: 'Yakin ingin menghapus jenjang pendidikan ini beserta unit dan kelas di dalamnya?',
        itemName: level.name,
        onConfirm: (done) => {
            router.delete(`/portal/school-levels/${level.id}`, {
                preserveScroll: true,
                onFinish: done,
                onSuccess: () => {
                    showToast('success', `Jenjang "${level.name}" berhasil dihapus.`);
                },
            });
        },
    });
}

// School Unit Modals
const showUnitModal = ref(false);
const editingUnit = ref(null);
const selectedLevelForUnit = ref(null);

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
    showToast('success', 'Unit sekolah berhasil disimpan.');
}

function confirmUnitDelete(unit, level) {
    openConfirmModal({
        title: 'Hapus Unit Sekolah',
        message: level ? `Yakin ingin menghapus unit "${unit.name}" dari jenjang ${level.name}?` : `Yakin ingin menghapus unit "${unit.name}" ini beserta kelas di dalamnya?`,
        itemName: unit.name,
        onConfirm: (done) => {
            router.delete(`/portal/school-units/${unit.id}`, {
                data: { level_id: level?.id },
                preserveScroll: true,
                onFinish: done,
                onSuccess: () => {
                    showToast('success', `Unit "${unit.name}" berhasil dihapus.`);
                },
            });
        },
    });
}

// Program Modals
const showProgramModal = ref(false);
const editingProgram = ref(null);
const selectedUnitForProgram = ref(null);

const allExistingPrograms = computed(() => {
    const set = new Set();

    (props.schoolLevels || []).forEach(level => {
        (level.units || []).forEach(unit => {
            (unit.programs || []).forEach(p => {
                const name = typeof p === 'string' ? p : p?.name;
                if (name && typeof name === 'string' && name.trim()) {
                    set.add(name.trim());
                }
            });
        });
    });

    (props.existingPrograms || []).forEach(p => {
        const name = typeof p === 'string' ? p : p?.name;
        if (name && typeof name === 'string' && name.trim()) {
            set.add(name.trim());
        }
    });

    return Array.from(set).sort((a, b) => a.localeCompare(b));
});

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
    showToast('success', 'Program pendidikan berhasil disimpan.');
}

function confirmProgramDelete(program, unit) {
    openConfirmModal({
        title: 'Hapus Program Pendidikan',
        message: unit ? `Yakin ingin menghapus program "${program.name}" dari unit ${unit.name}?` : 'Yakin ingin menghapus program pendidikan ini?',
        itemName: program.name,
        onConfirm: (done) => {
            router.delete(`/portal/school-programs/${program.id}`, {
                data: { unit_id: unit?.id },
                preserveScroll: true,
                onFinish: done,
                onSuccess: () => {
                    showToast('success', `Program "${program.name}" berhasil dihapus.`);
                },
            });
        },
    });
}

// School Class Modals
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
    showToast('success', 'Kelas berhasil disimpan.');
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
                onSuccess: () => {
                    showToast('success', `Kelas "${cls.name}" berhasil dihapus.`);
                },
            });
        },
    });
}

// Department Modals
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
    showToast('success', 'Unit yayasan berhasil disimpan.');
}

function confirmDepartmentDelete(department) {
    if (department.positions && department.positions.length > 0) {
        openConfirmModal({
            title: 'Tidak Dapat Dihapus',
            message: `Unit "${department.name}" masih memiliki ${department.positions.length} jabatan terdaftar. Hapus semua jabatan di dalamnya terlebih dahulu.`,
            itemName: department.name,
            hideConfirmButton: true,
        });
        return;
    }

    openConfirmModal({
        title: 'Hapus Unit Yayasan',
        message: 'Yakin ingin menghapus unit yayasan ini?',
        itemName: department.name,
        onConfirm: (done) => {
            router.delete(`/portal/departments/${department.id}`, {
                preserveScroll: true,
                onFinish: done,
                onSuccess: () => {
                    showToast('success', `Unit "${department.name}" berhasil dihapus.`);
                },
            });
        },
    });
}

// Department Position Modals
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

function closePositionModal() {
    showPositionModal.value = false;
    editingPosition.value = null;
    selectedDepartmentForPosition.value = null;
    presetPositionParentId.value = null;
    presetPositionDepartmentId.value = null;
}

function onPositionSuccess() {
    closePositionModal();
    showToast('success', 'Jabatan organisasi berhasil disimpan.');
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
                onSuccess: () => {
                    showToast('success', `Jabatan "${position.name}" berhasil dihapus.`);
                },
            });
        },
    });
}

// User Modal Functions
const showUserModal = ref(false);
const showGenerateUserModal = ref(false);
const editingUser = ref(null);
const deletingUserId = ref(null);
const togglingUserId = ref(null);
const resettingUserId = ref(null);
const copiedPassId = ref(null);

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
    showToast('success', 'Data akun pengguna berhasil disimpan.');
}

function openGenerateUserModal() {
    showGenerateUserModal.value = true;
}

function closeGenerateUserModal() {
    showGenerateUserModal.value = false;
}

function onGenerateUserSuccess() {
    closeGenerateUserModal();
    showToast('success', 'Akun pengguna berhasil digenerate.');
}

function onRolePermissionSaved(message) {
    showToast('success', message || 'Hak akses berhasil disimpan.');
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
                onSuccess: () => {
                    showToast('success', `Akun "${user.name}" berhasil dihapus.`);
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
                onSuccess: () => {
                    showToast('success', `Password akun "${user.name}" berhasil di-reset.`);
                },
            });
        },
    });
}

function copyPassDefault(user) {
    if (!user.pass_default) return;
    navigator.clipboard.writeText(user.pass_default).then(() => {
        copiedPassId.value = user.id;
        showToast('success', `Password default "${user.pass_default}" berhasil disalin.`);
        setTimeout(() => {
            if (copiedPassId.value === user.id) {
                copiedPassId.value = null;
            }
        }, 2000);
    });
}

function toggleUserStatus(user) {
    if (user.id === page.props.auth?.user?.id) {
        showToast('error', 'Anda tidak dapat menonaktifkan akun yang sedang Anda gunakan.');
        return;
    }
    togglingUserId.value = user.id;
    router.patch(`/portal/users/${user.id}/toggle-status`, {}, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            togglingUserId.value = null;
        },
        onSuccess: () => {
            showToast('success', `Status akses akun "${user.name}" berhasil diubah.`);
        },
    });
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    try {
        const d = new Date(dateStr + (dateStr.includes('T') ? '' : 'T00:00:00'));
        return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch {
        return dateStr;
    }
}

function getInitials(name) {
    if (!name) return '??';
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((w) => w[0]?.toUpperCase() || '')
        .join('');
}

onMounted(() => {
    window.addEventListener('resize', checkScroll);
    nextTick(() => {
        checkScroll();
        const activeBtn = tabsContainerRef.value?.querySelector(`[data-tab-id="${activeTab.value}"]`);
        if (activeBtn) {
            activeBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
    });
});

onUnmounted(() => {
    window.removeEventListener('resize', checkScroll);
});
</script>

<template>
    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop pb-24 md:pb-margin-desktop bg-white min-h-screen">
        <Head title="Pengaturan Sistem & Master Data - Yayasan Li Ulil Albab" />

        <div class="max-w-[1440px] mx-auto space-y-6">
            <!-- Header Card Pengaturan (Sama seperti Data Pegawai & Siswa) -->
            <div class="relative bg-gradient-to-br from-white via-emerald-50/25 to-slate-50/70 rounded-3xl border border-slate-200/90 p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <!-- Efek Cahaya Lembut Statis di Latar Belakang -->
                <div class="absolute inset-0 rounded-3xl overflow-hidden pointer-events-none">
                    <div class="absolute -top-20 -right-20 w-72 h-72 rounded-full bg-gradient-to-br from-emerald-200/40 via-teal-100/25 to-transparent blur-3xl"></div>
                    <div class="absolute -bottom-20 -left-16 w-64 h-64 rounded-full bg-gradient-to-tr from-sky-200/35 via-emerald-100/20 to-transparent blur-3xl"></div>
                </div>

                <div class="relative flex items-center gap-4 sm:gap-5 min-w-0 flex-1 z-10">
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-emerald-900 text-yellow-200 flex items-center justify-center shrink-0 shadow-xs border border-emerald-950/20">
                        <span class="material-symbols-outlined text-3xl">tune</span>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                            Pengaturan Sistem &amp; Master Data
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium leading-relaxed">
                            Kelola Tahun Ajaran, master jenjang, unit sekolah, struktur organisasi, pengguna, dan aplikasi yayasan
                        </p>
                    </div>
                </div>

                <!-- Tombol Cepat PWA di Header -->
                <div class="relative z-10 flex items-center gap-2.5 shrink-0">
                    <button
                        type="button"
                        @click="installPwa"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold bg-white hover:bg-slate-50 text-slate-800 border border-slate-200/90 shadow-2xs transition-all cursor-pointer active:scale-95"
                        title="Pasang aplikasi di HP atau komputer"
                    >
                        <span class="material-symbols-outlined text-[18px] text-emerald-700">install_mobile</span>
                        <span>{{ isInstalled ? 'Aplikasi Terpasang' : 'Pasang Aplikasi (PWA)' }}</span>
                    </button>
                </div>
            </div>

            <!-- Segmented Capsule Tabs Navigation (Sama seperti di Detail Data Pegawai) -->
            <div class="relative z-10">
                <!-- Left Scroll Arrow & Fade Hint -->
                <Transition
                    enter-active-class="transition duration-150 ease-out"
                    enter-from-class="opacity-0 -translate-x-2"
                    enter-to-class="opacity-100 translate-x-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-x-0"
                    leave-to-class="opacity-0 -translate-x-2"
                >
                    <div
                        v-if="canScrollLeft"
                        class="absolute left-0 top-0 bottom-0 z-20 flex items-center pr-6 pl-1 bg-gradient-to-r from-white via-white/95 to-transparent pointer-events-none"
                    >
                        <button
                            type="button"
                            @click="scrollTabs('left')"
                            class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white shadow-md border border-slate-200 text-slate-700 flex items-center justify-center hover:bg-slate-50 pointer-events-auto active:scale-95 transition-transform"
                            title="Gulir tab ke kiri"
                        >
                            <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                        </button>
                    </div>
                </Transition>

                <!-- Tabs Scroll Container -->
                <div
                    ref="tabsContainerRef"
                    @scroll="checkScroll"
                    class="overflow-x-auto no-scrollbar scroll-smooth py-1"
                >
                    <div class="bg-slate-100 p-1.5 rounded-2xl sm:rounded-full inline-flex items-center gap-1 border border-slate-200/90 min-w-max">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            :data-tab-id="tab.id"
                            type="button"
                            @click="selectTab(tab.id)"
                            :class="[
                                'inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs sm:text-sm transition-all duration-150 shrink-0 whitespace-nowrap',
                                activeTab === tab.id
                                    ? 'bg-emerald-100 text-emerald-950 font-bold border border-emerald-300 shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900 font-medium border border-transparent hover:bg-slate-200/60 cursor-pointer'
                            ]"
                        >
                            <span class="material-symbols-outlined text-[18px]">{{ tab.icon }}</span>
                            <span>{{ tab.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Right Scroll Arrow & Fade Hint -->
                <Transition
                    enter-active-class="transition duration-150 ease-out"
                    enter-from-class="opacity-0 translate-x-2"
                    enter-to-class="opacity-100 translate-x-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-x-0"
                    leave-to-class="opacity-0 translate-x-2"
                >
                    <div
                        v-if="canScrollRight"
                        class="absolute right-0 top-0 bottom-0 z-20 flex items-center pl-6 pr-1 bg-gradient-to-l from-white via-white/95 to-transparent pointer-events-none"
                    >
                        <button
                            type="button"
                            @click="scrollTabs('right')"
                            class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white shadow-md border border-slate-200 text-slate-700 flex items-center justify-center hover:bg-slate-50 pointer-events-auto active:scale-95 transition-transform"
                            title="Ada tab lain di sebelah kanan"
                        >
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </button>
                    </div>
                </Transition>
            </div>

            <!-- TAB 1: AKADEMIK -->
            <div v-show="activeTab === 'akademik'" class="space-y-6">
                <!-- 1. TAHUN AJARAN SECTION -->
                <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-7 shadow-xs">
                    <!-- Section Header -->
                    <div class="flex items-center justify-between gap-3 pb-3 mb-4 border-b border-slate-100 flex-wrap">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block shrink-0"></span>
                                <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">
                                    Daftar Tahun Ajaran
                                </h4>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200/80">
                                    {{ academicYears.length }} Periode
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                Kelola periode akademik dan tentukan tahun ajaran yang aktif untuk seluruh sistem yayasan
                            </p>
                        </div>

                        <button
                            v-if="canCreateAcademic"
                            type="button"
                            @click="openCreateModal"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                            title="Tambah Tahun Ajaran Baru"
                        >
                            <span class="material-symbols-outlined text-[16px] sm:text-[18px]">add</span>
                            <span>Tambah TA</span>
                        </button>
                    </div>

                    <!-- Desktop Table View -->
                    <div class="hidden md:block overflow-hidden rounded-2xl border border-slate-200/90 shadow-2xs">
                        <table class="w-full text-left border-collapse text-xs sm:text-sm">
                            <thead class="bg-slate-50/90 text-slate-700 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200/90">
                                <tr>
                                    <th class="py-3.5 px-3 text-center w-12 text-slate-500 font-semibold">No</th>
                                    <th class="py-3.5 px-5">Tahun Ajaran</th>
                                    <th class="py-3.5 px-4 text-center w-36">Semester</th>
                                    <th class="py-3.5 px-5">Periode Tanggal</th>
                                    <th class="py-3.5 px-4 text-center w-32">Jumlah Kelas</th>
                                    <th class="py-3.5 px-3 text-center w-28">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr
                                    v-for="(year, index) in academicYears"
                                    :key="year.id"
                                    class="hover:bg-slate-50/70 transition-colors"
                                >
                                    <td class="py-3.5 px-3 text-center text-slate-400 font-mono text-xs">
                                        {{ index + 1 }}
                                    </td>
                                    <td class="py-3.5 px-5">
                                        <span class="font-bold text-slate-900 text-sm">{{ year.name }}</span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border"
                                            :class="year.semester === 'Ganjil'
                                                ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                                : 'bg-teal-50 text-teal-800 border-teal-200'"
                                        >
                                            {{ year.semester }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-5 text-slate-600">
                                        <div class="inline-flex items-center gap-1.5 text-xs font-medium">
                                            <span class="material-symbols-outlined text-[16px] text-slate-400">calendar_today</span>
                                            <span>{{ formatDate(year.start_date) }} — {{ formatDate(year.end_date) }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200/80">
                                            {{ year.classrooms_count || 0 }} Kelas
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <div class="inline-flex items-center justify-center gap-1">
                                            <button
                                                v-if="canUpdateAcademic"
                                                type="button"
                                                @click="openEditModal(year)"
                                                class="p-1.5 rounded-lg text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 transition-colors cursor-pointer"
                                                title="Edit Tahun Ajaran"
                                            >
                                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                            </button>
                                            <button
                                                v-if="canDeleteAcademic"
                                                type="button"
                                                @click="confirmDelete(year)"
                                                :disabled="deletingId === year.id"
                                                class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                                title="Hapus Tahun Ajaran"
                                            >
                                                <span v-if="deletingId === year.id" class="material-symbols-outlined text-[18px] animate-spin text-rose-600">sync</span>
                                                <span v-else class="material-symbols-outlined text-[18px]">delete</span>
                                            </button>
                                            <span v-if="!canUpdateAcademic && !canDeleteAcademic" class="text-xs text-slate-400 italic">-</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="academicYears.length === 0">
                                    <td colspan="6" class="py-12 text-center text-slate-500">
                                        <div class="py-8 text-center border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50 max-w-md mx-auto">
                                            <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">calendar_month</span>
                                            <p class="text-xs font-semibold text-slate-600">Belum ada tahun ajaran terdaftar</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card View (md:hidden) -->
                    <div class="md:hidden space-y-3">
                        <div
                            v-for="(year, index) in academicYears"
                            :key="year.id"
                            class="bg-slate-50/80 rounded-2xl p-4 border border-slate-200/80 space-y-3 hover:border-slate-300 transition-colors shadow-2xs"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 font-mono text-xs font-bold flex items-center justify-center shrink-0">
                                        {{ index + 1 }}
                                    </span>
                                    <h5 class="text-sm font-bold text-slate-900 leading-snug">
                                        {{ year.name }}
                                    </h5>
                                </div>
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold border shrink-0"
                                    :class="year.semester === 'Ganjil'
                                        ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                        : 'bg-teal-50 text-teal-800 border-teal-200'"
                                >
                                    {{ year.semester }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs pt-1 border-t border-slate-200/60">
                                <div>
                                    <span class="text-[11px] text-slate-400 block font-medium">Periode:</span>
                                    <span class="font-semibold text-slate-700 leading-relaxed">
                                        {{ formatDate(year.start_date) }} — {{ formatDate(year.end_date) }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-[11px] text-slate-400 block font-medium">Jumlah Kelas:</span>
                                    <span class="font-semibold text-slate-700">
                                        {{ year.classrooms_count || 0 }} Kelas
                                    </span>
                                </div>
                            </div>

                            <div v-if="canUpdateAcademic || canDeleteAcademic" class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200/60">
                                <button
                                    v-if="canUpdateAcademic"
                                    type="button"
                                    @click="openEditModal(year)"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-white text-slate-700 border border-slate-200 hover:bg-slate-100 transition-colors cursor-pointer"
                                >
                                    <span class="material-symbols-outlined text-[15px]">edit</span>
                                    <span>Edit</span>
                                </button>
                                <button
                                    v-if="canDeleteAcademic"
                                    type="button"
                                    @click="confirmDelete(year)"
                                    :disabled="deletingId === year.id"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 border border-rose-200 transition-colors cursor-pointer"
                                >
                                    <span v-if="deletingId === year.id" class="material-symbols-outlined text-[15px] animate-spin">sync</span>
                                    <span v-else class="material-symbols-outlined text-[15px]">delete</span>
                                    <span>Hapus</span>
                                </button>
                            </div>
                        </div>

                        <div v-if="academicYears.length === 0" class="py-12 text-center border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                            <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">calendar_month</span>
                            <p class="text-xs font-semibold text-slate-600">Belum ada tahun ajaran terdaftar</p>
                        </div>
                    </div>
                </div>

                <!-- 2. JENJANG & UNIT SEKOLAH SECTION -->
                <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-7 shadow-xs">
                    <!-- Section Header -->
                    <div class="flex items-center justify-between gap-3 pb-3 mb-4 border-b border-slate-100 flex-wrap">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block shrink-0"></span>
                                <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">
                                    Jenjang, Unit Sekolah, Program &amp; Kelas
                                </h4>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200/80">
                                    {{ schoolLevels.length }} Jenjang
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                Kelola struktur jenjang pendidikan, unit sekolah, program studi, dan master kelas yayasan
                            </p>
                        </div>

                        <button
                            v-if="canCreateAcademic"
                            type="button"
                            @click="openLevelCreateModal"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                            title="Tambah Jenjang Pendidikan Baru"
                        >
                            <span class="material-symbols-outlined text-[16px] sm:text-[18px]">add</span>
                            <span>Tambah Jenjang</span>
                        </button>
                    </div>

                    <!-- Desktop Table View -->
                    <div class="hidden md:block overflow-hidden rounded-2xl border border-slate-200/90 shadow-2xs">
                        <table class="w-full text-xs sm:text-sm border-collapse text-left">
                            <thead class="bg-slate-50/90 text-slate-700 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200/90">
                                <tr>
                                    <th class="py-3.5 px-5 w-1/4">Jenjang</th>
                                    <th class="py-3.5 px-5 w-1/4">Unit Sekolah</th>
                                    <th class="py-3.5 px-5 w-1/4">Program Pendidikan</th>
                                    <th class="py-3.5 px-5 w-1/4">Kelas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <template v-for="level in schoolLevels" :key="level.id">
                                    <!-- Jika jenjang belum memiliki unit -->
                                    <tr v-if="!level.units || level.units.length === 0" class="hover:bg-slate-50/70 transition-colors">
                                        <td class="px-5 py-4 align-top bg-slate-50/40 border-r border-slate-100">
                                            <div class="font-bold text-slate-900 text-sm mb-2">{{ level.name }}</div>
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <button v-if="canUpdateAcademic" type="button" @click="openLevelEditModal(level)" class="p-1.5 rounded-lg text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 transition-colors cursor-pointer" title="Edit Jenjang">
                                                    <span class="material-symbols-outlined text-[16px]">edit</span>
                                                </button>
                                                <button v-if="canDeleteAcademic" type="button" @click="confirmLevelDelete(level)" class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer" title="Hapus Jenjang">
                                                    <span class="material-symbols-outlined text-[16px]">delete</span>
                                                </button>
                                                <button v-if="canCreateAcademic" type="button" @click="openUnitCreateModal(level)" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 hover:bg-emerald-100 transition-colors text-xs font-bold border border-emerald-200 cursor-pointer">
                                                    <span class="material-symbols-outlined text-[14px]">add</span> Unit Baru
                                                </button>
                                            </div>
                                        </td>
                                        <td colspan="3" class="px-5 py-4 align-middle text-slate-400 italic text-xs bg-slate-50/20">
                                            Belum ada unit sekolah ditambahkan di jenjang ini.
                                        </td>
                                    </tr>

                                    <!-- Jika jenjang memiliki unit -->
                                    <template v-else>
                                        <tr v-for="(unit, index) in level.units" :key="unit.id" class="hover:bg-slate-50/70 transition-colors">
                                            <!-- Kolom Jenjang (Rowspan baris pertama) -->
                                            <td v-if="index === 0" :rowspan="level.units.length" class="px-5 py-4 align-top bg-slate-50/40 border-r border-slate-100">
                                                <div class="font-bold text-slate-900 text-sm mb-2">{{ level.name }}</div>
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <button v-if="canUpdateAcademic" type="button" @click="openLevelEditModal(level)" class="p-1.5 rounded-lg text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 transition-colors cursor-pointer" title="Edit Jenjang">
                                                        <span class="material-symbols-outlined text-[16px]">edit</span>
                                                    </button>
                                                    <button v-if="canDeleteAcademic" type="button" @click="confirmLevelDelete(level)" class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer" title="Hapus Jenjang">
                                                        <span class="material-symbols-outlined text-[16px]">delete</span>
                                                    </button>
                                                    <button v-if="canCreateAcademic" type="button" @click="openUnitCreateModal(level)" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 hover:bg-emerald-100 transition-colors text-xs font-bold border border-emerald-200 cursor-pointer">
                                                        <span class="material-symbols-outlined text-[14px]">add</span> Unit Baru
                                                    </button>
                                                </div>
                                            </td>

                                            <!-- Kolom Unit Sekolah -->
                                            <td class="px-5 py-4 align-top border-r border-slate-100">
                                                <div class="flex items-start justify-between gap-2">
                                                    <span class="font-bold text-slate-900 text-xs sm:text-sm">{{ unit.name }}</span>
                                                    <div class="flex items-center gap-1 shrink-0">
                                                        <button v-if="canUpdateAcademic" type="button" @click="openUnitEditModal(unit, level)" class="p-1 rounded-lg text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 transition-colors cursor-pointer" title="Edit Unit">
                                                            <span class="material-symbols-outlined text-[16px]">edit</span>
                                                        </button>
                                                        <button v-if="canDeleteAcademic" type="button" @click="confirmUnitDelete(unit, level)" class="p-1 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer" title="Hapus Unit">
                                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Kolom Program -->
                                            <td class="px-5 py-4 align-top border-r border-slate-100">
                                                <div class="flex flex-col gap-2">
                                                    <div v-if="unit.programs && unit.programs.length > 0" class="flex flex-wrap gap-1.5">
                                                        <div v-for="program in unit.programs" :key="program.id" class="inline-flex items-center gap-1 bg-slate-50 border border-slate-200 pl-2.5 pr-1 py-1 rounded-lg text-xs font-medium text-slate-800">
                                                            <span>{{ program.name }}</span>
                                                            <div class="flex items-center ml-1">
                                                                <button v-if="canUpdateAcademic" type="button" @click="openProgramEditModal(program, unit)" class="text-slate-400 hover:text-emerald-700 p-0.5 rounded cursor-pointer" title="Edit Program">
                                                                    <span class="material-symbols-outlined text-[14px]">edit</span>
                                                                </button>
                                                                <button v-if="canDeleteAcademic" type="button" @click="confirmProgramDelete(program, unit)" class="text-slate-400 hover:text-rose-600 p-0.5 rounded cursor-pointer" title="Hapus Program">
                                                                    <span class="material-symbols-outlined text-[14px]">close</span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div v-else class="text-xs text-slate-400 italic">Belum ada program.</div>

                                                    <div v-if="canCreateAcademic">
                                                        <button type="button" @click="openProgramCreateModal(unit)" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-800 hover:bg-emerald-50 px-2 py-1 rounded-md transition-colors border border-emerald-200 cursor-pointer">
                                                            <span class="material-symbols-outlined text-[14px]">add</span> Tambah Program
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Kolom Kelas -->
                                            <td class="px-5 py-4 align-top">
                                                <div class="flex flex-col gap-2">
                                                    <div v-if="unit.classes && unit.classes.length > 0" class="flex flex-wrap gap-1.5">
                                                        <div v-for="cls in unit.classes" :key="cls.id" class="inline-flex items-center gap-1 bg-slate-50 border border-slate-200 pl-2.5 pr-1 py-1 rounded-lg text-xs font-medium text-slate-800">
                                                            <span>{{ cls.name }}</span>
                                                            <div class="flex items-center ml-1">
                                                                <button v-if="canUpdateAcademic" type="button" @click="openClassEditModal(cls, unit)" class="text-slate-400 hover:text-emerald-700 p-0.5 rounded cursor-pointer" title="Edit Kelas">
                                                                    <span class="material-symbols-outlined text-[14px]">edit</span>
                                                                </button>
                                                                <button v-if="canDeleteAcademic" type="button" @click="confirmClassDelete(cls)" class="text-slate-400 hover:text-rose-600 p-0.5 rounded cursor-pointer" title="Hapus Kelas">
                                                                    <span class="material-symbols-outlined text-[14px]">close</span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div v-else class="text-xs text-slate-400 italic">Belum ada kelas.</div>

                                                    <div v-if="canCreateAcademic">
                                                        <button type="button" @click="openClassCreateModal(unit)" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-800 hover:bg-emerald-50 px-2 py-1 rounded-md transition-colors border border-emerald-200 cursor-pointer">
                                                            <span class="material-symbols-outlined text-[14px]">add</span> Tambah Kelas
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </template>

                                <tr v-if="schoolLevels.length === 0">
                                    <td colspan="4" class="py-12 text-center text-slate-500">
                                        <div class="py-8 text-center border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50 max-w-md mx-auto">
                                            <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">apartment</span>
                                            <p class="text-xs font-semibold text-slate-600">Belum ada jenjang pendidikan</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card View (md:hidden) -->
                    <div class="md:hidden space-y-4">
                        <div
                            v-for="level in schoolLevels"
                            :key="level.id"
                            class="bg-slate-50/80 rounded-2xl p-4 border border-slate-200/80 space-y-3.5 shadow-2xs"
                        >
                            <!-- Jenjang Header -->
                            <div class="flex items-start justify-between gap-3 pb-2.5 border-b border-slate-200/80">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-full inline-block mb-1 border border-emerald-200">
                                        Jenjang Pendidikan
                                    </span>
                                    <h5 class="text-base font-extrabold text-slate-900 leading-snug">
                                        {{ level.name }}
                                    </h5>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button
                                        v-if="canUpdateAcademic"
                                        type="button"
                                        @click="openLevelEditModal(level)"
                                        class="p-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-emerald-700 transition-colors shadow-2xs"
                                        title="Edit Jenjang"
                                    >
                                        <span class="material-symbols-outlined text-[16px]">edit</span>
                                    </button>
                                    <button
                                        v-if="canDeleteAcademic"
                                        type="button"
                                        @click="confirmLevelDelete(level)"
                                        class="p-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-rose-600 transition-colors shadow-2xs"
                                        title="Hapus Jenjang"
                                    >
                                        <span class="material-symbols-outlined text-[16px]">delete</span>
                                    </button>
                                    <button
                                        v-if="canCreateAcademic"
                                        type="button"
                                        @click="openUnitCreateModal(level)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-emerald-900 text-yellow-200 font-bold text-xs shadow-2xs"
                                        title="Tambah Unit Sekolah"
                                    >
                                        <span class="material-symbols-outlined text-[14px]">add</span>
                                        <span>Unit</span>
                                    </button>
                                </div>
                            </div>

                            <!-- List Units di Jenjang ini -->
                            <div v-if="level.units && level.units.length > 0" class="space-y-3">
                                <div
                                    v-for="unit in level.units"
                                    :key="unit.id"
                                    class="bg-white rounded-xl p-3.5 border border-slate-200/90 space-y-3 shadow-2xs"
                                >
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                                            <span class="text-xs font-bold text-slate-900">{{ unit.name }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <button
                                                v-if="canUpdateAcademic"
                                                type="button"
                                                @click="openUnitEditModal(unit, level)"
                                                class="p-1 text-slate-400 hover:text-emerald-700 transition-colors"
                                                title="Edit Unit"
                                            >
                                                <span class="material-symbols-outlined text-[15px]">edit</span>
                                            </button>
                                            <button
                                                v-if="canDeleteAcademic"
                                                type="button"
                                                @click="confirmUnitDelete(unit, level)"
                                                class="p-1 text-slate-400 hover:text-rose-600 transition-colors"
                                                title="Hapus Unit"
                                            >
                                                <span class="material-symbols-outlined text-[15px]">delete</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Programs -->
                                    <div class="space-y-1.5 pt-2 border-t border-slate-100">
                                        <span class="text-[11px] font-semibold text-slate-500 block">Program Studi:</span>
                                        <div class="flex flex-wrap gap-1.5">
                                            <div
                                                v-for="program in unit.programs"
                                                :key="program.id"
                                                class="inline-flex items-center gap-1 bg-slate-50 border border-slate-200 pl-2 pr-1 py-0.5 rounded-lg text-xs font-medium text-slate-800"
                                            >
                                                <span>{{ program.name }}</span>
                                                <button v-if="canUpdateAcademic" type="button" @click="openProgramEditModal(program, unit)" class="text-slate-400 hover:text-emerald-700 p-0.5">
                                                    <span class="material-symbols-outlined text-[13px]">edit</span>
                                                </button>
                                                <button v-if="canDeleteAcademic" type="button" @click="confirmProgramDelete(program, unit)" class="text-slate-400 hover:text-rose-600 p-0.5">
                                                    <span class="material-symbols-outlined text-[13px]">close</span>
                                                </button>
                                            </div>
                                            <button
                                                v-if="canCreateAcademic"
                                                type="button"
                                                @click="openProgramCreateModal(unit)"
                                                class="inline-flex items-center gap-0.5 text-[10.5px] font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200"
                                            >
                                                <span class="material-symbols-outlined text-[13px]">add</span> Program
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Classes -->
                                    <div class="space-y-1.5 pt-2 border-t border-slate-100">
                                        <span class="text-[11px] font-semibold text-slate-500 block">Master Kelas:</span>
                                        <div class="flex flex-wrap gap-1.5">
                                            <div
                                                v-for="cls in unit.classes"
                                                :key="cls.id"
                                                class="inline-flex items-center gap-1 bg-slate-50 border border-slate-200 pl-2 pr-1 py-0.5 rounded-lg text-xs font-medium text-slate-800"
                                            >
                                                <span>{{ cls.name }}</span>
                                                <button v-if="canUpdateAcademic" type="button" @click="openClassEditModal(cls, unit)" class="text-slate-400 hover:text-emerald-700 p-0.5">
                                                    <span class="material-symbols-outlined text-[13px]">edit</span>
                                                </button>
                                                <button v-if="canDeleteAcademic" type="button" @click="confirmClassDelete(cls)" class="text-slate-400 hover:text-rose-600 p-0.5">
                                                    <span class="material-symbols-outlined text-[13px]">close</span>
                                                </button>
                                            </div>
                                            <button
                                                v-if="canCreateAcademic"
                                                type="button"
                                                @click="openClassCreateModal(unit)"
                                                class="inline-flex items-center gap-0.5 text-[10.5px] font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200"
                                            >
                                                <span class="material-symbols-outlined text-[13px]">add</span> Kelas
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-xs text-slate-400 italic py-2">
                                Belum ada unit sekolah di jenjang ini.
                            </div>
                        </div>

                        <div v-if="schoolLevels.length === 0" class="py-12 text-center border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                            <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">apartment</span>
                            <p class="text-xs font-semibold text-slate-600">Belum ada jenjang pendidikan terdaftar</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: ORGANISASI -->
            <div v-show="activeTab === 'unit_bidang'" class="space-y-6">
                <OrgChartDiagram
                    :is-active="activeTab === 'unit_bidang'"
                    :positions="organizationPositions"
                    :departments="departments"
                    :organization-levels="organizationLevels"
                    :can-create="canCreateOrg"
                    :can-update="canUpdateOrg"
                    :can-delete="canDeleteOrg"
                    @add-position="handleAddPosition"
                    @edit-position="handleEditPosition"
                    @delete-position="confirmPositionDelete"
                    @add-department="openDepartmentCreateModal"
                    @edit-department="openDepartmentEditModal"
                    @delete-department="confirmDepartmentDelete"
                />
            </div>

            <!-- TAB 3: PENGGUNA (SUPERADMIN ONLY) -->
            <div v-show="activeTab === 'pengguna'" class="space-y-6">
                <div v-if="$page.props.auth?.user?.role === 'superadmin'" class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-7 shadow-xs">
                    <!-- Section Header -->
                    <div class="flex items-center justify-between gap-3 pb-3 mb-4 border-b border-slate-100 flex-wrap">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block shrink-0"></span>
                                <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">
                                    Daftar Akun Pengguna
                                </h4>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200/80">
                                    {{ filteredUsers.length }} dari {{ users.length }} Pengguna
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                Kelola kredensial login, hak peran, status keaktifan akun, dan reset kata sandi staf/guru
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="openGenerateUserModal"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs sm:text-sm shadow-2xs border border-slate-300 transition-all cursor-pointer active:scale-95"
                                title="Buat akun massal dari data pegawai yang belum punya akun"
                            >
                                <span class="material-symbols-outlined text-[16px] sm:text-[18px]">auto_mode</span>
                                <span>Generate Akun</span>
                            </button>
                            <button
                                type="button"
                                @click="openUserCreateModal"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                                title="Tambah Akun Pengguna Baru"
                            >
                                <span class="material-symbols-outlined text-[16px] sm:text-[18px]">person_add</span>
                                <span>Tambah Akun</span>
                            </button>
                        </div>
                    </div>

                    <!-- Search Input -->
                    <div class="mb-4">
                        <div class="relative max-w-md">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-[18px] text-slate-400">search</span>
                            <input
                                v-model="userSearchQuery"
                                type="text"
                                placeholder="Cari nama, NIP/username, peran..."
                                class="w-full pl-10 pr-8 py-2 rounded-xl border border-slate-300 bg-white text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 shadow-2xs transition-all"
                            />
                            <button
                                v-if="userSearchQuery"
                                type="button"
                                @click="userSearchQuery = ''"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1"
                            >
                                <span class="material-symbols-outlined text-[14px]">close</span>
                            </button>
                        </div>
                    </div>

                    <!-- Desktop Table View -->
                    <div class="hidden md:block overflow-hidden rounded-2xl border border-slate-200/90 shadow-2xs">
                        <table class="w-full text-left border-collapse text-xs sm:text-sm">
                            <thead class="bg-slate-50/90 text-slate-700 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200/90">
                                <tr>
                                    <th class="py-3.5 px-3 text-center w-12 text-slate-500 font-semibold">No</th>
                                    <th class="py-3.5 px-4">Nama Lengkap</th>
                                    <th class="py-3.5 px-4">NIP / Username</th>
                                    <th class="py-3.5 px-4 text-center w-36">Pass Default</th>
                                    <th class="py-3.5 px-4 text-center w-32">Role</th>
                                    <th class="py-3.5 px-4 text-center w-32">Status Akses</th>
                                    <th class="py-3.5 px-4 text-center w-36">Dibuat Pada</th>
                                    <th class="py-3.5 px-3 text-center w-32">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr
                                    v-for="(u, idx) in filteredUsers"
                                    :key="u.id"
                                    class="hover:bg-slate-50/70 transition-colors"
                                >
                                    <td class="py-3.5 px-3 text-center text-slate-400 font-mono text-xs">
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200/80 font-bold text-xs flex items-center justify-center shrink-0">
                                                {{ getInitials(u.name) }}
                                            </div>
                                            <div>
                                                <span class="font-bold text-slate-900 block leading-tight">{{ u.name }}</span>
                                                <span v-if="u.id === $page.props.auth?.user?.id" class="mt-0.5 inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-emerald-100 text-emerald-950 border border-emerald-300">
                                                    Anda
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-600 font-mono text-xs">
                                        {{ u.username }}
                                    </td>
                                    <!-- Pass Default -->
                                    <td class="py-3.5 px-4 text-center">
                                        <div v-if="u.pass_default" class="inline-flex items-center gap-1.5 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200/80">
                                            <span class="font-mono text-xs font-bold text-slate-800 tracking-wider">{{ u.pass_default }}</span>
                                            <button
                                                type="button"
                                                @click="copyPassDefault(u)"
                                                class="text-slate-400 hover:text-emerald-700 transition-colors p-0.5 rounded cursor-pointer"
                                                :title="copiedPassId === u.id ? 'Berhasil disalin!' : 'Salin password default'"
                                            >
                                                <span class="material-symbols-outlined text-[15px]" :class="copiedPassId === u.id ? 'text-emerald-600' : ''">
                                                    {{ copiedPassId === u.id ? 'check' : 'content_copy' }}
                                                </span>
                                            </button>
                                        </div>
                                        <span v-else class="text-slate-300 text-xs">—</span>
                                    </td>
                                    <!-- Role -->
                                    <td class="py-3.5 px-4 text-center">
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-[10.5px] font-bold border"
                                            :class="u.role === 'superadmin'
                                                ? 'bg-rose-50 text-rose-700 border-rose-200'
                                                : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
                                        >
                                            {{ u.role }}
                                        </span>
                                    </td>
                                    <!-- Toggle Aktif/Tidak -->
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="inline-flex items-center gap-2">
                                            <button
                                                type="button"
                                                role="switch"
                                                :aria-checked="u.is_active"
                                                :disabled="togglingUserId === u.id || u.id === $page.props.auth?.user?.id"
                                                @click="toggleUserStatus(u)"
                                                :class="[
                                                    'relative inline-flex h-5 w-10 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none',
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
                                    <td class="py-3.5 px-4 text-center text-slate-500 text-xs">
                                        {{ formatDate(u.created_at) }}
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <div class="inline-flex items-center justify-center gap-1">
                                            <button
                                                type="button"
                                                @click="confirmResetPassword(u)"
                                                :disabled="resettingUserId === u.id"
                                                class="p-1.5 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-amber-50 transition-colors cursor-pointer"
                                                title="Reset Password ke Default"
                                            >
                                                <span v-if="resettingUserId === u.id" class="material-symbols-outlined text-[18px] animate-spin text-amber-600">sync</span>
                                                <span v-else class="material-symbols-outlined text-[18px]">lock_reset</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="openUserEditModal(u)"
                                                class="p-1.5 rounded-lg text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 transition-colors cursor-pointer"
                                                title="Edit Akun"
                                            >
                                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="confirmUserDelete(u)"
                                                :disabled="deletingUserId === u.id || u.id === $page.props.auth?.user?.id"
                                                class="p-1.5 rounded-lg transition-colors cursor-pointer"
                                                :class="u.id === $page.props.auth?.user?.id
                                                    ? 'text-slate-300 cursor-not-allowed'
                                                    : 'text-slate-500 hover:text-rose-600 hover:bg-rose-50'"
                                                :title="u.id === $page.props.auth?.user?.id ? 'Tidak bisa menghapus akun sendiri' : 'Hapus Akun'"
                                            >
                                                <span v-if="deletingUserId === u.id" class="material-symbols-outlined text-[18px] animate-spin text-rose-600">sync</span>
                                                <span v-else class="material-symbols-outlined text-[18px]">delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredUsers.length === 0">
                                    <td colspan="8" class="py-12 text-center text-slate-400 italic">
                                        Data pengguna tidak ditemukan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card View (md:hidden) -->
                    <div class="md:hidden space-y-3">
                        <div
                            v-for="u in filteredUsers"
                            :key="u.id"
                            class="bg-slate-50/80 rounded-2xl p-4 border border-slate-200/80 space-y-3 hover:border-slate-300 transition-colors shadow-2xs"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-900 border border-emerald-300/80 font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ getInitials(u.name) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h5 class="text-sm font-bold text-slate-900 truncate leading-snug">
                                            {{ u.name }}
                                        </h5>
                                        <span v-if="u.id === $page.props.auth?.user?.id" class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-emerald-100 text-emerald-950 border border-emerald-300">
                                            Akun Anda
                                        </span>
                                    </div>
                                </div>

                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10.5px] font-bold border shrink-0"
                                    :class="u.role === 'superadmin'
                                        ? 'bg-rose-50 text-rose-700 border-rose-200'
                                        : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
                                >
                                    {{ u.role }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs pt-1 border-t border-slate-200/60">
                                <div>
                                    <span class="text-[11px] text-slate-400 block font-medium">NIP / Username:</span>
                                    <span class="font-mono font-semibold text-slate-800 text-[11.5px] break-all">
                                        {{ u.username }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-[11px] text-slate-400 block font-medium">Status Akses:</span>
                                    <div class="inline-flex items-center gap-2 mt-0.5">
                                        <button
                                            type="button"
                                            role="switch"
                                            :aria-checked="u.is_active"
                                            :disabled="togglingUserId === u.id || u.id === $page.props.auth?.user?.id"
                                            @click="toggleUserStatus(u)"
                                            :class="[
                                                'relative inline-flex h-4 w-8 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none',
                                                u.id === $page.props.auth?.user?.id ? 'cursor-not-allowed opacity-50' : '',
                                                u.is_active ? 'bg-emerald-500' : 'bg-slate-300'
                                            ]"
                                        >
                                            <span
                                                aria-hidden="true"
                                                :class="[
                                                    'pointer-events-none inline-block h-3 w-3 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                                    u.is_active ? 'translate-x-4' : 'translate-x-0'
                                                ]"
                                            ></span>
                                        </button>
                                        <span class="text-xs font-bold" :class="u.is_active ? 'text-emerald-700' : 'text-slate-400'">
                                            {{ u.is_active ? 'Aktif' : 'Tidak' }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <span class="text-[11px] text-slate-400 block font-medium">Pass Default:</span>
                                    <div v-if="u.pass_default" class="inline-flex items-center gap-1">
                                        <span class="font-mono font-bold text-slate-800 text-xs">{{ u.pass_default }}</span>
                                        <button type="button" @click="copyPassDefault(u)" class="text-slate-400 hover:text-emerald-700 p-0.5">
                                            <span class="material-symbols-outlined text-[13px]">
                                                {{ copiedPassId === u.id ? 'check' : 'content_copy' }}
                                            </span>
                                        </button>
                                    </div>
                                    <span v-else class="text-slate-400 text-xs">—</span>
                                </div>
                                <div>
                                    <span class="text-[11px] text-slate-400 block font-medium">Dibuat:</span>
                                    <span class="text-slate-600 text-xs">{{ formatDate(u.created_at) }}</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200/60">
                                <button
                                    type="button"
                                    @click="confirmResetPassword(u)"
                                    :disabled="resettingUserId === u.id"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 transition-colors cursor-pointer"
                                >
                                    <span class="material-symbols-outlined text-[15px]">lock_reset</span>
                                    <span>Reset Pass</span>
                                </button>
                                <button
                                    type="button"
                                    @click="openUserEditModal(u)"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-white text-slate-700 border border-slate-200 hover:bg-slate-100 transition-colors cursor-pointer"
                                >
                                    <span class="material-symbols-outlined text-[15px]">edit</span>
                                    <span>Edit</span>
                                </button>
                                <button
                                    type="button"
                                    @click="confirmUserDelete(u)"
                                    :disabled="deletingUserId === u.id || u.id === $page.props.auth?.user?.id"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 border border-rose-200 transition-colors cursor-pointer disabled:opacity-40"
                                >
                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                    <span>Hapus</span>
                                </button>
                            </div>
                        </div>

                        <div v-if="filteredUsers.length === 0" class="py-10 text-center text-slate-400 italic">
                            Data pengguna tidak ditemukan.
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: HAK AKSES (SUPERADMIN ONLY) -->
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

            <!-- TAB 5: APLIKASI (PWA) -->
            <div v-show="activeTab === 'aplikasi_pwa'" class="space-y-6">
                <!-- PWA Main Hero Banner -->
                <div class="bg-gradient-to-br from-emerald-900 via-emerald-800 to-teal-950 text-white rounded-3xl p-6 sm:p-8 relative overflow-hidden shadow-lg border border-emerald-700/50">
                    <div class="absolute -right-12 -top-12 w-64 h-64 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
                    <div class="absolute -left-12 -bottom-12 w-64 h-64 rounded-full bg-amber-400/10 blur-3xl pointer-events-none"></div>

                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="flex items-start sm:items-center gap-4">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-white p-1.5 shadow-md flex items-center justify-center shrink-0">
                                <img
                                    src="/images/icons/icon-192x192.png"
                                    alt="Logo PWA Yayasan Li Ulil Albab"
                                    class="w-full h-full object-contain rounded-xl"
                                />
                            </div>
                            <div>
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold tracking-wide uppercase bg-white/15 text-emerald-200 border border-white/20 mb-1.5">
                                    <span class="w-2 h-2 rounded-full" :class="isInstalled ? 'bg-emerald-400' : 'bg-amber-300 animate-pulse'"></span>
                                    <span>{{ isInstalled ? 'Aplikasi Web Aktif' : 'PWA Siap Dipasang' }}</span>
                                </div>
                                <h3 class="text-xl sm:text-2xl font-extrabold tracking-tight text-white leading-tight">
                                    Sistem Terpadu Yayasan Li Ulil Albab
                                </h3>
                                <p class="text-xs sm:text-sm text-emerald-100/90 mt-1 max-w-xl leading-relaxed">
                                    Nikmati akses sistem layaknya aplikasi native langsung dari layar utama perangkat Anda dengan performa optimal dan tampilan layar penuh.
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                            <button
                                type="button"
                                @click="installPwa"
                                class="px-5 py-3 rounded-2xl bg-white text-emerald-950 hover:bg-emerald-50 active:scale-[0.99] font-bold text-sm shadow-md flex items-center justify-center gap-2.5 transition-all cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[20px] text-emerald-700">
                                    {{ isInstalled ? 'check_circle' : 'install_mobile' }}
                                </span>
                                <span>{{ isInstalled ? 'Cek Status PWA' : 'Pasang Aplikasi Sekarang' }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Grid Keunggulan & Manfaat PWA -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center mb-3">
                                <span class="material-symbols-outlined text-[22px]">touch_app</span>
                            </div>
                            <h4 class="text-sm font-bold text-slate-900 leading-snug">Akses 1-Ketuk</h4>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Buka portal langsung dari ikon beranda HP atau desktop Anda tanpa perlu membuka browser dan mengetik alamat URL.
                            </p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-700 flex items-center justify-center mb-3">
                                <span class="material-symbols-outlined text-[22px]">fullscreen</span>
                            </div>
                            <h4 class="text-sm font-bold text-slate-900 leading-snug">Layar Penuh Mandiri</h4>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Bebas dari bilah alamat peramban (standalone mode) untuk ruang kerja yang jauh lebih lega, bersih, dan fokus.
                            </p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center mb-3">
                                <span class="material-symbols-outlined text-[22px]">bolt</span>
                            </div>
                            <h4 class="text-sm font-bold text-slate-900 leading-snug">Cepat &amp; Ringan</h4>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Ukuran sangat kecil tidak membebani memori HP, didukung Service Worker cache untuk pemuatan data yang responsif.
                            </p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center mb-3">
                                <span class="material-symbols-outlined text-[22px]">devices</span>
                            </div>
                            <h4 class="text-sm font-bold text-slate-900 leading-snug">Multiplatform</h4>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Dapat dipasang di Android, iPhone/iPad (iOS Safari), laptop/PC Windows, macOS, dan Linux tanpa toko aplikasi.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Petunjuk Langkah Pemasangan per Platform -->
                <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200/90 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700">
                            <span class="material-symbols-outlined text-[18px]">help</span>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Panduan Pemasangan di Berbagai Perangkat</h3>
                            <p class="text-xs text-slate-500">Langkah-langkah jika tombol pasang otomatis tidak memicu dialog browser</p>
                        </div>
                    </div>

                    <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-5">
                        <!-- Android -->
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                            <div class="flex items-center gap-2 font-bold text-xs text-slate-800">
                                <span class="material-symbols-outlined text-emerald-700 text-[18px]">phone_android</span>
                                <span>Android (Chrome / Edge)</span>
                            </div>
                            <ol class="text-[11.5px] text-slate-600 space-y-1.5 list-decimal list-inside leading-relaxed font-medium">
                                <li>Buka alamat web di browser Chrome / Edge.</li>
                                <li>Ketuk tombol titik tiga (<strong>⋮</strong>) di kanan atas browser.</li>
                                <li>Pilih menu <strong>"Instal aplikasi"</strong> atau <strong>"Tambahkan ke Layar Utama"</strong>.</li>
                                <li>Konfirmasi dengan mengetuk <strong>"Instal"</strong>.</li>
                            </ol>
                        </div>

                        <!-- iOS Safari -->
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                            <div class="flex items-center gap-2 font-bold text-xs text-slate-800">
                                <span class="material-symbols-outlined text-sky-700 text-[18px]">phone_iphone</span>
                                <span>iPhone &amp; iPad (Safari)</span>
                            </div>
                            <ol class="text-[11.5px] text-slate-600 space-y-1.5 list-decimal list-inside leading-relaxed font-medium">
                                <li>Buka tautan sistem melalui peramban <strong>Safari</strong>.</li>
                                <li>Ketuk ikon <strong>Bagikan (Share)</strong> <span class="material-symbols-outlined text-[13px] inline align-middle">ios_share</span> di bilah bawah.</li>
                                <li>Gulir ke bawah dan ketuk <strong>"Tambahkan ke Layar Utama"</strong>.</li>
                                <li>Ketuk tombol <strong>"Tambah"</strong> di pojok kanan atas.</li>
                            </ol>
                        </div>

                        <!-- Desktop PC / Mac -->
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                            <div class="flex items-center gap-2 font-bold text-xs text-slate-800">
                                <span class="material-symbols-outlined text-indigo-700 text-[18px]">laptop</span>
                                <span>PC / Laptop (Windows &amp; Mac)</span>
                            </div>
                            <ol class="text-[11.5px] text-slate-600 space-y-1.5 list-decimal list-inside leading-relaxed font-medium">
                                <li>Buka tautan web menggunakan Google Chrome atau Microsoft Edge.</li>
                                <li>Lihat ikon <strong>Instal</strong> (komputer kecil berpanah) di sisi kanan bilah alamat URL.</li>
                                <li>Atau klik menu titik tiga (⋮) &gt; <strong>Simpan dan bagikan</strong> &gt; <strong>Instal halaman ini sebagai aplikasi</strong>.</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FORM MODALS -->
        <AcademicYearFormModal
            :show="showFormModal"
            :editing="editingYear"
            @close="closeFormModal"
            @success="onFormSuccess"
        />

        <UserFormModal
            :show="showUserModal"
            :editing="editingUser"
            :available-roles="availableRoles"
            :school-roles="schoolRoles"
            :dept-roles="deptRoles"
            @close="closeUserModal"
            @success="onUserFormSuccess"
        />

        <GenerateUserModal
            :show="showGenerateUserModal"
            :employees="employees"
            @close="closeGenerateUserModal"
            @success="onGenerateUserSuccess"
        />

        <SchoolLevelFormModal
            :show="showLevelModal"
            :editing="editingLevel"
            @close="closeLevelModal"
            @success="onLevelSuccess"
        />

        <SchoolUnitFormModal
            :show="showUnitModal"
            :editing="editingUnit"
            :school-level="selectedLevelForUnit"
            :departments="departments"
            :school-levels="schoolLevels"
            @close="closeUnitModal"
            @success="onUnitSuccess"
        />

        <SchoolProgramFormModal
            :show="showProgramModal"
            :editing="editingProgram"
            :school-unit="selectedUnitForProgram"
            :existing-programs="allExistingPrograms"
            @close="closeProgramModal"
            @success="onProgramSuccess"
        />

        <SchoolClassFormModal
            :show="showClassModal"
            :editing="editingClass"
            :school-unit="selectedUnitForClass"
            @close="closeClassModal"
            @success="onClassSuccess"
        />

        <DepartmentFormModal
            :show="showDepartmentModal"
            :editing="editingDepartment"
            @close="closeDepartmentModal"
            @success="onDepartmentSuccess"
        />

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

        <!-- MODAL KONFIRMASI HAPUS KUSTOM (PENGGANTI POPUP BAWAAN BROWSER) -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="isDeleteConfirmModalOpen"
                    class="fixed inset-0 z-[9998] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
                    @click.self="closeConfirmModal"
                >
                    <div class="w-full max-w-sm sm:max-w-md bg-white rounded-3xl p-6 sm:p-7 shadow-2xl border border-slate-100 space-y-4 animate-in zoom-in-95 duration-200">
                        <div
                            class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto shadow-2xs"
                            :class="deleteConfirmData.hideConfirmButton ? 'bg-amber-50 text-amber-600 border border-amber-100' : 'bg-rose-50 text-rose-600 border border-rose-100'"
                        >
                            <span class="material-symbols-outlined text-3xl">
                                {{ deleteConfirmData.hideConfirmButton ? 'warning' : 'delete_forever' }}
                            </span>
                        </div>

                        <div class="text-center space-y-1.5">
                            <h3 class="text-base sm:text-lg font-bold text-slate-900 leading-tight">
                                {{ deleteConfirmData.title }}
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                                {{ deleteConfirmData.message }}
                            </p>
                        </div>

                        <div v-if="deleteConfirmData.itemName" class="p-3 bg-slate-50 border border-slate-200/80 rounded-2xl text-center">
                            <span class="text-xs font-semibold text-slate-800 break-words leading-relaxed font-mono">
                                {{ deleteConfirmData.itemName }}
                            </span>
                        </div>

                        <div v-if="!deleteConfirmData.hideConfirmButton" class="p-2.5 bg-amber-50 border border-amber-200 rounded-xl text-center">
                            <p class="text-[11px] font-medium text-amber-800 leading-tight flex items-center justify-center gap-1.5">
                                <span class="material-symbols-outlined text-[15px] shrink-0">info</span>
                                <span>Tindakan ini permanen dan tidak dapat dibatalkan.</span>
                            </p>
                        </div>

                        <div class="grid gap-3 pt-2" :class="deleteConfirmData.hideConfirmButton ? 'grid-cols-1' : 'grid-cols-2'">
                            <button
                                type="button"
                                @click="closeConfirmModal"
                                :disabled="deleteConfirmData.isDeleting"
                                class="w-full py-2.5 px-4 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 font-bold text-xs sm:text-sm transition-all cursor-pointer disabled:opacity-50"
                            >
                                {{ deleteConfirmData.hideConfirmButton ? 'Tutup' : 'Batal' }}
                            </button>
                            <button
                                v-if="!deleteConfirmData.hideConfirmButton"
                                type="button"
                                @click="handleConfirmModalAction"
                                :disabled="deleteConfirmData.isDeleting"
                                class="w-full py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs sm:text-sm shadow-md shadow-rose-600/20 transition-all cursor-pointer flex items-center justify-center gap-1.5 disabled:opacity-50"
                            >
                                <span v-if="deleteConfirmData.isDeleting" class="material-symbols-outlined text-[18px] animate-spin">
                                    progress_activity
                                </span>
                                <span v-else class="material-symbols-outlined text-[18px]">
                                    delete
                                </span>
                                <span>{{ deleteConfirmData.isDeleting ? 'Menghapus...' : deleteConfirmData.confirmText }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- FLOATING TOAST MELAYANG DENGAN PROGRESS BAR MUNDUR -->
        <Teleport to="body">
            <Transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="toast.show"
                    class="fixed top-5 right-4 sm:top-6 sm:right-6 z-[9999] max-w-sm w-[calc(100%-2rem)] bg-white rounded-2xl shadow-2xl border overflow-hidden"
                    :class="toast.type === 'success' ? 'border-emerald-200 shadow-emerald-950/20' : 'border-rose-200 shadow-rose-950/20'"
                    role="alert"
                >
                    <div class="p-4 flex items-start gap-3">
                        <div
                            :class="[
                                'w-9 h-9 rounded-xl flex items-center justify-center shrink-0 shadow-2xs',
                                toast.type === 'success' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                            ]"
                        >
                            <span class="material-symbols-outlined text-[20px]">
                                {{ toast.type === 'success' ? 'check_circle' : 'error' }}
                            </span>
                        </div>
                        <div class="flex-1 min-w-0 pr-1">
                            <h4
                                class="text-xs sm:text-sm font-bold leading-tight"
                                :class="toast.type === 'success' ? 'text-emerald-950' : 'text-rose-950'"
                            >
                                {{ toast.title }}
                            </h4>
                            <p class="text-xs text-slate-600 mt-1 leading-relaxed break-words font-medium">
                                {{ toast.message }}
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="closeToast"
                            class="text-slate-400 hover:text-slate-700 p-1 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer shrink-0"
                            title="Tutup pemberitahuan"
                        >
                            <span class="material-symbols-outlined text-[18px]">close</span>
                        </button>
                    </div>

                    <!-- Progress Bar Mundur -->
                    <div class="w-full bg-slate-100 h-1 overflow-hidden">
                        <div
                            class="h-full transition-all duration-75 ease-linear"
                            :class="toast.type === 'success' ? 'bg-emerald-600' : 'bg-rose-600'"
                            :style="{ width: `${toast.progress}%` }"
                        ></div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
