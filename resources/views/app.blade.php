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
    <div id="app" data-page="{{ json_encode($page) }}">
        <!-- Splash / Initial Boot Skeleton Loader (Digantikan otomatis oleh Vue saat mount) -->
        <div id="initial-skeleton-loader" class="min-h-screen flex flex-col bg-white">
            <div class="h-16 border-b border-slate-100 flex items-center justify-between px-6">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-200 animate-pulse"></div>
                    <div class="w-36 h-4 rounded-lg bg-slate-100 animate-pulse"></div>
                </div>
                <div class="flex gap-2">
                    <div class="w-8 h-8 rounded-full bg-slate-100 animate-pulse"></div>
                </div>
            </div>
            <div class="p-6 max-w-7xl mx-auto w-full space-y-6">
                <div class="w-48 h-7 bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="h-44 rounded-3xl bg-slate-50 border border-slate-100 animate-pulse"></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <div class="h-28 rounded-2xl bg-slate-50 border border-slate-100 animate-pulse"></div>
                    <div class="h-28 rounded-2xl bg-slate-50 border border-slate-100 animate-pulse"></div>
                    <div class="h-28 rounded-2xl bg-slate-50 border border-slate-100 animate-pulse"></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
