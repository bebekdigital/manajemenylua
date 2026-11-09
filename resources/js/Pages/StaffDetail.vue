<script setup>
import { ref, reactive, computed, watch, onMounted, onUnmounted, nextTick, provide } from 'vue';
import { Head, Link, usePage, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    getInitials,
    getAvatarStyle,
    getJenjangBadgeClass,
    getStatusStyle,
} from './Staff/staffHelpers';

// Sub-components for all 8 tabs
import TabIdentitas from './Staff/Tabs/TabIdentitas.vue';
import TabAlamat from './Staff/Tabs/TabAlamat.vue';
import TabKeluarga from './Staff/Tabs/TabKeluarga.vue';
import TabKepegawaian from './Staff/Tabs/TabKepegawaian.vue';
import TabPenugasan from './Staff/Tabs/TabPenugasan.vue';
import TabPendidikan from './Staff/Tabs/TabPendidikan.vue';
import TabPerizinan from './Staff/Tabs/TabPerizinan.vue';
import TabDokumen from './Staff/Tabs/TabDokumen.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    employee: {
        type: Object,
        required: true,
    },
    allEmployees: {
        type: Array,
        default: () => [],
    },
    selectedAcademicYear: {
        type: Object,
        default: null,
    },
    allAcademicYears: {
        type: Array,
        default: () => [],
    },
    availableUnits: {
        type: Array,
        default: () => [],
    },
    schoolUnits: {
        type: Array,
        default: () => [],
    },
    departments: {
        type: Array,
        default: () => [],
    },
    permissions: {
        type: Object,
        default: () => ({
            can_update: true,
            can_delete: true,
            can_view_kepegawaian: true,
            can_create_kepegawaian: true,
            can_update_kepegawaian: true,
            can_delete_kepegawaian: true,
            can_view_penugasan: true,
            can_create_penugasan: true,
            can_update_penugasan: true,
            can_delete_penugasan: true,
            can_view_documents: true,
            can_create_documents: true,
            can_update_documents: true,
            can_delete_documents: true,
            can_export_documents: true,
        }),
    },
});

const allTabs = [
    { id: 'identitas', label: 'Identitas', icon: 'badge' },
    { id: 'alamat', label: 'Alamat', icon: 'home' },
    { id: 'keluarga', label: 'Keluarga', icon: 'family_restroom' },
    { id: 'kepegawaian', label: 'Kepegawaian', icon: 'work' },
    { id: 'penugasan', label: 'Penugasan', icon: 'assignment_ind' },
    { id: 'pendidikan', label: 'Pendidikan', icon: 'school' },
    { id: 'perizinan', label: 'Perizinan', icon: 'assignment_turned_in' },
    { id: 'dokumen', label: 'Dokumen', icon: 'folder_shared' },
];

const getInitialTab = () => {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        if (tabParam && allTabs.some((t) => t.id === tabParam)) {
            return tabParam;
        }
    }
    return 'identitas';
};

const page = usePage();
const activeTab = ref(getInitialTab());
const isEditing = ref(false);

const canEdit = computed(() => props.permissions?.can_update ?? true);
const canViewKepegawaian = computed(() => props.permissions?.can_view_kepegawaian ?? true);
const canCreateKepegawaian = computed(() => props.permissions?.can_create_kepegawaian ?? true);
const canUpdateKepegawaian = computed(() => props.permissions?.can_update_kepegawaian ?? true);
const canDeleteKepegawaian = computed(() => props.permissions?.can_delete_kepegawaian ?? true);
const canViewPenugasan = computed(() => props.permissions?.can_view_penugasan ?? true);
const canCreatePenugasan = computed(() => props.permissions?.can_create_penugasan ?? true);
const canUpdatePenugasan = computed(() => props.permissions?.can_update_penugasan ?? true);
const canDeletePenugasan = computed(() => props.permissions?.can_delete_penugasan ?? true);
const canViewDocuments = computed(() => props.permissions?.can_view_documents ?? true);
const canCreateDocuments = computed(() => props.permissions?.can_create_documents ?? true);
const canUpdateDocuments = computed(() => props.permissions?.can_update_documents ?? true);
const canDeleteDocuments = computed(() => props.permissions?.can_delete_documents ?? true);
const canExportDocuments = computed(() => props.permissions?.can_export_documents ?? true);
const canApprovePermit = computed(() => props.permissions?.can_approve_permit ?? false);

// Global state check untuk mencegah navigasi saat ada section kelola yang aktif di tab anak
const managingState = reactive({
    kepegawaian: false,
    penugasan: false,
    pendidikan: false,
    perizinan: false,
});

const isAnyManaging = computed(() => {
    return Object.values(managingState).some(Boolean);
});

function setManaging(tab, val) {
    managingState[tab] = !!val;
}

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

// Pantau flash message dari server / Inertia
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

// State & Fungsi Modal Konfirmasi Hapus Kustom (Pengganti dialog bawaan browser)
const isDeleteConfirmModalOpen = ref(false);
const deleteConfirmData = reactive({
    title: 'Konfirmasi Hapus Data',
    message: '',
    itemLabel: '',
    isDeleting: false,
    onConfirm: () => {},
});

function requestDeleteConfirm({ title, message, itemLabel, onConfirm }) {
    deleteConfirmData.title = title || 'Konfirmasi Hapus Data';
    deleteConfirmData.message = message || 'Apakah Anda yakin ingin menghapus catatan data ini?';
    deleteConfirmData.itemLabel = itemLabel || '';
    deleteConfirmData.isDeleting = false;
    deleteConfirmData.onConfirm = onConfirm;
    isDeleteConfirmModalOpen.value = true;
}

