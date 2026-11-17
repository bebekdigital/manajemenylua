<script setup>
import { computed } from 'vue';
import { Bubble } from 'vue-chartjs';
import {
    Chart as ChartJS,
    LinearScale,
    PointElement,
    Tooltip,
    Legend,
} from 'chart.js';
import { PSB_TIERS } from '@/Utils/psbMatcher';

ChartJS.register(LinearScale, PointElement, Tooltip, Legend);

const props = defineProps({
    schools: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['select-tier', 'select-schools']);

const zeroCount = computed(() =>
    props.schools.filter((s) => s.tier === 'belum_tergarap').length
);

/**
 * Kelompokkan sekolah berdasarkan titik (X = taCount, Y = totalStudents, tier)
 * sehingga sekolah pada koordinat kembar tidak saling menumpuk, melainkan tampil sebagai bubble agregat.
 */
const groupedCoordinates = computed(() => {
    const groups = {
        basis_utama: new Map(),
        potensial: new Map(),
        vakum: new Map(),
    };

    props.schools.forEach((school) => {
        if (school.tier === 'belum_tergarap') return;
        const map = groups[school.tier];
        if (!map) return;

        const key = `${school.taCount}_${school.totalStudents}`;
        if (!map.has(key)) {
            map.set(key, {
                x: school.taCount,
                y: school.totalStudents,
                tier: school.tier,
                schools: [],
            });
        }
        map.get(key).schools.push(school);
    });

    const toPoints = (map) =>
        Array.from(map.values()).map((item) => ({
            x: item.x,
            y: item.y,
            r: Math.min(7 + Math.sqrt(item.schools.length) * 4, 22),
            tier: item.tier,
            count: item.schools.length,
            schools: item.schools,
        }));

    return {
        basis_utama: toPoints(groups.basis_utama),
        potensial: toPoints(groups.potensial),
        vakum: toPoints(groups.vakum),
    };
});

const maxAxes = computed(() => {
    let maxX = 4;
    let maxY = 8;
    props.schools.forEach((s) => {
        if (s.taCount > maxX) maxX = s.taCount;
        if (s.totalStudents > maxY) maxY = s.totalStudents;
    });
    return {
        xMax: maxX + 1,
        yMax: maxY + Math.max(2, Math.ceil(maxY * 0.15)),
    };
});

const chartData = computed(() => ({
    datasets: [
        {
            label: `Basis Utama`,
            data: groupedCoordinates.value.basis_utama,
            backgroundColor: PSB_TIERS.basis_utama.fillRgba,
            borderColor: PSB_TIERS.basis_utama.strokeHex,
            borderWidth: 2,
            hoverBorderWidth: 3,
        },
        {
            label: `Potensial`,
            data: groupedCoordinates.value.potensial,
            backgroundColor: PSB_TIERS.potensial.fillRgba,
            borderColor: PSB_TIERS.potensial.strokeHex,
            borderWidth: 2,
            hoverBorderWidth: 3,
        },
        {
            label: `Vakum`,
            data: groupedCoordinates.value.vakum,
            backgroundColor: PSB_TIERS.vakum.fillRgba,
            borderColor: PSB_TIERS.vakum.strokeHex,
            borderWidth: 2,
            hoverBorderWidth: 3,
        },
        {
            label: `Belum Tergarap (0,0)`,
            data: zeroCount.value > 0
                ? [
                      {
                          x: 0,
                          y: 0,
                          r: 17,
                          tier: 'belum_tergarap',
                          count: zeroCount.value,
                          schools: [],
                      },
                  ]
                : [],
            backgroundColor: 'rgba(100, 116, 139, 0.25)',
            borderColor: '#475569',
            borderWidth: 2,
            hoverBorderWidth: 3,
        },
    ],
}));

// Plugin untuk menggambar garis batas kuadran & label zona pada canvas
const quadrantPlugin = {
    id: 'psbQuadrantLines',
    beforeDatasetsDraw(chart) {
        const { ctx, chartArea, scales } = chart;
        if (!chartArea || !scales?.x || !scales?.y) return;

        const xSplit = scales.x.getPixelForValue(2.5);
        const ySplit = scales.y.getPixelForValue(4.5);

        ctx.save();
        ctx.beginPath();
        ctx.rect(chartArea.left, chartArea.top, chartArea.width, chartArea.height);
        ctx.clip();

        // Highlight halus zona Basis Utama (Kanan & Atas ambang batas)
        ctx.fillStyle = 'rgba(16, 185, 129, 0.04)';
        if (xSplit < chartArea.right) {
            ctx.fillRect(Math.max(xSplit, chartArea.left), chartArea.top, chartArea.right - Math.max(xSplit, chartArea.left), chartArea.bottom - chartArea.top);
        }
        if (ySplit > chartArea.top) {
            ctx.fillRect(chartArea.left, chartArea.top, Math.max(0, Math.min(xSplit, chartArea.right) - chartArea.left), Math.max(0, ySplit - chartArea.top));
        }

        // Garis putus-putus pembatas kuadran
        ctx.strokeStyle = 'rgba(148, 163, 184, 0.55)';
        ctx.lineWidth = 1.25;
        ctx.setLineDash([5, 5]);

        if (xSplit > chartArea.left && xSplit < chartArea.right) {
            ctx.beginPath();
            ctx.moveTo(xSplit, chartArea.top);
            ctx.lineTo(xSplit, chartArea.bottom);
            ctx.stroke();
        }

        if (ySplit > chartArea.top && ySplit < chartArea.bottom) {
            ctx.beginPath();
            ctx.moveTo(chartArea.left, ySplit);
            ctx.lineTo(chartArea.right, ySplit);
            ctx.stroke();
        }

        // Garis sumbu 0 yang lebih tegas
        const xZero = scales.x.getPixelForValue(0);
        const yZero = scales.y.getPixelForValue(0);
        ctx.setLineDash([]);
        ctx.strokeStyle = 'rgba(148, 163, 184, 0.35)';
        ctx.lineWidth = 1;
        if (xZero >= chartArea.left && xZero <= chartArea.right) {
            ctx.beginPath();
            ctx.moveTo(xZero, chartArea.top);
            ctx.lineTo(xZero, chartArea.bottom);
            ctx.stroke();
        }
        if (yZero >= chartArea.top && yZero <= chartArea.bottom) {
            ctx.beginPath();
            ctx.moveTo(chartArea.left, yZero);
            ctx.lineTo(chartArea.right, yZero);
            ctx.stroke();
        }

        // Label zona di sudut grafik
        ctx.font = '700 10px Inter, system-ui, sans-serif';
        ctx.fillStyle = 'rgba(4, 120, 87, 0.65)';
        ctx.textAlign = 'right';
        ctx.fillText('ZONA BASIS UTAMA (>= 5 Siswa / >= 3 TA)', chartArea.right - 10, chartArea.top + 16);

        ctx.restore();
    },
    afterDatasetsDraw(chart) {
        const { ctx, chartArea, scales } = chart;
        if (!chartArea || !scales?.x || !scales?.y) return;

        // Gambar label penanda di samping titik (0, 0)
        const zeroMeta = chart.getDatasetMeta(3);
        if (zeroMeta && zeroMeta.data && zeroMeta.data.length > 0) {
            const pt = zeroMeta.data[0];
            const raw = chart.data.datasets[3]?.data?.[0];
            if (pt && raw) {
                ctx.save();
                // Lingkaran luar putus-putus di titik (0,0)
                ctx.beginPath();
                ctx.arc(pt.x, pt.y, 21, 0, Math.PI * 2);
                ctx.setLineDash([4, 3]);
                ctx.strokeStyle = '#64748b';
                ctx.lineWidth = 1.5;
                ctx.stroke();

                ctx.setLineDash([]);
                ctx.font = '700 10.5px Inter, system-ui, sans-serif';
                ctx.fillStyle = '#334155';
                ctx.textAlign = 'left';
                ctx.textBaseline = 'middle';
                ctx.fillText(`${raw.count.toLocaleString('id-ID')} Sekolah (0 Siswa)`, pt.x + 26, pt.y - 6);
                ctx.font = '500 9.5px Inter, system-ui, sans-serif';
                ctx.fillStyle = '#64748b';
                ctx.fillText('Klik titik (0,0) untuk melihat daftar', pt.x + 26, pt.y + 7);
                ctx.restore();
            }
        }
    },
};

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    layout: {
        padding: { top: 14, right: 16, bottom: 6, left: 4 },
    },
    onHover: (event, elements) => {
        const canvas = event?.native?.target;
        if (canvas) {
            canvas.style.cursor = elements && elements.length > 0 ? 'pointer' : 'default';
        }
    },
    onClick: (_event, elements, chart) => {
        if (!elements || elements.length === 0) return;
        const { datasetIndex, index } = elements[0];
        const point = chart.data.datasets[datasetIndex]?.data?.[index];
        if (!point) return;

        if (point.tier === 'belum_tergarap') {
            emit('select-tier', 'belum_tergarap');
        } else if (point.schools && point.schools.length > 0) {
            emit('select-schools', {
                title: `${PSB_TIERS[point.tier]?.label || 'Sekolah'} — ${point.x} TA, ${point.y} Siswa`,
                schools: point.schools,
            });
        }
    },
    plugins: {
        legend: {
            display: false,
        },
        datalabels: {
            display: false,
        },
        tooltip: {
            backgroundColor: '#0f172a',
            titleColor: '#f8fafc',
            bodyColor: '#e2e8f0',
            padding: 12,
            cornerRadius: 12,
            displayColors: true,
            callbacks: {
                title(items) {
                    const raw = items[0]?.raw;
                    if (!raw) return '';
                    if (raw.tier === 'belum_tergarap') {
                        return `Titik (0, 0) — Belum Tergarap`;
                    }
                    const tierLabel = PSB_TIERS[raw.tier]?.label || '';
                    return `${tierLabel} (${raw.x} TA • ${raw.y} Siswa)`;
                },
                label(item) {
                    const raw = item.raw;
                    if (!raw) return '';
                    if (raw.tier === 'belum_tergarap') {
                        return [
                            `Total: ${raw.count.toLocaleString('id-ID')} sekolah berstatus 0 siswa`,
                            `Klik penanda ini untuk memfilter tabel ke Belum Tergarap (0)`,
                        ];
                    }
                    const lines = [`Jumlah: ${raw.count} sekolah pada posisi ini`];
                    const preview = (raw.schools || []).slice(0, 5);
                    preview.forEach((s) => {
                        lines.push(`• ${s.nama} (${s.kecamatan})`);
                    });
                    if (raw.count > 5) {
                        lines.push(`... dan ${raw.count - 5} sekolah lainnya (klik untuk lihat)`);
                    }
                    return lines;
                },
            },
        },
    },
    scales: {
        x: {
            min: -0.6,
            max: maxAxes.value.xMax,
            title: {
                display: true,
                text: 'Konsistensi (Jumlah Tahun Ajaran Menyumbang)',
                color: '#475569',
                font: { size: 11, weight: '700' },
            },
            grid: {
                color: 'rgba(226, 232, 240, 0.7)',
            },
            ticks: {
                stepSize: 1,
                color: '#64748b',
                font: { size: 11, weight: '600' },
                callback(val) {
                    if (val < 0 || !Number.isInteger(val)) return '';
                    return val === 0 ? '0 TA' : `${val} TA`;
                },
            },
        },
        y: {
            min: -1.1,
            max: maxAxes.value.yMax,
            title: {
                display: true,
                text: 'Total Siswa Masuk',
                color: '#475569',
                font: { size: 11, weight: '700' },
            },
            grid: {
                color: 'rgba(226, 232, 240, 0.7)',
            },
            ticks: {
                color: '#64748b',
                font: { size: 11, weight: '600' },
                callback(val) {
                    if (val < 0 || !Number.isInteger(val)) return '';
                    return `${val}`;
                },
            },
        },
    },
}));
</script>

