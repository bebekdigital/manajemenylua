<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, usePage, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    employee: {
        type: Object,
        required: true,
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
});

const page = usePage();
const activeTab = ref('identitas');
const isEditing = ref(false);
const dismissedFlash = ref(false);

const flashSuccess = computed(() => !dismissedFlash.value ? page.props.flash?.success : null);
const flashError = computed(() => !dismissedFlash.value ? page.props.flash?.error : null);

const tabs = [
    { id: 'identitas', label: 'Identitas', icon: 'badge' },
    { id: 'alamat', label: 'Alamat', icon: 'home' },
    { id: 'keluarga', label: 'Keluarga', icon: 'family_restroom' },
    { id: 'kepegawaian', label: 'Kepegawaian', icon: 'work' },
    { id: 'penugasan', label: 'Penugasan', icon: 'assignment_ind' },
    { id: 'pendidikan', label: 'Pendidikan', icon: 'school' },
    { id: 'dokumen', label: 'Dokumen', icon: 'folder_shared' },
];

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

    // Kepegawaian (Data Statis Sheet 1 & Jenjang Sheet 3)
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

    // Riwayat Pendidikan
    riwayat_pendidikan: [],
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

    form.riwayat_pendidikan = (props.employee.riwayat_pendidikan || []).map((p) => ({
        id: p.id,
        jenjang_pendidikan: p.jenjang_pendidikan || '',
        jurusan: p.jurusan || '',
        instansi_pendidikan: p.instansi_pendidikan || '',
        tahun_lulus: p.tahun_lulus || '',
        pembiayaan: p.pembiayaan || '',
    }));
}

onMounted(() => {
    initFormFromProps();
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('edit') === 'true') {
        isEditing.value = true;
    }
});

