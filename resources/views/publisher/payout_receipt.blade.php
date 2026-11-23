<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Reçu de retrait — {{ $payout->reference }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body class="bg-light py-5">
    <div class="container" style="max-width: 680px;">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
            <div class="d-flex justify-content-between align-items-start mb-4 pb-3 border-bottom">
                <div>
                    <h1 class="h4 fw-bold mb-1">CM-Ads Cameroun</h1>
                    <div class="small text-muted">Reçu de paiement Éditeur</div>
                </div>
                <span class="badge {{ $payout->status->value === 'paid' ? 'bg-success' : 'bg-secondary' }} fs-6">
                    {{ strtoupper($payout->status->label()) }}
                </span>
            </div>

            <div class="row g-3 mb-4 small">
                <div class="col-sm-6">
                    <div class="text-muted">Référence</div>
                    <div class="fw-semibold"><code>{{ $payout->reference }}</code></div>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted">Date de traitement</div>
                    <div class="fw-semibold">{{ ($payout->processed_at ?? $payout->created_at)?->format('d/m/Y H:i') }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted">Bénéficiaire</div>
                    <div class="fw-semibold">{{ $payout->publisher->name }} ({{ $payout->publisher->email }})</div>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted">Compte Mobile Money</div>
                    <div class="fw-semibold">{{ $payout->paymentMethod?->providerLabel() }} — {{ $payout->paymentMethod?->account_number }}</div>
                </div>
                @if($payout->gateway_reference)
                    <div class="col-12">
                        <div class="text-muted">ID Transaction Opérateur</div>
                        <div class="fw-bold"><code>{{ $payout->gateway_reference }}</code></div>
                    </div>
                @endif
            </div>

            <div class="p-3 rounded-3 bg-light border d-flex justify-content-between align-items-center mb-4">
                <span class="fw-semibold">Montant versé</span>
                <span class="fs-3 fw-bold text-success">{{ number_format($payout->amount_xaf, 0, ',', ' ') }} FCFA</span>
            </div>

            @if($payout->proof_path)
                <div class="mb-4">
                    <div class="small fw-semibold text-muted mb-2">Capture d'écran justificative :</div>
                    <img src="{{ $payout->proofUrl() }}" alt="Preuve de paiement" class="img-fluid rounded-3 border">
                </div>
            @endif

            <div class="d-flex justify-content-between no-print">
                <a href="{{ route('publisher.payouts.index') }}" class="btn btn-outline-secondary btn-sm">Retour</a>
                <button onclick="window.print()" class="btn btn-dark btn-sm">Imprimer / Enregistrer PDF</button>
            </div>
        </div>
    </div>
</body>
</html>
