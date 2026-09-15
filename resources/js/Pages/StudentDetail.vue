<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    student: {
        type: Object,
        required: true,
    },
});

const activeTab = ref('identitas');



const tabs = [
    { id: 'identitas', label: 'Identitas', icon: 'person' },
    { id: 'alamat', label: 'Alamat', icon: 'home' },
    { id: 'keluarga', label: 'Keluarga', icon: 'family_restroom' },
    { id: 'registrasi', label: 'Registrasi', icon: 'how_to_reg' },
    { id: 'kesejahteraan', label: 'Kesejahteraan', icon: 'volunteer_activism' },
];

function getAlamatLengkap(s) {
    if (!s) return '-';
    return [s.jalan, s.rt_rw, s.dusun, s.desa, s.kecamatan, s.kabupaten, s.provinsi]
        .filter(Boolean)
        .join(', ');
}

function hitungUsia(tanggalLahir) {
    if (!tanggalLahir) return '-';
    const birthDate = new Date(tanggalLahir);
    if (isNaN(birthDate)) return '-';
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age;
}
</script>

<template>
    <Head :title="`Detail Siswa - ${student.nama}`" />

    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop bg-surface-container-lowest">
        <!-- Back Button -->
        <div class="mb-4">
            <Link href="/students" class="inline-flex items-center gap-1 text-sm font-medium text-on-surface-variant hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Kembali ke Data Siswa
            </Link>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 flex flex-col overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/30 bg-surface-container-low">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xl">
                        {{ student.nama ? student.nama.charAt(0) : '?' }}
                    </div>
                    <div>
                        <h3 class="text-lg sm:text-xl font-bold text-on-surface">{{ student.nama }}</h3>
                        <p class="text-xs sm:text-sm text-on-surface-variant">NISN: {{ student.nisn }} &middot; Kelas {{ student.kelas }}</p>
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
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.nama }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">NISN</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5 font-mono">{{ student.nisn }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">NIPD</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5 font-mono">{{ student.nipd }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">NIK</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5 font-mono">{{ student.nik }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">No. KK</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5 font-mono">{{ student.no_kk }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Unit</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.unit || '-' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Program</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">
                                            <span :class="[
                                                'inline-flex items-center px-2 py-0.5 rounded-md text-[10px] sm:text-xs font-semibold',
                                                student.program === 'Boarding' ? 'bg-error/10 text-error' :
                                                student.program === 'Fullday' ? 'bg-secondary/10 text-secondary' :
                                                'bg-outline-variant/10 text-on-surface-variant'
                                            ]">
                                                {{ student.program || 'Umum' }}
                                            </span>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Kelas</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.kelas }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Jenjang</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.jenjang || '-' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Tingkat</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.tingkat || '-' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Jenis Kelamin</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.jk === 'L' ? 'Laki-laki' : 'Perempuan' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Tempat, Tanggal Lahir</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.ttl }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Agama</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.agama }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">No. WhatsApp</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.no_wa || '-' }}</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Sekolah Asal</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.sekolah_asal || '-' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: Alamat -->
                            <div v-if="activeTab === 'alamat'" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                                    <div class="md:col-span-2">
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Alamat Lengkap</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ getAlamatLengkap(student) }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Jalan</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.jalan || '-' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">RT / RW</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.rt_rw || '-' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Dusun</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.dusun || '-' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Desa / Kelurahan</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.desa || '-' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Kecamatan</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.kecamatan || '-' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Kabupaten / Kota</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.kabupaten || '-' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Provinsi</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.provinsi || '-' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: Keluarga -->
                            <div v-if="activeTab === 'keluarga'" class="space-y-6">
                                <!-- Data Siswa di Keluarga -->
                                <div>
                                    <h4 class="text-xs sm:text-sm font-bold text-primary mb-2 sm:mb-3 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px]">child_care</span>
                                        Data Siswa dalam Keluarga
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 pl-1">
                                        <div>
                                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Status dalam Keluarga</label>
                                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.status_keluarga || '-' }}</p>
                                        </div>
                                        <div>
                                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Anak ke</label>
                                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.anak_ke || '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <hr class="border-outline-variant/30" />

                                <!-- Ayah -->
                                <div>
                                    <h4 class="text-xs sm:text-sm font-bold text-primary mb-2 sm:mb-3 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px]">man</span>
                                        Data Ayah
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 pl-1">
                                        <div>
                                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Nama Ayah</label>
                                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.ayah?.nama || '-' }}</p>
                                        </div>
                                        <div>
                                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Tahun Lahir</label>
                                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.ayah?.tahun_lahir || '-' }}</p>
                                        </div>
                                        <div>
                                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Pekerjaan</label>
                                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.ayah?.pekerjaan || '-' }}</p>
                                        </div>
                                        <div>
                                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Penghasilan</label>
                                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.ayah?.penghasilan || '-' }}</p>
                                        </div>
                                        <div>
                                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Status</label>
                                            <p class="mt-0.5">
                                                <span
                                                    v-if="student.ayah?.status"
                                                    :class="[
                                                        'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] sm:text-xs font-semibold',
                                                        student.ayah.status === 'Meninggal' ? 'bg-slate-200 text-slate-600' : 'bg-emerald-100 text-emerald-700'
                                                    ]"
                                                >
                                                    <span class="material-symbols-outlined text-[12px]">{{ student.ayah.status === 'Meninggal' ? 'sentiment_sad' : 'favorite' }}</span>
                                                    {{ student.ayah.status }}
                                                </span>
                                                <span v-else class="text-xs sm:text-sm font-medium text-on-surface">-</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Ibu -->
                                <div>
                                    <h4 class="text-xs sm:text-sm font-bold text-primary mb-2 sm:mb-3 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px]">woman</span>
                                        Data Ibu
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 pl-1">
                                        <div>
                                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Nama Ibu</label>
                                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.ibu?.nama || '-' }}</p>
                                        </div>
                                        <div>
                                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Tahun Lahir</label>
                                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.ibu?.tahun_lahir || '-' }}</p>
                                        </div>
                                        <div>
                                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Pekerjaan</label>
                                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.ibu?.pekerjaan || '-' }}</p>
                                        </div>
                                        <div>
                                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Penghasilan</label>
                                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.ibu?.penghasilan || '-' }}</p>
                                        </div>
                                        <div>
                                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Status</label>
                                            <p class="mt-0.5">
                                                <span
                                                    v-if="student.ibu?.status"
                                                    :class="[
                                                        'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] sm:text-xs font-semibold',
                                                        student.ibu.status === 'Meninggal' ? 'bg-slate-200 text-slate-600' : 'bg-pink-100 text-pink-700'
                                                    ]"
                                                >
                                                    <span class="material-symbols-outlined text-[12px]">{{ student.ibu.status === 'Meninggal' ? 'sentiment_sad' : 'favorite' }}</span>
                                                    {{ student.ibu.status }}
                                                </span>
                                                <span v-else class="text-xs sm:text-sm font-medium text-on-surface">-</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bisnis / Usaha Keluarga -->
                                <div>
                                    <h4 class="text-xs sm:text-sm font-bold text-primary mb-2 sm:mb-3 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px]">shoppingmode</span>
                                        Bisnis / Usaha Keluarga
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 pl-1">
                                        <div>
                                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Memiliki Bisnis/Usaha</label>
                                            <p class="mt-0.5">
                                                <span
                                                    v-if="student.bisnis !== null"
                                                    :class="[
                                                        'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] sm:text-xs font-semibold',
                                                        student.bisnis?.has_bisnis ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500'
                                                    ]"
                                                >
                                                    <span class="material-symbols-outlined text-[12px]">{{ student.bisnis?.has_bisnis ? 'store' : 'store_mall_directory' }}</span>
                                                    {{ student.bisnis?.has_bisnis ? 'Ya' : 'Tidak' }}
                                                </span>
                                                <span v-else class="text-xs sm:text-sm font-medium text-on-surface">-</span>
                                            </p>
                                        </div>
                                        <div>
                                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Jenis Bisnis/Usaha</label>
                                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.bisnis?.jenis_bisnis || '-' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Wali -->
                                <div v-if="student.wali">
                                    <h4 class="text-xs sm:text-sm font-bold text-primary mb-2 sm:mb-3 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px]">supervisor_account</span>
                                        Data Wali
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 pl-1">
                                        <div>
                                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Nama Wali</label>
                                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.wali.nama }}</p>
                                        </div>
                                        <div>
                                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Hubungan</label>
                                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.wali.hubungan || '-' }}</p>
                                        </div>
                                        <div>
                                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Pekerjaan</label>
                                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.wali.pekerjaan || '-' }}</p>
                                        </div>
                                        <div>
                                            <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Penghasilan</label>
                                            <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.wali.penghasilan || '-' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Saudara -->
                                <div v-if="student.saudara && student.saudara.length > 0">
                                    <h4 class="text-xs sm:text-sm font-bold text-primary mb-2 sm:mb-3 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px]">group</span>
                                        Data Saudara
                                        <span class="text-xs font-normal text-on-surface-variant">(untuk keperluan penawaran PSB)</span>
                                    </h4>
                                    <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                                        <table class="w-full text-xs sm:text-sm">
                                            <thead>
                                                <tr class="bg-surface-container-low text-on-surface-variant">
                                                    <th class="px-4 py-2 text-left text-[10px] sm:text-xs uppercase tracking-wider">No</th>
                                                    <th class="px-4 py-2 text-left text-[10px] sm:text-xs uppercase tracking-wider">Nama</th>
                                                    <th class="px-4 py-2 text-left text-[10px] sm:text-xs uppercase tracking-wider">Tanggal Lahir Lengkap</th>
                                                    <th class="px-4 py-2 text-left text-[10px] sm:text-xs uppercase tracking-wider">Usia (Tahun)</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-outline-variant/10">
                                                <tr v-for="(sdr, idx) in student.saudara" :key="idx">
                                                    <td class="px-4 py-2 text-on-surface-variant">{{ idx + 1 }}</td>
                                                    <td class="px-4 py-2 text-on-surface font-medium">{{ sdr.nama }}</td>
                                                    <td class="px-4 py-2 text-on-surface-variant">{{ sdr.tanggal_lahir || '-' }}</td>
                                                    <td class="px-4 py-2 text-on-surface-variant">{{ hitungUsia(sdr.tanggal_lahir) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: Registrasi -->
                            <div v-if="activeTab === 'registrasi'" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Diterima di Jenjang / Kelas</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.diterima_di_jenjang || '-' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Tanggal Diterima</label>
                                        <p class="text-xs sm:text-sm font-medium text-on-surface mt-0.5">{{ student.formatted_tanggal_diterima || '-' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: Kesejahteraan -->
                            <div v-if="activeTab === 'kesejahteraan'" class="space-y-6">
                                <div>
                                    <h4 class="text-xs sm:text-sm font-bold text-primary mb-2 sm:mb-3 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px]">account_balance</span>
                                        Status Bantuan Pemerintah
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- Status Desil -->
                                        <div class="p-4 rounded-xl border border-outline-variant/30 bg-surface-container-low">
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="text-sm font-semibold text-on-surface">Status Desil (1-10)</span>
                                            </div>
                                            <p class="text-lg font-bold text-primary mt-1">{{ student.bantuan?.desil || '-' }}</p>
                                        </div>

                                        <!-- PIP -->
                                        <div class="p-4 rounded-xl border border-outline-variant/30 bg-surface-container-low">
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="text-sm font-semibold text-on-surface">Status Penerima PIP</span>
                                                <span
                                                    :class="[
                                                        'text-xs px-2.5 py-1 rounded-full font-medium',
                                                        student.bantuan?.pip ? 'bg-primary/10 text-primary' : 'bg-outline-variant/20 text-on-surface-variant'
                                                    ]"
                                                >
                                                    {{ student.bantuan?.pip ? 'Menerima' : 'Tidak' }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-on-surface-variant">{{ student.bantuan?.pip_keterangan || 'Tidak menerima bantuan PIP' }}</p>
                                        </div>

                                        <!-- KIP -->
                                        <div class="p-4 rounded-xl border border-outline-variant/30 bg-surface-container-low">
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="text-sm font-semibold text-on-surface">KIP (Kartu Indonesia Pintar)</span>
                                                <span
                                                    :class="[
                                                        'text-xs px-2.5 py-1 rounded-full font-medium',
                                                        student.bantuan?.kip ? 'bg-primary/10 text-primary' : 'bg-outline-variant/20 text-on-surface-variant'
                                                    ]"
                                                >
                                                    {{ student.bantuan?.kip ? 'Memiliki' : 'Tidak' }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-on-surface-variant">No. KIP: {{ student.bantuan?.no_kip || '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

            </div>
        </div>
    </div>
</template>


