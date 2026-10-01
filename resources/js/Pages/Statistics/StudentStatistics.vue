<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    studentsPerClass: {
        type: Array,
        default: () => [],
    },
    selectedAcademicYear: {
        type: Object,
        default: null,
    },
});

const unitTabs = [
    { id: 'SDIT', label: 'SDIT', icon: 'school' },
    { id: 'SMPIT', label: 'SMPIT', icon: 'domain' },
];

const activeUnit = ref('SDIT');

const filteredData = computed(() => {
    return props.studentsPerClass.filter(item => {
        if (!item.unit) return false;
        const u = item.unit.toUpperCase();
        if (activeUnit.value === 'SDIT') {
            return u.includes('SDIT') || u.includes('SD IT');
        }
        if (activeUnit.value === 'SMPIT') {
            return u.includes('SMPIT') || u.includes('SMP IT');
        }
        return false;
    });
});

const totalStudents = computed(() => {
    return filteredData.value.reduce((sum, item) => sum + item.total, 0);
});

const totalClasses = computed(() => filteredData.value.length);

const colorPalettes = {
    SDIT: [
        'rgba(16, 185, 129, 0.8)',
        'rgba(52, 211, 153, 0.8)',
        'rgba(110, 231, 183, 0.8)',
        'rgba(5, 150, 105, 0.8)',
        'rgba(4, 120, 87, 0.8)',
        'rgba(6, 95, 70, 0.8)',
        'rgba(20, 184, 166, 0.8)',
        'rgba(13, 148, 136, 0.8)',
        'rgba(45, 212, 191, 0.8)',
        'rgba(94, 234, 212, 0.8)',
        'rgba(99, 102, 241, 0.8)',
        'rgba(129, 140, 248, 0.8)',
    ],
    SMPIT: [
        'rgba(59, 130, 246, 0.8)',
        'rgba(96, 165, 250, 0.8)',
        'rgba(147, 197, 253, 0.8)',
        'rgba(37, 99, 235, 0.8)',
        'rgba(29, 78, 216, 0.8)',
        'rgba(30, 64, 175, 0.8)',
        'rgba(99, 102, 241, 0.8)',
        'rgba(129, 140, 248, 0.8)',
        'rgba(139, 92, 246, 0.8)',
        'rgba(167, 139, 250, 0.8)',
        'rgba(14, 165, 233, 0.8)',
        'rgba(56, 189, 248, 0.8)',
    ],
};

const chartData = computed(() => {
    const data = filteredData.value;
    const palette = colorPalettes[activeUnit.value] || colorPalettes.SDIT;

    return {
        labels: data.map(item => item.classroom_name),
        datasets: [{
            label: 'Jumlah Siswa',
            data: data.map(item => item.total),
            backgroundColor: data.map((_, i) => palette[i % palette.length]),
            borderColor: data.map((_, i) => palette[i % palette.length].replace('0.8', '1')),
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
        }],
    };
});

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        title: {
            display: false,
        },
        tooltip: {
            backgroundColor: 'rgba(15, 23, 42, 0.9)',
            titleFont: { size: 13, weight: 'bold', family: "'Inter', sans-serif" },
            bodyFont: { size: 12, family: "'Inter', sans-serif" },
            padding: 12,
            cornerRadius: 10,
            displayColors: true,
            boxPadding: 4,
            callbacks: {
                title: (items) => `Kelas ${items[0].label}`,
                label: (item) => ` ${item.raw} Siswa`,
            },
        },
    },
    scales: {
        x: {
            grid: {
                display: false,
            },
            ticks: {
                font: { size: 12, weight: '600', family: "'Inter', sans-serif" },
                color: '#64748b',
            },
            border: {
                display: false,
            },
        },
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(226, 232, 240, 0.6)',
                drawBorder: false,
            },
            ticks: {
                font: { size: 11, family: "'Inter', sans-serif" },
                color: '#94a3b8',
                stepSize: 5,
                padding: 8,
            },
            border: {
                display: false,
                dash: [4, 4],
            },
        },
    },
    animation: {
        duration: 800,
        easing: 'easeOutQuart',
    },
}));

const chartKey = ref(0);
watch(activeUnit, () => {
    chartKey.value++;
});
</script>

