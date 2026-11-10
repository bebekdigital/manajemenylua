<script setup>
import { ref, onMounted, watch, inject, nextTick } from 'vue';
import { useWilayah } from '@/Composables/useWilayah';
import { getAlamatDomisili, getAlamatTinggal } from '../staffHelpers';

const props = defineProps({
    employee: {
        type: Object,
        required: true,
    },
    form: {
        type: Object,
        required: true,
    },
    isEditing: {
        type: Boolean,
        default: false,
    },
    canEdit: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['startEdit', 'cancelEdit', 'saveChanges']);

const showToast = inject('showToast', null);
const localErrors = ref({});

const requiredFields = [
    { key: 'provinsi_domisili', label: 'Provinsi Domisili', id: 'field-provinsi_domisili' },
    { key: 'kab_domisili', label: 'Kabupaten/Kota Domisili', id: 'field-kab_domisili' },
    { key: 'kecamatan_domisili', label: 'Kecamatan Domisili', id: 'field-kecamatan_domisili' },
    { key: 'desa_domisili', label: 'Desa/Kelurahan Domisili', id: 'field-desa_domisili' },
    { key: 'dusun_domisili', label: 'Dusun/Dukuh Domisili', id: 'field-dusun_domisili' },
    { key: 'rt_rw_domisili', label: 'RT/RW Domisili', id: 'field-rt_rw_domisili' },
    { key: 'jalan_domisili', label: 'Jalan/Alamat Domisili', id: 'field-jalan_domisili' },
    { key: 'provinsi_tinggal', label: 'Provinsi Tempat Tinggal', id: 'field-provinsi_tinggal' },
    { key: 'kabupaten_tinggal', label: 'Kabupaten/Kota Tempat Tinggal', id: 'field-kabupaten_tinggal' },
    { key: 'kecamatan_tinggal', label: 'Kecamatan Tempat Tinggal', id: 'field-kecamatan_tinggal' },
    { key: 'desa_tinggal', label: 'Desa/Kelurahan Tempat Tinggal', id: 'field-desa_tinggal' },
    { key: 'dusun_tinggal', label: 'Dusun/Dukuh Tempat Tinggal', id: 'field-dusun_tinggal' },
    { key: 'rt_rw_tinggal', label: 'RT/RW Tempat Tinggal', id: 'field-rt_rw_tinggal' },
    { key: 'jalan_tinggal', label: 'Jalan/Alamat Tempat Tinggal', id: 'field-jalan_tinggal' },
    { key: 'status_rumah', label: 'Status Kepemilikan Rumah', id: 'field-status_rumah' },
];

function hasError(key) {
    return Boolean(localErrors.value[key] || props.form.errors?.[key]);
}

function getError(key) {
    return localErrors.value[key] || props.form.errors?.[key];
}

function clearFieldError(key) {
    if (localErrors.value[key]) {
        delete localErrors.value[key];
    }
    if (props.form.errors?.[key]) {
        props.form.clearErrors(key);
    }
}

function scrollToField(id) {
    nextTick(() => {
        const el = document.getElementById(id);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => {
                try {
                    el.focus({ preventScroll: true });
                } catch {
                    el.focus();
                }
            }, 180);
        }
    });
}

function validateAndSave() {
    localErrors.value = {};
    const errors = {};
    let firstInvalidField = null;

    for (const item of requiredFields) {
        const val = props.form[item.key];
        const trimmed = typeof val === 'string' ? val.trim() : val;
        if (trimmed === null || trimmed === undefined || trimmed === '') {
            errors[item.key] = `${item.label} wajib diisi.`;
            if (!firstInvalidField) {
                firstInvalidField = item;
            }
        }
    }

    if (Object.keys(errors).length > 0) {
        localErrors.value = errors;
        if (showToast) {
            showToast('error', `Terdapat ${Object.keys(errors).length} kolom wajib yang belum diisi atau belum valid pada Tab Alamat.`, 'Data Belum Lengkap');
        }
        if (firstInvalidField) {
            scrollToField(firstInvalidField.id);
        }
        return;
    }

    emit('saveChanges');
}

function handleCancel() {
    localErrors.value = {};
    emit('cancelEdit');
}

watch(
    () => props.isEditing,
    (val) => {
        if (!val) {
            localErrors.value = {};
        }
    }
);

watch(
    () => props.form.errors,
    (newErrors) => {
        if (newErrors && Object.keys(newErrors).length > 0 && props.isEditing) {
            const firstErrorKey = Object.keys(newErrors)[0];
            const field = requiredFields.find((f) => f.key === firstErrorKey);
            if (field) {
                scrollToField(field.id);
            }
        }
    },
    { deep: true }
);

// ==========================================
// WILAYAH INDONESIA API & CASCADING STATE
// ==========================================
const { getProvinces, getRegencies, getDistricts, getVillages, isMatch } = useWilayah();

