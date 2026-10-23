<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, usePage, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EditDataButton from '@/Components/Shared/EditDataButton.vue';

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

const dokumenPribadiList = computed(() => {
    const list = [];
    let no = 1;

    // 1. KTP
    list.push({
        id: 'ktp',
        no: no++,
        nama: 'KTP (Kartu Tanda Penduduk)',
        keterangan: props.employee.nik ? `NIK: ${props.employee.nik}` : 'NIK belum diisi',
        document: props.employee.dokumen_ktp,
        uploadCategory: 'ktp',
        recordType: null,
        recordId: null,
        uploadTitle: `KTP - ${props.employee.nama}`,
        isAdditional: false,
    });

    // 2. KK
    list.push({
        id: 'kk',
        no: no++,
        nama: 'KK (Kartu Keluarga)',
        keterangan: 'Dokumen resmi Kartu Keluarga',
        document: props.employee.dokumen_kk,
        uploadCategory: 'kk',
        recordType: null,
        recordId: null,
        uploadTitle: `KK - ${props.employee.nama}`,
        isAdditional: false,
    });

    return list;
});

const dokumenKepegawaianList = computed(() => {
    const list = [];
    let no = 1;

    // 1. SK Pengangkatan Awal
    const tmtAwal = props.employee.formatted_tmt_pengangkatan || formatTanggalIndo(props.employee.tmt_pengangkatan) || '-';
    const tstAwal = props.employee.formatted_tst_pengangkatan || formatTanggalIndo(props.employee.tst_pengangkatan) || 'Tetap';
    list.push({
        id: 'sk_pengangkatan',
        no: no++,
        nama: 'SK Pengangkatan Awal',
        keterangan: `TMT: ${tmtAwal} • TST: ${tstAwal}`,
        document: props.employee.dokumen_sk_pengangkatan,
        uploadCategory: 'sk_pengangkatan',
        recordType: null,
        recordId: null,
        uploadTitle: `SK Pengangkatan - ${props.employee.nama}`,
        isAdditional: false,
    });

    // 2. SK Jenjang Kepegawaian (Terkoneksi Tab Kepegawaian)
    if (props.employee.riwayat_kepegawaian && props.employee.riwayat_kepegawaian.length > 0) {
        props.employee.riwayat_kepegawaian.forEach((rec) => {
            const jenjang = rec.jenjang_kepegawaian || '-';
            const tmt = rec.formatted_tmt_kenaikan_jenjang || formatTanggalIndo(rec.tmt_kenaikan_jenjang) || '-';
            const tst = rec.formatted_tst_kenaikan_jenjang || rec.formatted_tst_jenjang || formatTanggalIndo(rec.tst_kenaikan_jenjang || rec.tst_jenjang) || 'Sekarang / Tetap';

            list.push({
                id: `rec_${rec.id}`,
                no: no++,
                nama: `SK Jenjang ${jenjang}`,
                keterangan: `TMT: ${tmt} • TST: ${tst}`,
                document: rec.document,
                uploadCategory: 'sk_jenjang',
                recordType: 'record',
                recordId: rec.id,
                uploadTitle: `SK Jenjang ${jenjang}`,
                isAdditional: false,
            });
        });
    }

    return list;
});

const dokumenJabatanList = computed(() => {
    const list = [];
    let no = 1;

    // Riwayat Jabatan (Terkoneksi Tab Penugasan / Jabatan)
    if (props.employee.riwayat_jabatan && props.employee.riwayat_jabatan.length > 0) {
        props.employee.riwayat_jabatan.forEach((pos) => {
            const jabatan = pos.jabatan || '-';
            const unit = pos.unit_kerja ? `Unit: ${pos.unit_kerja}` : '';
            const jenis = pos.jenis_kepegawaian ? `${pos.jenis_kepegawaian}` : '';
            const tmt = pos.formatted_tmt_jabatan || formatTanggalIndo(pos.tmt_jabatan) || '-';
            const tst = pos.formatted_tst_jabatan || formatTanggalIndo(pos.tst_jabatan) || 'Sekarang / Aktif';

            const rincianParts = [];
            if (unit) rincianParts.push(unit);
            if (jenis) rincianParts.push(jenis);
            rincianParts.push(`TMT: ${tmt} • TST: ${tst}`);

            list.push({
                id: `pos_${pos.id}`,
                no: no++,
                nama: `SK Penugasan: ${jabatan}`,
                keterangan: rincianParts.join(' • '),
                document: pos.document,
                uploadCategory: 'sk_penugasan',
                recordType: 'position',
                recordId: pos.id,
                uploadTitle: `SK Penugasan ${jabatan}${pos.unit_kerja ? ' (' + pos.unit_kerja + ')' : ''}`,
                isAdditional: false,
            });
        });
    }

    return list;
});

