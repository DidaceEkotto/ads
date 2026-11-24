<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>html,body{margin:0;padding:0;width:100%;height:100%;text-align:center;font-family:system-ui,sans-serif;}</style>
</head>
<body>
    <a href="{{ $clickUrl }}" target="_blank" rel="noopener sponsored" style="display:inline-block;max-width:100%;">
        <img src="{{ $creative->mediaUrl() }}" alt="{{ $creative->title }}" loading="lazy" style="max-width:100%;width:auto;height:auto;border:0;display:block;margin:0 auto;">
    </a>
</body>
</html>
