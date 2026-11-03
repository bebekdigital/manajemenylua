<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
});

const searchQuery = ref('');
const selectedCategory = ref('all');
const selectedMonth = ref('all');

// Mock initial agendas data
const agendas = ref([
    {
        id: 1,
        title: 'Rapat Koordinasi Awal Bulan November',
        category: 'Rapat',
        date: '2026-11-10',
        day: '10',
        month: 'Nov',
        year: '2026',
        time: '08:00 - 10:00 WIB',
        location: 'Ruang Rapat Utama & Virtual Zoom',
        organizer: 'Yayasan Li Ulil Albab',
        attendees: 'Seluruh Pimpinan Unit, Kepala Sekolah, dan Koordinator Bidang',
        description: 'Pembahasan evaluasi program kerja bulan Oktober dan sinkronisasi target kalender kegiatan November 2026.',
        badgeClass: 'bg-blue-50 text-blue-800 border-blue-200',
    },
    {
        id: 2,
        title: 'Pelaksanaan Ujian Tengah Semester (UTS) Gasal',
        category: 'Akademik',
        date: '2026-11-15',
        day: '15',
        month: 'Nov',
        year: '2026',
        time: '07:30 - Selesai WIB',
        location: 'Seluruh Ruang Kelas SDIT & SMPIT',
        organizer: 'Bidang Kurikulum & Akademik',
        attendees: 'Seluruh Santri & Dewan Guru Pengawas',
        description: 'Penilaian Tengah Semester Gasal Tahun Ajaran 2026/2027 berlangsung serentak di unit pendidikan.',
        badgeClass: 'bg-emerald-50 text-emerald-800 border-emerald-200',
    },
    {
        id: 3,
        title: 'Batas Akhir Input Nilai Rapor UTS ke Sistem',
        category: 'Deadline',
        date: '2026-11-25',
        day: '25',
        month: 'Nov',
        year: '2026',
        time: '23:59 WIB',
        location: 'Portal Manajemen Ulil Albab',
        organizer: 'Tim Data & Sistem Informasi',
        attendees: 'Semua Guru Mata Pelajaran',
        description: 'Batas akhir penginputan nilai capaian santri untuk diterbitkan dalam e-Rapor dan dibagikan kepada wali santri.',
        badgeClass: 'bg-orange-50 text-orange-800 border-orange-200',
    },
    {
        id: 4,
        title: 'Kajian Bulanan & Parenting Akbar Wali Santri',
        category: 'Kegiatan Yayasan',
        date: '2026-12-05',
        day: '05',
        month: 'Des',
        year: '2026',
        time: '08:30 - 11:30 WIB',
        location: 'Masjid Jami\' PPTQ Ulil Albab',
        organizer: 'Bidang Dakwah & Sosial Yayasan',
        attendees: 'Wali Santri TKIT, SDIT, SMPIT & PPTQ',
        description: 'Silaturahmi berkala dan pembekalan sinergi pengasuhan santri di rumah dan pondok pesantren.',
        badgeClass: 'bg-purple-50 text-purple-800 border-purple-200',
    },
    {
        id: 5,
        title: 'Libur Akhir Semester Gasal & Cuti Bersama',
        category: 'Libur & Cuti',
        date: '2026-12-21',
        day: '21',
        month: 'Des',
        year: '2026',
        time: '21 Des 2026 - 02 Jan 2027',
        location: 'Seluruh Lingkungan Ulil Albab',
        organizer: 'Sekretariat Yayasan',
        attendees: 'Seluruh Pegawai & Santri',
        description: 'Libur semester gasal bagi santri dan jadwal piket kepegawaian yayasan.',
        badgeClass: 'bg-rose-50 text-rose-800 border-rose-200',
    },
]);

const categories = ['Rapat', 'Akademik', 'Deadline', 'Kegiatan Yayasan', 'Libur & Cuti'];

const filteredAgendas = computed(() => {
    return agendas.value.filter((item) => {
        const query = searchQuery.value.toLowerCase().trim();
        const matchesQuery =
            !query ||
            item.title.toLowerCase().includes(query) ||
            item.location.toLowerCase().includes(query) ||
            item.organizer.toLowerCase().includes(query) ||
            item.description.toLowerCase().includes(query);

        const matchesCategory =
            selectedCategory.value === 'all' || item.category === selectedCategory.value;

        return matchesQuery && matchesCategory;
    });
});

// Quick Add Modal placeholder
const showAddModal = ref(false);
const newAgenda = ref({
    title: '',
    category: 'Rapat',
    date: '',
    time: '',
    location: '',
    description: '',
});

function handleSaveAgenda() {
    if (!newAgenda.value.title || !newAgenda.value.date) {
        alert('Harap isi judul dan tanggal agenda.');
        return;
    }

    const d = new Date(newAgenda.value.date);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    agendas.value.unshift({
        id: Date.now(),
        title: newAgenda.value.title,
        category: newAgenda.value.category,
        date: newAgenda.value.date,
        day: String(d.getDate()).padStart(2, '0'),
        month: months[d.getMonth()],
        year: String(d.getFullYear()),
        time: newAgenda.value.time || 'Sepanjang Hari',
        location: newAgenda.value.location || 'Kampus Ulil Albab',
        organizer: 'Yayasan Li Ulil Albab',
        attendees: 'Pihak Terkait',
        description: newAgenda.value.description || '-',
        badgeClass: 'bg-emerald-50 text-emerald-800 border-emerald-200',
    });

    showAddModal.value = false;
    newAgenda.value = {
        title: '',
        category: 'Rapat',
        date: '',
        time: '',
        location: '',
        description: '',
    };
}
</script>