function handleConfirmDelete() {
    if (typeof deleteConfirmData.onConfirm === 'function') {
        deleteConfirmData.isDeleting = true;
        deleteConfirmData.onConfirm();
    }
}

function closeDeleteConfirmModal() {
    if (deleteConfirmData.isDeleting) return;
    isDeleteConfirmModalOpen.value = false;
}

// Preview Modal State & Methods
const showPreviewModal = ref(false);
const previewDoc = ref(null);

function openPreview(doc) {
    if (!doc) return;
    previewDoc.value = doc;
    showPreviewModal.value = true;
}

function closePreviewModal() {
    showPreviewModal.value = false;
    previewDoc.value = null;
}

// Provide dependencies ke semua sub-komponen
provide('openPreview', openPreview);
provide('requestDeleteConfirm', requestDeleteConfirm);
provide('showToast', showToast);
provide('closeDeleteConfirmModal', closeDeleteConfirmModal);

watch(() => page.url, (newUrl) => {
    try {
        const parsed = new URL(newUrl, window.location.origin);
        const tabParam = parsed.searchParams.get('tab');
        if (tabParam && allTabs.some((t) => t.id === tabParam)) {
            activeTab.value = tabParam;
        }
    } catch {
        // ignore
    }
}, { immediate: true });

const tabs = computed(() => {
    return allTabs.filter((tab) => {
        if (tab.id === 'kepegawaian' && !canViewKepegawaian.value) return false;
        if (tab.id === 'penugasan' && !canViewPenugasan.value) return false;
        if (tab.id === 'dokumen' && !canViewDocuments.value) return false;
        return true;
    });
});

watch(canViewKepegawaian, (canView) => {
    if (!canView && activeTab.value === 'kepegawaian') {
        activeTab.value = 'identitas';
    }
});

watch(canViewPenugasan, (canView) => {
    if (!canView && activeTab.value === 'penugasan') {
        activeTab.value = 'identitas';
    }
});

watch(canViewDocuments, (canView) => {
    if (!canView && activeTab.value === 'dokumen') {
        activeTab.value = 'identitas';
    }
});

// Dropdown Switcher Pemilih Pegawai Lain di Header
const isEmployeeDropdownOpen = ref(false);
const employeeSearchQuery = ref('');
const employeeDropdownRef = ref(null);
const employeeSearchInputRef = ref(null);
const mobileEmployeeSearchInputRef = ref(null);

const filteredEmployees = computed(() => {
    const list = props.allEmployees || [];
    const q = employeeSearchQuery.value.trim().toLowerCase();
    if (!q) return list;
    return list.filter((emp) => {
        const nameMatch = emp.nama?.toLowerCase().includes(q);
        const nipyMatch = emp.nipy?.toLowerCase().includes(q);
        const jabatanMatch = emp.jabatan?.toLowerCase().includes(q);
        const unitMatch = emp.unit_kerja?.toLowerCase().includes(q);
        return nameMatch || nipyMatch || jabatanMatch || unitMatch;
    });
});

function toggleEmployeeDropdown(e) {
    e.stopPropagation();
    if (isEditing.value || isAnyManaging.value) {
        alert('Anda sedang dalam mode edit/kelola. Selesaikan terlebih dahulu sebelum beralih pegawai.');
        return;
    }
    isEmployeeDropdownOpen.value = !isEmployeeDropdownOpen.value;
    if (isEmployeeDropdownOpen.value) {
        employeeSearchQuery.value = '';
        setTimeout(() => {
            if (window.innerWidth < 640) {
                mobileEmployeeSearchInputRef.value?.focus();
            } else {
                employeeSearchInputRef.value?.focus();
            }
        }, 60);
    }
}

function closeEmployeeDropdown() {
    isEmployeeDropdownOpen.value = false;
    employeeSearchQuery.value = '';
}

function handleOutsideClick(e) {
    if (e.target?.closest?.('.mobile-employee-sheet')) return;
    if (employeeDropdownRef.value && !employeeDropdownRef.value.contains(e.target)) {
        closeEmployeeDropdown();
    }
}

function selectEmployee(empId) {
    if (String(empId) === String(props.employee.id)) {
        closeEmployeeDropdown();
        return;
    }

    if (isEditing.value || isAnyManaging.value) {
        alert('Anda sedang dalam mode edit/kelola. Selesaikan terlebih dahulu sebelum beralih ke pegawai lain.');
        return;
    }

    closeEmployeeDropdown();
    router.visit(`/staff/${empId}`);
}

// Form state untuk Identitas, Alamat, Keluarga & Kepegawaian (Wadah 1)
const form = useForm({
    nama: '',
    gelar_depan: '',
    gelar_belakang: '',
    nipy: '',
    nik: '',
    jk: '',
    tempat_lahir: '',
    tanggal_lahir: '',
    no_wa: '',
    email: '',
    skill: '',

    // Alamat Domisili
    jalan_domisili: '',
    rt_rw_domisili: '',
    dusun_domisili: '',
    desa_domisili: '',
    kecamatan_domisili: '',
    kab_domisili: '',
    provinsi_domisili: '',

    // Alamat Tinggal
    jalan_tinggal: '',
    rt_rw_tinggal: '',
    dusun_tinggal: '',
    desa_tinggal: '',
    kecamatan_tinggal: '',
    kabupaten_tinggal: '',
    provinsi_tinggal: '',
    status_rumah: '',
    kepemilikan_bpjs: '',
    penanggung_bpjs: '',

    // Profil & Keluarga
    status_pernikahan: '',
    nama_suami_istri: '',
    ttl_suami_istri: '',
    pekerjaan_suami_istri: '',
    tanggal_menikah: '',
    jumlah_anak: '',
    nama_ibu: '',
    nama_ayah: '',
    alamat_orangtua: '',
    kontak_darurat: '',
    hubungan_kontak_darurat: '',

    // Kepegawaian
    tmt_pengangkatan: '',
    tst_pengangkatan: '',
    academic_year_id: null,
    unit_kerja: '',
    jabatan: '',
    jenis_kepegawaian: '',
    tmt_jabatan: '',
    tst_jabatan: '',
    status_keaktifan: 'Aktif',
    keterangan_tidak_aktif: '',
    jenjang_kepegawaian: '',
    tmt_kenaikan_jenjang: '',
    tst_kenaikan_jenjang: '',
    tst_jenjang: '',
    keaktifan_dapodik: '',
    unit_keaktifan_dapodik: '',

    riwayat_kepegawaian: [],
    riwayat_jabatan: [],
    riwayat_pendidikan: [],
    riwayat_pelatihan: [],
    riwayat_perizinan: [],
});

