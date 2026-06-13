<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PreferenceController extends Controller
{
    /**
     * List the supported currencies and locales.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'currencies' => config('currencies.supported'),
            'locales' => [
                'en' => 'English',
                'ur' => 'اردو (Urdu)',
                'ar' => 'العربية (Arabic)',
                'fr' => 'Français (French)',
                'de' => 'Deutsch (German)',
                'es' => 'Español (Spanish)',
                'zh' => '中文 (Chinese)',
            ],
        ]);
    }

    /**
     * Update the authenticated user's currency and/or locale.
     */
    public function update(Request $request): UserResource
    {
        $validated = $request->validate([
            'default_currency' => ['required', 'string', Rule::in(array_keys(config('currencies.supported')))],
            'locale' => ['sometimes', 'string', 'max:10'],
        ]);

        $user = $request->user();
        $currencyWasUnset = $user->default_currency === null;

        $user->update($validated);

        // On first set, align the auto-created (unused) wallets with the chosen currency.
        if ($currencyWasUnset) {
            $user->wallets()
                ->doesntHave('transactions')
                ->update(['currency' => $validated['default_currency']]);
        }

        return new UserResource($user->refresh());
    }
}
