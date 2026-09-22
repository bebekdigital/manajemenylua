<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchFilter from '@/Components/Shared/SearchFilter.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    students: { type: Array, default: () => [] },
    classrooms: { type: Array, default: () => [] },
    units: { type: Array, default: () => [] },
});

const searchQuery = ref('');
const activeFilters = ref({ unit: '', kelas: '', has_photo: '' });

const filters = computed(() => [
    {
        key: 'unit',
        label: 'Semua Unit',
        options: props.units.map(u => ({ value: u, label: u })),
    },
    {
        key: 'kelas',
        label: 'Semua Kelas',
        options: props.classrooms.map(k => ({ value: k, label: k })),
    },
    {
        key: 'has_photo',
        label: 'Semua Status Foto',
        options: [
            { value: 'yes', label: '✓ Ada Foto' },
            { value: 'no', label: '✗ Belum Ada Foto' },
        ],
    },
]);

const filteredStudents = computed(() => {
    let result = props.students;

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase().trim();
        result = result.filter(s =>
            s.nama?.toLowerCase().includes(q) ||
            s.nisn?.includes(q) ||
            s.kelas?.toLowerCase().includes(q)
        );
    }

    if (activeFilters.value.unit) {
        result = result.filter(s => s.unit === activeFilters.value.unit);
    }

    if (activeFilters.value.kelas) {
        result = result.filter(s => s.kelas === activeFilters.value.kelas);
    }

    if (activeFilters.value.has_photo === 'yes') {
        result = result.filter(s => s.photo_url);
    } else if (activeFilters.value.has_photo === 'no') {
        result = result.filter(s => !s.photo_url);
    }

    return result;
});

const withPhotoCount = computed(() => props.students.filter(s => s.photo_url).length);
const withoutPhotoCount = computed(() => props.students.length - withPhotoCount.value);
</script>

<template>
    <Head title="Cetak Foto Siswa" />

    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop bg-surface-container-lowest">
        <!-- Back + Header -->
        <div class="mb-6">
            <Link
                href="/administration"
                class="inline-flex items-center gap-1 text-sm font-medium text-on-surface-variant hover:text-primary transition-colors mb-4"
            >
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Kembali ke Administrasi
            </Link>

            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-on-surface">Cetak Foto Siswa</h2>
                    <p class="text-sm text-on-surface-variant mt-1">
                        Kelola dan cetak foto siswa sesuai kebutuhan administrasi
                    </p>
                </div>

                <!-- Stats summary -->
                <div class="flex gap-3 shrink-0">
                    <div class="flex flex-col items-center px-4 py-2 bg-primary/5 border border-primary/15 rounded-xl">
                        <span class="text-lg font-bold text-primary">{{ withPhotoCount }}</span>
                        <span class="text-[10px] text-on-surface-variant font-medium">Ada Foto</span>
                    </div>
                    <div class="flex flex-col items-center px-4 py-2 bg-error/5 border border-error/15 rounded-xl">
                        <span class="text-lg font-bold text-error">{{ withoutPhotoCount }}</span>
                        <span class="text-[10px] text-on-surface-variant font-medium">Belum Ada</span>
                    </div>
                    <div class="flex flex-col items-center px-4 py-2 bg-surface-container border border-outline-variant/30 rounded-xl">
                        <span class="text-lg font-bold text-on-surface">{{ students.length }}</span>
                        <span class="text-[10px] text-on-surface-variant font-medium">Total Siswa</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="mb-6">
            <SearchFilter
                v-model="searchQuery"
                v-model:activeFilters="activeFilters"
                :filters="filters"
                :result-count="filteredStudents.length"
                placeholder="Cari nama, NISN, atau kelas..."
            />
        </div>

        <!-- Empty State -->
        <div
            v-if="filteredStudents.length === 0"
            class="flex flex-col items-center justify-center py-24 text-center"
        >
            <span class="material-symbols-outlined text-5xl text-on-surface-variant/30 mb-4">photo_library</span>
            <p class="text-base font-semibold text-on-surface-variant">Tidak ada siswa ditemukan</p>
            <p class="text-sm text-on-surface-variant/60 mt-1">Coba ubah kata kunci pencarian atau filter</p>
        </div>

        <!-- Photo Grid -->
        <div
            v-else
            class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4"
        >
            <div
                v-for="student in filteredStudents"
                :key="student.nisn"
                class="group relative bg-surface rounded-2xl border border-outline-variant/30 overflow-hidden shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex flex-col"
            >
                <!-- Photo / Placeholder -->
                <div class="relative aspect-[3/4] bg-surface-container overflow-hidden">
                    <img
                        v-if="student.photo_url"
                        :src="student.photo_url"
                        :alt="student.nama"
                        class="w-full h-full object-cover object-top"
                        loading="lazy"
                    />
                    <div
                        v-else
                        class="w-full h-full flex flex-col items-center justify-center gap-2 bg-surface-container-high"
                    >
                        <span class="material-symbols-outlined text-4xl text-on-surface-variant/25">
                            person
                        </span>
                        <span class="text-[10px] text-on-surface-variant/40 font-medium">Belum ada foto</span>
                    </div>

                    <!-- Badge ada foto -->
                    <div
                        v-if="student.photo_url"
                        class="absolute top-2 right-2 w-5 h-5 rounded-full bg-emerald-500 flex items-center justify-center shadow"
                    >
                        <span class="material-symbols-outlined text-white text-[11px]">check</span>
                    </div>
                </div>

                <!-- Info -->
                <div class="p-2.5 flex flex-col gap-0.5">
                    <p class="text-[11px] font-mono text-on-surface-variant/60 leading-none">{{ student.nisn }}</p>
                    <p class="text-xs font-bold text-on-surface leading-snug line-clamp-2">{{ student.nama }}</p>
                    <p class="text-[10px] text-on-surface-variant mt-0.5">{{ student.kelas || '-' }}</p>
                </div>

                <!-- Hover overlay — link ke detail siswa -->
                <Link
                    :href="`/students/${student.nisn}`"
                    class="absolute inset-0 rounded-2xl ring-2 ring-transparent group-hover:ring-primary/30 transition-all"
                    :title="`Lihat detail ${student.nama}`"
                />
            </div>
        </div>
    </div>
</template>
