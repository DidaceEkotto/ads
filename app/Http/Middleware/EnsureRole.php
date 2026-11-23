<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vérifie que l'utilisateur authentifié possède l'un des rôles autorisés
 * et que son compte est actif.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => __('Votre compte a été désactivé. Contactez le support.'),
            ]);
        }

        if (! in_array($user->role->value, $roles, true)) {
            abort(403, __('Accès non autorisé pour votre rôle.'));
        }

        return $next($request);
    }
}
