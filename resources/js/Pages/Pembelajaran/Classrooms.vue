<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    classrooms: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref('SDIT');
const tabs = [
    { id: 'SDIT', label: 'SDIT', icon: 'school' },
    { id: 'SMPIT', label: 'SMPIT', icon: 'school' },
];

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
                <div class="flex flex-col sm:flex-row sm:items-end justify-start gap-4 sm:gap-12">
                    <div class="pb-3 shrink-0">
                        <h2 class="text-xl sm:text-2xl text-primary font-bold leading-tight">
                            Daftar Kelas
                        </h2>
                        <p class="font-body-md text-[13px] sm:text-sm text-on-surface-variant mt-1">
                            Kelola seluruh kelas yang tersedia di setiap jenjang pendidikan
                        </p>
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
            </div>

            <!-- Content Area (Kosong untuk sementara) -->
            <div>
                <!-- Kosong, konten selanjutnya akan diisi di tahap berikutnya sesuai instruksi -->
            </div>
        </div>
    </div>
</template>