function startEdit() {
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
    form.put(`/staff/${props.employee.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditing.value = false;
            dismissedFlash.value = false;
        },
    });
}

function copyDomisiliToTinggal() {
    form.jalan_tinggal = form.jalan_domisili;
    form.rt_rw_tinggal = form.rt_rw_domisili;
    form.dusun_tinggal = form.dusun_domisili;
    form.desa_tinggal = form.desa_domisili;
    form.kecamatan_tinggal = form.kecamatan_domisili;
    form.kabupaten_tinggal = form.kab_domisili;
    form.provinsi_tinggal = form.provinsi_domisili;
}

function addEducationRow() {
    form.riwayat_pendidikan.push({
        jenjang_pendidikan: '',
        jurusan: '',
        instansi_pendidikan: '',
        tahun_lulus: '',
        pembiayaan: '',
    });
}

function removeEducationRow(index) {
    form.riwayat_pendidikan.splice(index, 1);
}

function getAlamatDomisili(e) {
    return [e.jalan_domisili, e.rt_rw_domisili, e.dusun_domisili, e.desa_domisili, e.kecamatan_domisili, e.kab_domisili, e.provinsi_domisili]
        .filter(Boolean).join(', ') || '-';
}

function getAlamatTinggal(e) {
    return [e.jalan_tinggal, e.rt_rw_tinggal, e.dusun_tinggal, e.desa_tinggal, e.kecamatan_tinggal, e.kabupaten_tinggal, e.provinsi_tinggal]
        .filter(Boolean).join(', ') || '-';
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

function getJenjangColor(jenjang) {
    const map = {
        'GTY': 'bg-primary/10 text-primary',
        'GTT': 'bg-secondary/10 text-secondary',
        'PTY': 'bg-tertiary/10 text-tertiary',
        'PTT': 'bg-orange-100 text-orange-700',
    };
    return map[jenjang] ?? 'bg-outline-variant/10 text-on-surface-variant';
}

function hitungMasaKerja(tmtDate) {
    if (!tmtDate) return '-';
    const start = new Date(tmtDate);
    if (isNaN(start.getTime())) return '-';
    const now = new Date();
    if (start > now) return '0 Tahun';

    let years = now.getFullYear() - start.getFullYear();
    let months = now.getMonth() - start.getMonth();
    let days = now.getDate() - start.getDate();

    if (days < 0) {
        months--;
    }
    if (months < 0) {
        years--;
        months += 12;
    }

    if (years <= 0 && months <= 0) {
        return 'Kurang dari 1 bulan';
    }

    const parts = [];
    if (years > 0) parts.push(`${years} Tahun`);
    if (months > 0) parts.push(`${months} Bulan`);
    return parts.join(' ');
}

function formatTanggalIndo(dateStr) {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
    } catch {
        return dateStr;
    }
}

function hitungUsia(tanggalLahir, ttlString = '') {
    if (tanggalLahir) {
        const birthDate = new Date(tanggalLahir);
        if (!isNaN(birthDate.getTime())) {
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            return age >= 0 ? `${age} Tahun` : '-';
        }
    }

    if (ttlString) {
        const indonesianMonths = {
            januari: 0, februari: 1, maret: 2, april: 3, mei: 4, juni: 5,
            juli: 6, agustus: 7, september: 8, oktober: 9, november: 10, desember: 11
        };
        const match = ttlString.match(/(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/);
        if (match) {
            const day = parseInt(match[1]);
            const monthName = match[2].toLowerCase();
            const year = parseInt(match[3]);
            if (indonesianMonths[monthName] !== undefined) {
                const birthDate = new Date(year, indonesianMonths[monthName], day);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const m = today.getMonth() - birthDate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                return age >= 0 ? `${age} Tahun` : '-';
            }
        }

        const numMatch = ttlString.match(/(\d{1,2})[-/](\d{1,2})[-/](\d{4})/);
        if (numMatch) {
            const day = parseInt(numMatch[1]);
            const month = parseInt(numMatch[2]) - 1;
            const year = parseInt(numMatch[3]);
            const birthDate = new Date(year, month, day);
            if (!isNaN(birthDate.getTime())) {
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const m = today.getMonth() - birthDate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                return age >= 0 ? `${age} Tahun` : '-';
            }
        }
    }

    return '-';
}

// ==========================================
// DOKUMEN PEGAWAI (PRIVATE STORAGE - OPSI A)
// ==========================================
const showUploadModal = ref(false);
const showPreviewModal = ref(false);
const uploadCategory = ref('');
const uploadRecordType = ref(null);
const uploadRecordId = ref(null);
const uploadTitle = ref('');
const uploadFile = ref(null);
const uploadFileError = ref('');
const isUploading = ref(false);
const isDragging = ref(false);
const fileInput = ref(null);

const previewDoc = ref(null);

function openUploadModal({ category, recordType = null, recordId = null, title = '' }) {
    uploadCategory.value = category;
    uploadRecordType.value = recordType;
    uploadRecordId.value = recordId;
    uploadTitle.value = title;
    uploadFile.value = null;
    uploadFileError.value = '';
    showUploadModal.value = true;
}

function closeUploadModal() {
    if (isUploading.value) return;
    showUploadModal.value = false;
    uploadFile.value = null;
    uploadFileError.value = '';
}

function handleFileChange(event) {
    const file = event.target.files?.[0];
    validateAndSetFile(file);
}

function handleDrop(event) {
    isDragging.value = false;
    const file = event.dataTransfer.files?.[0];
    validateAndSetFile(file);
}

function validateAndSetFile(file) {
    if (!file) return;
    const allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
    if (!allowed.includes(file.type)) {
        uploadFileError.value = 'Format berkas tidak didukung. Harap pilih berkas PDF, JPG, PNG, atau WEBP.';
        uploadFile.value = null;
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        uploadFileError.value = 'Ukuran berkas melebihi batas maksimal 10 MB.';
        uploadFile.value = null;
        return;
    }
    uploadFileError.value = '';
    uploadFile.value = file;
}

function formatBytes(bytes) {
    if (!bytes) return '0 B';
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return bytes + ' B';
}

function submitDocument() {
    if (!uploadFile.value) {
        uploadFileError.value = 'Silakan pilih berkas dokumen terlebih dahulu.';
        return;
    }

    const formData = new FormData();
    formData.append('file', uploadFile.value);
    formData.append('category', uploadCategory.value);
    if (uploadRecordType.value) formData.append('record_type', uploadRecordType.value);
    if (uploadRecordId.value) formData.append('record_id', uploadRecordId.value);
    if (uploadTitle.value) formData.append('title', uploadTitle.value);

    isUploading.value = true;
    router.post(`/staff/${props.employee.id}/documents`, formData, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showUploadModal.value = false;
            uploadFile.value = null;
            isUploading.value = false;
        },
        onError: (errors) => {
            isUploading.value = false;
            uploadFileError.value = Object.values(errors).join(' ') || 'Gagal mengunggah dokumen.';
        },
    });
}

function openPreview(doc) {
    if (!doc) return;
    previewDoc.value = doc;
    showPreviewModal.value = true;
}

function closePreviewModal() {
    showPreviewModal.value = false;
    previewDoc.value = null;
}

function deleteDocument(doc) {
    if (!doc) return;
    if (!confirm(`Apakah Anda yakin ingin menghapus dokumen "${doc.title}"?`)) {
        return;
    }
    router.delete(`/staff/${props.employee.id}/documents/${doc.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop bg-surface-container-lowest" :class="{ 'pb-24': isEditing }">
        <Head :title="`${isEditing ? 'Edit' : 'Detail'} Pegawai - ${employee.nama}`" />

        <!-- Back Button -->
        <div class="mb-4 flex items-center justify-between gap-4">
            <Link href="/staff" class="inline-flex items-center gap-1 text-sm font-medium text-on-surface-variant hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Kembali ke Data Pegawai
            </Link>
        </div>

        <!-- Flash Notifications -->
        <div
            v-if="flashSuccess"
            class="mb-5 p-4 bg-primary/10 border border-primary/30 rounded-2xl flex items-start justify-between gap-3 animate-in fade-in duration-200"
        >
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-primary text-2xl shrink-0 mt-0.5">check_circle</span>
                <div>
                    <h4 class="text-sm font-bold text-primary">Berhasil</h4>
                    <p class="text-xs text-on-surface-variant mt-0.5 leading-relaxed">{{ flashSuccess }}</p>
                </div>
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
                <div>
                    <h4 class="text-sm font-bold text-error">Terjadi Kesalahan</h4>
                    <p class="text-xs text-on-surface-variant mt-0.5 leading-relaxed">{{ flashError }}</p>
                </div>
            </div>
            <button
                type="button"
                @click="dismissedFlash = true"
                class="text-on-surface-variant hover:text-on-surface p-1 rounded-full hover:bg-error/15 transition-colors cursor-pointer"
            >
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>


        <!-- Main Card -->
        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 flex flex-col overflow-hidden">
            <!-- Profile Header (Warna Emas Soft) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between p-5 sm:px-6 sm:py-5 border-b border-amber-200/70 bg-gradient-to-r from-amber-50/90 via-amber-100/40 to-yellow-50/80 gap-4">
                <div class="flex items-center gap-4 sm:gap-5">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-white/95 flex items-center justify-center overflow-hidden border-2 border-amber-300/70 shadow-sm shrink-0 ring-4 ring-amber-100/60">
                        <img
                            v-if="employee.foto"
                            :src="employee.foto"
                            :alt="employee.nama"
                            class="w-full h-full object-cover"
                        />
                        <span v-else class="text-primary font-bold text-2xl sm:text-3xl">
                            {{ employee.nama ? employee.nama.charAt(0) : '?' }}
                        </span>
                    </div>
                    <div>
                        <h3 class="text-lg sm:text-xl font-bold text-on-surface">{{ employee.nama_lengkap_gelar || employee.nama }}</h3>
                        <p class="text-xs sm:text-sm text-on-surface-variant font-medium">NIPY: {{ employee.nipy || '-' }}</p>
                        <div class="flex flex-wrap items-center gap-2 mt-2">
                            <span
                                v-if="employee.jenjang_kepegawaian"
                                :class="['inline-flex items-center px-2.5 py-0.5 rounded-md text-[10px] font-semibold border shadow-xs', getJenjangColor(employee.jenjang_kepegawaian)]"
                            >
                                {{ employee.jenjang_kepegawaian }}
                            </span>
                            <span
                                v-if="employee.status_keaktifan"
                                :class="['inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold border shadow-xs', getStatusColor(employee.status_keaktifan)]"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                {{ employee.status_keaktifan }}
                            </span>
                        </div>
                        <p v-if="employee.jabatan" class="text-xs text-on-surface-variant mt-1">
                            {{ employee.jabatan }}{{ employee.unit_kerja ? ` · ${employee.unit_kerja}` : '' }}
                        </p>
                    </div>
                </div>

                <!-- Action Button in Header -->
                <div v-if="!isEditing" class="flex items-center gap-2 self-start sm:self-auto shrink-0">
                    <button
                        type="button"
                        @click="startEdit"
                        class="inline-flex items-center gap-2 px-4 py-2 sm:px-5 sm:py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-xs sm:text-sm transition-all shadow-xs hover:shadow-md cursor-pointer"
                        title="Edit data pegawai ini langsung di tab"
                    >
                        <span class="material-symbols-outlined text-[18px]">edit_square</span>
                        <span>Edit Data Pegawai</span>
                    </button>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="flex border-b border-outline-variant/30 px-4 bg-surface-container-low overflow-x-auto">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="[
                        'flex items-center gap-2 px-3 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm font-medium transition-colors whitespace-nowrap border-b-2 cursor-pointer',
                        activeTab === tab.id
                            ? 'text-primary border-primary bg-primary/5 font-semibold'
                            : 'text-on-surface-variant border-transparent hover:text-on-surface hover:border-outline-variant'
                    ]"
                >
                    <span class="material-symbols-outlined text-[18px]">{{ tab.icon }}</span>
                    {{ tab.label }}
                </button>
            </div>

            <!-- Tab Content -->
            <div class="flex-1 p-6">

                <!-- Warning NIK Kosong (View Mode Only) -->
                <div v-if="!isEditing && !employee.nik" class="mb-6 p-4 rounded-xl bg-error/10 border border-error/20 flex gap-3 text-error">
                    <span class="material-symbols-outlined shrink-0 mt-0.5">warning</span>
                    <div>
                        <h4 class="font-bold text-sm">NIK Belum Dimasukkan</h4>
                        <p class="text-xs mt-1 leading-relaxed text-error/90">
                            Pegawai ini belum memiliki data NIK. Relasi data (Riwayat Pendidikan dan Catatan Kepegawaian) sangat bergantung pada NIK sebagai pengenal unik. Klik tombol <strong>Edit Data Pegawai</strong> untuk melengkapi NIK.
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
                <div v-if="activeTab === 'identitas'" class="space-y-4">
                    <!-- VIEW MODE -->
                    <div v-if="!isEditing" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Nama Lengkap</label>
                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.nama }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">NIPY</label>
                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5 font-mono">{{ employee.nipy || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Gelar Depan</label>
                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.gelar_depan || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Gelar Belakang</label>
                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.gelar_belakang || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Jenis Kelamin</label>
                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">
                                {{ employee.jk === 'L' ? 'Laki-laki' : employee.jk === 'P' ? 'Perempuan' : '-' }}
                            </p>
                        </div>
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Tempat, Tanggal Lahir</label>
                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.ttl || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Usia</label>
                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">
                                <span v-if="hitungUsia(employee.tanggal_lahir, employee.ttl) !== '-'" class="inline-flex items-center gap-1.5 text-primary font-semibold">
                                    <span class="material-symbols-outlined text-[16px]">cake</span>
                                    <span>{{ hitungUsia(employee.tanggal_lahir, employee.ttl) }}</span>
                                </span>
                                <span v-else class="text-outline">-</span>
                            </p>
                        </div>
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">NIK</label>
                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5 font-mono">{{ employee.nik || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">No. WhatsApp</label>
                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.no_wa || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Email</label>
                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.email || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Kepemilikan BPJS</label>
                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.kepemilikan_bpjs || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Penanggung BPJS</label>
                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.penanggung_bpjs || '-' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Skill / Keahlian</label>
                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.skill || '-' }}</p>
                        </div>
                    </div>

                    <!-- EDIT MODE -->
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Gelar Depan
                            </label>
                            <input
                                v-model="form.gelar_depan"
                                type="text"
                                placeholder="Contoh: Dr., Ir., Ust."
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                :class="{ 'border-error': form.errors.gelar_depan }"
                            />
                            <div v-if="form.errors.gelar_depan" class="text-error text-xs mt-1">{{ form.errors.gelar_depan }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Gelar Belakang
                            </label>
                            <input
                                v-model="form.gelar_belakang"
                                type="text"
                                placeholder="Contoh: S.Pd, M.Pd, Lc."
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                :class="{ 'border-error': form.errors.gelar_belakang }"
                            />
                            <div v-if="form.errors.gelar_belakang" class="text-error text-xs mt-1">{{ form.errors.gelar_belakang }}</div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Nama Lengkap <span class="text-error">*</span>
                            </label>
                            <input
                                v-model="form.nama"
                                type="text"
                                required
                                placeholder="Nama lengkap tanpa gelar"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                :class="{ 'border-error': form.errors.nama }"
                            />
                            <div v-if="form.errors.nama" class="text-error text-xs mt-1">{{ form.errors.nama }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                NIPY
                            </label>
                            <input
                                v-model="form.nipy"
                                type="text"
                                placeholder="Nomor Induk Pegawai Yayasan"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all font-mono"
                                :class="{ 'border-error': form.errors.nipy }"
                            />
                            <div v-if="form.errors.nipy" class="text-error text-xs mt-1">{{ form.errors.nipy }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                NIK (Nomor Induk Kependudukan)
                            </label>
                            <input
                                v-model="form.nik"
                                type="text"
                                placeholder="16 digit NIK"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all font-mono"
                                :class="{ 'border-error': form.errors.nik }"
                            />
                            <div v-if="form.errors.nik" class="text-error text-xs mt-1">{{ form.errors.nik }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Jenis Kelamin
                            </label>
                            <select
                                v-model="form.jk"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                                :class="{ 'border-error': form.errors.jk }"
                            >
                                <option value="">-- Pilih Jenis Kelamin --</option>
                                <option value="L">Laki-laki (L)</option>
                                <option value="P">Perempuan (P)</option>
                            </select>
                            <div v-if="form.errors.jk" class="text-error text-xs mt-1">{{ form.errors.jk }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Nomor WhatsApp
                            </label>
                            <input
                                v-model="form.no_wa"
                                type="text"
                                placeholder="Contoh: 081234567890"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                :class="{ 'border-error': form.errors.no_wa }"
                            />
                            <div v-if="form.errors.no_wa" class="text-error text-xs mt-1">{{ form.errors.no_wa }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Tempat Lahir
                            </label>
                            <input
                                v-model="form.tempat_lahir"
                                type="text"
                                placeholder="Kota / Kabupaten Kelahiran"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                :class="{ 'border-error': form.errors.tempat_lahir }"
                            />
                            <div v-if="form.errors.tempat_lahir" class="text-error text-xs mt-1">{{ form.errors.tempat_lahir }}</div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                                    Tanggal Lahir
                                </label>
                                <span v-if="hitungUsia(form.tanggal_lahir) !== '-'" class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary bg-primary/10 px-2 py-0.5 rounded-md">
                                    <span class="material-symbols-outlined text-[13px]">cake</span>
                                    <span>Usia: {{ hitungUsia(form.tanggal_lahir) }}</span>
                                </span>
                            </div>
                            <input
                                v-model="form.tanggal_lahir"
                                type="date"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                                :class="{ 'border-error': form.errors.tanggal_lahir }"
                            />
                            <div v-if="form.errors.tanggal_lahir" class="text-error text-xs mt-1">{{ form.errors.tanggal_lahir }}</div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Email
                            </label>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="nama@email.com"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                :class="{ 'border-error': form.errors.email }"
                            />
                            <div v-if="form.errors.email" class="text-error text-xs mt-1">{{ form.errors.email }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Kepemilikan BPJS
                            </label>
                            <select
                                v-model="form.kepemilikan_bpjs"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                            >
                                <option value="">-- Pilih --</option>
                                <option value="Ya">Ya</option>
                                <option value="Tidak">Tidak</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Penanggung BPJS
                            </label>
                            <input
                                v-model="form.penanggung_bpjs"
                                type="text"
                                placeholder="Contoh: Yayasan / Pribadi / Mandiri"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Skill / Keahlian
                            </label>
                            <input
                                v-model="form.skill"
                                type="text"
                                placeholder="Contoh: Matematika, Desain Grafis, IT"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                :class="{ 'border-error': form.errors.skill }"
                            />
                            <div v-if="form.errors.skill" class="text-error text-xs mt-1">{{ form.errors.skill }}</div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: ALAMAT -->
                <div v-if="activeTab === 'alamat'" class="space-y-6">
                    <!-- VIEW MODE -->
                    <template v-if="!isEditing">
                        <!-- Domisili -->
                        <div>
                            <h4 class="text-xs sm:text-sm font-bold text-primary mb-3 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">location_on</span>
                                Alamat Domisili (KTP)
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 pl-1">
                                <div class="md:col-span-2">
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Alamat Lengkap</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ getAlamatDomisili(employee) }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Jalan</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.jalan_domisili || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">RT / RW</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.rt_rw_domisili || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Dusun</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.dusun_domisili || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Desa / Kelurahan</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.desa_domisili || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Kecamatan</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.kecamatan_domisili || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Kabupaten / Kota</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.kab_domisili || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Provinsi</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.provinsi_domisili || '-' }}</p>
                                </div>
                            </div>
                        </div>

                        <hr class="border-outline-variant/30" />

                        <!-- Tinggal -->
                        <div>
                            <h4 class="text-xs sm:text-sm font-bold text-primary mb-3 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">home</span>
                                Alamat Tempat Tinggal
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 pl-1">
                                <div class="md:col-span-2">
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Alamat Lengkap</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ getAlamatTinggal(employee) }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Jalan</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.jalan_tinggal || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">RT / RW</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.rt_rw_tinggal || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Dusun</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.dusun_tinggal || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Desa / Kelurahan</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.desa_tinggal || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Kecamatan</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.kecamatan_tinggal || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Kabupaten / Kota</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.kabupaten_tinggal || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Provinsi</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.provinsi_tinggal || '-' }}</p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Status Rumah</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.status_rumah || '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- EDIT MODE -->
                    <template v-else>
                        <!-- Domisili Form -->
                        <div class="bg-surface-container-low/40 p-4 sm:p-5 rounded-2xl border border-outline-variant/30 space-y-4">
                            <div class="flex items-center gap-2 text-primary font-bold text-sm">
                                <span class="material-symbols-outlined text-[18px]">location_on</span>
                                <h4>Alamat Domisili (KTP)</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Jalan Domisili</label>
                                    <input
                                        v-model="form.jalan_domisili"
                                        type="text"
                                        placeholder="Nama jalan, nomor rumah / blok"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">RT / RW</label>
                                    <input
                                        v-model="form.rt_rw_domisili"
                                        type="text"
                                        placeholder="001/002"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Dusun</label>
                                    <input
                                        v-model="form.dusun_domisili"
                                        type="text"
                                        placeholder="Nama dusun / kampung"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Desa / Kelurahan</label>
                                    <input
                                        v-model="form.desa_domisili"
                                        type="text"
                                        placeholder="Desa / Kelurahan"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Kecamatan</label>
                                    <input
                                        v-model="form.kecamatan_domisili"
                                        type="text"
                                        placeholder="Kecamatan"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Kabupaten / Kota</label>
                                    <input
                                        v-model="form.kab_domisili"
                                        type="text"
                                        placeholder="Kabupaten / Kota"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Provinsi</label>
                                    <input
                                        v-model="form.provinsi_domisili"
                                        type="text"
                                        placeholder="Provinsi"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Tinggal Form -->
                        <div class="bg-surface-container-low/40 p-4 sm:p-5 rounded-2xl border border-outline-variant/30 space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div class="flex items-center gap-2 text-primary font-bold text-sm">
                                    <span class="material-symbols-outlined text-[18px]">home</span>
                                    <h4>Alamat Tempat Tinggal Saat Ini</h4>
                                </div>
                                <button
                                    type="button"
                                    @click="copyDomisiliToTinggal"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-primary/30 text-primary bg-primary/5 hover:bg-primary/10 text-xs font-semibold transition-all cursor-pointer self-start sm:self-auto"
                                >
                                    <span class="material-symbols-outlined text-[16px]">content_copy</span>
                                    <span>Salin dari Domisili (KTP)</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Jalan Tinggal</label>
                                    <input
                                        v-model="form.jalan_tinggal"
                                        type="text"
                                        placeholder="Nama jalan, nomor rumah / kontrakan"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">RT / RW</label>
                                    <input
                                        v-model="form.rt_rw_tinggal"
                                        type="text"
                                        placeholder="001/002"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Dusun</label>
                                    <input
                                        v-model="form.dusun_tinggal"
                                        type="text"
                                        placeholder="Nama dusun / kampung"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Desa / Kelurahan</label>
                                    <input
                                        v-model="form.desa_tinggal"
                                        type="text"
                                        placeholder="Desa / Kelurahan"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Kecamatan</label>
                                    <input
                                        v-model="form.kecamatan_tinggal"
                                        type="text"
                                        placeholder="Kecamatan"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Kabupaten / Kota</label>
                                    <input
                                        v-model="form.kabupaten_tinggal"
                                        type="text"
                                        placeholder="Kabupaten / Kota"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Provinsi</label>
                                    <input
                                        v-model="form.provinsi_tinggal"
                                        type="text"
                                        placeholder="Provinsi"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Status Kepemilikan Rumah</label>
                                    <select
                                        v-model="form.status_rumah"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                    >
                                        <option value="">-- Pilih Status Rumah --</option>
                                        <option value="Milik Sendiri">Milik Sendiri</option>
                                        <option value="Sewa / Kontrak">Sewa / Kontrak</option>
                                        <option value="Menumpang / Orang Tua">Menumpang / Orang Tua</option>
                                        <option value="Rumah Dinas">Rumah Dinas</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- TAB 3: KELUARGA -->
                <div v-if="activeTab === 'keluarga'" class="space-y-6">
                    <!-- VIEW MODE -->
                    <template v-if="!isEditing">
                        <!-- Pernikahan -->
                        <div>
                            <h4 class="text-xs sm:text-sm font-bold text-primary mb-3 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">favorite</span>
                                Status Pernikahan
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 pl-1">
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Status Pernikahan</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.status_pernikahan || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Tanggal Menikah</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.formatted_tanggal_menikah || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Nama Suami/Istri</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.nama_suami_istri || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">TTL Suami/Istri</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.ttl_suami_istri || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Pekerjaan Suami/Istri</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.pekerjaan_suami_istri || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Jumlah Anak</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">
                                        {{ employee.jumlah_anak !== null && employee.jumlah_anak !== undefined ? employee.jumlah_anak + ' anak' : '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <hr class="border-outline-variant/30" />

                        <!-- Orangtua -->
                        <div>
                            <h4 class="text-xs sm:text-sm font-bold text-primary mb-3 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">family_restroom</span>
                                Data Orangtua
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 pl-1">
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Nama Ibu</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.nama_ibu || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Nama Ayah</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.nama_ayah || '-' }}</p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Alamat Orangtua</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.alamat_orangtua || '-' }}</p>
                                </div>
                            </div>
                        </div>

                        <hr class="border-outline-variant/30" />

                        <!-- Kontak Darurat -->
                        <div>
                            <h4 class="text-xs sm:text-sm font-bold text-primary mb-3 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">contact_phone</span>
                                Kontak Darurat
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 pl-1">
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Nomor Kontak</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.kontak_darurat || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Hubungan</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.hubungan_kontak_darurat || '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- EDIT MODE -->
                    <template v-else>
                        <!-- Pernikahan Form -->
                        <div class="bg-surface-container-low/40 p-4 sm:p-5 rounded-2xl border border-outline-variant/30 space-y-4">
                            <div class="flex items-center gap-2 text-primary font-bold text-sm">
                                <span class="material-symbols-outlined text-[18px]">favorite</span>
                                <h4>Status Pernikahan & Pasangan</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Status Pernikahan</label>
                                    <select
                                        v-model="form.status_pernikahan"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                    >
                                        <option value="">-- Pilih Status --</option>
                                        <option value="Belum Menikah">Belum Menikah</option>
                                        <option value="Menikah">Menikah</option>
                                        <option value="Cerai Hidup">Cerai Hidup</option>
                                        <option value="Cerai Mati">Cerai Mati</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Tanggal Menikah</label>
                                    <input
                                        v-model="form.tanggal_menikah"
                                        type="date"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Nama Suami / Istri</label>
                                    <input
                                        v-model="form.nama_suami_istri"
                                        type="text"
                                        placeholder="Nama lengkap pasangan"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">TTL Suami / Istri</label>
                                    <input
                                        v-model="form.ttl_suami_istri"
                                        type="text"
                                        placeholder="Contoh: Solo, 10 Maret 1987"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Pekerjaan Suami / Istri</label>
                                    <input
                                        v-model="form.pekerjaan_suami_istri"
                                        type="text"
                                        placeholder="Contoh: Guru / Wiraswasta / PNS"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Jumlah Anak</label>
                                    <input
                                        v-model="form.jumlah_anak"
                                        type="number"
                                        min="0"
                                        placeholder="0"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Data Orangtua Form -->
                        <div class="bg-surface-container-low/40 p-4 sm:p-5 rounded-2xl border border-outline-variant/30 space-y-4">
                            <div class="flex items-center gap-2 text-primary font-bold text-sm">
                                <span class="material-symbols-outlined text-[18px]">family_restroom</span>
                                <h4>Data Orangtua</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Nama Ibu</label>
                                    <input
                                        v-model="form.nama_ibu"
                                        type="text"
                                        placeholder="Nama lengkap ibu"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Nama Ayah</label>
                                    <input
                                        v-model="form.nama_ayah"
                                        type="text"
                                        placeholder="Nama lengkap ayah"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Alamat Orangtua</label>
                                    <input
                                        v-model="form.alamat_orangtua"
                                        type="text"
                                        placeholder="Alamat tempat tinggal orangtua"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Kontak Darurat Form -->
                        <div class="bg-surface-container-low/40 p-4 sm:p-5 rounded-2xl border border-outline-variant/30 space-y-4">
                            <div class="flex items-center gap-2 text-primary font-bold text-sm">
                                <span class="material-symbols-outlined text-[18px]">contact_phone</span>
                                <h4>Kontak Darurat</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Nomor Kontak Darurat</label>
                                    <input
                                        v-model="form.kontak_darurat"
                                        type="text"
                                        placeholder="Contoh: 082345678901"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Hubungan</label>
                                    <input
                                        v-model="form.hubungan_kontak_darurat"
                                        type="text"
                                        placeholder="Contoh: Saudara Kandung / Paman / Kerabat"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- TAB 4: KEPEGAWAIAN -->
                <div v-if="activeTab === 'kepegawaian'" class="space-y-6">
                    <!-- DATA STATIS: PENGANGKATAN, KEAKTIFAN & DAPODIK (SHEET 1) -->
                    <div class="p-4 sm:p-5 bg-surface-container-low border border-outline-variant/30 rounded-2xl shadow-xs">
                        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                            <h4 class="text-xs sm:text-sm font-bold text-primary flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">verified</span>
                                Informasi Pengangkatan, Keaktifan, & Dapodik (Data Statis Pegawai)
                            </h4>
                            <span class="text-[11px] text-on-surface-variant bg-surface-container-lowest px-2.5 py-1 rounded-lg border border-outline-variant/30 font-medium">
                                Database Pegawai (Sheet 1)
                            </span>
                        </div>

                        <!-- View Mode Data Statis -->
                        <div v-if="!isEditing" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- TMT & TST Pengangkatan -->
                            <div class="p-3.5 bg-surface-container-lowest rounded-xl border border-outline-variant/20 flex items-start gap-3">
                                <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0 mt-0.5">
                                    <span class="material-symbols-outlined text-[20px]">calendar_today</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <label class="text-[10px] sm:text-xs font-semibold text-on-surface-variant uppercase tracking-wider block">TMT / TST Pengangkatan</label>
                                    <p class="text-xs sm:text-sm font-bold text-on-surface mt-0.5">
                                        {{ employee.formatted_tmt_pengangkatan || formatTanggalIndo(employee.tmt_pengangkatan) || '-' }}
                                        <span class="text-outline font-normal">s.d.</span>
                                        {{ employee.formatted_tst_pengangkatan || formatTanggalIndo(employee.tst_pengangkatan) || 'Sekarang' }}
                                    </p>
                                    <span class="text-[11px] text-outline mt-0.5 block">Masa pengangkatan pegawai yayasan</span>
                                </div>
                            </div>

                            <!-- Masa Kerja -->
                            <div class="p-3.5 bg-surface-container-lowest rounded-xl border border-outline-variant/20 flex items-start gap-3">
                                <div class="w-9 h-9 rounded-lg bg-secondary/10 text-secondary flex items-center justify-center shrink-0 mt-0.5">
                                    <span class="material-symbols-outlined text-[20px]">hourglass_top</span>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs font-semibold text-on-surface-variant uppercase tracking-wider block">Masa Kerja</label>
                                    <p class="text-sm sm:text-base font-bold text-primary mt-0.5">
                                        {{ hitungMasaKerja(employee.tmt_pengangkatan) || employee.masa_kerja || '-' }}
                                    </p>
                                    <span class="text-[11px] text-outline mt-0.5 block">Dihitung otomatis sejak TMT Pengangkatan</span>
                                </div>
                            </div>

                            <!-- Status Keaktifan -->
                            <div class="p-3.5 bg-surface-container-lowest rounded-xl border border-outline-variant/20 flex items-start gap-3">
                                <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5">
                                    <span class="material-symbols-outlined text-[20px]">check_circle</span>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs font-semibold text-on-surface-variant uppercase tracking-wider block">Status Keaktifan</label>
                                    <div class="mt-1 flex items-center gap-2 flex-wrap">
                                        <span :class="['inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold', getStatusColor(employee.status_keaktifan || 'Aktif')]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                            {{ employee.status_keaktifan || 'Aktif' }}
                                        </span>
                                        <span v-if="employee.keterangan_tidak_aktif" class="text-xs text-on-surface-variant">
                                            ({{ employee.keterangan_tidak_aktif }})
                                        </span>
                                    </div>
                                    <span class="text-[11px] text-outline mt-0.5 block">Status keaktifan pegawai saat ini</span>
                                </div>
                            </div>

                            <!-- Keaktifan Dapodik -->
                            <div class="p-3.5 bg-surface-container-lowest rounded-xl border border-outline-variant/20 flex items-start gap-3 sm:col-span-2 lg:col-span-3">
                                <div class="w-9 h-9 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 mt-0.5">
                                    <span class="material-symbols-outlined text-[20px]">cloud_sync</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 flex-1">
                                    <div>
                                        <label class="text-[10px] sm:text-xs font-semibold text-on-surface-variant uppercase tracking-wider block">Keaktifan Dapodik</label>
                                        <p class="text-xs sm:text-sm font-semibold text-on-surface mt-0.5">
                                            {{ employee.keaktifan_dapodik || '-' }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs font-semibold text-on-surface-variant uppercase tracking-wider block">Unit Keaktifan Dapodik</label>
                                        <p class="text-xs sm:text-sm font-semibold text-on-surface mt-0.5">
                                            {{ employee.unit_keaktifan_dapodik || '-' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Mode Data Statis -->
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                    TMT Pengangkatan
                                </label>
                                <input
                                    v-model="form.tmt_pengangkatan"
                                    type="date"
                                    class="w-full px-3.5 py-2.5 bg-surface-container-lowest border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                    TST Pengangkatan
                                </label>
                                <input
                                    v-model="form.tst_pengangkatan"
                                    type="date"
                                    class="w-full px-3.5 py-2.5 bg-surface-container-lowest border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                />
                            </div>

                            <div class="p-3 bg-surface-container-lowest rounded-xl border border-outline-variant/20 flex items-center justify-between gap-3">
                                <div>
                                    <label class="text-[10px] sm:text-xs font-semibold text-on-surface-variant uppercase tracking-wider block">Masa Kerja Otomatis</label>
                                    <p class="text-xs sm:text-sm font-bold text-primary mt-0.5">
                                        {{ hitungMasaKerja(form.tmt_pengangkatan) }}
                                    </p>
                                </div>
                                <span class="material-symbols-outlined text-primary text-2xl opacity-40">update</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Status Keaktifan</label>
                                <select
                                    v-model="form.status_keaktifan"
                                    class="w-full px-3.5 py-2.5 bg-surface-container-lowest border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                >
                                    <option value="Aktif">Aktif</option>
                                    <option value="Non-Aktif">Non-Aktif</option>
                                    <option value="Pensiun">Pensiun</option>
                                    <option value="Cuti">Cuti</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Keterangan Tidak Aktif</label>
                                <input
                                    v-model="form.keterangan_tidak_aktif"
                                    type="text"
                                    placeholder="Keterangan jika cuti / non-aktif / pensiun"
                                    class="w-full px-3.5 py-2.5 bg-surface-container-lowest border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Keaktifan Dapodik</label>
                                <select
                                    v-model="form.keaktifan_dapodik"
                                    class="w-full px-3.5 py-2.5 bg-surface-container-lowest border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                >
                                    <option value="">-- Pilih Status Dapodik --</option>
                                    <option value="Aktif">Aktif</option>
                                    <option value="Tidak Aktif">Tidak Aktif</option>
                                </select>
                            </div>

                            <div class="md:col-span-2 lg:col-span-3">
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Unit Keaktifan Dapodik</label>
                                <input
                                    v-model="form.unit_keaktifan_dapodik"
                                    type="text"
                                    placeholder="Unit yang terdaftar pada Dapodik"
                                    class="w-full px-3.5 py-2.5 bg-surface-container-lowest border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- EDIT FORM FOR JENJANG KEPEGAWAIAN (Hanya saat isEditing) -->
                    <div v-if="isEditing" class="p-4 sm:p-5 bg-surface-container-low/60 border border-primary/30 rounded-2xl space-y-4">
                        <div class="flex items-center gap-2 text-primary font-bold text-sm">
                            <span class="material-symbols-outlined text-[20px]">workspace_premium</span>
                            <h4>Perbarui Jenjang Kepegawaian</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Jenjang Kepegawaian</label>
                                <select
                                    v-model="form.jenjang_kepegawaian"
                                    class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                >
                                    <option value="">-- Pilih Jenjang --</option>
                                    <option value="GTY">GTY (Guru Tetap Yayasan)</option>
                                    <option value="GTT">GTT (Guru Tidak Tetap)</option>
                                    <option value="PTY">PTY (Pegawai Tetap Yayasan)</option>
                                    <option value="PTT">PTT (Pegawai Tidak Tetap)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">TMT Kenaikan Jenjang</label>
                                <input
                                    v-model="form.tmt_kenaikan_jenjang"
                                    type="date"
                                    class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">TST Kenaikan Jenjang</label>
                                <input
                                    v-model="form.tst_kenaikan_jenjang"
                                    type="date"
                                    class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- RIWAYAT JENJANG KEPEGAWAIAN LIST (SHEET 3 - Murni Tanggal TMT & TST) -->
                    <div>
                        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                            <div>
                                <h4 class="text-xs sm:text-sm font-bold text-primary flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[18px]">work_history</span>
                                    Riwayat Jenjang Kepegawaian (Sheet 3)
                                </h4>
                                <span class="text-[11px] text-on-surface-variant">Berdasarkan tanggal TMT dan TST Kenaikan Jenjang</span>
                            </div>
                            <span class="text-xs text-outline font-medium">
                                {{ employee.riwayat_kepegawaian?.length || 0 }} Catatan
                            </span>
                        </div>

                        <div v-if="employee.riwayat_kepegawaian && employee.riwayat_kepegawaian.length > 0">
                            <div class="overflow-x-auto rounded-xl border border-outline-variant/30 bg-surface-container-low">
                                <table class="w-full text-xs sm:text-sm">
                                    <thead>
                                        <tr class="bg-surface-container-low border-b border-outline-variant/30 text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                                            <th class="px-4 py-3 text-left">No</th>
                                            <th class="px-4 py-3 text-left">Jenjang Kepegawaian</th>
                                            <th class="px-4 py-3 text-left">TMT Kenaikan Jenjang</th>
                                            <th class="px-4 py-3 text-left">TST Kenaikan Jenjang</th>
                                            <th class="px-4 py-3 text-left">Status Masa Berlaku</th>
                                            <th class="px-4 py-3 text-center">Berkas SK Jenjang</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-outline-variant/20">
                                        <tr
                                            v-for="(record, idx) in employee.riwayat_kepegawaian"
                                            :key="record.id || idx"
                                            class="hover:bg-surface-container-lowest/50 transition-colors"
                                        >
                                            <td class="px-4 py-3 text-on-surface-variant font-mono">{{ idx + 1 }}</td>
                                            <td class="px-4 py-3 font-semibold text-on-surface">
                                                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold', getJenjangColor(record.jenjang_kepegawaian)]">
                                                    {{ record.jenjang_kepegawaian || '-' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-on-surface">
                                                {{ record.formatted_tmt_kenaikan_jenjang || formatTanggalIndo(record.tmt_kenaikan_jenjang) || '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-on-surface">
                                                {{ record.formatted_tst_kenaikan_jenjang || record.formatted_tst_jenjang || formatTanggalIndo(record.tst_kenaikan_jenjang || record.tst_jenjang) || 'Sekarang / Tetap' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <span
                                                    v-if="!record.tst_kenaikan_jenjang && !record.tst_jenjang"
                                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-700"
                                                >
                                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                                    Aktif Berlaku
                                                </span>
                                                <span
                                                    v-else
                                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600"
                                                >
                                                    Selesai
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <div v-if="record.document" class="inline-flex items-center gap-1.5">
                                                    <button
                                                        type="button"
                                                        @click="openPreview(record.document)"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                        title="Lihat / Preview SK Jenjang"
                                                    >
                                                        <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                        <span>Lihat SK</span>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="openUploadModal({ category: 'sk_jenjang', recordType: 'record', recordId: record.id, title: `SK Jenjang ${record.jenjang_kepegawaian}` })"
                                                        class="p-1 rounded-lg text-slate-500 hover:text-primary hover:bg-slate-100 transition-colors cursor-pointer"
                                                        title="Ganti Berkas SK"
                                                    >
                                                        <span class="material-symbols-outlined text-[16px]">sync</span>
                                                    </button>
                                                </div>
                                                <button
                                                    v-else
                                                    type="button"
                                                    @click="openUploadModal({ category: 'sk_jenjang', recordType: 'record', recordId: record.id, title: `SK Jenjang ${record.jenjang_kepegawaian}` })"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-primary/10 text-primary hover:bg-primary/20 transition-colors cursor-pointer"
                                                    title="Upload SK Jenjang"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                                    <span>Upload SK</span>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div v-else class="py-10 text-center border-2 border-dashed border-outline-variant/30 rounded-2xl">
                            <span class="material-symbols-outlined text-4xl text-outline/60 block mb-2">work_history</span>
                            <p class="text-sm text-on-surface-variant">Belum ada riwayat jenjang kepegawaian</p>
                        </div>
                    </div>
                </div>

                <!-- TAB 5: PENUGASAN (RIWAYAT JABATAN - SHEET 4) -->
                <div v-if="activeTab === 'penugasan'" class="space-y-6">
                    <!-- EDIT FORM FOR JABATAN (Hanya saat isEditing) -->
                    <div v-if="isEditing" class="p-4 sm:p-5 bg-surface-container-low/60 border border-primary/30 rounded-2xl space-y-4">
                        <div class="flex items-center gap-2 text-primary font-bold text-sm">
                            <span class="material-symbols-outlined text-[20px]">assignment_ind</span>
                            <h4>Perbarui Riwayat Jabatan</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Unit Kerja</label>
                                <input
                                    v-model="form.unit_kerja"
                                    list="unitOptionsDetail"
                                    type="text"
                                    placeholder="Pilih atau ketik unit kerja"
                                    class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                />
                                <datalist id="unitOptionsDetail">
                                    <option v-for="unit in availableUnits" :key="unit" :value="unit" />
                                </datalist>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Jabatan</label>
                                <input
                                    v-model="form.jabatan"
                                    type="text"
                                    placeholder="Contoh: Guru Kelas, Kepala Sekolah, Bendahara"
                                    class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Jenis Kepegawaian</label>
                                <input
                                    v-model="form.jenis_kepegawaian"
                                    type="text"
                                    placeholder="Tenaga Pendidik / Tenaga Kependidikan"
                                    class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">TMT Jabatan</label>
                                <input
                                    v-model="form.tmt_jabatan"
                                    type="date"
                                    class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">TST Jabatan</label>
                                <input
                                    v-model="form.tst_jabatan"
                                    type="date"
                                    class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- RIWAYAT JABATAN LIST (SHEET 4 - Murni Tanggal TMT & TST) -->
                    <div>
                        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                            <div>
                                <h4 class="text-xs sm:text-sm font-bold text-primary flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[18px]">assignment_turned_in</span>
                                    Riwayat Jabatan (Sheet 4)
                                </h4>
                                <span class="text-[11px] text-on-surface-variant">Berdasarkan tanggal TMT dan TST Jabatan</span>
                            </div>
                            <span class="text-xs text-outline font-medium">
                                {{ employee.riwayat_jabatan?.length || 0 }} Jabatan
                            </span>
                        </div>

                        <div v-if="employee.riwayat_jabatan && employee.riwayat_jabatan.length > 0">
                            <div class="overflow-x-auto rounded-xl border border-outline-variant/30 bg-surface-container-low">
                                <table class="w-full text-xs sm:text-sm">
                                    <thead>
                                        <tr class="bg-surface-container-low border-b border-outline-variant/30 text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                                            <th class="px-4 py-3 text-left">No</th>
                                            <th class="px-4 py-3 text-left">Jabatan</th>
                                            <th class="px-4 py-3 text-left">Unit Kerja</th>
                                            <th class="px-4 py-3 text-left">Jenis Kepegawaian</th>
                                            <th class="px-4 py-3 text-left">TMT Jabatan</th>
                                            <th class="px-4 py-3 text-left">TST Jabatan</th>
                                            <th class="px-4 py-3 text-left">Status</th>
                                            <th class="px-4 py-3 text-center">Dokumen SK Penugasan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-outline-variant/20">
                                        <tr
                                            v-for="(pos, idx) in employee.riwayat_jabatan"
                                            :key="pos.id || idx"
                                            class="hover:bg-surface-container-lowest/50 transition-colors"
                                        >
                                            <td class="px-4 py-3 text-on-surface-variant font-mono">{{ idx + 1 }}</td>
                                            <td class="px-4 py-3 font-bold text-on-surface">{{ pos.jabatan || '-' }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-primary/10 text-primary">
                                                    {{ pos.unit_kerja || '-' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-on-surface">{{ pos.jenis_kepegawaian || '-' }}</td>
                                            <td class="px-4 py-3 text-on-surface">
                                                {{ pos.formatted_tmt_jabatan || formatTanggalIndo(pos.tmt_jabatan) || '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-on-surface">
                                                {{ pos.formatted_tst_jabatan || formatTanggalIndo(pos.tst_jabatan) || 'Sekarang / Aktif' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <span
                                                    v-if="!pos.tst_jabatan"
                                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-700"
                                                >
                                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                                    Menjabat
                                                </span>
                                                <span
                                                    v-else
                                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600"
                                                >
                                                    Selesai
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <div v-if="pos.document" class="inline-flex items-center gap-1.5">
                                                    <button
                                                        type="button"
                                                        @click="openPreview(pos.document)"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                        title="Lihat / Preview SK Penugasan"
                                                    >
                                                        <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                        <span>Lihat SK</span>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="openUploadModal({ category: 'sk_penugasan', recordType: 'position', recordId: pos.id, title: `SK Penugasan ${pos.jabatan} (${pos.unit_kerja})` })"
                                                        class="p-1 rounded-lg text-slate-500 hover:text-primary hover:bg-slate-100 transition-colors cursor-pointer"
                                                        title="Ganti Berkas SK"
                                                    >
                                                        <span class="material-symbols-outlined text-[16px]">sync</span>
                                                    </button>
                                                </div>
                                                <button
                                                    v-else
                                                    type="button"
                                                    @click="openUploadModal({ category: 'sk_penugasan', recordType: 'position', recordId: pos.id, title: `SK Penugasan ${pos.jabatan} (${pos.unit_kerja})` })"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-primary/10 text-primary hover:bg-primary/20 transition-colors cursor-pointer"
                                                    title="Upload SK Penugasan"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                                    <span>Upload SK</span>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div v-else class="py-10 text-center border-2 border-dashed border-outline-variant/30 rounded-2xl">
                            <span class="material-symbols-outlined text-4xl text-outline/60 block mb-2">assignment_late</span>
                            <p class="text-sm text-on-surface-variant">Belum ada riwayat jabatan untuk pegawai ini</p>
                        </div>
                    </div>
                </div>

                <!-- TAB 5: PENDIDIKAN -->
                <div v-if="activeTab === 'pendidikan'" class="space-y-4">
                    <!-- VIEW MODE -->
                    <template v-if="!isEditing">
                        <div v-if="employee.riwayat_pendidikan && employee.riwayat_pendidikan.length > 0">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-surface-container-low border-b border-outline-variant/30">
                                            <th class="px-4 py-2.5 text-left text-xs font-bold text-on-surface-variant uppercase tracking-wider">Jenjang</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-bold text-on-surface-variant uppercase tracking-wider">Jurusan</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-bold text-on-surface-variant uppercase tracking-wider">Instansi</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-bold text-on-surface-variant uppercase tracking-wider">Tahun Lulus</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-bold text-on-surface-variant uppercase tracking-wider">Pembiayaan</th>
                                            <th class="px-4 py-2.5 text-center text-xs font-bold text-on-surface-variant uppercase tracking-wider">Berkas Ijazah</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-outline-variant/20">
                                        <tr
                                            v-for="edu in employee.riwayat_pendidikan"
                                            :key="edu.id"
                                            class="hover:bg-surface-container-low/50 transition-colors"
                                        >
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-primary/10 text-primary">
                                                    {{ edu.jenjang_pendidikan || '-' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-xs text-on-surface">{{ edu.jurusan || '-' }}</td>
                                            <td class="px-4 py-3 text-xs text-on-surface">{{ edu.instansi_pendidikan || '-' }}</td>
                                            <td class="px-4 py-3 text-xs font-semibold text-on-surface">{{ edu.tahun_lulus || '-' }}</td>
                                            <td class="px-4 py-3 text-xs text-on-surface">{{ edu.pembiayaan || '-' }}</td>
                                            <td class="px-4 py-3 text-center">
                                                <div v-if="edu.document" class="inline-flex items-center gap-1.5">
                                                    <button
                                                        type="button"
                                                        @click="openPreview(edu.document)"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                        title="Lihat Berkas Ijazah"
                                                    >
                                                        <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                        <span>Lihat Ijazah</span>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="openUploadModal({ category: 'ijazah', recordType: 'education', recordId: edu.id, title: `Ijazah ${edu.jenjang_pendidikan} - ${edu.jurusan || ''}` })"
                                                        class="p-1 rounded-lg text-slate-500 hover:text-primary hover:bg-slate-100 transition-colors cursor-pointer"
                                                        title="Ganti Berkas Ijazah"
                                                    >
                                                        <span class="material-symbols-outlined text-[16px]">sync</span>
                                                    </button>
                                                </div>
                                                <button
                                                    v-else
                                                    type="button"
                                                    @click="openUploadModal({ category: 'ijazah', recordType: 'education', recordId: edu.id, title: `Ijazah ${edu.jenjang_pendidikan} - ${edu.jurusan || ''}` })"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-primary/10 text-primary hover:bg-primary/20 transition-colors cursor-pointer"
                                                    title="Upload Ijazah"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                                    <span>Upload Ijazah</span>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div v-else class="py-12 text-center border-2 border-dashed border-outline-variant/30 rounded-2xl">
                            <span class="material-symbols-outlined text-4xl text-outline/60 block mb-2">school</span>
                            <p class="text-sm text-on-surface-variant">Belum ada riwayat pendidikan</p>
                        </div>
                    </template>

                    <!-- EDIT MODE -->
                    <template v-else>
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-on-surface">Riwayat Pendidikan</h4>
                                <p class="text-xs text-on-surface-variant">Ubah, tambah, atau hapus riwayat pendidikan</p>
                            </div>
                            <button
                                type="button"
                                @click="addEducationRow"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-primary/10 text-primary hover:bg-primary/20 text-xs font-bold transition-all cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[16px]">add</span>
                                <span>Tambah Pendidikan</span>
                            </button>
                        </div>

                        <div v-if="form.riwayat_pendidikan.length === 0" class="py-10 text-center border-2 border-dashed border-outline-variant/40 rounded-2xl">
                            <span class="material-symbols-outlined text-3xl text-outline/60 block mb-1">school</span>
                            <p class="text-xs sm:text-sm text-on-surface-variant">Belum ada riwayat pendidikan.</p>
                            <button
                                type="button"
                                @click="addEducationRow"
                                class="mt-3 inline-flex items-center gap-1 text-xs text-primary font-bold hover:underline cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[14px]">add_circle</span>
                                Klik untuk menambah data pendidikan
                            </button>
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="(edu, index) in form.riwayat_pendidikan"
                                :key="index"
                                class="p-4 bg-surface-container-low rounded-2xl border border-outline-variant/40"
                            >
                                <div class="flex items-center justify-between mb-3 border-b border-outline-variant/20 pb-2">
                                    <span class="text-xs font-bold text-primary flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[16px]">history_edu</span>
                                        Pendidikan #{{ index + 1 }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="removeEducationRow(index)"
                                        class="text-error hover:bg-error/10 px-2 py-1 rounded-md text-xs font-semibold flex items-center gap-1 transition-colors cursor-pointer"
                                        title="Hapus baris ini"
                                    >
                                        <span class="material-symbols-outlined text-[16px]">delete</span>
                                        <span>Hapus</span>
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Jenjang</label>
                                        <select
                                            v-model="edu.jenjang_pendidikan"
                                            class="w-full px-3 py-2 bg-surface-container-lowest border border-outline-variant/60 rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary cursor-pointer"
                                        >
                                            <option value="">-- Pilih --</option>
                                            <option value="SD">SD / Sederajat</option>
                                            <option value="SMP">SMP / MTs</option>
                                            <option value="SMA">SMA / SMK / MA</option>
                                            <option value="D1">D1</option>
                                            <option value="D2">D2</option>
                                            <option value="D3">D3</option>
                                            <option value="D4">D4</option>
                                            <option value="S1">S1 (Sarjana)</option>
                                            <option value="S2">S2 (Magister)</option>
                                            <option value="S3">S3 (Doktor)</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Jurusan</label>
                                        <input
                                            v-model="edu.jurusan"
                                            type="text"
                                            placeholder="Contoh: Pendidikan Matematika"
                                            class="w-full px-3 py-2 bg-surface-container-lowest border border-outline-variant/60 rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Instansi / Universitas</label>
                                        <input
                                            v-model="edu.instansi_pendidikan"
                                            type="text"
                                            placeholder="Contoh: UNS Surakarta"
                                            class="w-full px-3 py-2 bg-surface-container-lowest border border-outline-variant/60 rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Tahun Lulus</label>
                                        <input
                                            v-model="edu.tahun_lulus"
                                            type="text"
                                            placeholder="Contoh: 2018"
                                            class="w-full px-3 py-2 bg-surface-container-lowest border border-outline-variant/60 rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary"
                                        />
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Pembiayaan</label>
                                        <input
                                            v-model="edu.pembiayaan"
                                            type="text"
                                            placeholder="Contoh: Mandiri / Beasiswa / Dinas"
                                            class="w-full px-3 py-2 bg-surface-container-lowest border border-outline-variant/60 rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- TAB 7: DOKUMEN (PRIVATE STORAGE - OPSI A) -->
                <div v-if="activeTab === 'dokumen'" class="space-y-8">
                    <!-- Banner Informasi Keamanan Dokumen -->
                    <div class="p-5 sm:p-6 rounded-2xl bg-gradient-to-r from-primary/10 via-surface-container-high to-surface-container-low border border-primary/20">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="flex items-start sm:items-center gap-3.5">
                                <div class="w-12 h-12 rounded-xl bg-primary text-on-primary flex items-center justify-center shrink-0 shadow-md">
                                    <span class="material-symbols-outlined text-2xl">folder_shared</span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-base sm:text-lg font-bold text-on-surface">Dokumen Digital Pegawai</h3>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                            Penyimpanan Terproteksi (Opsi A)
                                        </span>
                                    </div>
                                    <p class="text-xs text-on-surface-variant mt-1 leading-relaxed">
                                        Dokumen tersimpan aman di direktori privat sistem (tidak dapat diakses publik secara langsung). Akses unduh dan pratinjau diautentikasi ketat.
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="px-3.5 py-2 rounded-xl bg-surface-container-lowest border border-outline-variant/40 text-xs font-bold text-primary flex items-center gap-2 shadow-sm">
                                    <span class="material-symbols-outlined text-[18px]">verified_user</span>
                                    <span>{{ employee.all_documents?.length || 0 }} Berkas Terunggah</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 1. DOKUMEN POKOK (KTP, KK, SK PENGANGKATAN) -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-on-surface flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-[20px]">badge</span>
                                    Dokumen Pokok Pegawai
                                </h4>
                                <span class="text-xs text-on-surface-variant">Dokumen identitas dasar dan SK awal pengangkatan</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- KTP Card -->
                            <div class="p-4 rounded-2xl border bg-surface-container-low transition-all duration-200"
                                :class="employee.dokumen_ktp ? 'border-primary/30 shadow-sm' : 'border-outline-variant/40 border-dashed'">
                                <div class="flex items-start justify-between gap-2 mb-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                                            :class="employee.dokumen_ktp ? 'bg-primary/10 text-primary' : 'bg-outline-variant/15 text-outline'">
                                            <span class="material-symbols-outlined text-[20px]">badge</span>
                                        </div>
                                        <div>
                                            <h5 class="text-xs font-bold text-on-surface">KTP (Kartu Tanda Penduduk)</h5>
                                            <p class="text-[11px] text-on-surface-variant font-mono">{{ employee.nik || 'NIK belum diisi' }}</p>
                                        </div>
                                    </div>
                                    <span v-if="employee.dokumen_ktp" class="material-symbols-outlined text-emerald-600 text-[18px]">check_circle</span>
                                </div>

                                <div v-if="employee.dokumen_ktp" class="space-y-3 pt-2 border-t border-outline-variant/20">
                                    <div class="flex items-center justify-between text-[11px] text-on-surface-variant">
                                        <span class="truncate max-w-[170px]" :title="employee.dokumen_ktp.file_name">{{ employee.dokumen_ktp.file_name }}</span>
                                        <span class="font-semibold text-outline">{{ employee.dokumen_ktp.formatted_file_size }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="openPreview(employee.dokumen_ktp)"
                                            class="flex-1 inline-flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                            <span>Lihat</span>
                                        </button>
                                        <a
                                            :href="employee.dokumen_ktp.download_url"
                                            class="p-1.5 rounded-lg bg-surface-container-high hover:bg-surface-container-highest text-on-surface transition-colors cursor-pointer"
                                            title="Unduh KTP"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">download</span>
                                        </a>
                                        <button
                                            type="button"
                                            @click="openUploadModal({ category: 'ktp', title: `KTP - ${employee.nama}` })"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-primary hover:bg-slate-100 transition-colors cursor-pointer"
                                            title="Ganti Berkas KTP"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">sync</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteDocument(employee.dokumen_ktp)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-error hover:bg-error/10 transition-colors cursor-pointer"
                                            title="Hapus KTP"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                        </button>
                                    </div>
                                </div>

                                <div v-else class="text-center py-4">
                                    <p class="text-[11px] text-on-surface-variant mb-2.5">Belum ada berkas KTP yang diunggah</p>
                                    <button
                                        type="button"
                                        @click="openUploadModal({ category: 'ktp', title: `KTP - ${employee.nama}` })"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-primary/10 text-primary hover:bg-primary/20 text-xs font-bold transition-all cursor-pointer"
                                    >
                                        <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                        <span>Unggah KTP</span>
                                    </button>
                                </div>
                            </div>

                            <!-- KK Card -->
                            <div class="p-4 rounded-2xl border bg-surface-container-low transition-all duration-200"
                                :class="employee.dokumen_kk ? 'border-primary/30 shadow-sm' : 'border-outline-variant/40 border-dashed'">
                                <div class="flex items-start justify-between gap-2 mb-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                                            :class="employee.dokumen_kk ? 'bg-primary/10 text-primary' : 'bg-outline-variant/15 text-outline'">
                                            <span class="material-symbols-outlined text-[20px]">family_restroom</span>
                                        </div>
                                        <div>
                                            <h5 class="text-xs font-bold text-on-surface">KK (Kartu Keluarga)</h5>
                                            <p class="text-[11px] text-on-surface-variant">Dokumen resmi keluarga</p>
                                        </div>
                                    </div>
                                    <span v-if="employee.dokumen_kk" class="material-symbols-outlined text-emerald-600 text-[18px]">check_circle</span>
                                </div>

                                <div v-if="employee.dokumen_kk" class="space-y-3 pt-2 border-t border-outline-variant/20">
                                    <div class="flex items-center justify-between text-[11px] text-on-surface-variant">
                                        <span class="truncate max-w-[170px]" :title="employee.dokumen_kk.file_name">{{ employee.dokumen_kk.file_name }}</span>
                                        <span class="font-semibold text-outline">{{ employee.dokumen_kk.formatted_file_size }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="openPreview(employee.dokumen_kk)"
                                            class="flex-1 inline-flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                            <span>Lihat</span>
                                        </button>
                                        <a
                                            :href="employee.dokumen_kk.download_url"
                                            class="p-1.5 rounded-lg bg-surface-container-high hover:bg-surface-container-highest text-on-surface transition-colors cursor-pointer"
                                            title="Unduh KK"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">download</span>
                                        </a>
                                        <button
                                            type="button"
                                            @click="openUploadModal({ category: 'kk', title: `KK - ${employee.nama}` })"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-primary hover:bg-slate-100 transition-colors cursor-pointer"
                                            title="Ganti Berkas KK"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">sync</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteDocument(employee.dokumen_kk)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-error hover:bg-error/10 transition-colors cursor-pointer"
                                            title="Hapus KK"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                        </button>
                                    </div>
                                </div>

                                <div v-else class="text-center py-4">
                                    <p class="text-[11px] text-on-surface-variant mb-2.5">Belum ada berkas KK yang diunggah</p>
                                    <button
                                        type="button"
                                        @click="openUploadModal({ category: 'kk', title: `KK - ${employee.nama}` })"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-primary/10 text-primary hover:bg-primary/20 text-xs font-bold transition-all cursor-pointer"
                                    >
                                        <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                        <span>Unggah KK</span>
                                    </button>
                                </div>
                            </div>

                            <!-- SK Pengangkatan Awal Card -->
                            <div class="p-4 rounded-2xl border bg-surface-container-low transition-all duration-200"
                                :class="employee.dokumen_sk_pengangkatan ? 'border-primary/30 shadow-sm' : 'border-outline-variant/40 border-dashed'">
                                <div class="flex items-start justify-between gap-2 mb-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                                            :class="employee.dokumen_sk_pengangkatan ? 'bg-primary/10 text-primary' : 'bg-outline-variant/15 text-outline'">
                                            <span class="material-symbols-outlined text-[20px]">workspace_premium</span>
                                        </div>
                                        <div>
                                            <h5 class="text-xs font-bold text-on-surface">SK Pengangkatan Awal</h5>
                                            <p class="text-[11px] text-on-surface-variant">TMT: {{ employee.formatted_tmt_pengangkatan || formatTanggalIndo(employee.tmt_pengangkatan) || '-' }}</p>
                                        </div>
                                    </div>
                                    <span v-if="employee.dokumen_sk_pengangkatan" class="material-symbols-outlined text-emerald-600 text-[18px]">check_circle</span>
                                </div>

                                <div v-if="employee.dokumen_sk_pengangkatan" class="space-y-3 pt-2 border-t border-outline-variant/20">
                                    <div class="flex items-center justify-between text-[11px] text-on-surface-variant">
                                        <span class="truncate max-w-[170px]" :title="employee.dokumen_sk_pengangkatan.file_name">{{ employee.dokumen_sk_pengangkatan.file_name }}</span>
                                        <span class="font-semibold text-outline">{{ employee.dokumen_sk_pengangkatan.formatted_file_size }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="openPreview(employee.dokumen_sk_pengangkatan)"
                                            class="flex-1 inline-flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                            <span>Lihat</span>
                                        </button>
                                        <a
                                            :href="employee.dokumen_sk_pengangkatan.download_url"
                                            class="p-1.5 rounded-lg bg-surface-container-high hover:bg-surface-container-highest text-on-surface transition-colors cursor-pointer"
                                            title="Unduh SK Pengangkatan"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">download</span>
                                        </a>
                                        <button
                                            type="button"
                                            @click="openUploadModal({ category: 'sk_pengangkatan', title: `SK Pengangkatan - ${employee.nama}` })"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-primary hover:bg-slate-100 transition-colors cursor-pointer"
                                            title="Ganti Berkas SK Pengangkatan"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">sync</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteDocument(employee.dokumen_sk_pengangkatan)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-error hover:bg-error/10 transition-colors cursor-pointer"
                                            title="Hapus SK Pengangkatan"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                        </button>
                                    </div>
                                </div>

                                <div v-else class="text-center py-4">
                                    <p class="text-[11px] text-on-surface-variant mb-2.5">Belum ada SK Pengangkatan</p>
                                    <button
                                        type="button"
                                        @click="openUploadModal({ category: 'sk_pengangkatan', title: `SK Pengangkatan - ${employee.nama}` })"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-primary/10 text-primary hover:bg-primary/20 text-xs font-bold transition-all cursor-pointer"
                                    >
                                        <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                        <span>Unggah SK</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. IJAZAH KULIAH / PENDIDIKAN (MULTI DOKUMEN: S1, S2, DLL) -->
                    <div class="space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <h4 class="text-sm font-bold text-on-surface flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-[20px]">school</span>
                                    Berkas Ijazah Kuliah & Pendidikan
                                </h4>
                                <p class="text-xs text-on-surface-variant">
                                    Mendukung upload lebih dari 1 ijazah secara terpisah (S1, S2, program profesi, dll)
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="openUploadModal({ category: 'ijazah', recordType: null, recordId: null, title: `Ijazah Tambahan - ${employee.nama}` })"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-primary text-on-primary hover:bg-primary-container text-xs font-bold transition-all shadow-sm cursor-pointer self-start sm:self-auto"
                            >
                                <span class="material-symbols-outlined text-[16px]">add_circle</span>
                                <span>+ Upload Ijazah Tambahan</span>
                            </button>
                        </div>

                        <!-- Ijazah Formal Terkait Riwayat Pendidikan -->
                        <div class="space-y-3">
                            <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider block">
                                Ijazah dari Riwayat Pendidikan Formal
                            </span>
                            <div v-if="employee.riwayat_pendidikan && employee.riwayat_pendidikan.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <div
                                    v-for="edu in employee.riwayat_pendidikan"
                                    :key="edu.id"
                                    class="p-4 rounded-xl border bg-surface-container-low transition-all"
                                    :class="edu.document ? 'border-primary/30' : 'border-outline-variant/30'"
                                >
                                    <div class="flex items-start justify-between gap-3 mb-2.5">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-primary/10 text-primary">
                                                    {{ edu.jenjang_pendidikan || '-' }}
                                                </span>
                                                <span class="text-xs font-bold text-on-surface">{{ edu.jurusan || 'Tanpa Jurusan' }}</span>
                                            </div>
                                            <p class="text-[11px] text-on-surface-variant mt-1">
                                                {{ edu.instansi_pendidikan || '-' }} • Lulus {{ edu.tahun_lulus || '-' }}
                                            </p>
                                        </div>
                                        <span v-if="edu.document" class="material-symbols-outlined text-emerald-600 text-[18px]">check_circle</span>
                                    </div>

                                    <div v-if="edu.document" class="pt-2 border-t border-outline-variant/20 flex items-center justify-between gap-2">
                                        <div class="text-[11px] text-on-surface-variant truncate max-w-[180px]" :title="edu.document.file_name">
                                            <span class="font-medium text-on-surface">{{ edu.document.file_name }}</span>
                                            <span class="text-outline block text-[10px]">{{ edu.document.formatted_file_size }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <button
                                                type="button"
                                                @click="openPreview(edu.document)"
                                                class="px-2 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                title="Lihat Ijazah"
                                            >
                                                Lihat
                                            </button>
                                            <a
                                                :href="edu.document.download_url"
                                                class="p-1 rounded-lg bg-surface-container-high hover:bg-surface-container-highest text-on-surface transition-colors cursor-pointer"
                                                title="Unduh Ijazah"
                                            >
                                                <span class="material-symbols-outlined text-[15px]">download</span>
                                            </a>
                                            <button
                                                type="button"
                                                @click="openUploadModal({ category: 'ijazah', recordType: 'education', recordId: edu.id, title: `Ijazah ${edu.jenjang_pendidikan} - ${edu.jurusan || ''}` })"
                                                class="p-1 rounded-lg text-slate-500 hover:text-primary hover:bg-slate-100 transition-colors cursor-pointer"
                                                title="Ganti Berkas Ijazah"
                                            >
                                                <span class="material-symbols-outlined text-[15px]">sync</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="deleteDocument(edu.document)"
                                                class="p-1 rounded-lg text-slate-400 hover:text-error hover:bg-error/10 transition-colors cursor-pointer"
                                                title="Hapus Berkas Ijazah"
                                            >
                                                <span class="material-symbols-outlined text-[15px]">delete</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div v-else class="pt-2 border-t border-outline-variant/20 flex items-center justify-between">
                                        <span class="text-[11px] text-on-surface-variant italic">Belum ada berkas ijazah</span>
                                        <button
                                            type="button"
                                            @click="openUploadModal({ category: 'ijazah', recordType: 'education', recordId: edu.id, title: `Ijazah ${edu.jenjang_pendidikan} - ${edu.jurusan || ''}` })"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-primary/10 text-primary hover:bg-primary/20 transition-all cursor-pointer"
                                        >
                                            <span class="material-symbols-outlined text-[14px]">upload_file</span>
                                            <span>Upload</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="p-6 text-center border-2 border-dashed border-outline-variant/30 rounded-xl">
                                <p class="text-xs text-on-surface-variant">Belum ada data riwayat pendidikan formal.</p>
                            </div>
                        </div>

                        <!-- Ijazah Tambahan Lainnya (Opsional S2, Sertifikat Gelar, dll) -->
                        <div v-if="employee.dokumen_ijazah_tambahan && employee.dokumen_ijazah_tambahan.length > 0" class="space-y-3 pt-2">
                            <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider block">
                                Ijazah Tambahan Lainnya ({{ employee.dokumen_ijazah_tambahan.length }})
                            </span>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <div
                                    v-for="doc in employee.dokumen_ijazah_tambahan"
                                    :key="doc.id"
                                    class="p-4 rounded-xl border border-primary/30 bg-surface-container-low transition-all"
                                >
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <div>
                                            <h5 class="text-xs font-bold text-on-surface">{{ doc.title }}</h5>
                                            <p class="text-[11px] text-on-surface-variant font-mono mt-0.5">{{ doc.file_name }}</p>
                                        </div>
                                        <span class="text-[10px] font-semibold text-outline">{{ doc.formatted_file_size }}</span>
                                    </div>
                                    <div class="pt-2 border-t border-outline-variant/20 flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            @click="openPreview(doc)"
                                            class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                        >
                                            Lihat
                                        </button>
                                        <a
                                            :href="doc.download_url"
                                            class="p-1 rounded-lg bg-surface-container-high hover:bg-surface-container-highest text-on-surface transition-colors cursor-pointer"
                                            title="Unduh"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">download</span>
                                        </a>
                                        <button
                                            type="button"
                                            @click="deleteDocument(doc)"
                                            class="p-1 rounded-lg text-slate-400 hover:text-error hover:bg-error/10 transition-colors cursor-pointer"
                                            title="Hapus"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">delete</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. SK JENJANG KEPEGAWAIAN (TERHUBUNG KE RIWAYAT JENJANG) -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-on-surface flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-[20px]">military_tech</span>
                                    SK Jenjang Kepegawaian (Terkoneksi Riwayat Kepegawaian)
                                </h4>
                                <p class="text-xs text-on-surface-variant">
                                    Daftar otomatis diambil dari Tabel Riwayat Kepegawaian (GTY, GTT, PTY, PTT). Berkas otomatis tersinkron saat riwayat diubah.
                                </p>
                            </div>
                            <span class="text-xs text-outline font-medium">
                                {{ employee.riwayat_kepegawaian?.length || 0 }} Jenjang
                            </span>
                        </div>

                        <div v-if="employee.riwayat_kepegawaian && employee.riwayat_kepegawaian.length > 0">
                            <div class="overflow-x-auto rounded-xl border border-outline-variant/30 bg-surface-container-low">
                                <table class="w-full text-xs sm:text-sm">
                                    <thead>
                                        <tr class="bg-surface-container-low border-b border-outline-variant/30 text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                                            <th class="px-4 py-3 text-left">No</th>
                                            <th class="px-4 py-3 text-left">Jenjang</th>
                                            <th class="px-4 py-3 text-left">TMT Kenaikan</th>
                                            <th class="px-4 py-3 text-left">TST Kenaikan</th>
                                            <th class="px-4 py-3 text-left">Status Berkas</th>
                                            <th class="px-4 py-3 text-center">Aksi Dokumen</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-outline-variant/20">
                                        <tr
                                            v-for="(record, idx) in employee.riwayat_kepegawaian"
                                            :key="record.id || idx"
                                            class="hover:bg-surface-container-lowest/50 transition-colors"
                                        >
                                            <td class="px-4 py-3 text-on-surface-variant font-mono">{{ idx + 1 }}</td>
                                            <td class="px-4 py-3 font-semibold text-on-surface">
                                                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold', getJenjangColor(record.jenjang_kepegawaian)]">
                                                    {{ record.jenjang_kepegawaian || '-' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-on-surface">
                                                {{ record.formatted_tmt_kenaikan_jenjang || formatTanggalIndo(record.tmt_kenaikan_jenjang) || '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-on-surface">
                                                {{ record.formatted_tst_kenaikan_jenjang || record.formatted_tst_jenjang || formatTanggalIndo(record.tst_kenaikan_jenjang || record.tst_jenjang) || 'Sekarang / Tetap' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <span v-if="record.document" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                    Terunggah ({{ record.document.formatted_file_size }})
                                                </span>
                                                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                                    Belum Ada Berkas
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <div v-if="record.document" class="inline-flex items-center gap-1.5">
                                                    <button
                                                        type="button"
                                                        @click="openPreview(record.document)"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                        title="Lihat / Preview SK Jenjang"
                                                    >
                                                        <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                        <span>Lihat</span>
                                                    </button>
                                                    <a
                                                        :href="record.document.download_url"
                                                        class="p-1 rounded-lg bg-surface-container-high hover:bg-surface-container-highest text-on-surface transition-colors cursor-pointer"
                                                        title="Unduh SK"
                                                    >
                                                        <span class="material-symbols-outlined text-[15px]">download</span>
                                                    </a>
                                                    <button
                                                        type="button"
                                                        @click="openUploadModal({ category: 'sk_jenjang', recordType: 'record', recordId: record.id, title: `SK Jenjang ${record.jenjang_kepegawaian}` })"
                                                        class="p-1 rounded-lg text-slate-500 hover:text-primary hover:bg-slate-100 transition-colors cursor-pointer"
                                                        title="Ganti Berkas SK"
                                                    >
                                                        <span class="material-symbols-outlined text-[15px]">sync</span>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="deleteDocument(record.document)"
                                                        class="p-1 rounded-lg text-slate-400 hover:text-error hover:bg-error/10 transition-colors cursor-pointer"
                                                        title="Hapus SK"
                                                    >
                                                        <span class="material-symbols-outlined text-[15px]">delete</span>
                                                    </button>
                                                </div>
                                                <button
                                                    v-else
                                                    type="button"
                                                    @click="openUploadModal({ category: 'sk_jenjang', recordType: 'record', recordId: record.id, title: `SK Jenjang ${record.jenjang_kepegawaian}` })"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-primary/10 text-primary hover:bg-primary/20 transition-colors cursor-pointer"
                                                    title="Upload SK Jenjang"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                                    <span>Upload SK</span>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div v-else class="py-8 text-center border-2 border-dashed border-outline-variant/30 rounded-2xl">
                            <span class="material-symbols-outlined text-3xl text-outline/60 block mb-1">work_history</span>
                            <p class="text-xs text-on-surface-variant">Belum ada riwayat jenjang kepegawaian. Tambahkan di Tab Kepegawaian.</p>
                        </div>
                    </div>

                    <!-- 4. SK PENUGASAN (TERHUBUNG KE RIWAYAT JABATAN) -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-on-surface flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-[20px]">assignment_turned_in</span>
                                    SK Penugasan / Jabatan (Terkoneksi Riwayat Jabatan)
                                </h4>
                                <p class="text-xs text-on-surface-variant">
                                    Daftar otomatis diambil dari Tabel Riwayat Jabatan (Sheet 4). Berkas otomatis tersinkron saat riwayat diubah.
                                </p>
                            </div>
                            <span class="text-xs text-outline font-medium">
                                {{ employee.riwayat_jabatan?.length || 0 }} Jabatan
                            </span>
                        </div>

                        <div v-if="employee.riwayat_jabatan && employee.riwayat_jabatan.length > 0">
                            <div class="overflow-x-auto rounded-xl border border-outline-variant/30 bg-surface-container-low">
                                <table class="w-full text-xs sm:text-sm">
                                    <thead>
                                        <tr class="bg-surface-container-low border-b border-outline-variant/30 text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                                            <th class="px-4 py-3 text-left">No</th>
                                            <th class="px-4 py-3 text-left">Jabatan</th>
                                            <th class="px-4 py-3 text-left">Unit Kerja</th>
                                            <th class="px-4 py-3 text-left">TMT Jabatan</th>
                                            <th class="px-4 py-3 text-left">TST Jabatan</th>
                                            <th class="px-4 py-3 text-left">Status Berkas</th>
                                            <th class="px-4 py-3 text-center">Aksi Dokumen</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-outline-variant/20">
                                        <tr
                                            v-for="(pos, idx) in employee.riwayat_jabatan"
                                            :key="pos.id || idx"
                                            class="hover:bg-surface-container-lowest/50 transition-colors"
                                        >
                                            <td class="px-4 py-3 text-on-surface-variant font-mono">{{ idx + 1 }}</td>
                                            <td class="px-4 py-3 font-bold text-on-surface">{{ pos.jabatan || '-' }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-primary/10 text-primary">
                                                    {{ pos.unit_kerja || '-' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-on-surface">
                                                {{ pos.formatted_tmt_jabatan || formatTanggalIndo(pos.tmt_jabatan) || '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-on-surface">
                                                {{ pos.formatted_tst_jabatan || formatTanggalIndo(pos.tst_jabatan) || 'Sekarang / Aktif' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <span v-if="pos.document" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                    Terunggah ({{ pos.document.formatted_file_size }})
                                                </span>
                                                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                                    Belum Ada Berkas
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <div v-if="pos.document" class="inline-flex items-center gap-1.5">
                                                    <button
                                                        type="button"
                                                        @click="openPreview(pos.document)"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                        title="Lihat / Preview SK Penugasan"
                                                    >
                                                        <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                        <span>Lihat</span>
                                                    </button>
                                                    <a
                                                        :href="pos.document.download_url"
                                                        class="p-1 rounded-lg bg-surface-container-high hover:bg-surface-container-highest text-on-surface transition-colors cursor-pointer"
                                                        title="Unduh SK"
                                                    >
                                                        <span class="material-symbols-outlined text-[15px]">download</span>
                                                    </a>
                                                    <button
                                                        type="button"
                                                        @click="openUploadModal({ category: 'sk_penugasan', recordType: 'position', recordId: pos.id, title: `SK Penugasan ${pos.jabatan} (${pos.unit_kerja})` })"
                                                        class="p-1 rounded-lg text-slate-500 hover:text-primary hover:bg-slate-100 transition-colors cursor-pointer"
                                                        title="Ganti Berkas SK"
                                                    >
                                                        <span class="material-symbols-outlined text-[15px]">sync</span>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="deleteDocument(pos.document)"
                                                        class="p-1 rounded-lg text-slate-400 hover:text-error hover:bg-error/10 transition-colors cursor-pointer"
                                                        title="Hapus SK"
                                                    >
                                                        <span class="material-symbols-outlined text-[15px]">delete</span>
                                                    </button>
                                                </div>
                                                <button
                                                    v-else
                                                    type="button"
                                                    @click="openUploadModal({ category: 'sk_penugasan', recordType: 'position', recordId: pos.id, title: `SK Penugasan ${pos.jabatan} (${pos.unit_kerja})` })"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-primary/10 text-primary hover:bg-primary/20 transition-colors cursor-pointer"
                                                    title="Upload SK Penugasan"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                                    <span>Upload SK</span>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div v-else class="py-8 text-center border-2 border-dashed border-outline-variant/30 rounded-2xl">
                            <span class="material-symbols-outlined text-3xl text-outline/60 block mb-1">assignment_late</span>
                            <p class="text-xs text-on-surface-variant">Belum ada riwayat jabatan / penugasan. Tambahkan di Tab Penugasan.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Floating Action Button when in Edit Mode -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-8 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-8 scale-95"
        >
            <div
                v-if="isEditing"
                class="fixed bottom-6 right-6 sm:bottom-8 sm:right-8 z-50 flex items-center gap-2 bg-surface-container-lowest/95 backdrop-blur-md p-2 sm:p-2.5 rounded-2xl border border-primary/30 shadow-[0_12px_36px_rgba(0,0,0,0.22)] ring-1 ring-black/5"
            >
                <button
                    type="button"
                    @click="cancelEdit"
                    class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high border border-outline-variant/60 transition-all cursor-pointer"
                    :disabled="form.processing"
                    title="Batalkan perubahan"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="saveChanges"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-bold text-xs sm:text-sm transition-all shadow-md hover:shadow-lg active:scale-95 cursor-pointer disabled:opacity-60"
                    :disabled="form.processing"
                    title="Simpan semua perubahan data pegawai"
                >
                    <span v-if="form.processing" class="material-symbols-outlined text-[18px] animate-spin">sync</span>
                    <span v-else class="material-symbols-outlined text-[18px]">save</span>
                    <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                </button>
            </div>
        </Transition>

        <!-- MODAL UPLOAD DOKUMEN -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showUploadModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="closeUploadModal"
            >
                <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-5 border-b border-outline-variant/20 bg-surface-container-low/50">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">upload_file</span>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-on-surface">Unggah Dokumen Pegawai</h3>
                                <p class="text-xs text-on-surface-variant truncate max-w-[280px] sm:max-w-xs">{{ uploadTitle || 'Pilih berkas untuk diunggah' }}</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="closeUploadModal"
                            class="p-1.5 rounded-lg text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high transition-colors cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <!-- Modal Form -->
                    <form @submit.prevent="submitDocument" class="p-5 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Judul Dokumen</label>
                            <input
                                v-model="uploadTitle"
                                type="text"
                                placeholder="Contoh: KTP Pegawai, SK Pengangkatan, Ijazah S1"
                                class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                required
                            />
                        </div>

                        <!-- Dropzone -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Berkas Dokumen</label>
                            <div
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="handleDrop"
                                :class="[
                                    'border-2 border-dashed rounded-2xl p-6 text-center transition-all cursor-pointer relative',
                                    isDragging ? 'border-primary bg-primary/5' : 'border-outline-variant/60 hover:border-primary/50 bg-surface-container-low/30',
                                    uploadFile ? 'border-emerald-500 bg-emerald-50/20' : ''
                                ]"
                                @click="fileInput?.click()"
                            >
                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept=".pdf,image/jpeg,image/png,image/webp"
                                    class="hidden"
                                    @change="handleFileChange"
                                />

                                <div v-if="uploadFile" class="flex flex-col items-center">
                                    <span class="material-symbols-outlined text-4xl text-emerald-600 mb-2">task_alt</span>
                                    <span class="text-xs font-bold text-on-surface max-w-full truncate px-2">{{ uploadFile.name }}</span>
                                    <span class="text-[11px] text-on-surface-variant mt-0.5">{{ formatBytes(uploadFile.size) }}</span>
                                    <span class="mt-2 text-[11px] text-primary font-semibold hover:underline">Klik untuk mengganti berkas</span>
                                </div>
                                <div v-else class="flex flex-col items-center">
                                    <span class="material-symbols-outlined text-4xl text-primary/70 mb-2">cloud_upload</span>
                                    <p class="text-xs font-bold text-on-surface">Klik atau seret berkas ke sini</p>
                                    <p class="text-[11px] text-on-surface-variant mt-1">Format didukung: PDF, JPG, PNG, WEBP (Maksimal 10 MB)</p>
                                </div>
                            </div>
                            <p v-if="uploadFileError" class="mt-1.5 text-xs text-error font-medium flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">error</span>
                                {{ uploadFileError }}
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-outline-variant/20">
                            <button
                                type="button"
                                @click="closeUploadModal"
                                class="px-4 py-2 rounded-xl text-xs font-semibold text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high transition-colors cursor-pointer"
                                :disabled="isUploading"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="isUploading || !uploadFile"
                                class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-bold text-xs shadow-md transition-all disabled:opacity-50 cursor-pointer"
                            >
                                <span v-if="isUploading" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                <span v-else class="material-symbols-outlined text-[16px]">upload</span>
                                <span>{{ isUploading ? 'Mengunggah...' : 'Unggah Sekarang' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

        <!-- MODAL PREVIEW DOKUMEN -->
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
                <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 shadow-2xl w-full max-w-4xl h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-4 border-b border-outline-variant/20 bg-surface-container-low shrink-0">
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
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-surface-container-high hover:bg-surface-container-highest text-on-surface transition-colors cursor-pointer"
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
                                class="p-1.5 rounded-lg text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high transition-colors ml-1 cursor-pointer"
                                title="Tutup Preview"
                            >
                                <span class="material-symbols-outlined text-[20px]">close</span>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Content (PDF or Image viewer) -->
                    <div class="flex-1 bg-surface-container-lowest overflow-auto p-4 flex items-center justify-center">
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
    </div>
</template>
