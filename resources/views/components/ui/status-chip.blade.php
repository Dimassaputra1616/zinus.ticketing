@props([
    'status',
    'label' => null,
])

@php
    $map = [
        'open' => [
            'classes' => 'bg-amber-50 text-amber-700 border border-amber-100',
            'dot' => 'bg-amber-500',
        ],
        'in_progress' => [
            'classes' => 'bg-sky-50 text-sky-700 border border-sky-100',
            'dot' => 'bg-sky-500',
        ],
        'progress' => [
            'classes' => 'bg-sky-50 text-sky-700 border border-sky-100',
            'dot' => 'bg-sky-500',
        ],
        'resolved' => [
            'classes' => 'bg-emerald-50 text-emerald-700 border border-emerald-100',
            'dot' => 'bg-emerald-600',
        ],
        'done' => [
            'classes' => 'bg-emerald-50 text-emerald-700 border border-emerald-100',
            'dot' => 'bg-emerald-600',
        ],
        'closed' => [
            'classes' => 'bg-emerald-50 text-emerald-700 border border-emerald-100',
            'dot' => 'bg-emerald-600',
        ],
    ];

    $tone = $map[$status] ?? [
        'classes' => 'bg-gray-100 text-gray-700 border border-gray-200',
        'dot' => 'bg-gray-500',
    ];

    $displayLabel = $label ?? str_replace('_', ' ', ucfirst($status));
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-3.5 py-1 text-sm font-semibold shadow-sm ' . $tone['classes']]) }}>
    <span class="h-2.5 w-2.5 rounded-full {{ $tone['dot'] }}"></span>
    {{ $displayLabel }}
</span>
