<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class EmailOtpController extends Controller
{
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        $key = 'email-otp-verify:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors(['code' => "Too many attempts. Try again in {$seconds} seconds."]);
        }

        $otp = OtpCode::where('email', $user->email)
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

        $user->markEmailAsVerified();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
