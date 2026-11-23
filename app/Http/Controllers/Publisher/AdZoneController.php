<?php

namespace App\Http\Controllers\Publisher;

use App\Enums\AdZoneFormat;
use App\Http\Controllers\Controller;
use App\Http\Requests\Publisher\StoreAdZoneRequest;
use App\Models\AdZone;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class AdZoneController extends Controller
{
    public function store(StoreAdZoneRequest $request, Site $site): RedirectResponse
    {
        Gate::authorize('update', $site);

        if (! $site->isApproved()) {
            return back()->with('error', __('Votre site doit être approuvé avant de créer des emplacements.'));
        }

        $format = AdZoneFormat::from($request->validated('format'));

        $site->adZones()->create([
            'name' => $request->validated('name'),
            'token' => Str::lower(Str::random(16)),
            'format' => $format,
            'is_native' => $format === AdZoneFormat::Native || $request->boolean('is_native'),
            'is_active' => true,
        ]);

        return back()->with('success', __('Emplacement créé. Copiez le code d\'intégration ci-dessous.'));
    }

    public function toggle(Site $site, AdZone $adZone): RedirectResponse
    {
        Gate::authorize('update', $site);

        if ($adZone->site_id !== $site->id) {
            abort(404);
        }

        $adZone->update(['is_active' => ! $adZone->is_active]);

        return back()->with('success', __('Statut de l\'emplacement mis à jour.'));
    }
}
