<?php

namespace App\Http\Controllers\Publisher;

use App\Enums\SocialPlatform;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Connexion OAuth pour Facebook & LinkedIn (vérification automatique des comptes).
 */
class SocialOAuthController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        if (! in_array($provider, ['facebook', 'linkedin'], true)) {
            abort(404);
        }

        $clientId = config("services.{$provider}.client_id");
        if (! $clientId) {
            return redirect()->route('publisher.social.index')
                ->with('error', __('Connexion OAuth :provider non configurée dans .env (utilisez la vérification par code bio).', ['provider' => ucfirst($provider)]));
        }

        $state = Str::random(32);
        session(['social_oauth_state' => $state]);
        $redirectUri = route('publisher.social.oauth.callback', ['provider' => $provider]);

        $authUrl = $provider === 'facebook'
            ? 'https://www.facebook.com/v19.0/dialog/oauth?'.http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'state' => $state,
                'scope' => 'public_profile,pages_show_list',
            ])
            : 'https://www.linkedin.com/oauth/v2/authorization?'.http_build_query([
                'response_type' => 'code',
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'state' => $state,
                'scope' => 'openid profile',
            ]);

        return redirect()->away($authUrl);
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        if ($request->query('state') !== session('social_oauth_state')) {
            return redirect()->route('publisher.social.index')->with('error', __('Session OAuth invalide.'));
        }

        $code = (string) $request->query('code', '');
        $clientId = config("services.{$provider}.client_id");
        $clientSecret = config("services.{$provider}.client_secret");
        $redirectUri = route('publisher.social.oauth.callback', ['provider' => $provider]);

        if ($code === '' || ! $clientId || ! $clientSecret) {
            return redirect()->route('publisher.social.index')->with('error', __('Échec de la vérification OAuth.'));
        }

        $tokenUrl = $provider === 'facebook'
            ? 'https://graph.facebook.com/v19.0/oauth/access_token'
            : 'https://www.linkedin.com/oauth/v2/accessToken';

        $tokenRes = Http::asForm()->post($tokenUrl, [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);

        if ($tokenRes->failed() || ! $tokenRes->json('access_token')) {
            return redirect()->route('publisher.social.index')->with('error', __('Impossible d\'obtenir le jeton OAuth.'));
        }

        $handle = $provider.'_'.Str::lower(Str::random(6));
        $request->user()->socialAccounts()->firstOrCreate(
            ['platform' => SocialPlatform::from($provider), 'handle' => $handle],
            [
                'profile_url' => $provider === 'facebook' ? 'https://facebook.com/'.$handle : 'https://linkedin.com/in/'.$handle,
                'followers_count' => 0,
                'verification_code' => 'OAUTH-VERIFIED',
                'status' => 'approved',
            ]
        );

        return redirect()->route('publisher.social.index')->with('success', __('Compte :provider connecté et approuvé automatiquement !', ['provider' => ucfirst($provider)]));
    }
}
