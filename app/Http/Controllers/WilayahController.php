<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WilayahController extends Controller
{
    private const BASE_URL_PRIMARY = 'https://www.emsifa.com/api-wilayah-indonesia/api';

    private const BASE_URL_FALLBACK = 'https://raw.githubusercontent.com/emsifa/api-wilayah-indonesia/gh-pages/api';

    /**
     * Retrieve list of all provinces in Indonesia.
     */
    public function provinces(): JsonResponse
    {
        $data = $this->getCachedOrFetch('wilayah_provinces', fn (): array => $this->fetchWilayahData('/provinces.json'));

        return response()->json($data);
    }

    /**
     * Retrieve list of regencies/cities in a province.
     */
    public function regencies(string $provinceId): JsonResponse
    {
        $data = $this->getCachedOrFetch(
            "wilayah_regencies_{$provinceId}",
            fn (): array => $this->fetchWilayahData("/regencies/{$provinceId}.json")
        );

        return response()->json($data);
    }

    /**
     * Retrieve list of districts in a regency/city.
     */
    public function districts(string $regencyId): JsonResponse
    {
        $data = $this->getCachedOrFetch(
            "wilayah_districts_{$regencyId}",
            fn (): array => $this->fetchWilayahData("/districts/{$regencyId}.json")
        );

        return response()->json($data);
    }

    /**
     * Retrieve list of villages in a district.
     */
    public function villages(string $districtId): JsonResponse
    {
        $data = $this->getCachedOrFetch(
            "wilayah_villages_{$districtId}",
            fn (): array => $this->fetchWilayahData("/villages/{$districtId}.json")
        );

        return response()->json($data);
    }

    /**
     * Safe caching helper using file cache store with fallback if cache driver fails.
     *
     * @param  \Closure(): array<int, array<string, mixed>>  $callback
     * @return array<int, array<string, mixed>>
     */
    private function getCachedOrFetch(string $key, \Closure $callback): array
    {
        try {
            return Cache::store('file')->rememberForever($key, $callback);
        } catch (\Throwable $e) {
            Log::warning("File cache error for {$key}, executing direct callback: ".$e->getMessage());

            return $callback();
        }
    }

    /**
     * Helper to fetch data with primary and secondary fallback.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchWilayahData(string $endpoint): array
    {
        try {
            $response = Http::timeout(8)
                ->withoutVerifying()
                ->get(self::BASE_URL_PRIMARY.$endpoint);

            if ($response->successful()) {
                $json = $response->json();
                if (is_array($json) && ! empty($json)) {
                    return $json;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Wilayah primary fetch failed for {$endpoint}: ".$e->getMessage());
        }

        try {
            $fallbackResponse = Http::timeout(8)
                ->withoutVerifying()
                ->get(self::BASE_URL_FALLBACK.$endpoint);

            if ($fallbackResponse->successful()) {
                $json = $fallbackResponse->json();
                if (is_array($json) && ! empty($json)) {
                    return $json;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Wilayah fallback fetch failed for {$endpoint}: ".$e->getMessage());
        }

        return [];
    }
}
