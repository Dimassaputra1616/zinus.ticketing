<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>IT Ticketing</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}">
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#12824C">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    <link rel="manifest" href="/build/manifest.webmanifest">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
    <main class="min-h-screen flex items-center justify-center px-6 py-12 bg-gradient-to-br from-slate-100 via-slate-200 to-slate-100">
        <div class="w-full max-w-5xl">
            @yield('content')
        </div>
    </main>
</body>
</html>