<template>
    <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-6 shadow-xs flex flex-col">
        <!-- Header & Legend Tier -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
            <div class="flex items-start gap-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block mt-1.5 shrink-0"></span>
                <div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                        Matriks Potensi Sekolah (Konsistensi vs Total Siswa)
                    </h3>
                    <p class="text-[11.5px] text-slate-500 mt-0.5">
                        Titik <span class="font-bold text-slate-700">(0, 0)</span> merangkum sekolah Belum Tergarap; klik bubble mana pun untuk melihat daftar sekolahnya.
                    </p>
                </div>
            </div>

            <!-- Legend Klik Cepat -->
            <div class="flex items-center gap-1.5 flex-wrap">
                <button
                    v-for="tierKey in ['basis_utama', 'potensial', 'vakum', 'belum_tergarap']"
                    :key="tierKey"
                    type="button"
                    @click="emit('select-tier', tierKey)"
                    :class="[
                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border transition-all cursor-pointer hover:opacity-90 active:scale-95',
                        PSB_TIERS[tierKey].badgeClass,
                    ]"
                    :title="PSB_TIERS[tierKey].description"
                >
                    <span :class="['w-2 h-2 rounded-full', PSB_TIERS[tierKey].dotClass]"></span>
                    <span>{{ PSB_TIERS[tierKey].shortLabel }}</span>
                </button>
            </div>
        </div>

        <!-- Chart Canvas -->
        <div class="h-[310px] sm:h-[360px] w-full relative">
            <Bubble :data="chartData" :options="chartOptions" :plugins="[quadrantPlugin]" />
        </div>
    </div>
</template>

