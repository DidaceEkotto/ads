<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-slate mb-1">{{ __('Vue globale de la plateforme') }}</h1>
            <p class="text-muted small mb-0">Supervision des revenus, validations, retraits Mobile Money et sécurité</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.validations') }}" class="btn btn-coral btn-sm fw-semibold">
                <i class="bi bi-check2-square me-1"></i>File de validation ({{ $pendingSitesCount + $pendingCampaignsCount }})
            </a>
            <a href="{{ route('admin.payouts') }}" class="btn btn-outline-dark btn-sm fw-semibold">
                <i class="bi bi-cash-stack me-1"></i>Retraits ({{ $pendingPayoutsCount }})
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-medium">{{ __('Revenus plateforme') }}</span>
                    <span class="rounded-3 bg-coral-subtle text-coral d-inline-flex align-items-center justify-content-center" style="width:36px;height:36px;"><i class="bi bi-cash-coin"></i></span>
                </div>
                <div class="fs-3 fw-bold text-slate">{{ number_format($platformRevenueXaf, 0, ',', ' ') }} FCFA</div>
                <div class="small text-muted">{{ number_format($totalValidClicks, 0, ',', ' ') }} clics valides</div>
            </div></div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-medium">{{ __('Annonceurs / Éditeurs') }}</span>
                    <span class="rounded-3 bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center" style="width:36px;height:36px;"><i class="bi bi-people"></i></span>
                </div>
                <div class="fs-3 fw-bold text-slate">{{ $advertisersCount }} / {{ $publishersCount }}</div>
                <div class="small text-muted">{{ number_format($totalImpressions, 0, ',', ' ') }} impressions</div>
            </div></div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-medium">{{ __('En attente validation') }}</span>
                    <span class="rounded-3 bg-warning-subtle text-warning d-inline-flex align-items-center justify-content-center" style="width:36px;height:36px;"><i class="bi bi-hourglass-split"></i></span>
                </div>
                <div class="fs-3 fw-bold text-slate">{{ $pendingSitesCount + $pendingCampaignsCount }}</div>
                <div class="small text-muted">{{ $pendingSitesCount }} sites • {{ $pendingCampaignsCount }} campagnes</div>
            </div></div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-medium">{{ __('Retraits / Alertes fraude') }}</span>
                    <span class="rounded-3 bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center" style="width:36px;height:36px;"><i class="bi bi-shield-exclamation"></i></span>
                </div>
                <div class="fs-3 fw-bold text-slate">{{ $pendingPayoutsCount }} / {{ $unresolvedFraudCount }}</div>
                <div class="small text-muted">À traiter par l'équipe</div>
            </div></div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 fw-bold text-slate">Top Campagnes</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Campagne</th><th>Dépensé</th><th>Statut</th></tr></thead>
                        <tbody>
                            @forelse($topCampaigns as $c)
                                <tr>
                                    <td class="fw-semibold">{{ $c->name }}</td>
                                    <td class="text-coral fw-bold">{{ number_format($c->spent_xaf, 0, ',', ' ') }} FCFA</td>
                                    <td><span class="badge bg-light text-dark border">{{ $c->status->label() }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">Aucune campagne.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 fw-bold text-slate">Sites récents</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Domaine</th><th>Éditeur</th><th>Statut</th></tr></thead>
                        <tbody>
                            @forelse($recentSites as $s)
                                <tr>
                                    <td class="fw-semibold">{{ $s->domain }}</td>
                                    <td class="small text-muted">{{ $s->publisher?->name }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $s->status->label() }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">Aucun site.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
