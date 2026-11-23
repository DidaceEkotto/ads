<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>body{margin:0;padding:0;overflow:hidden;font-family:system-ui,sans-serif;}</style>
</head>
<body>
    <a href="{{ $clickUrl }}" target="_blank" rel="noopener sponsored">
        <img src="{{ $creative->mediaUrl() }}" alt="{{ $creative->title }}" loading="lazy" style="max-width:100%;height:auto;border:0;">
    </a>
</body>
</html>