function initFormFromProps() {
    form.nama = props.employee.nama || '';
    form.gelar_depan = props.employee.gelar_depan || '';
    form.gelar_belakang = props.employee.gelar_belakang || '';
    form.nipy = props.employee.nipy || '';
    form.nik = props.employee.nik || '';
    form.jk = props.employee.jk || '';
    form.tempat_lahir = props.employee.tempat_lahir || '';
    form.tanggal_lahir = props.employee.tanggal_lahir || '';
    form.no_wa = props.employee.no_wa || '';
    form.email = props.employee.email || '';
    form.skill = props.employee.skill || '';

    form.jalan_domisili = props.employee.jalan_domisili || '';
    form.rt_rw_domisili = props.employee.rt_rw_domisili || '';
    form.dusun_domisili = props.employee.dusun_domisili || '';
    form.desa_domisili = props.employee.desa_domisili || '';
    form.kecamatan_domisili = props.employee.kecamatan_domisili || '';
    form.kab_domisili = props.employee.kab_domisili || '';
    form.provinsi_domisili = props.employee.provinsi_domisili || '';

    form.jalan_tinggal = props.employee.jalan_tinggal || '';
    form.rt_rw_tinggal = props.employee.rt_rw_tinggal || '';
    form.dusun_tinggal = props.employee.dusun_tinggal || '';
    form.desa_tinggal = props.employee.desa_tinggal || '';
    form.kecamatan_tinggal = props.employee.kecamatan_tinggal || '';
    form.kabupaten_tinggal = props.employee.kabupaten_tinggal || '';
    form.provinsi_tinggal = props.employee.provinsi_tinggal || '';
    form.status_rumah = props.employee.status_rumah || '';
    form.kepemilikan_bpjs = props.employee.kepemilikan_bpjs || '';
    form.penanggung_bpjs = props.employee.penanggung_bpjs || '';

    form.status_pernikahan = props.employee.status_pernikahan || '';
    form.nama_suami_istri = props.employee.nama_suami_istri || '';
    form.ttl_suami_istri = props.employee.ttl_suami_istri || '';
    form.pekerjaan_suami_istri = props.employee.pekerjaan_suami_istri || '';
    form.tanggal_menikah = props.employee.tanggal_menikah || '';
    form.jumlah_anak = props.employee.jumlah_anak !== null && props.employee.jumlah_anak !== undefined ? props.employee.jumlah_anak : '';
    form.nama_ibu = props.employee.nama_ibu || '';
    form.nama_ayah = props.employee.nama_ayah || '';
    form.alamat_orangtua = props.employee.alamat_orangtua || '';
    form.kontak_darurat = props.employee.kontak_darurat || '';
    form.hubungan_kontak_darurat = props.employee.hubungan_kontak_darurat || '';

    form.tmt_pengangkatan = props.employee.tmt_pengangkatan || '';
    form.tst_pengangkatan = props.employee.tst_pengangkatan || '';
    form.academic_year_id = props.selectedAcademicYear?.id || null;
    form.unit_kerja = props.employee.unit_kerja || '';
    form.jabatan = props.employee.jabatan || '';
    form.jenis_kepegawaian = props.employee.jenis_kepegawaian || '';
    form.tmt_jabatan = props.employee.tmt_jabatan || '';
    form.tst_jabatan = props.employee.tst_jabatan || '';
    form.status_keaktifan = props.employee.status_keaktifan || 'Aktif';
    form.keterangan_tidak_aktif = props.employee.keterangan_tidak_aktif || '';
    form.jenjang_kepegawaian = props.employee.jenjang_kepegawaian || '';
    form.tmt_kenaikan_jenjang = props.employee.tmt_kenaikan_jenjang || '';
    form.tst_kenaikan_jenjang = props.employee.tst_kenaikan_jenjang || props.employee.tst_jenjang || '';
    form.tst_jenjang = props.employee.tst_kenaikan_jenjang || props.employee.tst_jenjang || '';
    form.keaktifan_dapodik = props.employee.keaktifan_dapodik || '';
    form.unit_keaktifan_dapodik = props.employee.unit_keaktifan_dapodik || '';

    form.riwayat_kepegawaian = props.employee.riwayat_kepegawaian || [];
    form.riwayat_jabatan = props.employee.riwayat_jabatan || [];
    form.riwayat_pendidikan = props.employee.riwayat_pendidikan || [];
    form.riwayat_pelatihan = props.employee.riwayat_pelatihan || [];
    form.riwayat_perizinan = props.employee.perizinan || [];
}

watch(() => props.employee, () => {
    initFormFromProps();
}, { deep: true });