const dokumenPendidikanList = computed(() => {
    const list = [];
    let no = 1;

    // 1. Ijazah Formal dari Riwayat Pendidikan (Terkoneksi Tab Pendidikan)
    if (props.employee.riwayat_pendidikan && props.employee.riwayat_pendidikan.length > 0) {
        props.employee.riwayat_pendidikan.forEach((edu) => {
            const jenjang = edu.jenjang_pendidikan || 'Pendidikan';
            const jurusan = edu.jurusan ? ` - ${edu.jurusan}` : '';
            const instansi = edu.instansi_pendidikan || '-';
            const tahun = edu.tahun_lulus ? `Lulus ${edu.tahun_lulus}` : '';
            const biaya = edu.pembiayaan ? `Pembiayaan: ${edu.pembiayaan}` : '';

            const rincianParts = [instansi];
            if (tahun) rincianParts.push(tahun);
            if (biaya) rincianParts.push(biaya);

            list.push({
                id: `edu_${edu.id}`,
                no: no++,
                nama: `Ijazah ${jenjang}${jurusan}`,
                keterangan: rincianParts.join(' • '),
                document: edu.document,
                uploadCategory: 'ijazah',
                recordType: 'education',
                recordId: edu.id,
                uploadTitle: `Ijazah ${jenjang}${jurusan}`,
                isAdditional: false,
            });
        });
    }

    // 2. Ijazah / Sertifikat Tambahan Lainnya
    if (props.employee.dokumen_ijazah_tambahan && props.employee.dokumen_ijazah_tambahan.length > 0) {
        props.employee.dokumen_ijazah_tambahan.forEach((doc) => {
            list.push({
                id: `doc_${doc.id}`,
                no: no++,
                nama: doc.title || 'Ijazah Tambahan',
                keterangan: 'Dokumen pendidikan tambahan / sertifikat profesi',
                document: doc,
                uploadCategory: 'ijazah',
                recordType: null,
                recordId: null,
                uploadTitle: doc.title,
                isAdditional: true,
            });
        });
    }

    return list;
});

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
    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop bg-white">
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
        <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/70 flex flex-col overflow-hidden">
            <!-- Profile Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between p-5 sm:px-6 sm:py-5 border-b border-outline-variant/70 bg-white gap-4">
                <div class="flex items-center gap-4 sm:gap-5">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-white flex items-center justify-center overflow-hidden border-2 border-outline-variant/90 shadow-sm shrink-0">
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
                    <div class="space-y-2">
                        <h3 class="text-lg sm:text-xl font-bold text-on-surface leading-snug">{{ employee.nama_lengkap_gelar || employee.nama }}</h3>
                        <p class="text-xs sm:text-sm text-on-surface-variant font-medium">NIPY: {{ employee.nipy || '-' }}</p>
                        <div class="flex flex-wrap items-center gap-2 pt-0.5">
                            <span
                                v-if="employee.jenjang_kepegawaian"
                                :class="['inline-flex items-center px-2.5 py-0.5 rounded-md text-[10px] font-semibold border border-outline-variant/40 shadow-2xs', getJenjangColor(employee.jenjang_kepegawaian)]"
                            >
                                {{ employee.jenjang_kepegawaian }}
                            </span>
                            <span
                                v-if="employee.status_keaktifan"
                                :class="['inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold border border-outline-variant/40 shadow-2xs', getStatusColor(employee.status_keaktifan)]"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                {{ employee.status_keaktifan }}
                            </span>
                        </div>
                        <p v-if="employee.jabatan" class="text-xs sm:text-sm text-on-surface-variant font-medium pt-0.5">
                            {{ employee.jabatan }}{{ employee.unit_kerja ? ` · ${employee.unit_kerja}` : '' }}
                        </p>
                    </div>
                </div>

                <!-- Action Buttons in Header -->
                <div class="flex items-center gap-2 self-start sm:self-auto shrink-0">
                    <!-- View Mode: Edit Data Button (Reusable Component dari Database Siswa) -->
                    <EditDataButton
                        v-if="!isEditing"
                        @click="startEdit"
                        title="Edit data pegawai ini langsung di tab"
                    />

                    <!-- Edit Mode: Batal & Simpan Perubahan Buttons (Hijau Tema Web) -->
                    <div v-else class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="cancelEdit"
                            class="px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-on-surface-variant hover:text-on-surface hover:bg-slate-100 border border-outline-variant/60 transition-all cursor-pointer"
                            :disabled="form.processing"
                            title="Batalkan perubahan"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="saveChanges"
                            class="inline-flex items-center gap-2 px-4 py-2 sm:px-5 sm:py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-bold text-xs sm:text-sm transition-all shadow-xs hover:shadow-md active:scale-95 cursor-pointer disabled:opacity-60"
                            :disabled="form.processing"
                            title="Simpan semua perubahan data pegawai"
                        >
                            <span v-if="form.processing" class="material-symbols-outlined text-[18px] animate-spin">sync</span>
                            <span v-else class="material-symbols-outlined text-[18px]">save</span>
                            <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="flex border-b border-outline-variant/60 px-4 bg-white overflow-x-auto">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="[
                        'flex items-center gap-2 px-3 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm font-medium transition-colors whitespace-nowrap border-b-2 cursor-pointer',
                        activeTab === tab.id
                            ? 'text-primary border-primary bg-primary/5 font-semibold'
                            : 'text-on-surface-variant border-transparent hover:text-on-surface hover:bg-slate-50'
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
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
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
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
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
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
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
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all font-mono"
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
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all font-mono"
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
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
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
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
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
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
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
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
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
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
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
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
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
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
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
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
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
                        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-outline-variant/60 shadow-2xs space-y-4">
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
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">RT / RW</label>
                                    <input
                                        v-model="form.rt_rw_domisili"
                                        type="text"
                                        placeholder="001/002"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Dusun</label>
                                    <input
                                        v-model="form.dusun_domisili"
                                        type="text"
                                        placeholder="Nama dusun / kampung"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Desa / Kelurahan</label>
                                    <input
                                        v-model="form.desa_domisili"
                                        type="text"
                                        placeholder="Desa / Kelurahan"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Kecamatan</label>
                                    <input
                                        v-model="form.kecamatan_domisili"
                                        type="text"
                                        placeholder="Kecamatan"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Kabupaten / Kota</label>
                                    <input
                                        v-model="form.kab_domisili"
                                        type="text"
                                        placeholder="Kabupaten / Kota"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Provinsi</label>
                                    <input
                                        v-model="form.provinsi_domisili"
                                        type="text"
                                        placeholder="Provinsi"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Tinggal Form -->
                        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-outline-variant/60 shadow-2xs space-y-4">
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
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">RT / RW</label>
                                    <input
                                        v-model="form.rt_rw_tinggal"
                                        type="text"
                                        placeholder="001/002"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Dusun</label>
                                    <input
                                        v-model="form.dusun_tinggal"
                                        type="text"
                                        placeholder="Nama dusun / kampung"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Desa / Kelurahan</label>
                                    <input
                                        v-model="form.desa_tinggal"
                                        type="text"
                                        placeholder="Desa / Kelurahan"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Kecamatan</label>
                                    <input
                                        v-model="form.kecamatan_tinggal"
                                        type="text"
                                        placeholder="Kecamatan"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Kabupaten / Kota</label>
                                    <input
                                        v-model="form.kabupaten_tinggal"
                                        type="text"
                                        placeholder="Kabupaten / Kota"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Provinsi</label>
                                    <input
                                        v-model="form.provinsi_tinggal"
                                        type="text"
                                        placeholder="Provinsi"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Status Kepemilikan Rumah</label>
                                    <select
                                        v-model="form.status_rumah"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
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
                        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-outline-variant/60 shadow-2xs space-y-4">
                            <div class="flex items-center gap-2 text-primary font-bold text-sm">
                                <span class="material-symbols-outlined text-[18px]">favorite</span>
                                <h4>Status Pernikahan & Pasangan</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Status Pernikahan</label>
                                    <select
                                        v-model="form.status_pernikahan"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
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
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Nama Suami / Istri</label>
                                    <input
                                        v-model="form.nama_suami_istri"
                                        type="text"
                                        placeholder="Nama lengkap pasangan"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">TTL Suami / Istri</label>
                                    <input
                                        v-model="form.ttl_suami_istri"
                                        type="text"
                                        placeholder="Contoh: Solo, 10 Maret 1987"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Pekerjaan Suami / Istri</label>
                                    <input
                                        v-model="form.pekerjaan_suami_istri"
                                        type="text"
                                        placeholder="Contoh: Guru / Wiraswasta / PNS"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Jumlah Anak</label>
                                    <input
                                        v-model="form.jumlah_anak"
                                        type="number"
                                        min="0"
                                        placeholder="0"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Data Orangtua Form -->
                        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-outline-variant/60 shadow-2xs space-y-4">
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
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Nama Ayah</label>
                                    <input
                                        v-model="form.nama_ayah"
                                        type="text"
                                        placeholder="Nama lengkap ayah"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Alamat Orangtua</label>
                                    <input
                                        v-model="form.alamat_orangtua"
                                        type="text"
                                        placeholder="Alamat tempat tinggal orangtua"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Kontak Darurat Form -->
                        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-outline-variant/60 shadow-2xs space-y-4">
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
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Hubungan</label>
                                    <input
                                        v-model="form.hubungan_kontak_darurat"
                                        type="text"
                                        placeholder="Contoh: Saudara Kandung / Paman / Kerabat"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- TAB 4: KEPEGAWAIAN -->
                <div v-if="activeTab === 'kepegawaian'" class="space-y-6">
                    <!-- DATA STATIS: PENGANGKATAN, KEAKTIFAN & DAPODIK (SHEET 1) -->
                    <div class="p-4 sm:p-5 bg-white border-2 border-outline-variant/60 rounded-2xl shadow-2xs">
                        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                            <h4 class="text-xs sm:text-sm font-bold text-primary flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">verified</span>
                                Informasi Umum Kepegawaian
                            </h4>
                        </div>

                        <!-- View Mode Data Statis -->
                        <div v-if="!isEditing" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- TMT & TST Pengangkatan -->
                            <div class="p-3.5 bg-white rounded-xl border border-outline-variant/60 flex items-start gap-3 shadow-2xs">
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
                            <div class="p-3.5 bg-white rounded-xl border border-outline-variant/60 flex items-start gap-3 shadow-2xs">
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
                            <div class="p-3.5 bg-white rounded-xl border border-outline-variant/60 flex items-start gap-3 shadow-2xs">
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
                            <div class="p-3.5 bg-white rounded-xl border border-outline-variant/60 flex items-start gap-3 sm:col-span-2 lg:col-span-3 shadow-2xs">
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
                                    class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                    TST Pengangkatan
                                </label>
                                <input
                                    v-model="form.tst_pengangkatan"
                                    type="date"
                                    class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                />
                            </div>

                            <div class="p-3 bg-white rounded-xl border border-outline-variant/60 flex items-center justify-between gap-3 shadow-2xs">
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
                                    class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
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
                                    class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Keaktifan Dapodik</label>
                                <select
                                    v-model="form.keaktifan_dapodik"
                                    class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
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
                                    class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- EDIT FORM FOR JENJANG KEPEGAWAIAN (Hanya saat isEditing) -->
                    <div v-if="isEditing" class="p-4 sm:p-5 bg-white border border-primary/40 rounded-2xl space-y-4 shadow-2xs">
                        <div class="flex items-center gap-2 text-primary font-bold text-sm">
                            <span class="material-symbols-outlined text-[20px]">workspace_premium</span>
                            <h4>Perbarui Jenjang Kepegawaian</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Jenjang Kepegawaian</label>
                                <select
                                    v-model="form.jenjang_kepegawaian"
                                    class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
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
                                    class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">TST Kenaikan Jenjang</label>
                                <input
                                    v-model="form.tst_kenaikan_jenjang"
                                    type="date"
                                    class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- RIWAYAT JENJANG KEPEGAWAIAN LIST -->
                    <div>
                        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                            <h4 class="text-xs sm:text-sm font-bold text-primary flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">work_history</span>
                                Riwayat Jenjang Kepegawaian
                            </h4>
                            <span class="text-xs text-outline font-medium">
                                {{ employee.riwayat_kepegawaian?.length || 0 }} Catatan
                            </span>
                        </div>

                        <div v-if="employee.riwayat_kepegawaian && employee.riwayat_kepegawaian.length > 0">
                            <div class="overflow-x-auto rounded-xl border border-outline-variant/60 bg-white shadow-2xs">
                                <table class="w-full text-xs sm:text-sm border-collapse border border-outline-variant/60">
                                    <thead>
                                        <tr class="bg-white text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                                            <th class="border border-outline-variant/60 px-4 py-3 text-center w-12">No</th>
                                            <th class="border border-outline-variant/60 px-4 py-3 text-left">Jenjang Kepegawaian</th>
                                            <th class="border border-outline-variant/60 px-4 py-3 text-left">TMT Kenaikan Jenjang</th>
                                            <th class="border border-outline-variant/60 px-4 py-3 text-left">TST Kenaikan Jenjang</th>
                                            <th class="border border-outline-variant/60 px-4 py-3 text-left">Status Masa Berlaku</th>
                                            <th class="border border-outline-variant/60 px-4 py-3 text-center">Berkas SK Jenjang</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(record, idx) in employee.riwayat_kepegawaian"
                                            :key="record.id || idx"
                                            class="hover:bg-slate-50 transition-colors"
                                        >
                                            <td class="border border-outline-variant/60 px-4 py-3 text-center text-on-surface-variant font-mono">{{ idx + 1 }}</td>
                                            <td class="border border-outline-variant/60 px-4 py-3 font-semibold text-on-surface">
                                                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold', getJenjangColor(record.jenjang_kepegawaian)]">
                                                    {{ record.jenjang_kepegawaian || '-' }}
                                                </span>
                                            </td>
                                            <td class="border border-outline-variant/60 px-4 py-3 text-on-surface">
                                                {{ record.formatted_tmt_kenaikan_jenjang || formatTanggalIndo(record.tmt_kenaikan_jenjang) || '-' }}
                                            </td>
                                            <td class="border border-outline-variant/60 px-4 py-3 text-on-surface">
                                                {{ record.formatted_tst_kenaikan_jenjang || record.formatted_tst_jenjang || formatTanggalIndo(record.tst_kenaikan_jenjang || record.tst_jenjang) || 'Sekarang / Tetap' }}
                                            </td>
                                            <td class="border border-outline-variant/60 px-4 py-3">
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
                                            <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                                <div v-if="record.document" class="inline-flex items-center justify-center">
                                                    <button
                                                        type="button"
                                                        @click="openPreview(record.document)"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                        title="Lihat / Preview SK Jenjang"
                                                    >
                                                        <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                        <span>Lihat SK</span>
                                                    </button>
                                                </div>
                                                <span v-else class="text-outline text-xs">-</span>
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

                <!-- TAB 5: PENUGASAN (RIWAYAT JABATAN) -->
                <div v-if="activeTab === 'penugasan'" class="space-y-6">
                    <!-- EDIT FORM FOR JABATAN (Hanya saat isEditing) -->
                    <div v-if="isEditing" class="p-4 sm:p-5 bg-white border border-primary/40 rounded-2xl space-y-4 shadow-2xs">
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
                                    class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
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
                                    class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Jenis Kepegawaian</label>
                                <input
                                    v-model="form.jenis_kepegawaian"
                                    type="text"
                                    placeholder="Tenaga Pendidik / Tenaga Kependidikan"
                                    class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">TMT Jabatan</label>
                                <input
                                    v-model="form.tmt_jabatan"
                                    type="date"
                                    class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">TST Jabatan</label>
                                <input
                                    v-model="form.tst_jabatan"
                                    type="date"
                                    class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- RIWAYAT JABATAN LIST -->
                    <div>
                        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                            <h4 class="text-xs sm:text-sm font-bold text-primary flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">assignment_turned_in</span>
                                Riwayat Jabatan
                            </h4>
                            <span class="text-xs text-outline font-medium">
                                {{ employee.riwayat_jabatan?.length || 0 }} Jabatan
                            </span>
                        </div>

                        <div v-if="employee.riwayat_jabatan && employee.riwayat_jabatan.length > 0">
                            <div class="overflow-x-auto rounded-xl border border-outline-variant/60 bg-white shadow-2xs">
                                <table class="w-full text-xs sm:text-sm border-collapse border border-outline-variant/60">
                                    <thead>
                                        <tr class="bg-white text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                                            <th class="border border-outline-variant/60 px-4 py-3 text-center w-12">No</th>
                                            <th class="border border-outline-variant/60 px-4 py-3 text-left">Jabatan</th>
                                            <th class="border border-outline-variant/60 px-4 py-3 text-left">Unit Kerja</th>
                                            <th class="border border-outline-variant/60 px-4 py-3 text-left">Jenis Kepegawaian</th>
                                            <th class="border border-outline-variant/60 px-4 py-3 text-left">TMT Jabatan</th>
                                            <th class="border border-outline-variant/60 px-4 py-3 text-left">TST Jabatan</th>
                                            <th class="border border-outline-variant/60 px-4 py-3 text-left">Status</th>
                                            <th class="border border-outline-variant/60 px-4 py-3 text-center">Dokumen SK Penugasan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(pos, idx) in employee.riwayat_jabatan"
                                            :key="pos.id || idx"
                                            class="hover:bg-slate-50 transition-colors"
                                        >
                                            <td class="border border-outline-variant/60 px-4 py-3 text-center text-on-surface-variant font-mono">{{ idx + 1 }}</td>
                                            <td class="border border-outline-variant/60 px-4 py-3 font-bold text-on-surface">{{ pos.jabatan || '-' }}</td>
                                            <td class="border border-outline-variant/60 px-4 py-3">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-primary/10 text-primary">
                                                    {{ pos.unit_kerja || '-' }}
                                                </span>
                                            </td>
                                            <td class="border border-outline-variant/60 px-4 py-3 text-on-surface">{{ pos.jenis_kepegawaian || '-' }}</td>
                                            <td class="border border-outline-variant/60 px-4 py-3 text-on-surface">
                                                {{ pos.formatted_tmt_jabatan || formatTanggalIndo(pos.tmt_jabatan) || '-' }}
                                            </td>
                                            <td class="border border-outline-variant/60 px-4 py-3 text-on-surface">
                                                {{ pos.formatted_tst_jabatan || formatTanggalIndo(pos.tst_jabatan) || 'Sekarang / Aktif' }}
                                            </td>
                                            <td class="border border-outline-variant/60 px-4 py-3">
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
                                            <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                                <div v-if="pos.document" class="inline-flex items-center justify-center">
                                                    <button
                                                        type="button"
                                                        @click="openPreview(pos.document)"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                        title="Lihat / Preview SK Penugasan"
                                                    >
                                                        <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                        <span>Lihat SK</span>
                                                    </button>
                                                </div>
                                                <span v-else class="text-outline text-xs">-</span>
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
                        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                            <h4 class="text-xs sm:text-sm font-bold text-primary flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">school</span>
                                Riwayat Pendidikan
                            </h4>
                            <span class="text-xs text-outline font-medium">
                                {{ employee.riwayat_pendidikan?.length || 0 }} Catatan
                            </span>
                        </div>

                        <div v-if="employee.riwayat_pendidikan && employee.riwayat_pendidikan.length > 0">
                            <div class="overflow-x-auto rounded-xl border border-outline-variant/60 bg-white shadow-2xs">
                                <table class="w-full text-xs sm:text-sm border-collapse border border-outline-variant/60">
                                    <thead>
                                        <tr class="bg-white text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                                            <th class="border border-outline-variant/60 px-4 py-3 text-center w-12">No</th>
                                            <th class="border border-outline-variant/60 px-4 py-3 text-left">Jenjang</th>
                                            <th class="border border-outline-variant/60 px-4 py-3 text-left">Jurusan</th>
                                            <th class="border border-outline-variant/60 px-4 py-3 text-left">Instansi Pendidikan</th>
                                            <th class="border border-outline-variant/60 px-4 py-3 text-center">Tahun Lulus</th>
                                            <th class="border border-outline-variant/60 px-4 py-3 text-left">Pembiayaan</th>
                                            <th class="border border-outline-variant/60 px-4 py-3 text-center">Berkas Ijazah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(edu, idx) in employee.riwayat_pendidikan"
                                            :key="edu.id || idx"
                                            class="hover:bg-slate-50 transition-colors"
                                        >
                                            <td class="border border-outline-variant/60 px-4 py-3 text-center text-on-surface-variant font-mono">{{ idx + 1 }}</td>
                                            <td class="border border-outline-variant/60 px-4 py-3">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-primary/10 text-primary">
                                                    {{ edu.jenjang_pendidikan || '-' }}
                                                </span>
                                            </td>
                                            <td class="border border-outline-variant/60 px-4 py-3 text-on-surface">{{ edu.jurusan || '-' }}</td>
                                            <td class="border border-outline-variant/60 px-4 py-3 text-on-surface">{{ edu.instansi_pendidikan || '-' }}</td>
                                            <td class="border border-outline-variant/60 px-4 py-3 text-center font-semibold text-on-surface">{{ edu.tahun_lulus || '-' }}</td>
                                            <td class="border border-outline-variant/60 px-4 py-3 text-on-surface">{{ edu.pembiayaan || '-' }}</td>
                                            <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                                <div v-if="edu.document" class="inline-flex items-center justify-center">
                                                    <button
                                                        type="button"
                                                        @click="openPreview(edu.document)"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                        title="Lihat Berkas Ijazah"
                                                    >
                                                        <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                        <span>Lihat Ijazah</span>
                                                    </button>
                                                </div>
                                                <span v-else class="text-outline text-xs">-</span>
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
                                class="p-4 bg-white rounded-2xl border border-outline-variant/60 shadow-2xs"
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
                                            class="w-full px-3 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary cursor-pointer"
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
                                            class="w-full px-3 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Instansi / Universitas</label>
                                        <input
                                            v-model="edu.instansi_pendidikan"
                                            type="text"
                                            placeholder="Contoh: UNS Surakarta"
                                            class="w-full px-3 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Tahun Lulus</label>
                                        <input
                                            v-model="edu.tahun_lulus"
                                            type="text"
                                            placeholder="Contoh: 2018"
                                            class="w-full px-3 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary"
                                        />
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Pembiayaan</label>
                                        <input
                                            v-model="edu.pembiayaan"
                                            type="text"
                                            placeholder="Contoh: Mandiri / Beasiswa / Dinas"
                                            class="w-full px-3 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                                <!-- TAB 7: DOKUMEN -->
                <div v-if="activeTab === 'dokumen'" class="space-y-8">
                    <!-- 1. TABEL DOKUMEN PRIBADI -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                            <h4 class="text-xs sm:text-sm font-bold text-primary flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">badge</span>
                                Dokumen Pribadi
                            </h4>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-outline-variant/60 bg-white shadow-2xs">
                            <table class="w-full text-xs sm:text-sm border-collapse border border-outline-variant/60">
                                <thead>
                                    <tr class="bg-white text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center w-12">No</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-left">Nama Dokumen</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center">Status Unggah</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in dokumenPribadiList"
                                        :key="item.id"
                                        class="hover:bg-slate-50 transition-colors"
                                    >
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center text-on-surface-variant font-mono">{{ item.no }}</td>
                                        <td class="border border-outline-variant/60 px-4 py-3 font-semibold text-on-surface">
                                            {{ item.nama }}
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                            <span
                                                v-if="item.document"
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                Sudah Diunggah
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                                Belum Diunggah
                                            </span>
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                            <div v-if="item.document" class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                                <button
                                                    type="button"
                                                    @click="openPreview(item.document)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                    title="Lihat Pratinjau Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                    <span>Lihat</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-outline-variant/60 hover:bg-slate-100 text-slate-700 transition-colors cursor-pointer"
                                                    title="Unggah Ulang / Ganti Berkas"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">sync</span>
                                                    <span>Unggah</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="deleteDocument(item.document)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                                    title="Hapus Berkas Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                            <div v-else class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                                <button
                                                    type="button"
                                                    @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-primary/10 text-primary hover:bg-primary/20 transition-colors cursor-pointer"
                                                    title="Upload Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                                    <span>Unggah</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    disabled
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60"
                                                    title="Belum ada berkas untuk dilihat"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">visibility_off</span>
                                                    <span>Lihat</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    disabled
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60"
                                                    title="Belum ada berkas untuk dihapus"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="dokumenPribadiList.length === 0">
                                        <td colspan="4" class="border border-outline-variant/60 px-4 py-8 text-center text-xs text-on-surface-variant">
                                            Belum ada dokumen pribadi.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 2. TABEL DOKUMEN RIWAYAT KEPEGAWAIAN -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                            <h4 class="text-xs sm:text-sm font-bold text-primary flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">work_history</span>
                                Dokumen Riwayat Kepegawaian
                            </h4>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-outline-variant/60 bg-white shadow-2xs">
                            <table class="w-full text-xs sm:text-sm border-collapse border border-outline-variant/60">
                                <thead>
                                    <tr class="bg-white text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center w-12">No</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-left">Nama Dokumen</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-left">Keterangan / Rincian</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center">Status Unggah</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in dokumenKepegawaianList"
                                        :key="item.id"
                                        class="hover:bg-slate-50 transition-colors"
                                    >
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center text-on-surface-variant font-mono">{{ item.no }}</td>
                                        <td class="border border-outline-variant/60 px-4 py-3 font-semibold text-on-surface">
                                            {{ item.nama }}
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-on-surface-variant">
                                            {{ item.keterangan || '-' }}
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                            <span
                                                v-if="item.document"
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                Sudah Diunggah
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                                Belum Diunggah
                                            </span>
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                            <div v-if="item.document" class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                                <button
                                                    type="button"
                                                    @click="openPreview(item.document)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                    title="Lihat Pratinjau Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                    <span>Lihat</span>
                                                </button>
                                                <a
                                                    :href="item.document.download_url"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-outline-variant/60 hover:bg-slate-100 text-on-surface transition-colors cursor-pointer"
                                                    title="Unduh Berkas"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">download</span>
                                                    <span>Unduh</span>
                                                </a>
                                                <button
                                                    v-if="!item.isAdditional"
                                                    type="button"
                                                    @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-outline-variant/60 hover:bg-slate-100 text-slate-700 transition-colors cursor-pointer"
                                                    title="Ganti Berkas Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">sync</span>
                                                    <span>Unggah</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="deleteDocument(item.document)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                                    title="Hapus Berkas Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                            <div v-else class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                                <button
                                                    type="button"
                                                    @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-primary/10 text-primary hover:bg-primary/20 transition-colors cursor-pointer"
                                                    title="Upload Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                                    <span>Unggah</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    disabled
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60"
                                                    title="Belum ada berkas untuk dilihat"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">visibility_off</span>
                                                    <span>Lihat</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    disabled
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60"
                                                    title="Belum ada berkas untuk dihapus"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="dokumenKepegawaianList.length === 0">
                                        <td colspan="5" class="border border-outline-variant/60 px-4 py-8 text-center text-xs text-on-surface-variant">
                                            Belum ada dokumen riwayat kepegawaian.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 3. TABEL DOKUMEN RIWAYAT JABATAN -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                            <h4 class="text-xs sm:text-sm font-bold text-primary flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">assignment_turned_in</span>
                                Dokumen Riwayat Jabatan
                            </h4>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-outline-variant/60 bg-white shadow-2xs">
                            <table class="w-full text-xs sm:text-sm border-collapse border border-outline-variant/60">
                                <thead>
                                    <tr class="bg-white text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center w-12">No</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-left">Nama Dokumen</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-left">Keterangan / Rincian</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center">Status Unggah</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in dokumenJabatanList"
                                        :key="item.id"
                                        class="hover:bg-slate-50 transition-colors"
                                    >
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center text-on-surface-variant font-mono">{{ item.no }}</td>
                                        <td class="border border-outline-variant/60 px-4 py-3 font-semibold text-on-surface">
                                            {{ item.nama }}
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-on-surface-variant">
                                            {{ item.keterangan || '-' }}
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                            <span
                                                v-if="item.document"
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                Sudah Diunggah
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                                Belum Diunggah
                                            </span>
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                            <div v-if="item.document" class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                                <button
                                                    type="button"
                                                    @click="openPreview(item.document)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                    title="Lihat Pratinjau Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                    <span>Lihat</span>
                                                </button>
                                                <a
                                                    :href="item.document.download_url"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-outline-variant/60 hover:bg-slate-100 text-on-surface transition-colors cursor-pointer"
                                                    title="Unduh Berkas"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">download</span>
                                                    <span>Unduh</span>
                                                </a>
                                                <button
                                                    v-if="!item.isAdditional"
                                                    type="button"
                                                    @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-outline-variant/60 hover:bg-slate-100 text-slate-700 transition-colors cursor-pointer"
                                                    title="Ganti Berkas Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">sync</span>
                                                    <span>Unggah</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="deleteDocument(item.document)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                                    title="Hapus Berkas Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                            <div v-else class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                                <button
                                                    type="button"
                                                    @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-primary/10 text-primary hover:bg-primary/20 transition-colors cursor-pointer"
                                                    title="Upload Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                                    <span>Unggah</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    disabled
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60"
                                                    title="Belum ada berkas untuk dilihat"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">visibility_off</span>
                                                    <span>Lihat</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    disabled
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60"
                                                    title="Belum ada berkas untuk dihapus"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="dokumenJabatanList.length === 0">
                                        <td colspan="5" class="border border-outline-variant/60 px-4 py-8 text-center text-xs text-on-surface-variant">
                                            Belum ada data riwayat jabatan.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 4. TABEL DOKUMEN RIWAYAT PENDIDIKAN -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                            <h4 class="text-xs sm:text-sm font-bold text-primary flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">school</span>
                                Dokumen Riwayat Pendidikan
                            </h4>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-outline-variant/60 bg-white shadow-2xs">
                            <table class="w-full text-xs sm:text-sm border-collapse border border-outline-variant/60">
                                <thead>
                                    <tr class="bg-white text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center w-12">No</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-left">Nama Dokumen</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-left">Keterangan / Rincian</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center">Status Unggah</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in dokumenPendidikanList"
                                        :key="item.id"
                                        class="hover:bg-slate-50 transition-colors"
                                    >
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center text-on-surface-variant font-mono">{{ item.no }}</td>
                                        <td class="border border-outline-variant/60 px-4 py-3 font-semibold text-on-surface">
                                            {{ item.nama }}
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-on-surface-variant">
                                            {{ item.keterangan || '-' }}
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                            <span
                                                v-if="item.document"
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                Sudah Diunggah
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                                Belum Diunggah
                                            </span>
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                            <div v-if="item.document" class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                                <button
                                                    type="button"
                                                    @click="openPreview(item.document)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                    title="Lihat Pratinjau Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                    <span>Lihat</span>
                                                </button>
                                                <a
                                                    :href="item.document.download_url"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-outline-variant/60 hover:bg-slate-100 text-on-surface transition-colors cursor-pointer"
                                                    title="Unduh Berkas"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">download</span>
                                                    <span>Unduh</span>
                                                </a>
                                                <button
                                                    v-if="!item.isAdditional"
                                                    type="button"
                                                    @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-outline-variant/60 hover:bg-slate-100 text-slate-700 transition-colors cursor-pointer"
                                                    title="Ganti Berkas Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">sync</span>
                                                    <span>Unggah</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="deleteDocument(item.document)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                                    title="Hapus Berkas Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                            <div v-else class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                                <button
                                                    type="button"
                                                    @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-primary/10 text-primary hover:bg-primary/20 transition-colors cursor-pointer"
                                                    title="Upload Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                                    <span>Unggah</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    disabled
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60"
                                                    title="Belum ada berkas untuk dilihat"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">visibility_off</span>
                                                    <span>Lihat</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    disabled
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60"
                                                    title="Belum ada berkas untuk dihapus"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="dokumenPendidikanList.length === 0">
                                        <td colspan="5" class="border border-outline-variant/60 px-4 py-8 text-center text-xs text-on-surface-variant">
                                            Belum ada dokumen riwayat pendidikan.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>


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
                <div class="bg-white rounded-2xl border border-outline-variant/60 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-5 border-b border-outline-variant/60 bg-white">
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
                            class="p-1.5 rounded-lg text-on-surface-variant hover:text-on-surface hover:bg-slate-100 transition-colors cursor-pointer"
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
                                class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
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
                                    isDragging ? 'border-primary bg-primary/5' : 'border-outline-variant/60 hover:border-primary/50 bg-white',
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
                                class="px-4 py-2 rounded-xl text-xs font-semibold text-on-surface-variant hover:text-on-surface hover:bg-slate-100 border border-outline-variant/60 transition-colors cursor-pointer"
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
    </div>
</template>
