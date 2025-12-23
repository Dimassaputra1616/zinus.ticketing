<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'IT Ticketing') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Geist:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

        <!-- Scripts -->
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
            body.page-loaded { opacity: 1; transform: translateY(0); }

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
        </style>
    </head>
    @php $authUser = Auth::user(); @endphp
    <body
        x-init="setTimeout(() => { document.body.classList.remove('page-preload'); document.body.classList.add('page-loaded'); }, 10)"
        x-data="{ mobileNav: false }"
        class="page-preload font-sans bg-white text-slate-800 antialiased min-h-screen overflow-x-hidden lg:flex"
        @if($authUser?->isAdmin()) data-notifications-endpoint="{{ route('admin.notifications.summary') }}" @endif
    >
        <div class="loading-bar" aria-hidden="true"></div>
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
            }

            $navItems = [
                [
                    'label' => 'Dashboard',
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
                    'label' => 'Tiket Saya',
                    'route' => 'tickets.mine',
                    'icon' => '
                        <path d="M3 9V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4Z" />
                        <path d="M13 5v14" />
                        <path d="M7 9h4" />
                        <path d="M7 15h4" />
                    ',
                    'visible' => true,
                    'badgeCount' => 0,
                    'badgeType' => null,
                ],
                [
                    'label' => 'Daftar Tiket',
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
                    'label' => 'Log Peminjaman',
                    'route' => 'loans.index',
                    'icon' => '
                        <path d="M7 7h10M7 12h4m1 8 3-3h4a2 2 0 0 0 2-2V5c0-1.1-.9-2-2-2H6a2 2 0 0 0-2 2v15l3-3h5" />
                    ',
                    'visible' => true,
                    'badgeCount' => 0,
                    'badgeType' => null,
                ],
                [
                    'label' => 'Kelola Asset',
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
                    'label' => 'Kelola User',
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

        <aside class="hidden lg:flex lg:sticky lg:top-0 lg:h-screen w-[260px] z-50 lg:shrink-0 flex-col justify-between bg-[#0E1F1B] text-emerald-50 shadow-lg shadow-black/20 ring-1 ring-black/10 overflow-y-auto">
            <div class="flex flex-col w-full h-full">
                <div class="px-6 pt-10 pb-6 space-y-5">
                        <div class="flex flex-col items-center text-center space-y-3">
                            <img src="{{ asset('images/logo-email.png') }}" alt="Zinus Dream" class="h-32 w-auto max-h-32 select-none opacity-100 p-1">
                            <div class="space-y-1.5">
                                <p class="text-[12px] font-semibold uppercase tracking-[0.24em] text-emerald-50">Zinus Dream</p>
                                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-[#cfe9dd]">IT Support Center</p>
                            </div>
                        </div>
                    @if ($user)
                        <div class="text-center space-y-2 mt-2">
                            <p class="text-sm font-semibold text-white">{{ $user->name }}</p>
                            @php $isAdmin = $user->isAdmin(); @endphp
                            <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-[0.62rem] font-semibold uppercase tracking-[0.28em] {{ $isAdmin ? 'role-badge-gold' : 'role-badge-soft text-emerald-700' }}">
                                <svg class="h-3 w-3 text-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 2 3 6v6c0 5.25 3.438 10 9 11 5.562-1 9-5.75 9-11V6l-9-4Z" />
                                    <path d="m9 12 2 2 4-4" />
                                </svg>
                                {{ $isAdmin ? 'IT ADMIN' : 'USER' }}
                            </span>
                        </div>
                    @endif
                    <div class="h-px w-full bg-white/20"></div>
                </div>

                <nav class="flex-1 px-4 space-y-1.5 sidebar-nav text-[14px]">
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
                                'group flex items-center gap-3 rounded-lg px-5 py-3 text-[14px] font-medium transition-all duration-150 hover:text-white border-l-[3px] border-transparent',
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
                </nav>

                <div class="px-6 py-6 border-t border-white/10">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-700 to-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-900/20 transition hover:from-emerald-600 hover:to-emerald-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
                            </svg>
                            Logout
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
                            @click="mobileNav = true"
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
                    x-show="mobileNav"
                    x-transition.opacity
                    class="fixed inset-0 z-50 bg-black/30 lg:hidden"
                    @click.self="mobileNav = false"
                >
                    <div
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="translate-x-[-100%]"
                        x-transition:enter-end="translate-x-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="translate-x-0"
                        x-transition:leave-end="translate-x-[-100%]"
                        class="h-full w-[82%] max-w-xs bg-[#0F2F22] text-emerald-50 shadow-2xl ring-1 ring-emerald-900/30 [box-shadow:inset_0_1px_0_rgba(255,255,255,0.06)]"
                        style="background:linear-gradient(180deg,#0f2f22,#0d241b);"
                    >
                        <div class="flex items-center justify-between px-5 py-4 border-b border-white/10">
                            <div>
                                <p class="text-[0.65rem] uppercase tracking-[0.5em] text-emerald-200/70">Zinus Dream</p>
                                <p class="text-lg font-semibold text-white">IT Ticketing</p>
                            </div>
                            <button
                                type="button"
                                @click="mobileNav = false"
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
                                    @click="mobileNav = false"
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

                @if (request()->routeIs('dashboard') || request()->routeIs('tickets.mine') || request()->routeIs('tickets.index') || request()->routeIs('tickets.show') || request()->routeIs('users.*') || request()->routeIs('loans.*') || request()->routeIs('assets.*'))
                    @php
                        $topbarTitle = match (true) {
                            request()->routeIs('tickets.show') => 'Detail Tiket',
                            request()->routeIs('tickets.mine') => 'Tiket Saya',
                            request()->routeIs('tickets.index') => 'Daftar Tiket',
                            request()->routeIs('users.*') => 'Kelola pengguna dan hak akses tim',
                            request()->routeIs('loans.*') => 'Log Peminjaman',
                            request()->routeIs('assets.*') => 'Kelola Asset & Inventori',
                            default => 'Dashboard Ticketing',
                        };
                        $topbarDescription = match (true) {
                            request()->routeIs('tickets.show') => 'Kelola tiket dan dukungan IT Zinus Dream Indonesia dari satu panel terpadu.',
                            request()->routeIs('tickets.mine') => 'Pantau progres tiket yang kamu buat dan terus ikuti update-nya.',
                            request()->routeIs('tickets.index') => 'Pantau dan kelola semua tiket yang masuk ke tim IT.',
                            request()->routeIs('users.*') => 'Atur akun, peran, dan status user agar dukungan IT tetap aman dan terstruktur.',
                            request()->routeIs('loans.*') => 'Pantau dan kelola peminjaman perangkat, proses persetujuan, dan pengembalian dengan lebih rapi.',
                            request()->routeIs('assets.*') => 'Kelola inventori perangkat, status ketersediaan, dan lokasi penggunaan dari satu halaman.',
                            default => 'Kelola tiket dan dukungan IT Zinus Dream Indonesia dari satu panel terpadu.',
                        };
                    @endphp
                    <x-topbar
                        :user="$user"
                        :title="$topbarTitle"
                        :description="$topbarDescription"
                    />
                @else
                    <header class="sticky-header sticky top-[64px] lg:top-0 z-40 relative bg-gradient-to-b from-white via-[#F6F9F8] to-[#EDF3F2] border-b border-[#d0e4de]">
                        <div class="w-full max-w-none px-4 lg:px-6 py-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
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
                                                Logout
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
                    <div class="relative w-full {{ request()->routeIs('tickets.index') ? 'max-w-none mx-0 px-10 lg:px-16' : (request()->routeIs('dashboard') || request()->routeIs('tickets.mine') ? 'max-w-6xl mx-auto px-6 lg:px-8' : 'max-w-none px-6 lg:px-8') }} py-4">
                        <div class="pointer-events-none absolute -top-16 right-8 h-36 w-36 rounded-full bg-emerald-300/40 blur-3xl"></div>
                        <div class="pointer-events-none absolute bottom-0 left-0 h-44 w-44 rounded-full bg-sky-200/40 blur-3xl"></div>
                        <div class="relative">
                            {{ $slot }}
                        </div>
                    </div>
        </section>
        <footer class="mt-auto bg-white/90 border-t border-slate-200 py-4">
            <div class="max-w-6xl mx-auto px-6 lg:px-8 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between text-xs text-slate-500">
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

                document.querySelectorAll('[data-ticket-form]').forEach(form => {
                    const fields = {
                        title: form.querySelector('[data-validate-field="title"]'),
                        description: form.querySelector('[data-validate-field="description"]'),
                        category_id: form.querySelector('[data-validate-field="category_id"]'),
                        department_id: form.querySelector('[data-validate-field="department_id"]'),
                    };

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
            });
        </script>
    </body>
</html>
