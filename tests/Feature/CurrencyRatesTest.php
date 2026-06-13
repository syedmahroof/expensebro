<?php

use App\Services\CurrencyService;
use Illuminate\Support\Facades\Http;

test('converts using live exchange rates when the api responds', function () {
    config()->set('services.exchange_rates.enabled', true);
    Http::preventStrayRequests();
    Http::fake([
        'open.er-api.com/*' => Http::response([
            'result' => 'success',
            'rates' => [
                'PKR' => 1,
                'USD' => 0.004, // 1 USD = 250 PKR
                'INR' => 0.25,  // 1 INR = 4 PKR
            ],
        ]),
    ]);

    expect(CurrencyService::convert(1, 'USD', 'PKR'))->toBe(250.0)
        ->and(CurrencyService::convert(100, 'INR', 'PKR'))->toBe(400.0);
});

test('live rates are cached so the api is only called once', function () {
    config()->set('services.exchange_rates.enabled', true);
    Http::preventStrayRequests();
    Http::fake([
        'open.er-api.com/*' => Http::response(['result' => 'success', 'rates' => ['PKR' => 1, 'USD' => 0.004]]),
    ]);

    CurrencyService::convert(1, 'USD', 'PKR');
    CurrencyService::convert(5, 'USD', 'PKR');
    CurrencyService::rate('USD', 'PKR');

    Http::assertSentCount(1);
});

test('falls back to static rates when the api fails', function () {
    config()->set('services.exchange_rates.enabled', true);
    Http::preventStrayRequests();
    Http::fake([
        'open.er-api.com/*' => Http::response(null, 500),
    ]);

    expect(CurrencyService::convert(1, 'KWD', 'PKR'))->toBe(912.0);
});

test('a failed fetch is not retried on every conversion', function () {
    config()->set('services.exchange_rates.enabled', true);
    Http::preventStrayRequests();
    Http::fake([
        'open.er-api.com/*' => Http::response(null, 500),
    ]);

    CurrencyService::convert(1, 'USD', 'PKR');
    CurrencyService::convert(1, 'EUR', 'PKR');

    Http::assertSentCount(1);
});

test('currencies missing from the api response keep their fallback rate', function () {
    config()->set('services.exchange_rates.enabled', true);
    Http::preventStrayRequests();
    Http::fake([
        'open.er-api.com/*' => Http::response(['result' => 'success', 'rates' => ['PKR' => 1, 'USD' => 0.004]]),
    ]);

    expect(CurrencyService::convert(1, 'USD', 'PKR'))->toBe(250.0)
        ->and(CurrencyService::convert(1, 'KWD', 'PKR'))->toBe(912.0);
});

test('no api call is made when live rates are disabled', function () {
    Http::fake();

    expect(CurrencyService::convert(1, 'USD', 'PKR'))->toBe(280.0);

    Http::assertNothingSent();
});
