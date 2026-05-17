<?php

namespace App\Services;

class CurrencyService
{
    /** Rates relative to PKR (base). 1 unit of currency = X PKR */
    private static array $rates = [
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
        'QAR' => 76.9,
        'KWD' => 912.0,
        'BHD' => 742.0,
        'OMR' => 727.0,
        'MYR' => 62.5,
        'SGD' => 207.0,
        'TRY' => 8.7,
        'CHF' => 312.0,
    ];

    public static function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) {
            return $amount;
        }

        $fromRate = self::$rates[strtoupper($from)] ?? 1.0;
        $toRate = self::$rates[strtoupper($to)] ?? 1.0;

        return round(($amount * $fromRate) / $toRate, 2);
    }

    public static function rate(string $from, string $to): float
    {
        if ($from === $to) {
            return 1.0;
        }

        $fromRate = self::$rates[strtoupper($from)] ?? 1.0;
        $toRate = self::$rates[strtoupper($to)] ?? 1.0;

        return round($fromRate / $toRate, 6);
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
            'QAR' => 'Qatari Riyal',
            'KWD' => 'Kuwaiti Dinar',
            'BHD' => 'Bahraini Dinar',
            'OMR' => 'Omani Rial',
            'MYR' => 'Malaysian Ringgit',
            'SGD' => 'Singapore Dollar',
            'TRY' => 'Turkish Lira',
            'CHF' => 'Swiss Franc',
        ];
    }
}
