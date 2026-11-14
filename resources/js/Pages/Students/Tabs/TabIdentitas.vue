<script setup>
import { computed, inject, nextTick, ref, watch } from 'vue';

const props = defineProps({
    student: {
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
    allUnits: {
        type: Array,
        default: () => [],
    },
    allLevels: {
        type: Array,
        default: () => [],
    },
    allPrograms: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['startEdit', 'cancelEdit', 'saveChanges']);

const showToast = inject('showToast', null);
const localErrors = ref({});

// Pilihan Unit Sekolah Formal (SDIT, SMPIT, SMAIT)
const formalUnitOptions = computed(() => {
    return (props.allUnits || []).filter(u => {
        const upper = (u || '').toUpperCase();
        return !upper.includes('PPTQ') && !upper.includes('PESANTREN') && !upper.includes('PONDOK');
    });
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
    if (props.form.program) set.add(props.form.program);
    return Array.from(set);
});

function onPesantrenUnitChange() {
    clearFieldError('unit_pesantren');
    if (props.form.unit_pesantren) {
        const currentProg = (props.form.program || '').toLowerCase();
        if (!currentProg.includes('boarding')) {
            const boardingMatch = programOptions.value.find(p => p.toLowerCase().includes('boarding'));
            props.form.program = boardingMatch || 'Boarding';
        }
    } else {
        const currentProg = (props.form.program || '').toLowerCase();
        if (currentProg.includes('boarding')) {
            const nonBoardingMatch = programOptions.value.find(p => !p.toLowerCase().includes('boarding'));
            props.form.program = nonBoardingMatch || 'Fullday';
        }
    }
}

function onProgramChange() {
    clearFieldError('program');
    const prog = (props.form.program || '').toLowerCase();
    if (prog.includes('boarding') || prog.includes('pptq') || prog.includes('pesantren')) {
        props.form.unit_pesantren = pesantrenUnitOptions.value[0] || 'PPTQ Ulil Albab';
    } else {
        props.form.unit_pesantren = '';
    }
}

const requiredFields = [
    { key: 'nama', label: 'Nama Lengkap', id: 'field-nama' },
    { key: 'nisn', label: 'NISN', id: 'field-nisn' },
    { key: 'jk', label: 'Jenis Kelamin', id: 'field-jk' },
    { key: 'tempat_lahir', label: 'Tempat Lahir', id: 'field-tempat_lahir' },
    { key: 'tanggal_lahir', label: 'Tanggal Lahir', id: 'field-tanggal_lahir' },
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

function hitungUsia(tanggalLahir, ttl) {
    if (tanggalLahir) {
        const birthDate = new Date(tanggalLahir);
        if (!isNaN(birthDate)) {
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            return `${age} Tahun`;
        }
    }

    if (ttl && typeof ttl === 'string') {
        const match = ttl.match(/\b(19\d{2}|20\d{2})\b/);
        if (match) {
            const birthYear = parseInt(match[1], 10);
            const currentYear = new Date().getFullYear();
            const age = currentYear - birthYear;
            if (age >= 0 && age < 120) {
                return `${age} Tahun`;
            }
        }
    }

    return '-';
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
                firstInvalidField = { key: 'email', label: 'Email', id: 'field-email' };
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
            const field = requiredFields.find((f) => f.key === firstErrorKey) || { id: `field-${firstErrorKey}` };
            if (field) {
                scrollToField(field.id);
            }
        }
    },
    { deep: true }
);
</script>

<template>
    <div class="space-y-6">
        <!-- ============================================================ -->
        <!-- CONTAINER 1: IDENTITAS PRIBADI SISWA                         -->
        <!-- ============================================================ -->
        <div class="p-4 sm:p-6 rounded-2xl bg-slate-50/60 border border-slate-200/90 shadow-2xs">
            <!-- Header Container 1 -->
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-200/70">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                    <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">
                        {{ isEditing ? 'Edit Identitas Pribadi Siswa' : 'Identitas Pribadi Siswa' }}
                    </h4>
                </div>

                <!-- Tombol Edit / Simpan di Pojok Kanan Atas -->
                <div v-if="canEdit" class="flex items-center gap-2">
                    <!-- Saat View Mode: Tombol Edit Data -->
                    <button
                        v-if="!isEditing"
                        type="button"
                        @click="$emit('startEdit')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                        title="Edit data identitas siswa"
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

            <!-- VIEW MODE - CONTAINER 1 -->
            <div v-if="!isEditing" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Nama Lengkap</label>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.nama }}</p>
                </div>
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">NISN</label>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1 font-mono">{{ student.nisn || '-' }}</p>
                </div>
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">NIPD</label>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1 font-mono">{{ student.nipd || '-' }}</p>
                </div>
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">NIK</label>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1 font-mono">{{ student.nik || '-' }}</p>
                </div>
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">No. KK</label>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1 font-mono">{{ student.no_kk || '-' }}</p>
                </div>
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Jenis Kelamin</label>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">
                        {{ student.jk === 'L' ? 'Laki-laki' : (student.jk === 'P' ? 'Perempuan' : '-') }}
                    </p>
                </div>
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Tempat, Tanggal Lahir</label>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.ttl || '-' }}</p>
                </div>
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Usia</label>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">
                        <span v-if="hitungUsia(student.tanggal_lahir, student.ttl) !== '-'" class="inline-flex items-center gap-1.5 text-emerald-800 font-bold">
                            <span class="material-symbols-outlined text-[18px]">cake</span>
                            <span>{{ hitungUsia(student.tanggal_lahir, student.ttl) }}</span>
                        </span>
                        <span v-else class="text-slate-400">-</span>
                    </p>
                </div>
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Agama</label>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.agama || '-' }}</p>
                </div>
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">No. WhatsApp 1</label>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.no_wa || '-' }}</p>
                </div>
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">No. WhatsApp 2</label>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.no_wa_2 || '-' }}</p>
                </div>
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Email</label>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.email || '-' }}</p>
                </div>
            </div>

            <!-- EDIT MODE - CONTAINER 1 -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Nama Lengkap * -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        Nama Lengkap <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input
                        id="field-nama"
                        v-model="form.nama"
                        type="text"
                        required
                        placeholder="Nama lengkap siswa"
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

                <!-- NISN * -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        NISN <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input
                        id="field-nisn"
                        v-model="form.nisn"
                        type="text"
                        required
                        placeholder="10 digit NISN"
                        @input="clearFieldError('nisn')"
                        class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface font-mono placeholder:text-outline focus:outline-none transition-all border"
                        :class="hasError('nisn')
                            ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                            : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                    />
                    <div v-if="hasError('nisn')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">error</span>
                        <span>{{ getError('nisn') }}</span>
                    </div>
                </div>

                <!-- NIPD -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        NIPD (Nomor Induk Peserta Didik)
                    </label>
                    <input
                        id="field-nipd"
                        v-model="form.nipd"
                        type="text"
                        placeholder="Nomor induk siswa di sekolah"
                        @input="clearFieldError('nipd')"
                        class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface font-mono placeholder:text-outline focus:outline-none transition-all border border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20"
                    />
                    <div v-if="hasError('nipd')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">error</span>
                        <span>{{ getError('nipd') }}</span>
                    </div>
                </div>

                <!-- NIK -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        NIK (Nomor Induk Kependudukan)
                    </label>
                    <input
                        id="field-nik"
                        v-model="form.nik"
                        type="text"
                        placeholder="16 digit NIK Siswa"
                        @input="clearFieldError('nik')"
                        class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface font-mono placeholder:text-outline focus:outline-none transition-all border border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20"
                    />
                    <div v-if="hasError('nik')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">error</span>
                        <span>{{ getError('nik') }}</span>
                    </div>
                </div>

                <!-- No. KK -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        No. KK (Kartu Keluarga)
                    </label>
                    <input
                        id="field-no_kk"
                        v-model="form.no_kk"
                        type="text"
                        placeholder="16 digit Nomor Kartu Keluarga"
                        @input="clearFieldError('no_kk')"
                        class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface font-mono placeholder:text-outline focus:outline-none transition-all border border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20"
                    />
                    <div v-if="hasError('no_kk')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">error</span>
                        <span>{{ getError('no_kk') }}</span>
                    </div>
                </div>

                <!-- Jenis Kelamin * -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        Jenis Kelamin <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <select
                        id="field-jk"
                        v-model="form.jk"
                        required
                        @change="clearFieldError('jk')"
                        class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all border cursor-pointer"
                        :class="hasError('jk')
                            ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                            : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                    >
                        <option value="">-- Pilih Jenis Kelamin --</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                    <div v-if="hasError('jk')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">error</span>
                        <span>{{ getError('jk') }}</span>
                    </div>
                </div>

                <!-- Agama -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        Agama
                    </label>
                    <select
                        id="field-agama"
                        v-model="form.agama"
                        @change="clearFieldError('agama')"
                        class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all border border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                    >
                        <option value="Islam">Islam</option>
                        <option value="Kristen">Kristen</option>
                        <option value="Katolik">Katolik</option>
                        <option value="Hindu">Hindu</option>
                        <option value="Buddha">Buddha</option>
                        <option value="Konghucu">Konghucu</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                    <div v-if="hasError('agama')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">error</span>
                        <span>{{ getError('agama') }}</span>
                    </div>
                </div>

                <!-- Tempat Lahir * -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        Tempat Lahir <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input
                        id="field-tempat_lahir"
                        v-model="form.tempat_lahir"
                        type="text"
                        required
                        placeholder="Kota / Kabupaten Lahir"
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
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        Tanggal Lahir <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input
                        id="field-tanggal_lahir"
                        v-model="form.tanggal_lahir"
                        type="date"
                        required
                        @input="clearFieldError('tanggal_lahir')"
                        class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all border cursor-pointer"
                        :class="hasError('tanggal_lahir')
                            ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10 focus:border-rose-600 focus:ring-rose-500/30'
                            : 'border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20'"
                    />
                    <div v-if="hasError('tanggal_lahir')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">error</span>
                        <span>{{ getError('tanggal_lahir') }}</span>
                    </div>
                </div>

                <!-- No. WhatsApp 1 -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        No. WhatsApp 1
                    </label>
                    <input
                        id="field-no_wa"
                        v-model="form.no_wa"
                        type="text"
                        placeholder="Contoh: 081234567890"
                        @input="clearFieldError('no_wa')"
                        class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none transition-all border border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20"
                    />
                    <div v-if="hasError('no_wa')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">error</span>
                        <span>{{ getError('no_wa') }}</span>
                    </div>
                </div>

                <!-- No. WhatsApp 2 -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        No. WhatsApp 2
                    </label>
                    <input
                        id="field-no_wa_2"
                        v-model="form.no_wa_2"
                        type="text"
                        placeholder="Contoh: 089876543210"
                        @input="clearFieldError('no_wa_2')"
                        class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none transition-all border border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20"
                    />
                    <div v-if="hasError('no_wa_2')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">error</span>
                        <span>{{ getError('no_wa_2') }}</span>
                    </div>
                </div>

                <!-- Email -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        Email
                    </label>
                    <input
                        id="field-email"
                        v-model="form.email"
                        type="email"
                        placeholder="Contoh: siswa@sekolah.sch.id"
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
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- CONTAINER 2: INFORMASI SEKOLAH & AKADEMIK                   -->
        <!-- ============================================================ -->
        <div class="p-4 sm:p-6 rounded-2xl bg-slate-50/60 border border-slate-200/90 shadow-2xs">
            <!-- Header Container 2 -->
            <div class="flex items-center gap-2 pb-3 mb-4 border-b border-slate-200/70">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">
                    {{ isEditing ? 'Edit Data Sekolah & Akademik' : 'Informasi Sekolah & Akademik' }}
                </h4>
            </div>

            <!-- VIEW MODE - CONTAINER 2 -->
            <div v-if="!isEditing" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                <!-- 1. Unit Sekolah Formal -->
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Unit Sekolah Formal</label>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">
                        {{ student.unit || '-' }}
                    </p>
                </div>

                <!-- 2. Unit Kepesantrenan / Asrama -->
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Unit Kepesantrenan / Asrama</label>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">
                        {{ student.unit_pesantren || '-' }}
                    </p>
                </div>

                <!-- 3. Program Pendidikan -->
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Program Pendidikan</label>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">
                        <span :class="[
                            'inline-flex items-center px-2 py-0.5 rounded-md text-[10px] sm:text-xs font-semibold',
                            (student.program || '').toLowerCase().includes('boarding') ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' :
                            (student.program || '').toLowerCase().includes('fullday') ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' :
                            'bg-slate-100 text-slate-700 border border-slate-200'
                        ]">
                            {{ student.program || 'Umum' }}
                        </span>
                    </p>
                </div>

                <!-- 4. Jenjang -->
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Jenjang</label>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.jenjang || '-' }}</p>
                </div>

                <!-- 5. Angkatan -->
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Angkatan</label>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">
                        <span v-if="student.angkatan" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            Angkatan {{ student.angkatan }}
                        </span>
                        <span v-else class="text-slate-400">-</span>
                    </p>
                </div>

                <!-- 6. Tingkat & Kelas -->
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Tingkat / Kelas</label>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">
                        {{ student.tingkat ? `Tingkat ${student.tingkat}` : '' }}
                        {{ student.kelas ? `(${student.kelas})` : (student.tingkat ? '' : '-') }}
                    </p>
                </div>
            </div>

            <!-- EDIT MODE - CONTAINER 2 -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Field 1: Unit Sekolah Formal -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        Unit Sekolah Formal <span class="text-error">*</span>
                    </label>
                    <select
                        v-if="allUnits && allUnits.length > 0"
                        id="field-unit"
                        v-model="form.unit"
                        @change="clearFieldError('unit')"
                        class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all border border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                    >
                        <option value="">-- Pilih Unit Formal --</option>
                        <option v-for="u in (formalUnitOptions.length > 0 ? formalUnitOptions : allUnits)" :key="u" :value="u">{{ u }}</option>
                        <option v-if="form.unit && !allUnits.includes(form.unit)" :value="form.unit">{{ form.unit }}</option>
                    </select>
                    <input
                        v-else
                        id="field-unit"
                        v-model="form.unit"
                        type="text"
                        placeholder="Contoh: SDIT Ulil Albab Gondangrejo"
                        @input="clearFieldError('unit')"
                        class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none transition-all border border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20"
                    />
                    <p class="text-[11px] text-slate-500 mt-1">Satuan pendidikan formal siswa (Kemdikbud/Kemenag).</p>
                    <div v-if="hasError('unit')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">error</span>
                        <span>{{ getError('unit') }}</span>
                    </div>
                </div>

                <!-- Field 2: Program -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        Program Pendidikan
                    </label>
                    <select
                        id="field-program"
                        v-model="form.program"
                        @change="onProgramChange"
                        class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all border border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                    >
                        <option v-for="prog in programOptions" :key="prog" :value="prog">{{ prog }}</option>
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1">
                        <span v-if="form.unit_pesantren" class="text-indigo-600 font-medium">Siswa terdata di unit pesantren: {{ form.unit_pesantren }}</span>
                        <span v-else>Siswa non-asrama (Fullday / Reguler).</span>
                    </p>
                    <div v-if="hasError('program')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">error</span>
                        <span>{{ getError('program') }}</span>
                    </div>
                </div>

                <!-- Field 3: Unit Kepesantrenan / Asrama -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        Unit Kepesantrenan / Asrama
                    </label>
                    <select
                        id="field-unit-pesantren"
                        v-model="form.unit_pesantren"
                        @change="onPesantrenUnitChange"
                        class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all border border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                    >
                        <option value="">-- Tidak Berasrama (Non-Boarding) --</option>
                        <option v-for="p in pesantrenUnitOptions" :key="p" :value="p">{{ p }}</option>
                        <option v-if="form.unit_pesantren && !pesantrenUnitOptions.includes(form.unit_pesantren)" :value="form.unit_pesantren">{{ form.unit_pesantren }}</option>
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1">Otomatis terisi jika memilih program kepesantrenan (Boarding) atau dapat dipilih manual.</p>
                </div>

                <!-- Field 4: Jenjang -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        Jenjang
                    </label>
                    <select
                        v-if="allLevels && allLevels.length > 0"
                        id="field-jenjang"
                        v-model="form.jenjang"
                        @change="clearFieldError('jenjang')"
                        class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all border border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                    >
                        <option value="">-- Pilih Jenjang --</option>
                        <option v-for="l in allLevels" :key="l" :value="l">{{ l }}</option>
                        <option v-if="form.jenjang && !allLevels.includes(form.jenjang)" :value="form.jenjang">{{ form.jenjang }}</option>
                    </select>
                    <select
                        v-else
                        id="field-jenjang"
                        v-model="form.jenjang"
                        @change="clearFieldError('jenjang')"
                        class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none transition-all border border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                    >
                        <option value="">-- Pilih Jenjang --</option>
                        <option value="PAUD">PAUD</option>
                        <option value="TK">TK</option>
                        <option value="SD">SD</option>
                        <option value="SMP">SMP</option>
                        <option value="SMA">SMA</option>
                    </select>
                    <div v-if="hasError('jenjang')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">error</span>
                        <span>{{ getError('jenjang') }}</span>
                    </div>
                </div>

                <!-- Field 5: Angkatan -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        Angkatan
                    </label>
                    <input
                        id="field-angkatan"
                        v-model="form.angkatan"
                        type="text"
                        placeholder="Contoh: 8, 9, 10"
                        @input="clearFieldError('angkatan')"
                        class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none transition-all border border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20"
                    />
                    <div v-if="hasError('angkatan')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">error</span>
                        <span>{{ getError('angkatan') }}</span>
                    </div>
                </div>

                <!-- Field 6: Tingkat & Kelas -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                            Tingkat
                        </label>
                        <input
                            id="field-tingkat"
                            v-model="form.tingkat"
                            type="text"
                            placeholder="Contoh: 1, 2, 7, 10"
                            @input="clearFieldError('tingkat')"
                            class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none transition-all border border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20"
                        />
                        <div v-if="hasError('tingkat')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            <span>{{ getError('tingkat') }}</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                            Kelas
                        </label>
                        <input
                            id="field-kelas"
                            v-model="form.kelas"
                            type="text"
                            placeholder="Contoh: 1A, 7B"
                            @input="clearFieldError('kelas')"
                            class="w-full px-3.5 py-2 sm:py-2.5 bg-white rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none transition-all border border-outline-variant/60 focus:border-primary focus:ring-2 focus:ring-primary/20"
                        />
                        <div v-if="hasError('kelas')" class="text-rose-600 font-medium text-xs mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            <span>{{ getError('kelas') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tombol Batal & Simpan di Bagian Bawah Form (Saat Edit Mode) -->
        <div v-if="isEditing" class="pt-2 flex items-center justify-end gap-2">
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
</template>

