<script setup>
import { inject, nextTick, ref, watch } from 'vue';
import { hitungUsia } from '../staffHelpers';

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
    canViewKepegawaian: {
        type: Boolean,
        default: false,
    },
    canUpdateKepegawaian: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['startEdit', 'cancelEdit', 'saveChanges']);

const showToast = inject('showToast', null);
const localErrors = ref({});

const requiredFields = [
    { key: 'nama', label: 'Nama Lengkap', id: 'field-nama' },
    { key: 'nik', label: 'NIK (Nomor Induk Kependudukan)', id: 'field-nik' },
    { key: 'jk', label: 'Jenis Kelamin', id: 'field-jk' },
    { key: 'no_wa', label: 'Nomor WhatsApp', id: 'field-no_wa' },
    { key: 'tempat_lahir', label: 'Tempat Lahir', id: 'field-tempat_lahir' },
    { key: 'tanggal_lahir', label: 'Tanggal Lahir', id: 'field-tanggal_lahir' },
    { key: 'email', label: 'Email', id: 'field-email' },
    { key: 'kepemilikan_bpjs', label: 'Kepemilikan BPJS', id: 'field-kepemilikan_bpjs' },
    { key: 'penanggung_bpjs', label: 'Penanggung BPJS', id: 'field-penanggung_bpjs' },
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

function onKepemilikanBpjsChange() {
    clearFieldError('kepemilikan_bpjs');
    if (props.form.kepemilikan_bpjs === 'Tidak') {
        if (!props.form.penanggung_bpjs || props.form.penanggung_bpjs === '') {
            props.form.penanggung_bpjs = 'Tidak Ada';
            clearFieldError('penanggung_bpjs');
        }
    } else if (props.form.kepemilikan_bpjs === 'Ya') {
        if (props.form.penanggung_bpjs === 'Tidak Ada') {
            props.form.penanggung_bpjs = '';
        }
    }
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

    // Validasi format email jika terisi
    if (!errors.email && props.form.email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(String(props.form.email).trim())) {
            errors.email = 'Format email tidak valid.';
            if (!firstInvalidField) {
                firstInvalidField = requiredFields.find((f) => f.key === 'email');
            }
        }
    }

    if (Object.keys(errors).length > 0) {
        localErrors.value = errors;
        if (showToast) {
            showToast('error', `Terdapat ${Object.keys(errors).length} kolom wajib yang belum diisi atau belum valid.`, 'Data Belum Lengkap');
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
</script>

<template>
    <div class="space-y-4">
        <!-- Section Header Identitas Pegawai -->
        <div class="flex items-center justify-between pb-3 mb-2 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">
                    Data Identitas Pegawai
                </h4>
            </div>

            <!-- Tombol Edit di Pojok Kanan Atas Section -->
            <div v-if="canEdit" class="flex items-center gap-2">
                <!-- Saat View Mode: Tombol Edit Data -->
                <button
                    v-if="!isEditing"
                    type="button"
                    @click="$emit('startEdit')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                    title="Edit data identitas pegawai"
                >
                    <span class="material-symbols-outlined text-[16px] sm:text-[18px]">edit_note</span>
                    <span>Edit Data</span>
                </button>

                <!-- Saat Edit Mode: Tombol Batal & Simpan di Header Section -->
                <template v-else>
                    <button
                        type="button"
                        @click="handleCancel"
                        class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs"
                        :disabled="form.processing"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="validateAndSave"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                        <span v-else class="material-symbols-outlined text-[16px]">save</span>
                        <span>Simpan</span>
                    </button>
                </template>
            </div>
        </div>

        <!-- VIEW MODE -->
        <div v-if="!isEditing" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
            <div>
                <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Nama Lengkap</label>
                <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.nama }}</p>
            </div>
            <div v-if="canViewKepegawaian">
                <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">NIPY</label>
                <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1 font-mono">{{ employee.nipy || '-' }}</p>
            </div>
            <div>
                <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Gelar Depan</label>
                <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.gelar_depan || '-' }}</p>
            </div>
            <div>
                <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Gelar Belakang</label>
                <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.gelar_belakang || '-' }}</p>
            </div>
            <div>
                <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Jenis Kelamin</label>
                <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">
                    {{ employee.jk === 'L' ? 'Laki-laki' : employee.jk === 'P' ? 'Perempuan' : '-' }}
                </p>
            </div>
            <div>
                <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Tempat, Tanggal Lahir</label>
                <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.ttl || '-' }}</p>
            </div>
            <div>
                <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Usia</label>
                <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">
                    <span v-if="hitungUsia(employee.tanggal_lahir, employee.ttl) !== '-'" class="inline-flex items-center gap-1.5 text-emerald-800 font-bold">
                        <span class="material-symbols-outlined text-[18px]">cake</span>
                        <span>{{ hitungUsia(employee.tanggal_lahir, employee.ttl) }}</span>
                    </span>
                    <span v-else class="text-slate-400">-</span>
                </p>
            </div>
            <div>
                <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">NIK</label>
                <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1 font-mono">{{ employee.nik || '-' }}</p>
            </div>
            <div>
                <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">No. WhatsApp</label>
                <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.no_wa || '-' }}</p>
            </div>
            <div>
                <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Email</label>
                <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.email || '-' }}</p>
            </div>
            <div>
                <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Kepemilikan BPJS</label>
                <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.kepemilikan_bpjs || '-' }}</p>
            </div>
            <div>
                <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Penanggung BPJS</label>
                <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.penanggung_bpjs || '-' }}</p>
            </div>
            <div class="md:col-span-2">
                <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Skill / Keahlian</label>
                <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.skill || '-' }}</p>
            </div>
        </div>

        <!-- EDIT MODE -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Gelar Depan -->
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                    Gelar Depan
                </label>
                <input
                    v-model="form.gelar_depan"
                    type="text"
                    placeholder="Contoh: Dr., Ir., Ust."
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                    :class="{ 'border-error': form.errors.gelar_depan }"
                />
                <div v-if="form.errors.gelar_depan" class="text-error text-xs mt-1">{{ form.errors.gelar_depan }}</div>
            </div>

            <!-- Gelar Belakang -->
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                    Gelar Belakang
                </label>
                <input
                    v-model="form.gelar_belakang"
                    type="text"
                    placeholder="Contoh: S.Pd, M.Pd, Lc."
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                    :class="{ 'border-error': form.errors.gelar_belakang }"
                />
                <div v-if="form.errors.gelar_belakang" class="text-error text-xs mt-1">{{ form.errors.gelar_belakang }}</div>
            </div>

            <!-- Nama Lengkap * -->
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                    Nama Lengkap <span class="text-error font-bold">*</span>
                </label>
                <input
                    id="field-nama"
                    v-model="form.nama"
                    type="text"
                    required
                    placeholder="Nama lengkap tanpa gelar"
                    @input="clearFieldError('nama')"
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none transition-all border"
                    :class="hasError('nama')
                        ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                        : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                />
                <div v-if="hasError('nama')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">error</span>
                    <span>{{ getError('nama') }}</span>
                </div>
            </div>

            <!-- NIPY -->
            <div v-if="canViewKepegawaian">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                        NIPY (Nomor Induk Pegawai Yayasan)
                    </label>
                    <span v-if="canUpdateKepegawaian" class="inline-flex items-center gap-1 text-[10px] font-semibold text-primary bg-primary/10 px-2 py-0.5 rounded-md border border-primary/20">
                        <span class="material-symbols-outlined text-[13px]">badge</span>
                        Data Kepegawaian
                    </span>
                    <span v-else class="inline-flex items-center gap-1 text-[10px] font-semibold text-on-surface-variant/80 bg-slate-100 px-2 py-0.5 rounded-md border border-outline-variant/40">
                        <span class="material-symbols-outlined text-[13px]">lock</span>
                        Terkunci
                    </span>
                </div>
                <input
                    v-if="canUpdateKepegawaian"
                    v-model="form.nipy"
                    type="text"
                    placeholder="Nomor Induk Pegawai Yayasan"
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 font-mono transition-all"
                    :class="{ 'border-error': form.errors.nipy }"
                />
                <input
                    v-else
                    :value="form.nipy"
                    type="text"
                    placeholder="Nomor Induk Pegawai Yayasan"
                    disabled
                    readonly
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-slate-100/90 border border-outline-variant/50 rounded-xl text-xs sm:text-sm text-slate-500 placeholder:text-outline cursor-not-allowed font-mono select-none"
                />
                <p v-if="!canUpdateKepegawaian" class="text-[11px] text-on-surface-variant/75 mt-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[13px]">lock</span>
                    Terkunci — hanya role dengan hak akses ubah Data Kepegawaian yang dapat mengubah NIPY.
                </p>
                <div v-if="form.errors.nipy" class="text-error text-xs mt-1">{{ form.errors.nipy }}</div>
            </div>

            <!-- NIK (Nomor Induk Kependudukan) * -->
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                    NIK (Nomor Induk Kependudukan) <span class="text-error font-bold">*</span>
                </label>
                <input
                    id="field-nik"
                    v-model="form.nik"
                    type="text"
                    placeholder="16 digit NIK"
                    @input="clearFieldError('nik')"
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none transition-all font-mono border"
                    :class="hasError('nik')
                        ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                        : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                />
                <div v-if="hasError('nik')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">error</span>
                    <span>{{ getError('nik') }}</span>
                </div>
            </div>

            <!-- Jenis Kelamin * -->
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                    Jenis Kelamin <span class="text-error font-bold">*</span>
                </label>
                <select
                    id="field-jk"
                    v-model="form.jk"
                    @change="clearFieldError('jk')"
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all cursor-pointer border"
                    :class="hasError('jk')
                        ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                        : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                >
                    <option value="">-- Pilih Jenis Kelamin --</option>
                    <option value="L">Laki-laki (L)</option>
                    <option value="P">Perempuan (P)</option>
                </select>
                <div v-if="hasError('jk')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">error</span>
                    <span>{{ getError('jk') }}</span>
                </div>
            </div>

            <!-- Nomor WhatsApp * -->
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                    Nomor WhatsApp <span class="text-error font-bold">*</span>
                </label>
                <input
                    id="field-no_wa"
                    v-model="form.no_wa"
                    type="text"
                    placeholder="Contoh: 081234567890"
                    @input="clearFieldError('no_wa')"
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none transition-all border"
                    :class="hasError('no_wa')
                        ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                        : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                />
                <div v-if="hasError('no_wa')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">error</span>
                    <span>{{ getError('no_wa') }}</span>
                </div>
            </div>

            <!-- Tempat Lahir * -->
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                    Tempat Lahir <span class="text-error font-bold">*</span>
                </label>
                <input
                    id="field-tempat_lahir"
                    v-model="form.tempat_lahir"
                    type="text"
                    placeholder="Kota / Kabupaten Kelahiran"
                    @input="clearFieldError('tempat_lahir')"
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none transition-all border"
                    :class="hasError('tempat_lahir')
                        ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                        : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                />
                <div v-if="hasError('tempat_lahir')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">error</span>
                    <span>{{ getError('tempat_lahir') }}</span>
                </div>
            </div>

            <!-- Tanggal Lahir * -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                        Tanggal Lahir <span class="text-error font-bold">*</span>
                    </label>
                    <span v-if="hitungUsia(form.tanggal_lahir) !== '-'" class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary bg-primary/10 px-2 py-0.5 rounded-md">
                        <span class="material-symbols-outlined text-[13px]">cake</span>
                        <span>Usia: {{ hitungUsia(form.tanggal_lahir) }}</span>
                    </span>
                </div>
                <input
                    id="field-tanggal_lahir"
                    v-model="form.tanggal_lahir"
                    type="date"
                    @input="clearFieldError('tanggal_lahir')"
                    @change="clearFieldError('tanggal_lahir')"
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all cursor-pointer border"
                    :class="hasError('tanggal_lahir')
                        ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                        : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                />
                <div v-if="hasError('tanggal_lahir')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">error</span>
                    <span>{{ getError('tanggal_lahir') }}</span>
                </div>
            </div>

            <!-- Email * -->
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                    Email <span class="text-error font-bold">*</span>
                </label>
                <input
                    id="field-email"
                    v-model="form.email"
                    type="email"
                    placeholder="nama@email.com"
                    @input="clearFieldError('email')"
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none transition-all border"
                    :class="hasError('email')
                        ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                        : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                />
                <div v-if="hasError('email')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">error</span>
                    <span>{{ getError('email') }}</span>
                </div>
            </div>

            <!-- Kepemilikan BPJS * -->
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                    Kepemilikan BPJS <span class="text-error font-bold">*</span>
                </label>
                <select
                    id="field-kepemilikan_bpjs"
                    v-model="form.kepemilikan_bpjs"
                    @change="onKepemilikanBpjsChange"
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all cursor-pointer border"
                    :class="hasError('kepemilikan_bpjs')
                        ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                        : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                >
                    <option value="">-- Pilih Kepemilikan BPJS --</option>
                    <option value="Ya">Ya</option>
                    <option value="Tidak">Tidak</option>
                </select>
                <div v-if="hasError('kepemilikan_bpjs')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">error</span>
                    <span>{{ getError('kepemilikan_bpjs') }}</span>
                </div>
            </div>

            <!-- Penanggung BPJS * -->
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                    Penanggung BPJS <span class="text-error font-bold">*</span>
                </label>
                <select
                    id="field-penanggung_bpjs"
                    v-model="form.penanggung_bpjs"
                    @change="clearFieldError('penanggung_bpjs')"
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all cursor-pointer border"
                    :class="hasError('penanggung_bpjs')
                        ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                        : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                >
                    <option value="">-- Pilih Penanggung BPJS --</option>
                    <option value="Yayasan">Yayasan</option>
                    <option value="Pemerintah">Pemerintah</option>
                    <option value="Suami/Istri">Suami/Istri</option>
                    <option value="Mandiri">Mandiri</option>
                    <option value="Tidak Ada">Tidak Ada</option>
                    <option
                        v-if="form.penanggung_bpjs && !['Yayasan', 'Pemerintah', 'Suami/Istri', 'Mandiri', 'Tidak Ada'].includes(form.penanggung_bpjs)"
                        :value="form.penanggung_bpjs"
                    >
                        {{ form.penanggung_bpjs }}
                    </option>
                </select>
                <div v-if="hasError('penanggung_bpjs')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">error</span>
                    <span>{{ getError('penanggung_bpjs') }}</span>
                </div>
            </div>

            <!-- Skill / Keahlian -->
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                    Skill / Keahlian
                </label>
                <input
                    v-model="form.skill"
                    type="text"
                    placeholder="Contoh: Matematika, Desain Grafis, IT"
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                    :class="{ 'border-error': form.errors.skill }"
                />
                <div v-if="form.errors.skill" class="text-error text-xs mt-1">{{ form.errors.skill }}</div>
            </div>

            <!-- Tombol Batal & Simpan di Bagian Bawah Form -->
            <div class="md:col-span-2 pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
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
        </div>
    </div>
</template>
