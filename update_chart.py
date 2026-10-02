import sys

with open('resources/js/Pages/Statistics/StudentStatistics.vue', 'r', encoding='utf-8') as f:
    content = f.read()

# Add ChartDataLabels import
if "import ChartDataLabels from 'chartjs-plugin-datalabels';" not in content:
    content = content.replace(
        "import { CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js';",
        "import { CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js';\nimport ChartDataLabels from 'chartjs-plugin-datalabels';"
    )
    content = content.replace(
        "ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);",
        "ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend, ChartDataLabels);"
    )

# ChartData update
old_chart_data = r'''const chartData = computed(() => {
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

new_chart_data = r'''const chartData = computed(() => {
    const data = filteredData.value;

    return {
        labels: data.map(item => item.classroom_name),
        datasets: [{
            label: 'Jumlah Siswa',
            data: data.map(item => item.total),
            backgroundColor: '#047857', // solid emerald-700 without opacity/shadow
            borderWidth: 0,
            borderRadius: { topLeft: 6, topRight: 6, bottomLeft: 0, bottomRight: 0 },
            borderSkipped: 'bottom',
            hoverBackgroundColor: '#064e3b', // emerald-900
        }],
    };
});'''

content = content.replace(old_chart_data, new_chart_data)

# ChartOptions update
old_chart_options = r'''const chartOptions = computed(() => ({
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
}));'''

new_chart_options = r'''const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    layout: {
        padding: {
            top: 20 // Make room for datalabels at the top
        }
    },
    plugins: {
        legend: { display: false },
        title: { display: false },
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
        datalabels: {
            anchor: 'end',
            align: 'top',
            color: '#047857', // match the bar color
            font: { weight: 'bold', size: 12, family: "'Inter', sans-serif" },
            offset: 4,
            formatter: (value) => value
        }
    },
    scales: {
        x: {
            title: {
                display: true,
                text: 'Nama Kelas',
                color: '#64748b',
                font: { size: 12, weight: '500', family: "'Inter', sans-serif" }
            },
            grid: { display: false },
            ticks: {
                font: { size: 12, weight: '600', family: "'Inter', sans-serif" },
                color: '#64748b',
            },
            border: { display: false },
        },
        y: {
            title: {
                display: true,
                text: 'Jumlah Siswa',
                color: '#64748b',
                font: { size: 12, weight: '500', family: "'Inter', sans-serif" }
            },
            beginAtZero: true,
            grid: {
                color: 'rgba(226, 232, 240, 1)', // solid horizontal lines
                drawBorder: false,
            },
            ticks: {
                font: { size: 11, family: "'Inter', sans-serif" },
                color: '#94a3b8',
                stepSize: 5,
                padding: 8,
            },
            border: { display: false, dash: [4, 4] },
        },
    },
    animation: {
        duration: 800,
        easing: 'easeOutQuart',
    },
}));'''

content = content.replace(old_chart_options, new_chart_options)

# Reduce chart height
content = content.replace('<div v-if="filteredData.length > 0" class="h-[280px]">', '<div v-if="filteredData.length > 0" class="h-[230px]">')
content = content.replace('h-[280px]', 'h-[230px]')

with open('resources/js/Pages/Statistics/StudentStatistics.vue', 'w', encoding='utf-8') as f:
    f.write(content)

print('Updated StudentStatistics.vue')
