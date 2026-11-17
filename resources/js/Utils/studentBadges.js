/**
 * Single Source of Truth untuk warna dan label badge siswa di seluruh aplikasi
 * (Tabel Siswa, Kartu Mobile, Detail Siswa, Tab Identitas & Registrasi, Inline Edit,
 * Pembelajaran/Kelas, dan Halaman Statistik) agar setiap jenis data memiliki warna
 * unik yang tidak saling bertabrakan dan konsisten 100%.
 *
 * Pemetaan Warna Semantik:
 * - Status Keaktifan:
 *   - Aktif                : Emerald
 *   - Lulus                : Blue
 *   - Tidak Aktif / Keluar : Rose
 *   - Aktif Sebagian/Ulang : Amber
 * - Jenis Kelamin:
 *   - Laki-laki (L)        : Cyan
 *   - Perempuan (P)        : Fuchsia
 * - Program Pendidikan:
 *   - Boarding             : Violet
 *   - Fullday              : Teal
 *   - Umum                 : Slate
 * - Status Registrasi Masuk:
 *   - Siswa Baru           : Emerald
 *   - Mutasi Masuk         : Amber
 * - Unit Pendidikan:
 *   - Unit Sekolah Formal  : Slate
 *   - Unit Pesantren       : Indigo
 */

export const BADGE_COLORS = {
    gender: {
        L: {
            hex: '#0891b2', // cyan-600
            barClass: 'bg-[#0891b2]',
            badgeClass: 'bg-cyan-50 text-cyan-800 border-cyan-200',
            pillActiveClass: 'bg-cyan-700 text-white shadow-2xs',
            pillBadgeClass: 'bg-cyan-100 text-cyan-900',
        },
        P: {
            hex: '#c026d3', // fuchsia-600
            barClass: 'bg-[#c026d3]',
            badgeClass: 'bg-fuchsia-50 text-fuchsia-800 border-fuchsia-200',
            pillActiveClass: 'bg-fuchsia-700 text-white shadow-2xs',
            pillBadgeClass: 'bg-fuchsia-100 text-fuchsia-900',
        },
    },
    program: {
        boarding: {
            hex: '#7c3aed', // violet-600
            barClass: 'bg-[#7c3aed]',
            badgeClass: 'bg-violet-50 text-violet-800 border-violet-200',
        },
        fullday: {
            hex: '#0d9488', // teal-600
            barClass: 'bg-[#0d9488]',
            badgeClass: 'bg-teal-50 text-teal-800 border-teal-200',
        },
        umum: {
            hex: '#64748b', // slate-500
            barClass: 'bg-[#64748b]',
            badgeClass: 'bg-slate-100 text-slate-700 border-slate-200',
        },
    },
};

export function getGenderLabel(jk) {
    if (jk === 'L') return 'Laki-laki';
    if (jk === 'P') return 'Perempuan';
    return jk || '-';
}

export function getGenderShortLabel(jk) {
    if (jk === 'L') return 'L (Putra)';
    if (jk === 'P') return 'P (Putri)';
    return jk || '-';
}

export function getGenderBadgeClass(jk) {
    if (jk === 'L') return BADGE_COLORS.gender.L.badgeClass;
    if (jk === 'P') return BADGE_COLORS.gender.P.badgeClass;
    return 'bg-slate-100 text-slate-600 border-slate-200';
}

export function getStatusKeaktifanCategory(statusOrStudent) {
    if (!statusOrStudent) return 'aktif';
    const raw = typeof statusOrStudent === 'object'
        ? (statusOrStudent.status_keaktifan || statusOrStudent.student_status || statusOrStudent.status || 'aktif')
        : String(statusOrStudent);

    const s = raw.toLowerCase().trim();
    if (!s || s === '-') return 'neutral';
    if (s === 'lulus') return 'lulus';
    if (s === 'tidak aktif-sebagian' || s === 'aktif sebagian' || s === 'mengulang') return 'sebagian';
    if (
        s === 'tidak aktif' ||
        s === 'tidak_aktif' ||
        s === 'non-aktif' ||
        s === 'dropout' ||
        s === 'mutasi_keluar' ||
        s === 'mutasi keluar' ||
        s.includes('tidak') ||
        s.includes('keluar') ||
        s.includes('drop')
    ) {
        return 'tidak_aktif';
    }
    return 'aktif';
}

export function getStatusKeaktifanBadgeClass(statusOrStudent) {
    const cat = getStatusKeaktifanCategory(statusOrStudent);
    if (cat === 'lulus') return 'bg-blue-50 text-blue-800 border-blue-200';
    if (cat === 'sebagian') return 'bg-amber-50 text-amber-900 border-amber-300';
    if (cat === 'tidak_aktif') return 'bg-rose-50 text-rose-800 border-rose-200';
    if (cat === 'neutral') return 'bg-slate-100 text-slate-700 border-slate-200';
    return 'bg-emerald-50 text-emerald-800 border-emerald-200';
}

export function getStatusKeaktifanDotClass(statusOrStudent) {
    const cat = getStatusKeaktifanCategory(statusOrStudent);
    if (cat === 'lulus') return 'bg-blue-600';
    if (cat === 'sebagian') return 'bg-amber-500';
    if (cat === 'tidak_aktif') return 'bg-rose-600';
    if (cat === 'neutral') return 'bg-slate-400';
    return 'bg-emerald-600';
}

export function getClassroomStatusBadgeClass(status) {
    const s = (status || '').toLowerCase().trim();
    if (s === 'aktif') return 'bg-emerald-50 text-emerald-800 border-emerald-200';
    if (s === 'mutasi_masuk') return 'bg-amber-100 text-amber-900 border-amber-300';
    if (s === 'lulus') return 'bg-blue-50 text-blue-800 border-blue-200';
    if (s === 'mengulang') return 'bg-amber-50 text-amber-900 border-amber-300';
    if (s === 'mutasi_keluar' || s === 'dropout') return 'bg-rose-50 text-rose-800 border-rose-200';
    return 'bg-slate-100 text-slate-600 border-slate-200';
}

export function getProgramBadgeClass(program) {
    const p = (program || '').toLowerCase().trim();
    if (p.includes('boarding')) return BADGE_COLORS.program.boarding.badgeClass;
    if (p.includes('fullday')) return BADGE_COLORS.program.fullday.badgeClass;
    return BADGE_COLORS.program.umum.badgeClass;
}

export function getRegistrationTypeBadgeClass(statusRegistrasiOrIsMutasi) {
    if (typeof statusRegistrasiOrIsMutasi === 'boolean') {
        return statusRegistrasiOrIsMutasi
            ? 'bg-amber-100 text-amber-900 border-amber-300'
            : 'bg-emerald-50 text-emerald-800 border-emerald-200';
    }
    const r = (statusRegistrasiOrIsMutasi || '').toLowerCase().trim();
    if (r === 'siswa baru') return 'bg-emerald-50 text-emerald-800 border-emerald-200';
    if (r.includes('mutasi')) return 'bg-amber-100 text-amber-900 border-amber-300';
    return 'bg-slate-100 text-slate-700 border-slate-200';
}

export function getUnitBadgeClass(type = 'formal') {
    if (type === 'pesantren') {
        return 'bg-indigo-50 text-indigo-700 border-indigo-200';
    }
    return 'bg-slate-100 text-slate-800 border-slate-300';
}

