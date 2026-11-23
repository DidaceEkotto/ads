<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture — {{ $transaction->reference }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>@media print { .no-print { display: none !important; } }</style>
</head>
<body class="bg-light py-5">
    <div class="container" style="max-width: 680px;">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
            <div class="d-flex justify-content-between align-items-start mb-4 pb-3 border-bottom">
                <div>
                    <h1 class="h4 fw-bold mb-1">{{ $siteName }}</h1>
                    <div class="small text-muted">Facture de recharge publicitaire</div>
                </div>
                <span class="badge bg-success fs-6">PAYÉE</span>
            </div>

            <div class="row g-3 mb-4 small">
                <div class="col-sm-6">
                    <div class="text-muted">N° Facture / Référence</div>
                    <div class="fw-semibold"><code>{{ $transaction->reference }}</code></div>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted">Date</div>
                    <div class="fw-semibold">{{ $transaction->created_at?->format('d/m/Y H:i') }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted">Client Annonceur</div>
                    <div class="fw-semibold">{{ $user->name }} ({{ $user->email }})</div>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted">Moyen de paiement</div>
                    <div class="fw-semibold">{{ strtoupper((string) $transaction->gateway) }}</div>
                </div>
            </div>

            <div class="p-3 rounded-3 bg-light border d-flex justify-content-between align-items-center mb-4">
                <span class="fw-semibold">{{ $transaction->description }}</span>
                <span class="fs-3 fw-bold text-dark">{{ number_format($transaction->amount_xaf, 0, ',', ' ') }} FCFA</span>
            </div>

            <div class="d-flex justify-content-between no-print">
                <a href="{{ route('advertiser.wallet.index') }}" class="btn btn-outline-secondary btn-sm">Retour</a>
                <button onclick="window.print()" class="btn btn-dark btn-sm">Imprimer / PDF</button>
            </div>
        </div>
    </div>
</body>
</html>
