<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AcademicYearFormModal from '@/Components/Portal/AcademicYearFormModal.vue';
import UserFormModal from '@/Components/Portal/UserFormModal.vue';
import SchoolLevelFormModal from '@/Components/Portal/SchoolLevelFormModal.vue';
import SchoolUnitFormModal from '@/Components/Portal/SchoolUnitFormModal.vue';
import SchoolProgramFormModal from '@/Components/Portal/SchoolProgramFormModal.vue';

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
});

const page = usePage();

const activeTab = ref('pendidikan');
const tabs = computed(() => {
    const list = [
        { id: 'pendidikan', label: 'Pendidikan', icon: 'school' }
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

        <!-- Tab Content: Pendidikan -->
        <div v-show="activeTab === 'pendidikan'" class="space-y-8 animate-in fade-in duration-300">
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
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-surface-container-low/50">
                                <th class="text-left px-5 py-3 font-semibold text-on-surface-variant text-xs uppercase tracking-wider">Jenjang</th>
                                <th class="text-left px-5 py-3 font-semibold text-on-surface-variant text-xs uppercase tracking-wider">Daftar Unit</th>
                                <th class="text-center px-5 py-3 font-semibold text-on-surface-variant text-xs uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20">
                            <tr v-for="level in schoolLevels" :key="level.id" class="hover:bg-surface-container-low/40 transition-colors">
                                <td class="px-5 py-3.5 align-top">
                                    <span class="font-semibold text-on-surface">{{ level.name }}</span>
                                </td>
                                <td class="px-5 py-3.5 align-top">
                                    <div v-if="level.units && level.units.length > 0" class="flex flex-col gap-3">
                                        <div v-for="unit in level.units" :key="unit.id" class="flex flex-col gap-2 p-3 bg-surface-variant/20 border border-outline-variant/40 rounded-xl">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-bold text-on-surface">{{ unit.name }}</span>
                                                <div class="flex items-center gap-1">
                                                    <button type="button" @click="openUnitEditModal(unit, level)" class="text-on-surface-variant hover:text-primary" title="Edit Unit">
                                                        <span class="material-symbols-outlined text-[16px]">edit</span>
                                                    </button>
                                                    <button type="button" @click="confirmUnitDelete(unit)" class="text-on-surface-variant hover:text-error" title="Hapus Unit">
                                                        <span class="material-symbols-outlined text-[16px]">close</span>
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <!-- Programs under Unit -->
                                            <div class="pl-2 border-l-2 border-outline-variant/30 flex flex-wrap gap-1.5 items-center">
                                                <span class="text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider mr-1">Program:</span>
                                                <div v-if="unit.programs && unit.programs.length > 0" class="flex flex-wrap gap-1.5">
                                                    <div v-for="program in unit.programs" :key="program.id" class="inline-flex items-center gap-1 bg-surface-container-high border border-outline-variant/30 px-2 py-0.5 rounded-md">
                                                        <span class="text-xs text-on-surface">{{ program.name }}</span>
                                                        <button type="button" @click="openProgramEditModal(program, unit)" class="text-on-surface-variant hover:text-primary" title="Edit Program">
                                                            <span class="material-symbols-outlined text-[12px]">edit</span>
                                                        </button>
                                                        <button type="button" @click="confirmProgramDelete(program)" class="text-on-surface-variant hover:text-error" title="Hapus Program">
                                                            <span class="material-symbols-outlined text-[12px]">close</span>
                                                        </button>
                                                    </div>
                                                </div>
                                                <span v-else class="text-xs text-on-surface-variant italic">Belum ada</span>
                                                
                                                <button type="button" @click="openProgramCreateModal(unit)" class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary hover:bg-primary/10 px-1.5 py-0.5 rounded-md transition-colors">
                                                    <span class="material-symbols-outlined text-[12px]">add</span> Tambah Program
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-on-surface-variant italic text-sm">Belum ada unit ditambahkan</div>
                                    
                                    <button type="button" @click="openUnitCreateModal(level)" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-primary hover:bg-primary/10 px-2 py-1 rounded-lg transition-colors">
                                        <span class="material-symbols-outlined text-[14px]">add</span> Tambah Unit Baru
                                    </button>
                                </td>
                                <td class="px-5 py-3.5 text-center align-top">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" @click="openLevelEditModal(level)" class="p-2 rounded-lg text-on-surface-variant hover:text-primary hover:bg-primary/10 transition-colors cursor-pointer" title="Edit Jenjang">
                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                        </button>
                                        <button type="button" @click="confirmLevelDelete(level)" class="p-2 rounded-lg text-on-surface-variant hover:text-error hover:bg-error/10 transition-colors cursor-pointer" title="Hapus Jenjang">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="schoolLevels.length === 0">
                                <td colspan="3" class="px-5 py-8 text-center text-on-surface-variant text-sm">
                                    Belum ada jenjang pendidikan. Silakan tambah jenjang pertama Anda.
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
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-container-low/50">
                            <th class="text-left px-5 py-3 font-semibold text-on-surface-variant text-xs uppercase tracking-wider">Nama Lengkap</th>
                            <th class="text-left px-5 py-3 font-semibold text-on-surface-variant text-xs uppercase tracking-wider">NIP / Username</th>
                            <th class="text-left px-5 py-3 font-semibold text-on-surface-variant text-xs uppercase tracking-wider">Email</th>
                            <th class="text-center px-5 py-3 font-semibold text-on-surface-variant text-xs uppercase tracking-wider">Role</th>
                            <th class="text-center px-5 py-3 font-semibold text-on-surface-variant text-xs uppercase tracking-wider">Dibuat Pada</th>
                            <th class="text-center px-5 py-3 font-semibold text-on-surface-variant text-xs uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        <tr
                            v-for="u in users"
                            :key="u.id"
                            class="hover:bg-surface-container-low/40 transition-colors"
                        >
                            <td class="px-5 py-3.5">
                                <span class="font-semibold text-on-surface">{{ u.name }}</span>
                                <span v-if="u.id === $page.props.auth?.user?.id" class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-primary/10 text-primary border border-primary/20">
                                    Anda
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-on-surface-variant">
                                {{ u.username }}
                            </td>
                            <td class="px-5 py-3.5 text-on-surface-variant">
                                {{ u.email || '—' }}
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider"
                                      :class="u.role === 'superadmin' ? 'bg-error/10 text-error border border-error/20' : 'bg-secondary-container/40 text-on-secondary-container border border-secondary-container/60'">
                                    {{ u.role }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center text-on-surface-variant text-xs">
                                {{ u.created_at }}
                            </td>
                            <td class="px-5 py-3.5 text-center">
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
                    </tbody>
                </table>
            </div>
            
            <!-- Empty State Users -->
            <div v-if="users.length === 0" class="px-5 py-10 text-center text-on-surface-variant">
                Belum ada data pengguna lainnya.
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
</template>
