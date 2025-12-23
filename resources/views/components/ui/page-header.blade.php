@props([
    'title',
    'subtitle' => null,
    'eyebrow' => null,
    'meta' => [],
])

<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-xl border border-slate-100 bg-white px-6 py-5 shadow-sm reveal-on-scroll transition ease-[cubic-bezier(.22,.61,.36,1)] duration-300']) }}>
    <div class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-200/60 via-brand-400/70 to-transparent"></div>
    <div class="pointer-events-none absolute -left-10 top-6 h-32 w-32 rounded-full bg-brand-100/50 blur-3xl"></div>
    <div class="pointer-events-none absolute -right-10 -bottom-10 h-32 w-32 rounded-full bg-brand-50/70 blur-3xl"></div>

    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div class="space-y-3">
            @if ($eyebrow)
                <span class="inline-flex items-center gap-2 rounded-full border border-brand-100 bg-brand-50 ps-4 pe-3.5 py-1.5 text-sm font-semibold uppercase tracking-[0.22em] text-brand-800">
                    {{ $eyebrow }}
                </span>
            @endif
            <div class="space-y-2">
                <h3 class="text-xl font-semibold tracking-tight text-ink-900">{{ $title }}</h3>
                @if ($subtitle)
                    <p class="text-sm text-ink-500 sm:text-base">{{ $subtitle }}</p>
                @endif
            </div>

            @if (! empty($meta))
                <div class="flex flex-wrap gap-2 text-sm">
                    @foreach ($meta as $label => $value)
                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-100 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700">
                            <span class="h-2 w-2 rounded-full bg-brand-400"></span>
                            <span>{{ $value }}</span>
                            <span class="text-slate-400 uppercase tracking-[0.2em]">{{ $label }}</span>
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        @if (trim($slot))
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 lg:ml-auto lg:justify-end">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>
