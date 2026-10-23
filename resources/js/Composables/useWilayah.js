import { ref } from 'vue';

// In-memory cache to avoid redundant network requests during the session
const cache = {
    provinces: null,
    regencies: {},
    districts: {},
    villages: {},
};

const BASE_URL = 'https://emsifa.github.io/api-wilayah-indonesia/api';

/**
 * Convert all-caps string from API into clean Title Case
 * e.g. "JAWA TENGAH" -> "Jawa Tengah", "KABUPATEN KARANGANYAR" -> "Kabupaten Karanganyar"
 */
export function toTitleCase(str) {
    if (!str) return '';
    return str
        .toLowerCase()
        .replace(/(?:^|\s|-|\/)\S/g, (m) => m.toUpperCase())
        .replace(/\bDki\b/g, 'DKI')
        .replace(/\bDi\b/g, 'DI');
}

/**
 * Flexible name matcher that ignores common prefixes (Kabupaten, Kota, etc.)
 */
export function isMatch(name1, name2) {
    if (!name1 || !name2) return false;
    const clean = (s) =>
        s
            .toString()
            .toLowerCase()
            .replace(/^(kabupaten|kab\.|kab|kota|provinsi|prov\.|prov|kecamatan|kec\.|kec|desa|kelurahan|ds\.|kel\.)\s+/i, '')
            .trim();
    return clean(name1) === clean(name2) || name1.toString().toLowerCase().trim() === name2.toString().toLowerCase().trim();
}

export function useWilayah() {
    const isLoadingProvinces = ref(false);

    async function getProvinces() {
        if (cache.provinces && cache.provinces.length > 0) {
            return cache.provinces;
        }
        isLoadingProvinces.value = true;
        try {
            const res = await fetch(`${BASE_URL}/provinces.json`);
            if (!res.ok) throw new Error(`HTTP error ${res.status}`);
            const data = await res.json();
            const mapped = data
                .map((item) => ({
                    id: String(item.id),
                    name: toTitleCase(item.name),
                    rawName: item.name,
                }))
                .sort((a, b) => a.name.localeCompare(b.name));
            cache.provinces = mapped;
            return mapped;
        } catch (e) {
            console.error('Failed to fetch provinces from API:', e);
            return [];
        } finally {
            isLoadingProvinces.value = false;
        }
    }

    async function getRegencies(provinceId) {
        if (!provinceId) return [];
        const key = String(provinceId);
        if (cache.regencies[key]) return cache.regencies[key];
        try {
            const res = await fetch(`${BASE_URL}/regencies/${key}.json`);
            if (!res.ok) throw new Error(`HTTP error ${res.status}`);
            const data = await res.json();
            const mapped = data
                .map((item) => ({
                    id: String(item.id),
                    province_id: String(item.province_id),
                    name: toTitleCase(item.name),
                    rawName: item.name,
                }))
                .sort((a, b) => a.name.localeCompare(b.name));
            cache.regencies[key] = mapped;
            return mapped;
        } catch (e) {
            console.error(`Failed to fetch regencies for province ${provinceId}:`, e);
            return [];
        }
    }

    async function getDistricts(regencyId) {
        if (!regencyId) return [];
        const key = String(regencyId);
        if (cache.districts[key]) return cache.districts[key];
        try {
            const res = await fetch(`${BASE_URL}/districts/${key}.json`);
            if (!res.ok) throw new Error(`HTTP error ${res.status}`);
            const data = await res.json();
            const mapped = data
                .map((item) => ({
                    id: String(item.id),
                    regency_id: String(item.regency_id),
                    name: toTitleCase(item.name),
                    rawName: item.name,
                }))
                .sort((a, b) => a.name.localeCompare(b.name));
            cache.districts[key] = mapped;
            return mapped;
        } catch (e) {
            console.error(`Failed to fetch districts for regency ${regencyId}:`, e);
            return [];
        }
    }

    async function getVillages(districtId) {
        if (!districtId) return [];
        const key = String(districtId);
        if (cache.villages[key]) return cache.villages[key];
        try {
            const res = await fetch(`${BASE_URL}/villages/${key}.json`);
            if (!res.ok) throw new Error(`HTTP error ${res.status}`);
            const data = await res.json();
            const mapped = data
                .map((item) => ({
                    id: String(item.id),
                    district_id: String(item.district_id),
                    name: toTitleCase(item.name),
                    rawName: item.name,
                }))
                .sort((a, b) => a.name.localeCompare(b.name));
            cache.villages[key] = mapped;
            return mapped;
        } catch (e) {
            console.error(`Failed to fetch villages for district ${districtId}:`, e);
            return [];
        }
    }

    return {
        getProvinces,
        getRegencies,
        getDistricts,
        getVillages,
        toTitleCase,
        isMatch,
        isLoadingProvinces,
    };
}
