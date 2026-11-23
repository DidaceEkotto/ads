<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance — {{ $siteName }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>body { font-family: 'Poppins', sans-serif; background: #fef4ea; color: #192733; }</style>
</head>
<body class="min-vh-100 d-flex align-items-center justify-content-center p-4">
    <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5 text-center bg-white" style="max-width: 540px;">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="mx-auto mb-3" style="max-height: 56px; object-fit: contain;">
        @else
            <div class="mx-auto mb-3 rounded-3 d-inline-flex align-items-center justify-content-center text-white" style="width:54px;height:54px;background:#ff5c35;">
                <i class="bi bi-tools fs-3"></i>
            </div>
        @endif
        <h1 class="h3 fw-bold mb-2">{{ $siteName }} en maintenance</h1>
        <p class="text-muted mb-4">{{ $message }}</p>
        @if($supportWhatsapp)
            <a href="{{ $supportWhatsapp }}" target="_blank" rel="noopener" class="btn btn-outline-success fw-semibold">
                <i class="bi bi-whatsapp me-1"></i>Contacter le support WhatsApp
            </a>
        @endif
    </div>
</body>
</html>
