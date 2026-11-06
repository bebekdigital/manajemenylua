<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
});

const searchQuery = ref('');
const selectedCategory = ref('all');

const announcements = ref([
    {
        id: 1,
        title: 'Pemberitahuan Libur Nasional & Penyesuaian KBM',
        category: 'Penting',
        badgeClass: 'bg-rose-50 text-rose-800 border-rose-200',
        author: 'Sekretariat Yayasan',
        publishedAt: 'Hari ini, 09:00 WIB',
        date: '03 Nov 2026',
        summary: 'Sehubungan dengan hari libur nasional dan cuti bersama, kegiatan belajar mengajar tatap muka diliburkan.',
        content: 'Diberitahukan kepada seluruh asatidz, pengasuh, dan wali santri Yayasan Li Ulil Albab Karanganyar, bahwa menindaklanjuti ketetapan bersama Kementerian, seluruh kegiatan pembelajaran formal diliburkan mulai tanggal 12 hingga 14 November 2026. Kegiatan pembinaan pondok pesantren dan kepengasuhan tetap berjalan dengan sistem piket.',
        attachment: 'Surat_Edaran_Libur_Nov2026.pdf',
        isPinned: true,
    },
    {
        id: 2,
        title: 'Pemeliharaan Rutin & Pembaruan Sistem Manajemen Data',
        category: 'Sistem',
        badgeClass: 'bg-blue-50 text-blue-800 border-blue-200',
        author: 'Tim IT & Sistem Informasi',
        publishedAt: '28 Okt 2026, 14:30 WIB',
        date: '28 Okt 2026',
        summary: 'Pembaruan server dan database akan dilakukan pada malam hari untuk meningkatkan performa akses e-Rapor dan presensi.',
        content: 'Kami menginformasikan bahwa sistem manajemen sekolah (data.ulilalbabkra.sch.id) akan menjalani pemeliharaan berkala pada hari Sabtu malam pukul 23:00 - 03:00 WIB. Layanan input nilai dan data siswa mungkin akan mengalami jeda sesaat. Mohon simpan data pekerjaan Anda sebelum rentang waktu tersebut.',
        attachment: null,
        isPinned: true,
    },
    {
        id: 3,
        title: 'Sosialisasi Alur Pengajuan Perizinan dan Cuti Online Pegawai',
        category: 'Kepegawaian',
        badgeClass: 'bg-amber-50 text-amber-800 border-amber-200',
        author: 'Bidang SDM Yayasan',
        publishedAt: '24 Okt 2026',
        date: '24 Okt 2026',
        summary: 'Seluruh pengajuan izin sakit, dinas luar, dan cuti tahunan kini wajib diajukan melalui modul Perizinan Pegawai.',
        content: 'Dalam rangka tertib administrasi dan transparansi hierarki jabatan, seluruh tenaga pendidik dan kependidikan di unit PPTQ, SDIT, dan SMPIT kini dapat mengajukan perizinan secara mandiri melalui menu Data Pegawai > Tab Perizinan. Persetujuan akan langsung diteruskan secara realtime ke akun atasan langsung sesuai struktur organisasi.',
        attachment: 'Panduan_Pengajuan_Izin_Pegawai.pdf',
        isPinned: false,
    },
    {
        id: 4,
        title: 'Jadwal Penilaian Kinerja Guru (PKG) Semester Gasal',
        category: 'Akademik',
        badgeClass: 'bg-emerald-50 text-emerald-800 border-emerald-200',
        author: 'Bidang Penjaminan Mutu & Akademik',
        publishedAt: '18 Okt 2026',
        date: '18 Okt 2026',
        summary: 'Supervisi kelas dan evaluasi perangkat ajar guru SDIT & SMPIT akan dimulai pekan depan.',
        content: 'Kegiatan PKG bertujuan meningkatkan standar mutu pembelajaran dan penguasaan kurikulum. Setiap guru diharapkan telah mempersiapkan modul ajar, rubrik penilaian, serta lembar observasi siswa sebelum jadwal visitasi kepala sekolah dan tim penjamin mutu.',
        attachment: 'Instrumen_PKG_2026.pdf',
        isPinned: false,
    },
]);

const categories = ['Penting', 'Akademik', 'Kepegawaian', 'Sistem'];

const filteredAnnouncements = computed(() => {
    return announcements.value.filter((item) => {
        const query = searchQuery.value.toLowerCase().trim();
        const matchesQuery =
            !query ||
            item.title.toLowerCase().includes(query) ||
            item.summary.toLowerCase().includes(query) ||
            item.content.toLowerCase().includes(query) ||
            item.author.toLowerCase().includes(query);

        const matchesCategory =
            selectedCategory.value === 'all' || item.category === selectedCategory.value;

        return matchesQuery && matchesCategory;
    });
});

// Detail Modal
const selectedDetail = ref(null);

function openDetail(item) {
    selectedDetail.value = item;
}

function closeDetail() {
    selectedDetail.value = null;
}
</script>

