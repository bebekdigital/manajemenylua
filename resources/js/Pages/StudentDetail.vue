<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
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
    permissions: {
        type: Object,
        default: () => ({
            can_view: true,
            can_create: false,
            can_update: false,
            can_delete: false,
            can_export: false,
            can_import: false,
        }),
    },
});

const activeTab = ref('identitas');

const timelineHeaders = computed(() => {
    if (!props.student.riwayat_keaktifan) return [];
    
    const headersMap = new Map();
    props.student.riwayat_keaktifan.forEach(r => {
        const key = `${r.academic_year} ${r.semester}`;
        if (!headersMap.has(key)) {
            headersMap.set(key, { ta: r.academic_year, semester: r.semester, key });
        }
    });
    return Array.from(headersMap.values());
});

const timelineRows = computed(() => {
    if (!props.student.riwayat_keaktifan) return [];
    
    const unitsMap = new Map();
    props.student.riwayat_keaktifan.forEach(r => {
        const u = r.unit || '-';
        if (!unitsMap.has(u)) {
            unitsMap.set(u, {});
        }
        const key = `${r.academic_year} ${r.semester}`;
        unitsMap.get(u)[key] = r;
    });
    
    return Array.from(unitsMap.entries()).map(([unit, data]) => ({
        unit,
        data
    }));
});




const tabs = [
    { id: 'identitas', label: 'Identitas', icon: 'person' },
    { id: 'alamat', label: 'Alamat', icon: 'home' },
    { id: 'keluarga', label: 'Keluarga', icon: 'family_restroom' },
    { id: 'registrasi', label: 'Registrasi', icon: 'how_to_reg' },
    { id: 'kesejahteraan', label: 'Kesejahteraan', icon: 'volunteer_activism' },
];

