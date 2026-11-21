/**
 * Hitung median dari kumpulan angka non-negatif.
 *
 * @param {number[]} values
 * @returns {number}
 */
export function computeMedian(values) {
    const clean = values
        .map((v) => Number(v))
        .filter((v) => Number.isFinite(v))
        .sort((a, b) => a - b);

    if (clean.length === 0) return 0;
    const mid = Math.floor(clean.length / 2);
    if (clean.length % 2 === 1) {
        return clean[mid];
    }
    return (clean[mid - 1] + clean[mid]) / 2;
}

/**
 * Hitung tahun ajaran berikutnya dari format "YYYY/YYYY".
 *
 * @param {string} yearName
 * @returns {string}
 */
export function getNextAcademicYearLabel(yearName) {
    const match = String(yearName || '').match(/(\d{4})\s*\/\s*(\d{4})/);
    if (match) {
        const y1 = parseInt(match[1], 10) + 1;
        const y2 = parseInt(match[2], 10) + 1;
        return `${y1}/${y2}`;
    }
    return 'Tahun Depan';
}

/**
 * Hitung prediksi jumlah siswa baru & mutasi masuk tahun ajaran berikutnya
 * menggunakan Exponentially Weighted Linear Trend (lebih sensitif 3 TA terakhir)
 * dengan faktor redaman (damped trend) dan Median untuk Mutasi Masuk.
 *
 * @param {Array<{year: string, total: number, siswaBaru: number, mutasiMasuk: number}>} series
 * @param {string|null} activeAcademicYearName
 * @returns {{
 *   isAvailable: boolean,
 *   minRequired: number,
 *   reason: string|null,
 *   nextYearLabel: string,
 *   historyYearsCount: number,
 *   siswaBaruEst: number,
 *   mutasiEst: number,
 *   totalEst: number,
 *   siswaBaruLow: number,
 *   siswaBaruHigh: number,
 *   totalLow: number,
 *   totalHigh: number,
 *   slopePerYear: number,
 *   recent3DeltaPerYear: number|null,
 *   consecutiveDown: number,
 *   consecutiveUp: number,
 *   status: 'warning'|'growing'|'fluctuating'|'stable',
 *   statusLabel: string,
 *   headline: string,
 *   trendPointsSiswaBaru: number[],
 *   trendPointsWithMutasi: number[],
 *   referenceCurrentSiswaBaru: number,
 *   referenceCurrentTotal: number,
 * }}
 */
