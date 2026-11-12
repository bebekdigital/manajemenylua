<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick, inject } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    allUnits: {
        type: Array,
        default: () => ['SDIT', 'SMPIT'],
    },
    allLevels: {
        type: Array,
        default: () => ['SD', 'SMP'],
    },
    allPrograms: {
        type: Array,
        default: () => ['Umum', 'Fullday', 'Tahfidz'],
    },
    allClasses: {
        type: Array,
        default: () => [],
    },
    academicYears: {
        type: Array,
        default: () => [],
    },
    activeAcademicYear: {
        type: Object,
        default: null,
    },
});

const page = usePage();
const activeTab = ref('identitas');

// Toast Notification
const toast = reactive({
    show: false,
    type: 'success',
    title: '',
    message: '',
});
let toastTimer = null;

function showToast(type, message, title = null) {
    if (!message) return;
    if (toastTimer) clearTimeout(toastTimer);
    toast.type = type;
    toast.title = title || (type === 'success' ? 'Berhasil' : 'Terjadi Kesalahan');
    toast.message = message;
    toast.show = true;
    toastTimer = setTimeout(() => {
        toast.show = false;
    }, 4000);
}

const tabs = [
    { id: 'identitas', label: 'Identitas', icon: 'person' },
    { id: 'alamat', label: 'Alamat', icon: 'home' },
    { id: 'keluarga', label: 'Keluarga', icon: 'family_restroom' },
    { id: 'registrasi', label: 'Registrasi', icon: 'how_to_reg' },
    { id: 'kesejahteraan', label: 'Kesejahteraan', icon: 'volunteer_activism' },
];

const form = useForm({
    // Tab 1: Identitas
    nama: '',
    nisn: '',
    nipd: '',
    nik: '',
    no_kk: '',
    jk: 'L',
    tempat_lahir: '',
    tanggal_lahir: '',
    agama: 'Islam',
    unit: (props.allUnits || []).find(u => {
        const upper = (u || '').toUpperCase();
        return !upper.includes('PPTQ') && !upper.includes('PESANTREN') && !upper.includes('PONDOK');
    }) || props.allUnits?.[0] || 'SDIT',
    unit_pesantren: '',
    program: 'Umum',
    jenjang: props.allLevels?.[0] || 'SD',
    tingkat: '',
    classroom_id: '',
    kelas: '',
    no_wa: '',
    no_wa_2: '',
    email: '',

    // Tab 2: Alamat
    jalan: '',
    rt: '',
    rw: '',
    dusun: '',
    desa: '',
    kecamatan: '',
    kabupaten: 'Karanganyar',
    provinsi: 'Jawa Tengah',
    jenis_tinggal: 'Bersama Orang Tua',
    alat_transportasi: 'Kendaraan Pribadi',
    jarak_rumah: '',

    // Tab 3: Keluarga
    status_keluarga: 'Anak Kandung',
    anak_ke: 1,
    ayah_nama: '',
    ayah_tahun_lahir: '',
    ayah_pendidikan: '',
    ayah_pekerjaan: '',
    ayah_penghasilan: '',
    ayah_nik: '',
    ayah_status: 'Hidup',
    ibu_nama: '',
    ibu_tahun_lahir: '',
    ibu_pendidikan: '',
    ibu_pekerjaan: '',
    ibu_penghasilan: '',
    ibu_nik: '',
    ibu_status: 'Hidup',
    has_bisnis: false,
    jenis_bisnis: '',
    wali_nama: '',
    wali_tahun_lahir: '',
    wali_pendidikan: '',
    wali_pekerjaan: '',
    wali_penghasilan: '',
    wali_nik: '',
    wali_hubungan: '',

    // Tab 4: Registrasi
    status_registrasi: 'Siswa Baru',
    sekolah_asal: '',
    diterima_di_jenjang: '',
    tanggal_diterima: new Date().toISOString().slice(0, 10),
    info_psb: '',
    status_keaktifan: 'Aktif',
    keterangan_tidak_aktif: '',
    tanggal_tidak_aktif: '',

    // Tab 5: Kesejahteraan
    status_kip: false,
    no_kip: '',
    nama_di_kip: '',
    status_pip: false,
    pip_keterangan: '',
    desil: '',
    kebutuhan_khusus: '',
});

// Pilihan Unit Sekolah Formal (SDIT, SMPIT, SMAIT)
const formalUnitOptions = computed(() => {
    const list = (props.allUnits || []).filter(u => {
        const upper = (u || '').toUpperCase();
        return !upper.includes('PPTQ') && !upper.includes('PESANTREN') && !upper.includes('PONDOK');
    });
    return list.length > 0 ? list : (props.allUnits || []);
});

