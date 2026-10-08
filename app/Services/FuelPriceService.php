<?php

namespace App\Services;

use App\Models\SystemSetting;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Fetches current Malaysian fuel prices from the official data.gov.my open
 * data API. The API is free, needs no key, and is refreshed weekly in line
 * with the government's Automatic Pricing Mechanism announcements.
 *
 * The fare advisor used to rely on RON95, RON97 and diesel prices typed
 * directly into the code, and those had fallen badly out of date.
 */
class FuelPriceService
{
    private const CACHE_KEY = 'fuel_prices_current';
    private const CACHE_TTL_HOURS = 20;
    private const API_URL = 'https://api.data.gov.my/data-catalogue?id=fuelprice';

    // Only used when the API cannot be reached. These were the correct
    // prices when this file was written, so treat them as a last resort
    // rather than as real data.
    private const FALLBACK = [
        'RON95'  => ['budi' => 1.99, 'market' => 3.77],
        'RON97'  => ['market' => 4.25],
        'Diesel' => ['market' => 4.67],
        'as_of'  => null,
        'source' => 'fallback',
    ];

    public function current(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHours(self::CACHE_TTL_HOURS), function () {
            return $this->fetchFromApi() ?? $this->dbFallback() ?? self::FALLBACK;
        });
    }

    /**
     * Called after an admin edits the fuel-price fallback in System Settings
     * so the new values take effect immediately instead of waiting up to
     * CACHE_TTL_HOURS for the cached figure to expire on its own.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * An override the admin can set, used when the live API cannot be
     * reached. It sits between the API call and the FALLBACK constant above,
     * so the constant is only reached when the admin has not set anything.
     */
    private function dbFallback(): ?array
    {
        $budi = SystemSetting::get('fuel_price_ron95_budi');
        $ron95 = SystemSetting::get('fuel_price_ron95_market');
        $ron97 = SystemSetting::get('fuel_price_ron97_market');
        $diesel = SystemSetting::get('fuel_price_diesel_market');

        if ($budi === null || $ron95 === null || $ron97 === null || $diesel === null) {
            return null;
        }

        return [
            'RON95' => ['budi' => (float) $budi, 'market' => (float) $ron95],
            'RON97' => ['market' => (float) $ron97],
            'Diesel' => ['market' => (float) $diesel],
            'as_of' => null,
            'source' => 'admin_override',
        ];
    }

    private function fetchFromApi(): ?array
    {
        try {
            $client = new Client(['timeout' => 8]);
            $response = $client->get(self::API_URL);
            $rows = json_decode((string) $response->getBody(), true);

            if (! is_array($rows)) {
                return null;
            }

            $levels = array_values(array_filter($rows, fn ($row) => ($row['series_type'] ?? null) === 'level'));
            if ($levels === []) {
                return null;
            }

            usort($levels, fn ($a, $b) => strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? '')));
            $latest = end($levels);

            $ron95Market = (float) ($latest['ron95'] ?? 0);
            $ron97       = (float) ($latest['ron97'] ?? 0);
            $diesel      = (float) ($latest['diesel'] ?? 0);
            $ron95Budi   = (float) ($latest['ron95_budi95'] ?? 0);

            if ($ron95Market <= 0 || $ron97 <= 0 || $diesel <= 0) {
                return null;
            }

            return [
                'RON95'  => ['budi' => $ron95Budi > 0 ? $ron95Budi : self::FALLBACK['RON95']['budi'], 'market' => $ron95Market],
                'RON97'  => ['market' => $ron97],
                'Diesel' => ['market' => $diesel],
                'as_of'  => (string) ($latest['date'] ?? ''),
                'source' => 'data.gov.my',
            ];
        } catch (\Throwable $e) {
            Log::warning('Fuel price API fetch failed: ' . $e->getMessage());

            return null;
        }
    }
}
