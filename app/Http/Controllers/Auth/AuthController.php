<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\AdvertiserProfile;
use App\Models\PublisherProfile;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function showLogin(): InertiaResponse
    {
        return Inertia::render('Auth/Login');
    }

    public function showRegister(): InertiaResponse
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Authentifie l'utilisateur et le redirige vers son espace (Inertia ou Livewire Admin).
     */
    public function login(LoginRequest $request): Response
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            return back()->withErrors([
                'email' => __('Ces identifiants ne correspondent à aucun compte.'),
            ])->onlyInput('email');
        }

        $request->session()->regenerate();
        /** @var User $user */
        $user = $request->user();

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => __('Votre compte est désactivé.'),
            ]);
        }

        $targetUrl = route($user->role->dashboardRoute());

        // L'admin utilise Livewire (hors Inertia) : on force une redirection navigateur complète.
        if ($user->isAdmin()) {
            return Inertia::location($targetUrl);
        }

        return redirect()->intended($targetUrl);
    }

    /**
     * Inscrit un nouvel annonceur ou éditeur avec son profil et son portefeuille XAF.
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        $role = UserRole::from($request->validated('role'));
        $referralCode = trim((string) ($request->validated('referral_code') ?: $request->query('ref', '')));
        $sponsorId = $referralCode !== ''
            ? User::query()->where('referral_code', strtoupper($referralCode))->value('id')
            : null;

        $user = DB::transaction(function () use ($request, $role, $sponsorId): User {
            $user = User::query()->create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'phone' => $request->validated('phone'),
                'role' => $role,
                'locale' => app()->getLocale(),
                'referred_by' => $sponsorId,
                'password' => $request->validated('password'),
            ]);

            Wallet::query()->create(['user_id' => $user->id]);

            if ($role === UserRole::Advertiser) {
                AdvertiserProfile::query()->create([
                    'user_id' => $user->id,
                    'company_name' => $request->validated('company_name'),
                    'city' => $request->validated('city'),
                    'whatsapp_number' => $request->validated('phone'),
                ]);
            } else {
                PublisherProfile::query()->create([
                    'user_id' => $user->id,
                    'display_name' => $request->validated('name'),
                    'city' => $request->validated('city'),
                    'whatsapp_number' => $request->validated('phone'),
                ]);
            }

            return $user;
        });

        Auth::login($user);

        return redirect()->route($role->dashboardRoute());
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
