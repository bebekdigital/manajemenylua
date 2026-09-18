<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    students: {
        type: Array,
        required: true,
    },
    selectedAcademicYear: {
        type: Object,
        default: null,
    },
});

// We create a deep copy of the students array for our form
const form = useForm({
    students: props.students.map(s => ({ ...s }))
});

function saveChanges() {
    form.post(route('students.inline-update'), {
        preserveScroll: true,
        onSuccess: () => {
            // handle success if needed
        },
    });
}

// Helper to filter
const searchQuery = ref('');
const filteredStudents = computed(() => {
    if (!searchQuery.value) return form.students;
    const query = searchQuery.value.toLowerCase().trim();
    return form.students.filter(s =>
        s.nama.toLowerCase().includes(query) ||
        s.nisn.includes(query) ||
        (s.nipd && s.nipd.includes(query))
    );
});
</script>

<template>
    <Head title="Inline Edit Data Siswa" />

    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop bg-surface-container-lowest">
        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm text-on-surface-variant mb-1">
                    <Link href="/students" class="hover:text-primary hover:underline">Database Siswa</Link>
                    <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                    <span class="font-medium text-on-surface">Inline Edit</span>
                </div>
                <h2 class="text-xl sm:text-2xl text-primary font-bold leading-tight">
                    Edit Data Massal
                </h2>
                <p class="font-body-md text-[13px] sm:text-sm text-on-surface-variant mt-1">
                    Ubah data peserta didik secara langsung pada tabel di bawah ini.
                </p>
            </div>
            <!-- Actions -->
            <div class="flex items-center gap-2 shrink-0">
                <Link
                    href="/students"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 sm:px-4 sm:py-2.5 rounded-lg sm:rounded-xl border border-outline-variant text-on-surface font-semibold text-[11px] sm:text-sm transition-all hover:bg-surface-variant"
                >
                    <span class="material-symbols-outlined text-[16px] sm:text-[18px]">close</span>
                    <span>Batal</span>
                </Link>

                <button
                    type="button"
                    @click="saveChanges"
                    :disabled="form.processing"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 sm:px-4 sm:py-2.5 rounded-lg sm:rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-[11px] sm:text-sm transition-all shadow-xs hover:shadow-md disabled:opacity-50 cursor-pointer"
                >
                    <span class="material-symbols-outlined text-[16px] sm:text-[18px]">save</span>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </div>

        <div class="bg-white rounded-xl sm:rounded-2xl p-3 sm:p-4 md:p-5 mb-4 shadow-sm border border-slate-200">
             <!-- Search -->
             <div class="relative w-full sm:w-64 mb-4">
                <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-outline pointer-events-none text-[18px]">search</span>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari siswa..."
                    class="w-full pl-9 pr-3 py-2 bg-white border border-slate-300 rounded-lg text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors"
                />
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-xl border border-slate-200" style="max-height: 60vh;">
                <table class="w-full text-left border-collapse min-w-max relative">
                    <thead class="sticky top-0 z-20">
                        <tr class="bg-emerald-600 border-b border-emerald-700">
                            <th class="p-3 text-[12px] font-semibold text-white uppercase tracking-wider w-12 sticky left-0 z-30 bg-emerald-600 border-r border-emerald-700">No</th>
                            <th class="p-3 text-[12px] font-semibold text-white uppercase tracking-wider w-32 sticky left-[48px] z-30 bg-emerald-600 border-r border-emerald-700">NISN</th>
                            <th class="p-3 text-[12px] font-semibold text-white uppercase tracking-wider min-w-[200px] sticky left-[176px] z-30 bg-emerald-600 border-r border-emerald-700 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">Nama Lengkap</th>
                            <th class="p-3 text-[12px] font-semibold text-white uppercase tracking-wider w-32">NIPD</th>
                            <th class="p-3 text-[12px] font-semibold text-white uppercase tracking-wider w-32">Unit</th>
                            <th class="p-3 text-[12px] font-semibold text-white uppercase tracking-wider w-32">Program</th>
                            <th class="p-3 text-[12px] font-semibold text-white uppercase tracking-wider w-24">Jenjang</th>
                            <th class="p-3 text-[12px] font-semibold text-white uppercase tracking-wider w-24">Tingkat</th>
                            <th class="p-3 text-[12px] font-semibold text-white uppercase tracking-wider w-24">Kelas</th>
                            <th class="p-3 text-[12px] font-semibold text-white uppercase tracking-wider w-24">JK</th>
                            <th class="p-3 text-[12px] font-semibold text-white uppercase tracking-wider w-40">Tempat Lahir</th>
                            <th class="p-3 text-[12px] font-semibold text-white uppercase tracking-wider w-40">Tanggal Lahir</th>
                            <th class="p-3 text-[12px] font-semibold text-white uppercase tracking-wider w-32">No WA</th>
                            <th class="p-3 text-[12px] font-semibold text-white uppercase tracking-wider w-48">Jalan</th>
                            <th class="p-3 text-[12px] font-semibold text-white uppercase tracking-wider w-24">RT/RW</th>
                            <th class="p-3 text-[12px] font-semibold text-white uppercase tracking-wider w-32">Dusun</th>
                            <th class="p-3 text-[12px] font-semibold text-white uppercase tracking-wider w-32">Desa</th>
                            <th class="p-3 text-[12px] font-semibold text-white uppercase tracking-wider w-32">Kecamatan</th>
                            <th class="p-3 text-[12px] font-semibold text-white uppercase tracking-wider w-32">Kabupaten</th>
                            <th class="p-3 text-[12px] font-semibold text-white uppercase tracking-wider w-32">Provinsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="(student, index) in filteredStudents" :key="student.nisn" class="hover:bg-slate-50 transition-colors">
                            <td class="p-2 text-[12px] text-slate-600 sticky left-0 z-10 bg-white border-r border-slate-200 group-hover:bg-slate-50">
                                {{ index + 1 }}
                            </td>
                            <td class="p-2 text-[12px] text-slate-600 font-mono sticky left-[48px] z-10 bg-white border-r border-slate-200 group-hover:bg-slate-50">
                                {{ student.nisn }}
                            </td>
                            <td class="p-2 sticky left-[176px] z-10 bg-white border-r border-slate-200 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] group-hover:bg-slate-50">
                                <input v-model="student.nama" type="text" class="w-full px-2 py-1.5 text-[12px] border border-slate-300 rounded focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none" />
                            </td>
                            <td class="p-2">
                                <input v-model="student.nipd" type="text" class="w-full px-2 py-1.5 text-[12px] border border-slate-300 rounded focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none" />
                            </td>
                            <td class="p-2">
                                <select v-model="student.unit" class="w-full px-2 py-1.5 text-[12px] border border-slate-300 rounded focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">
                                    <option value="">-</option>
                                    <option value="SDIT Ulil Albab Gondangrejo">SDIT Ulil Albab Gondangrejo</option>
                                    <option value="SMPIT Ulil Albab Gondangrejo">SMPIT Ulil Albab Gondangrejo</option>
                                    <option value="PPTQ Ulil Albab Gondangrejo">PPTQ Ulil Albab Gondangrejo</option>
                                </select>
                            </td>
                            <td class="p-2">
                                <select v-model="student.program" class="w-full px-2 py-1.5 text-[12px] border border-slate-300 rounded focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">
                                    <option value="Umum">Umum</option>
                                    <option value="Fullday">Fullday</option>
                                    <option value="Boarding">Boarding</option>
                                </select>
                            </td>
                            <td class="p-2">
                                <select v-model="student.jenjang" class="w-full px-2 py-1.5 text-[12px] border border-slate-300 rounded focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">
                                    <option value="">-</option>
                                    <option value="SD">SD</option>
                                    <option value="SMP">SMP</option>
                                    <option value="SMA">SMA</option>
                                </select>
                            </td>
                            <td class="p-2">
                                <input v-model="student.tingkat" type="number" class="w-full px-2 py-1.5 text-[12px] border border-slate-300 rounded focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none" />
                            </td>
                            <td class="p-2">
                                <input v-model="student.kelas" type="text" class="w-full px-2 py-1.5 text-[12px] border border-slate-300 rounded focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none" />
                            </td>
                            <td class="p-2">
                                <select v-model="student.jk" class="w-full px-2 py-1.5 text-[12px] border border-slate-300 rounded focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">
                                    <option value="L">L</option>
                                    <option value="P">P</option>
                                </select>
                            </td>
                            <td class="p-2">
                                <input v-model="student.tempat_lahir" type="text" class="w-full px-2 py-1.5 text-[12px] border border-slate-300 rounded focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none" />
                            </td>
                            <td class="p-2">
                                <input v-model="student.tanggal_lahir" type="date" class="w-full px-2 py-1.5 text-[12px] border border-slate-300 rounded focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none" />
                            </td>
                            <td class="p-2">
                                <input v-model="student.no_wa" type="text" class="w-full px-2 py-1.5 text-[12px] border border-slate-300 rounded focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none" />
                            </td>
                            <td class="p-2">
                                <input v-model="student.jalan" type="text" class="w-full px-2 py-1.5 text-[12px] border border-slate-300 rounded focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none" />
                            </td>
                            <td class="p-2">
                                <input v-model="student.rt_rw" type="text" class="w-full px-2 py-1.5 text-[12px] border border-slate-300 rounded focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none" />
                            </td>
                            <td class="p-2">
                                <input v-model="student.dusun" type="text" class="w-full px-2 py-1.5 text-[12px] border border-slate-300 rounded focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none" />
                            </td>
                            <td class="p-2">
                                <input v-model="student.desa" type="text" class="w-full px-2 py-1.5 text-[12px] border border-slate-300 rounded focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none" />
                            </td>
                            <td class="p-2">
                                <input v-model="student.kecamatan" type="text" class="w-full px-2 py-1.5 text-[12px] border border-slate-300 rounded focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none" />
                            </td>
                            <td class="p-2">
                                <input v-model="student.kabupaten" type="text" class="w-full px-2 py-1.5 text-[12px] border border-slate-300 rounded focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none" />
                            </td>
                            <td class="p-2">
                                <input v-model="student.provinsi" type="text" class="w-full px-2 py-1.5 text-[12px] border border-slate-300 rounded focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none" />
                            </td>
                        </tr>
                        <tr v-if="filteredStudents.length === 0">
                            <td colspan="20" class="p-8 text-center text-slate-500">
                                Tidak ada data yang sesuai.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