const provincesList = ref([]);

// Domisili Wilayah State
const selectedProvDomisiliId = ref('');
const selectedKabDomisiliId = ref('');
const selectedKecDomisiliId = ref('');
const selectedDesDomisiliId = ref('');
const regenciesDomisili = ref([]);
const districtsDomisili = ref([]);
const villagesDomisili = ref([]);
const loadingDomisili = ref({ prov: false, kab: false, kec: false, des: false });

// Tinggal Wilayah State
const selectedProvTinggalId = ref('');
const selectedKabTinggalId = ref('');
const selectedKecTinggalId = ref('');
const selectedDesTinggalId = ref('');
const regenciesTinggal = ref([]);
const districtsTinggal = ref([]);
const villagesTinggal = ref([]);
const loadingTinggal = ref({ prov: false, kab: false, kec: false, des: false });

let isInitialized = false;

async function initWilayahFromForm() {
    loadingDomisili.value.prov = true;
    loadingTinggal.value.prov = true;
    try {
        provincesList.value = await getProvinces();
    } finally {
        loadingDomisili.value.prov = false;
        loadingTinggal.value.prov = false;
    }

    // 1. Domisili
    if (props.form.provinsi_domisili) {
        const prov = provincesList.value.find((p) => p.id === selectedProvDomisiliId.value || isMatch(p.name, props.form.provinsi_domisili));
        if (prov) {
            selectedProvDomisiliId.value = prov.id;
            props.form.provinsi_domisili = prov.name;
            loadingDomisili.value.kab = true;
            try {
                regenciesDomisili.value = await getRegencies(prov.id);
            } finally {
                loadingDomisili.value.kab = false;
            }

            if (props.form.kab_domisili) {
                const kab = regenciesDomisili.value.find((k) => k.id === selectedKabDomisiliId.value || isMatch(k.name, props.form.kab_domisili));
                if (kab) {
                    selectedKabDomisiliId.value = kab.id;
                    props.form.kab_domisili = kab.name;
                    loadingDomisili.value.kec = true;
                    try {
                        districtsDomisili.value = await getDistricts(kab.id);
                    } finally {
                        loadingDomisili.value.kec = false;
                    }

                    if (props.form.kecamatan_domisili) {
                        const kec = districtsDomisili.value.find((d) => d.id === selectedKecDomisiliId.value || isMatch(d.name, props.form.kecamatan_domisili));
                        if (kec) {
                            selectedKecDomisiliId.value = kec.id;
                            props.form.kecamatan_domisili = kec.name;
                            loadingDomisili.value.des = true;
                            try {
                                villagesDomisili.value = await getVillages(kec.id);
                            } finally {
                                loadingDomisili.value.des = false;
                            }

                            if (props.form.desa_domisili) {
                                const des = villagesDomisili.value.find((v) => v.id === selectedDesDomisiliId.value || isMatch(v.name, props.form.desa_domisili));
                                if (des) {
                                    selectedDesDomisiliId.value = des.id;
                                    props.form.desa_domisili = des.name;
                                }
                            }
                        }
                    }
                }
            }
        }
    } else {
        selectedProvDomisiliId.value = '';
        selectedKabDomisiliId.value = '';
        selectedKecDomisiliId.value = '';
        selectedDesDomisiliId.value = '';
        regenciesDomisili.value = [];
        districtsDomisili.value = [];
        villagesDomisili.value = [];
    }

    // 2. Tinggal
    if (props.form.provinsi_tinggal) {
        const prov = provincesList.value.find((p) => p.id === selectedProvTinggalId.value || isMatch(p.name, props.form.provinsi_tinggal));
        if (prov) {
            selectedProvTinggalId.value = prov.id;
            props.form.provinsi_tinggal = prov.name;
            loadingTinggal.value.kab = true;
            try {
                regenciesTinggal.value = await getRegencies(prov.id);
            } finally {
                loadingTinggal.value.kab = false;
            }

            if (props.form.kabupaten_tinggal) {
                const kab = regenciesTinggal.value.find((k) => k.id === selectedKabTinggalId.value || isMatch(k.name, props.form.kabupaten_tinggal));
                if (kab) {
                    selectedKabTinggalId.value = kab.id;
                    props.form.kabupaten_tinggal = kab.name;
                    loadingTinggal.value.kec = true;
                    try {
                        districtsTinggal.value = await getDistricts(kab.id);
                    } finally {
                        loadingTinggal.value.kec = false;
                    }

                    if (props.form.kecamatan_tinggal) {
                        const kec = districtsTinggal.value.find((d) => d.id === selectedKecTinggalId.value || isMatch(d.name, props.form.kecamatan_tinggal));
                        if (kec) {
                            selectedKecTinggalId.value = kec.id;
                            props.form.kecamatan_tinggal = kec.name;
                            loadingTinggal.value.des = true;
                            try {
                                villagesTinggal.value = await getVillages(kec.id);
                            } finally {
                                loadingTinggal.value.des = false;
                            }

                            if (props.form.desa_tinggal) {
                                const des = villagesTinggal.value.find((v) => v.id === selectedDesTinggalId.value || isMatch(v.name, props.form.desa_tinggal));
                                if (des) {
                                    selectedDesTinggalId.value = des.id;
                                    props.form.desa_tinggal = des.name;
                                }
                            }
                        }
                    }
                }
            }
        }
    } else {
        selectedProvTinggalId.value = '';
        selectedKabTinggalId.value = '';
        selectedKecTinggalId.value = '';
        selectedDesTinggalId.value = '';
        regenciesTinggal.value = [];
        districtsTinggal.value = [];
        villagesTinggal.value = [];
    }
}

