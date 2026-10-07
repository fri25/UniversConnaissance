<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\MetaPixel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(MetaPixel $meta): View
    {
        return view('admin.settings', [
            'pixelId' => Setting::get('meta_pixel_id', config('services.meta.pixel_id')),
            'hasToken' => $meta->capiToken() !== null,
            'testEventCode' => Setting::get('meta_test_event_code'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'meta_pixel_id' => ['nullable', 'regex:/^\d{8,20}$/'],
            'meta_capi_token' => ['nullable', 'string', 'max:500'],
            'meta_test_event_code' => ['nullable', 'alpha_num', 'max:30'],
            'remove_token' => ['nullable', 'boolean'],
        ], [
            'meta_pixel_id.regex' => 'L\'identifiant du pixel ne contient que des chiffres (15 ou 16 en général).',
        ], [
            'meta_pixel_id' => 'identifiant du pixel',
            'meta_capi_token' => 'jeton de l\'API Conversions',
            'meta_test_event_code' => 'code de test',
        ]);

        Setting::put('meta_pixel_id', trim((string) ($data['meta_pixel_id'] ?? '')));
        Setting::put('meta_test_event_code', $data['meta_test_event_code'] ?? null);

        // Champ jeton vide = on garde le jeton enregistré (il n'est jamais réaffiché).
        if ($request->boolean('remove_token')) {
            Setting::put('meta_capi_token', null);
        } elseif (! empty($data['meta_capi_token'])) {
            Setting::put('meta_capi_token', trim($data['meta_capi_token']));
        }

        return back()->with('status', 'Réglages enregistrés.');
    }
}
