<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} — Slam In</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $frontendUrl }}">
    <meta property="og:site_name" content="Slam In">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $imageUrl }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1080">
    <meta property="og:image:height" content="1080">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $imageUrl }}">
</head>
<body>
    <p><a href="{{ $frontendUrl }}">{{ $title }}</a></p>
</body>
</html>
