<?php

namespace App\Http\Controllers\Advertiser;

use App\Http\Controllers\Controller;
use App\Models\AdvertiserProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SettingsController extends Controller
{
    public function edit(Request $request): InertiaResponse
    {
        $user = $request->user();
        /** @var AdvertiserProfile $profile */
        $profile = $user->advertiserProfile()->firstOrCreate(['user_id' => $user->id]);

        return Inertia::render('Advertiser/Settings', [
            'profile' => [
                'company_name' => $profile->company_name,
                'logo_url' => $profile->logoUrl(),
                'business_sector' => $profile->business_sector,
                'city' => $profile->city,
                'postal_box' => $profile->postal_box,
                'whatsapp_number' => $profile->whatsapp_number,
                'website_url' => $profile->website_url,
                'facebook_url' => $profile->facebook_url,
                'linkedin_url' => $profile->linkedin_url,
                'x_url' => $profile->x_url,
                'description' => $profile->description,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['nullable', 'string', 'max:150'],
            'business_sector' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'postal_box' => ['nullable', 'string', 'max:80'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'website_url' => ['nullable', 'url:http,https', 'max:255'],
            'facebook_url' => ['nullable', 'url:http,https', 'max:255'],
            'linkedin_url' => ['nullable', 'url:http,https', 'max:255'],
            'x_url' => ['nullable', 'url:http,https', 'max:255'],
            'description' => ['nullable', 'string', 'max:1500'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:300'],
        ]);

        $user = $request->user();
        /** @var AdvertiserProfile $profile */
        $profile = $user->advertiserProfile()->firstOrCreate(['user_id' => $user->id]);

        if ($request->hasFile('logo')) {
            if ($profile->logo_path) {
                Storage::disk('public')->delete($profile->logo_path);
            }
            $file = $request->file('logo');
            $ext = $file->guessExtension() ?: 'png';
            $validated['logo_path'] = Storage::disk('public')->putFileAs(
                'logos',
                $file,
                Str::uuid().'.'.$ext
            );
        }

        unset($validated['logo']);
        $profile->update($validated);

        return back()->with('success', __('Informations de l\'entreprise mises à jour avec succès.'));
    }
}
