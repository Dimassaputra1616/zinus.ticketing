@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'iconPosition' => 'left',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 font-semibold tracking-tight transition-all duration-200 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-200 focus-visible:ring-offset-2 hover:shadow-md active:scale-[0.98]';
    $sizes = [
        'sm' => 'text-2xs rounded-lg px-3 py-2',
        'md' => 'text-sm rounded-xl px-4 py-2.5',
        'lg' => 'text-sm rounded-xl px-5 py-3',
    ];

    $variants = [
        'primary' => 'bg-brand-600 text-white shadow-button hover:-translate-y-0.5 hover:bg-emerald-700 active:translate-y-0',
        'ghost' => 'border border-ink-100 bg-white text-ink-700 shadow-sm hover:-translate-y-0.5 hover:border-brand-200 hover:text-brand-800',
        'soft' => 'border border-brand-100 bg-brand-50 text-brand-800 hover:-translate-y-0.5 hover:border-brand-200 hover:bg-brand-100',
        'muted' => 'border border-ink-100 bg-ink-50 text-ink-700 hover:-translate-y-0.5 hover:border-brand-100 hover:text-ink-900',
    ];

    $classes = collect([
        $base,
        $sizes[$size] ?? $sizes['md'],
        $variants[$variant] ?? $variants['primary'],
    ])->implode(' ');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon && $iconPosition === 'left')
            <span class="text-[0.95em]">{!! $icon !!}</span>
        @endif
        <span>{{ $slot }}</span>
        @if ($icon && $iconPosition === 'right')
            <span class="text-[0.95em]">{!! $icon !!}</span>
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon && $iconPosition === 'left')
            <span class="text-[0.95em]">{!! $icon !!}</span>
        @endif
        <span>{{ $slot }}</span>
        @if ($icon && $iconPosition === 'right')
            <span class="text-[0.95em]">{!! $icon !!}</span>
        @endif
    </button>
@endif
