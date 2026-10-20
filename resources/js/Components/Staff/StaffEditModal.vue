<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    employee: {
        type: Object,
        required: true,
    },
    selectedAcademicYear: {
        type: Object,
        default: null,
    },
    availableUnits: {
        type: Array,
        default: () => [],
    },
    initialTab: {
        type: String,
        default: 'identitas',
    },
});

const emit = defineEmits(['close', 'success']);

const activeModalTab = ref('identitas');

const modalTabs = [
    { id: 'identitas', label: 'Identitas & Kontak', icon: 'badge' },
    { id: 'alamat', label: 'Alamat & Tinggal', icon: 'home' },
    { id: 'keluarga', label: 'Keluarga', icon: 'family_restroom' },
    { id: 'kepegawaian', label: 'Kepegawaian', icon: 'work' },
    { id: 'pendidikan', label: 'Pendidikan', icon: 'school' },
];

const form = useForm({
    // Identitas
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

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen && props.employee) {
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

            if (props.initialTab) {
                activeModalTab.value = props.initialTab;
            }
            form.clearErrors();
        }
    },
    { immediate: true }
);

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

function submit() {
    form.put(`/staff/${props.employee.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
            emit('close');
        },
    });
}
</script>

<template>
    <Teleport to="body">
        <Transition name="modal-backdrop">
            <div
                v-if="show"
                class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-3 sm:p-6 overflow-y-auto"
                @click.self="$emit('close')"
            >
                <Transition name="modal-content">
                    <div
                        v-if="show"
                        class="bg-surface-container-lowest rounded-3xl shadow-2xl w-full max-w-4xl border border-outline-variant/30 overflow-hidden flex flex-col max-h-[90vh] my-auto animate-in"
                    >
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between px-6 py-4.5 border-b border-outline-variant/30 bg-surface-container-low shrink-0">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shadow-2xs">
                                    <span class="material-symbols-outlined text-[22px]">edit_square</span>
                                </div>
                                <div>
                                    <h3 class="text-base sm:text-lg font-bold text-on-surface leading-snug">
                                        Edit Data Pegawai
                                    </h3>
                                    <p class="text-xs text-on-surface-variant">
                                        {{ employee.nama }} {{ employee.nipy ? `· NIPY: ${employee.nipy}` : '' }}
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="$emit('close')"
                                class="p-2 rounded-full text-on-surface-variant hover:bg-surface-variant hover:text-on-surface transition-colors cursor-pointer"
                                title="Tutup modal"
                            >
                                <span class="material-symbols-outlined text-xl">close</span>
                            </button>
                        </div>

                        <!-- Modal Tabs Navigation -->
                        <div class="flex border-b border-outline-variant/30 px-6 bg-surface-container-low/60 overflow-x-auto shrink-0 gap-1">
                            <button
                                v-for="tab in modalTabs"
                                :key="tab.id"
                                type="button"
                                @click="activeModalTab = tab.id"
                                :class="[
                                    'flex items-center gap-2 px-3.5 py-2.5 text-xs sm:text-sm font-semibold transition-all border-b-2 whitespace-nowrap cursor-pointer',
                                    activeModalTab === tab.id
                                        ? 'text-primary border-primary bg-primary/5'
                                        : 'text-on-surface-variant border-transparent hover:text-on-surface hover:border-outline-variant/40'
                                ]"
                            >
                                <span class="material-symbols-outlined text-[18px]">{{ tab.icon }}</span>
                                <span>{{ tab.label }}</span>
                            </button>
                        </div>

                        <!-- Form Content (Scrollable) -->
                        <form @submit.prevent="submit" class="flex-1 overflow-y-auto p-6 space-y-6">

                            <!-- TAB 1: IDENTITAS & KONTAK -->
                            <div v-show="activeModalTab === 'identitas'" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Nama Lengkap -->
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                            Nama Lengkap Beserta Gelar <span class="text-error">*</span>
                                        </label>
                                        <input
                                            v-model="form.nama"
                                            type="text"
                                            required
                                            placeholder="Contoh: Ahmad Fauzi, S.Pd"
                                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                            :class="{ 'border-error': form.errors.nama }"
                                        />
                                        <div v-if="form.errors.nama" class="text-error text-xs mt-1">{{ form.errors.nama }}</div>
                                    </div>

                                    <!-- NIPY -->
                                    <div>
                                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                            NIPY
                                        </label>
                                        <input
                                            v-model="form.nipy"
                                            type="text"
                                            placeholder="Nomor Induk Pegawai Yayasan"
                                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all font-mono"
                                            :class="{ 'border-error': form.errors.nipy }"
                                        />
                                        <div v-if="form.errors.nipy" class="text-error text-xs mt-1">{{ form.errors.nipy }}</div>
                                    </div>

                                    <!-- NIK -->
                                    <div>
                                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                            NIK (Nomor Induk Kependudukan)
                                        </label>
                                        <input
                                            v-model="form.nik"
                                            type="text"
                                            placeholder="16 digit NIK sesuai KTP"
                                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all font-mono"
                                            :class="{ 'border-error': form.errors.nik }"
                                        />
                                        <div v-if="form.errors.nik" class="text-error text-xs mt-1">{{ form.errors.nik }}</div>
                                    </div>

                                    <!-- Jenis Kelamin -->
                                    <div>
                                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                            Jenis Kelamin
                                        </label>
                                        <select
                                            v-model="form.jk"
                                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                                            :class="{ 'border-error': form.errors.jk }"
                                        >
                                            <option value="">-- Pilih Jenis Kelamin --</option>
                                            <option value="L">Laki-laki (L)</option>
                                            <option value="P">Perempuan (P)</option>
                                        </select>
                                        <div v-if="form.errors.jk" class="text-error text-xs mt-1">{{ form.errors.jk }}</div>
                                    </div>

                                    <!-- No. WhatsApp -->
                                    <div>
                                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                            Nomor WhatsApp
                                        </label>
                                        <input
                                            v-model="form.no_wa"
                                            type="text"
                                            placeholder="Contoh: 081234567890"
                                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                            :class="{ 'border-error': form.errors.no_wa }"
                                        />
                                        <div v-if="form.errors.no_wa" class="text-error text-xs mt-1">{{ form.errors.no_wa }}</div>
                                    </div>

                                    <!-- Tempat Lahir -->
                                    <div>
                                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                            Tempat Lahir
                                        </label>
                                        <input
                                            v-model="form.tempat_lahir"
                                            type="text"
                                            placeholder="Kota / Kabupaten Kelahiran"
                                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                            :class="{ 'border-error': form.errors.tempat_lahir }"
                                        />
                                        <div v-if="form.errors.tempat_lahir" class="text-error text-xs mt-1">{{ form.errors.tempat_lahir }}</div>
                                    </div>

                                    <!-- Tanggal Lahir -->
                                    <div>
                                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                            Tanggal Lahir
                                        </label>
                                        <input
                                            v-model="form.tanggal_lahir"
                                            type="date"
                                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                                            :class="{ 'border-error': form.errors.tanggal_lahir }"
                                        />
                                        <div v-if="form.errors.tanggal_lahir" class="text-error text-xs mt-1">{{ form.errors.tanggal_lahir }}</div>
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                            Email
                                        </label>
                                        <input
                                            v-model="form.email"
                                            type="email"
                                            placeholder="nama@email.com"
                                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                            :class="{ 'border-error': form.errors.email }"
                                        />
                                        <div v-if="form.errors.email" class="text-error text-xs mt-1">{{ form.errors.email }}</div>
                                    </div>

                                    <!-- Skill / Keahlian -->
                                    <div>
                                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                            Skill / Keahlian
                                        </label>
                                        <input
                                            v-model="form.skill"
                                            type="text"
                                            placeholder="Contoh: Matematika, Desain Grafis, IT"
                                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                            :class="{ 'border-error': form.errors.skill }"
                                        />
                                        <div v-if="form.errors.skill" class="text-error text-xs mt-1">{{ form.errors.skill }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 2: ALAMAT & TEMPAT TINGGAL -->
                            <div v-show="activeModalTab === 'alamat'" class="space-y-6">
                                <!-- Section Alamat Domisili -->
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

                                <!-- Section Alamat Tempat Tinggal -->
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
                            </div>

                            <!-- TAB 3: KELUARGA & KONTAK DARURAT -->
                            <div v-show="activeModalTab === 'keluarga'" class="space-y-6">
                                <!-- Status Pernikahan -->
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

                                <!-- Data Orangtua -->
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
                                                placeholder="Alamat lengkap tempat tinggal orangtua"
                                                class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Kontak Darurat -->
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
                            </div>

                            <!-- TAB 4: KEPEGAWAIAN -->
                            <div v-show="activeModalTab === 'kepegawaian'" class="space-y-4">
                                <!-- Banner Info Tahun Ajaran -->
                                <div class="p-3.5 bg-primary/10 border border-primary/20 rounded-2xl flex items-center gap-3">
                                    <span class="material-symbols-outlined text-primary text-xl shrink-0">info</span>
                                    <div class="text-xs text-on-surface-variant">
                                        Mengatur status kepegawaian untuk Tahun Ajaran aktif:
                                        <strong class="text-primary font-bold">
                                            {{ selectedAcademicYear ? `${selectedAcademicYear.name} (${selectedAcademicYear.semester})` : 'Tahun Ajaran Aktif' }}
                                        </strong>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Unit Kerja -->
                                    <div>
                                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                            Unit Kerja
                                        </label>
                                        <input
                                            v-model="form.unit_kerja"
                                            list="unitOptions"
                                            type="text"
                                            placeholder="Pilih atau ketik unit kerja"
                                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                            :class="{ 'border-error': form.errors.unit_kerja }"
                                        />
                                        <datalist id="unitOptions">
                                            <option v-for="unit in availableUnits" :key="unit" :value="unit" />
                                        </datalist>
                                        <div v-if="form.errors.unit_kerja" class="text-error text-xs mt-1">{{ form.errors.unit_kerja }}</div>
                                    </div>

                                    <!-- Jabatan -->
                                    <div>
                                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                            Jabatan
                                        </label>
                                        <input
                                            v-model="form.jabatan"
                                            type="text"
                                            placeholder="Contoh: Guru Kelas, Kepala Sekolah, Tata Usaha"
                                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                            :class="{ 'border-error': form.errors.jabatan }"
                                        />
                                        <div v-if="form.errors.jabatan" class="text-error text-xs mt-1">{{ form.errors.jabatan }}</div>
                                    </div>

                                    <!-- Jenjang Kepegawaian -->
                                    <div>
                                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                            Jenjang Kepegawaian
                                        </label>
                                        <select
                                            v-model="form.jenjang_kepegawaian"
                                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                                            :class="{ 'border-error': form.errors.jenjang_kepegawaian }"
                                        >
                                            <option value="">-- Pilih Jenjang --</option>
                                            <option value="GTY">GTY (Guru Tetap Yayasan)</option>
                                            <option value="GTT">GTT (Guru Tidak Tetap)</option>
                                            <option value="PTY">PTY (Pegawai Tetap Yayasan)</option>
                                            <option value="PTT">PTT (Pegawai Tidak Tetap)</option>
                                        </select>
                                        <div v-if="form.errors.jenjang_kepegawaian" class="text-error text-xs mt-1">{{ form.errors.jenjang_kepegawaian }}</div>
                                    </div>

                                    <!-- Jenis Kepegawaian -->
                                    <div>
                                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                            Jenis Kepegawaian
                                        </label>
                                        <input
                                            v-model="form.jenis_kepegawaian"
                                            type="text"
                                            placeholder="Contoh: Tenaga Pendidik / Tenaga Kependidikan"
                                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                            :class="{ 'border-error': form.errors.jenis_kepegawaian }"
                                        />
                                        <div v-if="form.errors.jenis_kepegawaian" class="text-error text-xs mt-1">{{ form.errors.jenis_kepegawaian }}</div>
                                    </div>

                                    <!-- Status Keaktifan -->
                                    <div>
                                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                            Status Keaktifan
                                        </label>
                                        <select
                                            v-model="form.status_keaktifan"
                                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                                            :class="{ 'border-error': form.errors.status_keaktifan }"
                                        >
                                            <option value="Aktif">Aktif</option>
                                            <option value="Non-Aktif">Non-Aktif</option>
                                            <option value="Pensiun">Pensiun</option>
                                            <option value="Cuti">Cuti</option>
                                        </select>
                                        <div v-if="form.errors.status_keaktifan" class="text-error text-xs mt-1">{{ form.errors.status_keaktifan }}</div>
                                    </div>

                                    <!-- Keterangan Tidak Aktif -->
                                    <div>
                                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                            Keterangan Status (Opsional)
                                        </label>
                                        <input
                                            v-model="form.keterangan_tidak_aktif"
                                            type="text"
                                            placeholder="Alasan tidak aktif / cuti / pensiun"
                                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                            :class="{ 'border-error': form.errors.keterangan_tidak_aktif }"
                                        />
                                        <div v-if="form.errors.keterangan_tidak_aktif" class="text-error text-xs mt-1">{{ form.errors.keterangan_tidak_aktif }}</div>
                                    </div>

                                    <!-- TMT (Terhitung Mulai Tanggal) -->
                                    <div>
                                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                            TMT (Terhitung Mulai Tanggal)
                                        </label>
                                        <input
                                            v-model="form.tmt"
                                            type="date"
                                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                                            :class="{ 'border-error': form.errors.tmt }"
                                        />
                                        <div v-if="form.errors.tmt" class="text-error text-xs mt-1">{{ form.errors.tmt }}</div>
                                    </div>

                                    <!-- TST Jenjang -->
                                    <div>
                                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                            TST Jenjang
                                        </label>
                                        <input
                                            v-model="form.tst_jenjang"
                                            type="date"
                                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                                            :class="{ 'border-error': form.errors.tst_jenjang }"
                                        />
                                        <div v-if="form.errors.tst_jenjang" class="text-error text-xs mt-1">{{ form.errors.tst_jenjang }}</div>
                                    </div>

                                    <!-- Masa Kerja -->
                                    <div>
                                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                            Masa Kerja
                                        </label>
                                        <input
                                            v-model="form.masa_kerja"
                                            type="text"
                                            placeholder="Contoh: 5 Tahun 2 Bulan"
                                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                            :class="{ 'border-error': form.errors.masa_kerja }"
                                        />
                                        <div v-if="form.errors.masa_kerja" class="text-error text-xs mt-1">{{ form.errors.masa_kerja }}</div>
                                    </div>

                                    <!-- Keaktifan Dapodik -->
                                    <div>
                                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                            Keaktifan Dapodik
                                        </label>
                                        <select
                                            v-model="form.keaktifan_dapodik"
                                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                                            :class="{ 'border-error': form.errors.keaktifan_dapodik }"
                                        >
                                            <option value="">-- Pilih Status Dapodik --</option>
                                            <option value="Aktif">Aktif</option>
                                            <option value="Tidak Aktif">Tidak Aktif</option>
                                        </select>
                                        <div v-if="form.errors.keaktifan_dapodik" class="text-error text-xs mt-1">{{ form.errors.keaktifan_dapodik }}</div>
                                    </div>

                                    <!-- Unit Keaktifan Dapodik -->
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                            Unit Keaktifan Dapodik
                                        </label>
                                        <input
                                            v-model="form.unit_keaktifan_dapodik"
                                            type="text"
                                            placeholder="Nama unit terdaftar di Dapodik"
                                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                            :class="{ 'border-error': form.errors.unit_keaktifan_dapodik }"
                                        />
                                        <div v-if="form.errors.unit_keaktifan_dapodik" class="text-error text-xs mt-1">{{ form.errors.unit_keaktifan_dapodik }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 5: RIWAYAT PENDIDIKAN -->
                            <div v-show="activeModalTab === 'pendidikan'" class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-sm font-bold text-on-surface">Daftar Riwayat Pendidikan</h4>
                                        <p class="text-xs text-on-surface-variant">Tambahkan jenjang dan riwayat pendidikan yang telah ditempuh</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addEducationRow"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary/10 text-primary hover:bg-primary/20 text-xs font-bold transition-all cursor-pointer"
                                    >
                                        <span class="material-symbols-outlined text-[16px]">add</span>
                                        <span>Tambah Pendidikan</span>
                                    </button>
                                </div>

                                <div v-if="form.riwayat_pendidikan.length === 0" class="py-10 text-center border-2 border-dashed border-outline-variant/40 rounded-2xl">
                                    <span class="material-symbols-outlined text-3xl text-outline/60 block mb-1">school</span>
                                    <p class="text-xs sm:text-sm text-on-surface-variant">Belum ada data riwayat pendidikan.</p>
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
                                        class="p-4 bg-surface-container-low rounded-2xl border border-outline-variant/40 relative group"
                                    >
                                        <div class="flex items-center justify-between mb-3 border-b border-outline-variant/20 pb-2">
                                            <span class="text-xs font-bold text-primary flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[16px]">history_edu</span>
                                                Pendidikan #{{ index + 1 }}
                                            </span>
                                            <button
                                                type="button"
                                                @click="removeEducationRow(index)"
                                                class="text-error hover:bg-error/10 p-1 rounded-md text-xs font-semibold flex items-center gap-1 transition-colors cursor-pointer"
                                                title="Hapus baris pendidikan ini"
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
                                                    class="w-full px-3 py-1.5 bg-surface-container-lowest border border-outline-variant/60 rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary cursor-pointer"
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
                                                    class="w-full px-3 py-1.5 bg-surface-container-lowest border border-outline-variant/60 rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary"
                                                />
                                            </div>

                                            <div>
                                                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Instansi / Universitas</label>
                                                <input
                                                    v-model="edu.instansi_pendidikan"
                                                    type="text"
                                                    placeholder="Contoh: UNS Surakarta"
                                                    class="w-full px-3 py-1.5 bg-surface-container-lowest border border-outline-variant/60 rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary"
                                                />
                                            </div>

                                            <div>
                                                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Tahun Lulus</label>
                                                <input
                                                    v-model="edu.tahun_lulus"
                                                    type="text"
                                                    placeholder="Contoh: 2018"
                                                    class="w-full px-3 py-1.5 bg-surface-container-lowest border border-outline-variant/60 rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary"
                                                />
                                            </div>

                                            <div class="sm:col-span-2">
                                                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Pembiayaan</label>
                                                <input
                                                    v-model="edu.pembiayaan"
                                                    type="text"
                                                    placeholder="Contoh: Mandiri / Beasiswa / Dinas"
                                                    class="w-full px-3 py-1.5 bg-surface-container-lowest border border-outline-variant/60 rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Error Alert (If any) -->
                            <div v-if="Object.keys(form.errors).length > 0" class="p-3.5 rounded-xl bg-error/10 border border-error/20 flex items-start gap-2 text-error text-xs">
                                <span class="material-symbols-outlined text-[18px] shrink-0">error</span>
                                <div>
                                    <span class="font-bold">Terdapat kesalahan pengisian data:</span>
                                    <ul class="list-disc list-inside mt-1 space-y-0.5">
                                        <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                                    </ul>
                                </div>
                            </div>
                        </form>

                        <!-- Modal Footer -->
                        <div class="flex items-center justify-between px-6 py-4 border-t border-outline-variant/30 bg-surface-container-low shrink-0">
                            <span class="text-xs text-on-surface-variant">
                                * Kolom bertanda bintang wajib diisi
                            </span>
                            <div class="flex items-center gap-2.5">
                                <button
                                    type="button"
                                    @click="$emit('close')"
                                    class="px-4 py-2 sm:px-5 sm:py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-on-surface-variant hover:bg-surface-container-high border border-outline-variant/50 transition-colors cursor-pointer"
                                    :disabled="form.processing"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    @click="submit"
                                    class="inline-flex items-center gap-1.5 px-5 py-2 sm:px-6 sm:py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-xs sm:text-sm transition-all shadow-xs hover:shadow-md cursor-pointer disabled:opacity-60"
                                    :disabled="form.processing"
                                >
                                    <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                    <span v-else class="material-symbols-outlined text-[16px]">save</span>
                                    <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-backdrop-enter-active,
.modal-backdrop-leave-active {
    transition: opacity 0.2s ease;
}
.modal-backdrop-enter-from,
.modal-backdrop-leave-to {
    opacity: 0;
}
.modal-content-enter-active {
    transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-content-leave-active {
    transition: all 0.15s ease-in;
}
.modal-content-enter-from {
    opacity: 0;
    transform: scale(0.96) translateY(8px);
}
.modal-content-leave-to {
    opacity: 0;
    transform: scale(0.98) translateY(4px);
}
</style>
