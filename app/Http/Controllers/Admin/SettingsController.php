<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Show the settings edit form.
     */
    public function edit()
    {
        $settings = Setting::first();
        return Inertia::render('settings/Edit', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update the settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'affiliation_number' => 'nullable|string|max:255',
            'stream'             => 'nullable|string|max:255',
            'college_name'       => 'nullable|string|max:255',
            'address'            => 'nullable|string|max:255',
            'phone_primary'      => 'nullable|string|max:20',
            'phone_secondary'    => 'nullable|string|max:20',
        ]);

        $settings = Setting::firstOrCreate([]);
        $settings->update($validated);

        return redirect()->route('admin.settings.edit')
            ->with('flash', ['message' => 'Settings updated successfully.']);
    }
}
