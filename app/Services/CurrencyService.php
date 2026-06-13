<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class CurrencyService
{
    private const RATES_CACHE_KEY = 'currency:rates:pkr';

    private const FETCH_FAILED_CACHE_KEY = 'currency:rates:fetch-failed';

    /** Fallback rates relative to PKR (base), used when live rates are unavailable. 1 unit of currency = X PKR */
    private static array $fallbackRates = [
        'PKR' => 1.0,
        'USD' => 280.0,
        'AED' => 76.2,
        'EUR' => 304.0,
        'GBP' => 355.0,
        'SAR' => 74.6,
        'CAD' => 205.0,
        'AUD' => 181.0,
        'JPY' => 1.88,
        'CNY' => 38.6,
        'INR' => 3.36,
        'BDT' => 2.54,
        'NPR' => 2.11,
        'LKR' => 0.93,
        'QAR' => 76.9,
        'KWD' => 912.0,
        'BHD' => 742.0,
        'OMR' => 727.0,
        'MYR' => 62.5,
        'SGD' => 207.0,
        'IDR' => 0.0175,
        'THB' => 7.8,
        'VND' => 0.011,
        'PHP' => 4.9,
        'KRW' => 0.2,
        'HKD' => 35.9,
        'NZD' => 170.0,
        'TRY' => 8.7,
        'CHF' => 312.0,
        'SEK' => 26.7,
        'NOK' => 26.4,
        'DKK' => 40.6,
        'RUB' => 3.1,
        'ILS' => 75.7,
        'ZAR' => 15.1,
        'EGP' => 5.8,
        'NGN' => 0.18,
        'KES' => 2.17,
        'BRL' => 50.9,
        'MXN' => 15.6,
    ];

    public static function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) {
            return $amount;
        }

        $rates = self::rates();
        $fromRate = $rates[strtoupper($from)] ?? 1.0;
        $toRate = $rates[strtoupper($to)] ?? 1.0;

        return round(($amount * $fromRate) / $toRate, 2);
    }

    public static function rate(string $from, string $to): float
    {
        if ($from === $to) {
            return 1.0;
        }

        $rates = self::rates();
        $fromRate = $rates[strtoupper($from)] ?? 1.0;
        $toRate = $rates[strtoupper($to)] ?? 1.0;

        return round($fromRate / $toRate, 6);
    }

    /**
     * Current rates relative to PKR: live rates when available, otherwise the static fallback.
     *
     * @return array<string, float>
     */
    public static function rates(): array
    {
        if (! config('services.exchange_rates.enabled')) {
            return self::$fallbackRates;
        }

        $cached = Cache::get(self::RATES_CACHE_KEY);

        if (is_array($cached)) {
            return $cached;
        }

        if (Cache::has(self::FETCH_FAILED_CACHE_KEY)) {
            return self::$fallbackRates;
        }

        $live = self::fetchLiveRates();

        if ($live === null) {
            Cache::put(self::FETCH_FAILED_CACHE_KEY, true, now()->addMinutes(10));

            return self::$fallbackRates;
        }

        Cache::put(self::RATES_CACHE_KEY, $live, now()->addHours((int) config('services.exchange_rates.cache_hours', 12)));

        return $live;
    }

    /**
     * Fetch PKR-based rates from the exchange rate API. The API returns how many
     * units of each currency 1 PKR buys, so the PKR value of 1 unit is the inverse.
     *
     * @return array<string, float>|null
     */
    private static function fetchLiveRates(): ?array
    {
        try {
            $response = Http::connectTimeout(3)->timeout(5)->get(config('services.exchange_rates.url'));
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful() || $response->json('result') !== 'success') {
            return null;
        }

        $apiRates = $response->json('rates');

        if (! is_array($apiRates)) {
            return null;
        }

        $rates = self::$fallbackRates;

        foreach (array_keys(self::$fallbackRates) as $code) {
            $perPkr = $apiRates[$code] ?? null;

            if (is_numeric($perPkr) && $perPkr > 0) {
                $rates[$code] = round(1 / $perPkr, 6);
            }
        }

        return $rates;
    }

    public static function isSupported(string $code): bool
    {
        return array_key_exists(strtoupper($code), self::supported());
    }

    /** @return array<int, string> */
    public static function codes(): array
    {
        return array_keys(self::supported());
    }

    /** @return array<string, string> */
    public static function supported(): array
    {
        return [
            'PKR' => 'Pakistani Rupee',
            'USD' => 'US Dollar',
            'AED' => 'UAE Dirham',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'SAR' => 'Saudi Riyal',
            'CAD' => 'Canadian Dollar',
            'AUD' => 'Australian Dollar',
            'JPY' => 'Japanese Yen',
            'CNY' => 'Chinese Yuan',
            'INR' => 'Indian Rupee',
            'BDT' => 'Bangladeshi Taka',
            'NPR' => 'Nepalese Rupee',
            'LKR' => 'Sri Lankan Rupee',
            'QAR' => 'Qatari Riyal',
            'KWD' => 'Kuwaiti Dinar',
            'BHD' => 'Bahraini Dinar',
            'OMR' => 'Omani Rial',
            'MYR' => 'Malaysian Ringgit',
            'SGD' => 'Singapore Dollar',
            'IDR' => 'Indonesian Rupiah',
            'THB' => 'Thai Baht',
            'VND' => 'Vietnamese Dong',
            'PHP' => 'Philippine Peso',
            'KRW' => 'South Korean Won',
            'HKD' => 'Hong Kong Dollar',
            'NZD' => 'New Zealand Dollar',
            'TRY' => 'Turkish Lira',
            'CHF' => 'Swiss Franc',
            'SEK' => 'Swedish Krona',
            'NOK' => 'Norwegian Krone',
            'DKK' => 'Danish Krone',
            'RUB' => 'Russian Ruble',
            'ILS' => 'Israeli Shekel',
            'ZAR' => 'South African Rand',
            'EGP' => 'Egyptian Pound',
            'NGN' => 'Nigerian Naira',
            'KES' => 'Kenyan Shilling',
            'BRL' => 'Brazilian Real',
            'MXN' => 'Mexican Peso',
        ];
    }
}
