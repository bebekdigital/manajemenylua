<script setup>
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import StudentDocumentSheet from './StudentDocumentSheet.vue';

const props = defineProps({
    template: {
        type: Object,
        required: true,
    },
    students: {
        type: Array,
        default: () => [],
    },
});

// Form state initialized from props.template
const form = reactive({
    school_profile: {
        nama_sekolah: props.template?.school_profile?.nama_sekolah || '',
        jenjang_tingkat: props.template?.school_profile?.jenjang_tingkat || 'SEKOLAH MENENGAH PERTAMA',
        jenjang_singkat: props.template?.school_profile?.jenjang_singkat || '( SMP )',
        npsn: props.template?.school_profile?.npsn || '',
        nis_nss_nds: props.template?.school_profile?.nis_nss_nds || '-',
        alamat_sekolah: props.template?.school_profile?.alamat_sekolah || '',
        kelurahan_desa: props.template?.school_profile?.kelurahan_desa || '',
        kecamatan: props.template?.school_profile?.kecamatan || '',
        kota_kabupaten: props.template?.school_profile?.kota_kabupaten || '',
        provinsi: props.template?.school_profile?.provinsi || '',
        website: props.template?.school_profile?.website || '',
        email: props.template?.school_profile?.email || '',
        kementerian_title: props.template?.school_profile?.kementerian_title || 'KEMENTERIAN PENDIDIKAN DASAR DAN MENENGAH REPUBLIK INDONESIA',
    },
    signatory: {
        tempat_penandatangan: props.template?.signatory?.tempat_penandatangan || props.template?.signatory?.tempat_titimangsa || 'Karanganyar',
        jabatan: props.template?.signatory?.jabatan || 'Kepala Sekolah',
        nama_kepala_sekolah: props.template?.signatory?.nama_kepala_sekolah || 'Nurul Choirul Janah, S.Pd.',
        nip: props.template?.signatory?.nip || '',
    },
    identity_visibility: {
        nama_lengkap: props.template?.options?.identity_visibility?.nama_lengkap ?? true,
        nomor_induk: props.template?.options?.identity_visibility?.nomor_induk ?? true,
        tempat_tanggal_lahir: props.template?.options?.identity_visibility?.tempat_tanggal_lahir ?? true,
        jenis_kelamin: props.template?.options?.identity_visibility?.jenis_kelamin ?? true,
        agama: props.template?.options?.identity_visibility?.agama ?? true,
        status_keluarga: props.template?.options?.identity_visibility?.status_keluarga ?? true,
        anak_ke: props.template?.options?.identity_visibility?.anak_ke ?? true,
        alamat_peserta: props.template?.options?.identity_visibility?.alamat_peserta ?? true,
        nomor_telepon: props.template?.options?.identity_visibility?.nomor_telepon ?? true,
        sekolah_asal: props.template?.options?.identity_visibility?.sekolah_asal ?? true,
        diterima_di_sekolah: props.template?.options?.identity_visibility?.diterima_di_sekolah ?? true,
        nama_orang_tua: props.template?.options?.identity_visibility?.nama_orang_tua ?? true,
        alamat_orang_tua: props.template?.options?.identity_visibility?.alamat_orang_tua ?? true,
        telepon_orang_tua: props.template?.options?.identity_visibility?.telepon_orang_tua ?? true,
        pekerjaan_orang_tua: props.template?.options?.identity_visibility?.pekerjaan_orang_tua ?? true,
        nama_wali: props.template?.options?.identity_visibility?.nama_wali ?? true,
        alamat_wali: props.template?.options?.identity_visibility?.alamat_wali ?? true,
        telepon_wali: props.template?.options?.identity_visibility?.telepon_wali ?? true,
        pekerjaan_wali: props.template?.options?.identity_visibility?.pekerjaan_wali ?? true,
    },
    options: {
        show_tut_wuri_logo: props.template?.options?.show_tut_wuri_logo ?? true,
        show_school_logo: props.template?.options?.show_school_logo ?? true,
        show_photo_box: props.template?.options?.show_photo_box ?? true,
        paper_size: props.template?.options?.paper_size || 'A4',
    },
    template_content: props.template?.template_content || null,
});