// Pilihan Unit Kepesantrenan / Asrama (PPTQ Ulil Albab)
const pesantrenUnitOptions = computed(() => {
    const list = (props.allUnits || []).filter(u => {
        const upper = (u || '').toUpperCase();
        return upper.includes('PPTQ') || upper.includes('PESANTREN') || upper.includes('PONDOK');
    });
    if (list.length === 0) {
        return ['PPTQ Ulil Albab'];
    }
    return list;
});

// Pilihan Program Pendidikan
const programOptions = computed(() => {
    const set = new Set();
    (props.allPrograms || []).forEach(p => {
        if (p && typeof p === 'string' && p.trim()) set.add(p.trim());
    });
    if (set.size === 0) {
        set.add('Fullday');
        set.add('Boarding');
        set.add('Umum');
    }
    if (form.program) set.add(form.program);
    return Array.from(set);
});

function onPesantrenUnitChange() {
    if (form.unit_pesantren) {
        const currentProg = (form.program || '').toLowerCase();
        if (!currentProg.includes('boarding')) {
            const boardingMatch = programOptions.value.find(p => p.toLowerCase().includes('boarding'));
            form.program = boardingMatch || 'Boarding';
        }
    } else {
        const currentProg = (form.program || '').toLowerCase();
        if (currentProg.includes('boarding')) {
            const nonBoardingMatch = programOptions.value.find(p => !p.toLowerCase().includes('boarding'));
            form.program = nonBoardingMatch || 'Fullday';
        }
    }
}

function onProgramChange() {
    const prog = (form.program || '').toLowerCase();
    if (prog.includes('boarding')) {
        if (!form.unit_pesantren) {
            form.unit_pesantren = pesantrenUnitOptions.value[0] || 'PPTQ Ulil Albab';
        }
    } else {
        if (form.unit_pesantren) {
            form.unit_pesantren = '';
        }
    }
}

// Daftar kelas yang relevan dengan unit yang dipilih
const filteredClasses = computed(() => {
    if (!props.allClasses || props.allClasses.length === 0) return [];
    if (!form.unit) return props.allClasses;
    return props.allClasses.filter((c) => {
        if (!c.unit) return true;
        return c.unit.toUpperCase().includes(form.unit.toUpperCase()) || form.unit.toUpperCase().includes(c.unit.toUpperCase());
    });
});

function handleClassChange() {
    if (form.classroom_id) {
        const selected = props.allClasses.find((c) => String(c.id) === String(form.classroom_id));
        if (selected) {
            form.kelas = selected.name;
            if (selected.grade && !form.tingkat) {
                form.tingkat = String(selected.grade);
            }
            if (selected.jenjang && !form.jenjang) {
                form.jenjang = selected.jenjang;
            }
        }
    } else {
        form.kelas = '';
    }
}

// Navigasi Tabs
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
        behavior: 'smooth',
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

const currentTabIndex = computed(() => tabs.findIndex((t) => t.id === activeTab.value));

function goToNextTab() {
    const idx = currentTabIndex.value;
    if (idx < tabs.length - 1) {
        selectTab(tabs[idx + 1].id);
    }
}

function goToPrevTab() {
    const idx = currentTabIndex.value;
    if (idx > 0) {
        selectTab(tabs[idx - 1].id);
    }
}