export function computeEnrollmentForecast(series, activeAcademicYearName = null) {
    const emptyResult = (reason = 'Data historis minimal 3 Tahun Ajaran untuk menghitung tren.') => ({
        isAvailable: false,
        minRequired: 3,
        reason,
        nextYearLabel: getNextAcademicYearLabel(series?.[series.length - 1]?.year || activeAcademicYearName || ''),
        historyYearsCount: series?.length || 0,
        siswaBaruEst: 0,
        mutasiEst: 0,
        totalEst: 0,
        siswaBaruLow: 0,
        siswaBaruHigh: 0,
        totalLow: 0,
        totalHigh: 0,
        slopePerYear: 0,
        recent3DeltaPerYear: null,
        consecutiveDown: 0,
        consecutiveUp: 0,
        status: 'stable',
        statusLabel: 'Belum Cukup Data',
        headline: reason,
        trendPointsSiswaBaru: [],
        trendPointsWithMutasi: [],
        trendByYearSiswaBaru: {},
        trendByYearWithMutasi: {},
        usableYears: [],
        referenceCurrentSiswaBaru: 0,
        referenceCurrentTotal: 0,
    });

    if (!Array.isArray(series) || series.length === 0) {
        return emptyResult();
    }

    // Potong TA masa depan di ujung kanan yang masih kosong (0 siswa) setelah TA aktif
    let usable = [...series];
    if (activeAcademicYearName) {
        const activeIdx = usable.findIndex((item) => item.year === activeAcademicYearName);
        if (activeIdx !== -1) {
            while (usable.length - 1 > activeIdx && usable[usable.length - 1].total === 0) {
                usable.pop();
            }
        }
    } else {
        while (usable.length > 0 && usable[usable.length - 1].total === 0) {
            usable.pop();
        }
    }

    // Lewati TA paling awal yang 0 jika hanya placeholder sebelum data masuk dimulai
    while (usable.length > 0 && usable[0].total === 0) {
        usable.shift();
    }

    if (usable.length < 3) {
        return emptyResult(`Minimal dibutuhkan 3 Tahun Ajaran historis (saat ini tersedia ${usable.length} TA).`);
    }

    const n = usable.length;
    const yNew = usable.map((item) => Number(item.siswaBaru) || 0);
    const yMutasi = usable.map((item) => Number(item.mutasiMasuk) || 0);

    // 1. Exponentially Weighted Linear Regression pada Siswa Baru (lambda = 0.55)
    // Bobot tahun terbaru = 1, TA-1 = 0.55, TA-2 = 0.30, dst -> responsif terhadap 3 TA terakhir
    const decay = 0.55;
    const weights = yNew.map((_, idx) => Math.pow(decay, n - 1 - idx));
    const sumW = weights.reduce((acc, w) => acc + w, 0);

    const xBar = weights.reduce((acc, w, idx) => acc + w * idx, 0) / sumW;
    const yBar = weights.reduce((acc, w, idx) => acc + w * yNew[idx], 0) / sumW;

    let numerator = 0;
    let denominator = 0;
    for (let i = 0; i < n; i++) {
        const dx = i - xBar;
        const dy = yNew[i] - yBar;
        numerator += weights[i] * dx * dy;
        denominator += weights[i] * dx * dx;
    }

    const slope = denominator > 0 ? numerator / denominator : 0;
    const intercept = yBar - slope * xBar;

    // 2. Proyeksi dengan Damping Factor (phi = 0.78) agar tidak turun/naik terlalu ekstrem
    const phi = 0.78;
    const fittedHistory = yNew.map((_, idx) => Math.max(0, Math.round(intercept + slope * idx)));
    const lastFitted = intercept + slope * (n - 1);
    const lastActualNew = yNew[n - 1];

    // Anchor proyeksi di antara level aktual TA terakhir dan fitted agar selaras saat turun berturut-turut
    const baseAnchor = lastActualNew * 0.55 + lastFitted * 0.45;
    const rawNewForecast = baseAnchor + phi * slope;
    const siswaBaruEst = Math.max(0, Math.round(rawNewForecast));

    // 3. Rentang Deviasi (Weighted Residual Std Dev)
    const weightedVariance =
        weights.reduce((acc, w, idx) => {
            const err = yNew[idx] - (intercept + slope * idx);
            return acc + w * err * err;
        }, 0) / sumW;
    const rmse = Math.sqrt(weightedVariance);

    // Perubahan rata-rata 3 TA terakhir (n >= 3 -> antar idx n-3 ke n-1)
    const recent3DeltaPerYear = Number(((yNew[n - 1] - yNew[n - 3]) / 2).toFixed(1));
    const absRecentStep = Math.abs(recent3DeltaPerYear);
    const margin = Math.max(2, Math.ceil(Math.max(rmse, absRecentStep * 0.65)));

    const siswaBaruLow = Math.max(0, siswaBaruEst - margin);
    const siswaBaruHigh = Math.max(siswaBaruLow, siswaBaruEst + margin);

    // 4. Estimasi Mutasi Masuk memakai Median TA penuh sebelumnya (atau seluruh TA jika hanya 3 tahun)
    const mutasiSource = n >= 4 ? yMutasi.slice(0, n - 1) : yMutasi;
    const mutasiEst = Math.max(0, Math.round(computeMedian(mutasiSource)));

    const totalEst = siswaBaruEst + mutasiEst;
    const totalLow = siswaBaruLow + mutasiEst;
    const totalHigh = siswaBaruHigh + mutasiEst;

    // 5. Deteksi penurunan / kenaikan beruntun menuju TA terakhir
    let consecutiveDown = 0;
    for (let i = n - 1; i > 0; i--) {
        if (yNew[i] < yNew[i - 1]) {
            consecutiveDown++;
        } else {
            break;
        }
    }

    let consecutiveUp = 0;
    for (let i = n - 1; i > 0; i--) {
        if (yNew[i] > yNew[i - 1]) {
            consecutiveUp++;
        } else {
            break;
        }
    }

    // Klasifikasi status Early Warning
    let status = 'stable';
    let statusLabel = 'Relatif Stabil';
    let headline = `Proyeksi siswa baru tahun depan diperkirakan sekitar ${siswaBaruEst} siswa (kisaran ${siswaBaruLow}–${siswaBaruHigh}).`;

    if (consecutiveDown >= 2 || slope <= -2.5) {
        status = 'warning';
        statusLabel = consecutiveDown >= 2 ? `Early Warning • Turun ${consecutiveDown} TA Beruntun` : 'Early Warning • Tren Menurun';
        headline =
            consecutiveDown >= 2
                ? `Siswa baru turun ${consecutiveDown} tahun ajaran beruntun (rata-rata ${recent3DeltaPerYear > 0 ? '+' : ''}${recent3DeltaPerYear} siswa/TA di 3 TA terakhir). Tanpa intervensi PSB, estimasi tahun depan ~${siswaBaruEst} siswa baru.`
                : `Tren siswa baru cenderung menurun (${slope.toFixed(1)} siswa/TA). Tanpa penguatan PSB, estimasi tahun depan ~${siswaBaruEst} siswa baru.`;
    } else if (consecutiveUp >= 2 || slope >= 2.5) {
        status = 'growing';
        statusLabel = consecutiveUp >= 2 ? `Tren Positif • Naik ${consecutiveUp} TA Beruntun` : 'Tren Positif • Bertumbuh';
        headline = `Tren siswa baru bertumbuh (+${Math.abs(slope).toFixed(1)} siswa/TA). Estimasi tahun depan ~${siswaBaruEst} siswa baru (kisaran ${siswaBaruLow}–${siswaBaruHigh}).`;
    } else if (rmse >= Math.max(5, yBar * 0.12)) {
        status = 'fluctuating';
        statusLabel = 'Pola Fluktuatif';
        headline = `Penerimaan siswa baru masih naik–turun antar tahun. Estimasi realistis berada di kisaran ${siswaBaruLow}–${siswaBaruHigh} siswa baru.`;
    }

    const trendPointsSiswaBaru = [...fittedHistory, siswaBaruEst];
    const trendPointsWithMutasi = usable.map((item, idx) => fittedHistory[idx] + (Number(item.mutasiMasuk) || 0));
    trendPointsWithMutasi.push(totalEst);

    const trendByYearSiswaBaru = {};
    const trendByYearWithMutasi = {};
    usable.forEach((item, idx) => {
        trendByYearSiswaBaru[item.year] = fittedHistory[idx];
        trendByYearWithMutasi[item.year] = fittedHistory[idx] + (Number(item.mutasiMasuk) || 0);
    });
    const usableYears = usable.map((item) => item.year);

    const lastActualTotal = usable[n - 1].total;

    return {
        isAvailable: true,
        minRequired: 3,
        reason: null,
        nextYearLabel: getNextAcademicYearLabel(usable[n - 1].year),
        historyYearsCount: n,
        siswaBaruEst,
        mutasiEst,
        totalEst,
        siswaBaruLow,
        siswaBaruHigh,
        totalLow,
        totalHigh,
        slopePerYear: Number(slope.toFixed(1)),
        recent3DeltaPerYear,
        consecutiveDown,
        consecutiveUp,
        status,
        statusLabel,
        headline,
        trendPointsSiswaBaru,
        trendPointsWithMutasi,
        trendByYearSiswaBaru,
        trendByYearWithMutasi,
        usableYears,
        referenceCurrentSiswaBaru: lastActualNew,
        referenceCurrentTotal: lastActualTotal,
    };
}