<template>
    <Head title="Agenda Kegiatan - Yayasan Li Ulil Albab" />

    <div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6">
        <!-- Header Banner -->
        <div class="bg-gradient-to-r from-emerald-900 via-emerald-800 to-teal-900 rounded-3xl p-6 sm:p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-emerald-700/30 rounded-full blur-3xl pointer-events-none"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-xs border border-white/20 text-xs font-semibold text-emerald-200">
                        <span class="material-symbols-outlined text-[16px]">calendar_month</span>
                        <span>Kalender & Kegiatan Yayasan</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Agenda Kegiatan</h1>
                    <p class="text-emerald-100 text-sm sm:text-base max-w-2xl">
                        Jadwal kegiatan akademik, rapat koordinasi, evaluasi kinerja, dan kalender penting Yayasan Li Ulil Albab.
                    </p>
                </div>

                <!-- Add Agenda Action -->
                <div class="shrink-0 self-start md:self-auto">
                    <button
                        type="button"
                        @click="showAddModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-amber-400 hover:bg-amber-300 text-emerald-950 font-bold text-xs sm:text-sm shadow-md transition-all active:scale-95 cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[18px]">add_circle</span>
                        <span>Tambah Agenda</span>
                    </button>
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
                        placeholder="Cari judul agenda, lokasi, atau penyelenggara..."
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

        <!-- Agenda List -->
        <div v-if="filteredAgendas.length > 0" class="space-y-3.5">
            <div
                v-for="item in filteredAgendas"
                :key="item.id"
                class="bg-white rounded-2xl border border-slate-200 hover:border-emerald-500/80 transition-all p-5 shadow-2xs group flex flex-col md:flex-row md:items-start gap-4"
            >
                <!-- Date Box -->
                <div class="flex flex-col items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-50 to-amber-50/70 border border-emerald-200 text-center shrink-0 shadow-2xs group-hover:scale-105 transition-transform">
                    <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">{{ item.month }}</span>
                    <span class="text-2xl font-black text-slate-900 leading-none">{{ item.day }}</span>
                    <span class="text-[9px] text-slate-400 font-semibold">{{ item.year }}</span>
                </div>

                <!-- Content -->
                <div class="space-y-2 flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span :class="['px-2.5 py-0.5 rounded-lg text-xs font-bold border', item.badgeClass]">
                            {{ item.category }}
                        </span>
                        <h3 class="text-base font-bold text-slate-900 group-hover:text-emerald-950 transition-colors">
                            {{ item.title }}
                        </h3>
                    </div>

                    <!-- Meta: Time, Location, Organizer -->
                    <div class="flex items-center gap-y-1 gap-x-4 text-xs text-slate-500 flex-wrap">
                        <span class="inline-flex items-center gap-1 font-medium text-slate-700">
                            <span class="material-symbols-outlined text-[16px] text-emerald-700">schedule</span>
                            {{ item.time }}
                        </span>
                        <span class="inline-flex items-center gap-1 font-medium text-slate-700">
                            <span class="material-symbols-outlined text-[16px] text-emerald-700">location_on</span>
                            {{ item.location }}
                        </span>
                        <span v-if="item.organizer" class="text-slate-400">
                            Penyelenggara: <strong class="text-slate-600">{{ item.organizer }}</strong>
                        </span>
                    </div>

                    <!-- Description -->
                    <p class="text-xs text-slate-600 leading-relaxed max-w-3xl">
                        {{ item.description }}
                    </p>

                    <!-- Attendees tag -->
                    <div v-if="item.attendees" class="pt-1 flex items-center gap-1.5 text-[11px] text-slate-400">
                        <span class="material-symbols-outlined text-[14px]">group</span>
                        <span>Peserta: {{ item.attendees }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white rounded-3xl border border-slate-200 p-12 text-center shadow-2xs space-y-3">
            <div class="w-16 h-16 rounded-3xl bg-emerald-50 text-emerald-700 flex items-center justify-center mx-auto border border-emerald-100">
                <span class="material-symbols-outlined text-3xl">event_busy</span>
            </div>
            <h3 class="text-base font-bold text-slate-800">Tidak Ada Agenda Ditemukan</h3>
            <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto">
                Tidak ada agenda yang sesuai dengan kriteria filter atau pencarian Anda.
            </p>
        </div>

        <!-- Modal Tambah Agenda -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showAddModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
                @click.self="showAddModal = false"
            >
                <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl border border-slate-200">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-700 text-xl">event_upcoming</span>
                            <h3 class="text-sm font-bold text-slate-900">Tambah Agenda Baru</h3>
                        </div>
                        <button
                            type="button"
                            @click="showAddModal = false"
                            class="text-slate-400 hover:text-slate-600 rounded-full p-1 cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Agenda</label>
                            <input
                                v-model="newAgenda.title"
                                type="text"
                                placeholder="Contoh: Rapat Kerja Dewan Guru..."
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-600"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori</label>
                                <select
                                    v-model="newAgenda.category"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-600"
                                >
                                    <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal</label>
                                <input
                                    v-model="newAgenda.date"
                                    type="date"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-600"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Waktu</label>
                                <input
                                    v-model="newAgenda.time"
                                    type="text"
                                    placeholder="Contoh: 08:00 - 10:00 WIB"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-600"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Lokasi</label>
                                <input
                                    v-model="newAgenda.location"
                                    type="text"
                                    placeholder="Contoh: Ruang Rapat Utama"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-600"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan / Deskripsi</label>
                            <textarea
                                v-model="newAgenda.description"
                                rows="3"
                                placeholder="Detail kegiatan, agenda pembahasan, dsb..."
                                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-600 resize-none"
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2">
                        <button
                            type="button"
                            @click="showAddModal = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="handleSaveAgenda"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-800 hover:bg-emerald-900 transition-colors shadow-2xs cursor-pointer"
                        >
                            Simpan Agenda
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
