<script setup>
import { computed } from 'vue';

const props = defineProps({
    student: {
        type: Object,
        required: true,
    },
    template: {
        type: Object,
        required: true,
    },
    pagesToPrint: {
        type: Array,
        default: () => ['cover', 'school_profile', 'identity'],
    },
});

const school = computed(() => props.template?.school_profile || {});
const signatory = computed(() => props.template?.signatory || {});
const options = computed(() => props.template?.options || {
    show_tut_wuri_logo: true,
    show_school_logo: true,
    show_photo_box: true,
});

const coverStudentName = computed(() => {
    return (props.student?.nama_kapital || props.student?.nama || '-').toUpperCase();
});

const coverNisnNis = computed(() => {
    return `${props.student?.nisn || '-'} / ${props.student?.nis || props.student?.nipd || '-'}`;
});

const signatoryDate = computed(() => {
    return props.student?.tanggal_diterima || '-';
});
</script>

<template>
    <div class="student-document-container font-serif text-black">
        <!-- ================= HALAMAN 1: COVER DEPAN ================= -->
        <div 
            v-if="pagesToPrint.includes('cover')"
            class="document-page a4-page flex flex-col justify-between items-center text-center shadow-lg border border-black mb-8"
        >
            <!-- Section 1: Logo Tut Wuri Handayani -->
            <div v-if="options.show_tut_wuri_logo" class="pt-2">
                <img v-if="options.tut_wuri_logo_preview || options.tut_wuri_logo_path" :src="options.tut_wuri_logo_preview || ('/storage/' + options.tut_wuri_logo_path)" alt="Logo Kementrian" class="object-contain mx-auto" style="width: 200px; height: 200px;">
                <div v-else class="mx-auto flex flex-col items-center justify-center bg-gray-100 p-2 text-center text-gray-500" style="width: 200px; height: 200px;">
                    <span class="material-symbols-outlined text-4xl mb-1">school</span>
                    <span class="text-[8px] font-bold uppercase tracking-tighter leading-none">LOGO KEMENTERIAN</span>
                </div>
            </div>
            <div v-else class="pt-2" style="height: 135px;"></div>

            <!-- Section 2: SEKOLAH MENENGAH PERTAMA ( SMP ) -->
            <div class="flex flex-col space-y-1">
                <h1 class="text-2xl font-bold tracking-wide uppercase">{{ school.jenjang_tingkat || 'SEKOLAH MENENGAH PERTAMA' }}</h1>
                <h2 class="text-2xl font-bold">{{ school.jenjang_singkat || '( SMP )' }}</h2>
            </div>

            <!-- Section 3: Logo Sekolah -->
            <div class="py-2" v-if="options.show_school_logo">
                <img v-if="options.school_logo_preview || options.school_logo_path" :src="options.school_logo_preview || ('/storage/' + options.school_logo_path)" alt="Logo Sekolah" class="object-contain mx-auto" style="width: 175px; height: 175px;">
                <div v-else class="mx-auto flex flex-col items-center justify-center bg-emerald-800 p-2 text-center text-amber-300" style="width: 135px; height: 135px;">
                    <span class="material-symbols-outlined text-4xl mb-1">menu_book</span>
                    <span class="text-[8px] font-bold uppercase tracking-tighter text-white leading-none">SMP IT TAHFIDZUL QURAN</span>
                    <span class="text-[7px] font-semibold leading-tight">ULIL ALBAB</span>
                </div>
            </div>
            <div v-else class="py-2" style="height: 135px;"></div>

            <!-- Section 4: Identitas Nama dan NIS/NISN -->
            <div class="flex flex-col space-y-6 w-full px-8">
                <div>
                    <p class="text-base font-medium text-gray-700 mb-1">Nama Peserta Didik</p>
                    <h3 class="text-2xl font-bold uppercase tracking-wider">{{ coverStudentName }}</h3>
                </div>
                <div>
                    <p class="text-base font-medium text-gray-700 mb-1">NISN / NIS</p>
                    <h4 class="text-xl font-semibold tracking-widest">{{ props.student?.nisn || '-' }} / {{ props.student?.nis || props.student?.nipd || '-' }}</h4>
                </div>
            </div>

            <!-- Section 5: KEMENTERIAN PENDIDIKAN DASAR DAN MENENGAH REPUBLIK INDONESIA -->
            <div class="w-full pt-4 pb-4">
                <template v-if="school.kementerian_title">
                    <h3 class="text-xl font-bold tracking-wide uppercase leading-snug" v-html="school.kementerian_title.replace(/\n/g, '<br/>')"></h3>
                </template>
                <template v-else>
                    <h3 class="text-xl font-bold tracking-wide uppercase leading-snug">KEMENTERIAN PENDIDIKAN DASAR DAN MENENGAH<br>REPUBLIK INDONESIA</h3>
                </template>
            </div>
        </div>

        <!-- ================= HALAMAN 2: PROFIL SEKOLAH ================= -->
        <div 
            v-if="pagesToPrint.includes('school_profile')"
            class="document-page a4-page flex flex-col shadow-lg border border-black mb-8"
        >
            <!-- Header -->
            <div>
                <h2 class="text-xl font-bold text-center uppercase mb-6 mt-2">{{ template?.template_content?.profile_title || 'PROFIL SEKOLAH' }}</h2>
            </div>

            <div class="w-full mt-4 px-6">
                <table class="w-full text-base border-collapse leading-[2.2rem]">
                    <tbody>
                        <tr>
                            <td class="py-1 font-semibold w-[35%]">Nama Sekolah</td>
                            <td class="py-1 w-5 text-center">:</td>
                            <td class="py-1 uppercase font-bold">{{ school.nama_sekolah }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 font-semibold">NPSN</td>
                            <td class="py-1 text-center">:</td>
                            <td class="py-1">{{ school.npsn || '-' }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 font-semibold">NIS/NSS/NDS</td>
                            <td class="py-1 text-center">:</td>
                            <td class="py-1">{{ school.nis_nss_nds || '-' }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 font-semibold">Alamat Sekolah</td>
                            <td class="py-1 text-center">:</td>
                            <td class="py-1">{{ school.alamat_sekolah || '-' }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 font-semibold">Kelurahan / Desa</td>
                            <td class="py-1 text-center">:</td>
                            <td class="py-1">{{ school.kelurahan_desa || '-' }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 font-semibold">Kecamatan</td>
                            <td class="py-1 text-center">:</td>
                            <td class="py-1">{{ school.kecamatan || '-' }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 font-semibold">Kota/Kabupaten</td>
                            <td class="py-1 text-center">:</td>
                            <td class="py-1">{{ school.kota_kabupaten || '-' }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 font-semibold">Provinsi</td>
                            <td class="py-1 text-center">:</td>
                            <td class="py-1">{{ school.provinsi || '-' }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 font-semibold">Website</td>
                            <td class="py-1 text-center">:</td>
                            <td class="py-1 text-blue-600 underline">{{ school.website || '-' }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 font-semibold">E-mail</td>
                            <td class="py-1 text-center">:</td>
                            <td class="py-1">{{ school.email || '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="w-full pb-4"></div>
        </div>

        <!-- ================= HALAMAN 3: IDENTITAS PESERTA DIDIK ================= -->
        <div 
            v-if="pagesToPrint.includes('identity')"
            class="document-page a4-page flex flex-col justify-between shadow-lg border border-black mb-8"
        >
            <div>
                <h2 class="text-xl font-bold text-center uppercase mb-6 mt-2">{{ template?.template_content?.identity_title || 'IDENTITAS PESERTA DIDIK' }}</h2>

                <table class="w-full text-sm border-collapse leading-tight identity-table" style="table-layout: fixed;">
                    <colgroup>
                        <col style="width: 5%;">
                        <col style="width: 32%;">
                        <col style="width: 3%;">
                        <col style="width: 60%;">
                    </colgroup>
                    <tbody>
                        <tr class="numbered-row" v-if="template?.options?.identity_visibility?.nama_lengkap ?? true">
                            <td class="py-1 row-number"></td>
                            <td>Nama Lengkap Peserta Didik</td>
                            <td class="text-center">:</td>
                            <td class="py-1 font-bold uppercase">{{ student?.nama || '-' }}</td>
                        </tr>
                        <tr class="numbered-row" v-if="template?.options?.identity_visibility?.nomor_induk ?? true">
                            <td class="py-1 row-number"></td>
                            <td class="w-56">Nomor Induk / NISN</td>
                            <td class="w-4 text-center">:</td>
                            <td class="py-1">{{ student?.nis || student?.nipd || '-' }} / {{ student?.nisn || '-' }}</td>
                        </tr>
                        <tr class="numbered-row" v-if="template?.options?.identity_visibility?.tempat_tanggal_lahir ?? true">
                            <td class="py-1 row-number"></td>
                            <td class="w-56">Tempat, Tanggal Lahir</td>
                            <td class="w-4 text-center">:</td>
                            <td class="py-1">{{ student?.tempat_lahir || '-' }}, {{ student?.tanggal_lahir || student?.ttl || '-' }}</td>
                        </tr>
                        <tr class="numbered-row" v-if="template?.options?.identity_visibility?.jenis_kelamin ?? true">
                            <td class="py-1 row-number"></td>
                            <td class="w-56">Jenis Kelamin</td>
                            <td class="w-4 text-center">:</td>
                            <td class="py-1">{{ student?.jk || '-' }}</td>
                        </tr>
                        <tr class="numbered-row" v-if="template?.options?.identity_visibility?.agama ?? true">
                            <td class="py-1 row-number"></td>
                            <td class="w-56">Agama</td>
                            <td class="w-4 text-center">:</td>
                            <td class="py-1">{{ student?.agama || '-' }}</td>
                        </tr>
                        <tr class="numbered-row" v-if="template?.options?.identity_visibility?.status_keluarga ?? true">
                            <td class="py-1 row-number"></td>
                            <td class="w-56">Status dalam Keluarga</td>
                            <td class="w-4 text-center">:</td>
                            <td class="py-1">{{ student?.status_keluarga || '-' }}</td>
                        </tr>
                        <tr class="numbered-row" v-if="template?.options?.identity_visibility?.anak_ke ?? true">
                            <td class="py-1 row-number"></td>
                            <td class="w-56">Anak ke</td>
                            <td class="w-4 text-center">:</td>
                            <td class="py-1">{{ student?.anak_ke || '-' }}</td>
                        </tr>
                        <tr class="numbered-row" v-if="template?.options?.identity_visibility?.alamat_peserta ?? true">
                            <td class="py-1 row-number"></td>
                            <td class="w-56">Alamat Peserta Didik</td>
                            <td class="w-4 text-center">:</td>
                            <td class="py-1">{{ student?.alamat || '-' }}</td>
                        </tr>
                        <tr class="numbered-row" v-if="template?.options?.identity_visibility?.nomor_telepon ?? true">
                            <td class="py-1 row-number"></td>
                            <td class="w-56">Nomor Telepon</td>
                            <td class="w-4 text-center">:</td>
                            <td class="py-1">{{ student?.no_wa || student?.hp || '-' }}</td>
                        </tr>
                        <tr class="numbered-row" v-if="template?.options?.identity_visibility?.sekolah_asal ?? true">
                            <td class="py-1 row-number"></td>
                            <td class="w-56">Sekolah Asal</td>
                            <td class="w-4 text-center">:</td>
                            <td class="py-1">{{ student?.sekolah_asal || '-' }}</td>
                        </tr>
                        
                        <tr class="numbered-row" v-if="template?.options?.identity_visibility?.diterima_di_sekolah ?? true">
                            <td class="py-1 row-number"></td>
                            <td colspan="3">Diterima di sekolah ini:</td>
                        </tr>
                        <tr v-if="template?.options?.identity_visibility?.diterima_di_sekolah ?? true">
                            <td></td>
                            <td class="pl-4 py-0.5">a. Di kelas</td>
                            <td class="text-center">:</td>
                            <td class="py-0.5">{{ student?.kelas_diterima || student?.kelas || '-' }}</td>
                        </tr>
                        <tr v-if="template?.options?.identity_visibility?.diterima_di_sekolah ?? true">
                            <td></td>
                            <td class="pl-4 py-0.5">b. Pada tanggal</td>
                            <td class="text-center">:</td>
                            <td class="py-0.5">{{ student?.tanggal_diterima || '-' }}</td>
                        </tr>

                        <tr class="numbered-row" v-if="template?.options?.identity_visibility?.nama_orang_tua ?? true">
                            <td class="py-1 row-number"></td>
                            <td colspan="3">Nama Orang Tua:</td>
                        </tr>
                        <tr v-if="template?.options?.identity_visibility?.nama_orang_tua ?? true">
                            <td></td>
                            <td class="pl-4 py-0.5">a. Ayah</td>
                            <td class="text-center">:</td>
                            <td class="py-0.5">{{ student?.ayah_nama || '-' }}</td>
                        </tr>
                        <tr v-if="template?.options?.identity_visibility?.nama_orang_tua ?? true">
                            <td></td>
                            <td class="pl-4 py-0.5">b. Ibu</td>
                            <td class="text-center">:</td>
                            <td class="py-0.5">{{ student?.ibu_nama || '-' }}</td>
                        </tr>
                        
                        <tr class="numbered-row" v-if="template?.options?.identity_visibility?.alamat_orang_tua ?? true">
                            <td class="py-1 row-number"></td>
                            <td class="w-56">Alamat Orang Tua</td>
                            <td class="w-4 text-center">:</td>
                            <td class="py-1">{{ student?.alamat || '-' }}</td>
                        </tr>
                        <tr class="numbered-row" v-if="template?.options?.identity_visibility?.telepon_orang_tua ?? true">
                            <td class="py-1 row-number"></td>
                            <td class="w-56">Nomor Telepon Orang Tua</td>
                            <td class="w-4 text-center">:</td>
                            <td class="py-1">{{ student?.no_wa || student?.hp || '-' }}</td>
                        </tr>

                        <tr class="numbered-row" v-if="template?.options?.identity_visibility?.pekerjaan_orang_tua ?? true">
                            <td class="py-1 row-number"></td>
                            <td colspan="3">Pekerjaan Orang Tua:</td>
                        </tr>
                        <tr v-if="template?.options?.identity_visibility?.pekerjaan_orang_tua ?? true">
                            <td></td>
                            <td class="pl-4 py-0.5">a. Ayah</td>
                            <td class="text-center">:</td>
                            <td class="py-0.5">{{ student?.ayah_pekerjaan || '-' }}</td>
                        </tr>
                        <tr v-if="template?.options?.identity_visibility?.pekerjaan_orang_tua ?? true">
                            <td></td>
                            <td class="pl-4 py-0.5">b. Ibu</td>
                            <td class="text-center">:</td>
                            <td class="py-0.5">{{ student?.ibu_pekerjaan || '-' }}</td>
                        </tr>

                        <tr class="numbered-row" v-if="template?.options?.identity_visibility?.nama_wali ?? true">
                            <td class="py-1 row-number"></td>
                            <td class="w-56">Nama Wali</td>
                            <td class="w-4 text-center">:</td>
                            <td class="py-1">{{ student?.wali_nama || '-' }}</td>
                        </tr>
                        <tr class="numbered-row" v-if="template?.options?.identity_visibility?.alamat_wali ?? true">
                            <td class="py-1 row-number"></td>
                            <td class="w-56">Alamat Wali</td>
                            <td class="w-4 text-center">:</td>
                            <td class="py-1">{{ student?.wali_alamat || '-' }}</td>
                        </tr>
                        <tr class="numbered-row" v-if="template?.options?.identity_visibility?.telepon_wali ?? true">
                            <td class="py-1 row-number"></td>
                            <td class="w-56">Nomor Telepon Wali</td>
                            <td class="w-4 text-center">:</td>
                            <td class="py-1">{{ student?.wali_hp || '-' }}</td>
                        </tr>
                        <tr class="numbered-row" v-if="template?.options?.identity_visibility?.pekerjaan_wali ?? true">
                            <td class="py-1 row-number"></td>
                            <td class="w-56">Pekerjaan Wali</td>
                            <td class="w-4 text-center">:</td>
                            <td class="py-1">{{ student?.wali_pekerjaan || '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Bagian Tanda Tangan Kepala Sekolah (Pas foto dihapus, posisi dialignment ke kanan) -->
            <div class="flex justify-end pb-12 pr-32">
                
                <!-- Tanda Tangan dengan Space Cukup untuk TTD Basah -->
                <div class="text-left text-sm">
                    <p>{{ signatory.tempat_penandatangan || signatory.tempat_titimangsa || 'Karanganyar' }}, {{ signatoryDate }}</p>
                    <p>Kepala Sekolah</p>
                    
                    <!-- Space / Ruang Kosong untuk Tanda Tangan Basah -->
                    <div class="h-16"></div>
                    
                    <p class="font-bold underline capitalize">{{ signatory.nama_kepala_sekolah || 'Nurul Choirul Janah, S.Pd.' }}</p>
                    <p>NIP. {{ signatory.nip || '-' }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.student-document-container {
    font-family: 'Times New Roman', Times, serif;
    background-color: transparent;
    -webkit-font-smoothing: antialiased;
}

.identity-table {
    counter-reset: identity-counter;
}
.identity-table > tbody > tr.numbered-row {
    counter-increment: identity-counter;
}
.identity-table > tbody > tr.numbered-row > td.row-number::after {
    content: counter(identity-counter) ".";
}

.document-page {
    width: 210mm;
    height: 297mm;
    padding: 20mm;
    margin: 0 auto 24px auto;
    background: white;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    position: relative;
    box-sizing: border-box;
}

@media print {
    @page {
        size: A4;
        margin: 0;
    }
    .document-page {
        box-shadow: none !important;
        border: none !important;
        margin: 0 !important;
        padding: 20mm !important; /* Ensure padding remains */
        page-break-after: always !important;
        break-after: page !important;
    }
    .document-page:last-child {
        page-break-after: avoid !important;
        break-after: avoid !important;
    }
}
</style>
