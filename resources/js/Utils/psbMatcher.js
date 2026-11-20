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

export const PSB_WELFARE_TIERS = {
    sejahtera_basis: {
        key: 'sejahtera_basis',
        label: 'Basis Dekat (<= 10 km)',
        shortLabel: 'Basis <=10km',
        description: 'Dalam radius <= 10 km dari kampus & >= 3 siswa domisili',
        badgeClass: 'bg-emerald-50 text-emerald-800 border-emerald-200',
        dotClass: 'bg-emerald-600',
        activeTabClass: 'bg-emerald-900 text-yellow-200 border-emerald-950 shadow-2xs',
        hex: '#059669',
        fillRgba: 'rgba(5, 150, 105, 0.80)',
        strokeHex: '#065f46',
    },
    sejahtera_potensial: {
        key: 'sejahtera_potensial',
        label: 'Target Dekat (<= 10 km)',
        shortLabel: 'Target <=10km',
        description: 'Dalam radius <= 10 km dari kampus, baru 0-2 siswa domisili',
        badgeClass: 'bg-amber-50 text-amber-800 border-amber-200',
        dotClass: 'bg-amber-500',
        activeTabClass: 'bg-amber-600 text-white border-amber-700 shadow-2xs',
        hex: '#d97706',
        fillRgba: 'rgba(245, 158, 11, 0.80)',
        strokeHex: '#b45309',
    },
    rentan_terjangkau: {
        key: 'rentan_terjangkau',
        label: 'Basis Luar (> 10 km)',
        shortLabel: 'Basis >10km',
        description: 'Luar radius > 10 km dari kampus namun >= 3 siswa domisili',
        badgeClass: 'bg-sky-50 text-sky-800 border-sky-200',
        dotClass: 'bg-sky-600',
        activeTabClass: 'bg-sky-700 text-white border-sky-800 shadow-2xs',
        hex: '#0284c7',
        fillRgba: 'rgba(2, 132, 199, 0.78)',
        strokeHex: '#0369a1',
    },
    rentan_minim: {
        key: 'rentan_minim',
        label: 'Luar Radius (0-2)',
        shortLabel: 'Luar (0-2)',
        description: 'Luar radius > 10 km & baru 0-2 siswa domisili',
        badgeClass: 'bg-slate-100 text-slate-700 border-slate-300',
        dotClass: 'bg-slate-400',
        activeTabClass: 'bg-slate-800 text-white border-slate-900 shadow-2xs',
        hex: '#64748b',
        fillRgba: 'rgba(100, 116, 139, 0.55)',
        strokeHex: '#475569',
    },
};

export const WELFARE_LEVEL_META = {
    sejahtera_tinggi: {
        label: 'Sejahtera Tinggi (RWI Q4)',
        shortLabel: 'Sejahtera',
        badgeClass: 'bg-emerald-50 text-emerald-800 border-emerald-200',
        hex: '#059669',
    },
    menengah_atas: {
        label: 'Menengah Atas (RWI Q3)',
        shortLabel: 'Menengah+',
        badgeClass: 'bg-teal-50 text-teal-800 border-teal-200',
        hex: '#34d399',
    },
    menengah_bawah: {
        label: 'Menengah Bawah (RWI Q2)',
        shortLabel: 'Menengah-',
        badgeClass: 'bg-amber-50 text-amber-800 border-amber-200',
        hex: '#fbbf24',
    },
    rentan_tinggi: {
        label: 'Ekonomi Rendah (RWI Q1)',
        shortLabel: 'Rendah',
        badgeClass: 'bg-rose-50 text-rose-800 border-rose-200',
        hex: '#f97316',
    },
};

export const CAMPUS_RING_META = {
    1: { label: 'Ring 1 (<= 5 km)', shortLabel: 'Ring 1', badgeClass: 'bg-emerald-50 text-emerald-800 border-emerald-200' },
    2: { label: 'Ring 2 (5-10 km)', shortLabel: 'Ring 2', badgeClass: 'bg-sky-50 text-sky-800 border-sky-200' },
    3: { label: 'Ring 3 (10-15 km)', shortLabel: 'Ring 3', badgeClass: 'bg-amber-50 text-amber-800 border-amber-200' },
    4: { label: 'Ring 4 (> 15 km)', shortLabel: 'Ring 4', badgeClass: 'bg-slate-100 text-slate-700 border-slate-300' },
};

export function normalizeDesaKey(raw) {
    if (!raw || typeof raw !== 'string') return '';
    return raw
        .toLowerCase()
        .replace(/^(desa|kel\.?|kelurahan|ds\.?)\s+/i, '')
        .replace(/[^a-z0-9]/g, '');
}

export function classifyVillageWelfareTier({ campus_ring, campus_km, totalStudents }) {
    const isNearCampus = Number(campus_ring || 4) <= 2 || Number(campus_km || 999) <= 10;
    if (isNearCampus && totalStudents >= 3) {
        return 'sejahtera_basis';
    }
    if (isNearCampus) {
        return 'sejahtera_potensial';
    }
    if (totalStudents >= 3) {
        return 'rentan_terjangkau';
    }
    return 'rentan_minim';
}

/**
 * Sandingkan data referensi kesejahteraan desa Solo Raya (Jatengprov P1-P4) dengan domisili siswa masuk.
 *
 * @param {Array} referenceVillages - Data statis dari public/data/solo-raya-desa-kemiskinan.json
 * @param {Array} students - Data siswa hasil filter unit / TA / status masuk
 * @param {Array} matchedSchools - Data sekolah terpadankan (opsional, untuk menghitung SD di desa)
 * @param {string|null} latestTaName - Nama Tahun Ajaran terbaru
 */
