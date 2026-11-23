<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * Template racine utilisé pour les pages Inertia (Annonceur & Éditeur).
     */
    protected $rootView = 'app';

    /**
     * Détermine la version courante des assets.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Données partagées automatiquement avec toutes les vues Vue 3.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        $logoPath = (string) Setting::getValue('site_logo_path', '');
        $seoImagePath = (string) Setting::getValue('seo_image_path', '');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role->value,
                    'locale' => $user->locale,
                    'avatar_url' => $user->avatarUrl(),
                    'initials' => $user->initials(),
                    'wallet' => $user->wallet ? [
                        'available_balance_xaf' => $user->wallet->available_balance_xaf,
                        'pending_balance_xaf' => $user->wallet->pending_balance_xaf,
                    ] : null,
                ] : null,
            ],
            'locale' => app()->getLocale(),
            'demoEnabled' => (bool) config('demo.enabled'),
            'supportWhatsapp' => Setting::getValue('support_whatsapp', 'https://wa.me/237600000000'),
            'siteSettings' => [
                'name' => (string) Setting::getValue('site_name', 'CM-Ads'),
                'logo_url' => $logoPath !== '' ? Storage::disk('public')->url($logoPath) : null,
                'seo_image_url' => $seoImagePath !== '' ? Storage::disk('public')->url($seoImagePath) : null,
                'description' => (string) Setting::getValue('site_description', ''),
                'keywords' => (string) Setting::getValue('site_keywords', ''),
                'email' => (string) Setting::getValue('site_email', ''),
                'phone' => (string) Setting::getValue('site_phone', ''),
                'postal_box' => (string) Setting::getValue('site_postal_box', ''),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