onMounted(() => {
    initFormFromProps();
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('edit') === 'true' && canEdit.value) {
        isEditing.value = true;
    }
    window.addEventListener('click', handleOutsideClick);
    window.addEventListener('resize', checkScroll);
    nextTick(() => checkScroll());
});

onUnmounted(() => {
    window.removeEventListener('click', handleOutsideClick);
    window.removeEventListener('resize', checkScroll);
});

function startEdit() {
    if (!canEdit.value) return;
    if (isAnyManaging.value) {
        alert('Anda sedang dalam mode kelola riwayat. Selesaikan (klik Selesai Kelola) terlebih dahulu sebelum mengedit Informasi Umum.');
        return;
    }
    initFormFromProps();
    form.clearErrors();
    isEditing.value = true;
}

function cancelEdit() {
    initFormFromProps();
    form.clearErrors();
    isEditing.value = false;
}

function saveChanges() {
    if (!canUpdateKepegawaian.value) {
        // Pulihkan nilai asli kepegawaian agar tidak terubah saat user tidak memiliki hak akses edit kepegawaian
        form.nipy = props.employee.nipy || '';
        form.tmt_pengangkatan = props.employee.tmt_pengangkatan || '';
        form.tst_pengangkatan = props.employee.tst_pengangkatan || '';
        form.status_keaktifan = props.employee.status_keaktifan || 'Aktif';
        form.keterangan_tidak_aktif = props.employee.keterangan_tidak_aktif || '';
        form.keaktifan_dapodik = props.employee.keaktifan_dapodik || '';
        form.unit_keaktifan_dapodik = props.employee.unit_keaktifan_dapodik || '';
        form.jenjang_kepegawaian = props.employee.jenjang_kepegawaian || '';
        form.tmt_kenaikan_jenjang = props.employee.tmt_kenaikan_jenjang || '';
        form.tst_kenaikan_jenjang = props.employee.tst_kenaikan_jenjang || props.employee.tst_jenjang || '';
        form.tst_jenjang = props.employee.tst_kenaikan_jenjang || props.employee.tst_jenjang || '';
    }

    form.transform((data) => ({
        ...data,
        _tab: activeTab.value,
    })).put(`/staff/${props.employee.id}`, {
        preserveScroll: true,
        only: ['employee', 'flash'],
        onSuccess: () => {
            isEditing.value = false;
            initFormFromProps();
            showToast('success', page.props.flash?.success || `Data pegawai '${props.employee.nama}' berhasil diperbarui.`);
        },
        onError: () => {
            showToast('error', 'Mohon lengkapi dan periksa kembali data yang belum valid.', 'Gagal Menyimpan');
        },
    });
}

// Navigation Tabs Scroll & Selection
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
    if (isEditing.value) {
        if (id === activeTab.value) return;
        alert('Anda sedang dalam mode edit. Selesaikan (Simpan) atau batalkan (Batal) perubahan terlebih dahulu sebelum berpindah ke tab lain.');
        return;
    }
    if (isAnyManaging.value) {
        if (id === activeTab.value) return;
        alert('Anda sedang dalam mode kelola riwayat. Selesaikan (klik Selesai Kelola) terlebih dahulu sebelum berpindah ke tab lain.');
        return;
    }
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

function handleBackClick(e) {
    if (isEditing.value) {
        if (!confirm('Perubahan yang sedang Anda edit belum disimpan. Yakin ingin keluar dari halaman edit?')) {
            e.preventDefault();
        }
    } else if (isAnyManaging.value) {
        if (!confirm('Anda sedang dalam mode kelola riwayat. Yakin ingin keluar dari halaman ini?')) {
            e.preventDefault();
        }
    }
}
</script>

