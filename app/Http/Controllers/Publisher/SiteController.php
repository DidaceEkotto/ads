<?php

namespace App\Http\Controllers\Publisher;

use App\Enums\AdZoneFormat;
use App\Enums\SiteStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Publisher\StoreSiteRequest;
use App\Models\AdZone;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SiteController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $sites = $request->user()->sites()
            ->withCount('adZones')
            ->latest()
            ->get()
            ->map(fn (Site $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'domain' => $s->domain,
                'category' => $s->category,
                'language' => $s->language,
                'status' => $s->status->value,
                'status_label' => $s->status->label(),
                'rejection_reason' => $s->rejection_reason,
                'auto_ads_enabled' => (bool) $s->auto_ads_enabled,
                'ad_zones_count' => $s->ad_zones_count + ($s->auto_ads_enabled ? count($s->allowed_formats ?: ['native', 'square_300x250']) : 0),
            ]);

        return Inertia::render('Publisher/Sites/Index', [
            'sites' => $sites,
            'categories' => ['actualites', 'sport', 'tech', 'divertissement', 'business', 'education', 'lifestyle'],
            'formats' => collect(AdZoneFormat::cases())->map(fn ($f) => [
                'value' => $f->value,
                'label' => $f->label(),
            ]),
        ]);
    }

    public function store(StoreSiteRequest $request): RedirectResponse
    {
        $formats = $request->validated('allowed_formats')
            ?: [AdZoneFormat::Native->value, AdZoneFormat::Square300x250->value];

        $request->user()->sites()->create([
            'name' => $request->validated('name'),
            'domain' => $request->validated('domain'),
            'category' => $request->validated('category'),
            'language' => $request->validated('language'),
            'status' => SiteStatus::Pending,
            'verification_token' => Str::random(40),
            'site_token' => Str::lower(Str::random(20)),
            'auto_ads_enabled' => true,
            'allowed_formats' => $formats,
        ]);

        return back()->with('success', __('Site enregistré. Une fois approuvé, copiez simplement la balise <head> sur votre site.'));
    }

    public function show(Site $site): InertiaResponse
    {
        Gate::authorize('view', $site);

        if (! $site->site_token) {
            $site->update(['site_token' => Str::lower(Str::random(20))]);
        }

        $zones = $site->adZones()->latest()->get()->map(fn (AdZone $z) => [
            'id' => $z->id,
            'name' => $z->name,
            'token' => $z->token,
            'format' => $z->format->value,
            'format_label' => $z->format->label(),
            'is_native' => $z->is_native,
            'is_active' => $z->is_active,
            'js_snippet' => $z->jsSnippet(),
            'iframe_snippet' => $z->iframeSnippet(),
        ]);

        return Inertia::render('Publisher/Sites/Show', [
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'domain' => $site->domain,
                'status' => $site->status->value,
                'status_label' => $site->status->label(),
                'is_approved' => $site->isApproved(),
                'auto_ads_enabled' => $site->auto_ads_enabled,
                'allowed_formats' => $site->allowed_formats ?: [AdZoneFormat::Native->value, AdZoneFormat::Square300x250->value],
                'custom_cdn_host' => $site->custom_cdn_host,
                'auto_ads_snippet' => $site->autoAdsHeadSnippet(),
            ],
            'zones' => $zones,
            'formats' => collect(AdZoneFormat::cases())->map(fn ($f) => [
                'value' => $f->value,
                'label' => $f->label(),
            ]),
        ]);
    }

    public function updateAutoAds(Request $request, Site $site): RedirectResponse
    {
        Gate::authorize('update', $site);

        $validated = $request->validate([
            'auto_ads_enabled' => ['required', 'boolean'],
            'allowed_formats' => ['required', 'array', 'min:1'],
            'allowed_formats.*' => [\Illuminate\Validation\Rule::enum(AdZoneFormat::class)],
            'custom_cdn_host' => ['nullable', 'url:http,https', 'max:255'],
        ]);

        $site->update($validated);

        return back()->with('success', __('Configuration des publicités automatiques mise à jour.'));
    }
}
