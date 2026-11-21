<script setup>
import { ref, computed, watch, inject, nextTick } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useWilayah } from '@/Composables/useWilayah';

const props = defineProps({
    student: {
        type: Object,
        required: true,
    },
    canEdit: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['startEdit', 'cancelEdit', 'editingChange']);

const page = usePage();
const showToast = inject('showToast', null);
const isEditing = ref(false);

const form = useForm({
    _tab: 'alamat',
    jalan: '',
    rt: '',
    rw: '',
    dusun: '',
    desa: '',
    kecamatan: '',
    kabupaten: '',
    provinsi: '',
    jenis_tinggal: '',
    alat_transportasi: '',
    jarak_rumah: '',
});

const jenisTinggalOptions = [
    'Bersama Orang Tua',
    'Wali',
    'Asrama / Pesantren',
    'Kost',
    'Panti Asuhan',
    'Lainnya',
];

const alatTransportasiOptions = [
    'Kendaraan Pribadi',
    'Jemputan Sekolah',
    'Jalan Kaki',
    'Sepeda',
    'Angkutan Umum',
    'Lainnya',
];

const computedJenisTinggalOptions = computed(() => {
    const list = [...jenisTinggalOptions];
    if (form.jenis_tinggal && !list.includes(form.jenis_tinggal)) {
        list.unshift(form.jenis_tinggal);
    }
    return list;
});

const computedTransportasiOptions = computed(() => {
    const list = [...alatTransportasiOptions];
    if (form.alat_transportasi && !list.includes(form.alat_transportasi)) {
        list.unshift(form.alat_transportasi);
    }
    return list;
});

function initForm() {
    form.jalan = props.student.jalan || '';
    form.rt = props.student.rt || '';
    form.rw = props.student.rw || '';
    form.dusun = props.student.dusun || '';
    form.desa = props.student.desa || '';
    form.kecamatan = props.student.kecamatan || '';
    form.kabupaten = props.student.kabupaten || '';
    form.provinsi = props.student.provinsi || '';
    form.jenis_tinggal = props.student.jenis_tinggal || '';
    form.alat_transportasi = props.student.alat_transportasi || '';
    form.jarak_rumah = props.student.jarak_rumah || '';
}

initForm();

watch(
    () => props.student,
    () => {
        if (!isEditing.value) {
            initForm();
        }
    },
    { deep: true }
);

function getAlamatLengkap(s) {
    if (!s) return '-';
    let alamat = s.jalan ? s.jalan : '';
    if (s.rt && s.rw) alamat += `${alamat ? ', ' : ''}RT ${s.rt} / RW ${s.rw}`;
    else if (s.rt) alamat += `${alamat ? ', ' : ''}RT ${s.rt}`;
    else if (s.rw) alamat += `${alamat ? ', ' : ''}RW ${s.rw}`;
    const result = [alamat, s.dusun, s.desa, s.kecamatan, s.kabupaten, s.provinsi]
        .filter(Boolean)
        .join(', ');
    return result || '-';
}

function hasError(key) {
    return Boolean(form.errors?.[key]);
}

function getError(key) {
    return form.errors?.[key];
}

function clearFieldError(key) {
    if (form.errors?.[key]) {
        form.clearErrors(key);
    }
}

// ==========================================
// WILAYAH INDONESIA API & CASCADING STATE
// ==========================================
const { getProvinces, getRegencies, getDistricts, getVillages, isMatch } = useWilayah();

const provincesList = ref([]);
const selectedProvId = ref('');
const selectedKabId = ref('');
const selectedKecId = ref('');
const selectedDesId = ref('');
const regenciesList = ref([]);
const districtsList = ref([]);
const villagesList = ref([]);
const loadingWilayah = ref({ prov: false, kab: false, kec: false, des: false });

async function initWilayahFromForm() {
    loadingWilayah.value.prov = true;
    try {
        provincesList.value = await getProvinces();
    } finally {
        loadingWilayah.value.prov = false;
    }

    selectedProvId.value = '';
    selectedKabId.value = '';
    selectedKecId.value = '';
    selectedDesId.value = '';
    regenciesList.value = [];
    districtsList.value = [];
    villagesList.value = [];

    if (!form.provinsi) return;

    const prov = provincesList.value.find((p) => isMatch(p.name, form.provinsi));
    if (!prov) return;

    selectedProvId.value = prov.id;
    form.provinsi = prov.name;
    loadingWilayah.value.kab = true;
    try {
        regenciesList.value = await getRegencies(prov.id);
    } finally {
        loadingWilayah.value.kab = false;
    }

    if (!form.kabupaten) return;

    const kab = regenciesList.value.find((k) => isMatch(k.name, form.kabupaten));
    if (!kab) return;

    selectedKabId.value = kab.id;
    form.kabupaten = kab.name;
    loadingWilayah.value.kec = true;
    try {
        districtsList.value = await getDistricts(kab.id);
    } finally {
        loadingWilayah.value.kec = false;
    }

    if (!form.kecamatan) return;

    const kec = districtsList.value.find((d) => isMatch(d.name, form.kecamatan));
    if (!kec) return;

    selectedKecId.value = kec.id;
    form.kecamatan = kec.name;
    loadingWilayah.value.des = true;
    try {
        villagesList.value = await getVillages(kec.id);
    } finally {
        loadingWilayah.value.des = false;
    }

    if (!form.desa) return;

    const des = villagesList.value.find((v) => isMatch(v.name, form.desa));
    if (des) {
        selectedDesId.value = des.id;
        form.desa = des.name;
    }
}

async function onProvinsiChange() {
    clearFieldError('provinsi');
    const val = selectedProvId.value;
    const prov = provincesList.value.find((p) => p.id === val || isMatch(p.name, val));

    if (prov) {
        selectedProvId.value = prov.id;
        form.provinsi = prov.name;
    } else {
        form.provinsi = val || '';
    }

    selectedKabId.value = '';
    selectedKecId.value = '';
    selectedDesId.value = '';
    form.kabupaten = '';
    form.kecamatan = '';
    form.desa = '';
    regenciesList.value = [];
    districtsList.value = [];
    villagesList.value = [];

    if (selectedProvId.value && prov) {
        loadingWilayah.value.kab = true;
        try {
            regenciesList.value = await getRegencies(selectedProvId.value);
        } finally {
            loadingWilayah.value.kab = false;
        }
    }
}

async function onKabupatenChange() {
    clearFieldError('kabupaten');
    const val = selectedKabId.value;
    const kab = regenciesList.value.find((k) => k.id === val || isMatch(k.name, val));

    if (kab) {
        selectedKabId.value = kab.id;
        form.kabupaten = kab.name;
    } else {
        form.kabupaten = val || '';
    }

    selectedKecId.value = '';
    selectedDesId.value = '';
    form.kecamatan = '';
    form.desa = '';
    districtsList.value = [];
    villagesList.value = [];

    if (selectedKabId.value && kab) {
        loadingWilayah.value.kec = true;
        try {
            districtsList.value = await getDistricts(selectedKabId.value);
        } finally {
            loadingWilayah.value.kec = false;
        }
    }
}

async function onKecamatanChange() {
    clearFieldError('kecamatan');
    const val = selectedKecId.value;
    const kec = districtsList.value.find((d) => d.id === val || isMatch(d.name, val));

    if (kec) {
        selectedKecId.value = kec.id;
        form.kecamatan = kec.name;
    } else {
        form.kecamatan = val || '';
    }

    selectedDesId.value = '';
    form.desa = '';
    villagesList.value = [];

    if (selectedKecId.value && kec) {
        loadingWilayah.value.des = true;
        try {
            villagesList.value = await getVillages(selectedKecId.value);
        } finally {
            loadingWilayah.value.des = false;
        }
    }
}

function onDesaChange() {
    clearFieldError('desa');
    const val = selectedDesId.value;
    const des = villagesList.value.find((v) => v.id === val || isMatch(v.name, val));

    if (des) {
        selectedDesId.value = des.id;
        form.desa = des.name;
    } else {
        form.desa = val || '';
    }
}

async function startEdit() {
    if (!props.canEdit) return;
    initForm();
    form.clearErrors();
    isEditing.value = true;
    emit('editingChange', true);
    emit('startEdit');
    await nextTick();
    await initWilayahFromForm();
}

function cancelEdit() {
    initForm();
    form.clearErrors();
    isEditing.value = false;
    emit('editingChange', false);
    emit('cancelEdit');
}

function saveChanges() {
    form.put(`/students/${props.student.id || props.student.nisn}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditing.value = false;
            emit('editingChange', false);
            initForm();
            if (showToast) {
                showToast('success', page.props.flash?.success || 'Data alamat siswa berhasil diperbarui.');
            }
        },
        onError: () => {
            if (showToast) {
                showToast('error', 'Mohon periksa kembali isian alamat yang belum valid.', 'Gagal Menyimpan');
            }
        },
    });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Section Header Alamat Siswa -->
        <div class="flex items-center justify-between pb-3 mb-2 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">
                    {{ isEditing ? 'Edit Data Tempat Tinggal Siswa' : 'Data Tempat Tinggal Siswa' }}
                </h4>
            </div>

            <!-- Tombol Aksi di Pojok Kanan Atas -->
            <div v-if="canEdit" class="flex items-center gap-2">
                <button
                    v-if="!isEditing"
                    type="button"
                    @click="startEdit"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                    title="Edit data alamat siswa"
                >
                    <span class="material-symbols-outlined text-[16px] sm:text-[18px]">edit_note</span>
                    <span>Edit Data</span>
                </button>

                <template v-else>
                    <button
                        type="button"
                        @click="cancelEdit"
                        class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs"
                        :disabled="form.processing"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="saveChanges"
                        class="inline-flex items-center gap-1.5 px-4 py-1.5 sm:px-5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                        <span v-else class="material-symbols-outlined text-[16px]">save</span>
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</span>
                    </button>
                </template>
            </div>
        </div>

        <!-- VIEW MODE -->
        <div v-if="!isEditing" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
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
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1 font-mono">{{ student.rt || '-' }}</p>
                </div>
                <div>
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">RW</label>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1 font-mono">{{ student.rw || '-' }}</p>
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

        <!-- EDIT MODE -->
        <div v-else class="space-y-5">
            <div class="bg-slate-50/70 p-4 sm:p-5 rounded-2xl border border-slate-200/80 space-y-4">
                <div class="flex items-center gap-2 text-emerald-950 font-bold text-xs sm:text-sm">
                    <span class="material-symbols-outlined text-[18px] text-emerald-700">location_on</span>
                    <h5>Wilayah Administrasi &amp; Alamat Tempat Tinggal</h5>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <!-- 1. Provinsi -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Provinsi</label>
                            <span v-if="loadingWilayah.prov" class="text-[11px] text-emerald-700 flex items-center gap-1 font-medium">
                                <span class="material-symbols-outlined text-[13px] animate-spin">sync</span>
                                Memuat...
                            </span>
                        </div>
                        <select
                            id="field-student-provinsi"
                            v-model="selectedProvId"
                            @change="onProvinsiChange"
                            :disabled="loadingWilayah.prov"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-slate-900 focus:outline-none transition-all cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 border"
                            :class="hasError('provinsi')
                                ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                                : 'border-slate-200/90 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20'"
                        >
                            <option value="">{{ loadingWilayah.prov ? 'Memuat Provinsi...' : '-- Pilih Provinsi --' }}</option>
                            <option v-if="form.provinsi && !selectedProvId" :value="form.provinsi">
                                {{ form.provinsi }} (Saat Ini)
                            </option>
                            <option v-for="prov in provincesList" :key="prov.id" :value="prov.id">
                                {{ prov.name }}
                            </option>
                        </select>
                        <div v-if="hasError('provinsi')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            <span>{{ getError('provinsi') }}</span>
                        </div>
                    </div>

                    <!-- 2. Kabupaten / Kota -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Kabupaten / Kota</label>
                            <span v-if="loadingWilayah.kab" class="text-[11px] text-emerald-700 flex items-center gap-1 font-medium">
                                <span class="material-symbols-outlined text-[13px] animate-spin">sync</span>
                                Memuat...
                            </span>
                        </div>
                        <select
                            id="field-student-kabupaten"
                            v-model="selectedKabId"
                            @change="onKabupatenChange"
                            :disabled="!selectedProvId || loadingWilayah.kab"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-slate-900 focus:outline-none transition-all cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed border"
                            :class="hasError('kabupaten')
                                ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                                : 'border-slate-200/90 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20'"
                        >
                            <option value="">{{ !selectedProvId ? '-- Pilih Provinsi Terlebih Dahulu --' : (loadingWilayah.kab ? 'Memuat Kab/Kota...' : '-- Pilih Kabupaten / Kota --') }}</option>
                            <option v-if="form.kabupaten && !selectedKabId" :value="form.kabupaten">
                                {{ form.kabupaten }} (Saat Ini)
                            </option>
                            <option v-for="kab in regenciesList" :key="kab.id" :value="kab.id">
                                {{ kab.name }}
                            </option>
                        </select>
                        <div v-if="hasError('kabupaten')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            <span>{{ getError('kabupaten') }}</span>
                        </div>
                    </div>

                    <!-- 3. Kecamatan -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Kecamatan</label>
                            <span v-if="loadingWilayah.kec" class="text-[11px] text-emerald-700 flex items-center gap-1 font-medium">
                                <span class="material-symbols-outlined text-[13px] animate-spin">sync</span>
                                Memuat...
                            </span>
                        </div>
                        <select
                            id="field-student-kecamatan"
                            v-model="selectedKecId"
                            @change="onKecamatanChange"
                            :disabled="!selectedKabId || loadingWilayah.kec"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-slate-900 focus:outline-none transition-all cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed border"
                            :class="hasError('kecamatan')
                                ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                                : 'border-slate-200/90 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20'"
                        >
                            <option value="">{{ !selectedKabId ? '-- Pilih Kab/Kota Terlebih Dahulu --' : (loadingWilayah.kec ? 'Memuat Kecamatan...' : '-- Pilih Kecamatan --') }}</option>
                            <option v-if="form.kecamatan && !selectedKecId" :value="form.kecamatan">
                                {{ form.kecamatan }} (Saat Ini)
                            </option>
                            <option v-for="kec in districtsList" :key="kec.id" :value="kec.id">
                                {{ kec.name }}
                            </option>
                        </select>
                        <div v-if="hasError('kecamatan')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            <span>{{ getError('kecamatan') }}</span>
                        </div>
                    </div>

                    <!-- 4. Desa / Kelurahan -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Desa / Kelurahan</label>
                            <span v-if="loadingWilayah.des" class="text-[11px] text-emerald-700 flex items-center gap-1 font-medium">
                                <span class="material-symbols-outlined text-[13px] animate-spin">sync</span>
                                Memuat...
                            </span>
                        </div>
                        <select
                            id="field-student-desa"
                            v-model="selectedDesId"
                            @change="onDesaChange"
                            :disabled="!selectedKecId || loadingWilayah.des"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-slate-900 focus:outline-none transition-all cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed border"
                            :class="hasError('desa')
                                ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                                : 'border-slate-200/90 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20'"
                        >
                            <option value="">{{ !selectedKecId ? '-- Pilih Kecamatan Terlebih Dahulu --' : (loadingWilayah.des ? 'Memuat Desa...' : '-- Pilih Desa / Kelurahan --') }}</option>
                            <option v-if="form.desa && !selectedDesId" :value="form.desa">
                                {{ form.desa }} (Saat Ini)
                            </option>
                            <option v-for="des in villagesList" :key="des.id" :value="des.id">
                                {{ des.name }}
                            </option>
                        </select>
                        <div v-if="hasError('desa')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            <span>{{ getError('desa') }}</span>
                        </div>
                    </div>

                    <!-- 5. Dusun -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Dusun / Dukuh</label>
                        <input
                            id="field-student-dusun"
                            v-model="form.dusun"
                            type="text"
                            placeholder="Nama dusun / dukuh / kampung"
                            @input="clearFieldError('dusun')"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-slate-900 focus:outline-none transition-all border border-slate-200/90 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20"
                        />
                    </div>

                    <!-- 6. RT / RW -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">RT</label>
                            <input
                                id="field-student-rt"
                                v-model="form.rt"
                                type="text"
                                placeholder="Contoh: 001"
                                @input="clearFieldError('rt')"
                                class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-slate-900 font-mono focus:outline-none transition-all border border-slate-200/90 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">RW</label>
                            <input
                                id="field-student-rw"
                                v-model="form.rw"
                                type="text"
                                placeholder="Contoh: 002"
                                @input="clearFieldError('rw')"
                                class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-slate-900 font-mono focus:outline-none transition-all border border-slate-200/90 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20"
                            />
                        </div>
                    </div>

                    <!-- 7. Jalan -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Jalan / Alamat Tambahan</label>
                        <input
                            id="field-student-jalan"
                            v-model="form.jalan"
                            type="text"
                            placeholder="Nama jalan, nomor rumah / blok / gang"
                            @input="clearFieldError('jalan')"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-slate-900 focus:outline-none transition-all border border-slate-200/90 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20"
                        />
                    </div>
                </div>
            </div>

            <!-- Informasi Mobilitas & Tempat Tinggal -->
            <div class="bg-slate-50/70 p-4 sm:p-5 rounded-2xl border border-slate-200/80 space-y-4">
                <div class="flex items-center gap-2 text-emerald-950 font-bold text-xs sm:text-sm">
                    <span class="material-symbols-outlined text-[18px] text-emerald-700">commute</span>
                    <h5>Detail Tempat Tinggal &amp; Transportasi</h5>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Jenis Tinggal</label>
                        <select
                            id="field-student-jenis_tinggal"
                            v-model="form.jenis_tinggal"
                            @change="clearFieldError('jenis_tinggal')"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-slate-900 focus:outline-none transition-all cursor-pointer border border-slate-200/90 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20"
                        >
                            <option value="">-- Pilih Jenis Tinggal --</option>
                            <option v-for="opt in computedJenisTinggalOptions" :key="opt" :value="opt">{{ opt }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Alat Transportasi</label>
                        <select
                            id="field-student-alat_transportasi"
                            v-model="form.alat_transportasi"
                            @change="clearFieldError('alat_transportasi')"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-slate-900 focus:outline-none transition-all cursor-pointer border border-slate-200/90 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20"
                        >
                            <option value="">-- Pilih Transportasi --</option>
                            <option v-for="opt in computedTransportasiOptions" :key="opt" :value="opt">{{ opt }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Jarak Rumah ke Sekolah</label>
                        <input
                            id="field-student-jarak_rumah"
                            v-model="form.jarak_rumah"
                            type="text"
                            placeholder="cth. Kurang dari 1 km atau 3.5 km"
                            @input="clearFieldError('jarak_rumah')"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-slate-900 focus:outline-none transition-all border border-slate-200/90 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20"
                        />
                    </div>
                </div>
            </div>

            <!-- Tombol Batal & Simpan di Bagian Bawah Form Tab Alamat -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                <button
                    type="button"
                    @click="cancelEdit"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs"
                    :disabled="form.processing"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="saveChanges"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                    <span v-else class="material-symbols-outlined text-[16px]">save</span>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </div>
    </div>
</template>
