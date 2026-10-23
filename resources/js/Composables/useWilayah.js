import { ref } from 'vue';

// In-memory cache to avoid redundant network requests during the session
const cache = {
    provinces: null,
    regencies: {},
    districts: {},
    villages: {},
};

const LOCAL_BASE_URL = '/api/wilayah';
const EXTERNAL_FALLBACK_URL = 'https://www.emsifa.com/api-wilayah-indonesia/api';

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

/**
 * Safe fetch helper that queries local Laravel proxy first, then falls back to direct HTTPS external API.
 */
async function fetchWilayah(localEndpoint, externalEndpoint) {
    // 1. Try Local Laravel Proxy Endpoint (same-origin, cached server-side)
    try {
        const res = await fetch(`${LOCAL_BASE_URL}${localEndpoint}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (res.ok) {
            const data = await res.json();
            if (Array.isArray(data) && data.length > 0) {
                return data;
            }
        }
    } catch (e) {
        console.warn(`Local wilayah fetch failed for ${localEndpoint}, trying external fallback:`, e);
    }

    // 2. Try Direct External HTTPS Endpoint
    try {
        const res = await fetch(`${EXTERNAL_FALLBACK_URL}${externalEndpoint}`);
        if (res.ok) {
            const data = await res.json();
            if (Array.isArray(data)) {
                return data;
            }
        }
    } catch (e) {
        console.error(`External wilayah fetch failed for ${externalEndpoint}:`, e);
    }

    return [];
}

export function useWilayah() {
    const isLoadingProvinces = ref(false);

    async function getProvinces() {
        if (cache.provinces && cache.provinces.length > 0) {
            return cache.provinces;
        }
        isLoadingProvinces.value = true;
        try {
            const data = await fetchWilayah('/provinces', '/provinces.json');
            const mapped = data
                .map((item) => ({
                    id: String(item.id),
                    name: toTitleCase(item.name),
                    rawName: item.name,
                }))
                .sort((a, b) => a.name.localeCompare(b.name));
            if (mapped.length > 0) {
                cache.provinces = mapped;
            }
            return mapped;
        } finally {
            isLoadingProvinces.value = false;
        }
    }

    async function getRegencies(provinceId) {
        if (!provinceId) return [];
        const key = String(provinceId);
        if (cache.regencies[key] && cache.regencies[key].length > 0) {
            return cache.regencies[key];
        }
        const data = await fetchWilayah(`/regencies/${key}`, `/regencies/${key}.json`);
        const mapped = data
            .map((item) => ({
                id: String(item.id),
                province_id: String(item.province_id || key),
                name: toTitleCase(item.name),
                rawName: item.name,
            }))
            .sort((a, b) => a.name.localeCompare(b.name));
        if (mapped.length > 0) {
            cache.regencies[key] = mapped;
        }
        return mapped;
    }

    async function getDistricts(regencyId) {
        if (!regencyId) return [];
        const key = String(regencyId);
        if (cache.districts[key] && cache.districts[key].length > 0) {
            return cache.districts[key];
        }
        const data = await fetchWilayah(`/districts/${key}`, `/districts/${key}.json`);
        const mapped = data
            .map((item) => ({
                id: String(item.id),
                regency_id: String(item.regency_id || key),
                name: toTitleCase(item.name),
                rawName: item.name,
            }))
            .sort((a, b) => a.name.localeCompare(b.name));
        if (mapped.length > 0) {
            cache.districts[key] = mapped;
        }
        return mapped;
    }

    async function getVillages(districtId) {
        if (!districtId) return [];
        const key = String(districtId);
        if (cache.villages[key] && cache.villages[key].length > 0) {
            return cache.villages[key];
        }
        const data = await fetchWilayah(`/villages/${key}`, `/villages/${key}.json`);
        const mapped = data
            .map((item) => ({
                id: String(item.id),
                district_id: String(item.district_id || key),
                name: toTitleCase(item.name),
                rawName: item.name,
            }))
            .sort((a, b) => a.name.localeCompare(b.name));
        if (mapped.length > 0) {
            cache.villages[key] = mapped;
        }
        return mapped;
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
