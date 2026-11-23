<div>
    <h1 class="h3 fw-bold text-slate mb-4">{{ __('Paramètres de la plateforme') }}</h1>
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    <form wire:submit="save" class="row g-4">
        <!-- Identité, Branding & SEO -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold text-coral text-uppercase mb-3">Identité &amp; SEO</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nom de la plateforme *</label>
                            <input wire:model="siteName" type="text" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Mots-clés SEO</label>
                            <input wire:model="siteKeywords" type="text" class="form-control" placeholder="publicité, cameroun, mtn momo...">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Description (SEO)</label>
                            <textarea wire:model="siteDescription" rows="2" class="form-control"></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Logo du site</label>
                            @if($currentLogoUrl)
                                <div class="mb-2"><img src="{{ $currentLogoUrl }}" alt="Logo" class="border rounded p-1 bg-light" style="max-height:48px;"></div>
                            @endif
                            <input wire:model="siteLogo" type="file" accept="image/*" class="form-control form-control-sm">
                            @error('siteLogo') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Image SEO (OpenGraph)</label>
                            @if($currentSeoImageUrl)
                                <div class="mb-2"><img src="{{ $currentSeoImageUrl }}" alt="SEO" class="border rounded p-1 bg-light" style="max-height:48px;"></div>
                            @endif
                            <input wire:model="seoImage" type="file" accept="image/*" class="form-control form-control-sm">
                            @error('seoImage') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold text-coral text-uppercase mb-3">Coordonnées &amp; Support</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email de contact</label>
                            <input wire:model="siteEmail" type="email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Téléphone</label>
                            <input wire:model="sitePhone" type="tel" class="form-control" placeholder="+2376XXXXXXXX">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Boîte postale (facultatif)</label>
                            <input wire:model="sitePostalBox" type="text" class="form-control" placeholder="BP 1234 Douala">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Lien Support WhatsApp *</label>
                            <input wire:model="supportWhatsapp" type="url" class="form-control" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Maintenance & Tarification -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 mb-4 {{ $maintenanceMode ? 'border border-danger' : '' }}">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="h6 fw-bold text-danger text-uppercase mb-0">
                            <i class="bi bi-tools me-1"></i>Mode Maintenance
                        </h2>
                        <div class="form-check form-switch">
                            <input wire:model.live="maintenanceMode" id="maintSwitch" type="checkbox" class="form-check-input">
                            <label for="maintSwitch" class="form-check-label small fw-bold">
                                {{ $maintenanceMode ? 'ACTIVÉ' : 'Désactivé' }}
                            </label>
                        </div>
                    </div>
                    <p class="small text-muted mb-2">
                        Si activé, tout le site public affiche un message aux visiteurs sauf <code>/admin/*</code>.
                    </p>
                    <textarea wire:model="maintenanceMessage" rows="2" class="form-control form-control-sm" placeholder="Message affiché aux visiteurs..."></textarea>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold text-coral text-uppercase mb-3">Tarification &amp; Commissions</h2>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Budget/jour (FCFA)</label>
                            <input wire:model="dailyCampaignCostXaf" type="number" min="500" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Prix clic (FCFA)</label>
                            <input wire:model="cpcUnitPriceXaf" type="number" min="5" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Prix vue scroll (FCFA)</label>
                            <input wire:model="viewUnitPriceXaf" type="number" min="1" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Part éditeur (%)</label>
                            <input wire:model="publisherSharePercent" type="number" min="10" max="95" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Retrait min (FCFA)</label>
                            <input wire:model="minPayoutXaf" type="number" min="500" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Blocage gains (j)</label>
                            <input wire:model="earningsHoldDays" type="number" min="1" max="60" class="form-control" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-coral w-100 fw-semibold mt-4">
                        <i class="bi bi-check2-circle me-1"></i>Enregistrer tous les paramètres
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
