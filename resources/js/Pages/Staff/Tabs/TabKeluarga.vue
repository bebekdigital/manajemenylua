<script setup>
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
});

defineEmits(['startEdit', 'cancelEdit', 'saveChanges']);
</script>

<template>
    <div class="space-y-6">
        <!-- Section Header Keluarga Pegawai -->
        <div class="flex items-center justify-between pb-3 mb-2 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">
                    Data Keluarga Pegawai
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
                    title="Edit data keluarga pegawai"
                >
                    <span class="material-symbols-outlined text-[16px] sm:text-[18px]">edit_note</span>
                    <span>Edit Data</span>
                </button>

                <!-- Saat Edit Mode: Tombol Batal & Simpan -->
                <template v-else>
                    <button
                        type="button"
                        @click="$emit('cancelEdit')"
                        class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs"
                        :disabled="form.processing"
                        title="Batalkan perubahan"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="$emit('saveChanges')"
                        class="inline-flex items-center gap-1.5 px-4 py-1.5 sm:px-5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all"
                        :disabled="form.processing"
                        title="Simpan perubahan data keluarga"
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
            <!-- Pernikahan -->
            <div>
                <div class="flex items-center gap-2 mb-3 text-emerald-950 font-bold text-xs sm:text-sm">
                    <span class="material-symbols-outlined text-[18px] text-emerald-700">favorite</span>
                    <h5>Status Pernikahan & Pasangan</h5>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 pl-1">
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Status Pernikahan</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.status_pernikahan || '-' }}</p>
                    </div>
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Tanggal Menikah</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.formatted_tanggal_menikah || '-' }}</p>
                    </div>
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Nama Suami/Istri</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.nama_suami_istri || '-' }}</p>
                    </div>
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">TTL Suami/Istri</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.ttl_suami_istri || '-' }}</p>
                    </div>
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Pekerjaan Suami/Istri</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.pekerjaan_suami_istri || '-' }}</p>
                    </div>
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Jumlah Anak</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">
                            {{ employee.jumlah_anak !== null && employee.jumlah_anak !== undefined ? employee.jumlah_anak + ' anak' : '-' }}
                        </p>
                    </div>
                </div>
            </div>

            <hr class="border-slate-100 my-6" />

            <!-- Orangtua -->
            <div>
                <div class="flex items-center gap-2 mb-3 text-emerald-950 font-bold text-xs sm:text-sm">
                    <span class="material-symbols-outlined text-[18px] text-emerald-700">family_restroom</span>
                    <h5>Data Orangtua</h5>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 pl-1">
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Nama Ibu</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.nama_ibu || '-' }}</p>
                    </div>
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Nama Ayah</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.nama_ayah || '-' }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Alamat Orangtua</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.alamat_orangtua || '-' }}</p>
                    </div>
                </div>
            </div>

            <hr class="border-slate-100 my-6" />

            <!-- Kontak Darurat -->
            <div>
                <div class="flex items-center gap-2 mb-3 text-emerald-950 font-bold text-xs sm:text-sm">
                    <span class="material-symbols-outlined text-[18px] text-emerald-700">contact_phone</span>
                    <h5>Kontak Darurat</h5>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 pl-1">
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Nomor Kontak</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1 font-mono">{{ employee.kontak_darurat || '-' }}</p>
                    </div>
                    <div>
                        <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Hubungan</label>
                        <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.hubungan_kontak_darurat || '-' }}</p>
                    </div>
                </div>
            </div>
        </template>

        <!-- EDIT MODE -->
        <template v-else>
            <!-- Pernikahan Form -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-outline-variant/60 shadow-2xs space-y-4">
                <div class="flex items-center gap-2 text-primary font-bold text-sm">
                    <span class="material-symbols-outlined text-[18px]">favorite</span>
                    <h4>Status Pernikahan & Pasangan</h4>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-on-surface-variant mb-1">Status Pernikahan</label>
                        <select
                            v-model="form.status_pernikahan"
                            class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                        >
                            <option value="">-- Pilih Status --</option>
                            <option value="Belum Menikah">Belum Menikah</option>
                            <option value="Menikah">Menikah</option>
                            <option value="Cerai Hidup">Cerai Hidup</option>
                            <option value="Cerai Mati">Cerai Mati</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-on-surface-variant mb-1">Tanggal Menikah</label>
                        <input
                            v-model="form.tanggal_menikah"
                            type="date"
                            class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-on-surface-variant mb-1">Nama Suami / Istri</label>
                        <input
                            v-model="form.nama_suami_istri"
                            type="text"
                            placeholder="Nama lengkap pasangan"
                            class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-on-surface-variant mb-1">TTL Suami / Istri</label>
                        <input
                            v-model="form.ttl_suami_istri"
                            type="text"
                            placeholder="Contoh: Solo, 10 Maret 1987"
                            class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-on-surface-variant mb-1">Pekerjaan Suami / Istri</label>
                        <input
                            v-model="form.pekerjaan_suami_istri"
                            type="text"
                            placeholder="Contoh: Guru / Wiraswasta / PNS"
                            class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-on-surface-variant mb-1">Jumlah Anak</label>
                        <input
                            v-model="form.jumlah_anak"
                            type="number"
                            min="0"
                            placeholder="0"
                            class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                        />
                    </div>
                </div>
            </div>

            <!-- Data Orangtua Form -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-outline-variant/60 shadow-2xs space-y-4">
                <div class="flex items-center gap-2 text-primary font-bold text-sm">
                    <span class="material-symbols-outlined text-[18px]">family_restroom</span>
                    <h4>Data Orangtua</h4>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-on-surface-variant mb-1">Nama Ibu</label>
                        <input
                            v-model="form.nama_ibu"
                            type="text"
                            placeholder="Nama lengkap ibu"
                            class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-on-surface-variant mb-1">Nama Ayah</label>
                        <input
                            v-model="form.nama_ayah"
                            type="text"
                            placeholder="Nama lengkap ayah"
                            class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-on-surface-variant mb-1">Alamat Orangtua</label>
                        <input
                            v-model="form.alamat_orangtua"
                            type="text"
                            placeholder="Alamat tempat tinggal orangtua"
                            class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                        />
                    </div>
                </div>
            </div>

            <!-- Kontak Darurat Form -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-outline-variant/60 shadow-2xs space-y-4">
                <div class="flex items-center gap-2 text-primary font-bold text-sm">
                    <span class="material-symbols-outlined text-[18px]">contact_phone</span>
                    <h4>Kontak Darurat</h4>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-on-surface-variant mb-1">Nomor Kontak Darurat</label>
                        <input
                            v-model="form.kontak_darurat"
                            type="text"
                            placeholder="Contoh: 082345678901"
                            class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-on-surface-variant mb-1">Hubungan</label>
                        <input
                            v-model="form.hubungan_kontak_darurat"
                            type="text"
                            placeholder="Contoh: Saudara Kandung / Paman / Kerabat"
                            class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                        />
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