onMounted(async () => {
    await initWilayahFromForm();
    isInitialized = true;
});

// Re-init if isEditing becomes true (e.g. user clicked Edit or Cancelled and re-clicked)
watch(() => props.isEditing, async (newVal) => {
    if (newVal && isInitialized) {
        await initWilayahFromForm();
    }
});

async function onProvinsiDomisiliChange() {
    clearFieldError('provinsi_domisili');
    const val = selectedProvDomisiliId.value;
    const prov = provincesList.value.find((p) => p.id === val || isMatch(p.name, val));

    if (prov) {
        selectedProvDomisiliId.value = prov.id;
        props.form.provinsi_domisili = prov.name;
    } else {
        props.form.provinsi_domisili = val || '';
    }

    selectedKabDomisiliId.value = '';
    selectedKecDomisiliId.value = '';
    selectedDesDomisiliId.value = '';
    props.form.kab_domisili = '';
    props.form.kecamatan_domisili = '';
    props.form.desa_domisili = '';
    regenciesDomisili.value = [];
    districtsDomisili.value = [];
    villagesDomisili.value = [];

    if (selectedProvDomisiliId.value) {
        loadingDomisili.value.kab = true;
        try {
            regenciesDomisili.value = await getRegencies(selectedProvDomisiliId.value);
        } finally {
            loadingDomisili.value.kab = false;
        }
    }
}

async function onKabupatenDomisiliChange() {
    clearFieldError('kab_domisili');
    const val = selectedKabDomisiliId.value;
    const kab = regenciesDomisili.value.find((k) => k.id === val || isMatch(k.name, val));

    if (kab) {
        selectedKabDomisiliId.value = kab.id;
        props.form.kab_domisili = kab.name;
    } else {
        props.form.kab_domisili = val || '';
    }

    selectedKecDomisiliId.value = '';
    selectedDesDomisiliId.value = '';
    props.form.kecamatan_domisili = '';
    props.form.desa_domisili = '';
    districtsDomisili.value = [];
    villagesDomisili.value = [];

    if (selectedKabDomisiliId.value) {
        loadingDomisili.value.kec = true;
        try {
            districtsDomisili.value = await getDistricts(selectedKabDomisiliId.value);
        } finally {
            loadingDomisili.value.kec = false;
        }
    }
}

async function onKecamatanDomisiliChange() {
    clearFieldError('kecamatan_domisili');
    const val = selectedKecDomisiliId.value;
    const kec = districtsDomisili.value.find((d) => d.id === val || isMatch(d.name, val));

    if (kec) {
        selectedKecDomisiliId.value = kec.id;
        props.form.kecamatan_domisili = kec.name;
    } else {
        props.form.kecamatan_domisili = val || '';
    }

    selectedDesDomisiliId.value = '';
    props.form.desa_domisili = '';
    villagesDomisili.value = [];

    if (selectedKecDomisiliId.value) {
        loadingDomisili.value.des = true;
        try {
            villagesDomisili.value = await getVillages(selectedKecDomisiliId.value);
        } finally {
            loadingDomisili.value.des = false;
        }
    }
}

function onDesaDomisiliChange() {
    clearFieldError('desa_domisili');
    const val = selectedDesDomisiliId.value;
    const des = villagesDomisili.value.find((v) => v.id === val || isMatch(v.name, val));

    if (des) {
        selectedDesDomisiliId.value = des.id;
        props.form.desa_domisili = des.name;
    } else {
        props.form.desa_domisili = val || '';
    }
}

async function onProvinsiTinggalChange() {
    clearFieldError('provinsi_tinggal');
    const val = selectedProvTinggalId.value;
    const prov = provincesList.value.find((p) => p.id === val || isMatch(p.name, val));

    if (prov) {
        selectedProvTinggalId.value = prov.id;
        props.form.provinsi_tinggal = prov.name;
    } else {
        props.form.provinsi_tinggal = val || '';
    }

    selectedKabTinggalId.value = '';
    selectedKecTinggalId.value = '';
    selectedDesTinggalId.value = '';
    props.form.kabupaten_tinggal = '';
    props.form.kecamatan_tinggal = '';
    props.form.desa_tinggal = '';
    regenciesTinggal.value = [];
    districtsTinggal.value = [];
    villagesTinggal.value = [];

    if (selectedProvTinggalId.value) {
        loadingTinggal.value.kab = true;
        try {
            regenciesTinggal.value = await getRegencies(selectedProvTinggalId.value);
        } finally {
            loadingTinggal.value.kab = false;
        }
    }
}

