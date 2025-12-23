@props([
    'user' => null,
    'badgePrimary' => 'IT Support Center',
    'badgeSecondary' => 'Zinus Dream',
    'tagline' => 'Dashboard Ticketing',
    'title' => 'Manajemen tiket yang lebih terstruktur',
    'description' => 'Kelola tiket dan dukungan IT Zinus Dream Indonesia dari satu panel terpadu.',
])

@php
    $displayName = $user?->name;
    $displayEmail = $user?->email;
    $initial = $displayName ? mb_strtoupper(mb_substr($displayName, 0, 1)) : null;
@endphp

@once
    <style>
        .badge-shine::after {
            content: '';
            position: absolute;
            inset: -1px;
            border-radius: inherit;
            background: linear-gradient(
                120deg,
                rgba(255,255,255,0) 0%,
                rgba(255,255,255,0.25) 30%,
                rgba(255,255,255,0.9) 50%,
                rgba(255,255,255,0.25) 70%,
                rgba(255,255,255,0) 100%
            );
            background-size: 320% 120%;
            mix-blend-mode: screen;
            opacity: 1;
            filter: drop-shadow(0 0 8px rgba(255,255,255,0.4));
            pointer-events: none;
            animation: badgeShimmer 2.8s linear infinite;
        }
        @keyframes badgeShimmer {
            0% { background-position: -80% 50%; }
            100% { background-position: 200% 50%; }
        }
    </style>
@endonce

<header class="sticky-header sticky top-[64px] lg:top-0 z-40 relative bg-gradient-to-b from-white via-[#F6F9F8] to-[#EDF3F2] border-b border-[#d0e4de]">
    <div class="mx-auto w-full max-w-6xl px-6 py-4 flex flex-wrap items-center justify-between gap-4">
        <div class="space-y-2 w-full lg:w-auto mt-1">
            <div class="flex flex-wrap items-center gap-2 mb-1">
                <span class="badge-shine relative inline-flex items-center rounded-full px-2.5 py-0.5 text-[9px] font-semibold uppercase tracking-[0.38em] text-[#1F3B42] shadow-sm">
                    <span class="absolute inset-0 rounded-full bg-gradient-to-r from-[#7fe9b3] via-[#8fd8ff] to-[#ffe0ba] opacity-80 blur-[1px]"></span>
                    <span class="relative">{{ $badgePrimary }}</span>
                </span>
                <span class="text-[9px] font-semibold uppercase tracking-[0.38em] brand-zinus">• {{ $badgeSecondary }}</span>
            </div>
            <div class="space-y-1">
                <h2 class="text-2xl font-semibold text-[#0C1F2C] leading-tight tracking-tight mb-0.5">{{ $title }}</h2>
                <div class="inline-flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-[#e5f8ef] text-[#118A58] px-2.5 py-0.5 text-sm font-semibold shadow-sm transition-all duration-200 hover:bg-[#d3f3e5]">
                        <svg class="h-4 w-4 text-[#118A58] mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2l7 4v6c0 5-3 9-7 10-4-1-7-5-7-10V6l7-4z" />
                            <path d="M9 12l2 2 4-4" />
                        </svg>
                        {{ $description }}
                    </span>
                </div>
            </div>
        </div>

        @if ($user)
            <div class="flex items-center gap-2.5 lg:mt-0">
                <div x-data="{ open: false }" class="relative">
                    <button
                        type="button"
                        @click="open = !open"
                        class="flex items-center gap-2.5 rounded-full border border-[#c5e5d0] bg-white px-3.5 py-2 text-left shadow-[0_10px_20px_rgba(35,69,93,0.1)] transition hover:border-[#53B77A] focus:outline-none"
                    >
                        <span class="h-8 w-8 rounded-full bg-gradient-to-br from-[#12824C] to-[#0F6D3F] text-white flex items-center justify-center text-sm font-semibold shadow-inner shadow-emerald-200/50 ring-2 ring-[#C5E5D0]">
                            {{ $initial }}
                        </span>
                        <span class="leading-tight pr-2">
                            <span class="block text-sm font-semibold text-[#0D1F2B]">{{ $displayName }}</span>
                            <span class="block text-xs text-[#4A5D68] truncate max-w-[180px]">{{ $displayEmail }}</span>
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
                            <p class="text-sm font-medium text-gray-900">{{ $displayName }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $displayEmail }}</p>
                        </div>
                        <div class="mt-1 border-t border-slate-100"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="flex w-full items-center gap-2 px-4 py-2 text-sm font-semibold text-[#12824C] transition hover:bg-[#EDF3F2]"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M13 16l4-4-4-4" />
                                    <path d="M3 12h14" />
                                    <path d="M7 5V4a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-6a2 2 0 0 1-2-2v-1" />
                                </svg>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</header>