<template>
    <Head title="Statistik Siswa - Foundation Data Center" />

    <div class="min-h-screen bg-surface">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

            <!-- Page Header -->
            <div class="mb-6 sm:mb-8">
                <div class="flex items-center gap-3 mb-1">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-md">
                        <span class="material-symbols-outlined text-white text-[22px] filled-icon">bar_chart</span>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-on-surface leading-tight">Statistik Siswa</h1>
                        <p class="text-xs sm:text-sm text-on-surface-variant mt-0.5">
                            Ringkasan data siswa
                            <span v-if="selectedAcademicYear" class="font-semibold text-primary">
                                — TA {{ selectedAcademicYear.name }} ({{ selectedAcademicYear.semester }})
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Unit Tabs -->
            <div class="mb-6 flex gap-2">
                <button
                    v-for="tab in unitTabs"
                    :key="tab.id"
                    @click="activeUnit = tab.id"
                    :class="[
                        'flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 cursor-pointer',
                        activeUnit === tab.id
                            ? 'bg-emerald-600 text-white shadow-md shadow-emerald-200'
                            : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'
                    ]"
                >
                    <span class="material-symbols-outlined text-[18px]" :class="{ 'filled-icon': activeUnit === tab.id }">{{ tab.icon }}</span>
                    {{ tab.label }}
                </button>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 mb-6">
                <div class="bg-white rounded-2xl border border-slate-100 p-4 sm:p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                            <span class="material-symbols-outlined text-emerald-600 text-[22px] filled-icon">groups</span>
                        </div>
                        <div>
                            <p class="text-[11px] sm:text-xs text-slate-500 font-medium uppercase tracking-wide">Total Siswa Aktif</p>
                            <p class="text-xl sm:text-2xl font-bold text-slate-800">{{ totalStudents }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-100 p-4 sm:p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                            <span class="material-symbols-outlined text-blue-600 text-[22px] filled-icon">meeting_room</span>
                        </div>
                        <div>
                            <p class="text-[11px] sm:text-xs text-slate-500 font-medium uppercase tracking-wide">Jumlah Kelas</p>
                            <p class="text-xl sm:text-2xl font-bold text-slate-800">{{ totalClasses }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-100 p-4 sm:p-5 shadow-sm col-span-2 sm:col-span-1">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                            <span class="material-symbols-outlined text-amber-600 text-[22px] filled-icon">calculate</span>
                        </div>
                        <div>
                            <p class="text-[11px] sm:text-xs text-slate-500 font-medium uppercase tracking-wide">Rata-rata / Kelas</p>
                            <p class="text-xl sm:text-2xl font-bold text-slate-800">{{ totalClasses > 0 ? Math.round(totalStudents / totalClasses) : 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart Card -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-emerald-600 text-[22px] filled-icon">bar_chart</span>
                            <h2 class="text-base sm:text-lg font-bold text-slate-800">Jumlah Siswa per Kelas</h2>
                        </div>
                        <span class="text-xs text-slate-400 font-medium bg-slate-50 px-3 py-1 rounded-full">{{ activeUnit }}</span>
                    </div>
                </div>

                <div class="p-4 sm:p-6">
                    <div v-if="filteredData.length > 0" class="h-[350px] sm:h-[420px]">
                        <Bar :key="chartKey" :data="chartData" :options="chartOptions" />
                    </div>

                    <!-- Empty State -->
                    <div v-else class="flex flex-col items-center justify-center py-16 text-center">
                        <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center mb-4">
                            <span class="material-symbols-outlined text-slate-300 text-[32px]">bar_chart</span>
                        </div>
                        <p class="text-sm font-semibold text-slate-500">Belum ada data kelas</p>
                        <p class="text-xs text-slate-400 mt-1">Data akan muncul setelah siswa diimpor ke unit {{ activeUnit }}</p>
                    </div>
                </div>

                <!-- Detail Table -->
                <div v-if="filteredData.length > 0" class="border-t border-slate-100">
                    <div class="px-5 sm:px-6 py-3 bg-slate-50/50">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Detail per Kelas</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-slate-100">
                                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 sm:px-6 py-3">No</th>
                                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 sm:px-6 py-3">Kelas</th>
                                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 sm:px-6 py-3">Jenjang</th>
                                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 sm:px-6 py-3">Tingkat</th>
                                    <th class="text-right text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 sm:px-6 py-3">Jumlah Siswa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, i) in filteredData" :key="i" class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                    <td class="px-5 sm:px-6 py-3 text-sm text-slate-500">{{ i + 1 }}</td>
                                    <td class="px-5 sm:px-6 py-3 text-sm font-semibold text-slate-800">{{ item.classroom_name }}</td>
                                    <td class="px-5 sm:px-6 py-3 text-sm text-slate-600">{{ item.jenjang }}</td>
                                    <td class="px-5 sm:px-6 py-3 text-sm text-slate-600">{{ item.grade }}</td>
                                    <td class="px-5 sm:px-6 py-3 text-sm font-bold text-emerald-700 text-right">{{ item.total }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-emerald-50/50 border-t-2 border-emerald-100">
                                    <td colspan="4" class="px-5 sm:px-6 py-3 text-sm font-bold text-emerald-800 uppercase">Total</td>
                                    <td class="px-5 sm:px-6 py-3 text-sm font-bold text-emerald-800 text-right">{{ totalStudents }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
.filled-icon {
    font-variation-settings: 'FILL' 1;
}
</style>
