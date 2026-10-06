<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
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
});

const activeTab = ref('identitas');

const tabs = [
    { id: 'identitas', label: 'Identitas', icon: 'badge' },
    { id: 'alamat', label: 'Alamat', icon: 'home' },
    { id: 'keluarga', label: 'Keluarga', icon: 'family_restroom' },
    { id: 'kepegawaian', label: 'Kepegawaian', icon: 'work' },
    { id: 'pendidikan', label: 'Pendidikan', icon: 'school' },
];

function getAlamatDomisili(e) {
    return [e.jalan_domisili, e.rt_rw_domisili, e.dusun_domisili, e.desa_domisili, e.kecamatan_domisili, e.kab_domisili, e.provinsi_domisili]
        .filter(Boolean).join(', ') || '-';
}

function getAlamatTinggal(e) {
    return [e.jalan_tinggal, e.rt_rw_tinggal, e.dusun_tinggal, e.desa_tinggal, e.kecamatan_tinggal, e.provinsi_tinggal]
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
    <Head :title="`Detail Pegawai - ${employee.nama}`" />

    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop bg-surface-container-lowest">
        <!-- Back Button -->
        <div class="mb-4">
            <Link href="/staff" class="inline-flex items-center gap-1 text-sm font-medium text-on-surface-variant hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Kembali ke Data Pegawai
            </Link>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 flex flex-col overflow-hidden">
            <!-- Profile Header -->
            <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/30 bg-surface-container-low">
                <div class="flex items-center gap-5">
                    <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-primary/10 flex items-center justify-center overflow-hidden border-2 border-outline-variant/20 shadow-sm shrink-0">
                        <span class="text-primary font-bold text-2xl sm:text-3xl">
                            {{ employee.nama ? employee.nama.charAt(0) : '?' }}
                        </span>
                    </div>
                    <div>
                        <h3 class="text-lg sm:text-xl font-bold text-on-surface">{{ employee.nama }}</h3>
                        <p class="text-xs sm:text-sm text-on-surface-variant">NIPY: {{ employee.nipy }}</p>
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
            </div>

            <!-- Tabs -->
            <div class="flex border-b border-outline-variant/30 px-4 bg-surface-container-low overflow-x-auto">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="[
                        'flex items-center gap-2 px-3 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm font-medium transition-colors whitespace-nowrap border-b-2',
                        activeTab === tab.id
                            ? 'text-primary border-primary'
                            : 'text-on-surface-variant border-transparent hover:text-on-surface hover:border-outline-variant'
                    ]"
                >
                    <span class="material-symbols-outlined text-[18px]">{{ tab.icon }}</span>
                    {{ tab.label }}
                </button>
            </div>

            <!-- Tab Content -->
            <div class="flex-1 p-6">

                <!-- TAB: Identitas -->
                <div v-if="activeTab === 'identitas'" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Nama Lengkap</label>
                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ employee.nama }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">NIPY</label>
                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5 font-mono">{{ employee.nipy }}</p>
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
                </div>

                <!-- TAB: Alamat -->
                <div v-if="activeTab === 'alamat'" class="space-y-6">

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
                </div>

                <!-- TAB: Keluarga -->
                <div v-if="activeTab === 'keluarga'" class="space-y-6">

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
                </div>

                <!-- TAB: Kepegawaian -->
                <div v-if="activeTab === 'kepegawaian'" class="space-y-4">
                    <div v-if="employee.riwayat_kepegawaian && employee.riwayat_kepegawaian.length > 0">
                        <div
                            v-for="record in employee.riwayat_kepegawaian"
                            :key="record.id"
                            class="mb-4 p-4 bg-surface-container-low border border-outline-variant/30 rounded-xl"
                        >
                            <!-- Record Header -->
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

                            <!-- Record Detail Grid -->
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

                    <div v-else class="py-12 text-center">
                        <span class="material-symbols-outlined text-4xl text-outline/60 block mb-2">work_history</span>
                        <p class="text-sm text-on-surface-variant">Belum ada riwayat kepegawaian</p>
                        <p class="text-xs text-outline mt-1">Import data melalui Sheet 3 template Excel</p>
                    </div>
                </div>

                <!-- TAB: Pendidikan -->
                <div v-if="activeTab === 'pendidikan'" class="space-y-4">
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

                    <div v-else class="py-12 text-center">
                        <span class="material-symbols-outlined text-4xl text-outline/60 block mb-2">school</span>
                        <p class="text-sm text-on-surface-variant">Belum ada riwayat pendidikan</p>
                        <p class="text-xs text-outline mt-1">Import data melalui Sheet 2 template Excel</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
