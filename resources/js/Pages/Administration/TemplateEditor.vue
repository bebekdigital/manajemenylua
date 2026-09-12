<script setup>
import { ref, reactive, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import StudentDocumentSheet from '@/Components/Administration/StudentDocumentSheet.vue';

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

const activeSection = ref('school'); // 'school' | 'signatory' | 'options' | 'variables'
const isSubmitting = ref(false);
const previewPage = ref('cover'); // 'cover' | 'school_profile' | 'identity'
const zoomLevel = ref(65);

// Form state
const form = reactive({
    school_profile: {
        nama_sekolah: props.template?.school_profile?.nama_sekolah || '',
        jenjang_tingkat: props.template?.school_profile?.jenjang_tingkat || 'SEKOLAH MENENGAH PERTAMA',
        jenjang_singkat: props.template?.school_profile?.jenjang_singkat || '( SMP )',
        kementerian_title: props.template?.school_profile?.kementerian_title || 'KEMENTERIAN PENDIDIKAN DASAR DAN MENENGAH REPUBLIK INDONESIA',
        npsn: props.template?.school_profile?.npsn || '',
        nis_nss_nds: props.template?.school_profile?.nis_nss_nds || '-',
        alamat_sekolah: props.template?.school_profile?.alamat_sekolah || '',
        kelurahan_desa: props.template?.school_profile?.kelurahan_desa || '',
        kecamatan: props.template?.school_profile?.kecamatan || '',
        kota_kabupaten: props.template?.school_profile?.kota_kabupaten || '',
        provinsi: props.template?.school_profile?.provinsi || '',
        website: props.template?.school_profile?.website || '',
        email: props.template?.school_profile?.email || '',
    },
    signatory: {
        tempat_titimangsa: props.template?.signatory?.tempat_titimangsa || 'Karanganyar',
        tanggal_titimangsa: props.template?.signatory?.tanggal_titimangsa || '{{Tanggal Diterima}}',
        jabatan: props.template?.signatory?.jabatan || 'Kepala Sekolah',
        nama_kepala_sekolah: props.template?.signatory?.nama_kepala_sekolah || 'Nurul Choirul Janah, S.Pd.',
        nip: props.template?.signatory?.nip || '',
    },
    options: {
        show_tut_wuri_logo: props.template?.options?.show_tut_wuri_logo ?? true,
        show_school_logo: props.template?.options?.show_school_logo ?? true,
        show_photo_box: props.template?.options?.show_photo_box ?? true,
        tut_wuri_logo_path: props.template?.options?.tut_wuri_logo_path ?? null,
        school_logo_path: props.template?.options?.school_logo_path ?? null,
        tut_wuri_logo_file: null,
        school_logo_file: null,
    },
    template_content: props.template?.template_content || null,
});

// Live preview template
const liveTemplate = computed(() => {
    // Generate object URLs for file previews if available
    const previewOptions = { ...form.options };
    if (form.options.tut_wuri_logo_file) {
        previewOptions.tut_wuri_logo_preview = URL.createObjectURL(form.options.tut_wuri_logo_file);
    }
    if (form.options.school_logo_file) {
        previewOptions.school_logo_preview = URL.createObjectURL(form.options.school_logo_file);
    }
    
    return {
        school_profile: form.school_profile,
        signatory: form.signatory,
        options: previewOptions,
        template_content: form.template_content,
    };
});

// Sample student for preview
const previewStudent = computed(() => {
    return props.students[0] || {
        nama: 'Ahmad Fauzi Rahman',
        nama_kapital: 'AHMAD FAUZI RAHMAN',
        nisn: '0051234001',
        nis: '10231001',
        jk: 'Laki-laki',
        tempat_lahir: 'Karanganyar',
        tanggal_lahir: '15 Maret 2011',
        agama: 'Islam',
        status_keluarga: 'Anak Kandung',
        anak_ke: '1',
        alamat: 'Jl. Lawu No. 12, Colomadu, Karanganyar',
        no_wa: '081234567001',
        sekolah_asal: 'SDIT Ulil Albab Karanganyar',
        kelas_diterima: 'VII A',
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

// Sections config
const sections = [
    { id: 'school', label: 'Profil Sekolah', icon: 'domain' },
    { id: 'signatory', label: 'Penandatangan', icon: 'draw' },
    { id: 'options', label: 'Opsi Tampilan', icon: 'tune' },
];

// Page options for preview
const previewPages = [
    { value: 'cover', label: 'Cover' },
    { value: 'school_profile', label: 'Profil Sekolah' },
    { value: 'identity', label: 'Identitas Siswa' },
];



const saveTemplate = () => {
    isSubmitting.value = true;
    
    // Setup FormData for file uploads
    const formData = new FormData();
    // School profile
    Object.keys(form.school_profile).forEach(key => {
        formData.append(`school_profile[${key}]`, form.school_profile[key] || '');
    });
    // Signatory
    Object.keys(form.signatory).forEach(key => {
        formData.append(`signatory[${key}]`, form.signatory[key] || '');
    });
    // Options
    Object.keys(form.options).forEach(key => {
        if (key !== 'tut_wuri_logo_file' && key !== 'school_logo_file') {
            // Convert boolean to 1/0 for safe transport
            const val = typeof form.options[key] === 'boolean' ? (form.options[key] ? 1 : 0) : form.options[key];
            if (val !== null) {
                formData.append(`options[${key}]`, val);
            }
        }
    });
    
    // File uploads
    if (form.options.tut_wuri_logo_file) {
        formData.append('tut_wuri_logo_file', form.options.tut_wuri_logo_file);
    }
    if (form.options.school_logo_file) {
        formData.append('school_logo_file', form.options.school_logo_file);
    }
    
    router.post('/administration/identity-document/template', formData, {
        preserveScroll: true,
        onSuccess: () => router.visit('/administration/identity-document'),
        onFinish: () => { isSubmitting.value = false; },
    });
};

const resetTemplate = () => {
    if (!confirm('Kembalikan template ke nilai bawaan? Semua perubahan akan hilang.')) return;
    isSubmitting.value = true;
    router.post('/administration/identity-document/template/reset', {}, {
        preserveScroll: true,
        onSuccess: () => {
            Object.assign(form.school_profile, props.template.school_profile);
            Object.assign(form.signatory, props.template.signatory);
            Object.assign(form.options, props.template.options);
            form.template_content = props.template.template_content;
        },
        onFinish: () => { isSubmitting.value = false; },
    });
};
</script>

<template>
    <div class="flex-grow min-h-screen bg-background">
        <Head title="Pengaturan Template Berkas" />
        <div class="max-w-7xl mx-auto px-6 py-8">
            
            <!-- Section 1: Header/Aksi -->
            <div class="bg-surface rounded-2xl shadow-sm border border-outline-variant mb-6">
                <div class="px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 bg-primary-container text-on-primary-container rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-lg">tune</span>
                        </div>
                        <div>
                            <h2 class="font-bold text-on-surface text-base leading-tight">Pengaturan Template Dokumen</h2>
                            <p class="text-xs text-on-surface-variant">Perubahan akan berlaku pada semua dokumen yang dicetak</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button
                            @click="resetTemplate"
                            :disabled="isSubmitting"
                            class="px-3 py-1.5 text-sm font-medium text-on-surface-variant hover:bg-surface-container rounded-xl border border-outline-variant transition-colors cursor-pointer disabled:opacity-50"
                        >
                            Reset Default
                        </button>
                        <button
                            @click="saveTemplate"
                            :disabled="isSubmitting"
                            class="px-4 py-1.5 bg-primary text-on-primary text-sm font-semibold rounded-xl hover:bg-primary/90 transition-colors active:scale-95 cursor-pointer disabled:opacity-50 flex items-center space-x-1.5"
                        >
                            <span class="material-symbols-outlined text-base">{{ isSubmitting ? 'sync' : 'save' }}</span>
                            <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan' }}</span>
                        </button>
                        <Link
                            href="/administration/identity-document"
                            class="p-2 text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high rounded-xl transition-colors cursor-pointer"
                        >
                            <span class="material-symbols-outlined">close</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Section 2: Tab & Form -->
            <div class="bg-surface rounded-2xl shadow-sm border border-outline-variant mb-6">
                <!-- Tab Header -->
                <div class="flex items-center gap-1 px-6 pt-4 border-b border-outline-variant bg-surface-container-low/50 rounded-t-2xl">
                    <button
                        v-for="sec in sections"
                        :key="sec.id"
                        @click="activeSection = sec.id"
                        :class="[
                            'flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-t-xl border-b-2 -mb-px transition-all cursor-pointer',
                            activeSection === sec.id
                                ? 'border-primary text-primary bg-primary-container/20'
                                : 'border-transparent text-on-surface-variant hover:text-on-surface hover:bg-surface-container'
                        ]"
                    >
                        <span class="material-symbols-outlined text-base">{{ sec.icon }}</span>
                        <span>{{ sec.label }}</span>
                    </button>
                </div>

                <!-- Form Content -->
                <div class="p-6">
                    <!-- SECTION: Profil Sekolah -->
                    <div v-show="activeSection === 'school'" class="space-y-5 max-w-3xl">
                        <div>
                            <h3 class="font-bold text-on-surface mb-0.5">Identitas Lembaga & Sekolah</h3>
                            <p class="text-xs text-on-surface-variant mb-4">Informasi ini ditampilkan pada halaman Profil Sekolah dan Cover dokumen.</p>
                        </div>

                        <div class="space-y-4 text-sm">
                            <div>
                                <label class="block font-medium text-on-surface mb-1">Nama Sekolah <span class="text-error">*</span></label>
                                <input v-model="form.school_profile.nama_sekolah" type="text" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary outline-none text-on-surface" />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-medium text-on-surface mb-1">Jenjang Tingkat</label>
                                    <input v-model="form.school_profile.jenjang_tingkat" type="text" placeholder="SEKOLAH MENENGAH PERTAMA" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2 focus:border-primary outline-none text-on-surface" />
                                </div>
                                <div>
                                    <label class="block font-medium text-on-surface mb-1">Jenjang Singkat</label>
                                    <input v-model="form.school_profile.jenjang_singkat" type="text" placeholder="( SMP )" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2 focus:border-primary outline-none text-on-surface" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-medium text-on-surface mb-1">NPSN</label>
                                    <input v-model="form.school_profile.npsn" type="text" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2 focus:border-primary outline-none text-on-surface" />
                                </div>
                                <div>
                                    <label class="block font-medium text-on-surface mb-1">NIS / NSS / NDS</label>
                                    <input v-model="form.school_profile.nis_nss_nds" type="text" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2 focus:border-primary outline-none text-on-surface" />
                                </div>
                            </div>
                            <div>
                                <label class="block font-medium text-on-surface mb-1">Alamat Sekolah</label>
                                <input v-model="form.school_profile.alamat_sekolah" type="text" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2 focus:border-primary outline-none text-on-surface" />
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="block font-medium text-on-surface mb-1">Kelurahan / Desa</label>
                                    <input v-model="form.school_profile.kelurahan_desa" type="text" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2 focus:border-primary outline-none text-on-surface" />
                                </div>
                                <div>
                                    <label class="block font-medium text-on-surface mb-1">Kecamatan</label>
                                    <input v-model="form.school_profile.kecamatan" type="text" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2 focus:border-primary outline-none text-on-surface" />
                                </div>
                                <div>
                                    <label class="block font-medium text-on-surface mb-1">Kota / Kabupaten</label>
                                    <input v-model="form.school_profile.kota_kabupaten" type="text" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2 focus:border-primary outline-none text-on-surface" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-medium text-on-surface mb-1">Provinsi</label>
                                    <input v-model="form.school_profile.provinsi" type="text" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2 focus:border-primary outline-none text-on-surface" />
                                </div>
                                <div>
                                    <label class="block font-medium text-on-surface mb-1">Website</label>
                                    <input v-model="form.school_profile.website" type="text" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2 focus:border-primary outline-none text-on-surface" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-medium text-on-surface mb-1">Email</label>
                                    <input v-model="form.school_profile.email" type="text" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2 focus:border-primary outline-none text-on-surface" />
                                </div>
                                <div>
                                    <label class="block font-medium text-on-surface mb-1">Judul Kementerian (Cover)</label>
                                    <textarea v-model="form.school_profile.kementerian_title" rows="2" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2 focus:border-primary outline-none text-on-surface"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION: Penandatangan -->
                    <div v-show="activeSection === 'signatory'" class="space-y-5 max-w-2xl">
                        <div>
                            <h3 class="font-bold text-on-surface mb-0.5">Data Penandatangan & Titimangsa</h3>
                            <p class="text-xs text-on-surface-variant mb-4">Ditampilkan di bagian bawah halaman Identitas Siswa.</p>
                        </div>
                        <div class="space-y-4 text-sm">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-medium text-on-surface mb-1">Kota Titimangsa</label>
                                    <input v-model="form.signatory.tempat_titimangsa" type="text" placeholder="Karanganyar" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2 focus:border-primary outline-none text-on-surface" />
                                </div>
                                <div>
                                    <label class="block font-medium text-on-surface mb-1">Tanggal Titimangsa</label>
                                    <input v-model="form.signatory.tanggal_titimangsa" type="text" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2 focus:border-primary outline-none text-on-surface" />
                                    <p class="mt-1 text-[11px] text-on-surface-variant">
                                         Gunakan <code v-text="'{{Tanggal Diterima}}'" class="bg-primary-container/50 px-1 rounded text-primary text-[10px]"></code> untuk otomatis.
                                    </p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-medium text-on-surface mb-1">Jabatan Penandatangan</label>
                                    <input v-model="form.signatory.jabatan" type="text" placeholder="Kepala Sekolah" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2 focus:border-primary outline-none text-on-surface" />
                                </div>
                                <div>
                                    <label class="block font-medium text-on-surface mb-1">NIP Kepala Sekolah</label>
                                    <input v-model="form.signatory.nip" type="text" placeholder="Kosongkan jika tidak ada NIP" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2 focus:border-primary outline-none text-on-surface" />
                                </div>
                            </div>
                            <div>
                                <label class="block font-medium text-on-surface mb-1">Nama Kepala Sekolah</label>
                                <input v-model="form.signatory.nama_kepala_sekolah" type="text" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2 focus:border-primary outline-none text-on-surface" />
                            </div>
                        </div>
                    </div>

                    <!-- SECTION: Opsi Tampilan -->
                    <div v-show="activeSection === 'options'" class="space-y-5 max-w-2xl">
                        <div>
                            <h3 class="font-bold text-on-surface mb-0.5">Opsi Tampilan Dokumen</h3>
                            <p class="text-xs text-on-surface-variant mb-4">Atur elemen visual yang ditampilkan pada dokumen cetak.</p>
                        </div>
                        <div class="space-y-3">
                            <div class="space-y-2">
                                <label class="flex items-center justify-between p-4 bg-surface-container-low rounded-2xl border border-outline-variant cursor-pointer hover:border-primary transition-colors group">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 bg-blue-100 text-blue-700 rounded-xl flex items-center justify-center overflow-hidden">
                                            <img v-if="liveTemplate.options.tut_wuri_logo_preview || liveTemplate.options.tut_wuri_logo_path" :src="liveTemplate.options.tut_wuri_logo_preview || ('/storage/' + liveTemplate.options.tut_wuri_logo_path)" class="w-full h-full object-contain" />
                                            <span v-else class="material-symbols-outlined text-lg">military_tech</span>
                                        </div>
                                        <div>
                                            <span class="block font-semibold text-sm text-on-surface">Lambang Kementrian (Atas)</span>
                                            <span class="text-xs text-on-surface-variant">Tampil di bagian atas Cover</span>
                                        </div>
                                    </div>
                                    <div :class="['w-11 h-6 rounded-full transition-colors relative cursor-pointer', form.options.show_tut_wuri_logo ? 'bg-primary' : 'bg-surface-container-high']" @click.prevent="form.options.show_tut_wuri_logo = !form.options.show_tut_wuri_logo">
                                        <div :class="['absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform', form.options.show_tut_wuri_logo ? 'translate-x-5' : 'translate-x-0.5']"></div>
                                    </div>
                                </label>
                                <div class="pl-16 pr-4">
                                    <input type="file" accept="image/*" @change="e => form.options.tut_wuri_logo_file = e.target.files[0]" class="text-xs text-on-surface-variant file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary-container file:text-on-primary-container hover:file:bg-primary-container/80 cursor-pointer" />
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="flex items-center justify-between p-4 bg-surface-container-low rounded-2xl border border-outline-variant cursor-pointer hover:border-primary transition-colors group">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 bg-emerald-100 text-emerald-700 rounded-xl flex items-center justify-center overflow-hidden">
                                            <img v-if="liveTemplate.options.school_logo_preview || liveTemplate.options.school_logo_path" :src="liveTemplate.options.school_logo_preview || ('/storage/' + liveTemplate.options.school_logo_path)" class="w-full h-full object-contain" />
                                            <span v-else class="material-symbols-outlined text-lg">account_balance</span>
                                        </div>
                                        <div>
                                            <span class="block font-semibold text-sm text-on-surface">Logo Sekolah (Tengah)</span>
                                            <span class="text-xs text-on-surface-variant">Tampil di tengah Cover</span>
                                        </div>
                                    </div>
                                    <div :class="['w-11 h-6 rounded-full transition-colors relative cursor-pointer', form.options.show_school_logo ? 'bg-primary' : 'bg-surface-container-high']" @click.prevent="form.options.show_school_logo = !form.options.show_school_logo">
                                        <div :class="['absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform', form.options.show_school_logo ? 'translate-x-5' : 'translate-x-0.5']"></div>
                                    </div>
                                </label>
                                <div class="pl-16 pr-4">
                                    <input type="file" accept="image/*" @change="e => form.options.school_logo_file = e.target.files[0]" class="text-xs text-on-surface-variant file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary-container file:text-on-primary-container hover:file:bg-primary-container/80 cursor-pointer" />
                                </div>
                            </div>
                            <label class="flex items-center justify-between p-4 bg-surface-container-low rounded-2xl border border-outline-variant cursor-pointer hover:border-primary transition-colors group">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 bg-orange-100 text-orange-700 rounded-xl flex items-center justify-center">
                                        <span class="material-symbols-outlined text-lg">person</span>
                                    </div>
                                    <div>
                                        <span class="block font-semibold text-sm text-on-surface">Kotak Pas Foto 3×4</span>
                                        <span class="text-xs text-on-surface-variant">Kotak foto di halaman Identitas Siswa</span>
                                    </div>
                                </div>
                                <div :class="['w-11 h-6 rounded-full transition-colors relative cursor-pointer', form.options.show_photo_box ? 'bg-primary' : 'bg-surface-container-high']" @click="form.options.show_photo_box = !form.options.show_photo_box">
                                    <div :class="['absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform', form.options.show_photo_box ? 'translate-x-5' : 'translate-x-0.5']"></div>
                                </div>
                            </label>
                        </div>
                    </div>


                </div>
            </div>

            <!-- Section 3: Preview -->
            <div class="bg-surface rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
                <!-- Preview Toolbar -->
                <div class="px-6 py-4 border-b border-outline-variant bg-surface-container flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-sm font-bold text-on-surface">Preview Dokumen</span>
                        <span class="text-xs text-on-surface-variant hidden sm:inline">— Pratinjau langsung dari pengaturan</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="relative">
                            <select v-model="previewPage" class="appearance-none text-sm bg-surface border border-outline-variant rounded-lg pl-3 pr-8 py-1.5 outline-none cursor-pointer text-on-surface">
                                <option v-for="p in previewPages" :key="p.value" :value="p.value">{{ p.label }}</option>
                            </select>
                            <span class="material-symbols-outlined absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant" style="font-size:18px;">expand_more</span>
                        </div>
                        <div class="flex items-center space-x-0.5 text-xs text-on-surface-variant bg-surface border border-outline-variant rounded-lg px-1 py-1">
                            <button @click="zoomLevel = Math.max(30, zoomLevel - 10)" class="p-1 hover:text-primary cursor-pointer text-on-surface">
                                <span class="material-symbols-outlined text-sm">remove</span>
                            </button>
                            <span class="w-10 text-center font-mono text-on-surface">{{ zoomLevel }}%</span>
                            <button @click="zoomLevel = Math.min(100, zoomLevel + 10)" class="p-1 hover:text-primary cursor-pointer text-on-surface">
                                <span class="material-symbols-outlined text-sm">add</span>
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Preview Canvas -->
                <div class="bg-slate-200/80 p-8 overflow-auto flex justify-center items-start min-h-[500px]">
                    <div :style="{ transform: `scale(${zoomLevel / 100})`, transformOrigin: 'top center', width: '210mm' }">
                        <StudentDocumentSheet
                            :student="previewStudent"
                            :template="liveTemplate"
                            :pages-to-print="[previewPage]"
                        />
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
