/**
 * Utilitas normalisasi, pencocokan (NPSN + nama ternormalisasi), dan klasifikasi Tier
 * untuk menyandingkan dataset referensi SD/MI/PKBM Solo Raya dengan data siswa baru.
 */

export const PSB_TIERS = {
    basis_utama: {
        key: 'basis_utama',
        label: 'Basis Utama',
        shortLabel: 'Basis',
        description: 'Menyumbang >= 5 siswa atau konsisten di >= 3 TA',
        badgeClass: 'bg-emerald-50 text-emerald-800 border-emerald-200',
        dotClass: 'bg-emerald-600',
        activeTabClass: 'bg-emerald-900 text-yellow-200 border-emerald-950 shadow-2xs',
        hex: '#059669',
        fillRgba: 'rgba(5, 150, 105, 0.78)',
        strokeHex: '#065f46',
    },
    potensial: {
        key: 'potensial',
        label: 'Potensial',
        shortLabel: 'Potensial',
        description: 'Sudah menyumbang 1-4 siswa dan aktif pada TA terbaru',
        badgeClass: 'bg-amber-50 text-amber-800 border-amber-200',
        dotClass: 'bg-amber-500',
        activeTabClass: 'bg-amber-600 text-white border-amber-700 shadow-2xs',
        hex: '#d97706',
        fillRgba: 'rgba(245, 158, 11, 0.80)',
        strokeHex: '#b45309',
    },
    vakum: {
        key: 'vakum',
        label: 'Vakum',
        shortLabel: 'Vakum',
        description: 'Pernah menyumbang di TA terdahulu, namun 0 pada TA terbaru',
        badgeClass: 'bg-rose-50 text-rose-800 border-rose-200',
        dotClass: 'bg-rose-600',
        activeTabClass: 'bg-rose-700 text-white border-rose-800 shadow-2xs',
        hex: '#e11d48',
        fillRgba: 'rgba(225, 29, 72, 0.78)',
        strokeHex: '#9f1239',
    },
    belum_tergarap: {
        key: 'belum_tergarap',
        label: 'Belum Tergarap (0)',
        shortLabel: '0 Siswa',
        description: 'Belum ada siswa masuk (titik 0,0)',
        badgeClass: 'bg-slate-100 text-slate-700 border-slate-300',
        dotClass: 'bg-slate-400',
        activeTabClass: 'bg-slate-800 text-white border-slate-900 shadow-2xs',
        hex: '#64748b',
        fillRgba: 'rgba(100, 116, 139, 0.55)',
        strokeHex: '#475569',
    },
};

export const SOLO_RAYA_KABUPATENS = [
    'Surakarta',
    'Karanganyar',
    'Sukoharjo',
    'Sragen',
    'Wonogiri',
    'Klaten',
    'Boyolali',
];

const EMPTY_SCHOOL_INDICATORS = new Set([
    '-', '--', '---', '.', '_', 'none', 'null', 'nil',
    'tidak ada', 'belum ada', 'belum', 'tidak tahu', 'tidak diisi', 'kosong', '?',
    'tidak berkesempatan', 'belum sekolah', 'belum bersekolah',
]);

/**
 * Normalisasi nama wilayah (kabupaten / kecamatan) agar mudah disandingkan.
 */
export function normalizeWilayahKey(raw) {
    if (!raw || typeof raw !== 'string') return '';
    return raw
        .toLowerCase()
        .replace(/^(kab\.?|kota|kec\.?|kecamatan|kabupaten)\s+/i, '')
        .replace(/\bsolo\b/g, 'surakarta')
        .replace(/[^a-z0-9]/g, '');
}

/**
 * Normalisasi nama sekolah (SD/MI/PKBM) menjadi kunci pencocokan yang toleran
 * terhadap variasi penulisan seperti "SD N 01 Lalung" vs "SDN 1 Lalung" vs "SD Negeri 01 Lalung".
 */
