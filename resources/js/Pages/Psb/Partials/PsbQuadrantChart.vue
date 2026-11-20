<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Bubble } from 'vue-chartjs';
import {
    Chart as ChartJS,
    LinearScale,
    PointElement,
    Tooltip,
    Legend,
} from 'chart.js';
import { PSB_TIERS, PSB_WELFARE_TIERS } from '@/Utils/psbMatcher';

ChartJS.register(LinearScale, PointElement, Tooltip, Legend);

const props = defineProps({
    mode: {
        type: String,
        default: 'school', // 'school' | 'welfare'
    },
    schools: {
        type: Array,
        default: () => [],
    },
    villages: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:mode', 'select-tier', 'select-schools', 'select-villages']);

const chartWrapperRef = ref(null);
const bubbleChartRef = ref(null);
const zoomWindow = ref(null); // null = semua area, atau { xMin, xMax, yMin, yMax }
const zoomPreset = ref('all'); // 'all' | 'dense' | 'custom'

// State pan / drag & pinch-to-zoom kanvas
const isDragging = ref(false);
const isPinching = ref(false);
let dragStartPx = null;
let dragStartWindow = null;
let pinchStartDist = 0;
let pinchStartMidPx = null;
let pinchStartDataMid = null;
let pinchStartWindow = null;
let suppressNextClick = false;

const isWelfareMode = computed(() => props.mode === 'welfare');

const zeroCount = computed(() =>
    isWelfareMode.value
        ? 0
        : props.schools.filter((s) => s.tier === 'belum_tergarap').length
);

// Domain penuh grafik
const fullBounds = computed(() => {
    if (isWelfareMode.value) {
        let maxY = 8;
        props.villages.forEach((v) => {
            if (v.totalStudents > maxY) maxY = v.totalStudents;
        });
        const yMax = maxY + Math.max(2, Math.ceil(maxY * 0.15));
        return {
            xMin: -4,
            xMax: 105,
            yMin: -Math.max(1.2, yMax * 0.08),
            yMax,
        };
    }

    let maxX = 4;
    let maxY = 8;
    props.schools.forEach((s) => {
        if (s.taCount > maxX) maxX = s.taCount;
        if (s.totalStudents > maxY) maxY = s.totalStudents;
    });
    const xMax = maxX + 1;
    const yMax = maxY + Math.max(2, Math.ceil(maxY * 0.15));
    return {
        xMin: -0.75,
        xMax,
        yMin: -Math.max(1.2, yMax * 0.08),
        yMax,
    };
});

// Window sumbu aktif (full atau zoom/pan)
const activeBounds = computed(() => zoomWindow.value || fullBounds.value);

const isZoomed = computed(() => zoomWindow.value !== null);

/**
 * Kelompokkan sekolah berdasarkan titik integer (origX = taCount, origY = totalStudents),
 * lalu jika dalam 1 titik terdapat lebih dari 1 tier (misal Potensial & Vakum di 1 TA, 1 Siswa),
 * geser posisi masing-masing lingkaran secara simetris ke sisi berbeda agar terpisah dan mudah diklik.
 */
const groupedCoordinates = computed(() => {
    const pointMap = new Map();

    props.schools.forEach((school) => {
        if (school.tier === 'belum_tergarap') return;
        const coordKey = `${school.taCount}_${school.totalStudents}`;
        if (!pointMap.has(coordKey)) {
            pointMap.set(coordKey, {
                origX: school.taCount,
                origY: school.totalStudents,
                tiers: new Map(),
            });
        }
        const entry = pointMap.get(coordKey);
        if (!entry.tiers.has(school.tier)) {
            entry.tiers.set(school.tier, []);
        }
        entry.tiers.get(school.tier).push(school);
    });

    const xSpan = Math.max(2, activeBounds.value.xMax - activeBounds.value.xMin);
    const ySpan = Math.max(3, activeBounds.value.yMax - activeBounds.value.yMin);

    // Offset proporsional terhadap rentang zoom aktif sehingga jarak antar sisi selalu jelas terbaca
    const dx = Math.min(0.22, Math.max(0.09, xSpan * 0.032));
    const dy = Math.min(Math.max(0.28, ySpan * 0.038), 1.4);

    const out = {
        basis_utama: [],
        potensial: [],
        vakum: [],
    };

    const tierOrder = ['basis_utama', 'potensial', 'vakum'];

    pointMap.forEach(({ origX, origY, tiers }) => {
        const presentTiers = tierOrder.filter((t) => tiers.has(t));
        const n = presentTiers.length;

        presentTiers.forEach((tierKey, idx) => {
            const list = tiers.get(tierKey);
            let offsetX = 0;
            let offsetY = 0;

            if (n === 2) {
                // Pisahkan ke sisi kiri-atas vs kanan-bawah
                offsetX = idx === 0 ? -dx : dx;
                offsetY = idx === 0 ? dy * 0.65 : -dy * 0.65;
            } else if (n === 3) {
                // Formasi segitiga mengelilingi titik asli (origX, origY)
                const angles = [Math.PI / 2, (7 * Math.PI) / 6, (11 * Math.PI) / 6];
                offsetX = Math.cos(angles[idx]) * dx * 1.15;
                offsetY = Math.sin(angles[idx]) * dy * 1.05;
            }

            out[tierKey].push({
                x: Number((origX + offsetX).toFixed(3)),
                y: Number((origY + offsetY).toFixed(3)),
                origX,
                origY,
                hasOffset: n > 1,
                r: Math.min(7 + Math.sqrt(list.length) * 3.8, 21),
                tier: tierKey,
                count: list.length,
                schools: list,
            });
        });
    });

    return out;
});

const groupedWelfareCoordinates = computed(() => {
    const pointMap = new Map();

    props.villages.forEach((v) => {
        const bx = Math.round(Number(v.welfare_score || 0) / 2) * 2;
        const by = v.totalStudents;
        const coordKey = `${bx}_${by}`;
        if (!pointMap.has(coordKey)) {
            pointMap.set(coordKey, {
                origX: bx,
                origY: by,
                tiers: new Map(),
            });
        }
        const entry = pointMap.get(coordKey);
        if (!entry.tiers.has(v.tier)) {
            entry.tiers.set(v.tier, []);
        }
        entry.tiers.get(v.tier).push(v);
    });

    const out = {
        sejahtera_basis: [],
        sejahtera_potensial: [],
        rentan_terjangkau: [],
        rentan_minim: [],
    };

    pointMap.forEach(({ origX, origY, tiers }) => {
        tiers.forEach((list, tierKey) => {
            if (!out[tierKey]) return;
            const totalStudentsSum = list.reduce((acc, v) => acc + v.totalStudents, 0);
            const avgTotalP14 = Math.round(list.reduce((acc, v) => acc + (v.total || 0), 0) / list.length);
            out[tierKey].push({
                x: origX,
                y: origY,
                origX,
                origY,
                hasOffset: false,
                r: Math.min(5 + Math.sqrt(list.length) * 2.3, 17),
                tier: tierKey,
                count: list.length,
                totalStudentsSum,
                avgTotalP14,
                villages: [...list].sort((a, b) => b.totalStudents - a.totalStudents || b.welfare_score - a.welfare_score),
            });
        });
    });

    return out;
});

const chartData = computed(() => {
    if (isWelfareMode.value) {
        return {
            datasets: [
                {
                    label: PSB_WELFARE_TIERS.sejahtera_basis.label,
                    data: groupedWelfareCoordinates.value.sejahtera_basis,
                    backgroundColor: PSB_WELFARE_TIERS.sejahtera_basis.fillRgba,
                    borderColor: PSB_WELFARE_TIERS.sejahtera_basis.strokeHex,
                    borderWidth: 2,
                    hoverBorderWidth: 3,
                },
                {
                    label: PSB_WELFARE_TIERS.sejahtera_potensial.label,
                    data: groupedWelfareCoordinates.value.sejahtera_potensial,
                    backgroundColor: PSB_WELFARE_TIERS.sejahtera_potensial.fillRgba,
                    borderColor: PSB_WELFARE_TIERS.sejahtera_potensial.strokeHex,
                    borderWidth: 2,
                    hoverBorderWidth: 3,
                },
                {
                    label: PSB_WELFARE_TIERS.rentan_terjangkau.label,
                    data: groupedWelfareCoordinates.value.rentan_terjangkau,
                    backgroundColor: PSB_WELFARE_TIERS.rentan_terjangkau.fillRgba,
                    borderColor: PSB_WELFARE_TIERS.rentan_terjangkau.strokeHex,
                    borderWidth: 2,
                    hoverBorderWidth: 3,
                },
                {
                    label: PSB_WELFARE_TIERS.rentan_minim.label,
                    data: groupedWelfareCoordinates.value.rentan_minim,
                    backgroundColor: PSB_WELFARE_TIERS.rentan_minim.fillRgba,
                    borderColor: PSB_WELFARE_TIERS.rentan_minim.strokeHex,
                    borderWidth: 1.5,
                    hoverBorderWidth: 2.5,
                },
            ],
        };
    }

    return {
        datasets: [
            {
                label: 'Basis Utama',
                data: groupedCoordinates.value.basis_utama,
                backgroundColor: PSB_TIERS.basis_utama.fillRgba,
                borderColor: PSB_TIERS.basis_utama.strokeHex,
                borderWidth: 2,
                hoverBorderWidth: 3,
            },
            {
                label: 'Potensial',
                data: groupedCoordinates.value.potensial,
                backgroundColor: PSB_TIERS.potensial.fillRgba,
                borderColor: PSB_TIERS.potensial.strokeHex,
                borderWidth: 2,
                hoverBorderWidth: 3,
            },
            {
                label: 'Vakum',
                data: groupedCoordinates.value.vakum,
                backgroundColor: PSB_TIERS.vakum.fillRgba,
                borderColor: PSB_TIERS.vakum.strokeHex,
                borderWidth: 2,
                hoverBorderWidth: 3,
            },
            {
                label: 'Belum Tergarap (0,0)',
                data:
                    zeroCount.value > 0
                        ? [
                              {
                                  x: 0,
                                  y: 0,
                                  origX: 0,
                                  origY: 0,
                                  hasOffset: false,
                                  r: 15,
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
    };
});

// Plugin canvas: zona kuadran, garis penghubung ke titik asli untuk orbital offset, dan label (0,0) di atas agar tak tabrakan
const quadrantPlugin = {
    id: 'psbQuadrantLines',
    beforeDatasetsDraw(chart) {
        const { ctx, chartArea, scales } = chart;
        if (!chartArea || !scales?.x || !scales?.y) return;

        const welfareMode = isWelfareMode.value;
        const xSplitVal = welfareMode ? 50 : 2.5;
        const ySplitVal = welfareMode ? 2.5 : 4.5;

        const xSplit = scales.x.getPixelForValue(xSplitVal);
        const ySplit = scales.y.getPixelForValue(ySplitVal);

        ctx.save();
        ctx.beginPath();
        ctx.rect(chartArea.left, chartArea.top, chartArea.width, chartArea.height);
        ctx.clip();

        // Highlight halus zona utama / potensial
        ctx.fillStyle = 'rgba(16, 185, 129, 0.045)';
        if (welfareMode) {
            // Top-right: Basis Sejahtera (>= 50, >= 3 siswa)
            const xLeft = Math.max(xSplit, chartArea.left);
            const yBot = Math.min(ySplit, chartArea.bottom);
            if (xLeft < chartArea.right && yBot > chartArea.top) {
                ctx.fillRect(xLeft, chartArea.top, chartArea.right - xLeft, yBot - chartArea.top);
            }
            // Bottom-right: Potensial Sejahtera (>= 50, < 3 siswa)
            ctx.fillStyle = 'rgba(245, 158, 11, 0.05)';
            const yPotTop = Math.max(ySplit, chartArea.top);
            if (xLeft < chartArea.right && chartArea.bottom > yPotTop) {
                ctx.fillRect(xLeft, yPotTop, chartArea.right - xLeft, chartArea.bottom - yPotTop);
            }
        } else {
            if (xSplit < chartArea.right) {
                ctx.fillRect(
                    Math.max(xSplit, chartArea.left),
                    chartArea.top,
                    chartArea.right - Math.max(xSplit, chartArea.left),
                    chartArea.bottom - chartArea.top
                );
            }
            if (ySplit > chartArea.top) {
                const rightEdge = Math.min(xSplit, chartArea.right);
                if (rightEdge > chartArea.left) {
                    ctx.fillRect(
                        chartArea.left,
                        chartArea.top,
                        rightEdge - chartArea.left,
                        Math.min(ySplit, chartArea.bottom) - chartArea.top
                    );
                }
            }
        }

        // Garis putus-putus kuadran
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

        // Sumbu 0
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

        // Garis hubung titik yang di-offset ke pusat grid integer aslinya (agar jelas titik kembar milik X,Y yang sama)
        const drawnAnchors = new Set();
        [0, 1, 2].forEach((dsIdx) => {
            const meta = chart.getDatasetMeta(dsIdx);
            const rawList = chart.data.datasets[dsIdx]?.data || [];
            if (!meta?.data) return;

            meta.data.forEach((pt, idx) => {
                const raw = rawList[idx];
                if (!raw?.hasOffset) return;
                const ax = scales.x.getPixelForValue(raw.origX);
                const ay = scales.y.getPixelForValue(raw.origY);

                ctx.beginPath();
                ctx.moveTo(ax, ay);
                ctx.lineTo(pt.x, pt.y);
                ctx.strokeStyle = 'rgba(100, 116, 139, 0.45)';
                ctx.lineWidth = 1;
                ctx.stroke();

                const key = `${raw.origX}_${raw.origY}`;
                if (!drawnAnchors.has(key)) {
                    drawnAnchors.add(key);
                    ctx.beginPath();
                    ctx.arc(ax, ay, 2.5, 0, Math.PI * 2);
                    ctx.fillStyle = '#475569';
                    ctx.fill();
                }
            });
        });

        // Watermark kuadran
        ctx.font = '700 10px Inter, system-ui, sans-serif';
        ctx.fillStyle = 'rgba(4, 120, 87, 0.65)';
        ctx.textAlign = 'right';
        ctx.fillText(
            welfareMode ? 'ZONA BASIS SEJAHTERA (Skor >= 50 • >= 3 Siswa)' : 'ZONA BASIS UTAMA (>= 5 Siswa / >= 3 TA)',
            chartArea.right - 10,
            chartArea.top + 16
        );
        if (welfareMode) {
            ctx.fillStyle = 'rgba(180, 83, 9, 0.72)';
            ctx.fillText('POTENSIAL SEJAHTERA (0-2 Siswa)', chartArea.right - 10, chartArea.bottom - 10);
        }

        ctx.restore();
    },
    afterDatasetsDraw(chart) {
        if (isWelfareMode.value) return;
        const { ctx, chartArea } = chart;
        if (!chartArea) return;

        // Gambar label titik (0, 0) di ATAS bubble-nya (bukan di kanan) supaya tidak tabrakan dengan kolom 1 TA
        const zeroMeta = chart.getDatasetMeta(3);
        if (zeroMeta && zeroMeta.data && zeroMeta.data.length > 0) {
            const pt = zeroMeta.data[0];
            const raw = chart.data.datasets[3]?.data?.[0];
            if (pt && raw && pt.x >= chartArea.left - 10 && pt.x <= chartArea.right + 10 && pt.y >= chartArea.top - 10 && pt.y <= chartArea.bottom + 10) {
                ctx.save();
                ctx.beginPath();
                ctx.rect(chartArea.left, chartArea.top, chartArea.width, chartArea.height);
                ctx.clip();

                // Cincin luar putus-putus di (0,0)
                ctx.beginPath();
                ctx.arc(pt.x, pt.y, 19, 0, Math.PI * 2);
                ctx.setLineDash([4, 3]);
                ctx.strokeStyle = '#64748b';
                ctx.lineWidth = 1.5;
                ctx.stroke();
                ctx.setLineDash([]);

                // Pill label di atas titik (0, 0)
                const labelText = `${raw.count.toLocaleString('id-ID')} Sekolah (0,0)`;
                ctx.font = '700 10px Inter, system-ui, sans-serif';
                const textW = ctx.measureText(labelText).width;
                const boxW = textW + 14;
                const boxH = 20;
                const boxX = Math.max(chartArea.left + 6, pt.x - 10);
                const boxY = Math.max(chartArea.top + 6, pt.y - 44);

                // Garis penghubung ke bubble (0,0)
                ctx.beginPath();
                ctx.moveTo(pt.x, pt.y - 19);
                ctx.lineTo(pt.x, boxY + boxH);
                ctx.strokeStyle = '#94a3b8';
                ctx.lineWidth = 1;
                ctx.stroke();

                // Kotak rounded putih
                ctx.fillStyle = 'rgba(255, 255, 255, 0.95)';
                ctx.strokeStyle = '#cbd5e1';
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.roundRect(boxX, boxY, boxW, boxH, 6);
                ctx.fill();
                ctx.stroke();

                ctx.fillStyle = '#334155';
                ctx.textAlign = 'left';
                ctx.textBaseline = 'middle';
                ctx.fillText(labelText, boxX + 7, boxY + boxH / 2);

                ctx.restore();
            }
        }
    },
};

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: isDragging.value || isPinching.value ? 0 : 180,
    },
    layout: {
        padding: { top: 14, right: 18, bottom: 6, left: 4 },
    },
    onHover: (event, elements) => {
        const canvas = event?.native?.target;
        if (canvas) {
            if (isDragging.value) {
                canvas.style.cursor = 'grabbing';
            } else if (elements && elements.length > 0) {
                canvas.style.cursor = 'pointer';
            } else {
                canvas.style.cursor = isZoomed.value ? 'grab' : 'default';
            }
        }
    },
    onClick: (_event, elements, chart) => {
        if (suppressNextClick) {
            suppressNextClick = false;
            return;
        }
        if (!elements || elements.length === 0) return;
        const { datasetIndex, index } = elements[0];
        const point = chart.data.datasets[datasetIndex]?.data?.[index];
        if (!point) return;

        if (point.tier === 'belum_tergarap') {
            emit('select-tier', 'belum_tergarap');
        } else if (isWelfareMode.value && point.villages && point.villages.length > 0) {
            emit('select-villages', {
                title: `${PSB_WELFARE_TIERS[point.tier]?.label || 'Desa'} — Skor ~${point.origX} • ${point.origY} Siswa Domisili`,
                villages: point.villages,
            });
        } else if (point.schools && point.schools.length > 0) {
            emit('select-schools', {
                title: `${PSB_TIERS[point.tier]?.label || 'Sekolah'} — ${point.origX} TA, ${point.origY} Siswa`,
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
                    if (isWelfareMode.value) {
                        const tLabel = PSB_WELFARE_TIERS[raw.tier]?.label || '';
                        return `${tLabel} (Skor ~${raw.origX}/100 • ${raw.origY} Siswa)`;
                    }
                    if (raw.tier === 'belum_tergarap') {
                        return 'Titik (0, 0) — Belum Tergarap';
                    }
                    const tierLabel = PSB_TIERS[raw.tier]?.label || '';
                    return `${tierLabel} (${raw.origX} TA • ${raw.origY} Siswa)`;
                },
                label(item) {
                    const raw = item.raw;
                    if (!raw) return '';
                    if (isWelfareMode.value) {
                        const lines = [
                            `Jumlah: ${raw.count} desa/kelurahan (Rata P1-P4: ${raw.avgTotalP14.toLocaleString('id-ID')})`,
                        ];
                        const preview = (raw.villages || []).slice(0, 5);
                        preview.forEach((v) => {
                            lines.push(`• ${v.desa}, Kec. ${v.kecamatan} (P1-4: ${v.total.toLocaleString('id-ID')}, ${v.totalStudents} siswa)`);
                        });
                        if (raw.count > 5) {
                            lines.push(`... dan ${raw.count - 5} desa lainnya (klik lingkaran)`);
                        }
                        return lines;
                    }
                    if (raw.tier === 'belum_tergarap') {
                        return [
                            `Total: ${raw.count.toLocaleString('id-ID')} sekolah berstatus 0 siswa`,
                            'Klik penanda ini untuk melihat daftar Sekolah 0',
                        ];
                    }
                    const lines = [`Jumlah: ${raw.count} sekolah pada kategori & posisi ini`];
                    const preview = (raw.schools || []).slice(0, 5);
                    preview.forEach((s) => {
                        lines.push(`• ${s.nama} (${s.kecamatan})`);
                    });
                    if (raw.count > 5) {
                        lines.push(`... dan ${raw.count - 5} sekolah lainnya (klik lingkaran)`);
                    }
                    return lines;
                },
            },
        },
    },
    scales: {
        x: {
            min: activeBounds.value.xMin,
            max: activeBounds.value.xMax,
            title: {
                display: true,
                text: isWelfareMode.value
                    ? 'Indeks Kesejahteraan Desa (0 = Beban P1-P4 Tinggi • 100 = Sejahtera)'
                    : 'Konsistensi (Jumlah Tahun Ajaran Menyumbang)',
                color: '#475569',
                font: { size: 11, weight: '700' },
            },
            grid: {
                color: 'rgba(226, 232, 240, 0.7)',
            },
            ticks: {
                stepSize: isWelfareMode.value ? 10 : 1,
                color: '#64748b',
                font: { size: 11, weight: '600' },
                callback(val) {
                    if (val < 0 || !Number.isInteger(val)) return '';
                    if (isWelfareMode.value) {
                        return val <= 100 ? `${val}` : '';
                    }
                    return val === 0 ? '0 TA' : `${val} TA`;
                },
            },
        },
        y: {
            min: activeBounds.value.yMin,
            max: activeBounds.value.yMax,
            title: {
                display: true,
                text: isWelfareMode.value ? 'Siswa Domisili Masuk' : 'Total Siswa Masuk',
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

// ============================================================
// Kontrol Zoom & Pan (Tombol Preset, +/- Zoom, Scroll Wheel, Drag)
// ============================================================
function clampWindow(win) {
    const fb = fullBounds.value;
    const minXSpan = isWelfareMode.value ? 18 : 1.6;
    const minYSpan = 2.6;

    let xSpan = Math.max(minXSpan, Math.min(fb.xMax - fb.xMin, win.xMax - win.xMin));
    let ySpan = Math.max(minYSpan, Math.min(fb.yMax - fb.yMin, win.yMax - win.yMin));

    let xMin = Math.max(fb.xMin, win.xMin);
    let xMax = xMin + xSpan;
    if (xMax > fb.xMax) {
        xMax = fb.xMax;
        xMin = Math.max(fb.xMin, xMax - xSpan);
    }

    let yMin = Math.max(fb.yMin, win.yMin);
    let yMax = yMin + ySpan;
    if (yMax > fb.yMax) {
        yMax = fb.yMax;
        yMin = Math.max(fb.yMin, yMax - ySpan);
    }

    const almostFull =
        xSpan >= (fb.xMax - fb.xMin) * 0.96 && ySpan >= (fb.yMax - fb.yMin) * 0.96;

    if (almostFull) {
        return null;
    }

    return {
        xMin: Number(xMin.toFixed(3)),
        xMax: Number(xMax.toFixed(3)),
        yMin: Number(yMin.toFixed(3)),
        yMax: Number(yMax.toFixed(3)),
    };
}

function setPresetAll() {
    zoomWindow.value = null;
    zoomPreset.value = 'all';
}

function setPresetDense() {
    const fb = fullBounds.value;
    if (isWelfareMode.value) {
        zoomWindow.value = clampWindow({
            xMin: 46,
            xMax: 103,
            yMin: -0.7,
            yMax: Math.min(fb.yMax, 6.2),
        });
    } else {
        zoomWindow.value = clampWindow({
            xMin: -0.55,
            xMax: Math.min(fb.xMax, 3.35),
            yMin: -0.7,
            yMax: Math.min(fb.yMax, 6.2),
        });
    }
    zoomPreset.value = zoomWindow.value ? 'dense' : 'all';
}

watch(
    () => props.mode,
    () => {
        zoomWindow.value = null;
        zoomPreset.value = 'all';
    }
);

function applyZoomFactor(factor, anchorDataX = null, anchorDataY = null) {
    const cur = activeBounds.value;
    const xSpan = cur.xMax - cur.xMin;
    const ySpan = cur.yMax - cur.yMin;

    const cx = anchorDataX !== null ? anchorDataX : cur.xMin + xSpan / 2;
    const cy = anchorDataY !== null ? anchorDataY : cur.yMin + ySpan / 2;

    const newXSpan = xSpan * factor;
    const newYSpan = ySpan * factor;

    const rx = (cx - cur.xMin) / xSpan;
    const ry = (cy - cur.yMin) / ySpan;

    zoomWindow.value = clampWindow({
        xMin: cx - rx * newXSpan,
        xMax: cx + (1 - rx) * newXSpan,
        yMin: cy - ry * newYSpan,
        yMax: cy + (1 - ry) * newYSpan,
    });
    zoomPreset.value = zoomWindow.value ? 'custom' : 'all';
}

function handleWheel(e) {
    const chart = bubbleChartRef.value?.chart;
    if (!chart?.chartArea || !chart?.scales?.x || !chart?.scales?.y) return;

    const rect = chart.canvas.getBoundingClientRect();
    const px = e.clientX - rect.left;
    const py = e.clientY - rect.top;

    // Hanya zoom jika kursor berada di dalam area plot chart
    const area = chart.chartArea;
    if (px < area.left || px > area.right || py < area.top || py > area.bottom) return;

    e.preventDefault();

    const dataX = chart.scales.x.getValueForPixel(px);
    const dataY = chart.scales.y.getValueForPixel(py);
    const factor = e.deltaY < 0 ? 0.78 : 1.28;
    applyZoomFactor(factor, dataX, dataY);
}

function handlePointerDown(e) {
    if (e.pointerType === 'touch') return;
    if (!isZoomed.value) return;
    if (e.button !== 0) return;
    isDragging.value = true;
    suppressNextClick = false;
    dragStartPx = { x: e.clientX, y: e.clientY };
    dragStartWindow = { ...activeBounds.value };
}

function handlePointerMove(e) {
    if (e.pointerType === 'touch') return;
    if (!isDragging.value || !dragStartPx || !dragStartWindow) return;
    const chart = bubbleChartRef.value?.chart;
    if (!chart?.chartArea) return;

    const dxPx = e.clientX - dragStartPx.x;
    const dyPx = e.clientY - dragStartPx.y;

    if (Math.hypot(dxPx, dyPx) >= 5) {
        suppressNextClick = true;
    } else {
        return;
    }

    const area = chart.chartArea;
    const xSpan = dragStartWindow.xMax - dragStartWindow.xMin;
    const ySpan = dragStartWindow.yMax - dragStartWindow.yMin;

    const dxVal = -(dxPx / area.width) * xSpan;
    const dyVal = (dyPx / area.height) * ySpan;

    zoomWindow.value = clampWindow({
        xMin: dragStartWindow.xMin + dxVal,
        xMax: dragStartWindow.xMax + dxVal,
        yMin: dragStartWindow.yMin + dyVal,
        yMax: dragStartWindow.yMax + dyVal,
    });
    zoomPreset.value = zoomWindow.value ? 'custom' : 'all';
}

function handlePointerUp(e) {
    if (e?.pointerType === 'touch') return;
    isDragging.value = false;
    dragStartPx = null;
    dragStartWindow = null;
}

// ============================================================
// Touch Gestures Mobile: Pinch-to-Zoom (2 Jari) & Pan (1 Jari)
// ============================================================
function getTouchDistAndMid(t0, t1) {
    const dist = Math.hypot(t1.clientX - t0.clientX, t1.clientY - t0.clientY);
    const midX = (t0.clientX + t1.clientX) / 2;
    const midY = (t0.clientY + t1.clientY) / 2;
    return { dist, midX, midY };
}

function startPinchFromTouches(touches) {
    const chart = bubbleChartRef.value?.chart;
    if (!chart?.chartArea || !chart?.scales?.x || !chart?.scales?.y) return;

    const { dist, midX, midY } = getTouchDistAndMid(touches[0], touches[1]);
    if (dist < 10) return;

    const rect = chart.canvas.getBoundingClientRect();
    const px = midX - rect.left;
    const py = midY - rect.top;

    isPinching.value = true;
    isDragging.value = false;
    suppressNextClick = true;
    pinchStartDist = dist;
    pinchStartMidPx = { x: px, y: py };
    pinchStartDataMid = {
        x: chart.scales.x.getValueForPixel(px),
        y: chart.scales.y.getValueForPixel(py),
    };
    pinchStartWindow = { ...activeBounds.value };
}

function handleTouchStart(e) {
    if (e.touches.length === 2) {
        e.preventDefault();
        startPinchFromTouches(e.touches);
    } else if (e.touches.length === 1 && isZoomed.value) {
        isPinching.value = false;
        isDragging.value = true;
        suppressNextClick = false;
        dragStartPx = { x: e.touches[0].clientX, y: e.touches[0].clientY };
        dragStartWindow = { ...activeBounds.value };
    }
}

function handleTouchMove(e) {
    const chart = bubbleChartRef.value?.chart;
    if (!chart?.chartArea) return;

    if (e.touches.length === 2) {
        e.preventDefault();
        if (!isPinching.value || !pinchStartWindow || !pinchStartDataMid) {
            startPinchFromTouches(e.touches);
            return;
        }

        const { dist, midX, midY } = getTouchDistAndMid(e.touches[0], e.touches[1]);
        if (dist < 10 || pinchStartDist < 10) return;

        const rect = chart.canvas.getBoundingClientRect();
        const curPx = midX - rect.left;
        const curPy = midY - rect.top;
        const area = chart.chartArea;

        const scaleFactor = pinchStartDist / dist;
        const startXSpan = pinchStartWindow.xMax - pinchStartWindow.xMin;
        const startYSpan = pinchStartWindow.yMax - pinchStartWindow.yMin;

        const newXSpan = startXSpan * scaleFactor;
        const newYSpan = startYSpan * scaleFactor;

        // Jaga titik data awal tetap mengikuti posisi titik tengah kedua jari di layar
        const rx = Math.max(0, Math.min(1, (curPx - area.left) / area.width));
        const ry = Math.max(0, Math.min(1, (area.bottom - curPy) / area.height));

        zoomWindow.value = clampWindow({
            xMin: pinchStartDataMid.x - rx * newXSpan,
            xMax: pinchStartDataMid.x + (1 - rx) * newXSpan,
            yMin: pinchStartDataMid.y - ry * newYSpan,
            yMax: pinchStartDataMid.y + (1 - ry) * newYSpan,
        });
        zoomPreset.value = zoomWindow.value ? 'custom' : 'all';
        return;
    }

    if (e.touches.length === 1 && isDragging.value && isZoomed.value && dragStartPx && dragStartWindow) {
        const dxPx = e.touches[0].clientX - dragStartPx.x;
        const dyPx = e.touches[0].clientY - dragStartPx.y;

        if (Math.hypot(dxPx, dyPx) >= 4) {
            suppressNextClick = true;
            e.preventDefault();
        } else {
            return;
        }

        const area = chart.chartArea;
        const xSpan = dragStartWindow.xMax - dragStartWindow.xMin;
        const ySpan = dragStartWindow.yMax - dragStartWindow.yMin;

        const dxVal = -(dxPx / area.width) * xSpan;
        const dyVal = (dyPx / area.height) * ySpan;

        zoomWindow.value = clampWindow({
            xMin: dragStartWindow.xMin + dxVal,
            xMax: dragStartWindow.xMax + dxVal,
            yMin: dragStartWindow.yMin + dyVal,
            yMax: dragStartWindow.yMax + dyVal,
        });
        zoomPreset.value = zoomWindow.value ? 'custom' : 'all';
    }
}

function handleTouchEnd(e) {
    if (e.touches.length === 0) {
        isPinching.value = false;
        isDragging.value = false;
        dragStartPx = null;
        dragStartWindow = null;
        pinchStartWindow = null;
        pinchStartDataMid = null;
    } else if (e.touches.length === 1 && isPinching.value) {
        isPinching.value = false;
        if (isZoomed.value) {
            isDragging.value = true;
            dragStartPx = { x: e.touches[0].clientX, y: e.touches[0].clientY };
            dragStartWindow = { ...activeBounds.value };
        }
    }
}

onMounted(() => {
    const el = chartWrapperRef.value;
    if (!el) return;
    el.addEventListener('touchstart', handleTouchStart, { passive: false });
    el.addEventListener('touchmove', handleTouchMove, { passive: false });
    el.addEventListener('touchend', handleTouchEnd, { passive: true });
    el.addEventListener('touchcancel', handleTouchEnd, { passive: true });
});

onBeforeUnmount(() => {
    const el = chartWrapperRef.value;
    if (!el) return;
    el.removeEventListener('touchstart', handleTouchStart);
    el.removeEventListener('touchmove', handleTouchMove);
    el.removeEventListener('touchend', handleTouchEnd);
    el.removeEventListener('touchcancel', handleTouchEnd);
});
</script>

<template>
    <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-6 shadow-xs flex flex-col">
        <!-- Header, Zoom Controls & Legend Tier -->
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
            <div class="flex items-start gap-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block mt-1.5 shrink-0"></span>
                <div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                        {{ isWelfareMode ? 'Matriks Kesejahteraan Desa vs Siswa Domisili' : 'Matriks Potensi Sekolah (Konsistensi vs Total Siswa)' }}
                    </h3>
                    <p class="text-[11.5px] text-slate-500 mt-0.5">
                        <template v-if="isWelfareMode">
                            Menyandingkan <span class="font-bold text-slate-700">Indeks Kesejahteraan (P1–P4 Jatengprov)</span> tiap desa dengan jumlah siswa berdomisili.
                        </template>
                        <template v-else>
                            Titik kembar otomatis dipisah ke sisi berbeda; gunakan scroll / tombol <span class="font-bold text-slate-700">Zoom Area Padat</span> untuk memperbesar titik yang berdekatan.
                        </template>
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Toggle Mode Matriks -->
                <div class="inline-flex items-center p-0.5 bg-slate-100 rounded-xl border border-slate-200/90">
                    <button
                        type="button"
                        @click="emit('update:mode', 'school')"
                        :class="[
                            'px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all cursor-pointer',
                            !isWelfareMode ? 'bg-emerald-900 text-yellow-200 shadow-2xs' : 'text-slate-600 hover:text-slate-900',
                        ]"
                    >
                        Asal Sekolah
                    </button>
                    <button
                        type="button"
                        @click="emit('update:mode', 'welfare')"
                        :class="[
                            'px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all cursor-pointer',
                            isWelfareMode ? 'bg-emerald-900 text-yellow-200 shadow-2xs' : 'text-slate-600 hover:text-slate-900',
                        ]"
                    >
                        Kesejahteraan Desa
                    </button>
                </div>

                <!-- Kontrol Zoom Cepat -->
                <div class="inline-flex items-center p-0.5 bg-slate-100 rounded-xl border border-slate-200/90">
                    <button
                        type="button"
                        @click="setPresetAll"
                        :class="[
                            'px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all cursor-pointer',
                            zoomPreset === 'all' ? 'bg-white text-emerald-950 shadow-2xs' : 'text-slate-500 hover:text-slate-800',
                        ]"
                    >
                        Semua Area
                    </button>
                    <button
                        type="button"
                        @click="setPresetDense"
                        :class="[
                            'px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all cursor-pointer',
                            zoomPreset === 'dense' ? 'bg-emerald-900 text-yellow-200 shadow-2xs' : 'text-slate-600 hover:text-slate-900',
                        ]"
                        title="Perbesar area 0–3 TA & 0–6 Siswa yang paling padat"
                    >
                        Zoom Area Padat
                    </button>
                    <button
                        type="button"
                        @click="applyZoomFactor(0.72)"
                        class="px-2 py-1 rounded-lg text-xs font-extrabold text-slate-700 hover:bg-white cursor-pointer"
                        title="Perbesar (+)"
                    >
                        +
                    </button>
                    <button
                        type="button"
                        @click="applyZoomFactor(1.35)"
                        :disabled="!isZoomed"
                        class="px-2 py-1 rounded-lg text-xs font-extrabold text-slate-700 hover:bg-white disabled:opacity-35 cursor-pointer"
                        title="Perkecil (−)"
                    >
                        −
                    </button>
                </div>

                <!-- Legend Klik Cepat -->
                <div v-if="isWelfareMode" class="flex items-center gap-1.5 flex-wrap">
                    <button
                        v-for="tierKey in ['sejahtera_basis', 'sejahtera_potensial', 'rentan_terjangkau', 'rentan_minim']"
                        :key="tierKey"
                        type="button"
                        @click="emit('select-tier', tierKey)"
                        :class="[
                            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border transition-all cursor-pointer hover:opacity-90 active:scale-95',
                            PSB_WELFARE_TIERS[tierKey].badgeClass,
                        ]"
                        :title="PSB_WELFARE_TIERS[tierKey].description"
                    >
                        <span :class="['w-2 h-2 rounded-full', PSB_WELFARE_TIERS[tierKey].dotClass]"></span>
                        <span>{{ PSB_WELFARE_TIERS[tierKey].shortLabel }}</span>
                    </button>
                </div>
                <div v-else class="flex items-center gap-1.5 flex-wrap">
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
        </div>

        <!-- Chart Canvas dengan dukungan Wheel Zoom, Pinch-to-Zoom (Mobile), & Drag Pan -->
        <div
            ref="chartWrapperRef"
            class="h-[330px] sm:h-[380px] w-full relative select-none"
            :style="{ touchAction: isZoomed ? 'none' : 'pan-y' }"
            @wheel="handleWheel"
            @pointerdown="handlePointerDown"
            @pointermove="handlePointerMove"
            @pointerup="handlePointerUp"
            @pointerleave="handlePointerUp"
        >
            <Bubble ref="bubbleChartRef" :data="chartData" :options="chartOptions" :plugins="[quadrantPlugin]" />

            <div
                v-if="isZoomed"
                class="absolute bottom-11 right-3 z-10 px-2.5 py-1 rounded-lg bg-white/95 border border-slate-200 shadow-xs text-[10.5px] font-semibold text-slate-600 flex items-center gap-2"
            >
                <span>Mode Zoom (geser kanvas atau scroll)</span>
                <button
                    type="button"
                    @click="setPresetAll"
                    class="text-emerald-700 font-bold hover:underline cursor-pointer"
                >
                    Reset
                </button>
            </div>
        </div>
    </div>
</template>
