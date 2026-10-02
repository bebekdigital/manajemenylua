<script setup>
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

function groupByJenjang(classrooms) {
    const groups = {};
    classrooms.forEach(c => {
        const key = c.jenjang || 'Lainnya';
        if (!groups[key]) groups[key] = [];
        groups[key].push(c);
    });
    return groups;
}

const grouped = groupByJenjang(props.classrooms);
</script>

<template>
    <Head title="Daftar Kelas - Pembelajaran" />

    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop font-sans">
        <div class="max-w-7xl mx-auto w-full">
            <!-- Page Header -->
            <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl text-primary font-bold">
                        Daftar Kelas
                    </h2>
                    <p class="font-body-md text-body-md text-on-surface-variant mt-1">
                        Kelola seluruh kelas yang tersedia di setiap jenjang pendidikan
                    </p>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="classrooms.length === 0" class="bg-white rounded-2xl border border-outline-variant p-12 text-center">
                <span class="material-symbols-outlined text-5xl text-outline-variant mb-4 block">school</span>
                <p class="font-body-lg text-body-lg text-on-surface-variant">Belum ada data kelas.</p>
                <p class="font-body-sm text-body-sm text-outline mt-1">Tambahkan Tahun Ajaran dan sinkronkan kelas melalui menu Pengaturan.</p>
            </div>

            <!-- Grouped by Jenjang -->
            <div v-else class="space-y-8">
                <div v-for="(classes, jenjang) in grouped" :key="jenjang">
                    <!-- Jenjang Header -->
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-primary-container flex items-center justify-center">
                            <span class="material-symbols-outlined text-xl text-on-primary-container">school</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-on-surface">{{ jenjang }}</h3>
                            <p class="text-xs text-on-surface-variant">{{ classes.length }} kelas</p>
                        </div>
                    </div>

                    <!-- Class Cards Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 sm:gap-4">
                        <div
                            v-for="kelas in classes"
                            :key="kelas.id"
                            class="group relative bg-white rounded-xl border border-outline-variant/60 overflow-hidden transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 cursor-default"
                        >
                            <!-- Top accent -->
                            <div class="h-1 bg-primary"></div>

                            <div class="p-4 flex flex-col items-center text-center">
                                <!-- Icon -->
                                <div class="w-11 h-11 rounded-full bg-primary/10 flex items-center justify-center mb-3">
                                    <span class="material-symbols-outlined text-primary text-xl">meeting_room</span>
                                </div>

                                <!-- Name -->
                                <h4 class="text-sm font-bold text-on-surface mb-1 leading-tight">{{ kelas.name }}</h4>

                                <!-- Details -->
                                <div class="space-y-1">
                                    <span v-if="kelas.unit" class="inline-block text-[10px] font-medium text-on-surface-variant bg-surface-container px-2 py-0.5 rounded-full">
                                        {{ kelas.unit }}
                                    </span>
                                    <span v-if="kelas.tingkat" class="inline-block text-[10px] font-semibold text-primary bg-primary/10 px-2 py-0.5 rounded-full">
                                        Tingkat {{ kelas.tingkat }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
