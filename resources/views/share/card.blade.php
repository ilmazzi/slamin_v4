<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} — Slam In</title>
    @if ($description !== '')
    <meta name="description" content="{{ $description }}">
    @endif
    <link rel="canonical" href="{{ $frontendUrl }}">
    <meta property="og:site_name" content="Slam In">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $title }}">
    @if ($description !== '')
    <meta property="og:description" content="{{ $description }}">
    @endif
    <meta property="og:url" content="{{ $canonicalUrl }}">
    @if ($imageUrl)
    <meta property="og:image" content="{{ $imageUrl }}">
    @isset($imageSize)
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="{{ $imageSize->width() }}">
    <meta property="og:image:height" content="{{ $imageSize->height() }}">
    @endisset
    @endif
    <meta name="twitter:card" content="{{ $imageUrl ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $title }}">
    @if ($description !== '')
    <meta name="twitter:description" content="{{ $description }}">
    @endif
    @if ($imageUrl)
    <meta name="twitter:image" content="{{ $imageUrl }}">
    @endif
</head>
<body>
    <p><a href="{{ $frontendUrl }}">{{ $title }}</a></p>
</body>
</html>
