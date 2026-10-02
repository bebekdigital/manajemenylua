<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    classrooms: {
        type: Array,
        default: () => [],
    },
    academicYears: {
        type: Array,
        default: () => [],
    },
    initialAcademicYearId: {
        type: Number,
        default: null,
    },
});

const activeTab = ref('SDIT');
const tabs = [
    { id: 'SDIT', label: 'SDIT', icon: 'school' },
    { id: 'SMPIT', label: 'SMPIT', icon: 'school' },
];

const selectedAcademicYearId = ref(props.initialAcademicYearId);
const selectedClassId = ref(null);

// Filter classes based on selected Unit/Jenjang and Academic Year
const availableClasses = computed(() => {
    return props.classrooms.filter(c => 
        (c.jenjang === 'SD' && activeTab.value === 'SDIT' || c.jenjang === 'SMP' && activeTab.value === 'SMPIT' || c.unit === activeTab.value) 
        && c.academic_year_id === selectedAcademicYearId.value
    );
});

// Auto-select first class when available classes change
watch(availableClasses, (newClasses) => {
    if (newClasses.length > 0 && (!selectedClassId.value || !newClasses.find(c => c.id === selectedClassId.value))) {
        selectedClassId.value = newClasses[0].id;
    } else if (newClasses.length === 0) {
        selectedClassId.value = null;
    }
}, { immediate: true });

function switchTab(tabId) {
    activeTab.value = tabId;
}
</script>

<template>
    <Head title="Daftar Kelas - Pembelajaran" />

    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop font-sans">
        <div class="max-w-7xl mx-auto w-full">
            <!-- Page Header -->
            <div class="mb-6 border-b border-outline-variant/30">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 sm:gap-12 mb-4">
                    <div class="shrink-0">
                        <h2 class="text-xl sm:text-2xl text-primary font-bold leading-tight">
                            Daftar Kelas
                        </h2>
                        <p class="font-body-md text-[13px] sm:text-sm text-on-surface-variant mt-1">
                            Kelola seluruh kelas yang tersedia di setiap jenjang pendidikan
                        </p>
                    </div>

                    <!-- Filter Tahun Ajaran -->
                    <div class="flex items-center space-x-2 shrink-0">
                        <span class="text-sm text-on-surface-variant font-medium">T.A:</span>
                        <select
                            v-model="selectedAcademicYearId"
                            class="text-sm bg-surface border border-outline-variant rounded-lg px-3 py-1.5 focus:ring-primary focus:border-primary"
                        >
                            <option v-for="ta in academicYears" :key="ta.id" :value="ta.id">
                                {{ ta.name }} - {{ ta.semester }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Unit Tabs -->
                <div class="flex overflow-x-auto -mb-px">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        @click="switchTab(tab.id)"
                        :class="[
                            'flex items-center gap-2 px-3 sm:px-5 py-2.5 sm:py-3 text-xs sm:text-sm font-medium transition-colors whitespace-nowrap border-b-2 rounded-t-lg',
                            activeTab === tab.id
                                ? 'text-emerald-600 border-emerald-600 bg-gradient-to-t from-emerald-600/20 to-transparent'
                                : 'text-on-surface-variant border-transparent hover:text-on-surface hover:border-outline-variant'
                        ]"
                    >
                        <span class="material-symbols-outlined text-[16px] sm:text-[18px]">{{ tab.icon }}</span>
                        <span>{{ tab.label }}</span>
                    </button>
                </div>
            </div>

            <!-- Class Tabs -->
            <div v-if="availableClasses.length > 0" class="mb-6 flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
                <button
                    v-for="kelas in availableClasses"
                    :key="kelas.id"
                    @click="selectedClassId = kelas.id"
                    :class="[
                        'px-4 py-2 rounded-full text-sm font-semibold transition-all whitespace-nowrap border',
                        selectedClassId === kelas.id
                            ? 'bg-primary text-on-primary border-primary shadow-sm'
                            : 'bg-white text-on-surface-variant border-outline-variant/60 hover:border-primary hover:text-primary'
                    ]"
                >
                    {{ kelas.name }}
                </button>
            </div>

            <!-- Content Area (Table & Tambah Siswa) -->
            <div v-if="selectedClassId" class="bg-white rounded-2xl border border-outline-variant overflow-hidden">
                <!-- Toolbar -->
                <div class="p-4 border-b border-outline-variant/60 flex items-center justify-between bg-surface-container/30">
                    <h3 class="font-bold text-on-surface text-lg">Daftar Siswa</h3>
                    <button class="flex items-center gap-1.5 px-3 py-2 bg-primary text-on-primary rounded-lg text-sm font-semibold hover:bg-primary/90 transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        <span>Tambah Siswa</span>
                    </button>
                </div>
                
                <!-- Table (Empty State) -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-on-surface">
                        <thead class="bg-surface-container text-on-surface-variant text-xs uppercase font-semibold">
                            <tr>
                                <th scope="col" class="px-4 py-3 border-b border-outline-variant/50 w-12 text-center">No</th>
                                <th scope="col" class="px-4 py-3 border-b border-outline-variant/50">NISN</th>
                                <th scope="col" class="px-4 py-3 border-b border-outline-variant/50">Nama Lengkap</th>
                                <th scope="col" class="px-4 py-3 border-b border-outline-variant/50">Jenis Kelamin</th>
                                <th scope="col" class="px-4 py-3 border-b border-outline-variant/50">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Empty row for now -->
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-on-surface-variant bg-surface/50">
                                    <span class="material-symbols-outlined text-4xl mb-2 text-outline-variant block">group_off</span>
                                    <p class="font-medium text-body-md">Belum ada siswa di kelas ini</p>
                                    <p class="text-xs mt-1 text-outline">Klik tombol "Tambah Siswa" untuk memasukkan siswa ke kelas ini.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else-if="academicYears.length > 0" class="bg-white rounded-2xl border border-outline-variant p-12 text-center">
                <span class="material-symbols-outlined text-5xl text-outline-variant mb-4 block">meeting_room</span>
                <p class="font-body-lg text-body-lg text-on-surface-variant">Belum ada data kelas untuk Tahun Ajaran ini.</p>
                <p class="font-body-sm text-body-sm text-outline mt-1">Sinkronkan kelas dari pengaturan tahun ajaran.</p>
            </div>
        </div>
    </div>
</template>
