<?php

namespace App\Http\Controllers\Advertiser;

use App\Enums\AdZoneFormat;
use App\Enums\BillingModel;
use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Advertiser\StoreCampaignRequest;
use App\Http\Requests\Advertiser\UpdateCampaignRequest;
use App\Models\Campaign;
use App\Models\Click;
use App\Models\Conversion;
use App\Models\Impression;
use App\Models\Setting;
use App\Models\Site;
use App\Services\Media\CreativeUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;

class CampaignController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $campaigns = $request->user()->campaigns()
            ->with('creatives')
            ->latest()
            ->paginate(12)
            ->through(fn (Campaign $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'type' => $c->type->value,
                'type_label' => $c->type->label(),
                'billing_model' => $c->billing_model->value,
                'billing_label' => $c->billing_model->label(),
                'status' => $c->status->value,
                'status_label' => $c->status->label(),
                'total_budget_xaf' => $c->total_budget_xaf,
                'spent_xaf' => $c->spent_xaf,
                'remaining_xaf' => $c->remainingBudgetXaf(),
                'unit_price_xaf' => $c->unit_price_xaf,
                'creative_preview_url' => $c->creatives->first()?->mediaUrl(),
                'starts_at' => $c->starts_at?->format('d/m/Y H:i'),
                'ends_at' => $c->ends_at?->format('d/m/Y H:i'),
                'duration_days' => ($c->starts_at && $c->ends_at) ? max(1, (int) $c->starts_at->diffInDays($c->ends_at)) : null,
                'is_expired' => $c->ends_at ? $c->ends_at->isPast() : false,
                'days_remaining' => $c->ends_at
                    ? ($c->ends_at->isFuture() ? max(1, (int) ceil(now()->floatDiffInDays($c->ends_at))) : 0)
                    : null,
                'created_at' => $c->created_at?->format('d/m/Y'),
            ]);

        return Inertia::render('Advertiser/Campaigns/Index', [
            'campaigns' => $campaigns,
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $wallet = $request->user()->wallet()->firstOrCreate(['user_id' => $request->user()->id]);

        return Inertia::render('Advertiser/Campaigns/Create', [
            'types' => collect(CampaignType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
            'billingModels' => collect(BillingModel::cases())->map(fn ($b) => ['value' => $b->value, 'label' => $b->label()]),
            'formats' => collect(AdZoneFormat::cases())->map(fn ($f) => ['value' => $f->value, 'label' => $f->label()]),
            'cameroonCities' => ['Douala', 'Yaoundé', 'Bafoussam', 'Garoua', 'Bamenda', 'Maroua', 'Ngaoundéré', 'Kribi', 'Limbé', 'Buea'],
            'categories' => ['actualites', 'sport', 'tech', 'divertissement', 'business', 'education', 'lifestyle'],
            'walletBalanceXaf' => (int) $wallet->available_balance_xaf,
            'pricing' => [
                'daily_campaign_cost_xaf' => (int) Setting::getValue('daily_campaign_cost_xaf', 2000),
                'cpc_unit_price_xaf' => (int) Setting::getValue('cpc_unit_price_xaf', 50),
                'view_unit_price_xaf' => (int) Setting::getValue('view_unit_price_xaf', 3),
            ],
        ]);
    }

    public function store(StoreCampaignRequest $request, CreativeUploadService $uploader): RedirectResponse
    {
        try {
            $media = $uploader->store($request->file('media'));
            $mediaB = $request->hasFile('media_b')
                ? $uploader->store($request->file('media_b'))
                : null;
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['media' => $e->getMessage()])->withInput();
        }

        // Tarifs calculés strictement côté serveur depuis les paramètres Admin
        $dailyCost = (int) Setting::getValue('daily_campaign_cost_xaf', 2000);
        $cpcPrice = (int) Setting::getValue('cpc_unit_price_xaf', 50);
        $viewPrice = (int) Setting::getValue('view_unit_price_xaf', 3);

        $durationDays = (int) $request->validated('duration_days');
        $totalBudget = $durationDays * $dailyCost;

        // Vérification obligatoire que le solde du wallet couvre le récapitulatif tarifaire
        $wallet = $request->user()->wallet()->firstOrCreate(['user_id' => $request->user()->id]);
        if ((int) $wallet->available_balance_xaf < $totalBudget) {
            return back()
                ->withErrors([
                    'wallet' => 'Solde insuffisant dans votre portefeuille ('
                        .number_format((int) $wallet->available_balance_xaf, 0, ',', ' ')
                        .' FCFA disponibles sur '
                        .number_format($totalBudget, 0, ',', ' ')
                        .' FCFA requis). Veuillez recharger votre wallet.',
                ])
                ->withInput();
        }

        $billingModel = BillingModel::from($request->validated('billing_model'));

        $unitPrice = match ($billingModel) {
            BillingModel::Cpm => $viewPrice,
            BillingModel::FlatPost => $dailyCost,
            BillingModel::Cpc => $cpcPrice,
        };

        $startsAt = $request->validated('starts_at')
            ? Carbon::parse($request->validated('starts_at'))
            : now();
        $endsAt = $startsAt->copy()->addDays($durationDays);

        // Soumission pour validation obligatoire et non négociable
        $status = CampaignStatus::PendingReview;

        DB::transaction(function () use ($request, $media, $mediaB, $status, $totalBudget, $dailyCost, $unitPrice, $startsAt, $endsAt): void {
            /** @var Campaign $campaign */
            $campaign = Campaign::query()->create([
                'advertiser_id' => $request->user()->id,
                'name' => $request->validated('name'),
                'type' => $request->validated('type'),
                'billing_model' => $request->validated('billing_model'),
                'status' => $status,
                'total_budget_xaf' => $totalBudget,
                'daily_budget_xaf' => $dailyCost,
                'unit_price_xaf' => $unitPrice,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            $campaign->creatives()->create([
                'title' => $request->validated('title'),
                'body_text' => $request->validated('body_text'),
                'destination_url' => $request->validated('destination_url'),
                'media_type' => $media['media_type'],
                'media_path' => $media['media_path'],
                'format' => $request->validated('format'),
                'file_size_bytes' => $media['file_size_bytes'],
            ]);

            if ($mediaB) {
                $campaign->creatives()->create([
                    'title' => $request->validated('title_b') ?: $request->validated('title').' (B)',
                    'body_text' => $request->validated('body_text'),
                    'destination_url' => $request->validated('destination_url'),
                    'media_type' => $mediaB['media_type'],
                    'media_path' => $mediaB['media_path'],
                    'format' => $request->validated('format'),
                    'file_size_bytes' => $mediaB['file_size_bytes'],
                ]);
            }

            $campaign->targeting()->create([
                'cities' => $request->validated('cities') ?? [],
                'languages' => $request->validated('languages') ?? [],
                'devices' => $request->validated('devices') ?? [],
                'categories' => $request->validated('categories') ?? [],
            ]);
        });

        return redirect()->route('advertiser.campaigns.index')
            ->with('success', __('Campagne enregistrée avec succès.'));
    }

    public function edit(Request $request, Campaign $campaign): InertiaResponse
    {
        Gate::authorize('update', $campaign);

        $campaign->load(['creatives', 'targeting']);
        $creative = $campaign->creatives->first();
        $targeting = $campaign->targeting;
        $wallet = $request->user()->wallet()->firstOrCreate(['user_id' => $request->user()->id]);

        $durationDays = ($campaign->starts_at && $campaign->ends_at)
            ? max(1, (int) $campaign->starts_at->diffInDays($campaign->ends_at))
            : 7;

        return Inertia::render('Advertiser/Campaigns/Edit', [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'type' => $campaign->type->value,
                'type_label' => $campaign->type->label(),
                'billing_model' => $campaign->billing_model->value,
                'billing_label' => $campaign->billing_model->label(),
                'status' => $campaign->status->value,
                'status_label' => $campaign->status->label(),
                'duration_days' => $durationDays,
                'starts_at' => $campaign->starts_at?->format('d/m/Y H:i'),
                'ends_at' => $campaign->ends_at?->format('d/m/Y H:i'),
                'title' => $creative?->title ?? '',
                'body_text' => $creative?->body_text ?? '',
                'destination_url' => $creative?->destination_url ?? 'https://',
                'format' => $creative?->format ?? 'square_300x250',
                'media_url' => $creative?->mediaUrl(),
                'media_type' => $creative?->media_type,
                'cities' => $targeting?->cities ?? ['Douala'],
                'languages' => $targeting?->languages ?? ['fr'],
                'devices' => $targeting?->devices ?? ['mobile', 'desktop'],
                'categories' => $targeting?->categories ?? ['actualites'],
            ],
            'formats' => collect(AdZoneFormat::cases())->map(fn ($f) => ['value' => $f->value, 'label' => $f->label()]),
            'cameroonCities' => ['Douala', 'Yaoundé', 'Bafoussam', 'Garoua', 'Bamenda', 'Maroua', 'Ngaoundéré', 'Kribi', 'Limbé', 'Buea'],
            'categories' => ['actualites', 'sport', 'tech', 'divertissement', 'business', 'education', 'lifestyle'],
            'walletBalanceXaf' => (int) $wallet->available_balance_xaf,
            'pricing' => [
                'daily_campaign_cost_xaf' => (int) Setting::getValue('daily_campaign_cost_xaf', 2000),
                'cpc_unit_price_xaf' => (int) Setting::getValue('cpc_unit_price_xaf', 50),
                'view_unit_price_xaf' => (int) Setting::getValue('view_unit_price_xaf', 3),
            ],
        ]);
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign, CreativeUploadService $uploader): RedirectResponse
    {
        Gate::authorize('update', $campaign);

        $media = null;
        if ($request->hasFile('media')) {
            try {
                $media = $uploader->store($request->file('media'));
            } catch (InvalidArgumentException $e) {
                return back()->withErrors(['media' => $e->getMessage()])->withInput();
            }
        }

        $dailyCost = (int) Setting::getValue('daily_campaign_cost_xaf', 2000);
        $durationDays = (int) $request->validated('duration_days');
        $totalBudget = $durationDays * $dailyCost;

        $wallet = $request->user()->wallet()->firstOrCreate(['user_id' => $request->user()->id]);
        if ((int) $wallet->available_balance_xaf < $totalBudget) {
            return back()
                ->withErrors([
                    'wallet' => 'Solde insuffisant dans votre portefeuille ('
                        .number_format((int) $wallet->available_balance_xaf, 0, ',', ' ')
                        .' FCFA disponibles sur '
                        .number_format($totalBudget, 0, ',', ' ')
                        .' FCFA requis). Veuillez recharger votre wallet.',
                ])
                ->withInput();
        }

        $startsAt = $campaign->starts_at ?? now();
        $endsAt = $startsAt->copy()->addDays($durationDays);

        $creative = $campaign->creatives()->first();
        $creativeChanged = $media !== null
            || ($creative && (
                $creative->title !== $request->validated('title')
                || $creative->destination_url !== $request->validated('destination_url')
            ));

        $newStatus = $campaign->status;
        if ($campaign->status === CampaignStatus::Rejected || ($creativeChanged && $campaign->status === CampaignStatus::Active)) {
            $newStatus = CampaignStatus::PendingReview;
        }

        DB::transaction(function () use ($request, $campaign, $creative, $media, $totalBudget, $dailyCost, $startsAt, $endsAt, $newStatus): void {
            $campaign->update([
                'name' => $request->validated('name'),
                'total_budget_xaf' => $totalBudget,
                'daily_budget_xaf' => $dailyCost,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => $newStatus,
                'rejection_reason' => $newStatus === CampaignStatus::PendingReview ? null : $campaign->rejection_reason,
            ]);

            if ($creative) {
                $creativeData = [
                    'title' => $request->validated('title'),
                    'body_text' => $request->validated('body_text'),
                    'destination_url' => $request->validated('destination_url'),
                    'format' => $request->validated('format'),
                ];
                if ($media) {
                    $creativeData['media_type'] = $media['media_type'];
                    $creativeData['media_path'] = $media['media_path'];
                    $creativeData['file_size_bytes'] = $media['file_size_bytes'];
                }
                $creative->update($creativeData);
            }

            $campaign->targeting()->updateOrCreate(
                ['campaign_id' => $campaign->id],
                [
                    'cities' => $request->validated('cities') ?? [],
                    'languages' => $request->validated('languages') ?? [],
                    'devices' => $request->validated('devices') ?? [],
                    'categories' => $request->validated('categories') ?? [],
                ]
            );
        });

        return redirect()->route('advertiser.campaigns.index')
            ->with('success', $newStatus === CampaignStatus::PendingReview && $creativeChanged
                ? __('Campagne mise à jour et soumise à nouveau pour validation.')
                : __('Campagne mise à jour avec succès.'));
    }

    public function show(Campaign $campaign): InertiaResponse
    {
        Gate::authorize('view', $campaign);

        $viewsCount = Impression::query()->where('campaign_id', $campaign->id)->count();
        $uniqueVisitorsCount = Impression::query()
            ->where('campaign_id', $campaign->id)
            ->distinct('visitor_hash')
            ->count('visitor_hash');

        $validClicksCount = Click::query()
            ->where('campaign_id', $campaign->id)
            ->where('is_valid', true)
            ->count();

        $blockedClicks = Click::query()
            ->where('campaign_id', $campaign->id)
            ->where('is_valid', false)
            ->selectRaw('invalid_reason, COUNT(*) as total')
            ->groupBy('invalid_reason')
            ->get()
            ->map(fn ($r) => [
                'reason' => $r->invalid_reason ?? 'unknown',
                'total' => (int) $r->total,
            ]);

        $blockedBotsCount = (int) $blockedClicks->sum('total');

        // Sites sur lesquels la campagne est présente
        $siteImpressions = Impression::query()
            ->where('campaign_id', $campaign->id)
            ->whereNotNull('site_id')
            ->selectRaw('site_id, COUNT(*) as views, COUNT(DISTINCT visitor_hash) as unique_visitors')
            ->groupBy('site_id')
            ->get()
            ->keyBy('site_id');

        $siteClicks = Click::query()
            ->where('campaign_id', $campaign->id)
            ->where('is_valid', true)
            ->whereNotNull('site_id')
            ->selectRaw('site_id, COUNT(*) as clicks')
            ->groupBy('site_id')
            ->pluck('clicks', 'site_id');

        $siteIds = $siteImpressions->keys()->merge($siteClicks->keys())->unique()->values();
        $sitesMap = Site::query()->whereIn('id', $siteIds)->get()->keyBy('id');

        $sitesBreakdown = $siteIds->map(function ($siteId) use ($sitesMap, $siteImpressions, $siteClicks) {
            $site = $sitesMap->get($siteId);
            $imp = (int) ($siteImpressions->get($siteId)->views ?? 0);
            $uniq = (int) ($siteImpressions->get($siteId)->unique_visitors ?? 0);
            $clk = (int) ($siteClicks->get($siteId) ?? 0);

            return [
                'id' => $siteId,
                'name' => $site?->name ?? 'Site #'.$siteId,
                'domain' => $site?->domain ?? '-',
                'views' => $imp,
                'unique_visitors' => $uniq,
                'clicks' => $clk,
                'ctr' => $imp > 0 ? round(($clk / $imp) * 100, 2) : 0,
            ];
        })->values();

        // Appareils
        $byDevice = Impression::query()
            ->where('campaign_id', $campaign->id)
            ->selectRaw('device_type, COUNT(*) as views, COUNT(DISTINCT visitor_hash) as unique_visitors')
            ->groupBy('device_type')
            ->get()
            ->map(fn ($r) => [
                'device_type' => $r->device_type ?: 'unknown',
                'views' => (int) $r->views,
                'unique_visitors' => (int) $r->unique_visitors,
            ]);

        // Géolocalisation (Pays & Villes)
        $byGeo = Impression::query()
            ->where('campaign_id', $campaign->id)
            ->selectRaw("COALESCE(country, 'Cameroun') as country, COALESCE(city, 'Inconnue') as city, COUNT(*) as views, COUNT(DISTINCT visitor_hash) as unique_visitors")
            ->groupBy('country', 'city')
            ->orderByDesc('views')
            ->limit(12)
            ->get()
            ->map(fn ($r) => [
                'country' => $r->country,
                'city' => $r->city,
                'views' => (int) $r->views,
                'unique_visitors' => (int) $r->unique_visitors,
            ]);

        // Courbe de comparaison sur 14 jours
        $start14 = now()->subDays(13)->startOfDay();
        $impDaily = Impression::query()
            ->where('campaign_id', $campaign->id)
            ->where('created_at', '>=', $start14)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as views, COUNT(DISTINCT visitor_hash) as unique_visitors')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $clkDaily = Click::query()
            ->where('campaign_id', $campaign->id)
            ->where('is_valid', true)
            ->where('created_at', '>=', $start14)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as clicks')
            ->groupBy('day')
            ->pluck('clicks', 'day');

        $dailyComparison = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $row = $impDaily->get($d);
            $dailyComparison[] = [
                'date' => Carbon::parse($d)->format('d/m'),
                'views' => (int) ($row->views ?? 0),
                'unique_visitors' => (int) ($row->unique_visitors ?? 0),
                'clicks' => (int) ($clkDaily->get($d) ?? 0),
            ];
        }

        $conversionsCount = Conversion::query()->where('campaign_id', $campaign->id)->count();

        $abComparison = $campaign->creatives()->get()->map(function ($creative) use ($campaign) {
            $views = Impression::query()->where('campaign_id', $campaign->id)->where('creative_id', $creative->id)->count();
            $clicks = Click::query()->where('campaign_id', $campaign->id)->where('creative_id', $creative->id)->where('is_valid', true)->count();

            return [
                'id' => $creative->id,
                'title' => $creative->title,
                'media_type' => $creative->media_type,
                'views' => $views,
                'clicks' => $clicks,
                'ctr' => $views > 0 ? round(($clicks / $views) * 100, 2) : 0,
            ];
        });

        return Inertia::render('Advertiser/Campaigns/Show', [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status->value,
                'status_label' => $campaign->status->label(),
                'billing_model' => strtoupper($campaign->billing_model->value),
                'total_budget_xaf' => $campaign->total_budget_xaf,
                'spent_xaf' => $campaign->spent_xaf,
                'conversion_pixel_url' => route('adserving.conversion', ['campaign' => $campaign->id]),
            ],
            'summary' => [
                'sites_count' => $sitesBreakdown->count(),
                'views_count' => $viewsCount,
                'unique_visitors_count' => $uniqueVisitorsCount,
                'valid_clicks_count' => $validClicksCount,
                'blocked_bots_count' => $blockedBotsCount,
                'conversions_count' => $conversionsCount,
                'ctr' => $viewsCount > 0 ? round(($validClicksCount / $viewsCount) * 100, 2) : 0,
            ],
            'abComparison' => $abComparison,
            'sitesBreakdown' => $sitesBreakdown,
            'byDevice' => $byDevice,
            'byGeo' => $byGeo,
            'blockedClicks' => $blockedClicks,
            'dailyComparison' => $dailyComparison,
        ]);
    }

    /**
     * Met en pause ou reprend une campagne active/en pause.
     */
    public function toggleStatus(Campaign $campaign): RedirectResponse
    {
        Gate::authorize('update', $campaign);

        if ($campaign->status === CampaignStatus::Active) {
            $campaign->update(['status' => CampaignStatus::Paused]);

            return back()->with('success', __('Campagne mise en pause.'));
        }

        if ($campaign->status === CampaignStatus::Paused) {
            $campaign->update(['status' => CampaignStatus::Active]);

            return back()->with('success', __('Campagne réactivée.'));
        }

        return back()->with('error', __('Seule une campagne active ou en pause peut changer d\'état.'));
    }

    public function submitForReview(Campaign $campaign): RedirectResponse
    {
        Gate::authorize('update', $campaign);

        if (! in_array($campaign->status, [CampaignStatus::Draft, CampaignStatus::Rejected], true)) {
            return back()->with('error', __('Cette campagne ne peut pas être soumise.'));
        }

        $campaign->update([
            'status' => CampaignStatus::PendingReview,
            'rejection_reason' => null,
        ]);

        return back()->with('success', __('Campagne soumise pour validation par l\'administrateur.'));
    }
}