// Validasi & Submit
function validateAndSubmit() {
    form.clearErrors();

    const requiredFields = [
        { key: 'nama', label: 'Nama Lengkap Siswa' },
        { key: 'nisn', label: 'NISN' },
        { key: 'jk', label: 'Jenis Kelamin' },
        { key: 'tempat_lahir', label: 'Tempat Lahir' },
        { key: 'tanggal_lahir', label: 'Tanggal Lahir' },
    ];

    let firstMissing = null;
    for (const f of requiredFields) {
        if (!form[f.key] || String(form[f.key]).trim() === '') {
            form.setError(f.key, `${f.label} wajib diisi.`);
            if (!firstMissing) firstMissing = f;
        }
    }

    if (firstMissing) {
        activeTab.value = 'identitas';
        showToast('error', `Harap lengkapi kolom wajib pada tab Identitas (${firstMissing.label}).`, 'Data Belum Lengkap');
        nextTick(() => {
            const el = document.getElementById(`field-${firstMissing.key}`);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el.focus();
            }
        });
        return;
    }

    form.post('/students', {
        preserveScroll: true,
        onError: (errs) => {
            showToast('error', 'Terdapat isian yang belum valid. Silakan periksa kembali formulir.', 'Gagal Menyimpan');
            if (errs.nama || errs.nisn || errs.jk || errs.tempat_lahir || errs.tanggal_lahir) {
                activeTab.value = 'identitas';
            }
        },
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
    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop pb-28 md:pb-margin-desktop bg-white min-h-screen">
        <Head title="Tambah Siswa Baru - Foundation Data Center" />

        <div class="max-w-[1440px] mx-auto">
            <!-- Back & Header Row -->
            <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        href="/students"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs sm:text-sm font-semibold shadow-2xs transition-all active:scale-95"
                    >
                        <span class="material-symbols-outlined text-[18px] text-slate-500">arrow_back</span>
                        <span>Kembali ke Data Siswa</span>
                    </Link>
                </div>

                <!-- Top Action Buttons -->
                <div class="flex items-center gap-2 self-end sm:self-auto">
                    <Link
                        href="/students"
                        class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all shadow-2xs"
                    >
                        Batal
                    </Link>
                    <button
                        type="button"
                        @click="validateAndSubmit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-1.5 px-4 sm:px-5 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all"
                    >
                        <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                        <span v-else class="material-symbols-outlined text-[18px]">save</span>
                        <span>Simpan Data Siswa</span>
                    </button>
                </div>
            </div>

            <!-- Page Title Card -->
            <div class="bg-white rounded-3xl border border-slate-200/90 p-5 sm:p-6 shadow-xs mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-emerald-900 flex items-center justify-center text-yellow-200 shadow-2xs shrink-0">
                        <span class="material-symbols-outlined text-3xl">person_add</span>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                            Tambah Siswa Baru
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">
                            Formulir penambahan data siswa secara manual dengan rincian lengkap antar tab.
                        </p>
                    </div>
                </div>

                <div v-if="activeAcademicYear" class="self-start sm:self-auto">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-950 border border-emerald-300 shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                        <span>Tahun Ajaran Aktif: {{ activeAcademicYear.name }} ({{ activeAcademicYear.semester }})</span>
                    </span>
                </div>
            </div>

            <!-- Segmented Capsule Tabs Navigation -->
            <div class="relative mb-5">
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
                                'inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs sm:text-sm transition-all duration-150 shrink-0 whitespace-nowrap cursor-pointer',
                                activeTab === tab.id
                                    ? 'bg-emerald-100 text-emerald-950 font-bold border border-emerald-300 shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900 font-medium border border-transparent hover:bg-slate-200/60'
                            ]"
                        >
                            <span class="material-symbols-outlined text-[18px]">{{ tab.icon }}</span>
                            <span>{{ tab.label }}</span>
                            <span
                                v-if="tab.id === 'identitas' && (form.errors.nama || form.errors.nisn || form.errors.jk || form.errors.tempat_lahir || form.errors.tanggal_lahir)"
                                class="w-2 h-2 rounded-full bg-rose-500 inline-block"
                                title="Ada kolom wajib yang belum diisi"
                            ></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Card Konten Form per Tab -->
            <div class="bg-white rounded-3xl border border-slate-200/90 p-5 sm:p-7 shadow-xs">
                <!-- ============================================================ -->
                <!-- TAB 1: IDENTITAS POKOK & PENEMPATAN                         -->
                <!-- ============================================================ -->
                <div v-show="activeTab === 'identitas'" class="space-y-6">
                    <div>
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">Identitas Pokok Siswa</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                            <!-- Nama Lengkap -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Nama Lengkap Siswa <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    id="field-nama"
                                    v-model="form.nama"
                                    type="text"
                                    placeholder="cth. Muhammad Fatih Pratama"
                                    class="w-full px-3.5 py-2.5 rounded-xl border text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                    :class="form.errors.nama ? 'border-rose-400 bg-rose-50/20' : 'border-slate-200/90'"
                                />
                                <p v-if="form.errors.nama" class="text-rose-500 text-[11px] font-medium mt-1">{{ form.errors.nama }}</p>
                            </div>

                            <!-- NISN -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    NISN <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    id="field-nisn"
                                    v-model="form.nisn"
                                    type="text"
                                    maxlength="20"
                                    placeholder="cth. 0081234567"
                                    class="w-full px-3.5 py-2.5 rounded-xl border text-xs sm:text-sm font-semibold text-slate-900 font-mono placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                    :class="form.errors.nisn ? 'border-rose-400 bg-rose-50/20' : 'border-slate-200/90'"
                                />
                                <p v-if="form.errors.nisn" class="text-rose-500 text-[11px] font-medium mt-1">{{ form.errors.nisn }}</p>
                            </div>

                            <!-- NIPD -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    NIPD / Nomor Induk Sekolah
                                </label>
                                <input
                                    v-model="form.nipd"
                                    type="text"
                                    placeholder="cth. 242501001"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 font-mono placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>

                            <!-- Jenis Kelamin -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Jenis Kelamin <span class="text-rose-500">*</span>
                                </label>
                                <div class="inline-flex p-1 rounded-2xl bg-slate-100 border border-slate-200/90 gap-1 w-full sm:w-auto">
                                    <button
                                        type="button"
                                        @click="form.jk = 'L'"
                                        :class="[
                                            'flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer',
                                            form.jk === 'L' ? 'bg-emerald-900 text-yellow-200 shadow-xs' : 'text-slate-600 hover:text-slate-900'
                                        ]"
                                    >
                                        <span class="material-symbols-outlined text-[16px]">male</span>
                                        <span>Laki-laki (Putra)</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="form.jk = 'P'"
                                        :class="[
                                            'flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer',
                                            form.jk === 'P' ? 'bg-emerald-900 text-yellow-200 shadow-xs' : 'text-slate-600 hover:text-slate-900'
                                        ]"
                                    >
                                        <span class="material-symbols-outlined text-[16px]">female</span>
                                        <span>Perempuan (Putri)</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Tempat Lahir -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Tempat Lahir <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    id="field-tempat_lahir"
                                    v-model="form.tempat_lahir"
                                    type="text"
                                    placeholder="cth. Karanganyar"
                                    class="w-full px-3.5 py-2.5 rounded-xl border text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                    :class="form.errors.tempat_lahir ? 'border-rose-400 bg-rose-50/20' : 'border-slate-200/90'"
                                />
                                <p v-if="form.errors.tempat_lahir" class="text-rose-500 text-[11px] font-medium mt-1">{{ form.errors.tempat_lahir }}</p>
                            </div>

                            <!-- Tanggal Lahir -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Tanggal Lahir <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    id="field-tanggal_lahir"
                                    v-model="form.tanggal_lahir"
                                    type="date"
                                    class="w-full px-3.5 py-2.5 rounded-xl border text-xs sm:text-sm font-semibold text-slate-900 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer"
                                    :class="form.errors.tanggal_lahir ? 'border-rose-400 bg-rose-50/20' : 'border-slate-200/90'"
                                />
                                <p v-if="form.errors.tanggal_lahir" class="text-rose-500 text-[11px] font-medium mt-1">{{ form.errors.tanggal_lahir }}</p>
                            </div>

                            <!-- NIK Siswa -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    NIK Siswa (16 digit)
                                </label>
                                <input
                                    v-model="form.nik"
                                    type="text"
                                    maxlength="16"
                                    placeholder="3313xxxxxxxxxxxx"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 font-mono placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>

                            <!-- No. KK -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Nomor Kartu Keluarga (KK)
                                </label>
                                <input
                                    v-model="form.no_kk"
                                    type="text"
                                    maxlength="16"
                                    placeholder="3313xxxxxxxxxxxx"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 font-mono placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>

                            <!-- Agama -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Agama
                                </label>
                                <select
                                    v-model="form.agama"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer"
                                >
                                    <option value="Islam">Islam</option>
                                    <option value="Kristen">Kristen</option>
                                    <option value="Katolik">Katolik</option>
                                    <option value="Hindu">Hindu</option>
                                    <option value="Buddha">Buddha</option>
                                    <option value="Konghucu">Konghucu</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Penempatan Akademik & Kontak Siswa -->
                    <div class="pt-4 border-t border-slate-100">
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">Penempatan Unit & Kontak</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5">
                            <!-- 1. Unit Sekolah Formal -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Unit Sekolah Formal
                                </label>
                                <select
                                    v-model="form.unit"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer"
                                >
                                    <option v-for="u in formalUnitOptions" :key="u" :value="u">{{ u }}</option>
                                    <option v-if="form.unit && !formalUnitOptions.includes(form.unit)" :value="form.unit">{{ form.unit }}</option>
                                </select>
                                <p class="text-[11px] text-slate-500 mt-1">Satuan pendidikan formal (Kemdikbud/Kemenag).</p>
                            </div>

                            <!-- 2. Unit Kepesantrenan / Asrama -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Unit Kepesantrenan / Asrama
                                </label>
                                <select
                                    v-model="form.unit_pesantren"
                                    @change="onPesantrenUnitChange"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer"
                                >
                                    <option value="">-- Tidak Berasrama (Non-Boarding) --</option>
                                    <option v-for="p in pesantrenUnitOptions" :key="p" :value="p">{{ p }}</option>
                                    <option v-if="form.unit_pesantren && !pesantrenUnitOptions.includes(form.unit_pesantren)" :value="form.unit_pesantren">{{ form.unit_pesantren }}</option>
                                </select>
                                <p class="text-[11px] text-slate-500 mt-1">Pilih pondok pesantren jika siswa tinggal di asrama.</p>
                            </div>

                            <!-- 3. Program Pendidikan -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Program Pendidikan
                                </label>
                                <select
                                    v-model="form.program"
                                    @change="onProgramChange"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer"
                                >
                                    <option v-for="p in programOptions" :key="p" :value="p">{{ p }}</option>
                                </select>
                                <p class="text-[11px] text-slate-500 mt-1">
                                    <span v-if="form.unit_pesantren" class="text-indigo-600 font-medium">Siswa asrama: {{ form.unit_pesantren }}</span>
                                    <span v-else>Siswa non-asrama (Fullday / Reguler).</span>
                                </p>
                            </div>

                            <!-- 4. Jenjang -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Jenjang
                                </label>
                                <select
                                    v-model="form.jenjang"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer"
                                >
                                    <option v-for="lvl in allLevels" :key="lvl" :value="lvl">{{ lvl }}</option>
                                </select>
                            </div>

                            <!-- 5. Tingkat / Tingkatan Kelas -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Tingkat (1 - 12)
                                </label>
                                <input
                                    v-model="form.tingkat"
                                    type="text"
                                    placeholder="cth. 1 atau 7"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>

                            <!-- 6. Kelas / Rombel -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Kelas / Rombel (Tahun Ajaran Aktif)
                                </label>
                                <select
                                    v-model="form.classroom_id"
                                    @change="handleClassChange"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer"
                                >
                                    <option value="">-- Belum Dipetakan --</option>
                                    <option v-for="c in filteredClasses" :key="c.id" :value="c.id">
                                        {{ c.unit }} - Kelas {{ c.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- 7. No. WhatsApp Siswa / Ortu -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Nomor WhatsApp (Utama)
                                </label>
                                <input
                                    v-model="form.no_wa"
                                    type="tel"
                                    placeholder="cth. 081234567890"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>

                            <!-- 8. No. WhatsApp Alternatif -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Nomor WhatsApp (Alternatif)
                                </label>
                                <input
                                    v-model="form.no_wa_2"
                                    type="tel"
                                    placeholder="cth. 089876543210"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>

                            <!-- 9. Email Siswa / Ortu -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Email Siswa / Orang Tua
                                </label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    placeholder="cth. siswa@domain.sch.id"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!-- TAB 2: ALAMAT & TEMPAT TINGGAL                              -->
                <!-- ============================================================ -->
                <div v-show="activeTab === 'alamat'" class="space-y-6">
                    <div>
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">Data Alamat & Tempat Tinggal</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                            <!-- Jalan -->
                            <div class="md:col-span-2">
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Nama Jalan / Alamat Rumah
                                </label>
                                <input
                                    v-model="form.jalan"
                                    type="text"
                                    placeholder="cth. Jl. Lawu Timur No. 45"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>

                            <!-- RT & RW -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">RT</label>
                                    <input
                                        v-model="form.rt"
                                        type="text"
                                        placeholder="01"
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                    />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">RW</label>
                                    <input
                                        v-model="form.rw"
                                        type="text"
                                        placeholder="05"
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                    />
                                </div>
                            </div>

                            <!-- Dusun -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Dusun / Lingkungan</label>
                                <input
                                    v-model="form.dusun"
                                    type="text"
                                    placeholder="cth. Bejen"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>

                            <!-- Desa / Kelurahan -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Desa / Kelurahan</label>
                                <input
                                    v-model="form.desa"
                                    type="text"
                                    placeholder="cth. Bejen"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>

                            <!-- Kecamatan -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Kecamatan</label>
                                <input
                                    v-model="form.kecamatan"
                                    type="text"
                                    placeholder="cth. Karanganyar"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>

                            <!-- Kabupaten / Kota -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Kabupaten / Kota</label>
                                <input
                                    v-model="form.kabupaten"
                                    type="text"
                                    placeholder="cth. Karanganyar"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>

                            <!-- Provinsi -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Provinsi</label>
                                <input
                                    v-model="form.provinsi"
                                    type="text"
                                    placeholder="cth. Jawa Tengah"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>

                            <!-- Jenis Tinggal -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Jenis Tinggal</label>
                                <select
                                    v-model="form.jenis_tinggal"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer"
                                >
                                    <option value="Bersama Orang Tua">Bersama Orang Tua</option>
                                    <option value="Wali">Wali</option>
                                    <option value="Asrama / Pesantren">Asrama / Pesantren</option>
                                    <option value="Kost">Kost</option>
                                    <option value="Panti Asuhan">Panti Asuhan</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>

                            <!-- Alat Transportasi -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Alat Transportasi</label>
                                <select
                                    v-model="form.alat_transportasi"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer"
                                >
                                    <option value="Kendaraan Pribadi">Kendaraan Pribadi</option>
                                    <option value="Jemputan Sekolah">Jemputan Sekolah</option>
                                    <option value="Jalan Kaki">Jalan Kaki</option>
                                    <option value="Sepeda">Sepeda</option>
                                    <option value="Angkutan Umum">Angkutan Umum</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>

                            <!-- Jarak Rumah ke Sekolah -->
                            <div class="md:col-span-2">
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Jarak Rumah ke Sekolah</label>
                                <input
                                    v-model="form.jarak_rumah"
                                    type="text"
                                    placeholder="cth. Kurang dari 1 km, atau 3.5 km"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!-- TAB 3: DATA KELUARGA & ORANG TUA                            -->
                <!-- ============================================================ -->
                <div v-show="activeTab === 'keluarga'" class="space-y-6">
                    <!-- Status Anak -->
                    <div>
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">Status Anak dalam Keluarga</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Status dalam Keluarga</label>
                                <input
                                    v-model="form.status_keluarga"
                                    type="text"
                                    placeholder="cth. Anak Kandung"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Anak ke-</label>
                                <input
                                    v-model="form.anak_ke"
                                    type="number"
                                    min="1"
                                    max="30"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Data Ayah -->
                    <div class="pt-4 border-t border-slate-100">
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">Data Ayah</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Ayah</label>
                                <input
                                    v-model="form.ayah_nama"
                                    type="text"
                                    placeholder="Nama lengkap ayah"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Tahun Lahir Ayah</label>
                                <input
                                    v-model="form.ayah_tahun_lahir"
                                    type="number"
                                    placeholder="cth. 1980"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">NIK Ayah</label>
                                <input
                                    v-model="form.ayah_nik"
                                    type="text"
                                    maxlength="16"
                                    placeholder="3313xxxxxxxxxxxx"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 font-mono placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Pendidikan Ayah</label>
                                <input
                                    v-model="form.ayah_pendidikan"
                                    type="text"
                                    placeholder="cth. S1 / SMA"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Pekerjaan Ayah</label>
                                <input
                                    v-model="form.ayah_pekerjaan"
                                    type="text"
                                    placeholder="cth. Wiraswasta / PNS"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Penghasilan Ayah</label>
                                <input
                                    v-model="form.ayah_penghasilan"
                                    type="text"
                                    placeholder="cth. 3.000.000 - 5.000.000"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Data Ibu -->
                    <div class="pt-4 border-t border-slate-100">
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">Data Ibu</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Ibu</label>
                                <input
                                    v-model="form.ibu_nama"
                                    type="text"
                                    placeholder="Nama lengkap ibu"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Tahun Lahir Ibu</label>
                                <input
                                    v-model="form.ibu_tahun_lahir"
                                    type="number"
                                    placeholder="cth. 1983"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">NIK Ibu</label>
                                <input
                                    v-model="form.ibu_nik"
                                    type="text"
                                    maxlength="16"
                                    placeholder="3313xxxxxxxxxxxx"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 font-mono placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Pendidikan Ibu</label>
                                <input
                                    v-model="form.ibu_pendidikan"
                                    type="text"
                                    placeholder="cth. S1 / SMA"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Pekerjaan Ibu</label>
                                <input
                                    v-model="form.ibu_pekerjaan"
                                    type="text"
                                    placeholder="cth. Ibu Rumah Tangga / Guru"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Penghasilan Ibu</label>
                                <input
                                    v-model="form.ibu_penghasilan"
                                    type="text"
                                    placeholder="cth. Tidak Berpenghasilan / 2.000.000"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Usaha Keluarga & Wali -->
                    <div class="pt-4 border-t border-slate-100">
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">Usaha Keluarga & Wali (Opsional)</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                            <div class="p-4 rounded-2xl border border-slate-200/90 bg-slate-50/60">
                                <label class="flex items-center gap-2 cursor-pointer mb-2">
                                    <input
                                        v-model="form.has_bisnis"
                                        type="checkbox"
                                        class="rounded border-slate-300 text-emerald-800 focus:ring-emerald-500/20"
                                    />
                                    <span class="text-xs sm:text-sm font-bold text-slate-800">Keluarga Memiliki Usaha / Bisnis</span>
                                </label>
                                <input
                                    v-if="form.has_bisnis"
                                    v-model="form.jenis_bisnis"
                                    type="text"
                                    placeholder="Sebutkan jenis usaha/bisnis keluarga"
                                    class="w-full px-3.5 py-2 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all mt-2"
                                />
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Wali (Jika ada)</label>
                                <input
                                    v-model="form.wali_nama"
                                    type="text"
                                    placeholder="Nama wali siswa jika tidak bersama ortu"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!-- TAB 4: DATA REGISTRASI & KEAKTIFAN                          -->
                <!-- ============================================================ -->
                <div v-show="activeTab === 'registrasi'" class="space-y-6">
                    <div>
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">Informasi Pendaftaran</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                            <!-- Status Registrasi -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Status Registrasi</label>
                                <select
                                    v-model="form.status_registrasi"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer"
                                >
                                    <option value="Siswa Baru">Siswa Baru</option>
                                    <option value="Mutasi Masuk">Mutasi Masuk</option>
                                </select>
                            </div>

                            <!-- Sekolah Asal -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Sekolah Asal</label>
                                <input
                                    v-model="form.sekolah_asal"
                                    type="text"
                                    placeholder="cth. TK Islam Terpadu Al-Azhar"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>

                            <!-- Diterima di Jenjang / Kelas -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Diterima di Jenjang / Kelas</label>
                                <input
                                    v-model="form.diterima_di_jenjang"
                                    type="text"
                                    placeholder="cth. SDIT / Kelas 1"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>

                            <!-- Tanggal Diterima -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Tanggal Diterima</label>
                                <input
                                    v-model="form.tanggal_diterima"
                                    type="date"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer"
                                />
                            </div>

                            <!-- Sumber Informasi PSB -->
                            <div class="md:col-span-2">
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Sumber Informasi PSB</label>
                                <input
                                    v-model="form.info_psb"
                                    type="text"
                                    placeholder="cth. Brosur / Pamflet, Instagram, Rekomendasi Keluarga/Teman"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Status Keaktifan -->
                    <div class="pt-4 border-t border-slate-100">
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">Status Keaktifan Siswa</h4>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-2">Status Awal</label>
                                <div class="inline-flex p-1 rounded-2xl bg-slate-100 border border-slate-200/90 gap-1">
                                    <button
                                        type="button"
                                        @click="form.status_keaktifan = 'Aktif'"
                                        :class="[
                                            'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer',
                                            form.status_keaktifan === 'Aktif'
                                                ? 'bg-emerald-900 text-yellow-200 shadow-xs'
                                                : 'text-slate-600 hover:text-slate-900'
                                        ]"
                                    >
                                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                        <span>Aktif</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="form.status_keaktifan = 'Tidak Aktif'"
                                        :class="[
                                            'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer',
                                            form.status_keaktifan === 'Tidak Aktif'
                                                ? 'bg-rose-700 text-white shadow-xs'
                                                : 'text-slate-600 hover:text-slate-900'
                                        ]"
                                    >
                                        <span class="w-2 h-2 rounded-full bg-rose-300"></span>
                                        <span>Tidak Aktif</span>
                                    </button>
                                </div>
                            </div>

                            <div
                                v-if="form.status_keaktifan === 'Tidak Aktif'"
                                class="p-4 rounded-2xl bg-rose-50/60 border border-rose-200/80 grid grid-cols-1 md:grid-cols-2 gap-4"
                            >
                                <div>
                                    <label class="block text-[11px] font-bold text-rose-900 uppercase tracking-wider mb-1">Keterangan Tidak Aktif</label>
                                    <select
                                        v-model="form.keterangan_tidak_aktif"
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-rose-200 text-xs sm:text-sm font-semibold text-rose-950 bg-white shadow-2xs focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all cursor-pointer"
                                    >
                                        <option value="">-- Pilih Keterangan --</option>
                                        <option value="Lulus">Lulus</option>
                                        <option value="Mutasi / Pindah Sekolah">Mutasi / Pindah Sekolah</option>
                                        <option value="Dikeluarkan">Dikeluarkan</option>
                                        <option value="Mengundurkan Diri">Mengundurkan Diri</option>
                                        <option value="Meninggal Dunia">Meninggal Dunia</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-rose-900 uppercase tracking-wider mb-1">Tanggal Tidak Aktif</label>
                                    <input
                                        v-model="form.tanggal_tidak_aktif"
                                        type="date"
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-rose-200 text-xs sm:text-sm font-semibold text-rose-950 bg-white shadow-2xs focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all cursor-pointer"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!-- TAB 5: KESEJAHTERAAN & BANTUAN                              -->
                <!-- ============================================================ -->
                <div v-show="activeTab === 'kesejahteraan'" class="space-y-6">
                    <div>
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">Status Bantuan & Kesejahteraan</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                            <!-- KIP -->
                            <div class="p-4 rounded-2xl border border-slate-200/90 bg-slate-50/60 space-y-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        v-model="form.status_kip"
                                        type="checkbox"
                                        class="rounded border-slate-300 text-emerald-800 focus:ring-emerald-500/20"
                                    />
                                    <span class="text-xs sm:text-sm font-bold text-slate-800">Penerima Kartu Indonesia Pintar (KIP)</span>
                                </label>
                                <div v-if="form.status_kip" class="space-y-2.5 pt-2 border-t border-slate-200/70">
                                    <input
                                        v-model="form.no_kip"
                                        type="text"
                                        placeholder="Nomor KIP"
                                        class="w-full px-3.5 py-2 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                    />
                                    <input
                                        v-model="form.nama_di_kip"
                                        type="text"
                                        placeholder="Nama yang tertera pada KIP"
                                        class="w-full px-3.5 py-2 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                    />
                                </div>
                            </div>

                            <!-- PIP -->
                            <div class="p-4 rounded-2xl border border-slate-200/90 bg-slate-50/60 space-y-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        v-model="form.status_pip"
                                        type="checkbox"
                                        class="rounded border-slate-300 text-emerald-800 focus:ring-emerald-500/20"
                                    />
                                    <span class="text-xs sm:text-sm font-bold text-slate-800">Kelayakan / Penerima PIP</span>
                                </label>
                                <div v-if="form.status_pip" class="space-y-2.5 pt-2 border-t border-slate-200/70">
                                    <input
                                        v-model="form.pip_keterangan"
                                        type="text"
                                        placeholder="Alasan / keterangan kelayakan PIP"
                                        class="w-full px-3.5 py-2 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                    />
                                </div>
                            </div>

                            <!-- Desil -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Tingkat Desil Ekonomi (1 - 10)</label>
                                <input
                                    v-model="form.desil"
                                    type="number"
                                    min="1"
                                    max="10"
                                    placeholder="cth. 1 untuk paling miskin"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>

                            <!-- Kebutuhan Khusus -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Kebutuhan Khusus (Jika Ada)</label>
                                <input
                                    v-model="form.kebutuhan_khusus"
                                    type="text"
                                    placeholder="cth. Tuna Netra, Tuna Rungu, dll."
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-900 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Navigation Bar (Prev, Next & Submit) -->
                <div class="mt-8 pt-5 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <button
                        type="button"
                        v-if="currentTabIndex > 0"
                        @click="goToPrevTab"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs"
                    >
                        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                        <span>Sebelumnya: {{ tabs[currentTabIndex - 1].label }}</span>
                    </button>
                    <div v-else></div>

                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                        <button
                            type="button"
                            v-if="currentTabIndex < tabs.length - 1"
                            @click="goToNextTab"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold bg-slate-100 hover:bg-slate-200 text-slate-800 transition-all cursor-pointer border border-slate-200 shadow-2xs"
                        >
                            <span>Lanjut ke {{ tabs[currentTabIndex + 1].label }}</span>
                            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                        </button>

                        <button
                            type="button"
                            @click="validateAndSubmit"
                            :disabled="form.processing"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-5 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all"
                        >
                            <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                            <span v-else class="material-symbols-outlined text-[18px]">save</span>
                            <span>Simpan Data Siswa</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Floating Toast Notification -->
        <Transition
            enter-active-class="transform transition duration-300 ease-out"
            enter-from-class="translate-y-4 opacity-0 scale-95"
            enter-to-class="translate-y-0 opacity-100 scale-100"
            leave-active-class="transform transition duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100 scale-100"
            leave-to-class="translate-y-4 opacity-0 scale-95"
        >
            <div
                v-if="toast.show"
                class="fixed bottom-6 right-6 z-[999] max-w-sm w-full bg-white rounded-2xl shadow-xl border overflow-hidden"
                :class="toast.type === 'success' ? 'border-emerald-200' : 'border-rose-200'"
            >
                <div class="p-4 flex items-start gap-3">
                    <div
                        class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                        :class="toast.type === 'success' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                    >
                        <span class="material-symbols-outlined text-[18px]">
                            {{ toast.type === 'success' ? 'check_circle' : 'error' }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs font-bold text-slate-900">{{ toast.title }}</h4>
                        <p class="text-[11px] text-slate-600 mt-0.5">{{ toast.message }}</p>
                    </div>
                    <button
                        type="button"
                        @click="toast.show = false"
                        class="text-slate-400 hover:text-slate-600"
                    >
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

