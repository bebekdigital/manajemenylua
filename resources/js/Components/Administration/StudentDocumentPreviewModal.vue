<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import StudentDocumentSheet from './StudentDocumentSheet.vue';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    student: {
        type: Object,
        default: null,
    },
    students: {
        type: Array,
        default: () => [],
    },
    template: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close', 'print']);

const activePageFilter = ref('all'); // 'all', 'cover', 'school_profile', 'identity'
const zoomLevel = ref(85); // percentage

const pagesToPrint = computed(() => {
    if (activePageFilter.value === 'all') {
        return ['cover', 'school_profile', 'identity'];
    }
    return [activePageFilter.value];
});

const handleKeyDown = (e) => {
    if (!props.isOpen) return;
    if (e.key === 'Escape') {
        emit('close');
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
});

const triggerPrint = () => {
    emit('print', {
        student: props.student,
        pages: pagesToPrint.value,
    });
};
</script>

<template>
    <div 
        v-if="isOpen && student"
        class="fixed inset-0 z-50 overflow-y-auto bg-black/70 backdrop-blur-xs flex items-center justify-center p-2 sm:p-4"
    >
        <!-- Modal Container -->
        <div class="bg-surface rounded-2xl w-full max-w-5xl h-[92vh] flex flex-col shadow-2xl overflow-hidden border border-outline-variant">
            <!-- Modal Header Toolbar -->
            <div class="px-6 py-4 bg-surface-container-low border-b border-outline-variant flex flex-wrap items-center justify-between gap-4 shrink-0">
                <!-- Title & Student Info -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-primary-container text-on-primary-container flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">print</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-on-surface leading-tight">
                            Preview Berkas: {{ student.nama }}
                        </h3>
                        <p class="text-xs text-on-surface-variant">
                            NISN: <span class="font-mono font-medium">{{ student.nisn }}</span> | Kelas: <span class="font-semibold">{{ student.kelas }}</span>
                        </p>
                    </div>
                </div>

                <!-- Page Filter Segmented Buttons -->
                <div class="flex items-center bg-surface-container-high rounded-xl p-1 text-xs font-medium text-on-surface-variant">
                    <button 
                        @click="activePageFilter = 'all'"
                        :class="[
                            'px-3 py-1.5 rounded-lg transition-all',
                            activePageFilter === 'all' ? 'bg-surface shadow-xs text-primary font-bold' : 'hover:text-on-surface'
                        ]"
                    >
                        Semua Halaman (3)
                    </button>
                    <button 
                        @click="activePageFilter = 'cover'"
                        :class="[
                            'px-3 py-1.5 rounded-lg transition-all',
                            activePageFilter === 'cover' ? 'bg-surface shadow-xs text-primary font-bold' : 'hover:text-on-surface'
                        ]"
                    >
                        1. Cover
                    </button>
                    <button 
                        @click="activePageFilter = 'school_profile'"
                        :class="[
                            'px-3 py-1.5 rounded-lg transition-all',
                            activePageFilter === 'school_profile' ? 'bg-surface shadow-xs text-primary font-bold' : 'hover:text-on-surface'
                        ]"
                    >
                        2. Profil Sekolah
                    </button>
                    <button 
                        @click="activePageFilter = 'identity'"
                        :class="[
                            'px-3 py-1.5 rounded-lg transition-all',
                            activePageFilter === 'identity' ? 'bg-surface shadow-xs text-primary font-bold' : 'hover:text-on-surface'
                        ]"
                    >
                        3. Identitas Siswa
                    </button>
                </div>

                <!-- Right Action Buttons -->
                <div class="flex items-center space-x-2">
                    <!-- Zoom Controls -->
                    <div class="hidden sm:flex items-center space-x-1 bg-surface-container-high rounded-lg px-2 py-1 text-xs text-on-surface-variant">
                        <button @click="zoomLevel = Math.max(50, zoomLevel - 10)" class="hover:text-primary p-1 cursor-pointer" title="Zoom Out">
                            <span class="material-symbols-outlined text-sm">remove</span>
                        </button>
                        <span class="w-10 text-center font-mono font-medium">{{ zoomLevel }}%</span>
                        <button @click="zoomLevel = Math.min(130, zoomLevel + 10)" class="hover:text-primary p-1 cursor-pointer" title="Zoom In">
                            <span class="material-symbols-outlined text-sm">add</span>
                        </button>
                    </div>

                    <!-- Print Button -->
                    <button 
                        @click="triggerPrint"
                        class="bg-primary hover:bg-primary-container text-on-primary hover:text-on-primary-container px-4 py-2 rounded-xl text-sm font-semibold flex items-center space-x-2 transition-all shadow-sm active:scale-98 cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-base">print</span>
                        <span>Cetak Dokumen</span>
                    </button>

                    <!-- Close Button -->
                    <button 
                        @click="$emit('close')"
                        class="p-2 text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high rounded-xl transition-colors cursor-pointer"
                        title="Tutup (Esc)"
                    >
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
            </div>

            <!-- Modal Content Preview Scroll Area -->
            <div class="flex-grow overflow-y-auto bg-slate-200/80 dark:bg-slate-900/80 p-6 flex justify-center">
                <div 
                    class="transition-transform duration-200 origin-top"
                    :style="{ transform: `scale(${zoomLevel / 100})`, transformOrigin: 'top center' }"
                >
                    <StudentDocumentSheet 
                        :student="student"
                        :template="template"
                        :pages-to-print="pagesToPrint"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