async function onKabupatenTinggalChange() {
    clearFieldError('kabupaten_tinggal');
    const val = selectedKabTinggalId.value;
    const kab = regenciesTinggal.value.find((k) => k.id === val || isMatch(k.name, val));

    if (kab) {
        selectedKabTinggalId.value = kab.id;
        props.form.kabupaten_tinggal = kab.name;
    } else {
        props.form.kabupaten_tinggal = val || '';
    }

    selectedKecTinggalId.value = '';
    selectedDesTinggalId.value = '';
    props.form.kecamatan_tinggal = '';
    props.form.desa_tinggal = '';
    districtsTinggal.value = [];
    villagesTinggal.value = [];

    if (selectedKabTinggalId.value) {
        loadingTinggal.value.kec = true;
        try {
            districtsTinggal.value = await getDistricts(selectedKabTinggalId.value);
        } finally {
            loadingTinggal.value.kec = false;
        }
    }
}

async function onKecamatanTinggalChange() {
    clearFieldError('kecamatan_tinggal');
    const val = selectedKecTinggalId.value;
    const kec = districtsTinggal.value.find((d) => d.id === val || isMatch(d.name, val));

    if (kec) {
        selectedKecTinggalId.value = kec.id;
        props.form.kecamatan_tinggal = kec.name;
    } else {
        props.form.kecamatan_tinggal = val || '';
    }

    selectedDesTinggalId.value = '';
    props.form.desa_tinggal = '';
    villagesTinggal.value = [];

    if (selectedKecTinggalId.value) {
        loadingTinggal.value.des = true;
        try {
            villagesTinggal.value = await getVillages(selectedKecTinggalId.value);
        } finally {
            loadingTinggal.value.des = false;
        }
    }
}

function onDesaTinggalChange() {
    clearFieldError('desa_tinggal');
    const val = selectedDesTinggalId.value;
    const des = villagesTinggal.value.find((v) => v.id === val || isMatch(v.name, val));

    if (des) {
        selectedDesTinggalId.value = des.id;
        props.form.desa_tinggal = des.name;
    } else {
        props.form.desa_tinggal = val || '';
    }
}

function copyDomisiliToTinggal() {
    props.form.jalan_tinggal = props.form.jalan_domisili;
    props.form.rt_rw_tinggal = props.form.rt_rw_domisili;
    props.form.dusun_tinggal = props.form.dusun_domisili;
    props.form.desa_tinggal = props.form.desa_domisili;
    props.form.kecamatan_tinggal = props.form.kecamatan_domisili;
    props.form.kabupaten_tinggal = props.form.kab_domisili;
    props.form.provinsi_tinggal = props.form.provinsi_domisili;

    // Sync cascading dropdown state
    selectedProvTinggalId.value = selectedProvDomisiliId.value;
    regenciesTinggal.value = [...regenciesDomisili.value];
    selectedKabTinggalId.value = selectedKabDomisiliId.value;
    districtsTinggal.value = [...districtsDomisili.value];
    selectedKecTinggalId.value = selectedKecDomisiliId.value;
    villagesTinggal.value = [...villagesDomisili.value];
    selectedDesTinggalId.value = selectedDesDomisiliId.value;

    ['provinsi_tinggal', 'kabupaten_tinggal', 'kecamatan_tinggal', 'desa_tinggal', 'dusun_tinggal', 'rt_rw_tinggal', 'jalan_tinggal'].forEach(key => clearFieldError(key));
}
</script>

