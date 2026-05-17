<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialController extends Controller
{
    private const ALLOWED_PROVIDERS = ['google', 'facebook', 'github'];

    public function redirect(string $provider): RedirectResponse
    {
        $this->abortIfInvalidProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $this->abortIfInvalidProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Throwable) {
            return redirect()->route('login')->withErrors(['email' => 'Social login failed or was cancelled.']);
        }

        $user = User::updateOrCreate(
            ['social_provider' => $provider, 'social_id' => (string) $socialUser->getId()],
            [
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                'email' => $socialUser->getEmail() ?? "{$provider}_{$socialUser->getId()}@social.local",
                'avatar' => $socialUser->getAvatar(),
                'password' => bcrypt(str()->random(32)),
                'email_verified_at' => now(),
            ]
        );

        if ($user->wasRecentlyCreated) {
            $this->createDefaultWallet($user->id);
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }

    private function abortIfInvalidProvider(string $provider): void
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS), 404);
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
