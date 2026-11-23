<div>
    <h1 class="h4 fw-bold mb-4">{{ __('Gestion des retraits (MTN MoMo / Orange Money)') }}</h1>
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Éditeur</th><th>Compte</th><th>Montant</th><th>Statut</th><th>Preuve</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($payouts as $payout)
                        <tr>
                            <td>{{ $payout->publisher->name }}</td>
                            <td class="small">{{ $payout->paymentMethod?->providerLabel() }} — {{ $payout->paymentMethod?->account_number }}</td>
                            <td class="fw-bold">{{ number_format($payout->amount_xaf, 0, ',', ' ') }} FCFA</td>
                            <td><span class="badge bg-secondary">{{ $payout->status->label() }}</span></td>
                            <td class="small">
                                @if($payout->proof_path)
                                    <a href="{{ $payout->proofUrl() }}" target="_blank" class="badge bg-info text-dark text-decoration-none">Capture</a>
                                @endif
                                @if($payout->gateway_reference)
                                    <code>{{ $payout->gateway_reference }}</code>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($payout->status->value === 'pending')
                                    <button wire:click="payViaSasPay({{ $payout->id }})" class="btn btn-primary btn-sm">Payer auto SasPay</button>
                                    <button wire:click="openApproveModal({{ $payout->id }})" class="btn btn-success btn-sm">Valider &amp; Preuve</button>
                                    <button wire:click="reject({{ $payout->id }})" class="btn btn-outline-danger btn-sm">Rejeter</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucune demande de retrait.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($selectedPayoutId)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <form wire:submit="approve" class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Valider le retrait &amp; envoyer la preuve</h5>
                        <button type="button" class="btn-close" wire:click="$set('selectedPayoutId', null)"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Référence transaction MoMo / OM</label>
                            <input wire:model="gatewayReference" type="text" class="form-control" placeholder="ex: MP261123.1620.A12345">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Capture d'écran du transfert (JPG, PNG, WebP)</label>
                            <input wire:model="proofImage" type="file" accept="image/jpeg,image/png,image/webp" class="form-control">
                            @error('proofImage') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Note pour l'éditeur (optionnel)</label>
                            <textarea wire:model="adminNote" rows="2" class="form-control" placeholder="Transfert effectué avec succès."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" wire:click="$set('selectedPayoutId', null)">Annuler</button>
                        <button type="submit" class="btn btn-success fw-semibold">Confirmer le paiement</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