<template>
    <Head title="Pusat Informasi & Pengumuman - Yayasan Li Ulil Albab" />

    <div class="p-4 md:p-margin-desktop max-w-7xl mx-auto space-y-6">
        <!-- Header Banner -->
        <div class="bg-gradient-to-r from-emerald-900 via-emerald-800 to-teal-900 rounded-3xl p-6 sm:p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-emerald-700/30 rounded-full blur-3xl pointer-events-none"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-xs border border-white/20 text-xs font-semibold text-emerald-200">
                        <span class="material-symbols-outlined text-[16px]">campaign</span>
                        <span>Pemberitahuan & Berita Resmi</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Pusat Informasi</h1>
                    <p class="text-emerald-100 text-sm sm:text-base max-w-2xl">
                        Kumpulan pengumuman penting, surat edaran yayasan, dan informasi operasional terbaru di lingkungan Yayasan Li Ulil Albab.
                    </p>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-2xs space-y-3">
            <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                <!-- Search -->
                <div class="relative flex-1">
                    <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">
                        search
                    </span>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari pengumuman, perihal, atau divisi pembuat..."
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:bg-white transition-all"
                    />
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs font-semibold"
                    >
                        ✕
                    </button>
                </div>

                <!-- Category Pills -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 no-scrollbar">
                    <button
                        type="button"
                        @click="selectedCategory = 'all'"
                        :class="[
                            'px-3.5 py-2 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer',
                            selectedCategory === 'all'
                                ? 'bg-emerald-800 text-white shadow-2xs'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                        ]"
                    >
                        Semua
                    </button>
                    <button
                        v-for="cat in categories"
                        :key="cat"
                        type="button"
                        @click="selectedCategory = cat"
                        :class="[
                            'px-3.5 py-2 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer',
                            selectedCategory === cat
                                ? 'bg-emerald-800 text-white shadow-2xs'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                        ]"
                    >
                        {{ cat }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Announcement Cards Grid -->
        <div v-if="filteredAnnouncements.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
                v-for="item in filteredAnnouncements"
                :key="item.id"
                class="bg-white rounded-2xl border border-slate-200 hover:border-emerald-500/80 transition-all p-5 shadow-2xs flex flex-col justify-between group"
            >
                <div class="space-y-3">
                    <!-- Top meta -->
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span :class="['px-2.5 py-0.5 rounded-lg text-xs font-bold border', item.badgeClass]">
                                {{ item.category }}
                            </span>
                            <span v-if="item.isPinned" class="inline-flex items-center gap-0.5 text-xs font-semibold text-amber-600">
                                <span class="material-symbols-outlined text-[15px]">keep</span>
                                Disematkan
                            </span>
                        </div>
                        <span class="text-xs text-slate-400 font-medium">{{ item.publishedAt }}</span>
                    </div>

                    <!-- Title -->
                    <h3 class="text-base font-bold text-slate-900 group-hover:text-emerald-950 transition-colors leading-snug">
                        {{ item.title }}
                    </h3>

                    <!-- Summary -->
                    <p class="text-xs text-slate-600 leading-relaxed line-clamp-3">
                        {{ item.content }}
                    </p>

                    <!-- Attachment if any -->
                    <div v-if="item.attachment" class="pt-1">
                        <span class="inline-flex items-center gap-1.5 text-xs text-emerald-800 bg-emerald-50/70 border border-emerald-200/80 px-2.5 py-1 rounded-lg">
                            <span class="material-symbols-outlined text-[15px]">attachment</span>
                            <span class="font-medium truncate max-w-xs">{{ item.attachment }}</span>
                        </span>
                    </div>
                </div>

                <!-- Footer: Author & Read More -->
                <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-400">
                        Oleh: <strong class="text-slate-600">{{ item.author }}</strong>
                    </span>

                    <button
                        type="button"
                        @click="openDetail(item)"
                        class="inline-flex items-center gap-1 font-bold text-emerald-800 hover:text-emerald-950 cursor-pointer"
                    >
                        <span>Baca Selengkapnya</span>
                        <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white rounded-3xl border border-slate-200 p-12 text-center shadow-2xs space-y-3">
            <div class="w-16 h-16 rounded-3xl bg-emerald-50 text-emerald-700 flex items-center justify-center mx-auto border border-emerald-100">
                <span class="material-symbols-outlined text-3xl">notifications_paused</span>
            </div>
            <h3 class="text-base font-bold text-slate-800">Tidak Ada Informasi</h3>
            <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto">
                Belum ada pengumuman yang sesuai dengan kata kunci pencarian Anda.
            </p>
        </div>

        <!-- Detail Modal -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="selectedDetail"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
                @click.self="closeDetail"
            >
                <div class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-7 space-y-4 shadow-2xl border border-slate-200">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span :class="['px-2.5 py-0.5 rounded-lg text-xs font-bold border', selectedDetail.badgeClass]">
                                {{ selectedDetail.category }}
                            </span>
                            <span class="text-xs text-slate-400">{{ selectedDetail.publishedAt }}</span>
                        </div>
                        <button
                            type="button"
                            @click="closeDetail"
                            class="text-slate-400 hover:text-slate-600 rounded-full p-1 cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <div class="space-y-3">
                        <h2 class="text-lg font-bold text-slate-900 leading-snug">
                            {{ selectedDetail.title }}
                        </h2>

                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-xs text-slate-500 flex items-center justify-between">
                            <span>Diterbitkan oleh: <strong class="text-slate-700">{{ selectedDetail.author }}</strong></span>
                            <span>{{ selectedDetail.date }}</span>
                        </div>

                        <div class="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line py-2">
                            {{ selectedDetail.content }}
                        </div>

                        <div v-if="selectedDetail.attachment" class="pt-2">
                            <div class="p-3 bg-emerald-50/70 border border-emerald-200 rounded-xl flex items-center justify-between">
                                <div class="flex items-center gap-2 text-xs text-emerald-900 font-semibold truncate">
                                    <span class="material-symbols-outlined text-[18px] text-emerald-700">description</span>
                                    <span class="truncate">{{ selectedDetail.attachment }}</span>
                                </div>
                                <span class="text-xs text-emerald-700 font-bold bg-white px-2.5 py-1 rounded-lg border border-emerald-200 shrink-0">
                                    Lampiran
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="closeDetail"
                            class="px-5 py-2 rounded-xl text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors cursor-pointer"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