const isSubmitting = ref(false);
const activeEditorSection = ref('school'); // 'school', 'signatory', 'options', 'fields'
const previewStudentIndex = ref(0);
const previewPage = ref('all'); // 'all', 'cover', 'school_profile', 'identity'
const zoomLevel = ref(70);

// Sample student for live preview fallback
const sampleStudent = computed(() => {
    if (props.students && props.students.length > 0) {
        return props.students[previewStudentIndex.value] || props.students[0];
    }

    return {
        nama: 'Ahmad Fauzi Rahman',
        nama_kapital: 'AHMAD FAUZI RAHMAN',
        nisn: '0051234001',
        nis: '10231001',
        nipd: '10231001',
        jk: 'Laki-laki',
        tempat_lahir: 'Karanganyar',
        tanggal_lahir: '15 Maret 2011',
        ttl: 'Karanganyar, 15 Maret 2011',
        agama: 'Islam',
        status_keluarga: 'Anak Kandung',
        anak_ke: '1',
        alamat: 'Jl. Lawu No. 12, RT 03 / RW 05, Dsn. Ngemplak, Ds. Colomadu, Kec. Colomadu, Kab. Karanganyar, Jawa Tengah',
        no_wa: '081234567001',
        sekolah_asal: 'SDIT Ulil Albab Karanganyar',
        kelas_diterima: 'VII A (Tujuh A)',
        tanggal_diterima: '15 Juli 2024',
        ayah_nama: 'Fauzi Hidayat',
        ayah_pekerjaan: 'Wiraswasta',
        ibu_nama: 'Siti Rahmawati',
        ibu_pekerjaan: 'Ibu Rumah Tangga',
        wali_nama: '-',
        wali_alamat: '-',
        wali_hp: '-',
        wali_pekerjaan: '-',
    };
});

const livePreviewTemplate = computed(() => {
    return {
        school_profile: form.school_profile,
        signatory: form.signatory,
        options: {
            ...form.options,
            identity_visibility: form.identity_visibility,
        },
        template_content: form.template_content,
    };
});

const availablePlaceholders = [
    { tag: '{{Nama}}', desc: 'Nama Lengkap Siswa' },
    { tag: '{{Nama Kapital}}', desc: 'Nama Siswa HURUF KAPITAL' },
    { tag: '{{NISN}}', desc: 'Nomor Induk Siswa Nasional' },
    { tag: '{{NIS}}', desc: 'Nomor Induk Siswa / NIPD' },
    { tag: '{{Tempat Lahir}}', desc: 'Kota / Tempat Lahir' },
    { tag: '{{TTL}}', desc: 'Tanggal Lahir Siswa' },
    { tag: '{{JK}}', desc: 'Jenis Kelamin (Laki-laki/Perempuan)' },
    { tag: '{{Agama}}', desc: 'Agama Siswa' },
    { tag: '{{Status Keluarga}}', desc: 'Status Anak (Kandung/Tiri/Angkat)' },
    { tag: '{{Anak ke}}', desc: 'Urutan Anak' },
    { tag: '{{Alamat}}', desc: 'Alamat Lengkap Domisili' },
    { tag: '{{HP}}', desc: 'Nomor Telepon / WhatsApp Siswa' },
    { tag: '{{Sekolah Asal}}', desc: 'Nama Sekolah Asal' },
    { tag: '{{Kelas Diterima}}', desc: 'Kelas Saat Diterima' },
    { tag: '{{Tanggal Diterima}}', desc: 'Tanggal Resmi Diterima di Sekolah' },
    { tag: '{{Nama Ayah}}', desc: 'Nama Lengkap Ayah' },
    { tag: '{{Nama Ibu}}', desc: 'Nama Lengkap Ibu' },
    { tag: '{{Pekerjaan Ayah}}', desc: 'Pekerjaan Ayah' },
    { tag: '{{Pekerjaan Ibu}}', desc: 'Pekerjaan Ibu' },
    { tag: '{{Nama Wali}}', desc: 'Nama Wali Siswa (jika ada)' },
    { tag: '{{Alamat Wali}}', desc: 'Alamat Domisili Wali' },
    { tag: '{{HP Wali}}', desc: 'Nomor Telepon Wali' },
    { tag: '{{Pekerjaan Wali}}', desc: 'Pekerjaan Wali' },
];

