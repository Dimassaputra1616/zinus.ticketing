@props([
    'label',
    'value',
    'description' => null,
    'pill' => null,
    'tone' => 'brand',
    'icon' => null,
])

@php
    $tones = [
        'brand' => [
            'bg' => 'from-brand-50/70 via-white to-white',
            'border' => 'border-brand-100/80',
            'pill' => 'bg-brand-50 text-brand-800 ring-brand-100',
            'icon' => 'text-brand-700 bg-white border-brand-50',
        ],
        'amber' => [
            'bg' => 'from-amber-50/70 via-white to-white',
            'border' => 'border-amber-100/80',
            'pill' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'icon' => 'text-amber-700 bg-white border-amber-50',
        ],
        'sky' => [
            'bg' => 'from-sky-50/70 via-white to-white',
            'border' => 'border-sky-100/80',
            'pill' => 'bg-sky-50 text-sky-700 ring-sky-100',
            'icon' => 'text-sky-700 bg-white border-sky-50',
        ],
        'slate' => [
            'bg' => 'from-ink-50/70 via-white to-white',
            'border' => 'border-ink-100/80',
            'pill' => 'bg-ink-50 text-ink-700 ring-ink-100',
            'icon' => 'text-ink-700 bg-white border-ink-50',
        ],
    ];

    $tone = $tones[$tone] ?? $tones['brand'];
@endphp

<div class="relative h-full overflow-hidden rounded-xl border {{ $tone['border'] }} bg-gradient-to-br {{ $tone['bg'] }} p-6 shadow-sm shadow-ink-900/5 reveal-on-scroll" data-scroll-animate>
    <div class="flex flex-col gap-3">
        <div class="flex items-start justify-between gap-3">
            <div class="space-y-3">
                <p class="text-[0.7rem] font-semibold uppercase tracking-[0.26em] text-ink-400">{{ $label }}</p>
                <p class="text-4xl font-semibold text-ink-900 leading-tight">{{ $value }}</p>
                @if ($description)
                    <p class="text-sm text-ink-600">{{ $description }}</p>
                @endif
            </div>

            @if ($icon)
                <span class="flex h-10 w-10 items-center justify-center rounded-xl border {{ $tone['icon'] }} bg-white/80 shadow-inner shadow-ink-100/60">
                    {!! $icon !!}
                </span>
            @endif
        </div>

        @if ($pill)
            <div class="inline-flex items-center gap-2 rounded-full {{ $tone['pill'] }} px-3 py-1 text-2xs font-semibold uppercase tracking-[0.22em] ring-1">
                {{ $pill }}
            </div>
        @endif
    </div>
</div>
