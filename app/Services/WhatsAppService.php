<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send a WhatsApp OTP message.
     *
     * Swap this body for Twilio, Meta Cloud API, or any provider.
     * Twilio example:
     *   $twilio = new \Twilio\Rest\Client(config('services.twilio.sid'), config('services.twilio.token'));
     *   $twilio->messages->create("whatsapp:{$phone}", [
     *       'from' => 'whatsapp:' . config('services.twilio.whatsapp_from'),
     *       'body' => "Your ExpenseBro OTP is: {$code}",
     *   ]);
     */
    public function sendOtp(string $phone, string $code): bool
    {
        // TODO: replace with real provider
        Log::info('[WhatsApp OTP]', ['phone' => $phone, 'code' => $code]);

        return true;
    }
}
