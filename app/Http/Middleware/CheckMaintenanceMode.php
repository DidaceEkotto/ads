<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloque l'accès public lorsque le mode maintenance est activé dans /admin/settings,
 * tout en laissant /admin/*, /login, /logout et /livewire/* accessibles.
 */
class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::getValue('maintenance_mode', false)) {
            return $next($request);
        }

        if (
            $request->is('admin')
            || $request->is('admin/*')
            || $request->is('login')
            || $request->is('logout')
            || $request->is('livewire/*')
        ) {
            return $next($request);
        }

        $logoPath = (string) Setting::getValue('site_logo_path', '');

        return response()->view('errors.maintenance', [
            'siteName' => (string) Setting::getValue('site_name', 'CM-Ads'),
            'logoUrl' => $logoPath !== '' ? Storage::disk('public')->url($logoPath) : null,
            'message' => (string) Setting::getValue('maintenance_message', 'Site en cours de maintenance.'),
            'supportWhatsapp' => (string) Setting::getValue('support_whatsapp', ''),
        ], 503);
    }
}
