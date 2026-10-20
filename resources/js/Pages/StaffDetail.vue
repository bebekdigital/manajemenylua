<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
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
    { id: 'pendidikan', label: 'Pendidikan', icon: 'school' },
];

const form = useForm({
    nama: '',
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

    // Kepegawaian Aktif
    academic_year_id: null,
    unit_kerja: '',
    jabatan: '',
    jenis_kepegawaian: '',
    status_keaktifan: 'Aktif',
    keterangan_tidak_aktif: '',
    jenjang_kepegawaian: '',
    tmt: '',
    tst_jenjang: '',
    masa_kerja: '',
    keaktifan_dapodik: '',
    unit_keaktifan_dapodik: '',

    // Riwayat Pendidikan
    riwayat_pendidikan: [],
});

function initFormFromProps() {
    form.nama = props.employee.nama || '';
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

    form.academic_year_id = props.selectedAcademicYear?.id || null;
    form.unit_kerja = props.employee.unit_kerja || '';
    form.jabatan = props.employee.jabatan || '';
    form.jenis_kepegawaian = props.employee.jenis_kepegawaian || '';
    form.status_keaktifan = props.employee.status_keaktifan || 'Aktif';
    form.keterangan_tidak_aktif = props.employee.keterangan_tidak_aktif || '';
    form.jenjang_kepegawaian = props.employee.jenjang_kepegawaian || '';
    form.tmt = props.employee.tmt || '';
    form.tst_jenjang = props.employee.tst_jenjang || '';
    form.masa_kerja = props.employee.masa_kerja || '';
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
</script>

<template>
    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop bg-surface-container-lowest">
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

        <!-- Mode Edit Indicator Banner -->
        <div
            v-if="isEditing"
            class="mb-4 p-4 rounded-2xl bg-primary/10 border-2 border-primary/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs"
        >
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-primary text-2xl shrink-0">edit_note</span>
                <div>
                    <h4 class="text-sm font-bold text-primary">Mode Edit Langsung Aktif</h4>
                    <p class="text-xs text-on-surface-variant mt-0.5">
                        Anda dapat mengubah data langsung pada tab di bawah ini. Klik <strong>Simpan Perubahan</strong> untuk menyimpan atau <strong>Batal</strong> untuk membatalkan.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0 self-end sm:self-auto">
                <button
                    type="button"
                    @click="cancelEdit"
                    class="px-3.5 py-1.5 rounded-xl border border-outline-variant/60 text-on-surface-variant hover:bg-surface-container-high text-xs font-semibold transition-all cursor-pointer"
                    :disabled="form.processing"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="saveChanges"
                    class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary text-xs font-semibold transition-all shadow-xs hover:shadow-md cursor-pointer disabled:opacity-60"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                    <span v-else class="material-symbols-outlined text-[16px]">save</span>
                    <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                </button>
            </div>
        </div>

        <!-- Main Card -->
        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 flex flex-col overflow-hidden">
            <!-- Profile Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between p-5 sm:px-6 sm:py-5 border-b border-outline-variant/30 bg-surface-container-low gap-4">
                <div class="flex items-center gap-4 sm:gap-5">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-primary/10 flex items-center justify-center overflow-hidden border-2 border-outline-variant/20 shadow-sm shrink-0">
                        <span class="text-primary font-bold text-2xl sm:text-3xl">
                            {{ employee.nama ? employee.nama.charAt(0) : '?' }}
                        </span>
                    </div>
                    <div>
                        <h3 class="text-lg sm:text-xl font-bold text-on-surface">{{ employee.nama }}</h3>
                        <p class="text-xs sm:text-sm text-on-surface-variant">NIPY: {{ employee.nipy || '-' }}</p>
                        <div class="flex flex-wrap items-center gap-2 mt-2">
                            <span
                                v-if="employee.jenjang_kepegawaian"
                                :class="['inline-flex items-center px-2.5 py-0.5 rounded-md text-[10px] font-semibold', getJenjangColor(employee.jenjang_kepegawaian)]"
                            >
                                {{ employee.jenjang_kepegawaian }}
                            </span>
                            <span
                                v-if="employee.status_keaktifan"
                                :class="['inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold', getStatusColor(employee.status_keaktifan)]"
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
                <div class="flex items-center gap-2 self-start sm:self-auto shrink-0">
                    <button
                        v-if="!isEditing"
                        type="button"
                        @click="startEdit"
                        class="inline-flex items-center gap-2 px-4 py-2 sm:px-5 sm:py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-xs sm:text-sm transition-all shadow-xs hover:shadow-md cursor-pointer"
                        title="Edit data pegawai ini langsung di tab"
                    >
                        <span class="material-symbols-outlined text-[18px]">edit_square</span>
                        <span>Edit Data Pegawai</span>
                    </button>

                    <template v-else>
                        <button
                            type="button"
                            @click="cancelEdit"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-xl border border-outline-variant/60 text-on-surface-variant hover:bg-surface-container-high font-semibold text-xs sm:text-sm transition-all cursor-pointer"
                            :disabled="form.processing"
                        >
                            <span class="material-symbols-outlined text-[18px]">close</span>
                            <span>Batal</span>
                        </button>
                        <button
                            type="button"
                            @click="saveChanges"
                            class="inline-flex items-center gap-1.5 px-4 py-2 sm:px-5 sm:py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-xs sm:text-sm transition-all shadow-xs hover:shadow-md cursor-pointer disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing" class="material-symbols-outlined text-[18px] animate-spin">sync</span>
                            <span v-else class="material-symbols-outlined text-[18px]">save</span>
                            <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                        </button>
                    </template>
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
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">NIK</label>
                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5 font-mono">{{ employee.nik || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">No. WhatsApp</label>
                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.no_wa || '-' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Email</label>
                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.email || '-' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Skill / Keahlian</label>
                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.skill || '-' }}</p>
                        </div>
                    </div>

                    <!-- EDIT MODE -->
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Nama Lengkap Beserta Gelar <span class="text-error">*</span>
                            </label>
                            <input
                                v-model="form.nama"
                                type="text"
                                required
                                placeholder="Contoh: Ahmad Fauzi, S.Pd"
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
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Tanggal Lahir
                            </label>
                            <input
                                v-model="form.tanggal_lahir"
                                type="date"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                                :class="{ 'border-error': form.errors.tanggal_lahir }"
                            />
                            <div v-if="form.errors.tanggal_lahir" class="text-error text-xs mt-1">{{ form.errors.tanggal_lahir }}</div>
                        </div>

                        <div>
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
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Status Rumah</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.status_rumah || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Kepemilikan BPJS</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.kepemilikan_bpjs || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Penanggung BPJS</label>
                                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.penanggung_bpjs || '-' }}</p>
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

                                <div>
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

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Kepemilikan BPJS</label>
                                    <select
                                        v-model="form.kepemilikan_bpjs"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                    >
                                        <option value="">-- Pilih --</option>
                                        <option value="Ya">Ya</option>
                                        <option value="Tidak">Tidak</option>
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Penanggung BPJS</label>
                                    <input
                                        v-model="form.penanggung_bpjs"
                                        type="text"
                                        placeholder="Contoh: Yayasan / Pribadi / Mandiri"
                                        class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
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
                    <!-- EDIT FORM FOR ACTIVE YEAR RECORD -->
                    <div v-if="isEditing" class="p-4 sm:p-5 bg-surface-container-low/60 border border-primary/30 rounded-2xl space-y-4">
                        <div class="flex items-center gap-2 text-primary font-bold text-sm">
                            <span class="material-symbols-outlined text-[20px]">badge</span>
                            <h4>Data Kepegawaian (Tahun Ajaran Aktif: {{ selectedAcademicYear ? `${selectedAcademicYear.name} - ${selectedAcademicYear.semester}` : 'Aktif' }})</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                    placeholder="Contoh: Guru Kelas, Kepala Sekolah, dll."
                                    class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                />
                            </div>

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
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Jenis Kepegawaian</label>
                                <input
                                    v-model="form.jenis_kepegawaian"
                                    type="text"
                                    placeholder="Contoh: Tenaga Pendidik / Tenaga Kependidikan"
                                    class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Status Keaktifan</label>
                                <select
                                    v-model="form.status_keaktifan"
                                    class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                >
                                    <option value="Aktif">Aktif</option>
                                    <option value="Non-Aktif">Non-Aktif</option>
                                    <option value="Pensiun">Pensiun</option>
                                    <option value="Cuti">Cuti</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Keterangan Status</label>
                                <input
                                    v-model="form.keterangan_tidak_aktif"
                                    type="text"
                                    placeholder="Keterangan jika cuti / non-aktif / pensiun"
                                    class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">TMT (Terhitung Mulai Tanggal)</label>
                                <input
                                    v-model="form.tmt"
                                    type="date"
                                    class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">TST Jenjang</label>
                                <input
                                    v-model="form.tst_jenjang"
                                    type="date"
                                    class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Masa Kerja</label>
                                <input
                                    v-model="form.masa_kerja"
                                    type="text"
                                    placeholder="Contoh: 5 Tahun 2 Bulan"
                                    class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Keaktifan Dapodik</label>
                                <select
                                    v-model="form.keaktifan_dapodik"
                                    class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                >
                                    <option value="">-- Pilih Status --</option>
                                    <option value="Aktif">Aktif</option>
                                    <option value="Tidak Aktif">Tidak Aktif</option>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Unit Keaktifan Dapodik</label>
                                <input
                                    v-model="form.unit_keaktifan_dapodik"
                                    type="text"
                                    placeholder="Unit yang terdaftar di Dapodik"
                                    class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- RIWAYAT KEPEGAWAIAN LIST -->
                    <div>
                        <h4 class="text-xs sm:text-sm font-bold text-primary mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">work_history</span>
                            Riwayat Catatan Kepegawaian (Seluruh Tahun Ajaran)
                        </h4>

                        <div v-if="employee.riwayat_kepegawaian && employee.riwayat_kepegawaian.length > 0">
                            <div
                                v-for="record in employee.riwayat_kepegawaian"
                                :key="record.id"
                                class="mb-4 p-4 bg-surface-container-low border border-outline-variant/30 rounded-xl"
                            >
                                <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px] text-primary">calendar_month</span>
                                        <span class="text-sm font-bold text-on-surface">
                                            {{ record.tahun_ajaran }} — Semester {{ record.semester }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span
                                            v-if="record.jenjang_kepegawaian"
                                            :class="['inline-flex items-center px-2.5 py-0.5 rounded-md text-[10px] font-semibold', getJenjangColor(record.jenjang_kepegawaian)]"
                                        >
                                            {{ record.jenjang_kepegawaian }}
                                        </span>
                                        <span
                                            v-if="record.status_keaktifan"
                                            :class="['inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold', getStatusColor(record.status_keaktifan)]"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                            {{ record.status_keaktifan }}
                                        </span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-3">
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Unit</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ record.unit_kerja || '-' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Jabatan</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ record.jabatan || '-' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Keaktifan Dapodik</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ record.keaktifan_dapodik || '-' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">TMT</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ record.formatted_tmt || '-' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">TST</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ record.formatted_tst_jenjang || '-' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Masa Kerja</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ record.masa_kerja || '-' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-10 text-center border-2 border-dashed border-outline-variant/30 rounded-2xl">
                            <span class="material-symbols-outlined text-4xl text-outline/60 block mb-2">work_history</span>
                            <p class="text-sm text-on-surface-variant">Belum ada riwayat kepegawaian</p>
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

            </div>
        </div>

        <!-- Floating Sticky Bottom Bar when in Edit Mode -->
        <div
            v-if="isEditing"
            class="sticky bottom-4 z-20 mt-6 bg-surface-container-lowest/95 backdrop-blur-md p-4 rounded-2xl border border-primary/30 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-3 animate-in fade-in"
        >
            <div class="flex items-center gap-2.5 text-xs sm:text-sm text-on-surface">
                <span class="w-2.5 h-2.5 rounded-full bg-primary animate-pulse shrink-0"></span>
                <span class="font-medium">
                    Perubahan belum disimpan. Pindah antar tab untuk mengubah data lain, lalu klik <strong>Simpan Perubahan</strong>.
                </span>
            </div>
            <div class="flex items-center gap-2 shrink-0 self-end sm:self-auto">
                <button
                    type="button"
                    @click="cancelEdit"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-on-surface-variant hover:bg-surface-container-high border border-outline-variant/60 transition-colors cursor-pointer"
                    :disabled="form.processing"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="saveChanges"
                    class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-xs sm:text-sm transition-all shadow-xs hover:shadow-md cursor-pointer disabled:opacity-60"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                    <span v-else class="material-symbols-outlined text-[16px]">save</span>
                    <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
