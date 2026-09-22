<script setup>
import { ref, computed, nextTick } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchFilter from '@/Components/Shared/SearchFilter.vue';
import PrintPhotoModal from '@/Components/Administration/PrintPhotoModal.vue';
import PrintPhotoLayout from '@/Components/Administration/PrintPhotoLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    students: { type: Array, default: () => [] },
    classrooms: { type: Array, default: () => [] },
    units: { type: Array, default: () => [] },
});

// ==============================
// Search & Filter
// ==============================
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

// ==============================
// Selection Logic
// ==============================
const selectedNisns = ref([]);

const isAllSelected = computed(() => {
    if (filteredStudents.value.length === 0) return false;
    return filteredStudents.value.every(s => selectedNisns.value.includes(s.nisn));
});

function toggleSelection(nisn) {
    const idx = selectedNisns.value.indexOf(nisn);
    if (idx > -1) {
        selectedNisns.value.splice(idx, 1);
    } else {
        selectedNisns.value.push(nisn);
    }
}

function toggleSelectAll() {
    if (isAllSelected.value) {
        // Deselect all currently filtered
        const currentNisns = filteredStudents.value.map(s => s.nisn);
        selectedNisns.value = selectedNisns.value.filter(n => !currentNisns.includes(n));
    } else {
        // Select all currently filtered
        const currentNisns = filteredStudents.value.map(s => s.nisn);
        currentNisns.forEach(n => {
            if (!selectedNisns.value.includes(n)) selectedNisns.value.push(n);
        });
    }
}

function clearSelection() {
    selectedNisns.value = [];
}

// ==============================
// Printing Logic
// ==============================
const showPrintModal = ref(false);
const isPrinting = ref(false);
const printSettings = ref(null);
const studentsToPrint = computed(() => {
    return props.students.filter(s => selectedNisns.value.includes(s.nisn));
});

function handlePrint(settings) {
    printSettings.value = settings;
    isPrinting.value = true;

    // Tambahkan class ke body untuk mengatur @page size dinamis
    const pageClass = settings.paperSize === 'F4' ? 'is-printing-f4' : 'is-printing-a4';
    document.body.classList.add(pageClass);

    // Inject dynamic @page style for specific paper sizes
    const styleId = 'dynamic-print-style';
    let styleEl = document.getElementById(styleId);
    if (!styleEl) {
        styleEl = document.createElement('style');
        styleEl.id = styleId;
        document.head.appendChild(styleEl);
    }

    // F4 size in mm is usually 215mm x 330mm
    // A4 is 210mm x 297mm
    if (settings.paperSize === 'F4') {
        styleEl.innerHTML = `@media print { @page { size: 215mm 330mm; margin: 0; } }`;
    } else {
        styleEl.innerHTML = `@media print { @page { size: A4; margin: 0; } }`;
    }

    // Wait for DOM to update AND modal transition to finish (leave-active is 150ms)
    setTimeout(() => {
        window.print();
        // Restore after print dialog closes
        isPrinting.value = false;
        document.body.classList.remove(pageClass);
        if (styleEl) styleEl.remove();
    }, 400);
}
</script>