const saveTemplate = () => {
    isSubmitting.value = true;
    router.post('/administration/identity-document/template', {
        school_profile: form.school_profile,
        signatory: form.signatory,
        options: form.options,
        template_content: form.template_content,
        identity_visibility: form.identity_visibility,
    }, {
        preserveScroll: true,
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};

const resetTemplate = () => {
    if (!confirm('Apakah Anda yakin ingin mengembalikan template ke format bawaan default? Semua penyesuaian khusus akan diatur ulang.')) {
        return;
    }

    isSubmitting.value = true;
    router.post('/administration/identity-document/template/reset', {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Re-sync local form with prop values
            Object.assign(form.school_profile, props.template.school_profile);
            Object.assign(form.signatory, props.template.signatory);
            Object.assign(form.options, props.template.options);
            form.template_content = props.template.template_content;
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};
</script>

<template>
    <div class="space-y-6">
        <!-- Top Banner Header -->
        <div class="bg-surface-container rounded-2xl p-6 border border-outline-variant flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-on-surface flex items-center space-x-2">
                    <span class="material-symbols-outlined text-primary">tune</span>
                    <span>Pengaturan Template Berkas Identitas Siswa</span>
                </h2>
                <p class="text-sm text-on-surface-variant mt-1">
                    Sesuaikan identitas sekolah, penandatangan, format titik dua, placeholder data, dan logo yang dicetak pada dokumen.
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center space-x-3">
                <button
                    @click="resetTemplate"
                    :disabled="isSubmitting"
                    class="px-4 py-2 border border-outline text-on-surface-variant hover:bg-surface-container-high rounded-xl text-sm font-semibold transition-all cursor-pointer disabled:opacity-50"
                >
                    Reset Default
                </button>
                <button
                    @click="saveTemplate"
                    :disabled="isSubmitting"
                    class="bg-primary hover:bg-primary-container text-on-primary hover:text-on-primary-container px-5 py-2 rounded-xl text-sm font-semibold flex items-center space-x-2 shadow-sm transition-all active:scale-98 cursor-pointer disabled:opacity-50"
                >
                    <span class="material-symbols-outlined text-base">save</span>
                    <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                </button>
            </div>
        </div>

        <!-- 2-Column Layout: Form Editor & Real-time Live Preview -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left Column: Form Editor (5 cols) -->
            <div class="lg:col-span-5 space-y-6">
                <!-- Section Nav Tabs -->
                <div class="flex bg-surface-container-high p-1 rounded-xl text-xs font-semibold text-on-surface-variant overflow-x-auto">
                    <button 
                        @click="activeEditorSection = 'school'"
                        :class="[
                            'flex-1 py-2 px-3 rounded-lg whitespace-nowrap transition-all flex items-center justify-center space-x-1',
                            activeEditorSection === 'school' ? 'bg-surface text-primary shadow-xs' : 'hover:text-on-surface'
                        ]"
                    >
                        <span class="material-symbols-outlined text-sm">school</span>
                        <span>Profil Sekolah</span>
                    </button>
                    <button 
                        @click="activeEditorSection = 'signatory'"
                        :class="[
                            'flex-1 py-2 px-3 rounded-lg whitespace-nowrap transition-all flex items-center justify-center space-x-1',
                            activeEditorSection === 'signatory' ? 'bg-surface text-primary shadow-xs' : 'hover:text-on-surface'
                        ]"
                    >
                        <span class="material-symbols-outlined text-sm">badge</span>
                        <span>Identitas Siswa</span>
                    </button>
                    <button 
                        @click="activeEditorSection = 'options'"
                        :class="[
                            'flex-1 py-2 px-3 rounded-lg whitespace-nowrap transition-all flex items-center justify-center space-x-1',
                            activeEditorSection === 'options' ? 'bg-surface text-primary shadow-xs' : 'hover:text-on-surface'
                        ]"
                    >
                        <span class="material-symbols-outlined text-sm">settings</span>
                        <span>Opsi Logo & Foto</span>
                    </button>
                    <button 
                        @click="activeEditorSection = 'placeholders'"
                        :class="[
                            'flex-1 py-2 px-3 rounded-lg whitespace-nowrap transition-all flex items-center justify-center space-x-1',
                            activeEditorSection === 'placeholders' ? 'bg-surface text-primary shadow-xs' : 'hover:text-on-surface'
                        ]"
                    >
                        <span class="material-symbols-outlined text-sm">code</span>
                        <span>Variabel Tag</span>
                    </button>
                </div>

                <!-- Section 1: Profil Sekolah -->
                <div v-show="activeEditorSection === 'school'" class="bg-surface-container-low p-6 rounded-2xl border border-outline-variant space-y-4">
                    <h3 class="font-bold text-base text-on-surface border-b border-outline-variant pb-2 flex items-center space-x-2">
                        <span class="material-symbols-outlined text-primary text-lg">domain</span>
                        <span>Identitas Lembaga & Sekolah</span>
                    </h3>

                    <div class="space-y-3 text-sm">
                        <div>
                            <label class="block font-medium text-on-surface mb-1">Nama Sekolah</label>
                            <input 
                                v-model="form.school_profile.nama_sekolah"
                                type="text"
                                class="w-full bg-surface border border-outline-variant rounded-xl px-3 py-2 text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-on-surface mb-1">Jenjang Tingkat</label>
                                <input 
                                    v-model="form.school_profile.jenjang_tingkat"
                                    type="text"
                                    placeholder="SEKOLAH MENENGAH PERTAMA"
                                    class="w-full bg-surface border border-outline-variant rounded-xl px-3 py-2 text-sm focus:border-primary outline-none"
                                />
                            </div>
                            <div>
                                <label class="block font-medium text-on-surface mb-1">Jenjang Singkat</label>
                                <input 
                                    v-model="form.school_profile.jenjang_singkat"
                                    type="text"
                                    placeholder="( SMP )"
                                    class="w-full bg-surface border border-outline-variant rounded-xl px-3 py-2 text-sm focus:border-primary outline-none"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-on-surface mb-1">NPSN</label>
                                <input 
                                    v-model="form.school_profile.npsn"
                                    type="text"
                                    class="w-full bg-surface border border-outline-variant rounded-xl px-3 py-2 text-sm focus:border-primary outline-none"
                                />
                            </div>
                            <div>
                                <label class="block font-medium text-on-surface mb-1">NIS / NSS / NDS</label>
                                <input 
                                    v-model="form.school_profile.nis_nss_nds"
                                    type="text"
                                    class="w-full bg-surface border border-outline-variant rounded-xl px-3 py-2 text-sm focus:border-primary outline-none"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block font-medium text-on-surface mb-1">Alamat Sekolah</label>
                            <input 
                                v-model="form.school_profile.alamat_sekolah"
                                type="text"
                                class="w-full bg-surface border border-outline-variant rounded-xl px-3 py-2 text-sm focus:border-primary outline-none"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-on-surface mb-1">Kelurahan / Desa</label>
                                <input 
                                    v-model="form.school_profile.kelurahan_desa"
                                    type="text"
                                    class="w-full bg-surface border border-outline-variant rounded-xl px-3 py-2 text-sm focus:border-primary outline-none"
                                />
                            </div>
                            <div>
                                <label class="block font-medium text-on-surface mb-1">Kecamatan</label>
                                <input 
                                    v-model="form.school_profile.kecamatan"
                                    type="text"
                                    class="w-full bg-surface border border-outline-variant rounded-xl px-3 py-2 text-sm focus:border-primary outline-none"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-on-surface mb-1">Kota / Kabupaten</label>
                                <input 
                                    v-model="form.school_profile.kota_kabupaten"
                                    type="text"
                                    class="w-full bg-surface border border-outline-variant rounded-xl px-3 py-2 text-sm focus:border-primary outline-none"
                                />
                            </div>
                            <div>
                                <label class="block font-medium text-on-surface mb-1">Provinsi</label>
                                <input 
                                    v-model="form.school_profile.provinsi"
                                    type="text"
                                    class="w-full bg-surface border border-outline-variant rounded-xl px-3 py-2 text-sm focus:border-primary outline-none"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-on-surface mb-1">Website</label>
                                <input 
                                    v-model="form.school_profile.website"
                                    type="text"
                                    class="w-full bg-surface border border-outline-variant rounded-xl px-3 py-2 text-sm focus:border-primary outline-none"
                                />
                            </div>
                            <div>
                                <label class="block font-medium text-on-surface mb-1">Email</label>
                                <input 
                                    v-model="form.school_profile.email"
                                    type="text"
                                    class="w-full bg-surface border border-outline-variant rounded-xl px-3 py-2 text-sm focus:border-primary outline-none"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block font-medium text-on-surface mb-1">Judul Kementerian (Header / Footer)</label>
                            <input 
                                v-model="form.school_profile.kementerian_title"
                                type="text"
                                class="w-full bg-surface border border-outline-variant rounded-xl px-3 py-2 text-sm focus:border-primary outline-none"
                            />
                        </div>
                    </div>
                </div>

                <!-- Section 2: Identitas Siswa -->
                <div v-show="activeEditorSection === 'signatory'" class="bg-surface-container-low p-6 rounded-2xl border border-outline-variant space-y-4">
                    <h3 class="font-bold text-base text-on-surface border-b border-outline-variant pb-2 flex items-center space-x-2">
                        <span class="material-symbols-outlined text-primary text-lg">badge</span>
                        <span>Pengaturan Penandatangan & Identitas Siswa</span>
                    </h3>

                    <div class="space-y-3 text-sm">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-on-surface mb-1">Tempat Penandatangan</label>
                                <input 
                                    v-model="form.signatory.tempat_penandatangan"
                                    type="text"
                                    placeholder="Karanganyar"
                                    class="w-full bg-surface border border-outline-variant rounded-xl px-3 py-2 text-sm focus:border-primary outline-none"
                                />
                            </div>
                            <div>
                                <label class="block font-medium text-on-surface mb-1">Jabatan Penandatangan</label>
                                <input 
                                    v-model="form.signatory.jabatan"
                                    type="text"
                                    placeholder="Kepala Sekolah"
                                    class="w-full bg-surface border border-outline-variant rounded-xl px-3 py-2 text-sm focus:border-primary outline-none"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block font-medium text-on-surface mb-1">Nama Kepala Sekolah</label>
                            <input 
                                v-model="form.signatory.nama_kepala_sekolah"
                                type="text"
                                placeholder="Nurul Choirul Janah, S.Pd."
                                class="w-full bg-surface border border-outline-variant rounded-xl px-3 py-2 text-sm focus:border-primary outline-none"
                            />
                        </div>

                        <div>
                            <label class="block font-medium text-on-surface mb-1">NIP (Opsional)</label>
                            <input 
                                v-model="form.signatory.nip"
                                type="text"
                                placeholder="-"
                                class="w-full bg-surface border border-outline-variant rounded-xl px-3 py-2 text-sm focus:border-primary outline-none"
                            />
                        </div>

                        <div class="pt-4 mt-4 border-t border-outline-variant">
                            <h4 class="font-bold text-sm text-on-surface mb-3 flex items-center space-x-1.5">
                                <span class="material-symbols-outlined text-base text-primary">visibility</span>
                                <span>Field yang Dicetak pada Identitas Siswa</span>
                            </h4>
                            <p class="text-[11px] text-on-surface-variant mb-4">Centang item yang ingin ditampilkan di lembar Identitas Peserta Didik. Nomor urut akan menyesuaikan secara otomatis.</p>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-64 overflow-y-auto pr-2">
                                <label class="flex items-center space-x-2 text-sm cursor-pointer p-2 hover:bg-surface-container rounded-lg transition-colors">
                                    <input type="checkbox" v-model="form.identity_visibility.nama_lengkap" class="w-4 h-4 rounded text-primary border-outline-variant focus:ring-primary">
                                    <span class="select-none">Nama Lengkap</span>
                                </label>
                                <label class="flex items-center space-x-2 text-sm cursor-pointer p-2 hover:bg-surface-container rounded-lg transition-colors">
                                    <input type="checkbox" v-model="form.identity_visibility.nomor_induk" class="w-4 h-4 rounded text-primary border-outline-variant focus:ring-primary">
                                    <span class="select-none">Nomor Induk / NISN</span>
                                </label>
                                <label class="flex items-center space-x-2 text-sm cursor-pointer p-2 hover:bg-surface-container rounded-lg transition-colors">
                                    <input type="checkbox" v-model="form.identity_visibility.tempat_tanggal_lahir" class="w-4 h-4 rounded text-primary border-outline-variant focus:ring-primary">
                                    <span class="select-none">Tempat, Tanggal Lahir</span>
                                </label>
                                <label class="flex items-center space-x-2 text-sm cursor-pointer p-2 hover:bg-surface-container rounded-lg transition-colors">
                                    <input type="checkbox" v-model="form.identity_visibility.jenis_kelamin" class="w-4 h-4 rounded text-primary border-outline-variant focus:ring-primary">
                                    <span class="select-none">Jenis Kelamin</span>
                                </label>
                                <label class="flex items-center space-x-2 text-sm cursor-pointer p-2 hover:bg-surface-container rounded-lg transition-colors">
                                    <input type="checkbox" v-model="form.identity_visibility.agama" class="w-4 h-4 rounded text-primary border-outline-variant focus:ring-primary">
                                    <span class="select-none">Agama</span>
                                </label>
                                <label class="flex items-center space-x-2 text-sm cursor-pointer p-2 hover:bg-surface-container rounded-lg transition-colors">
                                    <input type="checkbox" v-model="form.identity_visibility.status_keluarga" class="w-4 h-4 rounded text-primary border-outline-variant focus:ring-primary">
                                    <span class="select-none">Status dalam Keluarga</span>
                                </label>
                                <label class="flex items-center space-x-2 text-sm cursor-pointer p-2 hover:bg-surface-container rounded-lg transition-colors">
                                    <input type="checkbox" v-model="form.identity_visibility.anak_ke" class="w-4 h-4 rounded text-primary border-outline-variant focus:ring-primary">
                                    <span class="select-none">Anak ke</span>
                                </label>
                                <label class="flex items-center space-x-2 text-sm cursor-pointer p-2 hover:bg-surface-container rounded-lg transition-colors">
                                    <input type="checkbox" v-model="form.identity_visibility.alamat_peserta" class="w-4 h-4 rounded text-primary border-outline-variant focus:ring-primary">
                                    <span class="select-none">Alamat Peserta Didik</span>
                                </label>
                                <label class="flex items-center space-x-2 text-sm cursor-pointer p-2 hover:bg-surface-container rounded-lg transition-colors">
                                    <input type="checkbox" v-model="form.identity_visibility.nomor_telepon" class="w-4 h-4 rounded text-primary border-outline-variant focus:ring-primary">
                                    <span class="select-none">Nomor Telepon</span>
                                </label>
                                <label class="flex items-center space-x-2 text-sm cursor-pointer p-2 hover:bg-surface-container rounded-lg transition-colors">
                                    <input type="checkbox" v-model="form.identity_visibility.sekolah_asal" class="w-4 h-4 rounded text-primary border-outline-variant focus:ring-primary">
                                    <span class="select-none">Sekolah Asal</span>
                                </label>
                                <label class="flex items-center space-x-2 text-sm cursor-pointer p-2 hover:bg-surface-container rounded-lg transition-colors">
                                    <input type="checkbox" v-model="form.identity_visibility.diterima_di_sekolah" class="w-4 h-4 rounded text-primary border-outline-variant focus:ring-primary">
                                    <span class="select-none">Diterima di sekolah ini (Kelas & Tanggal)</span>
                                </label>
                                <label class="flex items-center space-x-2 text-sm cursor-pointer p-2 hover:bg-surface-container rounded-lg transition-colors">
                                    <input type="checkbox" v-model="form.identity_visibility.nama_orang_tua" class="w-4 h-4 rounded text-primary border-outline-variant focus:ring-primary">
                                    <span class="select-none">Nama Orang Tua</span>
                                </label>
                                <label class="flex items-center space-x-2 text-sm cursor-pointer p-2 hover:bg-surface-container rounded-lg transition-colors">
                                    <input type="checkbox" v-model="form.identity_visibility.alamat_orang_tua" class="w-4 h-4 rounded text-primary border-outline-variant focus:ring-primary">
                                    <span class="select-none">Alamat Orang Tua</span>
                                </label>
                                <label class="flex items-center space-x-2 text-sm cursor-pointer p-2 hover:bg-surface-container rounded-lg transition-colors">
                                    <input type="checkbox" v-model="form.identity_visibility.telepon_orang_tua" class="w-4 h-4 rounded text-primary border-outline-variant focus:ring-primary">
                                    <span class="select-none">Nomor Telepon Orang Tua</span>
                                </label>
                                <label class="flex items-center space-x-2 text-sm cursor-pointer p-2 hover:bg-surface-container rounded-lg transition-colors">
                                    <input type="checkbox" v-model="form.identity_visibility.pekerjaan_orang_tua" class="w-4 h-4 rounded text-primary border-outline-variant focus:ring-primary">
                                    <span class="select-none">Pekerjaan Orang Tua</span>
                                </label>
                                <label class="flex items-center space-x-2 text-sm cursor-pointer p-2 hover:bg-surface-container rounded-lg transition-colors">
                                    <input type="checkbox" v-model="form.identity_visibility.nama_wali" class="w-4 h-4 rounded text-primary border-outline-variant focus:ring-primary">
                                    <span class="select-none">Nama Wali</span>
                                </label>
                                <label class="flex items-center space-x-2 text-sm cursor-pointer p-2 hover:bg-surface-container rounded-lg transition-colors">
                                    <input type="checkbox" v-model="form.identity_visibility.alamat_wali" class="w-4 h-4 rounded text-primary border-outline-variant focus:ring-primary">
                                    <span class="select-none">Alamat Wali</span>
                                </label>
                                <label class="flex items-center space-x-2 text-sm cursor-pointer p-2 hover:bg-surface-container rounded-lg transition-colors">
                                    <input type="checkbox" v-model="form.identity_visibility.telepon_wali" class="w-4 h-4 rounded text-primary border-outline-variant focus:ring-primary">
                                    <span class="select-none">Nomor Telepon Wali</span>
                                </label>
                                <label class="flex items-center space-x-2 text-sm cursor-pointer p-2 hover:bg-surface-container rounded-lg transition-colors">
                                    <input type="checkbox" v-model="form.identity_visibility.pekerjaan_wali" class="w-4 h-4 rounded text-primary border-outline-variant focus:ring-primary">
                                    <span class="select-none">Pekerjaan Wali</span>
                                </label>
                            </div>
                        </div>


                    </div>
                </div>

                <!-- Section 3: Opsi Tampilan & Logo -->
                <div v-show="activeEditorSection === 'options'" class="bg-surface-container-low p-6 rounded-2xl border border-outline-variant space-y-4">
                    <h3 class="font-bold text-base text-on-surface border-b border-outline-variant pb-2 flex items-center space-x-2">
                        <span class="material-symbols-outlined text-primary text-lg">visibility</span>
                        <span>Opsi Tampilan & Logo Dokumen</span>
                    </h3>

                    <div class="space-y-4 text-sm">
                        <label class="flex items-center space-x-3 p-3 rounded-xl bg-surface border border-outline-variant cursor-pointer hover:bg-surface-container transition-colors">
                            <input 
                                type="checkbox"
                                v-model="form.options.show_tut_wuri_logo"
                                class="w-5 h-5 rounded text-primary focus:ring-primary"
                            />
                            <div>
                                <span class="font-semibold text-on-surface block">Tampilkan Lambang Tut Wuri Handayani</span>
                                <span class="text-xs text-on-surface-variant">Menampilkan lambang Tut Wuri Handayani di bagian atas Cover</span>
                            </div>
                        </label>

                        <label class="flex items-center space-x-3 p-3 rounded-xl bg-surface border border-outline-variant cursor-pointer hover:bg-surface-container transition-colors">
                            <input 
                                type="checkbox"
                                v-model="form.options.show_school_logo"
                                class="w-5 h-5 rounded text-primary focus:ring-primary"
                            />
                            <div>
                                <span class="font-semibold text-on-surface block">Tampilkan Logo Sekolah Ulil Albab</span>
                                <span class="text-xs text-on-surface-variant">Menampilkan lambang resmi SMP IT Ulil Albab di Cover</span>
                            </div>
                        </label>

                        <label class="flex items-center space-x-3 p-3 rounded-xl bg-surface border border-outline-variant cursor-pointer hover:bg-surface-container transition-colors">
                            <input 
                                type="checkbox"
                                v-model="form.options.show_photo_box"
                                class="w-5 h-5 rounded text-primary focus:ring-primary"
                            />
                            <div>
                                <span class="font-semibold text-on-surface block">Tampilkan Kotak Pas Foto 3x4</span>
                                <span class="text-xs text-on-surface-variant">Menyediakan bingkai kotak pas foto 3x4 cm di samping tanda tangan</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Section 4: Variable Tag Reference -->
                <div v-show="activeEditorSection === 'placeholders'" class="bg-surface-container-low p-6 rounded-2xl border border-outline-variant space-y-4">
                    <h3 class="font-bold text-base text-on-surface border-b border-outline-variant pb-2 flex items-center space-x-2">
                        <span class="material-symbols-outlined text-primary text-lg">code</span>
                        <span>Daftar Variabel Placeholder yang Didukung</span>
                    </h3>
                    <p class="text-xs text-on-surface-variant">
                        Tag di bawah ini akan digantikan secara otomatis dengan data siswa riil saat preview dan cetak dokumen:
                    </p>

                    <div class="grid grid-cols-1 gap-2 max-h-[360px] overflow-y-auto pr-1">
                        <div 
                            v-for="p in availablePlaceholders" 
                            :key="p.tag"
                            class="p-2.5 rounded-xl bg-surface border border-outline-variant flex items-center justify-between"
                        >
                            <code class="font-mono text-xs font-bold text-primary bg-primary-container px-2 py-1 rounded">
                                {{ p.tag }}
                            </code>
                            <span class="text-xs text-on-surface-variant text-right">{{ p.desc }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Live Interactive Preview (7 cols) -->
            <div class="lg:col-span-7 flex flex-col bg-surface-container-low rounded-2xl border border-outline-variant overflow-hidden">
                <!-- Preview Header Bar -->
                <div class="p-4 bg-surface-container border-b border-outline-variant flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="font-bold text-sm text-on-surface">Live Preview Cetak</span>
                    </div>

                    <!-- Page Picker & Zoom -->
                    <div class="flex items-center space-x-2 text-xs">
                        <select 
                            v-model="previewPage" 
                            class="bg-surface border border-outline-variant rounded-lg px-2.5 py-1 text-xs text-on-surface outline-none"
                        >
                            <option value="all">Semua Halaman (3)</option>
                            <option value="cover">Halaman 1 (Cover)</option>
                            <option value="school_profile">Halaman 2 (Profil Sekolah)</option>
                            <option value="identity">Halaman 3 (Identitas Siswa)</option>
                        </select>

                        <div class="flex items-center space-x-1 bg-surface rounded-lg px-2 py-1 border border-outline-variant">
                            <button @click="zoomLevel = Math.max(40, zoomLevel - 10)" class="hover:text-primary cursor-pointer px-1">-</button>
                            <span class="w-8 text-center font-mono">{{ zoomLevel }}%</span>
                            <button @click="zoomLevel = Math.min(100, zoomLevel + 10)" class="hover:text-primary cursor-pointer px-1">+</button>
                        </div>
                    </div>
                </div>

                <!-- Preview Canvas -->
                <div class="flex-grow p-6 overflow-y-auto bg-slate-200/90 dark:bg-slate-950/80 flex justify-center min-h-[550px]">
                    <div 
                        class="transition-all duration-200 origin-top"
                        :style="{ transform: `scale(${zoomLevel / 100})`, transformOrigin: 'top center' }"
                    >
                        <StudentDocumentSheet 
                            :student="sampleStudent"
                            :template="livePreviewTemplate"
                            :pages-to-print="previewPage === 'all' ? ['cover', 'school_profile', 'identity'] : [previewPage]"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