function getAlamatLengkap(s) {
    if (!s) return '-';
    let alamat = s.jalan ? s.jalan : '';
    if (s.rt && s.rw) alamat += `, RT ${s.rt} / RW ${s.rw}`;
    else if (s.rt) alamat += `, RT ${s.rt}`;
    else if (s.rw) alamat += `, RW ${s.rw}`;
    return [alamat, s.dusun, s.desa, s.kecamatan, s.kabupaten, s.provinsi]
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

function getInitials(name) {
    if (!name) return '??';
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map(w => w[0]?.toUpperCase() || '')
        .join('');
}

function getAvatarStyle(s) {
    if (s?.jk === 'L') {
        return 'bg-blue-50 text-blue-800 border-blue-200/80';
    }
    return 'bg-rose-50 text-rose-800 border-rose-200/80';
}

const tabsContainerRef = ref(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(false);

function checkScroll() {
    if (!tabsContainerRef.value) return;
    const { scrollLeft, scrollWidth, clientWidth } = tabsContainerRef.value;
    canScrollLeft.value = scrollLeft > 10;
    canScrollRight.value = scrollLeft + clientWidth < scrollWidth - 10;
}

function scrollTabs(direction) {
    if (!tabsContainerRef.value) return;
    const scrollAmount = 200;
    tabsContainerRef.value.scrollBy({
        left: direction === 'left' ? -scrollAmount : scrollAmount,
        behavior: 'smooth'
    });
}

function selectTab(id) {
    activeTab.value = id;
    nextTick(() => {
        const activeBtn = tabsContainerRef.value?.querySelector(`[data-tab-id="${id}"]`);
        if (activeBtn) {
            activeBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
        checkScroll();
    });
}

onMounted(() => {
    checkScroll();
    window.addEventListener('resize', checkScroll);
});

onUnmounted(() => {
    window.removeEventListener('resize', checkScroll);
});
</script>

<template>
    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop pb-24 md:pb-margin-desktop bg-white min-h-screen">
        <Head :title="`Detail Siswa - ${student.nama}`" />

        <div class="max-w-[1440px] mx-auto">
            <!-- Back Button -->
            <div class="mb-5 flex items-center justify-between gap-4">
                <Link
                    href="/students"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs sm:text-sm font-semibold shadow-2xs transition-all active:scale-95"
                >
                    <span class="material-symbols-outlined text-[18px] text-slate-500">arrow_back</span>
                    <span>Kembali ke Data Siswa</span>
                </Link>
            </div>

            <!-- Header Card Identitas Profil Siswa -->
            <div class="bg-white rounded-3xl border border-slate-200/90 p-5 sm:p-6 shadow-xs mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4 sm:gap-5 min-w-0 flex-1">
                    <!-- Foto / Avatar Siswa -->
                    <img
                        v-if="student.photo_url"
                        :src="student.photo_url"
                        :alt="student.nama"
                        class="w-16 h-16 sm:w-20 sm:h-20 max-w-[64px] max-h-[64px] sm:max-w-[80px] sm:max-h-[80px] aspect-square rounded-2xl object-cover border border-slate-200 shadow-2xs shrink-0"
                    />
                    <div
                        v-else
                        :class="[
                            'w-16 h-16 sm:w-20 sm:h-20 max-w-[64px] max-h-[64px] sm:max-w-[80px] sm:max-h-[80px] aspect-square rounded-2xl flex items-center justify-center font-bold text-xl sm:text-2xl border shadow-2xs shrink-0',
                            getAvatarStyle(student)
                        ]"
                    >
                        <span>{{ getInitials(student.nama) }}</span>
                    </div>

                    <!-- Informasi Profil Singkat Siswa -->
                    <div class="space-y-1.5 min-w-0 flex-1">
                        <h1 class="text-lg sm:text-2xl font-extrabold text-slate-900 tracking-tight leading-tight break-words">
                            {{ student.nama }}
                        </h1>
                        <div class="flex items-center gap-1.5 text-xs sm:text-sm text-slate-500 font-medium flex-wrap">
                            <span>NISN: <span class="font-mono text-slate-700 font-semibold">{{ student.nisn || '-' }}</span></span>
                            <span>•</span>
                            <span>{{ student.unit || '-' }}</span>
                            <span v-if="student.kelas">•</span>
                            <span v-if="student.kelas">Kelas {{ student.kelas }}</span>
                        </div>

                        <!-- Badges Identitas -->
                        <div class="flex items-center gap-2 flex-wrap pt-0.5">
                            <!-- Badge Status Keaktifan (Hijau Terang) -->
                            <span
                                :class="[
                                    'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border shadow-2xs',
                                    (student.student_status || 'aktif').toLowerCase() === 'aktif'
                                        ? 'bg-emerald-100 text-emerald-950 border-emerald-300'
                                        : 'bg-rose-50 text-rose-800 border-rose-200'
                                ]"
                            >
                                <span
                                    class="w-2 h-2 rounded-full"
                                    :class="(student.student_status || 'aktif').toLowerCase() === 'aktif' ? 'bg-emerald-600' : 'bg-rose-500'"
                                ></span>
                                <span>{{ (student.student_status || 'aktif').toLowerCase() === 'aktif' ? 'Aktif' : 'Tidak Aktif' }}</span>
                            </span>

                            <!-- Keterangan & Tanggal Tidak Aktif jika Tidak Aktif -->
                            <span
                                v-if="(student.student_status || 'aktif').toLowerCase() !== 'aktif' && (student.keterangan_tidak_aktif || student.ket_tidak_aktif)"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100/70 text-rose-900 border border-rose-300"
                            >
                                <span class="material-symbols-outlined text-[14px]">info</span>
                                <span>{{ student.keterangan_tidak_aktif || student.ket_tidak_aktif }} <template v-if="student.tanggal_tidak_aktif">({{ student.tanggal_tidak_aktif }})</template></span>
                            </span>

                            <!-- Badge Jenis Kelamin -->
                            <span
                                v-if="student.jk"
                                :class="[
                                    'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold border',
                                    student.jk === 'L' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-rose-50 text-rose-700 border-rose-200'
                                ]"
                            >
                                {{ student.jk === 'L' ? 'Laki-laki' : 'Perempuan' }}
                            </span>

                            <!-- Badge Program -->
                            <span
                                v-if="student.program"
                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200"
                            >
                                {{ student.program }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Segmented Capsule Tabs Navigation -->
            <div class="relative mb-5">
                <!-- Left Scroll Arrow & Fade Hint -->
                <Transition
                    enter-active-class="transition duration-150 ease-out"
                    enter-from-class="opacity-0 -translate-x-2"
                    enter-to-class="opacity-100 translate-x-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-x-0"
                    leave-to-class="opacity-0 -translate-x-2"
                >
                    <div
                        v-if="canScrollLeft"
                        class="absolute left-0 top-0 bottom-0 z-20 flex items-center pr-6 pl-1 bg-gradient-to-r from-white via-white/95 to-transparent pointer-events-none"
                    >
                        <button
                            type="button"
                            @click="scrollTabs('left')"
                            class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white shadow-md border border-slate-200 text-slate-700 flex items-center justify-center hover:bg-slate-50 pointer-events-auto active:scale-95 transition-transform"
                            title="Gulir tab ke kiri"
                        >
                            <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                        </button>
                    </div>
                </Transition>

                <!-- Tabs Scroll Container -->
                <div
                    ref="tabsContainerRef"
                    @scroll="checkScroll"
                    class="overflow-x-auto no-scrollbar scroll-smooth py-1"
                >
                    <div class="bg-slate-100 p-1.5 rounded-2xl sm:rounded-full inline-flex items-center gap-1 border border-slate-200/90 min-w-max">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            :data-tab-id="tab.id"
                            type="button"
                            @click="selectTab(tab.id)"
                            :class="[
                                'inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs sm:text-sm transition-all duration-150 cursor-pointer shrink-0 whitespace-nowrap',
                                activeTab === tab.id
                                    ? 'bg-emerald-100 text-emerald-950 font-bold border border-emerald-300 shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900 font-medium border border-transparent hover:bg-slate-200/60'
                            ]"
                        >
                            <span class="material-symbols-outlined text-[18px]">{{ tab.icon }}</span>
                            <span>{{ tab.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Right Scroll Arrow & Fade Hint -->
                <Transition
                    enter-active-class="transition duration-150 ease-out"
                    enter-from-class="opacity-0 translate-x-2"
                    enter-to-class="opacity-100 translate-x-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-x-0"
                    leave-to-class="opacity-0 translate-x-2"
                >
                    <div
                        v-if="canScrollRight"
                        class="absolute right-0 top-0 bottom-0 z-20 flex items-center pl-6 pr-1 bg-gradient-to-l from-white via-white/95 to-transparent pointer-events-none"
                    >
                        <button
                            type="button"
                            @click="scrollTabs('right')"
                            class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white shadow-md border border-slate-200 text-slate-700 flex items-center justify-center hover:bg-slate-50 pointer-events-auto active:scale-95 transition-transform"
                            title="Ada tab lain di sebelah kanan"
                        >
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </button>
                    </div>
                </Transition>
            </div>

            <!-- Card Konten Tab Siswa -->
            <div class="bg-white rounded-3xl border border-slate-200/90 p-5 sm:p-7 shadow-xs">
                <!-- TAB: Identitas -->
                <div v-if="activeTab === 'identitas'" class="space-y-6">
                    <div>
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Identitas Pokok Siswa</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Nama Lengkap</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.nama }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">NISN</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1 font-mono">{{ student.nisn || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">NIPD</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1 font-mono">{{ student.nipd || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">NIK</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1 font-mono">{{ student.nik || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">No. KK</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1 font-mono">{{ student.no_kk || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Unit Kerja</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.unit || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Program</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">
                                    <span :class="[
                                        'inline-flex items-center px-2 py-0.5 rounded-md text-[10px] sm:text-xs font-semibold',
                                        student.program === 'Boarding' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' :
                                        student.program === 'Fullday' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' :
                                        'bg-slate-100 text-slate-700 border border-slate-200'
                                    ]">
                                        {{ student.program || 'Umum' }}
                                    </span>
                                </p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Kelas</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.kelas || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Jenjang</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.jenjang || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tingkat</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.tingkat || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Jenis Kelamin</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.jk === 'L' ? 'Laki-laki' : 'Perempuan' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tempat, Tanggal Lahir</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.ttl || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Agama</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.agama || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">No. WhatsApp 1</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.no_wa || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">No. WhatsApp 2</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.no_wa_2 || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Email</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.email || '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: Alamat -->
                <div v-if="activeTab === 'alamat'" class="space-y-6">
                    <div>
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Data Tempat Tinggal Siswa</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                            <div class="md:col-span-2">
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Alamat Lengkap</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ getAlamatLengkap(student) }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Jalan</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.jalan || '-' }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">RT</label>
                                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.rt || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">RW</label>
                                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.rw || '-' }}</p>
                                </div>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Dusun</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.dusun || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Desa / Kelurahan</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.desa || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Kecamatan</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.kecamatan || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Kabupaten / Kota</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.kabupaten || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Provinsi</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.provinsi || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Jenis Tinggal</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.jenis_tinggal || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Alat Transportasi</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.alat_transportasi || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Jarak Rumah ke Sekolah</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.jarak_rumah || '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: Keluarga -->
                <div v-if="activeTab === 'keluarga'" class="space-y-6">
                    <!-- Data Siswa di Keluarga -->
                    <div>
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Data Siswa dalam Keluarga</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3">
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Status dalam Keluarga</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.status_keluarga || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Anak ke</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.anak_ke || '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Ayah -->
                    <div class="pt-2">
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Data Ayah</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3">
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Nama Ayah</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.ayah?.nama || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tahun Lahir</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.ayah?.tahun_lahir || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pekerjaan</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.ayah?.pekerjaan || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Penghasilan</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.ayah?.penghasilan || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">NIK</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1 font-mono">{{ student.ayah?.nik || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Status</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.ayah?.status || '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Ibu -->
                    <div class="pt-2">
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Data Ibu</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3">
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Nama Ibu</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.ibu?.nama || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tahun Lahir</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.ibu?.tahun_lahir || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pekerjaan</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.ibu?.pekerjaan || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Penghasilan</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.ibu?.penghasilan || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">NIK</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1 font-mono">{{ student.ibu?.nik || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Status</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.ibu?.status || '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Bisnis / Usaha Keluarga -->
                    <div class="pt-2">
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Bisnis / Usaha Keluarga</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3">
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Memiliki Bisnis/Usaha</label>
                                <p class="mt-1">
                                    <span
                                        v-if="student.bisnis !== null"
                                        :class="[
                                            'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold',
                                            student.bisnis?.has_bisnis ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200'
                                        ]"
                                    >
                                        <span class="material-symbols-outlined text-[14px]">{{ student.bisnis?.has_bisnis ? 'store' : 'store_mall_directory' }}</span>
                                        {{ student.bisnis?.has_bisnis ? 'Ya' : 'Tidak' }}
                                    </span>
                                    <span v-else class="text-xs sm:text-sm font-semibold text-slate-900">-</span>
                                </p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Jenis Bisnis/Usaha</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.bisnis?.jenis_bisnis || '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Wali -->
                    <div v-if="student.wali" class="pt-2">
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Data Wali</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3">
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Nama Wali</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.wali.nama }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Hubungan</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.wali.hubungan || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pekerjaan</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.wali.pekerjaan || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Penghasilan</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.wali.penghasilan || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">NIK</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1 font-mono">{{ student.wali.nik || '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Saudara -->
                    <div v-if="student.saudara && student.saudara.length > 0" class="pt-2">
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                Data Saudara
                                <span class="text-xs font-normal text-slate-400 lowercase">(untuk penawaran PSB)</span>
                            </h4>
                        </div>
                        <div class="overflow-x-auto rounded-2xl border border-slate-200">
                            <table class="w-full text-xs sm:text-sm">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                                        <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-wider">No</th>
                                        <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-wider">Nama</th>
                                        <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-wider">Tanggal Lahir</th>
                                        <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-wider">Usia (Tahun)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="(sdr, idx) in student.saudara" :key="idx" class="hover:bg-slate-50/60">
                                        <td class="px-4 py-2.5 text-slate-500 font-medium">{{ idx + 1 }}</td>
                                        <td class="px-4 py-2.5 text-slate-900 font-bold">{{ sdr.nama }}</td>
                                        <td class="px-4 py-2.5 text-slate-600">{{ sdr.tanggal_lahir || '-' }}</td>
                                        <td class="px-4 py-2.5 text-slate-600">{{ hitungUsia(sdr.tanggal_lahir) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB: Registrasi -->
                <div v-if="activeTab === 'registrasi'" class="space-y-6">
                    <div>
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Data Pendaftaran Siswa</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Status Registrasi</label>
                                <p class="mt-1">
                                    <span
                                        v-if="student.status_registrasi"
                                        :class="[
                                            'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border',
                                            student.status_registrasi === 'Siswa Baru' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-blue-50 text-blue-800 border-blue-200'
                                        ]"
                                    >
                                        {{ student.status_registrasi }}
                                    </span>
                                    <span v-else class="text-xs sm:text-sm font-semibold text-slate-900">-</span>
                                </p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Sekolah Asal</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.sekolah_asal || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Diterima di Jenjang / Kelas</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.diterima_di_jenjang || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tanggal Diterima</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.formatted_tanggal_diterima || '-' }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Sumber Informasi PSB</label>
                                <p class="mt-1">
                                    <span
                                        v-if="student.info_psb"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200"
                                    >
                                        <span class="material-symbols-outlined text-[14px]">campaign</span>
                                        {{ student.info_psb }}
                                    </span>
                                    <span v-else class="text-xs sm:text-sm font-semibold text-slate-900">-</span>
                                </p>
                            </div>
                        </div>

                        <!-- Status Keaktifan Siswa -->
                        <div class="mt-6 pt-5 border-t border-slate-100">
                            <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                                <span
                                    class="w-2.5 h-2.5 rounded-full inline-block"
                                    :class="(student.status_keaktifan === 'Tidak Aktif' || student.student_status === 'tidak_aktif') ? 'bg-rose-500' : 'bg-emerald-600'"
                                ></span>
                                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Status Keaktifan Siswa</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-4">
                                <div>
                                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Status Keaktifan</label>
                                    <p class="mt-1">
                                        <span
                                            v-if="student.status_keaktifan === 'Tidak Aktif' || student.student_status === 'tidak_aktif'"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            Tidak Aktif
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                            Aktif
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Keterangan Tidak Aktif</label>
                                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">
                                        <span
                                            v-if="student.keterangan_tidak_aktif || student.ket_tidak_aktif"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200"
                                        >
                                            <span class="material-symbols-outlined text-[14px] text-amber-600">info</span>
                                            {{ student.keterangan_tidak_aktif || student.ket_tidak_aktif }}
                                        </span>
                                        <span v-else class="text-slate-400 font-normal">-</span>
                                    </p>
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tanggal Tidak Aktif</label>
                                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">
                                        <span v-if="student.tanggal_tidak_aktif" class="inline-flex items-center gap-1.5">
                                            <span class="font-mono text-slate-800">{{ student.tanggal_tidak_aktif }}</span>
                                            <span v-if="student.formatted_tanggal_tidak_aktif" class="text-xs font-normal text-slate-500">({{ student.formatted_tanggal_tidak_aktif }})</span>
                                        </span>
                                        <span v-else class="text-slate-400 font-normal">-</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Riwayat Keaktifan Siswa Timeline -->
                    <div class="mt-6 pt-2" v-if="student.riwayat_keaktifan && student.riwayat_keaktifan.length > 0">
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Riwayat Keaktifan Siswa</h4>
                        </div>
                        <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-2xs relative">
                            <table class="w-full text-left text-xs sm:text-sm border-collapse whitespace-nowrap">
                                <thead>
                                    <tr class="border-b border-slate-200 bg-slate-50">
                                        <th class="p-3.5 font-bold text-slate-700 min-w-[160px] sticky left-0 z-10 border-r border-slate-200 bg-slate-50">Unit</th>
                                        <th v-for="header in timelineHeaders" :key="header.key" class="p-3.5 font-bold text-slate-700 text-center">
                                            {{ header.ta }} <br/>
                                            <span class="text-xs text-slate-500 font-medium">{{ header.semester }}</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="row in timelineRows" :key="row.unit" class="hover:bg-slate-50/60">
                                        <td class="p-3.5 text-slate-800 font-bold bg-white sticky left-0 z-10 border-r border-slate-100">
                                            {{ row.unit }}
                                        </td>
                                        <td v-for="header in timelineHeaders" :key="header.key" class="p-3.5 text-center min-w-[160px]">
                                            <template v-if="row.data[header.key]">
                                                <span v-if="row.data[header.key].status === 'aktif'" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-950 border border-emerald-300 shadow-2xs">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                    Kelas {{ row.data[header.key].kelas || 'Aktif' }}
                                                </span>
                                                <div v-else class="flex flex-col items-center justify-center">
                                                    <span class="text-rose-700 font-bold capitalize text-xs bg-rose-50 border border-rose-200 px-2.5 py-0.5 rounded-full">
                                                        {{ row.data[header.key].status.replace('_', ' ') }}
                                                    </span>
                                                    <span v-if="row.data[header.key].keterangan" class="text-[10px] text-rose-600 mt-1 max-w-[140px] truncate" :title="row.data[header.key].keterangan">
                                                        ({{ row.data[header.key].keterangan }})
                                                    </span>
                                                </div>
                                            </template>
                                            <template v-else>
                                                <span class="text-slate-300">-</span>
                                            </template>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB: Kesejahteraan -->
                <div v-if="activeTab === 'kesejahteraan'" class="space-y-6">
                    <div>
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Status Bantuan Pemerintah & Kesejahteraan</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- PIP -->
                            <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/70">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs sm:text-sm font-bold text-slate-800">Kelayakan PIP</span>
                                    <span
                                        :class="[
                                            'text-xs px-2.5 py-0.5 rounded-full font-bold border',
                                            student.bantuan?.pip ? 'bg-emerald-100 text-emerald-950 border-emerald-300' : 'bg-slate-100 text-slate-500 border-slate-200'
                                        ]"
                                    >
                                        {{ student.bantuan?.pip ? 'Layak' : 'Tidak Layak' }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-2">Alasan Layak PIP: <span class="font-semibold text-slate-800">{{ student.bantuan?.pip_keterangan || 'Tidak ada data' }}</span></p>
                            </div>

                            <!-- KIP -->
                            <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/70">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs sm:text-sm font-bold text-slate-800">Penerima KIP</span>
                                    <span
                                        :class="[
                                            'text-xs px-2.5 py-0.5 rounded-full font-bold border',
                                            student.bantuan?.kip ? 'bg-emerald-100 text-emerald-950 border-emerald-300' : 'bg-slate-100 text-slate-500 border-slate-200'
                                        ]"
                                    >
                                        {{ student.bantuan?.kip ? 'Menerima' : 'Tidak Menerima' }}
                                    </span>
                                </div>
                                <div class="flex flex-col gap-1 mt-2">
                                    <p class="text-xs text-slate-500">No. KIP: <span class="font-mono font-semibold text-slate-800">{{ student.bantuan?.no_kip || '-' }}</span></p>
                                    <p class="text-xs text-slate-500">Nama di KIP: <span class="font-semibold text-slate-800">{{ student.bantuan?.nama_di_kip || '-' }}</span></p>
                                </div>
                            </div>

                            <!-- Desil -->
                            <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/70 md:col-span-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-emerald-100 border border-emerald-200 flex items-center justify-center text-emerald-800 shrink-0">
                                        <span class="material-symbols-outlined text-[20px]">pie_chart</span>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Kategori Kesejahteraan (Desil)</p>
                                        <p class="text-sm font-bold text-slate-900 mt-0.5">
                                            {{ student.bantuan?.desil ? 'Desil ' + student.bantuan.desil : 'Belum ada data Desil' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kebutuhan Khusus -->
                    <div class="pt-2">
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Kebutuhan Khusus</h4>
                        </div>
                        <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/70">
                            <p class="text-xs sm:text-sm font-semibold text-slate-800">{{ student.kebutuhan_khusus || 'Tidak ada catatan kebutuhan khusus.' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>


