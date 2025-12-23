@props([
    'title' => null,
    'subtitle' => null,
    'actions' => null,
    'padding' => 'lg',
    'icon' => null,
    'headerPadding' => null,
    'bodyPadding' => null,
])

@php
    $paddingKey = $padding ?? 'lg';

    $bodyPaddingClass = $bodyPadding !== null
        ? $bodyPadding
        : ([
            'sm' => 'px-5 py-4',
            'md' => 'p-6 md:p-8',
            'lg' => 'p-6 md:p-8',
            'xl' => 'p-6 md:p-8',
        ][$paddingKey] ?? 'p-6 md:p-8');

    $headerPaddingClass = $headerPadding !== null
        ? $headerPadding
        : match ($paddingKey) {
            'sm' => 'px-5 py-4',
            'md' => 'px-6 md:px-8 py-5',
            'lg' => 'px-6 md:px-8 py-5',
            'xl' => 'px-6 md:px-8 py-5',
            default => 'px-6 md:px-8 py-5',
        };
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-100 bg-white shadow-[0_6px_28px_rgba(0,0,0,0.05)] reveal-on-scroll animate-fade-up transition-all duration-200 ease-out hover:-translate-y-0.5 hover:shadow-[0_8px_32px_rgba(0,0,0,0.07)]']) }} data-scroll-animate>
    @if ($title || $subtitle || $actions)
        <div class="flex flex-col gap-3 border-b border-slate-100 {{ $headerPaddingClass }} md:flex-row md:items-center md:justify-between">
            <div class="space-y-1.5">
                @if ($title)
                    <h3 class="text-xl font-semibold text-neutral-900 tracking-tight flex items-center gap-2 transition-transform duration-150 ease-out hover:-translate-y-[0.5px]">
                        @if ($icon)
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                                {!! $icon !!}
                            </span>
                        @endif
                        <span>{{ $title }}</span>
                    </h3>
                @endif
                @if ($subtitle)
                    <p class="text-sm font-medium text-ink-600">{{ $subtitle }}</p>
                @endif
            </div>
            @if ($actions)
                <div class="flex flex-wrap items-center gap-2 md:justify-end">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div class="{{ $bodyPaddingClass }}">
        {{ $slot }}
    </div>
</div>
