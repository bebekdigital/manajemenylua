<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Data Center - Yayasan Li Ulil Albab Karanganyar</title>

    <!-- PWA & Mobile Web App Meta Tags (Putih sesuai background page web) -->
    <meta name="theme-color" content="#ffffff" />
    <meta name="theme-color" media="(prefers-color-scheme: light)" content="#ffffff" />
    <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#ffffff" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="default" />
    <meta name="apple-mobile-web-app-title" content="Ulil Albab" />
    <meta name="application-name" content="Ulil Albab" />
    <meta name="msapplication-TileColor" content="#ffffff" />
    <meta name="msapplication-navbutton-color" content="#ffffff" />

    <!-- PWA Manifest & Icons -->
    <link rel="manifest" href="/manifest.json?v=2" />
    <link rel="icon" type="image/png" sizes="32x32" href="/images/icons/favicon-32x32.png" />
    <link rel="icon" type="image/png" sizes="16x16" href="/images/icons/favicon-16x16.png" />
    <link rel="apple-touch-icon" sizes="180x180" href="/images/icons/apple-touch-icon.png" />
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="bg-white text-on-background font-body-md min-h-screen antialiased">
    @inertia
</body>
</html>
