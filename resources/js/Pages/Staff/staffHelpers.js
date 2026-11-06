/**
 * Helper functions untuk fitur Kepegawaian (Staff Detail & Tabs)
 */

export function formatTanggalIndo(dateStr) {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
    } catch {
        return dateStr;
    }
}

export function formatDateTimeIndo(dateTimeStr) {
    if (!dateTimeStr) return '-';
    try {
        const d = new Date(dateTimeStr);
        if (isNaN(d.getTime())) return dateTimeStr;
        const datePart = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        const timePart = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace('.', ':');
        return `${datePart} ${timePart}`;
    } catch {
        return dateTimeStr;
    }
}

export function hitungUsia(tanggalLahir, ttlString = '') {
    if (tanggalLahir) {
        const birthDate = new Date(tanggalLahir);
        if (!isNaN(birthDate.getTime())) {
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            return age >= 0 ? `${age} Tahun` : '-';
        }
    }

    if (ttlString) {
        const indonesianMonths = {
            januari: 0, februari: 1, maret: 2, april: 3, mei: 4, juni: 5,
            juli: 6, agustus: 7, september: 8, oktober: 9, november: 10, desember: 11
        };
        const match = ttlString.match(/(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/);
        if (match) {
            const day = parseInt(match[1]);
            const monthName = match[2].toLowerCase();
            const year = parseInt(match[3]);
            if (indonesianMonths[monthName] !== undefined) {
                const birthDate = new Date(year, indonesianMonths[monthName], day);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const m = today.getMonth() - birthDate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                return age >= 0 ? `${age} Tahun` : '-';
            }
        }

        const numMatch = ttlString.match(/(\d{1,2})[-/](\d{1,2})[-/](\d{4})/);
        if (numMatch) {
            const day = parseInt(numMatch[1]);
            const month = parseInt(numMatch[2]) - 1;
            const year = parseInt(numMatch[3]);
            const birthDate = new Date(year, month, day);
            if (!isNaN(birthDate.getTime())) {
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const m = today.getMonth() - birthDate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                return age >= 0 ? `${age} Tahun` : '-';
            }
        }
    }

    return '-';
}

export function hitungMasaKerja(tmtStr) {
    if (!tmtStr) return '-';
    const start = new Date(tmtStr);
    if (isNaN(start.getTime())) return '-';

    const end = new Date();
    if (start > end) return '0 Tahun';

    let years = end.getFullYear() - start.getFullYear();
    let months = end.getMonth() - start.getMonth();
    let days = end.getDate() - start.getDate();

    if (days < 0) {
        months--;
    }
    if (months < 0) {
        years--;
        months += 12;
    }

    if (years <= 0 && months <= 0) {
        return 'Kurang dari 1 bulan';
    }

    const parts = [];
    if (years > 0) parts.push(`${years} Tahun`);
    if (months > 0) parts.push(`${months} Bulan`);
    return parts.join(' ');
}

export function getJenjangBadgeClass(jenjang) {
    if (!jenjang) return 'bg-slate-100 text-slate-600 border border-slate-200';
    const j = jenjang.toUpperCase();
    if (j.startsWith('GTY')) {
        return 'bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold';
    }
    if (j.startsWith('GTT')) {
        return 'bg-teal-50 text-teal-800 border border-teal-200 font-semibold';
    }
    if (j.startsWith('PTY')) {
        return 'bg-indigo-50 text-indigo-800 border border-indigo-200 font-semibold';
    }
    if (j.startsWith('PTT')) {
        return 'bg-amber-50 text-amber-800 border border-amber-200 font-semibold';
    }
    return 'bg-slate-100 text-slate-700 border border-slate-200 font-medium';
}

export function getInitials(name) {
    if (!name) return '??';
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map(w => w[0]?.toUpperCase() || '')
        .join('');
}

export function getAvatarStyle(emp) {
    if (emp?.jk === 'L') {
        return 'bg-blue-50 text-blue-800 border-blue-200/80';
    }
    return 'bg-rose-50 text-rose-800 border-rose-200/80';
}

export function getAlamatDomisili(e) {
    if (!e) return '-';
    return [e.jalan_domisili, e.rt_rw_domisili, e.dusun_domisili, e.desa_domisili, e.kecamatan_domisili, e.kab_domisili, e.provinsi_domisili]
        .filter(Boolean).join(', ') || '-';
}

export function getAlamatTinggal(e) {
    if (!e) return '-';
    return [e.jalan_tinggal, e.rt_rw_tinggal, e.dusun_tinggal, e.desa_tinggal, e.kecamatan_tinggal, e.kabupaten_tinggal, e.provinsi_tinggal]
        .filter(Boolean).join(', ') || '-';
}

export function getStatusStyle(status) {
    const s = (status || 'Aktif').toLowerCase();
    if (s === 'aktif') {
        return {
            wrapper: 'bg-emerald-50 text-emerald-800 border border-emerald-200 font-bold',
            dot: 'bg-emerald-600',
        };
    }
    if (s === 'pensiun') {
        return {
            wrapper: 'bg-amber-50 text-amber-800 border border-amber-200 font-bold',
            dot: 'bg-amber-500',
        };
    }
    return {
        wrapper: 'bg-rose-50 text-rose-700 border border-rose-200 font-bold',
        dot: 'bg-rose-600',
    };
}