export function normalizeSchoolMatchKey(raw) {
    if (!raw || typeof raw !== 'string') return '';
    let s = raw.trim();
    if (!s || EMPTY_SCHOOL_INDICATORS.has(s.toLowerCase())) return '';

    s = s
        .toLowerCase()
        .replace(/[`'’"]/g, '')
        .replace(/[\t\r\n]+/g, ' ')
        .replace(/[.,()/_-]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    // Satukan prefiks lembaga
    s = s
        .replace(/^(m\s*i\s*muhamm?adiyah|mi\s+muhamm?adiyah|mim)\b/, 'mim')
        .replace(/^(m\s*i\s*s|m\s*i\s*n|madrasah\s+ibtidaiyah\s+negeri)\b/, (m) => (m.includes('negeri') || m.includes('n') ? 'min' : 'mi'))
        .replace(/^(madrasah\s+ibtidaiyah|m\s*i)\b/, 'mi')
        .replace(/^(s\s*d\s*nege?ri|sd\s*neg|s\s*d\s*n|sdn)\b/, 'sdn')
        .replace(/^(s\s*d\s*islam\s*terpadu|sd\s+it|sdit)\b/, 'sdit')
        .replace(/^(s\s*d\s*muhamm?adiyah|sdm)\b/, 'sdmuhammadiyah')
        .replace(/^(s\s*d\s*swasta|sds)\b/, 'sd')
        .replace(/^(spk\s+sd)\b/, 'sd')
        .replace(/^(s\s*d)\b/, 'sd');

    // Hilangkan "mi" vs "mis" atau "mim" -> "mi muhammadiyah" disatukan pada level token
    // Hapus nol di depan nomor urut sekolah (01 -> 1, 02 -> 2)
    s = s.replace(/\b0+(\d+)\b/g, '$1');

    // Hapus spasi agar variasi spasi tidak mengganggu matching
    return s.replace(/\s+/g, '');
}

/**
 * Hitung Tier potensi sekolah berdasarkan jumlah siswa, konsistensi TA, dan keaktifan pada TA terbaru.
 */
export function classifySchoolTier({ totalStudents, taCount, latestTaCount, latestTaName }) {
    if (totalStudents <= 0) {
        return 'belum_tergarap';
    }
    if (totalStudents >= 5 || taCount >= 3) {
        return 'basis_utama';
    }
    if (latestTaName && latestTaCount === 0 && taCount > 0) {
        return 'vakum';
    }
    return 'potensial';
}

/**
 * Sandingkan data referensi SD/MI/PKBM Solo Raya dengan daftar siswa baru terfilter.
 *
 * @param {Array} referenceSchools - Data statis dari public/data/solo-raya-sd-sederajat.json
 * @param {Array} students - Data siswa hasil filter unit / TA / status masuk
 * @param {string|null} latestTaName - Nama Tahun Ajaran terbaru sebagai pembanding sekolah Vakum
 */
export function matchAndClassifySchools(referenceSchools, students, latestTaName = null) {
    const byNpsn = new Map();
    const byNormName = new Map();

    const buckets = new Map();

    referenceSchools.forEach((school, idx) => {
        const npsn = String(school.npsn || '').trim();
        const entry = {
            ...school,
            _idx: idx,
            _kabKey: normalizeWilayahKey(school.kabupaten),
            _kecKey: normalizeWilayahKey(school.kecamatan),
            matchedStudents: [],
        };

        buckets.set(npsn || `idx_${idx}`, entry);
        if (npsn) {
            byNpsn.set(npsn, entry);
        }

        const normName = normalizeSchoolMatchKey(school.nama);
        if (normName) {
            if (!byNormName.has(normName)) {
                byNormName.set(normName, []);
            }
            byNormName.get(normName).push(entry);
        }
    });

    const unrecordedStudents = [];
    const outsideOrUnmatchedMap = new Map();

    students.forEach((student) => {
        const rawNpsn = student.npsn ? String(student.npsn).trim() : '';
        const rawSchool = (student.sekolah_asal || '').trim();
        const normName = normalizeSchoolMatchKey(rawSchool);

        if (!rawNpsn && !normName) {
            unrecordedStudents.push(student);
            return;
        }

        // Lapis 1: Exact NPSN match
        let matched = rawNpsn ? byNpsn.get(rawNpsn) : null;

        // Lapis 2: Normalized Name match (+ disambiguasi wilayah jika ada nama ganda)
        if (!matched && normName) {
            const candidates = byNormName.get(normName);
            if (candidates && candidates.length === 1) {
                matched = candidates[0];
            } else if (candidates && candidates.length > 1) {
                const sKab = normalizeWilayahKey(student.kabupaten);
                const sKec = normalizeWilayahKey(student.kecamatan);
                matched =
                    candidates.find((c) => sKab && sKec && c._kabKey === sKab && c._kecKey === sKec) ||
                    candidates.find((c) => sKec && c._kecKey === sKec) ||
                    candidates.find((c) => sKab && c._kabKey === sKab) ||
                    candidates[0];
            }
        }

        if (matched) {
            matched.matchedStudents.push(student);
        } else {
            const displayLabel = rawSchool || (rawNpsn ? `NPSN ${rawNpsn}` : 'Belum Terdata');
            const key = `${normName || rawNpsn}`;
            if (!outsideOrUnmatchedMap.has(key)) {
                outsideOrUnmatchedMap.set(key, {
                    key,
                    name: displayLabel,
                    npsn: rawNpsn || '-',
                    kabupaten: student.kabupaten || '-',
                    kecamatan: student.kecamatan || '-',
                    students: [],
                });
            }
            outsideOrUnmatchedMap.get(key).students.push(student);
        }
    });

    const schools = Array.from(buckets.values()).map((entry) => {
        const list = entry.matchedStudents;
        const taBreakdown = {};

        list.forEach((st) => {
            const taKey = st.ta_masuk || (st.angkatan ? `Angk. ${st.angkatan}` : 'Lainnya');
            taBreakdown[taKey] = (taBreakdown[taKey] || 0) + 1;
        });

        // Hitung berapa TA nyata (hanya TA valid atau angkatan)
        const distinctTaKeys = Object.keys(taBreakdown).filter((k) => k !== 'Lainnya');
        const taCount = distinctTaKeys.length > 0 ? distinctTaKeys.length : (list.length > 0 ? 1 : 0);
        const totalStudents = list.length;
        const latestTaCount = latestTaName ? (taBreakdown[latestTaName] || 0) : 0;

        const tier = classifySchoolTier({
            totalStudents,
            taCount,
            latestTaCount,
            latestTaName,
        });

        const sortedTaEntries = Object.entries(taBreakdown).sort((a, b) => b[0].localeCompare(a[0]));

        return {
            npsn: entry.npsn,
            nama: entry.nama,
            bentuk: entry.bentuk,
            status: entry.status,
            alamat: entry.alamat,
            desa: entry.desa,
            kecamatan: entry.kecamatan,
            kabupaten: entry.kabupaten,
            lat: entry.lat,
            lng: entry.lng,
            coord_source: entry.coord_source,
            totalStudents,
            taCount,
            latestTaCount,
            taBreakdown: sortedTaEntries,
            tier,
            students: [...list].sort((a, b) => (a.nama || '').localeCompare(b.nama || '')),
        };
    });

    const unmatchedGroups = Array.from(outsideOrUnmatchedMap.values()).sort(
        (a, b) => b.students.length - a.students.length
    );

    return {
        schools,
        unrecordedStudents,
        unmatchedGroups,
    };
}

