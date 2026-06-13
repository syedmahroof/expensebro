<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PreferencesController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('settings/Preferences', [
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

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_currency' => ['required', 'string', Rule::in(array_keys(config('currencies.supported')))],
            'locale' => ['required', 'string', 'max:10'],
        ]);

        $request->user()->update($validated);

        return back()->with('status', 'preferences-updated');
    }
}