export function matchAndClassifyVillages(referenceVillages, students, matchedSchools = [], latestTaName = null) {
    const byExact = new Map();
    const byDesaKab = new Map();
    const byDesaKec = new Map();
    const buckets = new Map();

    referenceVillages.forEach((v, idx) => {
        const dKey = normalizeDesaKey(v.desa);
        const kecKey = normalizeWilayahKey(v.kecamatan);
        const kabKey = normalizeWilayahKey(v.kabupaten);

        const entry = {
            ...v,
            _idx: idx,
            _dKey: dKey,
            _kecKey: kecKey,
            _kabKey: kabKey,
            matchedStudents: [],
            schools: [],
            reachedSchoolsCount: 0,
        };

        const exactKey = `${dKey}_${kecKey}_${kabKey}`;
        buckets.set(v.kode || `v_${idx}`, entry);
        byExact.set(exactKey, entry);

        const dkKey = `${dKey}_${kabKey}`;
        if (!byDesaKab.has(dkKey)) byDesaKab.set(dkKey, []);
        byDesaKab.get(dkKey).push(entry);

        const dcKey = `${dKey}_${kecKey}`;
        if (!byDesaKec.has(dcKey)) byDesaKec.set(dcKey, []);
        byDesaKec.get(dcKey).push(entry);
    });

    // Tautkan jumlah sekolah referensi di desa terkait
    (matchedSchools || []).forEach((s) => {
        const dKey = normalizeDesaKey(s.desa);
        if (!dKey) return;
        const kecKey = normalizeWilayahKey(s.kecamatan);
        const kabKey = normalizeWilayahKey(s.kabupaten);
        const target =
            byExact.get(`${dKey}_${kecKey}_${kabKey}`) ||
            (byDesaKab.get(`${dKey}_${kabKey}`)?.length === 1 ? byDesaKab.get(`${dKey}_${kabKey}`)[0] : null);
        if (target) {
            target.schools.push(s);
            if (s.totalStudents > 0) target.reachedSchoolsCount++;
        }
    });

    const unrecordedStudents = [];
    const unmatchedMap = new Map();

    (students || []).forEach((student) => {
        const dKey = normalizeDesaKey(student.desa);
        const kecKey = normalizeWilayahKey(student.kecamatan);
        const kabKey = normalizeWilayahKey(student.kabupaten);

        if (!dKey) {
            unrecordedStudents.push(student);
            return;
        }

        let matched = byExact.get(`${dKey}_${kecKey}_${kabKey}`) || null;
        if (!matched && kabKey) {
            const cands = byDesaKab.get(`${dKey}_${kabKey}`);
            if (cands && cands.length === 1) {
                matched = cands[0];
            }
        }
        if (!matched && kecKey) {
            const cands = byDesaKec.get(`${dKey}_${kecKey}`);
            if (cands && cands.length === 1) {
                matched = cands[0];
            }
        }

        if (matched) {
            matched.matchedStudents.push(student);
        } else {
            const key = `${dKey}_${kecKey}_${kabKey}`;
            if (!unmatchedMap.has(key)) {
                unmatchedMap.set(key, {
                    key,
                    desa: student.desa || '-',
                    kecamatan: student.kecamatan || '-',
                    kabupaten: student.kabupaten || '-',
                    students: [],
                });
            }
            unmatchedMap.get(key).students.push(student);
        }
    });

    const villages = Array.from(buckets.values()).map((entry) => {
        const list = entry.matchedStudents;
        const taBreakdown = {};

        list.forEach((st) => {
            const taKey = st.ta_masuk || (st.angkatan ? `Angk. ${st.angkatan}` : 'Lainnya');
            taBreakdown[taKey] = (taBreakdown[taKey] || 0) + 1;
        });

        const distinctTaKeys = Object.keys(taBreakdown).filter((k) => k !== 'Lainnya');
        const taCount = distinctTaKeys.length > 0 ? distinctTaKeys.length : (list.length > 0 ? 1 : 0);
        const totalStudents = list.length;
        const latestTaCount = latestTaName ? (taBreakdown[latestTaName] || 0) : 0;

        const tier = classifyVillageWelfareTier({
            campus_ring: entry.campus_ring,
            campus_km: entry.campus_km,
            totalStudents,
        });

        const sortedTaEntries = Object.entries(taBreakdown).sort((a, b) => b[0].localeCompare(a[0]));

        return {
            kode: entry.kode,
            desa: entry.desa,
            kecamatan: entry.kecamatan,
            kabupaten: entry.kabupaten,
            lat: entry.lat,
            lng: entry.lng,
            rwi: entry.rwi ?? 0,
            campus_km: entry.campus_km ?? 0,
            campus_ring: entry.campus_ring ?? 4,
            toll_km: entry.toll_km ?? 0,
            toll_name: entry.toll_name ?? '-',
            welfare_score: entry.welfare_score,
            welfare_level: entry.welfare_level,
            totalStudents,
            taCount,
            latestTaCount,
            taBreakdown: sortedTaEntries,
            schoolsCount: entry.schools.length,
            reachedSchoolsCount: entry.reachedSchoolsCount,
            schools: entry.schools,
            tier,
            students: [...list].sort((a, b) => (a.nama || '').localeCompare(b.nama || '')),
        };
    });

    const unmatchedGroups = Array.from(unmatchedMap.values()).sort(
        (a, b) => b.students.length - a.students.length
    );

    return {
        villages,
        unrecordedStudents,
        unmatchedGroups,
    };
}