<template>
    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop pb-24 md:pb-margin-desktop bg-white min-h-screen">
        <Head :title="`${isEditing ? 'Edit' : 'Detail'} Pegawai - ${employee.nama}`" />

        <div class="max-w-[1440px] mx-auto">
            <!-- Back Button -->
            <div class="mb-5 flex items-center justify-between gap-4">
                <Link
                    href="/staff"
                    @click="handleBackClick"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs sm:text-sm font-semibold shadow-2xs transition-all active:scale-95"
                >
                    <span class="material-symbols-outlined text-[18px] text-slate-500">arrow_back</span>
                    <span>Kembali ke Data Pegawai</span>
                </Link>
            </div>

            <!-- Header Card Identitas Profil Pegawai -->
            <div
                :class="[
                    'relative bg-gradient-to-br from-white via-emerald-50/25 to-slate-50/70 rounded-3xl border border-slate-200/90 p-5 sm:p-6 shadow-xs mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4',
                    isEmployeeDropdownOpen ? 'z-50' : 'z-20'
                ]"
            >
                <!-- Efek Cahaya Lembut Statis di Latar Belakang -->
                <div class="absolute inset-0 rounded-3xl overflow-hidden pointer-events-none">
                    <div class="absolute -top-20 -right-20 w-72 h-72 rounded-full bg-gradient-to-br from-emerald-200/40 via-teal-100/25 to-transparent blur-3xl"></div>
                    <div class="absolute -bottom-20 -left-16 w-64 h-64 rounded-full bg-gradient-to-tr from-sky-200/35 via-emerald-100/20 to-transparent blur-3xl"></div>
                    <div class="absolute top-1/2 left-1/3 -translate-y-1/2 w-48 h-48 rounded-full bg-amber-100/30 blur-2xl"></div>
                </div>

                <div
                    :class="[
                        'relative flex items-center gap-4 sm:gap-5 min-w-0 flex-1',
                        isEmployeeDropdownOpen ? 'z-50' : 'z-10'
                    ]"
                >
                    <img
                        v-if="employee.foto"
                        :src="employee.foto"
                        :alt="employee.nama"
                        class="w-16 h-16 sm:w-20 sm:h-20 max-w-[64px] max-h-[64px] sm:max-w-[80px] sm:max-h-[80px] aspect-square rounded-2xl object-cover border border-slate-200 shadow-2xs shrink-0"
                    />
                    <div
                        v-else
                        :class="[
                            'w-16 h-16 sm:w-20 sm:h-20 max-w-[64px] max-h-[64px] sm:max-w-[80px] sm:max-h-[80px] aspect-square rounded-2xl flex items-center justify-center font-bold text-xl sm:text-2xl border shadow-2xs shrink-0',
                            getAvatarStyle(employee)
                        ]"
                    >
                        <span>{{ getInitials(employee.nama) }}</span>
                    </div>

                    <div class="space-y-1.5 min-w-0 flex-1">
                        <!-- Dropdown Pemilih Pegawai Lain -->
                        <div class="relative" ref="employeeDropdownRef">
                            <button
                                v-if="allEmployees.length > 1"
                                type="button"
                                @click="toggleEmployeeDropdown"
                                class="inline-flex items-center gap-1.5 text-left text-xl sm:text-2xl font-extrabold text-slate-900 hover:text-emerald-950 transition-colors cursor-pointer group rounded-xl -ml-2 px-2 py-0.5 hover:bg-slate-100/80 active:scale-[0.99] max-w-full"
                                title="Klik untuk beralih memilih pegawai lain"
                            >
                                <span class="break-words leading-tight">{{ employee.nama }}</span>
                                <span
                                    class="material-symbols-outlined text-[24px] text-slate-400 group-hover:text-emerald-950 transition-transform duration-200 shrink-0"
                                    :class="{ 'rotate-180 text-emerald-950': isEmployeeDropdownOpen }"
                                >
                                    arrow_drop_down
                                </span>
                            </button>
                            <h1 v-else class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight leading-tight break-words">
                                {{ employee.nama }}
                            </h1>

                            <!-- Floating Searchable Dropdown Menu (Desktop) -->
                            <div
                                v-if="isEmployeeDropdownOpen"
                                class="hidden sm:block absolute left-0 top-full mt-2 w-80 sm:w-96 max-w-[92vw] bg-white rounded-2xl shadow-2xl border border-slate-200/90 z-50 p-2 overflow-hidden animate-in fade-in slide-in-from-top-2 duration-150"
                            >
                                <div class="px-2 pt-1 pb-2 border-b border-slate-100 flex items-center justify-between text-xs text-slate-500">
                                    <span class="font-bold text-slate-800 text-[11px] uppercase tracking-wider">Pilih Pegawai Lain</span>
                                    <span class="text-[10px] text-slate-400">{{ filteredEmployees.length }} dari {{ allEmployees.length }} pegawai</span>
                                </div>

                                <!-- Search Input -->
                                <div class="relative p-1 my-1">
                                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[16px] text-slate-400">search</span>
                                    <input
                                        ref="employeeSearchInputRef"
                                        v-model="employeeSearchQuery"
                                        type="text"
                                        placeholder="Cari nama atau NIPY pegawai..."
                                        class="w-full pl-8 pr-7 py-1.5 rounded-xl border border-slate-300 bg-white text-xs text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                        @keydown.esc="closeEmployeeDropdown"
                                    />
                                    <button
                                        v-if="employeeSearchQuery"
                                        type="button"
                                        @click="employeeSearchQuery = ''"
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700"
                                    >
                                        <span class="material-symbols-outlined text-[14px]">close</span>
                                    </button>
                                </div>

                                <!-- Scrollable List -->
                                <div class="max-h-64 overflow-y-auto divide-y divide-slate-100">
                                    <div
                                        v-for="emp in filteredEmployees"
                                        :key="emp.id"
                                        @click="selectEmployee(emp.id)"
                                        class="p-2.5 rounded-xl hover:bg-slate-50 cursor-pointer transition-colors flex items-center justify-between gap-2.5"
                                        :class="{ 'bg-emerald-50 hover:bg-emerald-100/70': String(emp.id) === String(employee.id) }"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold text-slate-900 truncate" :class="{ 'text-emerald-950 font-extrabold': String(emp.id) === String(employee.id) }">
                                                {{ emp.nama }}
                                            </p>
                                            <div class="flex items-center gap-1.5 text-[10px] text-slate-500 truncate mt-0.5">
                                                <span v-if="emp.nipy && canViewKepegawaian" class="font-mono">NIPY: {{ emp.nipy }}</span>
                                                <span v-if="emp.nipy && canViewKepegawaian && (emp.jabatan || emp.unit_kerja)">·</span>
                                                <span v-if="emp.jabatan || emp.unit_kerja" class="truncate">{{ emp.jabatan || emp.unit_kerja }}</span>
                                            </div>
                                        </div>
                                        <span v-if="String(emp.id) === String(employee.id)" class="material-symbols-outlined text-emerald-700 text-[18px] shrink-0">check</span>
                                    </div>

                                    <div v-if="filteredEmployees.length === 0" class="py-6 text-center text-xs text-slate-400 italic">
                                        Pegawai tidak ditemukan.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile Bottom Sheet Pemilih Pegawai -->
                        <Teleport to="body">
                            <div
                                v-if="isEmployeeDropdownOpen"
                                class="mobile-employee-sheet fixed inset-0 z-[100] sm:hidden flex items-end justify-center bg-slate-900/50 backdrop-blur-xs animate-in fade-in duration-200"
                                @click.self="closeEmployeeDropdown"
                            >
                                <div class="w-full bg-white rounded-t-3xl max-h-[85vh] flex flex-col shadow-2xl border-t border-slate-200 animate-in slide-in-from-bottom duration-200 pb-[max(1.5rem,env(safe-area-inset-bottom))]">
                                    <div class="w-12 h-1.5 bg-slate-200 rounded-full mx-auto my-3 shrink-0"></div>

                                    <div class="px-5 pb-3 border-b border-slate-100 flex items-center justify-between shrink-0">
                                        <div>
                                            <h3 class="text-base font-extrabold text-slate-900">Pilih Pegawai Lain</h3>
                                            <p class="text-xs text-slate-400 mt-0.5">{{ filteredEmployees.length }} dari {{ allEmployees.length }} pegawai</p>
                                        </div>
                                        <button
                                            type="button"
                                            @click="closeEmployeeDropdown"
                                            class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center cursor-pointer transition-colors"
                                        >
                                            <span class="material-symbols-outlined text-[20px]">close</span>
                                        </button>
                                    </div>

                                    <div class="p-4 border-b border-slate-100 shrink-0">
                                        <div class="relative">
                                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-[18px] text-slate-400">search</span>
                                            <input
                                                ref="mobileEmployeeSearchInputRef"
                                                v-model="employeeSearchQuery"
                                                type="text"
                                                placeholder="Cari nama atau NIPY pegawai..."
                                                class="w-full pl-10 pr-9 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition-colors"
                                            />
                                            <button
                                                v-if="employeeSearchQuery"
                                                type="button"
                                                @click="employeeSearchQuery = ''"
                                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1"
                                            >
                                                <span class="material-symbols-outlined text-[16px]">close</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex-1 overflow-y-auto p-3 space-y-1 divide-y divide-slate-50">
                                        <div
                                            v-for="emp in filteredEmployees"
                                            :key="emp.id"
                                            @click="selectEmployee(emp.id)"
                                            class="p-3 rounded-2xl transition-all cursor-pointer flex items-center justify-between gap-3 active:scale-[0.99]"
                                            :class="String(emp.id) === String(employee.id) ? 'bg-emerald-50 text-emerald-950 font-bold border border-emerald-200/60' : 'hover:bg-slate-50 text-slate-800'"
                                        >
                                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                                <img
                                                    v-if="emp.foto"
                                                    :src="emp.foto"
                                                    :alt="emp.nama"
                                                    class="w-10 h-10 rounded-xl object-cover border border-slate-200 shrink-0"
                                                />
                                                <div
                                                    v-else
                                                    class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 border"
                                                    :class="String(emp.id) === String(employee.id) ? 'bg-emerald-100 text-emerald-900 border-emerald-300' : 'bg-slate-100 text-slate-700 border-slate-200'"
                                                >
                                                    {{ getInitials(emp.nama) }}
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-sm font-bold truncate" :class="{ 'text-emerald-950': String(emp.id) === String(employee.id) }">
                                                        {{ emp.nama }}
                                                    </p>
                                                    <div class="flex items-center gap-1.5 text-xs text-slate-500 truncate mt-0.5">
                                                        <span v-if="emp.nipy && canViewKepegawaian" class="font-mono">NIPY: {{ emp.nipy }}</span>
                                                        <span v-if="emp.nipy && canViewKepegawaian && (emp.jabatan || emp.unit_kerja)">·</span>
                                                        <span v-if="emp.jabatan || emp.unit_kerja" class="truncate">{{ emp.jabatan || emp.unit_kerja }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <span v-if="String(emp.id) === String(employee.id)" class="material-symbols-outlined text-emerald-700 text-[22px] shrink-0">
                                                check_circle
                                            </span>
                                        </div>

                                        <div v-if="filteredEmployees.length === 0" class="py-12 text-center text-sm text-slate-400 italic">
                                            Pegawai tidak ditemukan.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Teleport>

                        <!-- Info Jabatan & Unit Kerja -->
                        <div v-if="employee.jabatan || employee.unit_kerja" class="flex items-center gap-1.5 text-xs sm:text-sm text-slate-500 font-medium flex-wrap">
                            <span v-if="employee.jabatan">{{ employee.jabatan }}</span>
                            <span v-if="employee.jabatan && employee.unit_kerja">•</span>
                            <span v-if="employee.unit_kerja">{{ employee.unit_kerja }}</span>
                        </div>

                        <!-- Badges Identitas -->
                        <div class="flex flex-wrap items-center gap-2 pt-0.5">
                            <!-- Badge Status Keaktifan -->
                            <span
                                v-if="employee.status_keaktifan"
                                :class="[
                                    'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs shadow-2xs',
                                    getStatusStyle(employee.status_keaktifan).wrapper
                                ]"
                            >
                                <span
                                    :class="[
                                        'w-2 h-2 rounded-full shrink-0',
                                        getStatusStyle(employee.status_keaktifan).dot
                                    ]"
                                ></span>
                                <span>{{ employee.status_keaktifan }}</span>
                            </span>

                            <!-- Badge Jenjang -->
                            <span
                                v-if="employee.jenjang_kepegawaian"
                                :class="[
                                    'inline-flex items-center px-2.5 py-1 rounded-full text-xs shadow-2xs',
                                    getJenjangBadgeClass(employee.jenjang_kepegawaian)
                                ]"
                            >
                                {{ employee.jenjang_kepegawaian }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Segmented Capsule Tabs Navigation -->
            <div class="relative z-10 mb-5">
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
                            :disabled="(isEditing || isAnyManaging) && activeTab !== tab.id"
                            :class="[
                                'inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs sm:text-sm transition-all duration-150 shrink-0 whitespace-nowrap',
                                activeTab === tab.id
                                    ? 'bg-emerald-100 text-emerald-950 font-bold border border-emerald-300 shadow-2xs'
                                    : (isEditing || isAnyManaging)
                                        ? 'text-slate-400 opacity-40 cursor-not-allowed border border-transparent'
                                        : 'text-slate-600 hover:text-slate-900 font-medium border border-transparent hover:bg-slate-200/60 cursor-pointer'
                            ]"
                            :title="(isEditing || isAnyManaging) && activeTab !== tab.id ? 'Selesaikan atau batalkan mode edit/kelola terlebih dahulu untuk berpindah tab' : ''"
                        >
                            <span class="material-symbols-outlined text-[18px]">{{ tab.icon }}</span>
                            <span>{{ tab.label }}</span>
                            <span v-if="(isEditing || isAnyManaging) && activeTab !== tab.id" class="material-symbols-outlined text-[14px] text-slate-400">lock</span>
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

            <!-- Card Konten Tab Pegawai (Tab 1, 2, 3: Identitas, Alamat, Keluarga) -->
            <div
                v-if="['identitas', 'alamat', 'keluarga'].includes(activeTab)"
                class="bg-white rounded-3xl border border-slate-200/90 p-5 sm:p-7 shadow-xs mb-8"
            >
                <!-- Warning NIK Kosong (View Mode Only) -->
                <div v-if="!isEditing && !employee.nik" class="mb-6 p-4 rounded-xl bg-error/10 border border-error/20 flex gap-3 text-error">
                    <span class="material-symbols-outlined shrink-0 mt-0.5">warning</span>
                    <div>
                        <h4 class="font-bold text-sm">NIK Belum Dimasukkan</h4>
                        <p class="text-xs mt-1 leading-relaxed text-error/90">
                            Pegawai ini belum memiliki data NIK. Relasi data (Riwayat Pendidikan dan Catatan Kepegawaian) sangat bergantung pada NIK sebagai pengenal unik. Klik tombol <strong>Edit Data</strong> untuk melengkapi NIK.
                        </p>
                    </div>
                </div>

                <!-- Validation Error Alert in Edit Mode -->
                <div v-if="isEditing && Object.keys(form.errors).length > 0" class="mb-6 p-4 rounded-xl bg-error/10 border border-error/20 flex items-start gap-3 text-error">
                    <span class="material-symbols-outlined shrink-0 text-xl">error</span>
                    <div class="text-xs sm:text-sm">
                        <span class="font-bold">Terdapat kesalahan pada isian form:</span>
                        <ul class="list-disc list-inside mt-1 space-y-0.5">
                            <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                        </ul>
                    </div>
                </div>

                <!-- TAB 1: IDENTITAS -->
                <TabIdentitas
                    v-if="activeTab === 'identitas'"
                    :employee="employee"
                    :form="form"
                    :is-editing="isEditing"
                    :can-edit="canEdit"
                    :can-view-kepegawaian="canViewKepegawaian"
                    :can-update-kepegawaian="canUpdateKepegawaian"
                    @start-edit="startEdit"
                    @cancel-edit="cancelEdit"
                    @save-changes="saveChanges"
                />

                <!-- TAB 2: ALAMAT -->
                <TabAlamat
                    v-else-if="activeTab === 'alamat'"
                    :employee="employee"
                    :form="form"
                    :is-editing="isEditing"
                    :can-edit="canEdit"
                    @start-edit="startEdit"
                    @cancel-edit="cancelEdit"
                    @save-changes="saveChanges"
                />

                <!-- TAB 3: KELUARGA -->
                <TabKeluarga
                    v-else-if="activeTab === 'keluarga'"
                    :employee="employee"
                    :form="form"
                    :is-editing="isEditing"
                    :can-edit="canEdit"
                    @start-edit="startEdit"
                    @cancel-edit="cancelEdit"
                    @save-changes="saveChanges"
                />
            </div>

            <!-- TAB 4: KEPEGAWAIAN (Wadah Root Tanpa Double Card) -->
            <TabKepegawaian
                v-else-if="activeTab === 'kepegawaian'"
                :employee="employee"
                :form="form"
                :is-editing="isEditing"
                :is-any-managing="isAnyManaging"
                :can-view-kepegawaian="canViewKepegawaian"
                :can-create-kepegawaian="canCreateKepegawaian"
                :can-update-kepegawaian="canUpdateKepegawaian"
                :can-delete-kepegawaian="canDeleteKepegawaian"
                @start-edit="startEdit"
                @cancel-edit="cancelEdit"
                @save-changes="saveChanges"
                @managing-changed="val => setManaging('kepegawaian', val)"
            />

            <!-- TAB 5: PENUGASAN (Wadah Root Tanpa Double Card) -->
            <TabPenugasan
                v-else-if="activeTab === 'penugasan'"
                :employee="employee"
                :departments="departments"
                :school-units="schoolUnits"
                :is-editing="isEditing"
                :can-view-penugasan="canViewPenugasan"
                :can-create-penugasan="canCreatePenugasan"
                :can-update-penugasan="canUpdatePenugasan"
                :can-delete-penugasan="canDeletePenugasan"
                @managing-changed="val => setManaging('penugasan', val)"
            />

            <!-- TAB 6: PENDIDIKAN (Wadah Root Tanpa Double Card) -->
            <TabPendidikan
                v-else-if="activeTab === 'pendidikan'"
                :employee="employee"
                :is-editing="isEditing"
                :can-edit="canEdit"
                @managing-changed="val => setManaging('pendidikan', val)"
            />

            <!-- TAB 7: PERIZINAN (Wadah Root Tanpa Double Card) -->
            <TabPerizinan
                v-else-if="activeTab === 'perizinan'"
                :employee="employee"
                :permissions="permissions"
                :can-approve-permit="canApprovePermit"
                :is-editing="isEditing"
                :can-edit="canEdit"
                @managing-changed="val => setManaging('perizinan', val)"
            />

            <!-- TAB 8: DOKUMEN (Wadah Root Tanpa Double Card) -->
            <TabDokumen
                v-else-if="activeTab === 'dokumen'"
                :employee="employee"
                :permissions="permissions"
                :can-edit="canEdit"
            />
        </div>

        <!-- MODAL PREVIEW DOKUMEN GLOBAL -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showPreviewModal && previewDoc"
                class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-black/75 backdrop-blur-md"
                @click.self="closePreviewModal"
            >
                <div class="bg-white rounded-2xl border border-outline-variant/60 shadow-2xl w-full max-w-4xl h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-4 border-b border-outline-variant/60 bg-white shrink-0">
                        <div class="flex items-center gap-3 truncate mr-2">
                            <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[18px]">
                                    {{ previewDoc.mime_type?.includes('pdf') ? 'picture_as_pdf' : 'image' }}
                                </span>
                            </div>
                            <div class="truncate">
                                <h3 class="text-xs sm:text-sm font-bold text-on-surface truncate">{{ previewDoc.title || previewDoc.file_name }}</h3>
                                <p class="text-[11px] text-on-surface-variant truncate">{{ previewDoc.file_name }} • {{ previewDoc.formatted_file_size }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <a
                                :href="previewDoc.download_url"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-outline-variant/60 hover:bg-slate-100 text-on-surface transition-colors cursor-pointer"
                                title="Unduh Berkas"
                            >
                                <span class="material-symbols-outlined text-[16px]">download</span>
                                <span class="hidden sm:inline">Unduh</span>
                            </a>
                            <a
                                :href="previewDoc.preview_url"
                                target="_blank"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-primary/10 hover:bg-primary/20 text-primary transition-colors cursor-pointer"
                                title="Buka Tab Baru"
                            >
                                <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                                <span class="hidden sm:inline">Tab Baru</span>
                            </a>
                            <button
                                type="button"
                                @click="closePreviewModal"
                                class="p-1.5 rounded-lg text-on-surface-variant hover:text-on-surface hover:bg-slate-100 transition-colors ml-1 cursor-pointer"
                                title="Tutup Preview"
                            >
                                <span class="material-symbols-outlined text-[20px]">close</span>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Content (PDF or Image viewer) -->
                    <div class="flex-1 bg-white overflow-auto p-4 flex items-center justify-center">
                        <iframe
                            v-if="previewDoc.mime_type?.includes('pdf')"
                            :src="previewDoc.preview_url"
                            class="w-full h-full rounded-xl border border-outline-variant/30 shadow-inner"
                            title="Pratinjau Dokumen PDF"
                        ></iframe>
                        <img
                            v-else
                            :src="previewDoc.preview_url"
                            :alt="previewDoc.title"
                            class="max-w-full max-h-full object-contain rounded-xl shadow-lg border border-outline-variant/20"
                        />
                    </div>
                </div>
            </div>
        </Transition>

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
                    @click.self="closeDeleteConfirmModal"
                >
                    <div class="w-full max-w-sm sm:max-w-md bg-white rounded-3xl p-6 sm:p-7 shadow-2xl border border-slate-100 space-y-4 animate-in zoom-in-95 duration-200">
                        <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center mx-auto shadow-2xs">
                            <span class="material-symbols-outlined text-3xl">delete_forever</span>
                        </div>

                        <div class="text-center space-y-1.5">
                            <h3 class="text-base sm:text-lg font-bold text-slate-900 leading-tight">
                                {{ deleteConfirmData.title }}
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                                {{ deleteConfirmData.message }}
                            </p>
                        </div>

                        <div v-if="deleteConfirmData.itemLabel" class="p-3 bg-slate-50 border border-slate-200/80 rounded-2xl text-center">
                            <span class="text-xs font-semibold text-slate-800 break-words leading-relaxed font-mono">
                                {{ deleteConfirmData.itemLabel }}
                            </span>
                        </div>

                        <div class="p-2.5 bg-amber-50 border border-amber-200 rounded-xl text-center">
                            <p class="text-[11px] font-medium text-amber-800 leading-tight flex items-center justify-center gap-1.5">
                                <span class="material-symbols-outlined text-[15px] shrink-0">info</span>
                                <span>Tindakan ini permanen dan tidak dapat dibatalkan.</span>
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <button
                                type="button"
                                @click="closeDeleteConfirmModal"
                                :disabled="deleteConfirmData.isDeleting"
                                class="w-full py-2.5 px-4 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 font-bold text-xs sm:text-sm transition-all cursor-pointer disabled:opacity-50"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                @click="handleConfirmDelete"
                                :disabled="deleteConfirmData.isDeleting"
                                class="w-full py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs sm:text-sm shadow-md shadow-rose-600/20 transition-all cursor-pointer flex items-center justify-center gap-1.5 disabled:opacity-50"
                            >
                                <span v-if="deleteConfirmData.isDeleting" class="material-symbols-outlined text-[18px] animate-spin">
                                    progress_activity
                                </span>
                                <span v-else class="material-symbols-outlined text-[18px]">
                                    delete
                                </span>
                                <span>{{ deleteConfirmData.isDeleting ? 'Menghapus...' : 'Ya, Hapus' }}</span>
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