<template>
    <Head title="Cetak Foto Siswa" />

    <!-- LAYOUT PRINT (Hanya tampil saat isPrinting = true) -->
    <Teleport to="body">
        <div v-if="isPrinting" class="print-only-container bg-white w-screen">
            <PrintPhotoLayout :students="studentsToPrint" :settings="printSettings" />
        </div>
    </Teleport>

    <!-- LAYOUT APLIKASI (Sembunyi saat isPrinting = true) -->
    <div v-show="!isPrinting" class="flex-1 overflow-y-auto p-4 md:p-margin-desktop bg-surface-container-lowest relative pb-24">
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
                        Pilih foto siswa secara massal lalu atur tata letaknya untuk dicetak.
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

        <!-- Toolbar (Search & Filter & Select All) -->
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-end sm:items-center gap-4">
            <SearchFilter
                v-model="searchQuery"
                v-model:activeFilters="activeFilters"
                :filters="filters"
                :result-count="filteredStudents.length"
                placeholder="Cari nama, NISN, atau kelas..."
                class="w-full sm:w-auto flex-1"
            />

            <!-- Select All Button -->
            <button
                v-if="filteredStudents.length > 0"
                @click="toggleSelectAll"
                class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border transition-all shrink-0"
                :class="isAllSelected ? 'bg-primary/10 text-primary border-primary/30' : 'bg-surface-container-low text-on-surface-variant border-outline-variant/40 hover:border-outline-variant'"
            >
                <span class="material-symbols-outlined text-[20px]">
                    {{ isAllSelected ? 'check_box' : 'check_box_outline_blank' }}
                </span>
                {{ isAllSelected ? 'Batal Pilih Semua' : 'Pilih Semua' }}
            </button>
        </div>

        <!-- Empty State -->
        <div
            v-if="filteredStudents.length === 0"
            class="flex flex-col items-center justify-center py-24 text-center bg-surface rounded-3xl border border-dashed border-outline-variant/50"
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
                @click="toggleSelection(student.nisn)"
                class="group relative rounded-2xl border overflow-hidden transition-all duration-200 flex flex-col cursor-pointer select-none"
                :class="[
                    selectedNisns.includes(student.nisn)
                        ? 'border-primary ring-2 ring-primary/50 bg-primary/5 shadow-md -translate-y-1'
                        : 'border-outline-variant/30 bg-surface shadow-sm hover:shadow-md hover:-translate-y-0.5'
                ]"
            >
                <!-- Checkbox -->
                <div class="absolute top-2 left-2 z-10 w-6 h-6 rounded-md bg-white/80 backdrop-blur border border-outline-variant shadow flex items-center justify-center transition-colors"
                     :class="selectedNisns.includes(student.nisn) ? 'bg-primary border-primary text-on-primary' : 'text-transparent group-hover:text-on-surface-variant/30'"
                >
                    <span class="material-symbols-outlined text-[18px]">check</span>
                </div>

                <!-- Photo / Placeholder -->
                <div class="relative aspect-[3/4] bg-surface-container overflow-hidden">
                    <img
                        v-if="student.photo_url"
                        :src="student.photo_url"
                        :alt="student.nama"
                        class="w-full h-full object-cover object-top transition-transform duration-500"
                        :class="selectedNisns.includes(student.nisn) ? 'scale-105' : 'group-hover:scale-105'"
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
                </div>

                <!-- Info -->
                <div class="p-3 flex flex-col gap-0.5">
                    <p class="text-[11px] font-mono leading-none" :class="selectedNisns.includes(student.nisn) ? 'text-primary' : 'text-on-surface-variant/60'">{{ student.nisn }}</p>
                    <p class="text-xs font-bold leading-snug line-clamp-2" :class="selectedNisns.includes(student.nisn) ? 'text-primary' : 'text-on-surface'">{{ student.nama }}</p>
                    <p class="text-[10px] mt-0.5" :class="selectedNisns.includes(student.nisn) ? 'text-primary/70' : 'text-on-surface-variant'">{{ student.kelas || '-' }}</p>
                </div>
            </div>
        </div>

        <!-- Floating Action Bar -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-10 scale-95"
            leave-active-class="transition-all duration-200 ease-in"
            leave-to-class="opacity-0 translate-y-10 scale-95"
        >
            <div
                v-if="selectedNisns.length > 0"
                class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40"
            >
                <div class="bg-surface border border-outline-variant/30 shadow-2xl rounded-2xl px-5 py-4 flex items-center gap-6">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-on-surface">{{ selectedNisns.length }} Foto Terpilih</span>
                        <span class="text-xs text-on-surface-variant">Siap untuk dicetak</span>
                    </div>

                    <div class="h-8 w-px bg-outline-variant/30 hidden sm:block"></div>

                    <div class="flex items-center gap-2">
                        <button
                            @click="clearSelection"
                            class="px-4 py-2 text-sm font-medium text-error hover:bg-error/10 rounded-xl transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            @click="showPrintModal = true"
                            class="flex items-center gap-2 px-5 py-2 bg-primary text-on-primary rounded-xl font-bold shadow-md hover:shadow-lg hover:bg-primary/90 transition-all active:scale-95 cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-xl">print</span>
                            Atur & Cetak
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>

    <!-- Modals -->
    <PrintPhotoModal
        :show="showPrintModal"
        :count="selectedNisns.length"
        @close="showPrintModal = false"
        @print="handlePrint"
    />
</template>

<style>
/* Sembunyikan elemen bawaan Inerita/Browser jika sedang print overlay ini */
@media print {
    body {
        background-color: white !important;
    }
    #app {
        display: none !important;
    }
    body > div:not(.print-only-container) {
        display: none !important;
    }
}
</style>