<template>
    <div class="space-y-6">
        <!-- Section Header Alamat Pegawai -->
        <div class="flex items-center justify-between pb-3 mb-2 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">
                    Data Alamat Pegawai
                </h4>
            </div>

            <!-- Tombol Edit / Simpan di Pojok Kanan Atas Section -->
            <div v-if="canEdit" class="flex items-center gap-2">
                <!-- Saat View Mode: Tombol Edit Data -->
                <button
                    v-if="!isEditing"
                    type="button"
                    @click="$emit('startEdit')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                    title="Edit data alamat pegawai"
                >
                    <span class="material-symbols-outlined text-[16px] sm:text-[18px]">edit_note</span>
                    <span>Edit Data</span>
                </button>

                <!-- Saat Edit Mode: Tombol Batal & Simpan -->
                <template v-else>
                    <button
                        type="button"
                        @click="handleCancel"
                        class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs"
                        :disabled="form.processing"
                        title="Batalkan perubahan"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="validateAndSave"
                        class="inline-flex items-center gap-1.5 px-4 py-1.5 sm:px-5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all"
                        :disabled="form.processing"
                        title="Simpan perubahan data alamat"
                    >
                        <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                        <span v-else class="material-symbols-outlined text-[16px]">save</span>
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</span>
                    </button>
                </template>
            </div>
        </div>

        <!-- VIEW MODE -->
        <template v-if="!isEditing">
            <!-- Domisili -->
            <div>
                <div class="flex items-center gap-2 mb-3 text-emerald-950 font-bold text-xs sm:text-sm">
                    <span class="material-symbols-outlined text-[18px] text-emerald-700">location_on</span>
                    <h5>Alamat Domisili (KTP)</h5>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 pl-1">
                    <div class="md:col-span-2">
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Alamat Lengkap</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ getAlamatDomisili(employee) }}</p>
                    </div>
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Jalan</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.jalan_domisili || '-' }}</p>
                    </div>
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">RT / RW</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1 font-mono">{{ employee.rt_rw_domisili || '-' }}</p>
                    </div>
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Dusun</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.dusun_domisili || '-' }}</p>
                    </div>
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Desa / Kelurahan</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.desa_domisili || '-' }}</p>
                    </div>
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Kecamatan</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.kecamatan_domisili || '-' }}</p>
                    </div>
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Kabupaten / Kota</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.kab_domisili || '-' }}</p>
                    </div>
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Provinsi</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.provinsi_domisili || '-' }}</p>
                    </div>
                </div>
            </div>

            <hr class="border-slate-100 my-6" />

            <!-- Tinggal -->
            <div>
                <div class="flex items-center gap-2 mb-3 text-emerald-950 font-bold text-xs sm:text-sm">
                    <span class="material-symbols-outlined text-[18px] text-emerald-700">home</span>
                    <h5>Alamat Tempat Tinggal Saat Ini</h5>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 pl-1">
                    <div class="md:col-span-2">
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Alamat Lengkap</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ getAlamatTinggal(employee) }}</p>
                    </div>
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Jalan</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.jalan_tinggal || '-' }}</p>
                    </div>
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">RT / RW</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1 font-mono">{{ employee.rt_rw_tinggal || '-' }}</p>
                    </div>
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Dusun</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.dusun_tinggal || '-' }}</p>
                    </div>
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Desa / Kelurahan</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.desa_tinggal || '-' }}</p>
                    </div>
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Kecamatan</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.kecamatan_tinggal || '-' }}</p>
                    </div>
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Kabupaten / Kota</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.kabupaten_tinggal || '-' }}</p>
                    </div>
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Provinsi</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.provinsi_tinggal || '-' }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Status Kepemilikan Rumah</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.status_rumah || '-' }}</p>
                    </div>
                </div>
            </div>
        </template>

        <!-- EDIT MODE -->
        <template v-else>
            <!-- Domisili Form -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-outline-variant/60 shadow-2xs space-y-4">
                <div class="flex items-center gap-2 text-primary font-bold text-sm">
                    <span class="material-symbols-outlined text-[18px]">location_on</span>
                    <h4>Alamat Domisili (KTP)</h4>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <!-- 1. Provinsi -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                                Provinsi <span class="text-error font-bold">*</span>
                            </label>
                            <span v-if="loadingDomisili.prov" class="text-[11px] text-primary flex items-center gap-1 font-medium">
                                <span class="material-symbols-outlined text-[13px] animate-spin">sync</span>
                                Memuat...
                            </span>
                        </div>
                        <select
                            id="field-provinsi_domisili"
                            v-model="selectedProvDomisiliId"
                            @change="onProvinsiDomisiliChange"
                            :disabled="loadingDomisili.prov"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 border"
                            :class="hasError('provinsi_domisili')
                                ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                                : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                        >
                            <option value="">{{ loadingDomisili.prov ? 'Memuat Provinsi...' : '-- Pilih Provinsi --' }}</option>
                            <option
                                v-if="form.provinsi_domisili && !selectedProvDomisiliId"
                                :value="form.provinsi_domisili"
                            >
                                {{ form.provinsi_domisili }} (Saat Ini)
                            </option>
                            <option v-for="prov in provincesList" :key="prov.id" :value="prov.id">
                                {{ prov.name }}
                            </option>
                        </select>
                        <div v-if="hasError('provinsi_domisili')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            <span>{{ getError('provinsi_domisili') }}</span>
                        </div>
                    </div>

                    <!-- 2. Kabupaten / Kota -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                                Kabupaten / Kota <span class="text-error font-bold">*</span>
                            </label>
                            <span v-if="loadingDomisili.kab" class="text-[11px] text-primary flex items-center gap-1 font-medium">
                                <span class="material-symbols-outlined text-[13px] animate-spin">sync</span>
                                Memuat...
                            </span>
                        </div>
                        <select
                            id="field-kab_domisili"
                            v-model="selectedKabDomisiliId"
                            @change="onKabupatenDomisiliChange"
                            :disabled="!selectedProvDomisiliId || loadingDomisili.kab"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed border"
                            :class="hasError('kab_domisili')
                                ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                                : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                        >
                            <option value="">{{ !selectedProvDomisiliId ? '-- Pilih Provinsi Terlebih Dahulu --' : (loadingDomisili.kab ? 'Memuat Kab/Kota...' : '-- Pilih Kabupaten / Kota --') }}</option>
                            <option
                                v-if="form.kab_domisili && !selectedKabDomisiliId"
                                :value="form.kab_domisili"
                            >
                                {{ form.kab_domisili }} (Saat Ini)
                            </option>
                            <option v-for="kab in regenciesDomisili" :key="kab.id" :value="kab.id">
                                {{ kab.name }}
                            </option>
                        </select>
                        <div v-if="hasError('kab_domisili')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            <span>{{ getError('kab_domisili') }}</span>
                        </div>
                    </div>

                    <!-- 3. Kecamatan -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                                Kecamatan <span class="text-error font-bold">*</span>
                            </label>
                            <span v-if="loadingDomisili.kec" class="text-[11px] text-primary flex items-center gap-1 font-medium">
                                <span class="material-symbols-outlined text-[13px] animate-spin">sync</span>
                                Memuat...
                            </span>
                        </div>
                        <select
                            id="field-kecamatan_domisili"
                            v-model="selectedKecDomisiliId"
                            @change="onKecamatanDomisiliChange"
                            :disabled="!selectedKabDomisiliId || loadingDomisili.kec"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed border"
                            :class="hasError('kecamatan_domisili')
                                ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                                : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                        >
                            <option value="">{{ !selectedKabDomisiliId ? '-- Pilih Kab/Kota Terlebih Dahulu --' : (loadingDomisili.kec ? 'Memuat Kecamatan...' : '-- Pilih Kecamatan --') }}</option>
                            <option
                                v-if="form.kecamatan_domisili && !selectedKecDomisiliId"
                                :value="form.kecamatan_domisili"
                            >
                                {{ form.kecamatan_domisili }} (Saat Ini)
                            </option>
                            <option v-for="kec in districtsDomisili" :key="kec.id" :value="kec.id">
                                {{ kec.name }}
                            </option>
                        </select>
                        <div v-if="hasError('kecamatan_domisili')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            <span>{{ getError('kecamatan_domisili') }}</span>
                        </div>
                    </div>

                    <!-- 4. Desa / Kelurahan -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                                Desa / Kelurahan <span class="text-error font-bold">*</span>
                            </label>
                            <span v-if="loadingDomisili.des" class="text-[11px] text-primary flex items-center gap-1 font-medium">
                                <span class="material-symbols-outlined text-[13px] animate-spin">sync</span>
                                Memuat...
                            </span>
                        </div>
                        <select
                            id="field-desa_domisili"
                            v-model="selectedDesDomisiliId"
                            @change="onDesaDomisiliChange"
                            :disabled="!selectedKecDomisiliId || loadingDomisili.des"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed border"
                            :class="hasError('desa_domisili')
                                ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                                : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                        >
                            <option value="">{{ !selectedKecDomisiliId ? '-- Pilih Kecamatan Terlebih Dahulu --' : (loadingDomisili.des ? 'Memuat Desa...' : '-- Pilih Desa / Kelurahan --') }}</option>
                            <option
                                v-if="form.desa_domisili && !selectedDesDomisiliId"
                                :value="form.desa_domisili"
                            >
                                {{ form.desa_domisili }} (Saat Ini)
                            </option>
                            <option v-for="des in villagesDomisili" :key="des.id" :value="des.id">
                                {{ des.name }}
                            </option>
                        </select>
                        <div v-if="hasError('desa_domisili')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            <span>{{ getError('desa_domisili') }}</span>
                        </div>
                    </div>

                    <!-- 5. Dusun -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                            Dusun / Dukuh <span class="text-error font-bold">*</span>
                        </label>
                        <input
                            id="field-dusun_domisili"
                            v-model="form.dusun_domisili"
                            type="text"
                            placeholder="Nama dusun / dukuh / kampung"
                            @input="clearFieldError('dusun_domisili')"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all border"
                            :class="hasError('dusun_domisili')
                                ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                                : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                        />
                        <div v-if="hasError('dusun_domisili')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            <span>{{ getError('dusun_domisili') }}</span>
                        </div>
                    </div>

                    <!-- 6. RT / RW -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                            RT / RW <span class="text-error font-bold">*</span>
                        </label>
                        <input
                            id="field-rt_rw_domisili"
                            v-model="form.rt_rw_domisili"
                            type="text"
                            placeholder="Contoh: 001/002"
                            @input="clearFieldError('rt_rw_domisili')"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all font-mono border"
                            :class="hasError('rt_rw_domisili')
                                ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                                : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                        />
                        <div v-if="hasError('rt_rw_domisili')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            <span>{{ getError('rt_rw_domisili') }}</span>
                        </div>
                    </div>

                    <!-- 7. Jalan Domisili (span 2) -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                            Jalan / Alamat Tambahan <span class="text-error font-bold">*</span>
                        </label>
                        <input
                            id="field-jalan_domisili"
                            v-model="form.jalan_domisili"
                            type="text"
                            placeholder="Nama jalan, nomor rumah / blok / gang"
                            @input="clearFieldError('jalan_domisili')"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all border"
                            :class="hasError('jalan_domisili')
                                ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                                : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                        />
                        <div v-if="hasError('jalan_domisili')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            <span>{{ getError('jalan_domisili') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tinggal Form -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-outline-variant/60 shadow-2xs space-y-4">
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
                    <!-- 1. Provinsi -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                                Provinsi <span class="text-error font-bold">*</span>
                            </label>
                            <span v-if="loadingTinggal.prov" class="text-[11px] text-primary flex items-center gap-1 font-medium">
                                <span class="material-symbols-outlined text-[13px] animate-spin">sync</span>
                                Memuat...
                            </span>
                        </div>
                        <select
                            id="field-provinsi_tinggal"
                            v-model="selectedProvTinggalId"
                            @change="onProvinsiTinggalChange"
                            :disabled="loadingTinggal.prov"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 border"
                            :class="hasError('provinsi_tinggal')
                                ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                                : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                        >
                            <option value="">{{ loadingTinggal.prov ? 'Memuat Provinsi...' : '-- Pilih Provinsi --' }}</option>
                            <option
                                v-if="form.provinsi_tinggal && !selectedProvTinggalId"
                                :value="form.provinsi_tinggal"
                            >
                                {{ form.provinsi_tinggal }} (Saat Ini)
                            </option>
                            <option v-for="prov in provincesList" :key="prov.id" :value="prov.id">
                                {{ prov.name }}
                            </option>
                        </select>
                        <div v-if="hasError('provinsi_tinggal')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            <span>{{ getError('provinsi_tinggal') }}</span>
                        </div>
                    </div>

                    <!-- 2. Kabupaten / Kota -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                                Kabupaten / Kota <span class="text-error font-bold">*</span>
                            </label>
                            <span v-if="loadingTinggal.kab" class="text-[11px] text-primary flex items-center gap-1 font-medium">
                                <span class="material-symbols-outlined text-[13px] animate-spin">sync</span>
                                Memuat...
                            </span>
                        </div>
                        <select
                            id="field-kabupaten_tinggal"
                            v-model="selectedKabTinggalId"
                            @change="onKabupatenTinggalChange"
                            :disabled="!selectedProvTinggalId || loadingTinggal.kab"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed border"
                            :class="hasError('kabupaten_tinggal')
                                ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                                : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                        >
                            <option value="">{{ !selectedProvTinggalId ? '-- Pilih Provinsi Terlebih Dahulu --' : (loadingTinggal.kab ? 'Memuat Kab/Kota...' : '-- Pilih Kabupaten / Kota --') }}</option>
                            <option
                                v-if="form.kabupaten_tinggal && !selectedKabTinggalId"
                                :value="form.kabupaten_tinggal"
                            >
                                {{ form.kabupaten_tinggal }} (Saat Ini)
                            </option>
                            <option v-for="kab in regenciesTinggal" :key="kab.id" :value="kab.id">
                                {{ kab.name }}
                            </option>
                        </select>
                        <div v-if="hasError('kabupaten_tinggal')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            <span>{{ getError('kabupaten_tinggal') }}</span>
                        </div>
                    </div>

                    <!-- 3. Kecamatan -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                                Kecamatan <span class="text-error font-bold">*</span>
                            </label>
                            <span v-if="loadingTinggal.kec" class="text-[11px] text-primary flex items-center gap-1 font-medium">
                                <span class="material-symbols-outlined text-[13px] animate-spin">sync</span>
                                Memuat...
                            </span>
                        </div>
                        <select
                            id="field-kecamatan_tinggal"
                            v-model="selectedKecTinggalId"
                            @change="onKecamatanTinggalChange"
                            :disabled="!selectedKabTinggalId || loadingTinggal.kec"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed border"
                            :class="hasError('kecamatan_tinggal')
                                ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                                : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                        >
                            <option value="">{{ !selectedKabTinggalId ? '-- Pilih Kab/Kota Terlebih Dahulu --' : (loadingTinggal.kec ? 'Memuat Kecamatan...' : '-- Pilih Kecamatan --') }}</option>
                            <option
                                v-if="form.kecamatan_tinggal && !selectedKecTinggalId"
                                :value="form.kecamatan_tinggal"
                            >
                                {{ form.kecamatan_tinggal }} (Saat Ini)
                            </option>
                            <option v-for="kec in districtsTinggal" :key="kec.id" :value="kec.id">
                                {{ kec.name }}
                            </option>
                        </select>
                        <div v-if="hasError('kecamatan_tinggal')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            <span>{{ getError('kecamatan_tinggal') }}</span>
                        </div>
                    </div>

                    <!-- 4. Desa / Kelurahan -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                                Desa / Kelurahan <span class="text-error font-bold">*</span>
                            </label>
                            <span v-if="loadingTinggal.des" class="text-[11px] text-primary flex items-center gap-1 font-medium">
                                <span class="material-symbols-outlined text-[13px] animate-spin">sync</span>
                                Memuat...
                            </span>
                        </div>
                        <select
                            id="field-desa_tinggal"
                            v-model="selectedDesTinggalId"
                            @change="onDesaTinggalChange"
                            :disabled="!selectedKecTinggalId || loadingTinggal.des"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed border"
                            :class="hasError('desa_tinggal')
                                ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                                : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                        >
                            <option value="">{{ !selectedKecTinggalId ? '-- Pilih Kecamatan Terlebih Dahulu --' : (loadingTinggal.des ? 'Memuat Desa...' : '-- Pilih Desa / Kelurahan --') }}</option>
                            <option
                                v-if="form.desa_tinggal && !selectedDesTinggalId"
                                :value="form.desa_tinggal"
                            >
                                {{ form.desa_tinggal }} (Saat Ini)
                            </option>
                            <option v-for="des in villagesTinggal" :key="des.id" :value="des.id">
                                {{ des.name }}
                            </option>
                        </select>
                        <div v-if="hasError('desa_tinggal')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            <span>{{ getError('desa_tinggal') }}</span>
                        </div>
                    </div>

                    <!-- 5. Dusun -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                            Dusun / Dukuh <span class="text-error font-bold">*</span>
                        </label>
                        <input
                            id="field-dusun_tinggal"
                            v-model="form.dusun_tinggal"
                            type="text"
                            placeholder="Nama dusun / dukuh / kampung"
                            @input="clearFieldError('dusun_tinggal')"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all border"
                            :class="hasError('dusun_tinggal')
                                ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                                : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                        />
                        <div v-if="hasError('dusun_tinggal')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            <span>{{ getError('dusun_tinggal') }}</span>
                        </div>
                    </div>

                    <!-- 6. RT / RW -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                            RT / RW <span class="text-error font-bold">*</span>
                        </label>
                        <input
                            id="field-rt_rw_tinggal"
                            v-model="form.rt_rw_tinggal"
                            type="text"
                            placeholder="Contoh: 001/002"
                            @input="clearFieldError('rt_rw_tinggal')"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all font-mono border"
                            :class="hasError('rt_rw_tinggal')
                                ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                                : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                        />
                        <div v-if="hasError('rt_rw_tinggal')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            <span>{{ getError('rt_rw_tinggal') }}</span>
                        </div>
                    </div>

                    <!-- 7. Jalan Tinggal -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                            Jalan / Alamat Tambahan <span class="text-error font-bold">*</span>
                        </label>
                        <input
                            id="field-jalan_tinggal"
                            v-model="form.jalan_tinggal"
                            type="text"
                            placeholder="Nama jalan, nomor rumah / kontrakan"
                            @input="clearFieldError('jalan_tinggal')"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all border"
                            :class="hasError('jalan_tinggal')
                                ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                                : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                        />
                        <div v-if="hasError('jalan_tinggal')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            <span>{{ getError('jalan_tinggal') }}</span>
                        </div>
                    </div>

                    <!-- 8. Status Kepemilikan Rumah -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                            Status Kepemilikan Rumah <span class="text-error font-bold">*</span>
                        </label>
                        <select
                            id="field-status_rumah"
                            v-model="form.status_rumah"
                            @change="clearFieldError('status_rumah')"
                            class="w-full px-3.5 py-2 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all cursor-pointer border"
                            :class="hasError('status_rumah')
                                ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                                : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                        >
                            <option value="">-- Pilih Status Rumah --</option>
                            <option value="Milik Sendiri">Milik Sendiri</option>
                            <option value="Sewa / Kontrak">Sewa / Kontrak</option>
                            <option value="Menumpang / Orang Tua">Menumpang / Orang Tua</option>
                            <option value="Rumah Dinas">Rumah Dinas</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                        <div v-if="hasError('status_rumah')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            <span>{{ getError('status_rumah') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tombol Batal & Simpan di Bagian Bawah Form Tab Alamat -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                <button
                    type="button"
                    @click="handleCancel"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs"
                    :disabled="form.processing"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="validateAndSave"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                    <span v-else class="material-symbols-outlined text-[16px]">save</span>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </template>
    </div>
</template>
