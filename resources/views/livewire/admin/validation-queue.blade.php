<div>
    <h1 class="h4 fw-bold mb-4">{{ __('File de validation') }}</h1>
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">Sites web en attente ({{ $sites->count() }})</div>
        <div class="list-group list-group-flush">
            @forelse($sites as $site)
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div><strong>{{ $site->name }}</strong> ({{ $site->domain }}) — {{ $site->publisher->name }}</div>
                    <div class="d-flex gap-2">
                        <button wire:click="approveSite({{ $site->id }})" class="btn btn-success btn-sm">Approuver</button>
                        <button wire:click="rejectSite({{ $site->id }})" class="btn btn-outline-danger btn-sm">Rejeter</button>
                    </div>
                </div>
            @empty
                <div class="list-group-item text-muted">Aucun site en attente.</div>
            @endforelse
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">Campagnes en attente ({{ $campaigns->count() }})</div>
        <div class="list-group list-group-flush">
            @forelse($campaigns as $campaign)
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div><strong>{{ $campaign->name }}</strong> ({{ number_format($campaign->total_budget_xaf, 0, ',', ' ') }} FCFA) — {{ $campaign->advertiser->name }}</div>
                    <div class="d-flex gap-2">
                        <button wire:click="approveCampaign({{ $campaign->id }})" class="btn btn-success btn-sm">Activer</button>
                        <button wire:click="rejectCampaign({{ $campaign->id }})" class="btn btn-outline-danger btn-sm">Rejeter</button>
                    </div>
                </div>
            @empty
                <div class="list-group-item text-muted">Aucune campagne en attente.</div>
            @endforelse
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold">Comptes sociaux & Posts à valider</div>
        <div class="list-group list-group-flush">
            @foreach($socialAccounts as $acc)
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>Compte {{ $acc->platform->label() }} (@{{ $acc->handle }}) — Code bio : <code>{{ $acc->verification_code }}</code></div>
                    <button wire:click="approveSocialAccount({{ $acc->id }})" class="btn btn-success btn-sm">Approuver</button>
                </div>
            @endforeach
            @foreach($socialPlacements as $pl)
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>Post {{ $pl->campaign->name }} — <a href="{{ $pl->post_url }}" target="_blank">{{ $pl->post_url }}</a></div>
                    <button wire:click="approveSocialPlacement({{ $pl->id }})" class="btn btn-success btn-sm">Valider</button>
                </div>
            @endforeach
        </div>
    </div>
</div>
