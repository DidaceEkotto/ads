<?php

namespace App\Http\Controllers\Demo;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessAdClickJob;
use App\Models\AdZone;
use App\Models\Campaign;
use App\Models\TrackingLink;
use App\Models\User;
use App\Services\Billing\LedgerService;
use Database\Seeders\DemoSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class DemoController extends Controller
{
    public function index(): InertiaResponse
    {
        // Initialise automatiquement les données démo si absentes en sandbox
        if (! User::query()->where('email', 'advertiser@demo.cm')->exists()) {
            app(DemoSeeder::class)->run();
        }

        $advertiser = User::query()->with('wallet')->where('email', 'advertiser@demo.cm')->first();
        $publisher = User::query()->with('wallet')->where('email', 'publisher@demo.cm')->first();
        $zone = AdZone::query()->where('token', 'demozone12345678')->first();
        $socialLink = TrackingLink::query()->where('code', 'demosocial01')->first();
        $campaign = Campaign::query()->where('name', 'Promo Rentrée Douala (Démo)')->first();

        return Inertia::render('Demo/Index', [
            'accounts' => [
                'advertiser' => [
                    'email' => $advertiser?->email,
                    'balance_xaf' => $advertiser?->wallet?->available_balance_xaf ?? 0,
                ],
                'publisher' => [
                    'email' => $publisher?->email,
                    'available_xaf' => $publisher?->wallet?->available_balance_xaf ?? 0,
                    'pending_xaf' => $publisher?->wallet?->pending_balance_xaf ?? 0,
                ],
                'admin' => [
                    'email' => 'admin@cm-ads.cm',
                ],
            ],
            'zoneToken' => $zone?->token,
            'socialCode' => $socialLink?->code,
            'campaignSpentXaf' => $campaign?->spent_xaf ?? 0,
        ]);
    }

    /**
     * Connexion rapide en 1 clic sur l'un des 3 rôles de démonstration.
     */
    public function loginAs(Request $request, string $role): Response
    {
        $email = match ($role) {
            UserRole::Advertiser->value => 'advertiser@demo.cm',
            UserRole::Publisher->value => 'publisher@demo.cm',
            UserRole::Admin->value => 'admin@cm-ads.cm',
            default => abort(404),
        };

        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            app(DemoSeeder::class)->run();
            $user = User::query()->where('email', $email)->firstOrFail();
        }

        Auth::login($user);
        $request->session()->regenerate();

        $target = route($user->role->dashboardRoute());

        return $user->isAdmin()
            ? Inertia::location($target)
            : redirect($target);
    }

    /**
     * Simule un paiement SasPay réussi pour l'annonceur démo.
     */
    public function simulateDeposit(Request $request, LedgerService $ledger): RedirectResponse
    {
        $advertiser = User::query()->with('wallet')->where('email', 'advertiser@demo.cm')->firstOrFail();
        $wallet = $advertiser->wallet()->firstOrCreate(['user_id' => $advertiser->id]);

        $tx = $ledger->createOrGetPendingDeposit($wallet, 10000, (string) Str::uuid(), 'saspay_sandbox');
        $ledger->confirmDepositIdempotently($tx->reference, 'demo_sp_'.Str::random(8), 10000);

        return back()->with('success', __('Recharge Sandbox de 10 000 FCFA créditée sur le compte Annonceur Démo.'));
    }

    /**
     * Simule un clic suspect (Bot / Auto-clic) pour alimenter le moniteur anti-fraude.
     */
    public function simulateFraud(Request $request): RedirectResponse
    {
        $campaign = Campaign::query()->where('name', 'Promo Rentrée Douala (Démo)')->firstOrFail();
        $publisher = User::query()->where('email', 'publisher@demo.cm')->firstOrFail();

        ProcessAdClickJob::dispatchSync([
            'campaign_id' => $campaign->id,
            'creative_id' => $campaign->creatives()->first()?->id,
            'site_id' => null,
            'ad_zone_id' => null,
            'social_placement_id' => null,
            'tracking_link_id' => null,
            'publisher_id' => $publisher->id,
            'visitor_hash' => hash('sha256', 'demo-bot|'.Str::random(4)),
            'ip_address' => '192.168.100.55',
            'user_agent' => 'curl/8.0.1 (DemoBot)',
            'referer' => null,
            'city' => 'Douala',
            'device_type' => 'desktop',
            'authenticated_user_id' => null,
        ]);

        return back()->with('success', __('Clic bot simulé : bloqué, non facturé et enregistré dans le moniteur Anti-Fraude Admin.'));
    }
}
