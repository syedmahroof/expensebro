<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

class OtpController extends Controller
{
    public function __construct(private WhatsAppService $whatsApp) {}

    public function showPhone(): Response
    {
        return Inertia::render('auth/OtpPhone');
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => 'required|string|min:7|max:20|regex:/^\+?[0-9\s\-\(\)]+$/',
        ]);

        $phone = preg_replace('/[^0-9+]/', '', $request->phone);

        $key = 'otp-send:' . $phone;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors(['phone' => "Too many attempts. Try again in {$seconds} seconds."]);
        }

        RateLimiter::hit($key, 60);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->whatsApp->sendOtp($phone, $code);

        return redirect()->route('otp.verify', ['phone' => base64_encode($phone)]);
    }

    public function showVerify(string $phone): Response
    {
        return Inertia::render('auth/OtpVerify', [
            'phone' => base64_decode($phone),
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'code' => 'required|string|size:6',
        ]);

        $phone = preg_replace('/[^0-9+]/', '', $request->phone);

        $key = 'otp-verify:' . $phone;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors(['code' => "Too many attempts. Try again in {$seconds} seconds."]);
        }

        $otp = OtpCode::where('phone', $phone)
            ->where('code', $request->code)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otp) {
            RateLimiter::hit($key, 300);

            return back()->withErrors(['code' => 'Invalid or expired OTP.']);
        }

        RateLimiter::clear($key);
        $otp->update(['verified_at' => now()]);

        $user = User::firstOrCreate(
            ['phone' => $phone],
            [
                'name' => 'User ' . substr($phone, -4),
                'email' => $phone . '@whatsapp.local',
                'password' => bcrypt(str()->random(32)),
                'phone_verified_at' => now(),
            ]
        );

        if (! $user->wasRecentlyCreated && ! $user->phone_verified_at) {
            $user->update(['phone_verified_at' => now()]);
        }

        if ($user->wasRecentlyCreated) {
            $this->createDefaultWallet($user->id);
        }

        Auth::login($user, remember: true);

        return redirect()->route('dashboard');
    }

    private function createDefaultWallet(int $userId): void
    {
        Wallet::create([
            'user_id' => $userId,
            'name' => 'Cash',
            'type' => 'cash',
            'currency' => 'PKR',
            'balance' => 0,
            'color' => '#10b981',
            'icon' => 'banknotes',
            'is_default' => true,
        ]);
    }
}
