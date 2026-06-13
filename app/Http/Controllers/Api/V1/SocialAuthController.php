<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateDefaultWallet;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    private const ALLOWED_PROVIDERS = ['google'];

    public function __construct(private CreateDefaultWallet $createDefaultWallet) {}

    /**
     * Exchange a provider access token (obtained natively on the device) for an API token.
     */
    public function store(Request $request, string $provider): JsonResponse
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS, true), 404);

        $validated = $request->validate([
            'access_token' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $socialUser = Socialite::driver($provider)->stateless()->userFromToken($validated['access_token']);
        } catch (Throwable) {
            return response()->json([
                'message' => 'Could not verify the provided social login token.',
            ], 422);
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
            $this->createDefaultWallet->create($user);
        }

        $deviceName = $request->string('device_name')->trim()->value() ?: 'mobile';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ], $user->wasRecentlyCreated ? 201 : 200);
    }
}
