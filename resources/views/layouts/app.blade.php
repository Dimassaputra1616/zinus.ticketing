<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'IT Ticketing') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        <link rel="shortcut icon" href="{{ asset('favicon.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Geist:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

        <!-- Scripts -->
        <script>
            (function () {
                if (!window.crypto) {
                    window.crypto = {};
                }

                if (typeof window.crypto.randomUUID !== 'function') {
                    window.crypto.randomUUID = function () {
                        const ts = Date.now().toString(36);
                        const rand = Math.random().toString(36).substring(2, 10);
                        return `${ts}-${rand}`;
                    };
                }

                if (typeof window.safeUUID !== 'function') {
                    window.safeUUID = function () {
                        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                            return window.crypto.randomUUID();
                        }
                        const ts = Date.now().toString(36);
                        const rand = Math.random().toString(36).substring(2, 10);
                        return `${ts}-${rand}`;
                    };
                }
            })();
        </script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css"/>
        <script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            html { scroll-behavior: smooth; }
            html, body { min-height: 100vh; }
            :root {
                --zinus-green: #12824C;
                --zinus-green-strong: #0F6D3F;
                --zinus-mint: #53B77A;
                --zinus-blue: #23455D;
                --zinus-gold: #FFD966;
                --zinus-soft: #EDF3F2;
                --zinus-soft-alt: #F6F9F8;
            }
            body {
                position: relative;
                transition: opacity .25s ease, transform .25s ease;
                background: var(--zinus-soft-alt);
                font-family: 'Inter', 'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont;
                }
            h1, h2, h3, h4, .heading-font {
                font-family: 'Geist', 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont;
                letter-spacing: -0.4px;
                font-weight: 600;
            }
            body::before { display: none; }
            body.page-preload { opacity: 0; transform: translateY(12px); }
            body.page-loaded { opacity: 1; transform: none; }

            .reveal-on-scroll { opacity: 0; transform: translateY(10px); transition: opacity .24s ease-in-out, transform .24s ease-in-out; }
            .page-loaded .reveal-on-scroll { opacity: 1; transform: translateY(0); }

            .sidebar-nav a {
                transition: transform .2s ease, background-color .2s ease, box-shadow .2s ease, border-color .2s ease, color .2s ease;
                border-left: 3px solid transparent;
            }
            .sidebar-nav a:hover {
                transform: translateX(6px) translateY(-1px);
                background: linear-gradient(120deg, rgba(83,183,122,0.15), rgba(18,130,76,0.07));
                border-left-color: rgba(83,183,122,0.7);
                box-shadow: 0 12px 28px rgba(0,0,0,0.25);
            }
            .sidebar-nav a:active { transform: translateX(5px) translateY(1px); }
            .sidebar-nav a.is-active {
                background: linear-gradient(135deg, rgba(20,56,45,0.9), rgba(18,130,76,0.75));
                border-left-color: #53B77A;
                box-shadow: 0 20px 40px rgba(0,0,0,0.35);
            }

            .table-hover-row {
                transition: transform .18s ease, background-color .18s ease, box-shadow .2s ease, border-color .2s ease;
            }
            .table-hover-row:hover {
                transform: translateY(-2px);
                background: linear-gradient(135deg, rgba(237,243,242,0.95), #fff);
                box-shadow: 0 12px 26px rgba(18,130,76,0.15);
            }

            button, a, .btn-animate, .pressable { transition: all .2s ease; }
            button:active, .pressable:active { transform: scale(0.96); }
            input, select, textarea { transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease; }
            input:hover, select:hover, textarea:hover { border-color: #0a8f3c; box-shadow: 0 2px 10px rgba(10,143,60,0.08); background-color: rgba(248,250,252,0.9); }
            .input-error { border-color: #f87171 !important; box-shadow: 0 0 0 1px rgba(248,113,113,0.18) !important; }

            .search-shell:focus-within { box-shadow: 0 4px 16px rgba(16,112,67,0.15), 0 0 0 1px rgba(16,112,67,0.15); }

            .dropdown-animate { animation: dropdownFade .18s ease-in-out forwards; transform-origin: top; }
            .dropdown-arrow { transition: transform .2s ease-in-out; }
            .dropdown-open .dropdown-arrow { transform: rotate(180deg); }

            .live-dot { animation: livePulse 1.4s ease-in-out infinite; }
            .badge-live {
                position: relative;
                overflow: hidden;
                box-shadow: 0 10px 25px rgba(18,130,76,0.12);
            }
            .badge-live::after {
                content: '';
                position: absolute;
                inset: -40% auto auto -40%;
                width: 140%;
                height: 140%;
                background: radial-gradient(circle, rgba(18,130,76,0.18), transparent 60%);
                animation: liveBadgeWave 3s ease-in-out infinite;
                opacity: 0.7;
            }
            .badge-live > * { position: relative; z-index: 1; }

            .sticky-header { transition: box-shadow .2s ease; }
            .sticky-header.is-scrolled { box-shadow: 0 12px 24px rgba(15,47,34,0.06); }

            .btn-animate { transition: all .2s ease-in-out, transform .15s ease; }
            .btn-animate:hover { transform: translateY(-1px) scale(1.01); box-shadow: 0 6px 18px rgba(0,0,0,0.08); }
            .btn-animate:active { transform: translateY(1px) scale(0.99); }

            .btn-loading { position: relative; pointer-events: none; opacity: 0.8; }
            .btn-loading::after {
                content: '';
                position: absolute;
                inset: 50% auto auto 50%;
                width: 18px;
                height: 18px;
                margin: -9px 0 0 -9px;
                border-radius: 999px;
                border: 2px solid rgba(255,255,255,0.6);
                border-top-color: rgba(255,255,255,1);
                animation: spin .7s linear infinite;
            }
            .stat-card::before,
            .stat-card::after {
                content: none !important;
                display: none !important;
            }

            .surface-card {
                position: relative;
                overflow: hidden;
                transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease, transform .2s ease;
                background-image: linear-gradient(135deg, #ffffff, #F9FCFA 55%, #E8F2EE);
                border: 1px solid rgba(83, 183, 122, 0.25);
                box-shadow: 0 24px 55px rgba(18, 130, 76, 0.18);
                backdrop-filter: blur(2px);
            }
            .surface-card::before {
                content: '';
                position: absolute;
                inset: -20% auto auto -15%;
                width: 200px;
                height: 200px;
                background: radial-gradient(circle, rgba(83,183,122,0.25), transparent 65%);
                filter: blur(0px);
                opacity: 0.7;
                z-index: 0;
                border-radius: inherit;
            }
            .surface-card::after {
                content: '';
                position: absolute;
                inset: auto -10% -20% auto;
                width: 180px;
                height: 180px;
                background: radial-gradient(circle, rgba(35,69,93,0.12), transparent 65%);
                opacity: 0.5;
                z-index: 0;
                border-radius: inherit;
            }
            .surface-card > * {
                position: relative;
                z-index: 1;
            }
            .surface-card:hover {
                border-color: rgba(18, 130, 76, 0.6);
                background-image: linear-gradient(135deg, #FFFFFF, #EDF3F2);
                box-shadow: 0 32px 60px rgba(18, 130, 76, 0.25);
                transform: translateY(-2px);
            }

            .btn-interactive {
                transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease;
            }
            .btn-interactive:hover {
                transform: translateY(-1px);
                box-shadow: 0 12px 24px rgba(18, 130, 76, 0.3);
            }

            .btn-primary {
                background: linear-gradient(135deg, var(--zinus-green), var(--zinus-green-strong));
                border: 1px solid rgba(18, 130, 76, 0.85);
                box-shadow: 0 15px 30px rgba(18, 130, 76, 0.25);
            }
            .btn-primary:hover {
                background: linear-gradient(135deg, var(--zinus-green-strong), var(--zinus-green));
                box-shadow: 0 20px 34px rgba(35, 69, 93, 0.25);
            }
            .btn-secondary {
                border: 1px solid rgba(18, 130, 76, 0.4);
                color: var(--zinus-green);
                background: #fff;
            }
            .btn-secondary:hover {
                background: rgba(83, 183, 122, 0.15);
                border-color: var(--zinus-green);
                color: #0f5d33;
            }

            .badge-soft {
                transition: background-color .15s ease, color .15s ease, border-color .15s ease;
                border: 1px solid rgba(83, 183, 122, 0.3);
                background-color: rgba(83, 183, 122, 0.15);
                color: var(--zinus-green);
            }
            .badge-soft:hover {
                background-color: rgba(18, 130, 76, 0.15);
                border-color: rgba(18, 130, 76, 0.5);
                color: var(--zinus-green-strong);
            }
            .badge-chip {
                transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
            }
            .badge-chip:hover {
                transform: translateY(-1px) scale(1.02);
                box-shadow: 0 12px 20px rgba(0,0,0,0.08);
                border-color: rgba(83,183,122,0.45);
            }
            .brand-zinus {
                font-weight: 700;
                letter-spacing: 0.26em;
                background-image: linear-gradient(120deg, var(--zinus-blue), var(--zinus-green), var(--zinus-gold));
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                text-shadow: 0 2px 10px rgba(18, 130, 76, 0.16);
            }

            .role-badge-gold {
                background: linear-gradient(120deg, #fff3c4, #ffd88a);
                border: 1px solid #f1c350;
                color: #8b5e12;
                box-shadow: 0 10px 22px rgba(241, 195, 80, 0.25);
            }
            .role-badge-soft {
                background: linear-gradient(120deg, #e9f7f0, #f7fffb);
                border: 1px solid rgba(83, 183, 122, 0.35);
                color: #0f5d33;
                box-shadow: 0 8px 18px rgba(18, 130, 76, 0.15);
            }

            @keyframes spin { to { transform: rotate(360deg); } }

            @keyframes dropdownFade {
                from { opacity: 0; transform: translateY(-6px); }
                to { opacity: 1; transform: translateY(0); }
            }

            @keyframes livePulse {
                0% { transform: scale(1); opacity: 1; }
                50% { transform: scale(1.15); opacity: 0.65; }
                100% { transform: scale(1); opacity: 1; }
            }
            @keyframes liveBadgeWave {
                0% { transform: translate(-20%, -20%) scale(0.9); opacity: 0.5; }
                50% { transform: translate(0,0) scale(1.1); opacity: 0.8; }
                100% { transform: translate(-20%, -20%) scale(0.9); opacity: 0.5; }
            }

            .animate-fade-up {
                opacity: 0;
                transform: translateY(10px);
                animation: fadeUp .35s ease-in-out forwards;
            }
            @keyframes fadeUp {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .fade-in-small { opacity: 0; animation: fadeInSmall .15s ease-out forwards; }
            @keyframes fadeInSmall {
                from { opacity: 0; }
                to { opacity: 1; }
            }

            .loading-bar {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 3px;
                background: linear-gradient(90deg, rgba(16,112,67,0) 0%, rgba(16,112,67,0.8) 40%, rgba(16,112,67,0) 80%);
                background-size: 200% 100%;
                animation: loadingSlide 1.6s ease-in-out infinite;
                z-index: 50;
            }
            /* Hilangkan tombol clear bawaan input search di beberapa browser */
            input[type="search"]::-webkit-search-cancel-button,
            input[type="search"]::-webkit-search-decoration {
                -webkit-appearance: none;
                appearance: none;
            }
            @keyframes loadingSlide {
                0% { background-position: 0% 0; }
                100% { background-position: 100% 0; }
            }

            /* Preloader Styles */
            .preloader {
                position: fixed;
                inset: 0;
                z-index: 9999;
                background: #F6F9F8;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                transition: opacity 0.6s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.6s;
            }
            .preloader.hidden {
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
            }
            .preloader-logo {
                width: 120px;
                height: auto;
                animation: preloaderPulse 2s ease-in-out infinite;
                filter: drop-shadow(0 10px 20px rgba(18, 130, 76, 0.15));
            }
            .preloader-bar {
                width: 140px;
                height: 2px;
                background: rgba(18, 130, 76, 0.1);
                border-radius: 4px;
                margin-top: 24px;
                position: relative;
                overflow: hidden;
            }
            .preloader-bar-fill {
                position: absolute;
                inset: 0 auto 0 0;
                width: 40%;
                background: linear-gradient(90deg, var(--zinus-green), var(--zinus-mint));
                border-radius: inherit;
                animation: preloaderProgress 1.5s ease-in-out infinite;
            }

            /* Global Wire Loading */
            [wire\:loading] {
                pointer-events: none;
            }
            .loading-overlay {
                position: fixed;
                inset: 0;
                z-index: 9998;
                background: rgba(255, 255, 255, 0.4);
                backdrop-filter: blur(2px);
                display: none;
                align-items: center;
                justify-content: center;
                transition: opacity 0.3s ease;
            }

            @keyframes preloaderPulse {
                0%, 100% { transform: scale(1); opacity: 1; }
                50% { transform: scale(1.05); opacity: 0.8; }
            }
            @keyframes preloaderProgress {
                0% { left: -40%; }
                100% { left: 100%; }
            }
        </style>
    </head>
    @php $authUser = Auth::user(); @endphp
    <body
        x-data="{ 
            sidebarOpen: false, 
            sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
            pageLoaded: false 
        }"
        x-init="
            setTimeout(() => { 
                pageLoaded = true;
                document.body.classList.remove('page-preload'); 
                document.body.classList.add('page-loaded'); 
            }, 500);
        "
        x-effect="localStorage.setItem('sidebarCollapsed', sidebarCollapsed)"
        :class="sidebarOpen ? 'overflow-hidden max-h-screen' : ''"
        class="page-preload font-sans bg-white text-slate-800 antialiased min-h-screen overflow-x-hidden lg:flex"
        @if($authUser?->isAdmin()) data-notifications-endpoint="{{ route('admin.notifications.summary') }}" @endif
    >
        <!-- Preloader Overlay -->
        <div class="preloader" :class="{ 'hidden': pageLoaded }" aria-hidden="true">
            <div class="flex flex-col items-center">
                <img src="/images/logo-email.png" alt="Zinus Logo" class="preloader-logo">
                <div class="preloader-bar">
                    <div class="preloader-bar-fill"></div>
                </div>
                <p class="mt-4 text-[10px] font-bold uppercase tracking-[0.3em] text-emerald-800/60">Zinus Support Center</p>
            </div>
        </div>

        <div class="loading-bar" wire:loading style="display: none;" aria-hidden="true"></div>
        
        <!-- Global Processing Overlay (Shows on long requests) -->
        <div wire:loading.delay.longest class="loading-overlay" style="display: none;" aria-hidden="true">
            <div class="flex items-center gap-3 rounded-full bg-white/90 px-5 py-2.5 shadow-xl ring-1 ring-black/5">
                <svg class="h-4 w-4 animate-spin text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56" />
                </svg>
                <span class="text-xs font-bold uppercase tracking-widest text-emerald-900/80">Memproses...</span>
            </div>
        </div>

        @if (session('success') || session('error'))
            <div class="fixed top-6 right-6 z-[60] space-y-3" id="toast-stack">
                @if (session('success'))
                    <div class="toast success flex items-start gap-3 rounded-xl border border-emerald-100 bg-white/95 px-4 py-3 text-sm text-emerald-700 shadow-lg shadow-emerald-600/10">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7" /></svg>
                        </span>
                        <div>
                            <p class="font-semibold">Berhasil</p>
                            <p>{{ session('success') }}</p>
                        </div>
                    </div>
                @endif
                @if (session('error'))
                    <div class="toast error flex items-start gap-3 rounded-xl border border-rose-100 bg-white/95 px-4 py-3 text-sm text-rose-600 shadow-lg shadow-rose-500/10">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-rose-50 text-rose-500">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="M12 8v4" /><path d="M12 16h.01" /></svg>
                        </span>
                        <div>
                            <p class="font-semibold">Gagal</p>
                            <p>{{ session('error') }}</p>
                        </div>
                    </div>
                @endif
            </div>
        @endif
        @php
            $user = $authUser;
            $isAdmin = $user?->isAdmin() ?? false;

            $notificationCounts = collect();
            $ticketNotificationType = \App\Notifications\TicketCreatedNotification::class;
            $userNotificationType = \App\Notifications\UserRegisteredNotification::class;

            if ($isAdmin) {
                $notificationCounts = $user->unreadNotifications()
                    ->get()
                    ->groupBy('type')
                    ->map->count();

                if (request()->routeIs('tickets.index')) {
                    $user->unreadNotifications()->where('type', $ticketNotificationType)->update(['read_at' => now()]);
                    $notificationCounts[$ticketNotificationType] = 0;
                }

                if (request()->routeIs('users.index')) {
                    $user->unreadNotifications()->where('type', $userNotificationType)->update(['read_at' => now()]);
                    $notificationCounts[$userNotificationType] = 0;
                }
            }

            $navItems = [
                [
                    'label' => __('messages.dashboard'),
                    'route' => 'dashboard',
                    'icon' => '
                        <path d="M3 10.5 12 4l9 6.5" />
                        <path d="M5 10v9.5A1.5 1.5 0 0 0 6.5 21h11A1.5 1.5 0 0 0 19 19.5V10" />
                        <path d="M9 21V13h6v8" />
                    ',
                    'visible' => true,
                    'badgeCount' => 0,
                    'badgeType' => null,
                ],
                [
                    'label' => __('messages.my_tickets'),
                    'route' => 'tickets.mine',
                    'icon' => '
                        <path d="M3 9V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4Z" />
                        <path d="M13 5v14" />
                        <path d="M7 9h4" />
                        <path d="M7 15h4" />
                    ',
                    'visible' => !$isAdmin,
                    'badgeCount' => 0,
                    'badgeType' => null,
                ],
                [
                    'label' => __('messages.remote_system'),
                    'route' => 'remote-system.index',
                    'icon' => '
                        <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6Z" />
                        <path d="M10 19h4" />
                        <path d="M12 15v4" />
                        <circle cx="12" cy="10" r="1" />
                        <path d="M9.17 7.17a4 4 0 0 1 5.66 0" />
                        <path d="M7.05 5.05a7 7 0 0 1 9.9 0" />
                    ',
                    'visible' => $isAdmin,
                    'badgeCount' => 0,
                    'badgeType' => null,
                ],
                [
                    'label' => __('messages.ticket_list'),
                    'route' => 'tickets.index',
                    'icon' => '
                        <path d="M8 6h13" />
                        <path d="M8 12h13" />
                        <path d="M8 18h13" />
                        <path d="M3 6h.01" />
                        <path d="M3 12h.01" />
                        <path d="M3 18h.01" />
                    ',
                    'visible' => $user?->isAdmin(),
                    'badgeCount' => (int) ($notificationCounts[$ticketNotificationType] ?? 0),
                    'badgeType' => 'tickets',
                ],
                [
                    'label' => __('messages.loan_log'),
                    'route' => 'loans.index',
                    'icon' => '
                        <path d="M7 7h10M7 12h4m1 8 3-3h4a2 2 0 0 0 2-2V5c0-1.1-.9-2-2-2H6a2 2 0 0 0-2 2v15l3-3h5" />
                    ',
                    'visible' => true,
                    'badgeCount' => 0,
                    'badgeType' => null,
                ],
                [
                    'label' => __('messages.manage_asset'),
                    'route' => 'assets.index',
                    'icon' => '
                        <path d="M3 4h18v8H3z" />
                        <path d="M7 4v12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4" />
                        <path d="M5 20h14" />
                    ',
                    'visible' => $isAdmin,
                    'badgeCount' => 0,
                    'badgeType' => null,
                ],
                [
                    'label' => __('messages.live_chat'),
                    'route' => 'admin.conversations.index',
                    'icon' => '
                        <path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    ',
                    'visible' => $isAdmin,
                    'badgeCount' => $isAdmin ? \App\Models\Conversation::where('is_open', true)->withUnreadCount()->get()->sum('unread_count') : 0,
                    'badgeType' => 'conversations',
                ],
                [
                    'label' => __('messages.manage_user'),
                    'route' => 'users.index',
                    'icon' => '
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    ',
                    'visible' => $user?->isAdmin(),
                    'badgeCount' => (int) ($notificationCounts[$userNotificationType] ?? 0),
                    'badgeType' => 'users',
                ],
            ];
        @endphp

        <aside
            class="hidden lg:flex lg:sticky lg:top-0 lg:h-screen z-50 lg:shrink-0 flex-col justify-between bg-[#0E1F1B] text-emerald-50 shadow-lg shadow-black/20 ring-1 ring-black/10 overflow-y-auto overflow-x-hidden transition-all duration-300 ease-in-out"
            :class="sidebarCollapsed ? 'w-[72px]' : 'w-[260px]'"
        >
            <div class="flex flex-col w-full h-full">
                <div class="px-6 pt-10 pb-6 space-y-5 transition-all duration-300" :class="sidebarCollapsed ? 'px-3 pt-5 pb-3' : 'px-6 pt-10 pb-6'">
                        <div class="flex flex-col items-center text-center space-y-3">
                            <img src="/images/logo-email.png" alt="Zinus Dream" class="object-contain select-none transition-all duration-300" :class="sidebarCollapsed ? 'h-10 w-10' : 'h-32 w-auto max-h-32'">
                            <div class="space-y-1.5 overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'h-0 opacity-0' : 'h-auto opacity-100'">
                                <p class="text-[12px] font-semibold uppercase tracking-[0.24em] text-emerald-50 whitespace-nowrap">Zinus Dream</p>
                                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-[#cfe9dd] whitespace-nowrap">IT Support Center</p>
                            </div>
                        </div>
                    @if ($user)
                        <div class="text-center space-y-2 mt-2 overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'h-0 opacity-0 mt-0' : 'h-auto opacity-100'">
                            <p class="text-sm font-semibold text-white whitespace-nowrap">{{ $user->name }}</p>
                            @php $isAdmin = $user->isAdmin(); @endphp
                            <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-[0.62rem] font-semibold uppercase tracking-[0.28em] whitespace-nowrap {{ $isAdmin ? 'role-badge-gold' : 'role-badge-soft text-emerald-700' }}">
                                <svg class="h-3 w-3 text-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 2 3 6v6c0 5.25 3.438 10 9 11 5.562-1 9-5.75 9-11V6l-9-4Z" />
                                    <path d="m9 12 2 2 4-4" />
                                </svg>
                                <span x-show="!sidebarCollapsed" x-transition>{{ $isAdmin ? 'IT ADMIN' : 'USER' }}</span>
                            </span>
                        </div>
                    @endif
                    <div class="h-px w-full bg-white/20" x-show="!sidebarCollapsed" x-transition></div>
                </div>

                <nav id="tour-sidebar" class="flex-1 space-y-1.5 sidebar-nav text-[14px] transition-all duration-300" :class="sidebarCollapsed ? 'px-2' : 'px-4'">
                    @foreach ($navItems as $item)
                        @continue(! $item['visible'])
                        @php
                            $isActive = request()->routeIs($item['route']);
                            $badgeCount = (int) ($item['badgeCount'] ?? 0);
                            $badgeType = $item['badgeType'] ?? null;
                        @endphp
                        <a
                            href="{{ route($item['route']) }}"
                            @class([
                                'group flex items-center gap-3 rounded-lg py-3 text-[14px] font-medium transition-all duration-150 hover:text-white border-l-[3px] border-transparent',
                                'text-emerald-100/80' => ! $isActive,
                                'is-active text-white border-[#53B77A]' => $isActive,
                            ])
                            :class="sidebarCollapsed ? 'justify-center px-3' : 'px-5'"
                            :title="sidebarCollapsed ? '{{ $item['label'] }}' : ''"
                        >
                            <svg
                                class="h-[18px] w-[18px] flex-shrink-0 transition-transform duration-150 group-hover:scale-105"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                viewBox="0 0 24 24"
                            >
                                {!! $item['icon'] !!}
                            </svg>
                            <span class="flex-1 whitespace-nowrap overflow-hidden transition-all duration-300" x-show="!sidebarCollapsed" x-transition.opacity>{{ $item['label'] }}</span>
                            @if ($badgeType)
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-rose-100 py-0.5 text-[0.65rem] font-semibold uppercase text-rose-600 transition-all duration-300"
                                    :class="sidebarCollapsed ? 'absolute -top-1 -right-1 w-2 h-2 p-0 min-w-0 rounded-full' : 'ml-auto px-2 tracking-[0.25em]'"
                                    data-notification-badge="{{ $badgeType }}"
                                    @if ($badgeCount === 0) hidden style="display: none;" @endif
                                >
                                    <template x-if="!sidebarCollapsed">
                                        <span class="flex items-center gap-1"><span>New</span><span class="tracking-normal" data-notification-count="{{ $badgeType }}">{{ $badgeCount > 9 ? '9+' : $badgeCount }}</span></span>
                                    </template>
                                </span>
                            @endif
                        </a>
                    @endforeach
                </nav>

                <!-- Toggle Collapse Button -->
                <div class="px-3 py-2 border-t border-white/10">
                    <button
                        @click="sidebarCollapsed = !sidebarCollapsed"
                        class="w-full flex items-center justify-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium text-emerald-200/70 hover:text-white hover:bg-white/10 transition-all duration-200"
                        :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    >
                        <svg class="h-5 w-5 flex-shrink-0 transition-transform duration-300" :class="sidebarCollapsed ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <line x1="9" y1="3" x2="9" y2="21"/>
                            <polyline points="16 15 13 12 16 9"/>
                        </svg>
                        <span class="whitespace-nowrap overflow-hidden transition-all duration-300" x-show="!sidebarCollapsed" x-transition.opacity>Collapse</span>
                    </button>
                </div>

                <div class="px-3 pb-4 transition-all duration-300" :class="sidebarCollapsed ? 'px-2' : 'px-6'">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-700 to-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-900/20 transition hover:from-emerald-600 hover:to-emerald-500" :title="sidebarCollapsed ? '{{ __('messages.logout') }}' : ''">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
                            </svg>
                            <span class="whitespace-nowrap overflow-hidden transition-all duration-300" x-show="!sidebarCollapsed" x-transition.opacity>{{ __('messages.logout') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <main class="min-h-screen flex flex-col min-w-0 relative z-10 w-full lg:flex-1">
                <!-- Mobile top bar -->
                <div class="sticky top-0 z-50 flex items-center justify-between bg-white/95 px-4 py-3 shadow-sm backdrop-blur lg:hidden">
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="sidebarOpen = true"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                            aria-label="Buka menu navigasi"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 7h16M4 12h16M4 17h16" />
                            </svg>
                        </button>
                        <div class="leading-tight">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-700">Zinus Dream</p>
                            <p class="text-sm font-semibold text-ink-900">IT Ticketing</p>
                        </div>
                    </div>
                    @if ($user)
                        <div class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-ink-700 shadow-sm">
                            <span class="h-9 w-9 rounded-full bg-[#004F3B] text-white flex items-center justify-center font-semibold shadow-inner shadow-emerald-100">
                                {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                            </span>
                            <span class="max-w-[120px] truncate text-left leading-tight">
                                <span class="block">{{ $user->name }}</span>
                                <span class="block text-[11px] font-normal text-emerald-700/80">{{ $user->email }}</span>
                            </span>
                        </div>
                    @endif
                </div>

                <!-- Mobile nav drawer -->
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity
                    class="fixed inset-0 bg-black/40 lg:hidden z-40"
                    @click="sidebarOpen = false"
                ></div>
                <div
                    x-show="sidebarOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="translate-x-[-100%]"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="translate-x-[-100%]"
                    class="fixed inset-y-0 left-0 z-50 w-full lg:hidden"
                >
                    <div
                        class="h-full w-full bg-[#0F2F22] text-emerald-50 shadow-2xl ring-1 ring-emerald-900/30 [box-shadow:inset_0_1px_0_rgba(255,255,255,0.06)]"
                        style="background:linear-gradient(180deg,#0f2f22,#0d241b);"
                    >
                        <div class="flex items-center justify-between px-5 py-4 border-b border-white/10">
                            <div>
                                <p class="text-[0.65rem] uppercase tracking-[0.5em] text-emerald-200/70">Zinus Dream</p>
                                <p class="text-lg font-semibold text-white">IT Ticketing</p>
                            </div>
                            <button
                                type="button"
                                @click="sidebarOpen = false"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 text-emerald-50 transition hover:bg-white/10"
                                aria-label="Tutup menu navigasi"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 6l12 12M6 18L18 6" />
                                </svg>
                            </button>
                        </div>
                        <div class="space-y-1 px-3 py-4 sidebar-nav text-[14px]">
                            @foreach ($navItems as $item)
                                @continue(! $item['visible'])
                                @php
                                    $isActive = request()->routeIs($item['route']);
                                    $badgeCount = (int) ($item['badgeCount'] ?? 0);
                                    $badgeType = $item['badgeType'] ?? null;
                                @endphp
                                <a
                                    href="{{ route($item['route']) }}"
                                    @click="sidebarOpen = false"
                                    @class([
                                        'group flex items-center gap-3 rounded-lg px-5 py-3 text-[14px] font-medium transition-all duration-200 hover:text-white border-l-[3px] border-transparent',
                                        'text-emerald-100/80' => ! $isActive,
                                        'is-active text-white border-[#53B77A]' => $isActive,
                                    ])
                                >
                                    <svg
                                        class="h-[18px] w-[18px] flex-shrink-0 transition-transform duration-150 group-hover:scale-105"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        viewBox="0 0 24 24"
                                    >
                                        {!! $item['icon'] !!}
                                    </svg>
                                    <span class="flex-1">{{ $item['label'] }}</span>
                                    @if ($badgeType)
                                        <span
                                            class="ml-auto inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-0.5 text-[0.65rem] font-semibold uppercase tracking-[0.25em] text-rose-600"
                                            data-notification-badge="{{ $badgeType }}"
                                            @if ($badgeCount === 0) hidden @endif
                                        >
                                            <span>New</span>
                                            <span class="tracking-normal" data-notification-count="{{ $badgeType }}">{{ $badgeCount > 9 ? '9+' : $badgeCount }}</span>
                                        </span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                @if (request()->routeIs('dashboard') || request()->routeIs('tickets.mine') || request()->routeIs('tickets.index') || request()->routeIs('tickets.show') || request()->routeIs('users.*') || request()->routeIs('loans.*') || request()->routeIs('assets.*') || request()->routeIs('admin.conversations.*') || request()->routeIs('remote-system.*'))
                    @php
                        $topbarTitle = match (true) {
                            request()->routeIs('tickets.show') => __('messages.title_ticket_detail'),
                            request()->routeIs('tickets.mine') => __('messages.title_my_tickets'),
                            request()->routeIs('tickets.index') => __('messages.title_ticket_list'),
                            request()->routeIs('users.*') => __('messages.title_manage_users'),
                            request()->routeIs('loans.*') => __('messages.title_loan_log'),
                            request()->routeIs('assets.*') => __('messages.title_manage_assets'),
                            request()->routeIs('admin.conversations.index') => __('messages.title_live_chat'),
                            request()->routeIs('admin.conversations.show') => 'Detail Percakapan',
                            request()->routeIs('remote-system.*') => __('messages.title_remote_system'),
                            default => __('messages.dashboard'),
                        };
                        $topbarDescription = match (true) {
                            request()->routeIs('tickets.show') => __('messages.desc_ticket_detail'),
                            request()->routeIs('tickets.mine') => __('messages.desc_my_tickets'),
                            request()->routeIs('tickets.index') => __('messages.desc_ticket_list'),
                            request()->routeIs('users.*') => __('messages.desc_manage_users'),
                            request()->routeIs('loans.*') => __('messages.desc_loan_log'),
                            request()->routeIs('assets.*') => __('messages.desc_manage_assets'),
                            request()->routeIs('admin.conversations.*') => __('messages.desc_live_chat'),
                            request()->routeIs('remote-system.*') => __('messages.desc_remote_system'),
                            default => __('messages.desc_ticket_management'),
                        };
                    @endphp
                    <x-topbar
                        :user="$user"
                        :title="$topbarTitle"
                        :description="$topbarDescription"
                        :hide-user-on-mobile="request()->routeIs('dashboard')"
                    />
                @else
                    <header class="sticky-header sticky top-[64px] lg:top-0 z-40 relative bg-gradient-to-b from-white via-[#F6F9F8] to-[#EDF3F2] border-b border-[#d0e4de]">
                        <div class="w-full max-w-none px-4 sm:px-6 lg:px-8 py-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex flex-col gap-2">
                                <div class="text-[0.65rem] font-semibold uppercase tracking-[0.3em] text-[#23455D]/70">
                                    IT Support Center &middot; Zinus Dream
                                </div>
                                <div class="space-y-1">
                                    <h2 class="text-2xl font-semibold text-[#0C1F2C]">
                                        @isset($header)
                                            {{ $header }}
                                        @else
                                            Manajemen tiket yang lebih terstruktur
                                        @endisset
                                    </h2>
                                    <p class="text-sm text-[#1f5948]" style="background-image: linear-gradient(90deg, #23455D 0%, #12824C 45%, #53B77A 100%); -webkit-background-clip: text; color: transparent;">Kelola tiket dan dukungan IT Zinus Dream Indonesia dari satu panel terpadu.</p>
                                </div>
                            </div>

                            @if ($user)
                                <div class="flex items-center gap-2.5">
                                    <!-- Language Switcher -->
                                    <div x-data="{ langOpen: false }" class="relative">
                                        <button
                                            type="button"
                                            @click="langOpen = !langOpen"
                                            class="flex items-center justify-center gap-1.5 rounded-full border border-[#c5e5d0] bg-white px-3 py-1.5 shadow-sm transition hover:border-[#53B77A] focus:outline-none"
                                            title="Change Language"
                                        >
                                            <svg class="h-[18px] w-[18px] text-[#12824C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10" />
                                                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                                                <path d="M2 12h20" />
                                            </svg>
                                            <span class="text-[11px] font-bold text-[#0D1F2B]">{{ strtoupper(app()->getLocale()) }}</span>
                                            <svg class="h-3.5 w-3.5 text-slate-500 transition" :class="{ 'rotate-180': langOpen }" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.25a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08Z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                        <div
                                            x-show="langOpen"
                                            @click.away="langOpen = false"
                                            class="absolute right-0 mt-2 w-40 rounded-xl border border-slate-200 bg-white shadow-lg py-1.5 z-50 transform origin-top-right transition-all duration-200"
                                            x-transition:enter="ease-out duration-200"
                                            x-transition:enter-start="opacity-0 scale-95"
                                            x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="ease-in duration-150"
                                            x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-95"
                                            style="display: none;"
                                        >
                                            <p class="px-4 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">Language</p>
                                            <a href="{{ route('lang.switch', 'en') }}" class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-[#EDF3F2] {{ app()->getLocale() == 'en' ? 'text-[#12824C] font-semibold bg-emerald-50/50' : '' }}">
                                                EN - English
                                                @if(app()->getLocale() == 'en')<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>@endif
                                            </a>
                                            <a href="{{ route('lang.switch', 'id') }}" class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-[#EDF3F2] {{ app()->getLocale() == 'id' ? 'text-[#12824C] font-semibold bg-emerald-50/50' : '' }}">
                                                ID - Indonesia
                                                @if(app()->getLocale() == 'id')<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>@endif
                                            </a>
                                            <a href="{{ route('lang.switch', 'ko') }}" class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-[#EDF3F2] {{ app()->getLocale() == 'ko' ? 'text-[#12824C] font-semibold bg-emerald-50/50' : '' }}">
                                                KO - Korean
                                                @if(app()->getLocale() == 'ko')<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>@endif
                                            </a>
                                        </div>
                                    </div>

                                    <div x-data="{ open: false }" class="relative">
                                        <button
                                            type="button"
                                        @click="open = !open"
                                        class="flex items-center gap-2 rounded-full border border-[#c5e5d0] bg-white px-3 py-1.5 text-left shadow-sm transition hover:border-[#53B77A] focus:outline-none"
                                    >
                                        <span class="h-8 w-8 rounded-full bg-[#12824C] text-white flex items-center justify-center text-sm font-semibold ring-2 ring-[#C5E5D0]">
                                            {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                        </span>
                                        <span class="leading-tight">
                                            <span class="block text-sm font-semibold text-slate-900">{{ $user->name }}</span>
                                            <span class="block text-xs text-slate-500 truncate max-w-[180px]">{{ $user->email }}</span>
                                        </span>
                                        <svg class="h-4 w-4 text-slate-500 transition" :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.25a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08Z" clip-rule="evenodd" />
                                        </svg>
                                    </button>

                                    <div
                                        x-show="open"
                                        @click.away="open = false"
                                        @keydown.escape.window="open = false"
                                        class="absolute right-0 mt-2 w-52 rounded-xl border border-slate-200 bg-white py-2 shadow-lg shadow-slate-200/50 z-50"
                                        x-transition.origin.top.right
                                    >
                                        <div class="px-4 pb-2">
                                            <p class="text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                                            <p class="text-xs text-slate-500 truncate">{{ $user->email }}</p>
                                        </div>
                                        <div class="mt-1 border-t border-slate-100"></div>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="flex w-full items-center gap-2 px-4 py-2 text-sm font-semibold text-emerald-600 transition hover:bg-emerald-50"
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M13 16l4-4-4-4" />
                                                    <path d="M3 12h14" />
                                                    <path d="M7 5V4a2 2 0 012-2h6a2 2 0 012 2v12a2 2 0 01-2 2h-6a2 2 0 01-2-2v-1" />
                                                </svg>
                                                {{ __('messages.logout') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </header>
                @endif

                <section class="flex-1 w-full">
                    <div class="h-px w-full bg-gradient-to-r from-transparent via-emerald-200/70 to-transparent"></div>
                    <div class="relative w-full {{ request()->routeIs('assets.index') || request()->routeIs('tickets.index') ? 'max-w-none' : 'max-w-6xl mx-auto' }} px-4 sm:px-6 {{ request()->routeIs('assets.index') ? 'lg:px-10' : 'lg:px-8' }} py-4">
                        <div class="pointer-events-none absolute -top-16 right-8 h-36 w-36 rounded-full bg-emerald-300/40 blur-3xl"></div>
                        <div class="pointer-events-none absolute bottom-0 left-0 h-44 w-44 rounded-full bg-sky-200/40 blur-3xl"></div>
                        <div class="relative">
                            {{ $slot }}
                        </div>
                    </div>
        </section>
        <footer class="mt-auto bg-white/90 border-t border-slate-200 py-4">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between text-xs text-slate-500">
                <span>&copy; {{ now()->year }} Zinus Dream IT Support. Need help? hubungi support@zinusdream.com</span>
                <div class="flex items-center gap-3 text-slate-400">
                    <a href="mailto:support@zinusdream.com" class="hover:text-emerald-600" aria-label="Email support">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z" /><path d="m4 4 8 8 8-8" /></svg>
                    </a>
                    <a href="#" class="hover:text-emerald-600" aria-label="WhatsApp">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.52 3.48A11.92 11.92 0 0 0 12.05 0C5.49 0 .04 5.31.04 11.86a11.8 11.8 0 0 0 1.6 5.95L0 24l6.4-1.68a12.06 12.06 0 0 0 5.6 1.43h.01c6.56 0 11.94-5.34 11.94-11.9a11.7 11.7 0 0 0-3.43-8.37Z" /></svg>
                    </a>
                </div>
            </div>
        </footer>
        </main>
        
        <livewire:chat-widget />

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-file-preview]').forEach(wrapper => {
                    const input = wrapper.querySelector('[data-file-preview-input]');
                    const list = wrapper.querySelector('[data-file-preview-list]');
                    if (!input || !list) return;
                    input.addEventListener('change', () => {
                        list.innerHTML = '';
                        if (!input.files.length) {
                            list.hidden = true;
                            return;
                        }
                        Array.from(input.files).forEach(file => {
                            const pill = document.createElement('span');
                            pill.className = 'inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600';
                            pill.innerHTML = `<span class="truncate max-w-[140px]">${file.name}</span>`;
                            const removeBtn = document.createElement('button');
                            removeBtn.type = 'button';
                            removeBtn.className = 'text-slate-400 hover:text-rose-500 focus:outline-none';
                            removeBtn.innerHTML = '&times;';
                            removeBtn.addEventListener('click', () => {
                                input.value = '';
                                list.innerHTML = '';
                                list.hidden = true;
                            });
                            pill.appendChild(removeBtn);
                            list.appendChild(pill);
                        });
                        list.hidden = false;
                    });
                });
                document.querySelectorAll('[data-dropzone]').forEach(zone => {
                    const input = zone.querySelector('[data-file-preview-input]');
                    const addHover = () => zone.classList.add('ring-2', 'ring-emerald-200', 'bg-emerald-50/50');
                    const removeHover = () => zone.classList.remove('ring-2', 'ring-emerald-200', 'bg-emerald-50/50');
                    zone.addEventListener('dragover', event => {
                        event.preventDefault();
                        addHover();
                    });
                    zone.addEventListener('dragleave', removeHover);
                    zone.addEventListener('drop', event => {
                        event.preventDefault();
                        removeHover();
                        if (input) {
                            input.files = event.dataTransfer.files;
                            input.dispatchEvent(new Event('change'));
                        }
                    });
                });

                const toasts = document.querySelectorAll('#toast-stack .toast');
                toasts.forEach(toast => {
                    setTimeout(() => {
                        toast.classList.add('opacity-0', 'translate-y-2');
                        setTimeout(() => toast.remove(), 400);
                    }, 3500);
                });

                document.querySelectorAll('[data-notification-badge]').forEach(badge => {
                    const anchor = badge.closest('a');
                    const countTarget = document.querySelector(`[data-notification-count="${badge.dataset.notificationBadge}"]`);
                    const hideBadge = () => {
                        badge.hidden = true;
                        if (countTarget) {
                            countTarget.textContent = '0';
                        }
                    };
                    if (anchor) {
                        anchor.addEventListener('click', hideBadge);
                    } else {
                        badge.addEventListener('click', hideBadge);
                    }
                });

                document.querySelectorAll('[data-ticket-form]').forEach(form => {
                    const fields = {
                        title: form.querySelector('[data-validate-field="title"]'),
                        description: form.querySelector('[data-validate-field="description"]'),
                        category_id: form.querySelector('[data-validate-field="category_id"]'),
                        department_id: form.querySelector('[data-validate-field="department_id"]'),
                    };
                    const idempotencyInput = form.querySelector('[data-idempotency-key]');
                    const submitBtn = form.querySelector('[data-submit-btn]');
                    const submitLabel = form.querySelector('[data-submit-label]');
                    const submitSpinner = form.querySelector('[data-submit-spinner]');

                    const generateKey = () => {
                        if (window.crypto?.randomUUID) {
                            return window.crypto.randomUUID();
                        }
                        return 'idemp-' + Math.random().toString(16).slice(2) + Date.now().toString(16);
                    };

                    if (idempotencyInput && !idempotencyInput.value) {
                        idempotencyInput.value = generateKey();
                    }

                    const errorTargets = {
                        title: form.querySelector('[data-field-error="title"]'),
                        description: form.querySelector('[data-field-error="description"]'),
                        category_id: form.querySelector('[data-field-error="category_id"]'),
                        department_id: form.querySelector('[data-field-error="department_id"]'),
                    };

                    const showError = (field, message) => {
                        const target = errorTargets[field];
                        const input = fields[field];
                        if (target) {
                            target.textContent = message;
                            target.classList.remove('hidden');
                        }
                        input?.classList.add('input-error');
                    };

                    const clearError = field => {
                        const target = errorTargets[field];
                        const input = fields[field];
                        if (target && target.dataset.fromServer !== 'true') {
                            target.textContent = '';
                            target.classList.add('hidden');
                        }
                        input?.classList.remove('input-error');
                    };

                    Object.entries(errorTargets).forEach(([field, target]) => {
                        if (target && target.textContent.trim() !== '') {
                            target.dataset.fromServer = 'true';
                        }
                    });

                    const validators = {
                        title: value => value.trim().length >= 8 ? '' : 'Judul minimal 8 karakter.',
                        description: value => value.trim().length >= 20 ? '' : 'Deskripsi minimal 20 karakter.',
                        category_id: value => value ? '' : 'Pilih kategori tiket.',
                        department_id: value => value ? '' : 'Pilih departemen terkait.',
                    };

                    form.addEventListener('submit', event => {
                        let hasError = false;
                        let firstInvalid = null;

                        Object.entries(validators).forEach(([field, validator]) => {
                            const input = fields[field];
                            if (!input) {
                                return;
                            }
                            const message = validator(input.value || '');
                            if (message) {
                                if (!firstInvalid) {
                                    firstInvalid = input;
                                }
                                showError(field, message);
                                hasError = true;
                            } else {
                                clearError(field);
                            }
                        });

                        if (hasError) {
                            event.preventDefault();
                            firstInvalid?.focus();
                            firstInvalid?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            return;
                        }

                        if (form.dataset.submitted === 'true') {
                            event.preventDefault();
                            return;
                        }

                        form.dataset.submitted = 'true';
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
                        }
                        if (submitLabel) {
                            submitLabel.textContent = 'Mengirim...';
                        }
                        if (submitSpinner) {
                            submitSpinner.classList.remove('hidden');
                        }
                    });

                    const attachClearHandler = (fieldName, eventName = 'input') => {
                        const input = fields[fieldName];
                        if (!input) return;
                        input.addEventListener(eventName, () => {
                            errorTargets[fieldName]?.removeAttribute('data-from-server');
                            clearError(fieldName);
                        });
                    };

                    attachClearHandler('title');
                    attachClearHandler('description');
                    attachClearHandler('category_id', 'change');
                    attachClearHandler('department_id', 'change');
                });

                const animateCounts = () => {
                    document.querySelectorAll('[data-countup]').forEach(el => {
                        const target = Number(el.dataset.countup || 0);
                        const duration = 650;
                        const startTime = performance.now();
                        const start = 0;
                        const step = now => {
                            const progress = Math.min((now - startTime) / duration, 1);
                            const value = Math.floor(start + (target - start) * progress);
                            el.textContent = value.toLocaleString('id-ID');
                            if (progress < 1) requestAnimationFrame(step);
                        };
                        requestAnimationFrame(step);
                    });
                };

                const initTicketLists = () => {
                    document.querySelectorAll('[data-ticket-list]').forEach(list => {
                        const skeleton = list.querySelector('[data-ticket-skeleton]');
                        const body = list.querySelector('[data-ticket-body]');
                        const filter = list.querySelector('[data-ticket-filter]');
                        const sort = list.querySelector('[data-ticket-sort]');
                        const items = Array.from(list.querySelectorAll('[data-ticket-item]'));

                        skeleton?.classList.add('hidden');
                        body?.classList.remove('hidden');

                        const apply = () => {
                            const filterVal = filter?.value || 'all';
                            const sortVal = sort?.value || 'newest';
                            const filtered = items.filter(item => {
                                const status = item.dataset.ticketStatus || '';
                                return filterVal === 'all' || status === filterVal;
                            });

                            filtered.sort((a, b) => {
                                if (sortVal === 'oldest') {
                                    return (Number(a.dataset.created) || 0) - (Number(b.dataset.created) || 0);
                                }
                                if (sortVal === 'title') {
                                    return (a.dataset.ticketTitle || '').localeCompare(b.dataset.ticketTitle || '');
                                }
                                return (Number(b.dataset.created) || 0) - (Number(a.dataset.created) || 0);
                            });

                            items.forEach(item => item.style.display = 'none');
                            filtered.forEach(item => {
                                item.style.display = '';
                                body?.appendChild(item);
                            });
                        };

                        filter?.addEventListener('change', apply);
                        sort?.addEventListener('change', apply);
                        apply();

                        list.querySelectorAll('[data-copy-id]').forEach(btn => {
                            btn.addEventListener('click', () => {
                                const id = btn.dataset.copyId;
                                if (!id || !navigator.clipboard) return;
                                navigator.clipboard.writeText(id).then(() => {
                                    btn.textContent = 'Disalin';
                                    setTimeout(() => btn.textContent = 'Salin ID', 1200);
                                }).catch(() => {});
                            });
                        });
                    });
                };

                const initStatsSkeleton = () => {
                    const skeleton = document.querySelector('[data-stats-skeleton]');
                    const content = document.querySelector('[data-stats-content]');
                    if (content) content.classList.remove('hidden');
                    if (skeleton) skeleton.classList.add('hidden');
                    animateCounts();
                };

                initStatsSkeleton();
                initTicketLists();

                document.querySelectorAll('[data-status-tooltip]').forEach(chip => {
                    chip.setAttribute('title', chip.textContent.trim());
                });

                // Livewire Global Error Interceptor (Session Expired / 419)
                document.addEventListener('livewire:initialized', () => {
                    Livewire.hook('request.error', ({ status, preventDefault }) => {
                        if (status === 419) {
                            preventDefault();
                            window.dispatchEvent(new CustomEvent('open-session-expired'));
                        }
                    });
                });

                // Livewire Navigation Support
                document.addEventListener('livewire:navigating', () => {
                    const preloader = document.querySelector('.preloader');
                    if (preloader) {
                        preloader.classList.remove('hidden');
                        document.body.classList.add('page-preload');
                        document.body.classList.remove('page-loaded');
                    }
                });

                document.addEventListener('livewire:navigated', () => {
                    const preloader = document.querySelector('.preloader');
                    if (preloader) {
                        setTimeout(() => {
                            preloader.classList.add('hidden');
                            document.body.classList.remove('page-preload');
                            document.body.classList.add('page-loaded');
                        }, 300);
                    }
                });
            });
        </script>
        @stack('scripts')
        @livewireScripts

        <!-- Global Confirm Modal -->
        <x-confirm-modal />

        <!-- Global Session Expired Modal -->
        <div
            x-data="{ isOpen: false }"
            @open-session-expired.window="isOpen = true; document.documentElement.style.overflow = 'hidden'; document.body.style.overflow = 'hidden';"
            x-show="isOpen"
            style="display: none;"
            class="relative z-[999999]"
            aria-labelledby="session-expired-modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div
                x-show="isOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 backdrop-blur-none"
                x-transition:enter-end="opacity-100 backdrop-blur-md"
                class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-all"
            ></div>

            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div
                        x-show="isOpen"
                        x-transition:enter="ease-out duration-300 transform"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95 sm:rotate-1"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100 sm:rotate-0"
                        class="relative transform overflow-hidden rounded-2xl bg-white/95 text-left shadow-[0_32px_80px_-12px_rgba(0,0,0,0.3)] backdrop-blur-xl transition-all sm:my-8 sm:w-full sm:max-w-md ring-1 ring-slate-200/50"
                    >
                        <div class="absolute inset-x-0 top-0 h-2 bg-gradient-to-r from-red-400 to-rose-500"></div>
                        <div class="px-5 pb-5 pt-8 sm:p-7 sm:pb-5">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-12 sm:w-12 ring-8 ring-red-50">
                                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="mt-4 text-center sm:ml-5 sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-xl font-bold leading-6 text-slate-800 tracking-tight" id="session-expired-modal-title">Sesi Berakhir</h3>
                                    <div class="mt-3">
                                        <p class="text-[15px] leading-relaxed text-slate-500">Halaman ini sudah kadaluarsa karena terlalu lama tidak ada aktivitas. Silakan muat ulang halaman untuk melanjutkan.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50/50 px-5 py-4 sm:flex sm:flex-row-reverse sm:px-7 border-t border-slate-100">
                            <button
                                type="button"
                                @click="window.location.reload()"
                                class="btn-animate inline-flex w-full justify-center rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm ring-1 ring-inset ring-red-600 hover:bg-red-500 sm:ml-3 sm:w-auto"
                            >
                                Muat Ulang
                            </button>
                            <button
                                type="button"
                                @click="isOpen = false; document.documentElement.style.overflow = ''; document.body.style.overflow = '';"
                                class="btn-animate mt-3 inline-flex w-full justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto"
                            >
                                Batal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </body>
</html>
