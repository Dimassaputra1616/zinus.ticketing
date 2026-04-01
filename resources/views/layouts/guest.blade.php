<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'IT Ticketing') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        <link rel="shortcut icon" href="{{ asset('favicon.png') }}">

        <!-- PWA Meta Tags -->
        <meta name="theme-color" content="#0B2F26">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
        <link rel="manifest" href="/build/manifest.webmanifest">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Geist:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased text-slate-900" style="padding-top: env(safe-area-inset-top); padding-bottom: env(safe-area-inset-bottom);">
        <div class="relative min-h-screen overflow-hidden bg-[#021711]">
            <div class="absolute inset-0">
                <img
                    src="https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=1600&q=80"
                    alt="Workspace background"
                    class="h-full w-full object-cover object-center"
                >
                <div class="absolute inset-0 bg-[#0B2F26]/70"></div>
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-950/60 via-emerald-900/40 to-black/70 mix-blend-multiply"></div>
                <div class="absolute inset-0 opacity-30 mix-blend-overlay bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.18),transparent_45%),radial-gradient(circle_at_bottom,rgba(0,0,0,0.35),transparent_55%)]"></div>
            </div>

            <div class="relative z-10 flex min-h-screen items-center justify-center px-4 py-12 sm:px-6 lg:px-8" style="min-height: calc(100vh - env(safe-area-inset-top) - env(safe-area-inset-bottom));">
                <div class="grid w-full max-w-5xl gap-8 lg:grid-cols-[1.05fr_1fr]">
                    {{-- Left Panel (Desktop only) --}}
                    <div class="hidden lg:flex flex-col justify-between rounded-3xl border border-white/10 bg-white/5 p-10 text-white shadow-2xl shadow-emerald-950/40 backdrop-blur-sm">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.4em] text-emerald-100/80">IT Support Portal</p>
                            <h1 class="mt-5 text-4xl font-semibold leading-tight">Selesaikan kebutuhan IT Anda bersama tim Zinus Dream Indonesia</h1>
                            <p class="mt-5 text-sm text-emerald-100/80">Buat tiket baru, pantau progres penanganan, dan jaga produktivitas tim tetap maksimal dengan dukungan IT yang responsif.</p>
                        </div>
                        <ul class="mt-10 space-y-4 text-sm text-emerald-100/80">
                            <li class="flex items-start gap-3">
                                <span class="mt-1 inline-flex h-2 w-2 rounded-full bg-emerald-300"></span>
                                <span>Pantau status tiket secara real-time hingga masalah terselesaikan.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-1 inline-flex h-2 w-2 rounded-full bg-emerald-300"></span>
                                <span>Kolaborasi nyaman dengan tim IT melalui catatan dan lampiran.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-1 inline-flex h-2 w-2 rounded-full bg-emerald-300"></span>
                                <span>Prioritaskan tugas kritikal dengan SLA yang jelas dan terukur.</span>
                            </li>
                        </ul>
                    </div>

                    {{-- Login Card --}}
                    <div class="rounded-3xl border border-emerald-900/10 bg-white/95 px-8 py-10 sm:px-10 sm:py-12 shadow-[0_30px_70px_-45px_rgba(7,45,33,0.95)] backdrop-blur">
                        {{-- Mobile-only branding --}}
                        <div class="mb-6 flex items-center justify-center gap-3 lg:hidden">
                            <img src="{{ asset('favicon.png') }}" alt="Zinus" class="h-10 w-10 rounded-xl shadow-lg">
                            <div class="leading-tight">
                                <p class="text-[9px] font-bold uppercase tracking-[0.3em] text-emerald-600/70">Zinus Dream</p>
                                <p class="text-base font-bold text-[#0B2F26]">IT Ticketing</p>
                            </div>
                        </div>

                        <div class="mb-8 text-center">
                            <h2 class="text-2xl font-semibold text-[#0B2F26]">Masuk ke Portal IT</h2>
                            <p class="mt-2 text-sm text-emerald-700/80">Gunakan akun perusahaan Anda untuk memulai</p>
                        </div>

                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
        @livewireScripts
    </body>
</html>
