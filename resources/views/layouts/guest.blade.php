<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'IT Ticketing') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-900">
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

            <div class="relative z-10 flex min-h-screen items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
                <div class="grid w-full max-w-5xl gap-8 lg:grid-cols-[1.05fr_1fr]">
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

                    <div class="rounded-3xl border border-emerald-900/10 bg-white/95 px-10 py-12 shadow-[0_30px_70px_-45px_rgba(7,45,33,0.95)] backdrop-blur">
                        <div class="mb-8 text-center">
                            <h2 class="mt-6 text-2xl font-semibold text-[#0B2F26]">Masuk ke Portal IT</h2>
                            <p class="mt-2 text-sm text-emerald-700/80">Gunakan akun perusahaan Anda untuk memulai</p>
                        </div>

                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
