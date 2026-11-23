<?php

namespace App\Http\Controllers\Publisher;

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\SocialPlacementStatus;
use App\Enums\SocialPlatform;
use App\Http\Controllers\Controller;
use App\Http\Requests\Publisher\StoreSocialAccountRequest;
use App\Http\Requests\Publisher\SubmitSocialProofRequest;
use App\Models\Campaign;
use App\Models\Setting;
use App\Models\SocialAccount;
use App\Models\SocialPlacement;
use App\Models\TrackingLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SocialController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();

        $accounts = $user->socialAccounts()->latest()->get()->map(fn (SocialAccount $a) => [
            'id' => $a->id,
            'platform' => $a->platform->value,
            'platform_label' => $a->platform->label(),
            'handle' => $a->handle,
            'profile_url' => $a->profile_url,
            'followers_count' => $a->followers_count,
            'verification_code' => $a->verification_code,
            'status' => $a->status,
            'is_approved' => $a->isApproved(),
        ]);

        $userPlacements = $user->socialPlacements()
            ->with(['campaign', 'socialAccount', 'trackingLink'])
            ->latest()
            ->get();

        $placementsByCampaign = $userPlacements->keyBy('campaign_id');

        $availableCampaigns = Campaign::query()
            ->with('creatives')
            ->where('type', CampaignType::Social)
            ->where('status', CampaignStatus::Active)
            ->whereColumn('spent_xaf', '<', 'total_budget_xaf')
            ->latest()
            ->get()
            ->map(function (Campaign $c) use ($placementsByCampaign) {
                /** @var SocialPlacement|null $pl */
                $pl = $placementsByCampaign->get($c->id);

                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'billing_model' => $c->billing_model->value,
                    'unit_price_xaf' => $c->unit_price_xaf,
                    'title' => $c->creatives->first()?->title,
                    'body_text' => $c->creatives->first()?->body_text,
                    'media_url' => $c->creatives->first()?->mediaUrl(),
                    'placement' => $pl ? [
                        'id' => $pl->id,
                        'tracking_url' => $pl->trackingLink?->publicUrl(),
                        'post_url' => $pl->post_url,
                        'status' => $pl->status->value,
                        'status_label' => $pl->status->label(),
                    ] : null,
                ];
            });

        $placements = $userPlacements->map(fn (SocialPlacement $p) => [
            'id' => $p->id,
            'campaign_name' => $p->campaign->name,
            'account_label' => $p->socialAccount->platform->label().' (@'.$p->socialAccount->handle.')',
            'tracking_url' => $p->trackingLink?->publicUrl(),
            'post_url' => $p->post_url,
            'status' => $p->status->value,
            'status_label' => $p->status->label(),
            'min_duration_ends_at' => $p->min_duration_ends_at?->format('d/m/Y H:i'),
        ]);

        return Inertia::render('Publisher/Social/Index', [
            'accounts' => $accounts,
            'availableCampaigns' => $availableCampaigns,
            'placements' => $placements,
            'platforms' => collect(SocialPlatform::cases())->map(fn ($p) => ['value' => $p->value, 'label' => $p->label()]),
            'minFollowers' => (int) Setting::getValue('min_social_followers', 500),
            'minPostHours' => (int) Setting::getValue('social_post_min_hours', 48),
        ]);
    }

    public function storeAccount(StoreSocialAccountRequest $request): RedirectResponse
    {
        $request->user()->socialAccounts()->create([
            'platform' => $request->validated('platform'),
            'handle' => ltrim((string) $request->validated('handle'), '@'),
            'profile_url' => $request->validated('profile_url'),
            'followers_count' => 0,
            'verification_code' => 'CMADS-'.strtoupper(Str::random(6)),
            'status' => 'pending',
        ]);

        return back()->with('success', __('Compte social ajouté. Placez le code de vérification dans votre bio en attendant la validation.'));
    }

    /**
     * Génère un lien de suivi unique /c/{code} pour publier une campagne sociale.
     */
    public function joinCampaign(Request $request, Campaign $campaign): RedirectResponse
    {
        $validated = $request->validate([
            'social_account_id' => ['required', 'integer', 'exists:social_accounts,id'],
        ]);

        /** @var SocialAccount $account */
        $account = SocialAccount::query()->findOrFail($validated['social_account_id']);
        Gate::authorize('manageAccount', $account);

        if (! $account->isApproved()) {
            return back()->with('error', __('Ce compte social doit être approuvé par l\'administrateur avant de participer.'));
        }

        if ($campaign->type !== CampaignType::Social || $campaign->status !== CampaignStatus::Active) {
            return back()->with('error', __('Cette campagne sociale n\'est pas disponible.'));
        }

        $exists = SocialPlacement::query()
            ->where('campaign_id', $campaign->id)
            ->where('social_account_id', $account->id)
            ->exists();

        if ($exists) {
            return back()->with('error', __('Vous participez déjà à cette campagne avec ce compte.'));
        }

        DB::transaction(function () use ($request, $campaign, $account): void {
            $placement = SocialPlacement::query()->create([
                'campaign_id' => $campaign->id,
                'publisher_id' => $request->user()->id,
                'social_account_id' => $account->id,
                'status' => SocialPlacementStatus::PendingProof,
            ]);

            TrackingLink::query()->create([
                'code' => Str::lower(Str::random(10)),
                'campaign_id' => $campaign->id,
                'publisher_id' => $request->user()->id,
                'social_placement_id' => $placement->id,
                'is_active' => true,
            ]);
        });

        return back()->with('success', __('Lien de suivi généré ! Publiez votre post avec #pub puis soumettez l\'URL.'));
    }

    public function submitProof(SubmitSocialProofRequest $request, SocialPlacement $placement): RedirectResponse
    {
        Gate::authorize('managePlacement', $placement);

        $minHours = (int) Setting::getValue('social_post_min_hours', 48);

        $placement->update([
            'post_url' => $request->validated('post_url'),
            'has_disclosure_tag' => true,
            'status' => SocialPlacementStatus::PendingReview,
            'published_at' => now(),
            'min_duration_ends_at' => now()->addHours($minHours),
        ]);

        return back()->with('success', __('Preuve soumise pour vérification.'));
    }
}
