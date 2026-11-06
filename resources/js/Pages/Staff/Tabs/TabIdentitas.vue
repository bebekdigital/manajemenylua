<script setup>
import { hitungUsia } from '../staffHelpers';

defineProps({
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

defineEmits(['startEdit', 'cancelEdit', 'saveChanges']);
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
                        @click="$emit('cancelEdit')"
                        class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs"
                        :disabled="form.processing"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="$emit('saveChanges')"
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

            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                    Nama Lengkap <span class="text-error">*</span>
                </label>
                <input
                    v-model="form.nama"
                    type="text"
                    required
                    placeholder="Nama lengkap tanpa gelar"
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                    :class="{ 'border-error': form.errors.nama }"
                />
                <div v-if="form.errors.nama" class="text-error text-xs mt-1">{{ form.errors.nama }}</div>
            </div>

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

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                    NIK (Nomor Induk Kependudukan)
                </label>
                <input
                    v-model="form.nik"
                    type="text"
                    placeholder="16 digit NIK"
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all font-mono"
                    :class="{ 'border-error': form.errors.nik }"
                />
                <div v-if="form.errors.nik" class="text-error text-xs mt-1">{{ form.errors.nik }}</div>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                    Jenis Kelamin
                </label>
                <select
                    v-model="form.jk"
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                    :class="{ 'border-error': form.errors.jk }"
                >
                    <option value="">-- Pilih Jenis Kelamin --</option>
                    <option value="L">Laki-laki (L)</option>
                    <option value="P">Perempuan (P)</option>
                </select>
                <div v-if="form.errors.jk" class="text-error text-xs mt-1">{{ form.errors.jk }}</div>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                    Nomor WhatsApp
                </label>
                <input
                    v-model="form.no_wa"
                    type="text"
                    placeholder="Contoh: 081234567890"
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                    :class="{ 'border-error': form.errors.no_wa }"
                />
                <div v-if="form.errors.no_wa" class="text-error text-xs mt-1">{{ form.errors.no_wa }}</div>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                    Tempat Lahir
                </label>
                <input
                    v-model="form.tempat_lahir"
                    type="text"
                    placeholder="Kota / Kabupaten Kelahiran"
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                    :class="{ 'border-error': form.errors.tempat_lahir }"
                />
                <div v-if="form.errors.tempat_lahir" class="text-error text-xs mt-1">{{ form.errors.tempat_lahir }}</div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                        Tanggal Lahir
                    </label>
                    <span v-if="hitungUsia(form.tanggal_lahir) !== '-'" class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary bg-primary/10 px-2 py-0.5 rounded-md">
                        <span class="material-symbols-outlined text-[13px]">cake</span>
                        <span>Usia: {{ hitungUsia(form.tanggal_lahir) }}</span>
                    </span>
                </div>
                <input
                    v-model="form.tanggal_lahir"
                    type="date"
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                    :class="{ 'border-error': form.errors.tanggal_lahir }"
                />
                <div v-if="form.errors.tanggal_lahir" class="text-error text-xs mt-1">{{ form.errors.tanggal_lahir }}</div>
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                    Email
                </label>
                <input
                    v-model="form.email"
                    type="email"
                    placeholder="nama@email.com"
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                    :class="{ 'border-error': form.errors.email }"
                />
                <div v-if="form.errors.email" class="text-error text-xs mt-1">{{ form.errors.email }}</div>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                    Kepemilikan BPJS
                </label>
                <select
                    v-model="form.kepemilikan_bpjs"
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                >
                    <option value="">-- Pilih --</option>
                    <option value="Ya">Ya</option>
                    <option value="Tidak">Tidak</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                    Penanggung BPJS
                </label>
                <select
                    v-model="form.penanggung_bpjs"
                    class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                    :class="{ 'border-error': form.errors.penanggung_bpjs }"
                >
                    <option value="">-- Pilih Penanggung BPJS --</option>
                    <option value="Yayasan">Yayasan</option>
                    <option value="Pemerintah">Pemerintah</option>
                    <option value="Suami/Istri">Suami/Istri</option>
                    <option value="Mandiri">Mandiri</option>
                    <option
                        v-if="form.penanggung_bpjs && !['Yayasan', 'Pemerintah', 'Suami/Istri', 'Mandiri'].includes(form.penanggung_bpjs)"
                        :value="form.penanggung_bpjs"
                    >
                        {{ form.penanggung_bpjs }}
                    </option>
                </select>
                <div v-if="form.errors.penanggung_bpjs" class="text-error text-xs mt-1">{{ form.errors.penanggung_bpjs }}</div>
            </div>

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
        </div>
    </div>
</template>

