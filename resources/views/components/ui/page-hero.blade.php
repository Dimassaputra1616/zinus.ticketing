@props([
    'pill' => null,
    'brand' => null,
    'eyebrow' => null,
    'title' => null,
    'description' => null,
    'badges' => [],
])

@php
    $badgeItems = collect($badges)
        ->map(function ($badge) {
            return is_array($badge)
                ? ['label' => $badge['label'] ?? '', 'dot' => $badge['dot'] ?? '#10b981']
                : ['label' => (string) $badge, 'dot' => '#10b981'];
        })
        ->filter(fn ($badge) => $badge['label'] !== '')
        ->all();
@endphp

<section class="relative overflow-hidden rounded-3xl border border-emerald-100/80 bg-gradient-to-r from-emerald-50 via-white to-emerald-50 px-5 lg:px-6 py-6 shadow-md">
    <div class="pointer-events-none absolute -left-10 -top-16 h-52 w-52 rounded-full bg-emerald-200/20 blur-3xl"></div>
    <div class="pointer-events-none absolute -right-12 -bottom-24 h-64 w-64 rounded-full bg-teal-200/25 blur-3xl"></div>

    <div class="relative flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
        <div class="space-y-4">
            @if ($pill || $brand)
                <div class="flex flex-wrap items-center gap-2 ps-0.5 text-[11px] font-semibold uppercase tracking-[0.28em] text-emerald-700">
                    @if ($pill)
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1 text-emerald-700 shadow-sm ring-1 ring-white/60">
                            {{ $pill }}
                        </span>
                    @endif
                    @if ($brand)
                        <span class="text-emerald-600">{{ $brand }}</span>
                    @endif
                </div>
            @endif

            <div class="space-y-2">
                @if ($eyebrow)
                    <p class="text-[0.75rem] font-semibold uppercase tracking-[0.3em] text-emerald-600">{{ $eyebrow }}</p>
                @endif
                @if ($title)
                    <h1 class="text-3xl sm:text-4xl font-semibold text-ink-900 leading-snug">{{ $title }}</h1>
                @endif
                @if ($description)
                    <p class="text-sm sm:text-base text-ink-700 max-w-3xl">
                        {{ $description }}
                    </p>
                @endif
            </div>

            @if ($badgeItems)
                <div class="flex flex-wrap items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-emerald-800">
                    @foreach ($badgeItems as $badge)
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/90 px-3 py-1 shadow-sm ring-1 ring-white/70">
                            <span class="h-2 w-2 rounded-full" style="background-color: {{ $badge['dot'] }}"></span>
                            {{ $badge['label'] }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        @if (isset($side))
            <div class="w-full max-w-sm rounded-2xl border border-white/70 bg-white/90 p-5 shadow-lg backdrop-blur-md">
                {{ $side }}
            </div>
        @endif
    </div>
</section>
