<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $siteName = \App\Models\Setting::getValue('site_name', config('app.name', 'CM-Ads'));
        $siteDesc = \App\Models\Setting::getValue('site_description', '');
        $siteKeywords = \App\Models\Setting::getValue('site_keywords', '');
        $seoImg = \App\Models\Setting::getValue('seo_image_path', '');
    @endphp
    <title inertia>{{ $siteName }}</title>
    @if($siteDesc)<meta name="description" content="{{ $siteDesc }}">@endif
    @if($siteKeywords)<meta name="keywords" content="{{ $siteKeywords }}">@endif
    <meta property="og:title" content="{{ $siteName }}">
    @if($siteDesc)<meta property="og:description" content="{{ $siteDesc }}">@endif
    @if($seoImg)<meta property="og:image" content="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($seoImg) }}">@endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="bg-light">
    @inertia
</body>
</html>
