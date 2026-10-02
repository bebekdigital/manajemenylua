import sys

with open('resources/js/Pages/Statistics/StudentStatistics.vue', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update chart data colors
old_chart_data = r'''const colorPalettes = {
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
});'''

new_chart_data = r'''const chartData = computed(() => {
    const data = filteredData.value;

    return {
        labels: data.map(item => item.classroom_name),
        datasets: [{
            label: 'Jumlah Siswa',
            data: data.map(item => item.total),
            backgroundColor: 'rgba(4, 120, 87, 0.85)', // emerald-700 with opacity
            borderColor: '#047857', // emerald-700
            borderWidth: 2,
            borderRadius: 6,
            borderSkipped: false,
            hoverBackgroundColor: '#064e3b', // emerald-900
        }],
    };
});'''

content = content.replace(old_chart_data, new_chart_data)

# 2. Fix Unit Tabs Style
old_tabs_style = r'''                    :class="[
                        'flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 cursor-pointer',
                        activeUnit === tab.id
                            ? 'bg-emerald-600 text-white shadow-md shadow-emerald-200'
                            : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'
                    ]"'''
new_tabs_style = r'''                    :class="[
                        'flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-medium transition-all duration-200 cursor-pointer',
                        activeUnit === tab.id
                            ? 'bg-emerald-700 text-white'
                            : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'
                    ]"'''
content = content.replace(old_tabs_style, new_tabs_style)

# 3. Fix Summary Cards
old_cards = r'''            <!-- Summary Cards -->
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
            </div>'''

new_cards = r'''            <!-- Summary Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 mb-6">
                <div class="bg-emerald-50 rounded-2xl border border-emerald-100/50 p-4 sm:p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                            <span class="material-symbols-outlined text-emerald-700 text-[22px] filled-icon">groups</span>
                        </div>
                        <div>
                            <p class="text-[11px] sm:text-xs text-emerald-700/80 font-semibold uppercase tracking-wide">Total Siswa Aktif</p>
                            <p class="text-xl sm:text-2xl font-bold text-emerald-900">{{ totalStudents }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-blue-50 rounded-2xl border border-blue-100/50 p-4 sm:p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                            <span class="material-symbols-outlined text-blue-700 text-[22px] filled-icon">meeting_room</span>
                        </div>
                        <div>
                            <p class="text-[11px] sm:text-xs text-blue-700/80 font-semibold uppercase tracking-wide">Jumlah Kelas</p>
                            <p class="text-xl sm:text-2xl font-bold text-blue-900">{{ totalClasses }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-amber-50 rounded-2xl border border-amber-100/50 p-4 sm:p-5 col-span-2 sm:col-span-1">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                            <span class="material-symbols-outlined text-amber-700 text-[22px] filled-icon">calculate</span>
                        </div>
                        <div>
                            <p class="text-[11px] sm:text-xs text-amber-700/80 font-semibold uppercase tracking-wide">Rata-rata / Kelas</p>
                            <p class="text-xl sm:text-2xl font-bold text-amber-900">{{ totalClasses > 0 ? Math.round(totalStudents / totalClasses) : 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>'''
content = content.replace(old_cards, new_cards)

# 4. Wrap Chart in grid
old_chart = r'''            <!-- Chart Card -->
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
            </div>'''

new_chart = r'''            <!-- Charts Section (Grid Layout) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                
                <!-- Chart 1: Jumlah Siswa per Kelas -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden flex flex-col h-full">
                    <div class="px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-100 shrink-0">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-emerald-700 text-[22px] filled-icon">bar_chart</span>
                                <h2 class="text-base sm:text-lg font-bold text-slate-800">Jumlah Siswa per Kelas</h2>
                            </div>
                            <span class="text-xs text-slate-500 font-medium bg-slate-100 px-3 py-1 rounded-full">{{ activeUnit }}</span>
                        </div>
                    </div>

                    <div class="p-4 sm:p-6 shrink-0">
                        <div v-if="filteredData.length > 0" class="h-[280px]">
                            <Bar :key="chartKey" :data="chartData" :options="chartOptions" />
                        </div>

                        <!-- Empty State -->
                        <div v-else class="flex flex-col items-center justify-center h-[280px] text-center">
                            <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center mb-4">
                                <span class="material-symbols-outlined text-slate-300 text-[32px]">bar_chart</span>
                            </div>
                            <p class="text-sm font-semibold text-slate-500">Belum ada data kelas</p>
                            <p class="text-xs text-slate-400 mt-1">Data akan muncul setelah siswa diimpor ke unit {{ activeUnit }}</p>
                        </div>
                    </div>

                    <!-- Detail Table -->
                    <div v-if="filteredData.length > 0" class="border-t border-slate-100 flex-grow">
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
                                        <th class="text-right text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 sm:px-6 py-3">Siswa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, i) in filteredData" :key="i" class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                        <td class="px-5 sm:px-6 py-3 text-sm text-slate-500">{{ i + 1 }}</td>
                                        <td class="px-5 sm:px-6 py-3 text-sm font-semibold text-slate-800">{{ item.classroom_name }}</td>
                                        <td class="px-5 sm:px-6 py-3 text-sm text-slate-600">{{ item.jenjang }}</td>
                                        <td class="px-5 sm:px-6 py-3 text-sm font-bold text-emerald-700 text-right">{{ item.total }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-emerald-50/50 border-t-2 border-emerald-100">
                                        <td colspan="3" class="px-5 sm:px-6 py-3 text-sm font-bold text-emerald-800 uppercase">Total</td>
                                        <td class="px-5 sm:px-6 py-3 text-sm font-bold text-emerald-800 text-right">{{ totalStudents }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Placeholder for future charts -->
                <!-- <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden flex flex-col h-full"> ... </div> -->
            </div>'''
content = content.replace(old_chart, new_chart)

with open('resources/js/Pages/Statistics/StudentStatistics.vue', 'w', encoding='utf-8') as f:
    f.write(content)

print('Updated successfully.')
